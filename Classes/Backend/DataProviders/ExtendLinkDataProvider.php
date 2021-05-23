<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Frans van der Veen, de juiste oplossing, SPL
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

namespace BPN\BpnExpiringFeUsers\Backend\DataProviders;

use BPN\BpnExpiringFeUsers\Domain\Repository\ContentRepository;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ExtendLinkDataProvider implements FormDataProviderInterface
{
    /** @var string[] */
    protected $tableNames;

    /**
     * @var array
     */
    protected $allowedTables = [
        'tx_bpnexpiringfeusers_config',
    ];

    /**
     * Add form data to result array.
     *
     * @param array $result Initialized result array
     *
     * @return array Result filled with more data
     */
    public function addData(array $result)
    {
        $table = $result['tableName'];
        if (!in_array($table, $this->allowedTables)) {
            return $result;
        }
        /** @var ContentRepository $contentRepository */
        $contentRepository = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(ContentRepository::class);

        $pageRecord = $contentRepository->findActivePluginPage();
        if ($pageRecord) {
            $uri = $pageRecord['slug'];
            if (!$uri) {
                $uri = '/index.php?id='.$pageRecord['uid'];
            }
            $url = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST').$uri;
            $result['processedTca']['columns']['page']['config']['default'] = $url;

            if (!$result['databaseRow']['page'] && (int) $result['databaseRow']['reactivate_link']) {
                $result['databaseRow']['page'] = $url;
            }
        }

        return $result;
    }
}
