<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 22-5-2021 16:44
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

use BPN\BpnExpiringFeUsers\Domain\Model\Config;
use BPN\BpnExpiringFeUsers\Domain\Repository\LogRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

trait LogTrait
{
    /** @var LogRepository */
    protected $logRepository;

    public function injectLogRepository(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    public function addInfo(Config $config, int $userId, string $message)
    {
        $this->logRepository->addInfo($config, $userId, $message);
    }

    public function addError(Config $config, string $message)
    {
        $this->logRepository->addError($config, $message);
    }

    public function addLog(Config $config, int $userId, string $action, string $message)
    {
        $this->logRepository->addLog($config, $userId, $action, $message);
    }

    /**
     * @return LogRepository
     */
    public function getLogRepository() : LogRepository
    {
        if (!$this->logRepository) {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

            /** @var LogRepository $logRepository */
            $this->logRepository = $objectManager->get(LogRepository::class);
        }

        return $this->logRepository;
    }

}
