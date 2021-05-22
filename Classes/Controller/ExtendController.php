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
use BPN\BpnExpiringFeUsers\Event\ActionEvent;
use BPN\BpnExpiringFeUsers\Traits\ConfigTrait;
use BPN\BpnExpiringFeUsers\Traits\FrontEndUserTrait;
use BPN\BpnExpiringFeUsers\Traits\LogTrait;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ExtendController extends ActionController
{
    use LogTrait;
    use ConfigTrait;
    use FrontEndUserTrait;

    const ACTION = 'extend';

    /** @var array */
    private $errors = [];

    /** @var LanguageService */
    protected $languageService;

    public function injectLanguageService(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    public function extendAction(int $user, string $time, int $extend, int $job, int $group = 0)
    {
//        $params = '/?&u=' . $this->get['u'] . '&t=' . $this->get['t'] . '&e=' . $this->get['e'] . '&r=' . $this->get['r'];
//        if ($this->get['g']) {
//            $params .= '&g=' . $this->get['g'];
//        }

//        $c1 = $this->get['cHash'];
//        $c2 = GeneralUtility::hmac($params);

//        if ($c1 !== $c2) {
//            $content = htmlspecialchars($this->pi_getLL('pi1.invalidhash'));
//        } else {

        // TODO : check if link is valid

        $config = $this->configRepository->findByUid($job);

        $errorKey = $this->validArguments($config, $time, $user);
        if ($errorKey) {
            $this->view->assign('error', $this->errors);

            return;
        }

        $result = false;
        $userId = $user;
        if ($group) {
            $extendedUntil = $this->frontEndUserRepository->addExpiringGroup($userId, $group);
            $this->frontEndUserRepository->updateLastLogin($userId);

            if ($extendedUntil) {
                $this->setResult(
                    $config,
                    $userId,
                    'result.extendedgroup',
                    'log.extendedgroup',
                    ['groupid' => $group, 'endtime' => $extendedUntil]
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
                    ['endtime' => $extendedUntil]
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
    }

    private function validArguments(?Config $config, int $time, int $userId) : bool
    {
        $monthAgo = strtotime('-31 days');
        if ($time <= $monthAgo) {
            $this->errors[] = 'link.expired';
        }

        if (!$config) {
            $this->errors[] = 'job.not.found';
        } elseif ($this->logRepository->isAccountAlreadyExtended($config, $userId, $time)) {
            $this->errors[] = 'already.extended';
        }

        return !empty($this->errors);
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
                $text = str_replace('###' . $key . '###', $value, $text);
                $log = str_replace('###' . $key . '###', $value, $log);
            }
        }

        $this->addLog($config, $userId, self::ACTION, $log);

        $this->view->assign('result', $text);
    }

    protected function translate(string $key) : string
    {
        $linkText = $this->languageService->sL(
            'LLL:EXT:bpn_expiring_fe_users/Resources/Private/Language/locallang.xlf/locallang_db.xlf:' . $key
        );

        return $linkText;
    }

}
