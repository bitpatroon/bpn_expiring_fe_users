<?php

use SPL\SplLibrary\Utility\LinkHelper;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Sander Leeuwesteijn | iTypo <info@itypo.nl>
 *  All rights reserved
 */
class mailAction
{
    /**
     * Sends an e-mail to fe_users.
     *
     * @param        array $rec : row with job record info
     * @param        array $users : array with users
     */
    public function main($rec, $users)
    {
        if (self::validateJob($rec)) {
            foreach ($users as $user) {
                if ($user['email']) {
                    self::sendMail($rec, $user);
                } else {
                    tx_bpnexpiringfeusers_helpers::log($rec, $user['uid'], 'warning', 'fe_user not notified. No e-mail address found.');
                }

                // set account to expire, even when user has no e-mail address
                if ($rec['expires_in'] > 0) {
                    tx_bpnexpiringfeusers_helpers::setAccountExpirationDate($user['uid'], strtotime('+' . $rec['expires_in'] . ' days'), $rec);
                }
            }
        }
    }

    /**
     * Validates the job, see if all fields are filled in etc.
     *
     * @param $rec
     * @return bool
     */
    private static function validateJob($rec)
    {
        if ($rec['reactivate_link'] == '1' && !$rec['page']) {
            tx_bpnexpiringfeusers_helpers::log($rec, '0', 'error', 'No extend URL entered in job.');
            return false;
        }

        if (!$rec['email_from'] || !GeneralUtility::validEmail($rec['email_from'])) {
            tx_bpnexpiringfeusers_helpers::log($rec, '0', 'error', 'Job From e-mail address is invalid.');
            return false;
        }

        return true;
    }

    /**
     * Send expiration e-mail to a user
     *
     * @param    array $rec Array with job record info
     * @param    array $user Array with fe_user info
     */
    public function sendMail($rec, $user)
    {
        if ($rec['testmode'] === '0') {
            // send HTML e-mail via T3 SwiftMailer

            /** @var \TYPO3\CMS\Core\Mail\MailMessage $mail */
            $mail = GeneralUtility::makeInstance(MailMessage::class);

            // build extend link when set
            if ($rec['reactivate_link'] == '1' || $rec['reactivate_link'] == '20') {
                $t = time();
                $params = '/?&u=' . $user['uid'] . '&t=' . $t . '&e=' . $rec['extend_by'] . '&r=' . $rec['uid'];
                if ($rec['reactivate_link'] == '20') {
                    // add which group to extend to url
                    $params .= '&g=' . $rec['memberOf'];
                }
                $params .= '&cHash=' . GeneralUtility::hmac($params);
                $url = rtrim($rec['page'], '/') . $params;

                if (SPL_OTAP != 'P') {
                    $url = LinkHelper::makeAbsoluteHttpsUrl(rtrim($rec['page'], '/'));
                    $url .= $params;
                }

                /** @var \TYPO3\CMS\Lang\LanguageService $lang */
                $lang = $GLOBALS['LANG'];
                $extendLink = '<a href="' . $url . '">' . htmlspecialchars($lang->sL('LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:mailAction.extendby')) . '</a>';
                $extendLink .= '<p>Of kopieer en plak:<br>' . $url . '</p>';

                // replace LINK marker
                $rec['email_text'] = str_replace('###LINK###', $extendLink, $rec['email_text']);
            }

            // replace NAME marker
            $rec['email_text'] = str_replace('###NAME###', $user['name'], $rec['email_text']);

            // replace GROUPNAMES marker
            if (!empty($rec['expiringGroup'])) {
                $expGroups = explode(',', $rec['expiringGroup']);

                $names = '';
                foreach ($expGroups as $expGroup) {
                    $names .= tx_bpnexpiringfeusers_helpers::getGroupNameById($expGroup) . ',<br>';
                }

                $rec['email_text'] = str_replace('###GROUPNAMES###', $names, $rec['email_text']);
            }

            // build message
            $message = '<html><head></head><body>' . $rec['email_text'] . '</body></html>';

            // set basic parameters
            $mail->setFrom([$rec['email_from'] => $rec['email_fromName']]);

            if (GeneralUtility::validEmail($user['email'])) {
                if ($rec['email_test']) {
                    $mail->setTo([$rec['email_test'] => $user['name']]);
                } else {
                    $mail->setTo([$user['email'] => $user['name']]);
                }
            } else {
                tx_bpnexpiringfeusers_helpers::log($rec, $user['uid'], 'warning', 'fe_user has an invalid e-mail address.');
                return;
            }

            if ($rec['email_bcc']) {
                $mail->setBcc(explode(',', $rec['email_bcc']));
            }

            $mail->setSubject($rec['email_subject']);

            // add html body
            $mail->setBody($message, 'text/html');

            // send the mail
            $mail->send();

            // save this user has been notified by this job
            if ($rec['email_test']) {
                tx_bpnexpiringfeusers_helpers::log($rec, $user['uid'], 'testmail', 'fe_user was notified.');
            } else {
                tx_bpnexpiringfeusers_helpers::log($rec, $user['uid'], 'mail', 'fe_user was notified.');
            }
        } else {
            tx_bpnexpiringfeusers_helpers::log($rec, $user['uid'], 'info', 'fe_user was notified.');
        }
    }

    /**
     * This function checks if a user was already mailed by a job, and if so, if it was long enough ago to do it again.
     *
     * @param $rec
     * @param $userUid
     * @return bool
     */
    public static function checkSentLog($rec, $userUid)
    {
        $job = $rec['uid'];
        $testmode = $rec['testmode'];
        $daysAgo = $rec['extend_by'] - $rec['days'];
        $hasToBe = strtotime('-' . $daysAgo . ' days');

        if ($rec['extend_by'] > 0) {
            /** @var \TYPO3\CMS\Core\Database\DatabaseConnection $dbHandle */
            $dbHandle = $GLOBALS['TYPO3_DB'];

            if ($dbHandle->exec_SELECTgetSingleRow(
                'uid',
                'tx_bpnexpiringfeusers_log',
                "job = $job AND fe_user = $userUid AND deleted = 0 AND testmode = $testmode AND crdate > $hasToBe"
            )
            ) {
                return true;
            }
        } else {
            return true;
        }

        return false;
    }

    /**
     * Checks if a user has a newer exp group record for the groupid found and the days in the future given.
     *
     * @param $rec
     * @param $user
     * @param $grId
     * @param $daysfuture
     * @return bool
     */
    public static function checkForNewerExpRecord(/** @noinspection PhpUnusedParameterInspection */
        $rec,
        $user,
        $grId,
        $daysfuture
    )
    {
        $expGrFields = self::getExpiringGroupsArray($user['tx_itypoexpiringfegroups_groups']);

        foreach ($expGrFields as $expGr) {
            if ($expGr[0] == $grId) {
                // check if enddate newer than $daysfuture
                if ($expGr[2] > $daysfuture) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Converts the expiring groups for a user to an array.
     *
     * @param $expiringGroups
     * @return array
     */
    public static function getExpiringGroupsArray($expiringGroups)
    {
        $expGrFields = [];

        foreach (GeneralUtility::trimExplode('*', $expiringGroups) as $entry) {
            if (!empty($entry)) {
                $expGrFields[] = GeneralUtility::trimExplode('|', $entry);
            }
        }

        return $expGrFields;
    }
}
