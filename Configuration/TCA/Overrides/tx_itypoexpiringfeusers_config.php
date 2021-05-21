<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// Adds a checkbox and radiobutton option when this extension is loaded
if (ExtensionManagementUtility::isLoaded('itypo_expiring_fe_groups')) {
    // add condition
    $temporaryColumn = [
        'condition20' => [
            'exclude' => 0,
            'label' => '',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.condition20', ''],
                ],
            ]
        ]
    ];

    ExtensionManagementUtility::addTCAcolumns(
            'tx_bpnexpiringfeusers_config',
            $temporaryColumn
    );

    ExtensionManagementUtility::addFieldsToPalette(
            'tx_bpnexpiringfeusers_config',
            '3',
            'condition20,--linebreak--,',
            'before:condition1'
    );

    // add type of reactivate link
    $GLOBALS['TCA']['tx_bpnexpiringfeusers_config']['columns']['reactivate_link']['config']['items'][] = ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.reactivate_link.I.20', 20];
}