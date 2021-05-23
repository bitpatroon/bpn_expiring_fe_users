<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 22-5-2021 18:34
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

namespace BPN\BpnExpiringFeUsers\Controller;

use BPN\BpnExpiringFeUsers\Domain\Model\Config;
use BPN\BpnExpiringFeUsers\Domain\Model\FrontEndUserGroup;
use BPN\BpnExpiringFeUsers\Event\ActionEvent;
use BPN\BpnExpiringFeUsers\Service\MailActionService;
use BPN\BpnExpiringFeUsers\Traits\ConfigTrait;
use BPN\BpnExpiringFeUsers\Traits\FrontEndUserGroupTrait;
use BPN\BpnExpiringFeUsers\Traits\FrontEndUserTrait;
use BPN\BpnExpiringFeUsers\Traits\LogTrait;
use Exception;
use RuntimeException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ExtendController extends ActionController
{
    use LogTrait;
    use ConfigTrait;
    use FrontEndUserTrait;
    use FrontEndUserGroupTrait;

    const ACTION = 'extend';

    /** @var array */
    private $errors = [];

    /** @var LanguageService */
    protected $languageService;

    public function injectLanguageService(LanguageService $languageService)
    {
        $this->languageService = $languageService;
        $this->languageService->init('nl');
    }

    /** @var MailActionService */
    protected $mailActionService;

    public function injectMailActionService(MailActionService $mailActionService)
    {
        $this->mailActionService = $mailActionService;
    }

    public function extendAction()
    {
        try {
            $this->mailActionService->validateUrl();
            $arguments = $this->mailActionService->getLinkArguments();
            $userId = (int) $arguments['user'];
            $time = (int) $arguments['time'];
            $extend = (int) $arguments['extend'];
            $jobId = (int) $arguments['job'];
            $groupId = (int) $arguments['group'];

            $config = $this->configRepository->findByUid($jobId);

            $this->validArguments($config, $time, $userId);

            $result = false;
            if ($groupId) {
                $extendedUntil = $this->frontEndUserRepository->addExpiringGroup($userId, $groupId);
                $this->frontEndUserRepository->updateLastLogin($userId);

                if ($extendedUntil) {
                    /** @var FrontEndUserGroup $group */
                    $group = $this->frontEndUserGroupRepository->findByUid($groupId);
                    $groupName = $groupId;
                    if ($group) {
                        $groupName = $group->getTitle();
                    }

                    $this->setResult(
                        $config,
                        $userId,
                        'result.extendedgroup',
                        'log.extendedgroup',
                        [
                            'group'   => $groupName,
                            'endtime' => date('d-m-Y', $extendedUntil),
                        ]
                    );
                    $result = true;
                }
            } else {
                $extendedUntil = $this->frontEndUserRepository->extend($userId, $extend);
                $this->frontEndUserRepository->updateLastLogin($userId);

                if ($extendedUntil) {
                    $this->setResult(
                        $config,
                        $userId,
                        'result.extended_by_link',
                        'log.extended_by_link',
                        ['endtime' => date('d-m-Y', $extendedUntil)]
                    );
                    $result = true;
                }
            }

            $event = new ActionEvent();
            $event->setUserId($userId)
                ->setResult($result)
                ->setAction(self::ACTION)
                ->setConfig($config->getUid());
            $this->eventDispatcher->dispatch($event);
        } catch (Exception $exception) {
            $this->errors[] = $exception;
        }
        $this->view->assign('errors', $this->errors);
    }

    private function validArguments(?Config $config, int $time, int $userId)
    {
        $monthAgo = strtotime('-31 days');
        if ($time <= $monthAgo) {
            throw new RuntimeException($this->translate('link.expired'), 1621768798);
        }

        if (!$config) {
            throw new RuntimeException($this->translate('job.not.found'), 1621768932);
        } elseif ($this->logRepository->isAccountAlreadyExtended($config, $userId, $time)) {
            throw new RuntimeException($this->translate('result.already.extended'), 1621768948);
        }
    }

    private function setResult(
        Config $config,
        int $userId,
        string $translationKey,
        string $logKey,
        array $arguments = []
    ) {
        $text = $this->translate($translationKey);
        $log = $this->translate($logKey);

        if ($arguments) {
            foreach ($arguments as $key => $value) {
                $replaceKey = '###'.strtoupper($key).'###';
                $text = str_replace($replaceKey, $value, $text);
                $log = str_replace($replaceKey, $value, $log);
            }
        }

        $this->addLog($config, $userId, self::ACTION, $log);

        $this->view->assign('result', $text);
        $this->view->assign('actionResult', 1);
    }

    protected function translate(string $key): string
    {
        $linkText = $this->languageService->sL(
            'LLL:EXT:bpn_expiring_fe_users/Resources/Private/Language/locallang.xlf:'.$key
        );

        return $linkText;
    }
}
