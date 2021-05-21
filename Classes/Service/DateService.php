<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 20-5-2021 16:53
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

class DateService
{
    /**
     * Checks if the is exclude summer setting is set and the current time is within the summer (july / august)
     *
     * @param array $record the record
     *
     * @return bool false if the summer period is not excluded or it is not summer. True otherwise.
     */
    public function isSummerAndExcluded(array $record)
    {
        if (empty($record)) {
            return false;
        }
        if (isset($record['excludesummer'])) {
            return (int)$record['excludesummer'] !== 0;
        }

        $month = (int)date('n');
        switch ($month) {
            case 7:
            case 8:
                return true;
        }

        return false;
    }
}
