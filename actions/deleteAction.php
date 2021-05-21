<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Sander Leeuwesteijn | iTypo <info@itypo.nl>
 *  All rights reserved
 */

class deleteAction
{
    /**
     * Marks the fe_user deleted.
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
                    'deleted' => '1',
                ];

                /** @var \TYPO3\CMS\Core\Database\DatabaseConnection $dbHandle */
                $dbHandle = $GLOBALS['TYPO3_DB'];
                $dbHandle->exec_UPDATEquery('fe_users', 'uid = ' . (int)$user['uid'], $updateFields);
                tx_bpnexpiringfeusers_helpers::log($rec, $user['uid'], 'deleted', 'fe_user has been marked deleted.');
            } else {
                tx_bpnexpiringfeusers_helpers::log($rec, $user['uid'], 'info', 'fe_user has been marked deleted.');
            }
        }
    }
}
