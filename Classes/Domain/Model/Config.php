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
    protected $cruserId;
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
    protected $andorNot;
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
    protected $emailTest;
    /** @var string */
    protected $emailFromName;
    /** @var string */
    protected $emailFrom;
    /** @var string */
    protected $emailBcc;
    /** @var string */
    protected $emailSubject;
    /** @var string */
    protected $emailText;
    /** @var int */
    protected $expiresIn;
    /** @var int */
    protected $reactivateLink;
    /** @var int */
    protected $extendBy;
    /** @var string */
    protected $page;

    public function getTstamp(): int
    {
        return $this->tstamp ?? 0;
    }

    public function setTstamp(int $tstamp): Config
    {
        $this->tstamp = $tstamp;

        return $this;
    }

    public function getCrdate(): int
    {
        return $this->crdate ?? 0;
    }

    public function setCrdate(int $crdate): Config
    {
        $this->crdate = $crdate;

        return $this;
    }

    public function getCruserId(): int
    {
        return $this->cruserId ?? 0;
    }

    public function setCruserId(int $cruserId): Config
    {
        $this->cruserId = $cruserId;

        return $this;
    }

    public function getDeleted(): int
    {
        return $this->deleted ?? 0;
    }

    public function setDeleted(int $deleted): Config
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getHidden(): int
    {
        return $this->hidden ?? 0;
    }

    public function setHidden(int $hidden): Config
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function getTestmode(): int
    {
        return $this->testmode ?? 0;
    }

    public function setTestmode(int $testmode): Config
    {
        $this->testmode = $testmode;

        return $this;
    }

    public function getLimiter(): int
    {
        return $this->limiter ?? 1000;
    }

    public function setLimiter(int $limiter): Config
    {
        $this->limiter = $limiter;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function setTitle(string $title): Config
    {
        $this->title = $title;

        return $this;
    }

    public function getExcludesummer(): string
    {
        return $this->excludesummer ?? 0;
    }

    public function setExcludesummer(string $excludesummer): Config
    {
        $this->excludesummer = $excludesummer;

        return $this;
    }

    public function getSysfolder(): string
    {
        return $this->sysfolder ?? '';
    }

    public function getSysfolderAsArray(): array
    {
        $sysFolder = $this->getSysfolder();
        if (!$sysFolder) {
            return [];
        }

        return GeneralUtility::intExplode(',', $sysFolder);
    }

    public function setSysfolder(string $sysfolder): Config
    {
        $this->sysfolder = $sysfolder;

        return $this;
    }

    public function getMemberOf(): string
    {
        return $this->memberOf ?? '';
    }

    public function getMemberOfAsArray(): array
    {
        $memberOf = $this->getMemberOf();
        if (!is_array($memberOf)) {
            return GeneralUtility::intExplode(',', $memberOf);
        }

        return $memberOf;
    }

    public function setMemberOf(string $memberOf): Config
    {
        $this->memberOf = $memberOf;

        return $this;
    }

    public function getAndor()
    {
        return $this->andor;
    }

    public function getAndorAsString(): string
    {
        if (is_array($this->andor)) {
            $result = current($this->andor);
        } else {
            $result = $this->andor;
        }

        return strtoupper($result);
    }

    public function setAndor(string $andor): Config
    {
        $this->andor = $andor;

        return $this;
    }

    public function getNoMemberOf(): string
    {
        return $this->noMemberOf ?? '';
    }

    /**
     * @return int[]
     */
    public function getNoMemberOfAsArray(): array
    {
        $noMemberOf = $this->getNoMemberOf();
        if (!is_array($noMemberOf)) {
            return GeneralUtility::intExplode(',', $noMemberOf);
        }

        return $noMemberOf;
    }

    public function setNoMemberOf(string $noMemberOf): Config
    {
        $this->noMemberOf = $noMemberOf;

        return $this;
    }

    public function getAndorNot(): string
    {
        return $this->andorNot ?? 'AND';
    }

    public function getAndorNotAsString(): string
    {
        if (is_array($this->andorNot)) {
            $result = current($this->andorNot);
        } else {
            $result = $this->andorNot;
        }

        return strtoupper($result);
    }

    public function setAndorNot(string $andorNot): Config
    {
        $this->andorNot = $andorNot;

        return $this;
    }

    public function getExpiringGroup(): string
    {
        return $this->expiringGroup ?? '';
    }

    public function setExpiringGroup(string $expiringGroup): Config
    {
        $this->expiringGroup = $expiringGroup;

        return $this;
    }

    public function getGroupsToRemove(): string
    {
        return $this->groupsToRemove ?? '';
    }

    public function setGroupsToRemove(string $groupsToRemove): Config
    {
        $this->groupsToRemove = $groupsToRemove;

        return $this;
    }

    public function getCondition1(): int
    {
        return ((int) $this->condition1) ?? 0;
    }

    public function setCondition1(int $condition1): Config
    {
        $this->condition1 = $condition1 ? 1 : 0;

        return $this;
    }

    public function getCondition2(): int
    {
        return ((int) $this->condition2) ?? 0;
    }

    public function setCondition2(int $condition2): Config
    {
        $this->condition2 = $condition2 ? 1 : 0;

        return $this;
    }

    public function getCondition3(): int
    {
        return ((int) $this->condition3) ?? 0;
    }

    public function setCondition3(int $condition3): Config
    {
        $this->condition3 = $condition3 ? 1 : 0;

        return $this;
    }

    public function getCondition4(): int
    {
        return ((int) $this->condition4) ?? 0;
    }

    public function setCondition4(int $condition4): Config
    {
        $this->condition4 = $condition4 ? 1 : 0;

        return $this;
    }

    public function getCondition5(): int
    {
        return ((int) $this->condition5) ?? 0;
    }

    public function setCondition5(int $condition5 = 1): Config
    {
        $this->condition5 = $condition5 ? 1 : 0;

        return $this;
    }

    public function getCondition6(): int
    {
        return ((int) $this->condition6) ?? 0;
    }

    public function setCondition6(int $condition6): Config
    {
        $this->condition6 = $condition6 ? 1 : 0;

        return $this;
    }

    public function getCondition7(): int
    {
        return ((int) $this->condition7) ?? 0;
    }

    public function setCondition7(int $condition7): Config
    {
        $this->condition7 = $condition7 ? 1 : 0;

        return $this;
    }

    public function getCondition8(): int
    {
        return ((int) $this->condition8) ?? 0;
    }

    public function setCondition8(int $condition8): Config
    {
        $this->condition8 = $condition8 ? 1 : 0;

        return $this;
    }

    public function getCondition20(): int
    {
        return ((int) $this->condition20) ?? 0;
    }

    public function setCondition20(int $condition20): Config
    {
        $this->condition20 = $condition20 ? 1 : 0;

        return $this;
    }

    public function getDays(): int
    {
        return $this->days ?? 30;
    }

    public function setDays(int $days): Config
    {
        $this->days = $days;

        return $this;
    }

    public function getTodo(): int
    {
        return $this->todo ?? 0;
    }

    public function setTodo(int $todo): Config
    {
        $this->todo = $todo;

        return $this;
    }

    public function getEmailTest(): string
    {
        return $this->emailTest ?? '';
    }

    public function setEmailTest(string $emailTest): Config
    {
        $this->emailTest = $emailTest;

        return $this;
    }

    public function getEmailFromName(): string
    {
        return $this->emailFromName ?? '';
    }

    public function setEmailFromName(string $emailFromName): Config
    {
        $this->emailFromName = $emailFromName;

        return $this;
    }

    public function getEmailFrom(): string
    {
        return $this->emailFrom ?? '';
    }

    public function setEmailFrom(string $emailFrom): Config
    {
        $this->emailFrom = $emailFrom;

        return $this;
    }

    public function getEmailBcc(): string
    {
        return $this->emailBcc ?? '';
    }

    public function setEmailBcc(string $emailBcc): Config
    {
        $this->emailBcc = $emailBcc;

        return $this;
    }

    public function getEmailSubject(): string
    {
        return $this->emailSubject ?? '';
    }

    public function setEmailSubject(string $emailSubject): Config
    {
        $this->emailSubject = $emailSubject;

        return $this;
    }

    public function getEmailText(): string
    {
        return $this->emailText ?? '';
    }

    public function setEmailText(string $emailText): Config
    {
        $this->emailText = $emailText;

        return $this;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn ?? 0;
    }

    public function setExpiresIn(int $expiresIn): Config
    {
        $this->expiresIn = $expiresIn;

        return $this;
    }

    public function getReactivateLink(): int
    {
        return $this->reactivateLink ?? 0;
    }

    public function setReactivateLink(int $reactivateLink): Config
    {
        $this->reactivateLink = $reactivateLink;

        return $this;
    }

    public function getExtendBy(): int
    {
        return $this->extendBy ?? 0;
    }

    public function setExtendBy(int $extendBy): Config
    {
        $this->extendBy = $extendBy;

        return $this;
    }

    public function getPage(): string
    {
        return $this->page ?? '';
    }

    public function setPage(string $page): Config
    {
        $this->page = $page;

        return $this;
    }
}
