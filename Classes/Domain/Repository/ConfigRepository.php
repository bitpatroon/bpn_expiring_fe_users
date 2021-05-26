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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
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

    public function allowHiddenRecords()
    {
        $typo3QuerySettings = new Typo3QuerySettings();
        $typo3QuerySettings
            ->setIgnoreEnableFields(true)
            ->setIncludeDeleted(false)
            ->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($typo3QuerySettings);
    }

    public function allowAllStoragePages()
    {
        $typo3QuerySettings = new Typo3QuerySettings();
        $typo3QuerySettings
            ->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($typo3QuerySettings);
    }

    /**
     * Find a record by uid even if it is hidden or deleted.
     *
     * @param int       $uid
     * @param int|array $pid
     *
     * @return object
     */
    public function findByUidIncludingHidden($uid, int $pid = 0)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()
            ->setIgnoreEnableFields(true)
            ->setIncludeDeleted(false)
            ->setRespectStoragePage(false);
        if ($pid) {
            $query->getQuerySettings()->setStoragePageIds([$pid]);
        }
        $query->matching($query->equals('uid', $uid));

        return $query->execute()->getFirst();
    }
}
