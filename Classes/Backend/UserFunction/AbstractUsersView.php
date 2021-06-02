<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 22-5-2021 13:05
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

namespace BPN\BpnExpiringFeUsers\Backend\UserFunction;

use BPN\BpnChat\Traits\NameServiceTrait;
use BPN\BpnExpiringFeUsers\Traits\LogTrait;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;

abstract class AbstractUsersView extends AbstractFormElement
{
    use LogTrait;
    use NameServiceTrait;

    protected function renderView(array $users, array $resultArray) : array
    {
        $numRecords = count($users);

        $result = [];
        if (is_array($users) && $numRecords > 0) {
            $result[] = "<div>Displaying <b>{$numRecords}</b> entries.</div>";
            $result[] = '<table class="table-striped">';
            $result[] = '<thead><tr>';
            $result[] = '<th class="col-md-1 font-weight-bold text-left" title="Users unique id">UserId</th>';
            $result[] = '<th class="col-md-2 font-weight-bold text-left" title="Users name">Name</th>';
            $result[] = '<th class="col-md-3 font-weight-bold text-left" title="Users e-mail">E-mail</th>';
            $result[] = '<th class="col-md-2 font-weight-bold text-left" title="Users creation date">Created at</th>';
            $result[] = '<th class="col-md-2 font-weight-bold text-left" title="Users last login date">Lastlogin</th>';
            $result[] = '<th class="col-md-2 font-weight-bold text-left" title="User expires at">Expires</th>';
            $result[] = '</tr></thead>';
            $result[] = '<tbody>';

            foreach ($users as $user) {
                $endtime = $user['endtime'] > 0
                    ? date('d-m-y H:i', $user['endtime'])
                    : '-';

                $name = $this->getNameService()->getFullName($user, false) ?: '-';

                $result[] = '<tr>';
                $result[] = '<td class="col-md-1 text-left">' . $user['uid'] . '&nbsp;</td>';
                $result[] = '<td class="col-md-2 text-left">' . $name . '&nbsp;</td>';
                $result[] = '<td class="col-md-3 text-left">' . $user['email'] . '&nbsp;</td>';
                $result[] = '<td class="col-md-2 text-left">' . date('d-m-y H:i', $user['crdate']) . '&nbsp;</td>';
                $result[] = '<td class="col-md-2 text-left">' . date('d-m-y H:i', $user['lastlogin']) . '&nbsp;</td>';
                $result[] = '<td class="col-md-2 text-left">' . $endtime . '</td>';
                $result[] = '</tr>';
            }

            $result[] = '</tbody>';
            $result[] = '</table>';
        }

        $resultArray['html'] = implode('', $result);

        return $resultArray;
    }
}
