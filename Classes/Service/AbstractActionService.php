<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 22-5-2021 17:10
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
use BPN\BpnExpiringFeUsers\Event\ActionEvent;
use BPN\BpnExpiringFeUsers\Traits\FrontEndUserTrait;
use BPN\BpnExpiringFeUsers\Traits\LogTrait;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Localization\LanguageService;

abstract class AbstractActionService implements ActionInterface
{
    use LogTrait;
    use FrontEndUserTrait;

    /** @var EventDispatcher */
    protected $eventDispatcher;

    protected $action;

    public function injectEventDispatcher(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /** @var LanguageService */
    protected $languageService;

    public function injectLanguageService(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }


    protected function dispatchEvent(Config $config, string $action, int $userId, bool $result = true)
    {
        $event = new ActionEvent();
        $event->setAction($action)
            ->setConfig($config->getUid())
            ->setResult($result)
            ->setUserId($userId);

        $this->eventDispatcher->dispatch($event);
    }

    public function execute(Config $config, array $users)
    {
        if ($config->getHidden() || $config->getDeleted()) {
            return false;
        }

        if (!$users || !is_array($users)) {
            return false;
        }

        if (!$this->validateJob($config)) {
            return false;
        }

        foreach ($users as $user) {
            $userId = (int)$user['uid'];

            $this->beforeExecutingSingle($config, $user, $userId);
            if ($config->getTestmode()) {
                $this->addInfo($config, $userId, $this->getDefaultActionMessage(true));
                continue;
            }

            $result = $this->executeSingle($config, $user, $userId);

            $this->addLog($config, $userId, $this->action, $this->getDefaultActionMessage($result));
            $this->dispatchEvent($config, $this->action, $userId, $result);
        }
    }

    abstract protected function executeSingle(Config $config, array $user, int $userId) : bool;

    abstract function getDefaultActionMessage(bool $result) : string;

    protected function beforeExecutingSingle(Config $config, array $user, int $userId) : bool
    {
        return true;
    }

    protected function validateJob(Config $config) : bool
    {
        return true;
    }

    protected function translate(string $key) : string
    {
        $linkText = $this->languageService->sL(
            'LLL:EXT:bpn_expiring_fe_users/Resources/Private/Language/locallang_db.xlf/locallang_db.xlf:' . $key
        );

        return $linkText;
    }
}
