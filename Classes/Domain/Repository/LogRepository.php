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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException;

class LogRepository extends RepositoryException
{
    private const TABLE = 'tx_bpnexpiringfeusers_log';

    /**
     * Writes a line in the extension log table.
     *
     * @param array  $record  : Array with info about the job
     * @param int    $userId  : fe_user uid
     * @param string $action  : which action, can be info, warning, error, mail, extend, etc
     * @param string $message : Any message
     */
    public function log(array $record, int $userId, string $action, string $message)
    {
        $insertFields = [
            'crdate' => time(),
            'job' => (int) $record['uid'],
            'fe_user' => $userId,
            'action' => $action,
            'msg' => $message,
        ];

        if ('1' === (int) $record['testmode']) {
            $insertFields['testmode'] = '1';
        }

        $table = self::TABLE;
        /** Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $connection->insert($table, $insertFields);
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
                'job' => $job,
                'fe_user' => $userId,
                'testmode' => $testmode,
            ]
        )->fetchAssociative();

        return false != empty($rows);
    }

    /**
     * @deprecated Use isInSentLog
     */
    public function hasLogEntries(int $job, int $user, int $testmode): bool
    {
        return $this->isInSentLog($job, $user, $testmode);
    }
}
