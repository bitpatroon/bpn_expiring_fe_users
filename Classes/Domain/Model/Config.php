<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 20-5-2021 16:57
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Config extends AbstractEntity
{
    /** @var int */
    protected $tstamp;
    /** @var int */
    protected $crdate;
    /** @var int */
    protected $cruser_id;
    /** @var int */
    protected $deleted;
    /** @var int */
    protected $hidden;
    /** @var int */
    protected $testmode;
    /** @var int */
    protected $limiter;
    /** @var string */
    protected $title;
    /** @var string */
    protected $excludesummer;
    /** @var string */
    protected $sysfolder;
    /** @var string */
    protected $memberOf;
    /** @var string */
    protected $andor;
    /** @var string */
    protected $noMemberOf;
    /** @var string */
    protected $andor_not;
    /** @var string */
    protected $expiringGroup;
    /** @var string */
    protected $groupsToRemove;
    /** @var int */
    protected $condition1;
    /** @var int */
    protected $condition2;
    /** @var int */
    protected $condition3;
    /** @var int */
    protected $condition4;
    /** @var int */
    protected $condition5;
    /** @var int */
    protected $condition6;
    /** @var int */
    protected $condition7;
    /** @var int */
    protected $condition8;
    /** @var int */
    protected $condition20;
    /** @var int */
    protected $days;
    /** @var int */
    protected $todo;
    /** @var string */
    protected $email_test;
    /** @var string */
    protected $email_fromName;
    /** @var string */
    protected $email_from;
    /** @var string */
    protected $email_bcc;
    /** @var string */
    protected $email_subject;
    /** @var string */
    protected $email_text;
    /** @var int */
    protected $expires_in;
    /** @var int */
    protected $reactivate_link;
    /** @var int */
    protected $extend_by;
    /** @var string */
    protected $page;

    public function getTstamp() : int
    {
        return $this->tstamp;
    }

    public function setTstamp(int $tstamp) : Config
    {
        $this->tstamp = $tstamp;

        return $this;
    }

    public function getCrdate() : int
    {
        return $this->crdate;
    }

    public function setCrdate(int $crdate) : Config
    {
        $this->crdate = $crdate;

        return $this;
    }

    public function getCruserId() : int
    {
        return $this->cruser_id;
    }

    public function setCruserId(int $cruser_id) : Config
    {
        $this->cruser_id = $cruser_id;

        return $this;
    }

    public function getDeleted() : int
    {
        return $this->deleted;
    }

    public function setDeleted(int $deleted) : Config
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getHidden() : int
    {
        return $this->hidden;
    }

    public function setHidden(int $hidden) : Config
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function getTestmode() : int
    {
        return $this->testmode;
    }

    public function setTestmode(int $testmode) : Config
    {
        $this->testmode = $testmode;

        return $this;
    }

    public function getLimiter() : int
    {
        return $this->limiter;
    }

    public function setLimiter(int $limiter) : Config
    {
        $this->limiter = $limiter;

        return $this;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function setTitle(string $title) : Config
    {
        $this->title = $title;

        return $this;
    }

    public function getExcludesummer() : string
    {
        return $this->excludesummer;
    }

    public function setExcludesummer(string $excludesummer) : Config
    {
        $this->excludesummer = $excludesummer;

        return $this;
    }

    public function getSysfolder() : string
    {
        return $this->sysfolder ?? '';
    }

    public function getSysfolderAsArray() : array
    {
        $sysFolder = $this->getSysfolder();
        if (!$sysFolder) {
            return [];
        }

        return GeneralUtility::intExplode(',', $sysFolder);
    }

    public function setSysfolder(string $sysfolder) : Config
    {
        $this->sysfolder = $sysfolder;

        return $this;
    }

    public function getMemberOf() : string
    {
        return $this->memberOf ?? '';
    }

    public function getMemberOfAsArray() : array
    {
        $memberOf = $this->getMemberOf();
        if (!is_array($memberOf)) {
            return GeneralUtility::intExplode(',', $memberOf);
        }

        return $memberOf;
    }

    public function setMemberOf(string $memberOf) : Config
    {
        $this->memberOf = $memberOf;

        return $this;
    }

    public function getAndor()
    {
        return $this->andor;
    }

    public function getAndorAsString() : string
    {
        if (is_array($this->andor)) {
            $result = current($this->andor);
        } else {
            $result = $this->andor;
        }

        return strtoupper($result);
    }

    public function setAndor(string $andor) : Config
    {
        $this->andor = $andor;

        return $this;
    }

    public function getNoMemberOf() : string
    {
        return $this->noMemberOf ?? '';
    }

    /**
     * @return int[]
     */
    public function getNoMemberOfAsArray() : array
    {
        $noMemberOf = $this->getNoMemberOf();
        if (!is_array($noMemberOf)) {
            return GeneralUtility::intExplode(',', $noMemberOf);
        }

        return $noMemberOf;
    }

    public function setNoMemberOf(string $noMemberOf) : Config
    {
        $this->noMemberOf = $noMemberOf;

        return $this;
    }

    public function getAndorNot() : string
    {
        return $this->andor_not;
    }

    public function getAndorNotAsString() : string
    {
        if (is_array($this->andor_not)) {
            $result = current($this->andor_not);
        } else {
            $result = $this->andor_not;
        }

        return strtoupper($result);
    }


    public function setAndorNot(string $andor_not) : Config
    {
        $this->andor_not = $andor_not;

        return $this;
    }

    public function getExpiringGroup() : string
    {
        return $this->expiringGroup ?? '';
    }

    public function setExpiringGroup(string $expiringGroup) : Config
    {
        $this->expiringGroup = $expiringGroup;

        return $this;
    }

    public function getGroupsToRemove() : string
    {
        return $this->groupsToRemove ?? '';
    }

    public function setGroupsToRemove(string $groupsToRemove) : Config
    {
        $this->groupsToRemove = $groupsToRemove;

        return $this;
    }

    public function getCondition1() : int
    {
        return (int)$this->condition1;
    }

    public function setCondition1(int $condition1) : Config
    {
        $this->condition1 = $condition1 ? 1 : 0;

        return $this;
    }

    public function getCondition2() : int
    {
        return (int)$this->condition2;
    }

    public function setCondition2(int $condition2) : Config
    {
        $this->condition2 = $condition2 ? 1 : 0;

        return $this;
    }

    public function getCondition3() : int
    {
        return (int)$this->condition3;
    }

    public function setCondition3(int $condition3) : Config
    {
        $this->condition3 = $condition3 ? 1 : 0;

        return $this;
    }

    public function getCondition4() : int
    {
        return (int)$this->condition4;
    }

    public function setCondition4(int $condition4) : Config
    {
        $this->condition4 = $condition4 ? 1 : 0;

        return $this;
    }

    public function getCondition5() : int
    {
        return (int)$this->condition5;
    }

    public function setCondition5(int $condition5 = 1) : Config
    {
        $this->condition5 = $condition5 ? 1 : 0;

        return $this;
    }

    public function getCondition6() : int
    {
        return (int)$this->condition6;
    }

    public function setCondition6(int $condition6) : Config
    {
        $this->condition6 = $condition6 ? 1 : 0;

        return $this;
    }

    public function getCondition7() : int
    {
        return (int)$this->condition7;
    }

    public function setCondition7(int $condition7) : Config
    {
        $this->condition7 = $condition7 ? 1 : 0;

        return $this;
    }

    public function getCondition8() : int
    {
        return (int)$this->condition8;
    }

    public function setCondition8(int $condition8) : Config
    {
        $this->condition8 = $condition8 ? 1 : 0;

        return $this;
    }

    public function getCondition20() : int
    {
        return (int)$this->condition20;
    }

    public function setCondition20(int $condition20) : Config
    {
        $this->condition20 = $condition20 ? 1 : 0;

        return $this;
    }

    public function getDays() : int
    {
        return (int)$this->days;
    }

    public function setDays(int $days) : Config
    {
        $this->days = $days;

        return $this;
    }

    public function getTodo() : int
    {
        return $this->todo;
    }

    public function setTodo(int $todo) : Config
    {
        $this->todo = $todo;

        return $this;
    }

    public function getEmailTest() : string
    {
        return $this->email_test;
    }

    public function setEmailTest(string $email_test) : Config
    {
        $this->email_test = $email_test;

        return $this;
    }

    public function getEmailFromName() : string
    {
        return $this->email_fromName;
    }

    public function setEmailFromName(string $email_fromName) : Config
    {
        $this->email_fromName = $email_fromName;

        return $this;
    }

    public function getEmailFrom() : string
    {
        return $this->email_from;
    }

    public function setEmailFrom(string $email_from) : Config
    {
        $this->email_from = $email_from;

        return $this;
    }

    public function getEmailBcc() : string
    {
        return $this->email_bcc;
    }

    public function setEmailBcc(string $email_bcc) : Config
    {
        $this->email_bcc = $email_bcc;

        return $this;
    }

    public function getEmailSubject() : string
    {
        return $this->email_subject;
    }

    public function setEmailSubject(string $email_subject) : Config
    {
        $this->email_subject = $email_subject;

        return $this;
    }

    public function getEmailText() : string
    {
        return $this->email_text;
    }

    public function setEmailText(string $email_text) : Config
    {
        $this->email_text = $email_text;

        return $this;
    }

    public function getExpiresIn() : int
    {
        return $this->expires_in;
    }

    public function setExpiresIn(int $expires_in) : Config
    {
        $this->expires_in = $expires_in;

        return $this;
    }

    public function getReactivateLink() : int
    {
        return $this->reactivate_link;
    }

    public function setReactivateLink(int $reactivate_link) : Config
    {
        $this->reactivate_link = $reactivate_link;

        return $this;
    }

    public function getExtendBy() : int
    {
        return $this->extend_by ?? 0;
    }

    public function setExtendBy(int $extend_by) : Config
    {
        $this->extend_by = $extend_by;

        return $this;
    }

    public function getPage() : string
    {
        return $this->page;
    }

    public function setPage(string $page) : Config
    {
        $this->page = $page;

        return $this;
    }
}
