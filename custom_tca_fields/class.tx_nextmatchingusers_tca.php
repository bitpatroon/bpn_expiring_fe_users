<?php



class tx_nextmatchingusers_tca
{
    /**
     * @param $PA
     * @param $fObj
     * @return string
     * @throws Exception
     */
    public function field(/** @noinspection PhpUnusedParameterInspection */
        $PA,
        $fObj
    ) {
        if ((int)$PA['row']['uid']) {	// if its not a new record
            // first we must convert some records to regular uid lists
            // for some reason it comes out like pages_6|Title,pages_45|Foldertitle
            $pattern = '/_([0-9]*)\|/';
            preg_match_all($pattern, $PA['row']['sysfolder'], $matches);
            $PA['row']['sysfolder'] = implode(',', $matches[1]);

            preg_match_all($pattern, $PA['row']['page'], $matches);
            $PA['row']['page'] = implode(',', $matches[1]);

            /* This conversion no longer seems required in TYPO3 7.6
            // memberOf field does not have prefix, so no _
            $pattern = '/([0-9]*)\|/';
            $memberOf = is_array($PA['row']['memberOf'])
                ? implode(',', $PA['row']['memberOf'])
                : $PA['row']['memberOf'];

            preg_match_all($pattern, $memberOf, $matches);
            $PA['row']['memberOf'] = implode(',', $matches[1]);

            // noMemberOf field does not have prefix, so no _
            $pattern = '/([0-9]*)\|/';
            $noMemberOf = is_array($PA['row']['noMemberOf'])
                ? implode(',', $PA['row']['noMemberOf'])
                : $PA['row']['noMemberOf'];

            preg_match_all($pattern, $noMemberOf, $matches);
            $PA['row']['noMemberOf'] = implode(',', $matches[1]);

            // groupsToRemove field does not have prefix, so no _
            $pattern = '/([0-9]*)\|/';
            preg_match_all($pattern, $PA['row']['groupsToRemove'], $matches);
            $PA['row']['groupsToRemove'] = implode(',', $matches[1]);
            */

            if (\tx_bpnexpiringfeusers_helpers::isSummerAndExcluded($PA['row'])) {
                $formField = '<div><p>Suspended due to summer period.</p></div>';
            } else {
                $users = tx_bpnexpiringfeusers_scheduler::findMatchingUsers($PA['row']);
                if (!is_array($users)) {
                    $users = [];
                }
                $numrecords = count($users);
                $value = '';
                if (is_array($users) && $numrecords > 0) {
                    $value .= "<tr><td colspan=\"6\">Displaying <b>$numrecords</b> entries.</td></tr>";
                    $value .= '<tr><th width="50">uid</th><th width="250">Name</th><th width="250">E-mail</th><th width="175">crdate</th><th width="175">lastlogin</th><th width="175">expires</th></tr>';

                    $even = false;
                    foreach ($users as $user) {
                        if ($user['endtime'] > 0) {
                            $endtime = date('d-m-y H:s:i', $user['endtime']);
                        } else {
                            $endtime = '-';
                        }

                        $class = '';
                        if ($even) {
                            $class = ' class="even"';
                        }

                        $value .= '<tr' . $class . '><td>' . $user['uid'] . '</td><td>' . $user['name'] . '</td><td>' . $user['email'] . '</td><td>' . date('d-m-y H:s:i', $user['crdate']) . '</td><td>' . date('d-m-y H:s:i', $user['lastlogin']) . '</td><td>' . $endtime . '</td></tr>';
                        $even = !$even;
                    }

                    $formField = '<table border="1" cellspacing="0" class="bpn_expiring_fe_users nextmatchingusers">' . $value . '</table>';
                } else {
                    $formField = '<div><p>None.</p></div>';
                }
            }
        } else {
            $formField = '<div><p>New record, save first to see currently matching users.</p></div>';
        }

        return $formField;
    }
}
