<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Sjoerd Zonneveld <szonneveld@bitpatroon.nl>
 *  Date: 29-8-2017 14:48
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
use BPN\BpnExpiringFeUsers\Service\DateService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

class ConfigRepository extends Repository
{
    const TABLE = 'tx_bpnexpiringfeusers_config';

    const FIELD_UID = 'uid';
    const FIELD_PID = 'pid';
    const FIELD_SYSFOLDER = 'sysfolder';
    const FIELD_MEMBEROF = 'memberOf';
    const FIELD_AND_OR = 'andor';
    const FIELD_ACTION = 'todo';
    const FIELD_REACTIVATE_LINK = 'reactivate_link';

    const TODO_MAIL = 1;
    const ACTION_MAIL = 1;
    const ACTION_DISABLE = 2;
    const ACTION_DELETE = 3;
    const ACTION_REMOVE_GROUP = 4;
    const ACTION_EXPIRE = 5;

    /**
     * @var DateService
     */
    private $dateService;

    public function injectDateService(DateService $dateService)
    {
        $this->dateService = $dateService;
    }

    /** @var ExpiringGroupRepository */
    protected $expiringGroupRepository;

    public function injectExpiringGroupRepository(ExpiringGroupRepository $expiringGroupRepository)
    {
        $this->expiringGroupRepository = $expiringGroupRepository;
    }

    public function getConfigsByActionByPid(int $action, int $pid, int $reactivateLink = 1)
    {
        $table = self::TABLE;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(self::FIELD_SYSFOLDER, $pid),
                $queryBuilder->expr()->eq(self::FIELD_ACTION, $action),
                $queryBuilder->expr()->eq(self::FIELD_REACTIVATE_LINK, $reactivateLink),
            );

        return $queryBuilder->execute()->fetchAllAssociative();
    }

    /**
     * Finds the matching users for a job record.
     *
     * @param array    $config       : row with job record info
     * @param bool     $preview      true to preview. False to show for real!
     * @param int|null $userId
     * @param bool     $allowExpired true to include expired users
     *
     * @return array $users: array with all users found
     */
    public function findMatchingUsers(Config $config, $preview = false, $userId = null, $allowExpired = false) : array
    {
        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($rec);

        // check whether compatible extension is loaded
        $exp_fe_groups = ExtensionManagementUtility::isLoaded('itypo_expiring_fe_groups');

        if (!$preview && $this->dateService->isSummerAndExcluded($config)) {
            return [];
        }

        $users = $this->getUserByConfig($config);

        $users = [];

        foreach ($rows as $currentUserId => $row) {
            if ($exp_fe_groups && 1 == $config['condition20'] && $config['expiringGroup']) {    // now filter the users which are not a member of the group at all or not expiring within the set days
                $expiringGroups = $expiringGroupRepository->getActiveExpiringGroups(
                    $row['tx_itypoexpiringfegroups_groups']
                );
                foreach ($expiringGroups as $expiringGroup) {
                    // check whether this record actually concerns the selected group, the query above might accidentally select the wrong user because of the LIKE (can match in timestamp)
                    if (false !== array_search($expiringGroup->getUid(), $expGrList)) {
                        // it matches, now check if this group membership is about to expire
                        if ($expiringGroup->getEnd() > time() && $expiringGroup->getEnd() < $daysfuture) {
                            // Determine duration of expiring group (days)

                            $durationExpiringGroupDays = ($expiringGroup->getEnd() - $expiringGroup->getStart(
                                    )) / 86400;
                            if ($durationExpiringGroupDays < (int)$config['days']) {
                                continue;
                            }

                            // 1) skip users already in sentlog younger then extend_by days minus days
                            // 2) also check if there isnt a record for the same group already with a newer exp date, if so, dont mail
                            // 3) skip users already in the sent array, could happen when it has 2 memberships to this group which will expire soon. (done by uid as key)
                            if (
                                !mailAction::checkSentLog(
                                    $config,
                                    $currentUserId
                                ) && !mailAction::checkForNewerExpRecord(
                                    $config,
                                    $row,
                                    $expiringGroup->getUid(),
                                    $daysfuture
                                )) {
                                $users[$currentUserId] = $row;
                            }
                        }
                    }
                }
            } elseif ('1' == $config['todo'][0]) { // specific filter for mailAction
                // skip users already in sentlog younger then extend_by days minus days
                if (!mailAction::checkSentLog($config, $currentUserId)) {
                    $users[] = $row;
                }
            } elseif ('5' == $config['todo'][0]) {
                // specific filter for expireAction, skip users already in sentlog
                if (!tx_bpnexpiringfeusers_helpers::isInSentLog($config['uid'], $currentUserId, $config['testmode'])) {
                    $users[] = $row;
                }
            } else {
                // for all other actions, skip users already in sentlog when testmode is on. otherwise testmode would always affect the first batch of users.
                if ('1' == $config['testmode']) {
                    if (
                    !tx_bpnexpiringfeusers_helpers::isInSentLog(
                        $config['uid'],
                        $currentUserId,
                        $config['testmode']
                    )) {
                        $users[] = $row;
                    }
                } else {
                    $users[] = $row;
                }
            }

            // make sure we never select more users then the limiter allows.
            if (count($users) >= $config['limiter']) {
                break;
            }
        }

        return $users;
    }

}
