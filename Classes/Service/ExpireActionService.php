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

final class ExpireActionService extends AbstractActionService
{
    const ACTION = 'expire';

    protected $action = self::ACTION;
    protected $newEndTime;

    protected function beforeExecutingSingle(Config $config, array $user, int $userId) : bool
    {
        $endTime = $user['endtime'];
        if (!$endTime) {
            $endTime = time();
        }
        $this->newEndTime = strtotime('+' . $config->getExpiresIn() . ' days', $endTime);

        return true;
    }

    protected function executeSingle(Config $config, array $user, int $userId) : bool
    {
        $this->frontEndUserRepository->setAccountExpirationDate($userId, $this->newEndTime, $config);

        return true;
    }

    function getDefaultActionMessage(bool $result) : string
    {
        if ($result) {
            return 'User will now expire at ' . date('d-m-Y H:i', $this->newEndTime);
        }

        return 'Failed to set expiration for user';
    }
}
