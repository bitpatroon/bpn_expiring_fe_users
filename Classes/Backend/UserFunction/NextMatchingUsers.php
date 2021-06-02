<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 21-5-2021 21:54
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

namespace BPN\BpnExpiringFeUsers\Backend\UserFunction;

use BPN\BpnExpiringFeUsers\Domain\Model\Config;
use BPN\BpnExpiringFeUsers\Domain\Repository\ConfigRepository;
use BPN\BpnExpiringFeUsers\Traits\ConfigTrait;
use BPN\BpnExpiringFeUsers\Traits\DateServiceTrait;
use BPN\BpnExpiringFeUsers\Traits\FrontEndUserTrait;

class NextMatchingUsers extends AbstractUsersView
{
    use ConfigTrait;
    use DateServiceTrait;
    use FrontEndUserTrait;

    public function render()
    {
        $resultArray = $this->mergeChildReturnIntoExistingResult(
            $this->initializeResultArray(),
            $this->renderFieldInformation(),
            false
        );

        $databaseRow = $this->data['databaseRow'];
        $table = $this->data['tableName'];
        if (ConfigRepository::TABLE != $table) {
            return $this->showError(
                'Not allowed to use this control on another record other than '.ConfigRepository::TABLE,
                $resultArray
            );
        }

        if (!is_numeric($databaseRow['uid'])) {
            return $this->showError('New Record detected. Please save first. [1621631328224]', $resultArray);
        }

        if ($this->getDateService()->isSummerAndExcluded($databaseRow)) {
            return $this->showError('Currently suspended', $resultArray);
        }

        $this->getConfigRepository()->allowHiddenRecords();
        /** @var Config $config */
        $config = $this->getConfigRepository()->findByUidIncludingHidden((int) $databaseRow['uid']);
        if (!$config) {
            return $this->showError('Configuration was not found [1621681787]', $resultArray);
        }
        $users = $this->getFrontEndUserRepository()->findMatchingUsers($config);
        if (!$users) {
            return $this->showError('No users found', $resultArray);
        }
        return $this->renderView($users, $resultArray);
    }

    protected function showError(string $message, array $resultArray)
    {
        $resultArray['html'] = '<div class="alert alert-warning">'.$message.'</div>';

        return $resultArray;
    }
}
