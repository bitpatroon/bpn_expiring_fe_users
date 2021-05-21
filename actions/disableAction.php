<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Sander Leeuwesteijn | iTypo <info@itypo.nl>
 *  All rights reserved
 */

class disableAction
{
    /**
     * Disables the fe_user.
     *
     * @param        array $rec : row with job record info
     * @param        array $users : array with users
     */
    public function main($rec, $users)
    {
        foreach ($users as $user) {
            if ($rec['testmode'] === '0') {
                $updateFields = [
                    'tstamp' => time(),
                    'disable' => '1',
                ];
                /** @var \TYPO3\CMS\Core\Database\DatabaseConnection $dbHandle */
                $dbHandle = $GLOBALS['TYPO3_DB'];
                $dbHandle->exec_UPDATEquery('fe_users', 'uid = ' . (int)$user['uid'], $updateFields);

                tx_bpnexpiringfeusers_helpers::log($rec, $user['uid'], 'disabled', 'fe_user has been disabled.');
            } else {
                tx_bpnexpiringfeusers_helpers::log($rec, $user['uid'], 'info', 'fe_user has been disabled.');
            }
        }
    }
}
