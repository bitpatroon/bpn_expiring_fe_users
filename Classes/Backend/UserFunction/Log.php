<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 21-5-2021 21:54
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

use BPN\BpnExpiringFeUsers\Domain\Repository\ConfigRepository;
use BPN\BpnExpiringFeUsers\Domain\Repository\FrontEndUserRepository;
use BPN\BpnExpiringFeUsers\Traits\LogTrait;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class Log extends AbstractFormElement
{
    use LogTrait;

    public function render()
    {
        $resultArray = $this->mergeChildReturnIntoExistingResult(
            $this->initializeResultArray(),
            $this->renderFieldInformation(),
            false
        );

        $databaseRow = $this->data['databaseRow'];
        $table = $this->data['tableName'];
        if ($table != ConfigRepository::TABLE) {
            return $this->showError(
                'Not allowed to use this control on another record other than ' . ConfigRepository::TABLE,
                $resultArray
            );
        }

        if (!is_numeric($databaseRow['uid'])) {
            return $this->showError('New Record detected. Please save first. [1621631328224]', $resultArray);
        }

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var ConfigRepository $configRepository */
        $configRepository = $objectManager->get(ConfigRepository::class);

        /** @var FrontEndUserRepository $frontEndUserRepository */
        $frontEndUserRepository = $objectManager->get(FrontEndUserRepository::class);

        $uid = $databaseRow['uid'];

        $logs = $this->getLogRepository()->getByJobByUserWithUser($uid);
        if (!$logs) {
            return $this->showError('No log items found [1621679374933]', $resultArray);
        }

        $numResults = count($logs);

        $result = [];
        if (is_array($logs) && count($logs)) {
            $result[] = "<div>Displaying newest <b>{$numResults}</b> log entries.</div>";
            $result[] = '<table class="table-striped">';
            $result[] = '<thead><tr>';
            $result[] = '<th class="col-md-1 font-weight-bold text-left">Uid</th>';
            $result[] = '<th class="col-md-2 font-weight-bold text-left">Date</th>';
            $result[] = '<th class="col-md-1 font-weight-bold text-left">Action</th>';
            $result[] = '<th class="col font-weight-bold text-left">E-mail</th>';
            $result[] = '<th class="col-md-2 font-weight-bold text-left">Name</th>';
            $result[] = '<th class="col-md-2 font-weight-bold text-left">Message</th>';
            $result[] = '</tr></thead>';
            $result[] = '<tbody>';

            foreach ($logs as $log) {
                $result[] = '<tr>';
                $result[] = '<td class="col-md-1 text-left">' . $log['uid'] . '&nbsp;</td>';
                $result[] = '<td class="col-md-2 text-left">' . ($log['crdate'] ? date(
                        'd-m-y H:i:s',
                        $log['crdate']
                    ) : '-') . '&nbsp;</td>';
                $result[] = '<td class="col-md-3 text-left">' . $log['action'] . '&nbsp;</td>';
                $result[] = '<td class="col-md-2 text-left">' . date('d-m-y H:s', $log['fe_user']) . '&nbsp;</td>';
                $result[] = '<td class="col-md-2 text-left">' . date('d-m-y H:s', $log['name']) . '&nbsp;</td>';
                $result[] = '<td class="col-md-2 text-left">' . $log['msg'] . '</td>';
                $result[] = '</tr>';
            }
            $result[] = '</tbody>';
            $result[] = '</table>';
        }

        $resultArray['html'] = implode('', $result);

        return $resultArray;
    }

    protected function showError(string $message, array $resultArray)
    {
        $resultArray['html'] = '<div class="alert alert-warning">' . $message . '</div>';

        return $resultArray;
    }
}
