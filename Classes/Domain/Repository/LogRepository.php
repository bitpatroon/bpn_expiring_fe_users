<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 20-5-2021 15:49
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

use BPN\BpnExpiringFeUsers\Controller\ExtendController;
use BPN\BpnExpiringFeUsers\Domain\Model\Config;
use BPN\BpnExpiringFeUsers\Traits\RepositoryTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

class LogRepository extends Repository
{
    use RepositoryTrait;

    private const TABLE = 'tx_bpnexpiringfeusers_log';

    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    public function addInfo(Config $config, int $userId, string $message)
    {
        $this->addLog($config, $userId, 'info', $message);
    }

    public function addError(Config $config, string $message)
    {
        $this->addLog($config, 0, 'error', $message);
    }

    public function addLog(Config $config, int $userId, string $action, string $message)
    {
        $insertFields = [
            'crdate'  => time(),
            'job'     => $config->getUid(),
            'fe_user' => $userId,
            'action'  => $action,
            'msg'     => $message,
        ];

        if ($config->getTestmode()) {
            $insertFields['testmode'] = '1';
        }

        $table = self::TABLE;
        /** Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $connection->insert($table, $insertFields);

        if ($this->output) {
            $insertFields = array_slice($insertFields, 1);
            $this->output->writeln(
                sprintf(
                    '%s (Job:%s user:%s): %s',
                    $insertFields['action'],
                    $insertFields['job'],
                    $insertFields['fe_user'],
                    $insertFields['msg'],
                )
            );
        }
    }

    /**
     * Checks if a fe_user is found in a specific jobs sentlog.
     */
    public function isInSentLog(int $job, int $userId, int $testmode): bool
    {
        $table = self::TABLE;

        /** Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE);
        $rows = $connection->select(
            ['uid'],
            $table,
            [
                'job'      => $job,
                'fe_user'  => $userId,
                'testmode' => $testmode,
            ]
        )->fetchAssociative();

        return !empty($rows);
    }

    /**
     * @deprecated Use isInSentLog
     */
    public function hasLogEntries(int $job, int $user, int $testmode): bool
    {
        return $this->isInSentLog($job, $user, $testmode);
    }

    public function getByJob(int $uid)
    {
        $table = self::TABLE;

        /** @var Connection $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);

        $data = $queryBuilder
            ->select(['*'], $table, ['job' => $uid, 'deleted' => 0])
            ->fetchAssociative();

        $rows = [];
        if ($data) {
            foreach ($data as $row) {
                $rows[(int) $row['uid']] = $row;
            }
        }

        return $rows;
    }

    public function getByJobByUserWithUser(int $uid)
    {
        $table = self::TABLE;
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $queryBuilder
            ->select(
                self::TABLE .  '.*',
                'fe_users.email',
                'fe_users.first_name',
                'fe_users.middle_name',
                'fe_users.last_name',
                'fe_users.email',
                'fe_users.name',
                'fe_users.username'
            )
            ->from($table)
            ->leftJoin(
                $table,
                'fe_users',
                'fe_users',
                $queryBuilder->expr()->eq(
                    $table.'.fe_user',
                    $queryBuilder->quoteIdentifier('fe_users.uid')
                )
            )
            ->where(
                $queryBuilder->expr()->eq($table.'.job', $uid),
                $queryBuilder->expr()->eq($table.'.deleted', 0),
            )
            ->orderBy($table.'.crdate', 'DESC')
            ->setMaxResults(1000);

        return $queryBuilder->execute()->fetchAllAssociative();
    }

    public function findByJobUser(?int $jobId, int $userId, int $testmode, bool $newerThan)
    {
        $table = self::TABLE;
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('job', $jobId),
                $queryBuilder->expr()->eq('fe_user', $userId),
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('testmode', $testmode),
                $queryBuilder->expr()->gt('crdate', $newerThan)
            );

        return $queryBuilder->execute()->fetchAssociative();
    }

    public function setInput($input): LogRepository
    {
        if ($input) {
            $this->input = $input;
        }

        return $this;
    }

    public function setOutput($output): LogRepository
    {
        if ($output) {
            $this->output = $output;
        }

        return $this;
    }

    /**
     * Check if an account is already extended by any job
     * Determines if an account has already been extended by a particular job.
     * Sees if there already is an extend entry between time of link generation and max validity days.
     *
     * @return bool true if account has already been extended by this job, false if not
     */
    public function isAccountAlreadyExtended(Config $config, int $userId, int $linkTimeStamp)
    {
        $monthAhead = strtotime('+31 days', $linkTimeStamp);

        $table = self::TABLE;
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('job', $config->getUid()),
                $queryBuilder->expr()->eq('fe_user', $userId),
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('action', $queryBuilder->createNamedParameter(ExtendController::ACTION)),
                $queryBuilder->expr()->lt('crdate', $monthAhead),
                $queryBuilder->expr()->gt('crdate', $linkTimeStamp)
            );

        $data = $queryBuilder->execute()->fetchAssociative();

        return !empty($data) ? true : false;
    }

    /**
     * This function checks if a user was already mailed by a job, and if so, if it was long enough ago to do it again.
     */
    public function checkSentLog(Config $config, int $userId): bool
    {
        $testmode = $config->getTestmode();
        $daysAgo = $config->getExtendBy() - $config->getDays();
        $hasToBe = strtotime('-'.$daysAgo.' days');

        if ($config->getExtendBy() <= 0) {
            return true;
        }

        $row = $this->findByJobUser($config->getUid(), $userId, $testmode, $hasToBe);

        return $row ? true : false;
    }
}
