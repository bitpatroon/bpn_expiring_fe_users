<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 22-5-2021 16:51
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

namespace BPN\BpnExpiringFeUsers\Traits;

use BPN\BpnExpiringFeUsers\Service\DateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

trait DateServiceTrait
{
    /**
     * @var DateService
     */
    private $dateService;

    public function injectDateService(DateService $dateService)
    {
        $this->dateService = $dateService;
    }

    public function getDateService() : DateService
    {
        if (!$this->dateService) {
            /* @var DateService $dateService */
            $this->dateService = GeneralUtility::makeInstance(ObjectManager::class)
                ->get(DateService::class);
        }

        return $this->dateService;
    }
}
