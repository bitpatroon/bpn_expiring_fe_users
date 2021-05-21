<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2014 Sander Leeuwesteijn | iTypo <info@itypo.nl>
*  All rights reserved
*/

class expireAction
{
    /**
     * Sets an fe_user to expire after x days.
     *
     * @param		array		$rec: row with job record info
     * @param		array		$users: array with users
     */
    public function main($rec, $users)
    {
        foreach ($users as $user) {
            // if user has no endtime, use now.
            if ($user['endtime'] == 0) {
                $user['endtime'] = time();
            }

            // calculate new endtime
            $newtime = strtotime('+' . $rec['expires_in'] . ' days', $user['endtime']);

            // set user to expire
            tx_bpnexpiringfeusers_helpers::setAccountExpirationDate($user['uid'], $newtime, $rec);
        }
    }
}
