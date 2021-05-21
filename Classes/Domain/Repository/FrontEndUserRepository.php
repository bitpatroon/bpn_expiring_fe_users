<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 20-5-2021 15:56
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace BPN\BpnExpiringFeUsers\Domain\Repository;

use BPN\BpnExpiringFeUsers\Domain\Models\Config;
use BPN\BpnExpiringFeUsers\Traits\RepositoryTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FrontEndUserRepository extends \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
{
    use RepositoryTrait;

    const TABLE = 'fe_users';
    const CONDITION_AND = 'AND';
    const CONDITION_OR = 'OR';

    /**
     * @var \BPN\BpnExpiringFeUsers\Domain\Repository\LogRepository
     */
    private $logRepository;

    /**
     * @param \BPN\BpnExpiringFeUsers\Domain\Repository\LogRepository
     */
    public function injectLogRepository(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    /** @var ExpiringGroupRepository */
    protected $expiringGroupRepository;

    public function injectExpiringGroupRepository(ExpiringGroupRepository $expiringGroupRepository)
    {
        $this->expiringGroupRepository = $expiringGroupRepository;
    }

    /**
     * Sets the endtime for an account.
     *
     * @param int    $uid     : Uid of the fe_user.
     * @param string $endtime : Timestamp.
     * @param array  $rec     : Complete sql row of job.
     *
     * @return    void
     */
    public function setAccountExpirationDate(int $uid, string $endtime, array $record)
    {
        $table = self::TABLE;

        $message = 'fe_user has been set to expire on ' . date('d-m-y H:i:s', $endtime);
        $action = 'expiring';

        if ((int)$record['testmode'] || $record['email_test']) {
            $action = 'testexpiring';
        } else {
            $updateFields = [
                'tstamp'  => time(),
                'endtime' => $endtime,
            ];

            $where = ['uid' => $uid];

            /** Connection $connection */
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE);
            $connection->update($table, $updateFields, $where);
        }

        $this->logRepository->log($record, $uid, $action, $message);
    }

    /**
     * Gets all users for this configuration
     */
    public function getUserByConfig(Config $config, int $userId = 0, bool $allowExpired = false) : array
    {
        $removeQuerySettings = false;
        $table = self::TABLE;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $whereAnd = [];
        if ($config->getSysfolderAsArray()) {
            $whereAnd[] = $queryBuilder->expr()->in(
                'pid',
                $queryBuilder->createNamedParameter($config->getSysfolderAsArray(), Connection::PARAM_INT_ARRAY)
            );
        }

        // must be member of groups
        if ($config->getMemberOf()) {
            // Sometimes its an array but when executing the scheduled task its a string...sigh..
            $andOr = $config->getAndorAsString();

            $groupConditions = [];
            $memberOf = $config->getMemberOfAsArray();
            if ($memberOf) {
                foreach ($memberOf as $group) {
                    // expected group is an ID

                    $groupConditions[] = $queryBuilder->expr()->inSet(
                        'usergroup',
                        $queryBuilder->createNamedParameter($group, Connection::PARAM_INT)
                    );
                }

                if ($andOr === self::CONDITION_AND) {
                    $whereAnd[] = $queryBuilder->expr()->andX(...$groupConditions);
                } else {
                    $whereAnd[] = $queryBuilder->expr()->orX(...$groupConditions);
                }
            }
        }

        // must not be a member of group
        if ($config->getNoMemberOf()) {
            // Sometimes its an array but when executing the scheduled task its a string...sigh..
            $andOr = $config->getAndorNotAsString();

            $groupConditions = [];
            $memberOf = $config->getNoMemberOfAsArray();
            if ($memberOf) {
                foreach ($memberOf as $group) {
                    // expected group is an ID

                    $groupConditions[] = $queryBuilder->expr()->inSet(
                        'usergroup',
                        $queryBuilder->createNamedParameter($group, Connection::PARAM_INT)
                    );
                }

                if ($andOr === self::CONDITION_AND) {
                    $whereAnd[] = $queryBuilder->expr()->andX(...$groupConditions);
                } else {
                    $whereAnd[] = $queryBuilder->expr()->orX(...$groupConditions);
                }
            }
        }

        $time = time();                                         // current timestamp
        $daysago = strtotime('-' . $config->getDays() . ' days');            // timestamp of x days ago
        $daysfuture = strtotime('+' . $config->getDays() . ' days');        // timestamp of x days in the future

        // queries for each checkbox
        if ($config->getCondition1()) {
            // User has not logged in for..
            $whereAnd[] = $queryBuilder->expr()->neq('lastlogin', 0);
            $whereAnd[] = $queryBuilder->expr()->lt('lastlogin', $daysago);
        }
        if ($config->getCondition2()) {
            // Account older than..
            $whereAnd[] = $queryBuilder->expr()->lt('crdate', $daysago);
        }
        if ($config->getCondition3()) {
            // Account is disabled.
            $whereAnd[] = $queryBuilder->expr()->eq('disable', 1);
            $removeQuerySettings = true;
        }
        if ($config->getCondition4()) {
            // Account expires within..{
            $whereAnd[] = $queryBuilder->expr()->neq('endtime', 0);
            $whereAnd[] = $queryBuilder->expr()->gt('endtime', $time);
            $whereAnd[] = $queryBuilder->expr()->lt('endtime', $daysfuture);
        }
        if ($config->getCondition5()) {
            // Account expires within..{
            $whereAnd[] = $queryBuilder->expr()->neq('endtime', 0);
            $whereAnd[] = $queryBuilder->expr()->lt('endtime', $time);
            $removeQuerySettings = true;
        }
        if ($config->getCondition6()) {
            // Account has been expired for..
            $whereAnd[] = $queryBuilder->expr()->neq('endtime', 0);
            $whereAnd[] = $queryBuilder->expr()->lt('endtime', $time);
            $whereAnd[] = $queryBuilder->expr()->lt('endtime', $daysago);
            $removeQuerySettings = true;
        }
        if ($config->getCondition7()) {
            // Account has never logged in.
            $whereAnd[] = $queryBuilder->expr()->neq('lastlogin', 0);
        }
        if ($config->getCondition8()) {
            // Account has no expiration date.
            $whereAnd[] = $queryBuilder->expr()->eq('endtime', 0);
        }

        if ($this->epxiringGroupsEnabled() && $config->getCondition20() && $config->getExpiringGroup()) {
            $expiringGroups = $this->expiringGroupRepository->getAllExpiringGroups($config->getExpiringGroup());

            $expWhereOR = [];
            foreach ($expiringGroups as $expiringGroup) {
                $expWhereOR[] = $queryBuilder->expr()->like(
                    'tx_expiringfegroups_groups',
                    $queryBuilder->createNamedParameter($expiringGroup->getUid() . '|%', Connection::PARAM_INT)
                );
                $expWhereOR[] = $queryBuilder->expr()->like(
                    'tx_expiringfegroups_groups',
                    $queryBuilder->createNamedParameter('*' . $expiringGroup->getUid() . '|%', Connection::PARAM_INT)
                );
            }
            $whereAnd[] = $queryBuilder->expr()->orX(...$expWhereOR);
        }

        // regular conditions, hidden, deleted, starttime, endtime, etc (only when not using certain conditions)
        // cant use enableFields here because this function is static
        if (!$removeQuerySettings) {
            if (!$allowExpired) {
                $whereAnd[] = $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('endtime', 0),
                    $queryBuilder->expr()->gt('endtime', $time)
                );
            }

            $whereAnd[] = $queryBuilder->expr()->eq('disable', 0);
            $whereAnd[] = $queryBuilder->expr()->lt('starttime', 0);
        }
        // hide deleted!
        $whereAnd[] = $queryBuilder->expr()->eq('deleted', 0);

        $config->setExtendBy(50);

        if ($userId) {
            $whereAnd[] = $queryBuilder->expr()->eq('uid', $userId);
        }

        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(...$whereAnd);

        $data = $queryBuilder->execute()->fetchAllAssociative();

        $sql = $this->getFullStatement($queryBuilder);

        return $this->setResultIndexField($data);
    }

    private function epxiringGroupsEnabled()
    {
        return ExtensionManagementUtility::isLoaded('bpn_expiring_fe_groups');
    }
}

