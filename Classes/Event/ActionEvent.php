<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 22-5-2021 16:53
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

namespace BPN\BpnExpiringFeUsers\Event;

final class ActionEvent
{
    /** @var string */
    private $action;

    /** @var int */
    private $userId;

    /** @var int */
    private $config;

    /** @var bool */
    private $result;

    public function getAction() : string
    {
        return $this->action;
    }

    public function setAction(string $action) : ActionEvent
    {
        $this->action = $action;

        return $this;
    }

    public function getUserId() : int
    {
        return $this->userId;
    }

    public function setUserId(int $userId) : ActionEvent
    {
        $this->userId = $userId;

        return $this;
    }

    public function getConfig() : int
    {
        return $this->config;
    }

    public function setConfig(int $config) : ActionEvent
    {
        $this->config = $config;

        return $this;
    }

    public function isResult() : bool
    {
        return $this->result;
    }

    public function setResult(bool $result) : ActionEvent
    {
        $this->result = $result;

        return $this;
    }
}
