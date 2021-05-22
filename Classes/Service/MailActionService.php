<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 22-5-2021 13:21
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
use BPN\BpnExpiringFeUsers\Traits\FrontEndUserGroupTrait;
use BPN\BpnExpiringFeUsers\Traits\FrontEndUserTrait;
use BPN\BpnExpiringFeUsers\Traits\LogTrait;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

final class MailActionService extends AbstractActionService
{
    use LogTrait;
    use FrontEndUserTrait;
    use FrontEndUserGroupTrait;

    protected $action = 'mail';

    protected function beforeExecutingSingle(Config $config, array $user, int $userId) : bool
    {
        if (!$user['email']) {
            $this->addLog($config, $userId, 'warning', 'fe_user not notified. No e-mail address found.');

            return false;
        }

        return true;
    }

    protected function executeSingle(Config $config, array $user, int $userId) : bool
    {
        $this->sendMail($config, $user);

        // set account to expire, even when user has no e-mail address
        if ($config->getExpiresIn()) {
            $this->frontEndUserRepository->setAccountExpirationDate(
                $userId,
                strtotime('+' . $config->getExpiresIn() . ' days'),
                $config
            );
        }

        return true;
    }

    public function getDefaultActionMessage(bool $result) : string
    {
        if ($result) {
            return 'User was notified by e-mail.';
        }

        return 'Failed to notify user by e-mail';
    }

    /**
     * Validates the job, see if all fields are filled in etc.
     */
    protected function validateJob(Config $config) : bool
    {
        if ('1' == $config->getReactivateLink() && !$config->getPage()) {
            $this->logRepository->addError($config, 'No extend URL entered in job.');

            return false;
        }

        if (!$config->getEmailFrom() || !GeneralUtility::validEmail($config->getEmailFrom())) {
            $this->logRepository->addError($config, 'Job From e-mail address is invalid.');

            return false;
        }

        if ($config->getEmailTest() && !GeneralUtility::validEmail($config->getEmailTest())) {
            $this->logRepository->addError(
                $config,
                'Test email address for this job (' . $config->getUid() . ') is invalid.'
            );

            return false;
        }

        return true;
    }

    /**
     * Send expiration e-mail to a user.
     *
     * @param array $rec        Array with job record info
     * @param array $userRecord Array with fe_user info
     */
    protected function sendMail(Config $config, array $userRecord)
    {
        if (!isset($userRecord['uid']) || !$userRecord['uid']) {
            $this->logRepository->addError($config, 'Invalid user passed! Missing userid.');

            return;
        }
        $userId = (int)$userRecord['uid'];

        if ($config->getTestmode()) {
            $this->logRepository->addInfo($config, (int)$userId, 'Job From e-mail address is invalid.');

            return;
        }

        /** @var \TYPO3\CMS\Core\Mail\MailMessage $mail */
        /** @var MailMessage $mailMessage */
        $mailMessage = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(MailMessage::class);

        $emailText = $config->getEmailText();
        $extendLink = '';

        $reactivateLinkForExpiringGroups = (20 == $config->getReactivateLink());
        if ($config->getReactivateLink() || $reactivateLinkForExpiringGroups) {
            $params = sprintf(
                '/?&user=%s&time=%s&extend=%s&job=%s',
                $userId,
                time(),
                $config->getExtendBy(),
                $config->getUid()
            );
            if ($reactivateLinkForExpiringGroups) {
                // add which group to extend to url
                $params .= '&groups=' . $config->getMemberOf();
            }
            $params .= sprintf('&cHash=%s', GeneralUtility::hmac($params));
            $url = rtrim($config->getPage(), '/') . $params;

            if (!Environment::getContext()->isDevelopment()) {
                $linkService = new LinkService();
                $url = $linkService->makeAbsoluteHttpsUrl(rtrim($config->getPage(), '/'));
                $url .= $params;
            }

            $extendLink = sprintf(
                '<a href="%s">%s</a><p>%s:<br>%s</p>',
                $url,
                htmlspecialchars($this->translate('mailAction.extendby')),
                $this->translate('mailAction.or-copy-paste'),
                $url
            );
        }

        $groupNamesList = '';
        if ($config->getExpiringGroup()) {
            $expiringGroupIds = GeneralUtility::intExplode(',', $config->getExpiringGroup());
            $groupNames = $this->frontEndUserGroupRepository->getGroupNames($expiringGroupIds);
            $groupNamesList = implode(',', $groupNames);
        }

        $emailText = str_replace(
            ['###LINK###', '###NAME###', '###GROUPNAMES###', $groupNamesList],
            [$extendLink, $userRecord['name']],
            $emailText
        );

        if (!GeneralUtility::validEmail($config->getEmailFrom())) {
            $this->logRepository->addError($config, 'From Email address is not valid');
        }
        if ($config->getEmailTest()) {
            $emailTo = $config->getEmailTest();
        } else {
            $emailTo = $userRecord['email'];
            if (!GeneralUtility::validEmail($emailTo)) {
                $this->logRepository->addError(
                    $config,
                    sprintf('User (uid:%s) has an invalid e-mail address.', $userId)
                );

                return;
            }
        }

        $mailMessage
            ->setFrom($config->getEmailFrom(), $config->getEmailFromName())
            ->setTo([$emailTo])
            ->subject($config->getEmailSubject())
            ->html(sprintf('<html><head></head><body>%s</body></html>', $emailText));

        if ($config->getEmailBcc()) {
            $mailMessage->bcc(new Address($config->getEmailBcc()));
        }

        // send the mail
        $mailMessage->send();

        $action = $config->getEmailTest() ? 'testmail' : 'mail';
        $this->logRepository->addLog($config, $userId, $action, 'user was notified.');

        $this->dispatchEvent($config, $action, $userId, true);
    }

}
