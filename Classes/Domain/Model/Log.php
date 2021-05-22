<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 20-5-2021 17:07
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

namespace BPN\BpnExpiringFeUsers\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Log extends AbstractEntity
{
    /** @var int */
    protected $crdate;
    /** @var int */
    protected $job;
    /**
     * @var \BPN\BpnExpiringFeUsers\Domain\Model\FrontEndUser
     */
    protected $fe_user;
    /** @var int */
    protected $testmode;
    /** @var string */
    protected $action;
    /** @var string */
    protected $msg;

    public function getCrdate() : int
    {
        return $this->crdate;
    }

    public function setCrdate(int $crdate) : Log
    {
        $this->crdate = $crdate;

        return $this;
    }

    public function getJob() : int
    {
        return $this->job;
    }

    public function setJob(int $job) : Log
    {
        $this->job = $job;

        return $this;
    }

    public function getTestmode() : int
    {
        return $this->testmode;
    }

    public function setTestmode(int $testmode) : Log
    {
        $this->testmode = $testmode;

        return $this;
    }

    public function getAction() : string
    {
        return $this->action;
    }

    public function setAction(string $action) : Log
    {
        $this->action = $action;

        return $this;
    }

    public function getMsg() : string
    {
        return $this->msg;
    }

    public function setMsg(string $msg) : Log
    {
        $this->msg = $msg;

        return $this;
    }

    /**
     * @return FrontEndUser
     */
    public function getFeUser() : FrontEndUser
    {
        return $this->fe_user;
    }

    /**
     * @param FrontEndUser $fe_user
     *
     * @return Log
     */
    public function setFeUser(FrontEndUser $fe_user) : Log
    {
        $this->fe_user = $fe_user;

        return $this;
    }

}
