<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Sjoerd Zonneveld <typo3@bitpatroon.nl>
 *  Date: 31-1-2018 14:56
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

class ExpiringGroupModel
{
    /** @var int */
    private $uid;

    /** @var int */
    private $start;

    /** @var int */
    private $end;

    public function getStart(): int
    {
        return $this->start ?? 0;
    }

    /**
     * @param int $start
     *
     * @return $this
     */
    public function setStart($start)
    {
        $this->start = (int) $start;

        return $this;
    }

    public function getEnd(): int
    {
        return $this->end ?? 0;
    }

    /**
     * @param int $end
     *
     * @return $this
     */
    public function setEnd($end)
    {
        $this->end = (int) $end;

        return $this;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * @return $this
     */
    public function setUid(int $uid)
    {
        $this->uid = $uid;

        return $this;
    }
}
