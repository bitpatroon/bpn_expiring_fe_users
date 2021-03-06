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

use BPN\BpnExpiringFeUsers\Domain\Model\FrontEndUserGroup;

class FrontEndUserGroupRepository extends \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
{
    const TABLE = 'fe_users';

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

    public function getGroupName(int $uid) : ?string
    {
        /** @var FrontEndUserGroup $group */
        $group = $this->findByUid($uid);
        if ($group) {
            return $group->getTitle();
        }

        return null;
    }

    /**
     * @param int[] $uids
     *
     * @return string[]
     */
    public function getGroupNames(array $uids) : array
    {
        $result = [];
        foreach($uids as $uid){
            $result[$uid] = $this->getGroupName($uid);
        }
        return $result;
    }
}
