<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 22-5-2021 16:22
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

namespace BPN\BpnExpiringFeUsers\Service;

use BPN\BpnExpiringFeUsers\Domain\Model\Config;

final class RemoveGroupActionService extends AbstractActionService
{
    protected $action = 'removed-group';

    protected $groupsToRemove;

    protected function validateJob(Config $config) : bool
    {
        if (!$config->getGroupsToRemove()) {
            $this->addError($config, 'No groups to remove set. Cannot process [1621700027899]');

            return false;
        }

        return true;
    }

    protected function beforeExecutingSingle(Config $config, array $user, int $userId) : bool
    {
        $this->groupsToRemove = $config->getGroupsToRemove();

        return !empty($this->groupsToRemove) ? true : false;
    }

    protected function executeSingle(Config $config, array $user, int $userId) : bool
    {
        $currentGroups = explode(',', $user['usergroup']);
        $groupsToRemove = explode(',', $config->getGroupsToRemove());
        $newGroups = implode(',', array_diff($currentGroups, $groupsToRemove));

        $this->frontEndUserRepository->updateGroups($userId, $newGroups);

        return true;
    }

    function getDefaultActionMessage(bool $result) : string
    {
        if ($result) {
            return sprintf("groups %s have been removed from user.", $this->groupsToRemove);
        }

        return sprintf('Failed to remove groups %s from user', $this->groupsToRemove);
    }
}
