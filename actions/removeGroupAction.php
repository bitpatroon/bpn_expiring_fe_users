<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Sander Leeuwesteijn | iTypo <info@itypo.nl>
 *  All rights reserved
 */

class removeGroupAction
{
    /**
     * Removes one or more groups from an fe_user.
     *
     * @param        array $rec : row with job record info
     * @param        array $users : array with users
     */
    public function main($rec, $users)
    {
        foreach ($users as $user) {
            if ($rec['testmode'] === '0') {
                $currentGroups = explode(',', $user['usergroup']);
                $groupsToRemove = explode(',', $rec['groupsToRemove']);
                $newGroups = implode(',', array_diff($currentGroups, $groupsToRemove));

                $updateFields = [
                    'tstamp' => time(),
                    'usergroup' => $newGroups,
                    'tx_accountinfodialog_dirty' => 1       // mark account dirty to ensure validation upon next login.
                ];

                /** @var \TYPO3\CMS\Core\Database\DatabaseConnection $dbHandle */
                $dbHandle = $GLOBALS['TYPO3_DB'];
                $dbHandle->exec_UPDATEquery('fe_users', 'uid = ' . (int)$user['uid'], $updateFields);

                tx_bpnexpiringfeusers_helpers::log($rec, $user['uid'], 'removedgroup', 'groups ' . $rec['groupsToRemove'] . ' have been removed from user.');
            } else {
                tx_bpnexpiringfeusers_helpers::log($rec, $user['uid'], 'info', 'groups ' . $rec['groupsToRemove'] . ' have been removed from user.');
            }
        }
    }
}
