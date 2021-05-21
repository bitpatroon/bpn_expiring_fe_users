<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Sander Leeuwesteijn | iTypo <info@itypo.nl>
*  All rights reserved
*
*  Custom field displaying entries from the log for this record.
*
*/

class tx_log_tca
{

    /** @var $db \TYPO3\CMS\Core\Database\DatabaseConnection */
    public $db;

    /**
     * @var
     */
    public $dbHandle;

    /**
     * @param $PA
     * @param $fObj
     * @return string
     */
    public function field(/** @noinspection PhpUnusedParameterInspection */
        $PA,
        $fObj
    ) {
        $this->db = $GLOBALS['TYPO3_DB'];
        $this->dbHandle = $this->db->getDatabaseHandle();

        if ((int)$PA['row']['uid']) {	// if its not a new record
            $uid = $PA['row']['uid'];

            // count total
            $res = mysqli_query($this->dbHandle, "
			SELECT COUNT(uid) as total
			FROM tx_bpnexpiringfeusers_log AS s
			WHERE s.job = $uid
			AND s.deleted = 0
			");
            $row = $this->db->sql_fetch_row($res);
            $total = $row[0];

            // fetch and join with user
            $res = mysqli_query($this->dbHandle, "
			SELECT s.uid,s.crdate,s.fe_user,s.action,s.testmode,s.msg,f.name
			FROM tx_bpnexpiringfeusers_log AS s
			LEFT JOIN fe_users AS f
			ON s.fe_user = f.uid
			WHERE s.job = $uid
			AND s.deleted = 0
			ORDER BY s.uid DESC
			LIMIT 2500
			");

            $numresults = $this->db->sql_num_rows($res);
            $value = '';

            if ($res && $numresults > 0) {
                $value .= "<tr><td colspan=\"6\">Displaying <b>$numresults</b> entries. (Total: $total) Ordered by uid DESC. Displaying max 2500.</td></tr>";
                $value .= '<tr><th width="40">uid</th><th width="125">Date</th><th width="75">Action</th><th width="40">fe_user</th><th width="225">Name</th><th width="500">Message</th></tr>';

                $even = false;
                while ($row = $this->db->sql_fetch_assoc($res)) {
                    $testMode = null;
                    if ($row['testmode'] == '1') {
                        $testMode = '[TESTMODE] ';
                    }
                    $class = '';
                    if ($even) {
                        $class = ' class="even"';
                    }

                    $value .= '<tr' . $class . '>' .
                        '<td>' . $row['uid'] . '</td>' .
                        '<td>' . date('d-m-y H:i:s', $row['crdate']) . '</td>' .
                        '<td>' . $row['action'] . '</td>' .
                        '<td>' . $row['fe_user'] . '</td>' .
                        '<td>' . $row['name'] . '</td>' .
                        '<td>' . $testMode . $row['msg'] . '</td>' .
                        '</tr>';
                    $even = !$even;
                }

                $formField = '<table border="1" cellspacing="0" class="itypo_expiring_fe_users log>' . $value . '</table>';
            } else {
                $formField = '<div><p>Log is empty.</p></div>';
            }
        } else {
            $formField = '<div><p>New record so the log is empty!</p></div>';
        }

        return $formField;
    }
}
