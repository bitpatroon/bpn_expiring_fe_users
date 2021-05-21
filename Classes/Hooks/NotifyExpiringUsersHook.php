<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Sjoerd Zonneveld <szonneveld@bitpatroon.nl>
 *  Date: 29-8-2017 14:34
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

namespace BPN\BpnExpiringFeUsers\Hooks;

use BPN\BpnExpiringFeUsers\Domain\Repository\ConfigRepository;
use SPL\SplDomain\Domain\Service\FrontendUserService;
use SPL\SplLibrary\Utility\ObjectManagerHelper;
use TYPO3\CMS\Frontend\Utility\EidUtility;

class NotifyExpiringUsersHook
{
    /**
     * Hook
     * @param array $params The parameter Array
     * @param object $ref The parent object
     * @throws \Exception
     */
    public function onNotifyExpiredUser(/** @noinspection PhpUnusedParameterInspection */
        &$params,
        $ref
    )
    {
        //		$params = array(
        //			'result' => null,
        //			'user' => $user,
        //			'passwordIsValid' => $passwordIsValid
        //		);

        $params['result'] = false;

        if (empty($params)) {
            return;
        }

        /** @var \SPL\SplDomain\Domain\Model\LowLevel\UserModel $user */
        $user = $params['user'];

        /** @var bool $passwordIsValid */
        $passwordIsValid = $params['passwordIsValid'];
        if (!$passwordIsValid) {
            // geen notificatie
            return;
        }

        $frontendUserService = ObjectManagerHelper::get(FrontendUserService::class);
        /** @var array $userRecord */
        $userRecord = $frontendUserService->getFrontEndUser($user->getUid(), true, null);

        $configRepository = ObjectManagerHelper::get(ConfigRepository::class);
        $configs = $configRepository->getConfigsByActionByPid(
            ConfigRepository::TODO_MAIL,
            $user->getPid()
        );

        if (empty($configs)) {
            // no applying rules
            return;
        }

        EidUtility::initLanguage();

        foreach ($configs as $config) {
            $matchingUsers = \tx_bpnexpiringfeusers_scheduler::findMatchingUsers($configs, false, $user->getUid(), true);
            if (empty($matchingUsers)) {
                continue;
            }
            foreach ($matchingUsers as $matchingUser) {
                if ($user->getUid() != (int)$matchingUser['uid']) {
                    continue;
                }

                $mailAction = ObjectManagerHelper::get(\mailAction::class);
                $mailAction->sendMail($config, $userRecord);
                $params['result'] = true;
                $params['handle'] = 'handle_expired_mail_sent';
                break;
            }
        }
    }
}
