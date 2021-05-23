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

    const SECRET = '*4g&b@R#9Hx78rVP';
    protected $action = 'mail';

    protected function beforeExecutingSingle(Config $config, array $user, int $userId): bool
    {
        if (!$user['email']) {
            $this->addLog($config, $userId, 'warning', 'fe_user not notified. No e-mail address found.');

            return false;
        }

        return true;
    }

    protected function executeSingle(Config $config, array $user, int $userId): bool
    {
        $this->sendMail($config, $user);

        // set account to expire, even when user has no e-mail address
        if ($config->getExpiresIn()) {
            $this->frontEndUserRepository->setAccountExpirationDate(
                $userId,
                strtotime('+'.$config->getExpiresIn().' days'),
                $config
            );
        }

        return true;
    }

    public function getDefaultActionMessage(bool $result): string
    {
        if ($result) {
            return 'User was notified by e-mail.';
        }

        return 'Failed to notify user by e-mail';
    }

    /**
     * Validates the job, see if all fields are filled in etc.
     */
    protected function validateJob(Config $config): bool
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
                'Test email address for this job ('.$config->getUid().') is invalid.'
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
        $userId = (int) $userRecord['uid'];

        if ($config->getTestmode()) {
            $this->logRepository->addInfo($config, (int) $userId, 'Job From e-mail address is invalid.');

            return;
        }

        /** @var \TYPO3\CMS\Core\Mail\MailMessage $mail */
        /** @var MailMessage $mailMessage */
        $mailMessage = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(MailMessage::class);

        $emailText = $config->getEmailText();
        $extendLink = '';

        $reactivateLinkForExpiringGroups = (20 === $config->getReactivateLink());
        if ($config->getReactivateLink() || $reactivateLinkForExpiringGroups) {
            $params = [
                'user' => $userId,
                'time' => time(),
                'extend' => $config->getExtendBy(),
                'job' => $config->getUid(),
            ];
            if ($reactivateLinkForExpiringGroups) {
                // add which group to extend to url
                $params['group'] = $config->getExpiringGroup();
            }
            $cs = $this->generateHash($params);
            $params['cs'] = $cs;
            $queryString = http_build_query($params);
            $url = rtrim($config->getPage(), '/');
            if (!Environment::getContext()->isDevelopment()) {
                $linkService = new LinkService();
                $url = $linkService->makeAbsoluteHttpsUrl(rtrim($config->getPage(), '/'));
            }

            $url .= (strpos($url, '?') ? '&' : '?').http_build_query(['c' => base64_encode($queryString)]);

            $extendLink = sprintf(
                '<a href="%s" class="extend-link">%s</a><p>%s:<br>%s</p>',
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
            [$extendLink, $this->getFullName($userRecord)],
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

    private function getFullName(array $userRecord)
    {
        if (!$userRecord) {
            return '[user]';
        }

        $result = [
            0 => $userRecord['first_name'],
            100 => $userRecord['last_name'],
        ];

        if ($userRecord['middle_name']) {
            $result[50] = $userRecord['middle_name'];
        }

        ksort($result);
        $result = implode(' ', $result);
        $result = trim($result);
        if (!$result) {
            $result = sprintf('[user: %s]', $userRecord['uid']);
        }

        return $result;
    }

    protected function generateHash(array $arguments): string
    {
        $queryString = http_build_query($arguments);

        return GeneralUtility::hmac($queryString, self::SECRET);
    }

    public function getLinkArguments(): array
    {
        $queryString = GeneralUtility::_GP('c');
        if (!$queryString) {
            throw new \RuntimeException('result.invalidhash', 1621772396);
        }
        $queryString = base64_decode($queryString);
        if (!$queryString || false === strpos($queryString, '&cs=')) {
            throw new \RuntimeException('result.invalidhash', 1621772630);
        }

        parse_str($queryString, $parts);
        if (!$parts) {
            throw new \RuntimeException('result.invalidhash', 1621773192);
        }

        return $parts;
    }

    public function validateUrl(): void
    {
        $parts = $this->getLinkArguments();

        $hash = $parts['cs'];
        unset($parts['cs']);
        $calculatedHash = GeneralUtility::hmac(http_build_query($parts), self::SECRET);

        if ($calculatedHash !== $hash) {
            throw new \RuntimeException('result.invalidhash', 1621769030);
        }
    }
}
