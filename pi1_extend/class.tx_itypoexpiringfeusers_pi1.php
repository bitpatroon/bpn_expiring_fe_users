<?php

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Sander Leeuwesteijn | iTypo <info@itypo.nl>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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

/**
 * Class tx_bpnexpiringfeusers_pi1
 */
class tx_bpnexpiringfeusers_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    /**
     * @var string
     */
    public $prefixId = 'tx_bpnexpiringfeusers_pi1';                            // Same as class name

    /**
     * @var string
     */
    public $scriptRelPath = 'pi1_extend/class.tx_bpnexpiringfeusers_pi1.php';    // Path to this script relative to the extension dir.

    /**
     * @var string
     */
    public $extKey = 'itypo_expiring_fe_users';                                // The extension key.

    /** @var $db DatabaseConnection */
    public $db;

    /**
     * @var
     */
    public $dbHandle;

    /**
     * @var
     */
    public $get;

    /**
     * The main method of the PlugIn
     *
     * @param    string $content : The PlugIn content
     * @param    array $conf : The PlugIn configuration
     * @return   string The content that is displayed on the website
     */
    public function main(/** @noinspection PhpUnusedParameterInspection */
        $content,
        $conf
    )
    {
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL();

        $this->db = $GLOBALS['TYPO3_DB'];
        $this->dbHandle = $this->db->getDatabaseHandle();

        // fetch GET parameters
        $this->get = GeneralUtility::_GET();

        $params = '/?&u=' . $this->get['u'] . '&t=' . $this->get['t'] . '&e=' . $this->get['e'] . '&r=' . $this->get['r'];
        if ($this->get['g']) {
            $params .= '&g=' . $this->get['g'];
        }

        $c1 = $this->get['cHash'];
        $c2 = GeneralUtility::hmac($params);

        if ($c1 === $c2) {
            // check if link is still valid
            $thirtyonedaysago = strtotime('-31 days');
            if ($this->get['t'] > $thirtyonedaysago) {
                if (!$this->accountAlreadyExtended($this->get['u'], $this->get['r'], $this->get['t'])) {
                    // for logging
                    $rec['uid'] = (int)$this->get['r'];

                    // group extending
                    if ($this->get['g']) {
                        // we extend the given group by the set amount of days (by adding a new entry)
                        $endgroup = strtotime('+' . (int)$this->get['e'] . ' days');
                        $group_entry = (int)$this->get['g'] . '|' . time() . '|' . $endgroup . '*';
                        $query = "UPDATE fe_users SET tx_itypoexpiringfegroups_groups = CONCAT(tx_itypoexpiringfegroups_groups,'$group_entry'), tx_accountinfodialog_dirty = 1 WHERE uid = " . (int)$this->get['u'];
                        mysqli_query($this->dbHandle, $query);

                        tx_bpnexpiringfeusers_helpers::log($rec, (int)$this->get['u'], 'extend', 'fe_user has extended group ' . (int)$this->get['g'] . ' until ' . date('d-m-y H:i:s', $endgroup));

                        // update the lastlogin time for this user, so users wont get mailed again when the has not logged in for condition is set
                        $this->db->exec_UPDATEquery('fe_users', 'uid = ' . (int)$this->get['u'], ['lastlogin' => time()]);

                        $content = htmlspecialchars($this->pi_getLL('pi1.extendedgroup'));
                    } else {    // account extending
                        // fetch current endtime, if any
                        $res = $this->db->exec_SELECTquery('endtime', 'fe_users', 'uid = ' . (int)$this->get['u'] . ' AND endtime > 0', '', '', '1');
                        if ($this->db->sql_num_rows($res) === 1) {
                            $row = $this->db->sql_fetch_assoc($res);
                            $endtime = (int)$row['endtime'];
                        } else {
                            $endtime = time();
                        }

                        // [SPL-2519] time must be at least now
                        if ($endtime < time()) {
                            $endtime = time();
                        }

                        // calculate and set new endtime
                        $endtime = strtotime('+' . (int)$this->get['e'] . ' days', $endtime);
                        $updateFields = [
                            'tstamp' => time(),
                            'endtime' => $endtime,
                            // update the lastlogin time for this user, so users wont get mailed again when the has not logged in for condition is set
                            'lastlogin' => time()
                        ];
                        $this->db->exec_UPDATEquery('fe_users', 'uid = ' . (int)$this->get['u'], $updateFields);

                        tx_bpnexpiringfeusers_helpers::log($rec, (int)$this->get['u'], 'extend', 'fe_user has extended his account by e-mail link until ' . date('d-m-y H:i:s', $endtime));

                        $content = htmlspecialchars($this->pi_getLL('pi1.extended'));

                        // Ensure all cookies are 'reset'
                        \BPN\BpnExpiringFeUsers\Helpers\HookHelpers::processHook($this, 'on_after_extend');
                    }

                    // Add user to salesforce sync queue when account or groups were extended
                    if (ExtensionManagementUtility::isLoaded('spl_salesforce')) {

                        // Hardcoded check for folder, sucks but saving time now in old code
                        $pid = 0;
                        $res = $this->db->exec_SELECTquery('pid', 'fe_users', 'uid = ' . (int)$this->get['u'] . ' AND tx_splsalesforce_allowsync = 1', '', '', '1');
                        if ($this->db->sql_num_rows($res) === 1) {
                            $row = $this->db->sql_fetch_assoc($res);
                            $pid = (int)$row['pid'];
                        }

                        if ($pid === 422 || $pid === 116551) {
                            $insertFields = [
                                'crdate' => time(),
                                'tstamp' => time(),
                                'cruser_id' => 35,  // _cli_lowlevel be_user
                                'feuser' => (int)$this->get['u'],
                                'task' => 't3_account'
                            ];

                            $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_splsalesforce_domain_model_queueitem', $insertFields);

                            tx_bpnexpiringfeusers_helpers::log($rec, (int)$this->get['u'], 'salesforce', 'account has been added to the salesforce sync queue because the account or groups were extended');
                        }
                    }

                } else {
                    // De melding dat het account al verlengd is uitgeschakeld en gelijk gemaakt aan de melding dat het zojuist verlengd is.
                    // Om een onduidelijke reden worden sommigen mensen geredirect en komen ze 2x op de pagina waardoor de melding in beeld komt dat er al verlengd is, wat voor onduidelijkheid zorgt en vragen op de helpdesk.
                    // Komt waarschijnlijk door SPL-2519 , SPL-2522 en de on_after_extend hook hierboven, maar dit is een makkelijke oplossing die net zo goed werkt.
                    //$content = htmlspecialchars($this->pi_getLL('pi1.alreadyextended'));
                    $content = htmlspecialchars($this->pi_getLL('pi1.extended'));
                }
            } else {
                $content = htmlspecialchars($this->pi_getLL('pi1.expired'));
            }
        } else {
            $content = htmlspecialchars($this->pi_getLL('pi1.invalidhash'));
        }

        return $this->pi_wrapInBaseClass($content);
    }

    /**
     * Determines if an account has already been extended by a particular job.
     * Sees if there already is an extend entry between time of link generation and max validity days.
     *
     * @param    int $feuser : fe_user uid
     * @param    int $job : job uid
     * @param   int $linktimestamp : timestamp when the link was made
     * @return    bool    true if account has already been extended by this job, false if not
     */
    public function accountAlreadyExtended($feuser, $job, $linktimestamp)
    {
        $thirtyonedaysahead = strtotime('+31 days', $linktimestamp);
        $res = $this->db->exec_SELECTquery('uid', 'tx_bpnexpiringfeusers_log', "fe_user = $feuser AND job = $job AND crdate >= $linktimestamp AND crdate <= $thirtyonedaysahead AND deleted = 0 AND action = 'extend'");
        if ($this->db->sql_num_rows($res) > 0) {
            return true;
        }
        return false;
    }
}
