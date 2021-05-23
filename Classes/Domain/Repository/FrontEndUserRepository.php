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

use BPN\BpnExpiringFeUsers\Domain\Model\Config;
use BPN\BpnExpiringFeUsers\Service\DateService;
use BPN\BpnExpiringFeUsers\Service\MailActionService;
use BPN\BpnExpiringFeUsers\Traits\RepositoryTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
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

    /** @var DateService */
    protected $dateService;

    public function injectDateService(DateService $dateService)
    {
        $this->dateService = $dateService;
    }

    /** @var MailActionService */
    protected $mailActionService;

    public function injectMailActionService(MailActionService $mailActionService)
    {
        $this->mailActionService = $mailActionService;
    }

    /**
     * Sets the endtime for an account.
     *
     * @param int    $uid     : Uid of the fe_user
     * @param string $endtime : Timestamp
     * @param array  $rec     : Complete sql row of job
     *
     * @return void
     */
    public function setAccountExpirationDate(int $uid, string $endtime, Config $config)
    {
        $table = self::TABLE;

        $message = 'fe_user has been set to expire on ' . date('d-m-y H:i:s', $endtime);
        $action = 'expiring';

        if ($config->getTestmode() || $config->getEmailTest()) {
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

        $this->logRepository->addLog($config, $uid, $action, $message);
    }

    /**
     * Gets all users for this configuration.
     */
    public function getUserByConfig(
        Config $config,
        int $userId = 0,
        bool $allowExpired = false,
        int $limit = 1000
    ) : array {
        $removeQuerySettings = false;
        $table = self::TABLE;
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

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

                if (self::CONDITION_AND === $andOr) {
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
                    $groupConditions[] = 'FIND_IN_SET(:groupId, ' . $queryBuilder->quoteIdentifier(
                            'usergroup'
                        ) . ') = 0';
                }
                $queryBuilder->setParameter('groupId', $group, Connection::PARAM_INT);

                if (self::CONDITION_AND === $andOr) {
                    $whereAnd[] = $queryBuilder->expr()->andX(...$groupConditions);
                } else {
                    $whereAnd[] =
                        $queryBuilder->expr()->orX(...$groupConditions);
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
            $whereAnd[] = $queryBuilder->expr()->eq('lastlogin', 0);
        }
        if ($config->getCondition8()) {
            // Account has no expiration date.
            $whereAnd[] = $queryBuilder->expr()->eq('endtime', 0);
        }

        if ($this->expiringGroupsEnabled() && $config->getCondition20() && $config->getExpiringGroup()) {
            //$expiringGroups = $this->expiringGroupRepository->getAllExpiringGroups($config->getExpiringGroup());

            $expiringGroups = GeneralUtility::intExplode(',', $config->getExpiringGroup());
            if ($expiringGroups) {
                $expWhereOR = [];
                foreach ($expiringGroups as $expiringGroupId) {
                    $expWhereOR[] = $queryBuilder->expr()->like(
                        'tx_expiringfegroups_groups',
                        $queryBuilder->createNamedParameter($expiringGroupId . '|%', Connection::PARAM_STR)
                    );
                    $expWhereOR[] = $queryBuilder->expr()->like(
                        'tx_expiringfegroups_groups',
                        $queryBuilder->createNamedParameter('%*' . $expiringGroupId . '|%', Connection::PARAM_STR)
                    );
                }
                if ($expWhereOR) {
                    $whereAnd[] = $queryBuilder->expr()->orX(...$expWhereOR);
                }
            }
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
            $whereAnd[] = $queryBuilder->expr()->lt('starttime', time());
        }

        if ($config->getExtendBy() <= 0) {
            $config->setExtendBy(50);
        }

        if ($userId) {
            $whereAnd[] = $queryBuilder->expr()->eq('uid', $userId);
        }

        if ($limit > 2500) {
            $limit = 2500;
        } elseif ($limit < 1) {
            $limit = 1;
        }

        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(...$whereAnd)
            ->setMaxResults($limit);

        $data = $queryBuilder->execute()->fetchAllAssociative();

        $this->setFullStatement($queryBuilder);

        return $this->setResultIndexField($data);
    }

    private function expiringGroupsEnabled()
    {
        return ExtensionManagementUtility::isLoaded('bpn_expiring_fe_groups');
    }

    /**
     * Finds the matching users for a job record.
     *
     * @param array $config       : row with job record info
     * @param bool  $preview      true to preview. False to show for real!
     * @param int   $userId
     * @param bool  $allowExpired true to include expired users
     *
     * @return array $users: array with all users found
     */
    public function findMatchingUsers(
        Config $config,
        bool $preview = false,
        int $userId = 0,
        bool $allowExpired = false
    ) : array {
        // check whether compatible extension is loaded
        $exp_fe_groups = ExtensionManagementUtility::isLoaded('bpn_expiring_fe_groups');

        if (!$preview && $this->dateService->isSummerAndExcluded((array)$config)) {
            return [];
        }

        $daysFuture = strtotime('+' . $config->getDays() . ' days');
        $rows = $this->getUserByConfig($config, $userId, $allowExpired);

        $users = [];
        foreach ($rows as $currentUserId => $user) {
            if ($exp_fe_groups && 1 == $config->getCondition20() && $config->getExpiringGroup()) {
                // now filter the users which are not a member of the group at all or not expiring within the set days
                if (!$user[ExpiringGroupRepository::FIELD]) {
                    continue;
                }
                $expiringGroups = $this->expiringGroupRepository->getActiveExpiringGroups(
                    $user[ExpiringGroupRepository::FIELD]
                );
                if ($expiringGroups) {
                    continue;
                }
                foreach ($expiringGroups as $expiringGroup) {
                    // it matches, now check if this group membership is about to expire
                    $endTime = $expiringGroup->getEnd();
                    if (!$endTime) {
                        continue;
                    }

                    if ($endTime < time()) {
                        continue;
                    }

                    if ($endTime > $daysFuture) {
                        continue;
                    }

                    $durationExpiringGroupDays = ($expiringGroup->getEnd() - $expiringGroup->getStart()) / 86400;
                    if ($durationExpiringGroupDays < (int)$config['days']) {
                        continue;
                    }

                    if ($this->logRepository->checkSentLog($config, $currentUserId)) {
                        continue;
                    }
                    if (
                    $this->expiringGroupRepository->checkForNewerExpRecord(
                        $user,
                        $expiringGroup->getUid(),
                        $daysFuture
                    )) {
                        continue;
                    }
                    $users[$currentUserId] = $user;
                }
            } else {
                switch ($config->getTodo()) {
                    case 1:
                        // specific filter for mailAction
                        // skip users already in sentlog younger then extend_by days minus days
                        if (!$this->logRepository->checkSentLog($config, $currentUserId)) {
                            $users[] = $user;
                        }
                        break;
                    case 5:
                        // specific filter for expireAction, skip users already in sentlog
                        if (!$this->logRepository->isInSentLog($config['uid'], $currentUserId, $config['testmode'])) {
                            $users[] = $user;
                        }
                        break;
                    default:
                        if ($config->getTestmode()) {
                            if (
                            !$this->logRepository->isInSentLog(
                                $config['uid'],
                                $currentUserId,
                                $config['testmode']
                            )) {
                                $users[] = $user;
                            }
                        } else {
                            $users[] = $user;
                        }
                        break;
                }
            }

            if (count($users) >= $config->getLimiter()) {
                break;
            }
        }

        return $users;
    }

    public function deleteUser(int $userId)
    {
        $table = self::TABLE;

        /** @var Connection $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);

        $queryBuilder
            ->update(
                $table,
                [
                    'tstamp'  => time(),
                    'deleted' => '1',
                ],
                ['uid' => $userId]
            );
    }

    public function disableUser(int $userId)
    {
        $table = self::TABLE;

        /** @var Connection $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);

        $queryBuilder
            ->update(
                $table,
                [
                    'tstamp'  => time(),
                    'disable' => '1',
                ],
                ['uid' => $userId]
            );
    }

    public function updateGroups(int $userId, string $newGroups)
    {
        $table = self::TABLE;

        /** @var Connection $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);

        $queryBuilder
            ->update(
                $table,
                [
                    'tstamp'    => time(),
                    'usergroup' => $newGroups,
                ],
                ['uid' => $userId]
            );
    }

    public function updateLastLogin(int $userId)
    {
        $table = self::TABLE;

        /** @var Connection $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);

        $queryBuilder
            ->update(
                $table,
                ['lastlogin' => time()],
                ['uid' => $userId]
            );
    }

    public function addExpiringGroup(int $userId, int $groupId) : int
    {
        $newEndTimeGroup = strtotime(sprintf("+%d days", $groupId));

        $groupEntry = sprintf("%d|%s|%s", $groupId, time(), $newEndTimeGroup);

        $table = self::TABLE;
        /** @var Connection $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);

        $userRecord = $queryBuilder->select([ExpiringGroupRepository::FIELD], $table, ['uid' => $userId]);
        if (!$userRecord) {
            return 0;
        }
        $currentExpiringGroupList = $userRecord[ExpiringGroupRepository::FIELD];

        $currentExpiringGroups = explode('*', $currentExpiringGroupList);
        $currentExpiringGroups[] = $groupEntry;
        $newGroups = implode('*', $currentExpiringGroups);

        $queryBuilder
            ->update(
                $table,
                [
                    'tstamp'                       => time(),
                    ExpiringGroupRepository::FIELD => $newGroups,
                ],
                ['uid' => $userId]
            );

        return $newEndTimeGroup;
    }

    public function extend(int $userId, int $extendWithDays = 31) : int
    {
        $table = self::TABLE;
        /** @var Connection $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);

        $userRecord = $queryBuilder
            ->select(['endtime'], $table, ['uid' => $userId])
            ->fetchAssociative();
        if (!$userRecord) {
            return 0;
        }
        $endTime = max((int)$userRecord['endtime'], time());
        $newEndTime = strtotime('+' . $extendWithDays . ' days', $endTime);
        // remove seconds
        $newEndTime = $newEndTime - ($newEndTime % 86400);

        $queryBuilder
            ->update(
                $table,
                [
                    'tstamp'  => time(),
                    'endtime' => $newEndTime
                ],
                ['uid' => $userId]
            );

        return $newEndTime;
    }
}
