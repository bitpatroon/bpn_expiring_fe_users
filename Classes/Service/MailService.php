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
use BPN\BpnExpiringFeUsers\Domain\Repository\FrontEndUserGroupRepository;
use BPN\BpnExpiringFeUsers\Domain\Repository\FrontEndUserRepository;
use BPN\BpnExpiringFeUsers\Domain\Repository\LogRepository;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class MailService
{
    /** @var LogRepository */
    protected $logRepository;

    public function injectLogRepository(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    /** @var FrontEndUserRepository */
    protected $frontEndUserRepository;

    public function injectFrontEndUserRepository(FrontEndUserRepository $frontEndUserRepository)
    {
        $this->frontEndUserRepository = $frontEndUserRepository;
    }

    /** @var FrontEndUserGroupRepository */
    protected $frontEndUserGroupRepository;

    public function injectFrontEndUserGroupRepository(FrontEndUserGroupRepository $frontEndUserGroupRepository)
    {
        $this->frontEndUserGroupRepository = $frontEndUserGroupRepository;
    }

    /** @var LanguageService */
    protected $languageService;

    public function injectLanguageService(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    /**
     * Sends an e-mail to fe_users.
     */
    public function main(Config $config, array $users)
    {
        if (!$this->validateJob($config)) {
            return;
        }

        if (!$users || !is_array($users)) {
            return;
        }

        foreach ($users as $user) {
            $userId = (int)$user['uid'];
            if (!$user['email']) {
                $this->logRepository->addLog(
                    $config,
                    $userId,
                    'warning',
                    'fe_user not notified. No e-mail address found.'
                );
                continue;
            }

            $this->sendMail($config, $user);

            // set account to expire, even when user has no e-mail address
            if ($config->getExpiresIn()) {
                $this->frontEndUserRepository->setAccountExpirationDate(
                    $userId,
                    strtotime('+' . $config->getExpiresIn() . ' days'),
                    $config
                );
            }
        }
    }

    /**
     * Validates the job, see if all fields are filled in etc.
     */
    private function validateJob(Config $config) : bool
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
        if ($config->getTestmode()) {
            $this->logRepository->addInfo($config, (int)$userRecord['uid'], 'Job From e-mail address is invalid.');

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
                '/?&u=%s&t=%s&e=%s&r=%s',
                $userRecord['uid'],
                time(),
                $config->getExtendBy(),
                $config->getUid()
            );
            if ($reactivateLinkForExpiringGroups) {
                // add which group to extend to url
                $params .= '&g=' . $config->getMemberOf();
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
                    sprintf('User (uid:%s) has an invalid e-mail address.', $userRecord['uid'])
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
        $this->logRepository->addLog($config, $userRecord['uid'], $action, 'user was notified.');
    }

    /**
     * This function checks if a user was already mailed by a job, and if so, if it was long enough ago to do it again.
     */
    public function checkSentLog(Config $config, int $userId) : bool
    {
        $testmode = $config->getTestmode();
        $daysAgo = $config->getExtendBy() - $config->getDays();
        $hasToBe = strtotime('-' . $daysAgo . ' days');

        if ($config->getExtendBy() <= 0) {
            return true;
        }

        $row = $this->logRepository->findByJobUser($config->getUid(), $userId, $testmode, $hasToBe);

        return $row ? true : false;
    }

    protected function translate(string $key) : string
    {
        $linkText = $this->languageService->sL(
            'LLL:EXT:bpn_expiring_fe_users/Resources/Private/Language/locallang_db.xlf/locallang_db.xlf:' . $key
        );

        return $linkText;
    }
}