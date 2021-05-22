<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Sjoerd Zonneveld <typo3@bitpatroon.nl>
 *  Date: 31-1-2018 14:55
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

use BPN\BpnExpiringFeUsers\Domain\Model\ExpiringGroupModel;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

class ExpiringGroupRepository extends Repository
{
    const RE_GROUP_UIDS = '/(\d+)\|(\d+)\|(\d+)\**/';
    const FIELD = 'tx_bpnexpiringfegroups_groups';

    /**
     * Gets the group uids which are active
     *
     * @param string $expiringGroupsList An expiring groups list
     *
     * @return int[]
     * @throws \Exception
     */
    public function getActiveExpiringGroupsUids($expiringGroupsList)
    {
        $result = [];
        $now = time();
        $expiringGroups = $this->getAllExpiringGroups($expiringGroupsList);
        foreach ($expiringGroups as $expiringGroupModel) {
            if ($expiringGroupModel->getStart() > $now) {
                continue;
            }
            $end = $expiringGroupModel->getEnd();
            if (!empty($end) && ($end < $now)) {
                continue;
            }
            $uid = $expiringGroupModel->getUid();
            $result[$uid] = $uid;
        }

        return $result;
    }

    /**
     * Gets the group uids which are active
     *
     * @param string $expiringGroupsList An expiring groups list
     *
     * @return ExpiringGroupModel[]
     * @throws \Exception
     */
    public function getActiveExpiringGroups($expiringGroupsList)
    {
        $result = [];
        $now = time();
        $expiringGroups = $this->getAllExpiringGroups($expiringGroupsList);
        foreach ($expiringGroups as $expiringGroupModel) {
            if ($expiringGroupModel->getStart() > $now) {
                continue;
            }
            $end = $expiringGroupModel->getEnd();
            if (!empty($end) && ($end < $now)) {
                continue;
            }
            $result[] = $expiringGroupModel;
        }

        return $result;
    }

    /**
     * Gets the group uids which are active
     *
     * @param string $expiringGroupsList An expiring groups list
     *
     * @return ExpiringGroupModel[]
     */
    public function getAllExpiringGroups(?string $expiringGroupsList) : array
    {
        $result = [];

        if (!$expiringGroupsList) {
            return $result;
        }

        $matches = [];
        if (!preg_match_all(self::RE_GROUP_UIDS, $expiringGroupsList, $matches)) {
            return $result;
        }

        [, $uids, $startTimes, $endTimes] = $matches;

        foreach ($uids as $index => $uid) {
            if (!$uid || !isset($startTimes[$index]) || !isset($endTimes[$index])) {
                continue;
            }

            $start = (int)$startTimes[$index];
            $end = (int)$endTimes[$index];

            $expiringGroupModel = new ExpiringGroupModel();
            $expiringGroupModel
                ->setUid($uid)
                ->setStart($start)
                ->setEnd($end);
            $result[] = $expiringGroupModel;
        }

        return $result;
    }

    /**
     * Converts the expiring groups for a user to an array.
     */
    public function getExpiringGroups(string $expiringGroupsList) : array
    {
        $result = [];

        foreach (GeneralUtility::trimExplode('*', $expiringGroupsList) as $entry) {
            if ($entry) {
                $result[] = GeneralUtility::trimExplode('|', $entry);
            }
        }

        return $result;
    }

    /**
     * Converts the expiring groups for a user to an array.
     *
     * @deprecated Use getExpiringGroups
     */
    public function getExpiringGroupsArray(string $expiringGroupsList) : array
    {
        return $this->getExpiringGroups($expiringGroupsList);
    }

    /**
     * Checks if a user has a newer exp group record for the groupid found and the days in the future given.
     *
     * @param $userRecord
     * @param $groupId
     * @param $daysInTheFuture
     *
     * @return bool
     */
    public function checkForNewerExpRecord(array $userRecord, int $groupId, int $daysInTheFuture)
    {
        $expiringGroups = $this->getExpiringGroups($userRecord[self::FIELD]);

        foreach ($expiringGroups as $expiringGroup) {
            if (($expiringGroup[0] == $groupId) && $expiringGroup[2] > $daysInTheFuture) {
                return true;
            }
        }

        return false;
    }
}
