<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 23-5-2021 18:42
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

namespace BPN\BpnExpiringFeUsers\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentRepository
{
    const TABLE = 'tt_content';


    public function findActivePluginPage(bool $mustNotBeRestricted = true): array
    {
        $table = self::TABLE;

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $queryBuilder
            ->select('pages.uid', 'pages.pid', 'pages.slug')
            ->from($table)
            ->leftJoin(
                $table,
                'pages',
                'pages',
                $queryBuilder->expr()->eq(
                    $table.'.pid',
                    $queryBuilder->quoteIdentifier('pages.uid')
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter('list', Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'list_type',
                    $queryBuilder->createNamedParameter('bpnexpiringfeusers_extend', Connection::PARAM_STR)
                ),
            );

        $data = $queryBuilder->execute()->fetchAllAssociative();

        if (!$data) {
            return [];
        }

        foreach ($data as $row) {
            if ($mustNotBeRestricted) {
                $userGroups = GeneralUtility::intExplode(',', $row['fe_group'], true);
                if ($userGroups) {
                    continue;
                }
            }

            return $row;
        }

        return [];
    }
}
