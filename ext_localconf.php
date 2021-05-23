<?php

if (!defined('TYPO3_MODE')) {
    die('¯\_(ツ)_/¯');
}

call_user_func(
    function () {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'BpnExpiringFeUsers',
            'Extend',
            [
                \BPN\BpnExpiringFeUsers\Controller\ExtendController::class => 'extend',
            ],
            [
                \BPN\BpnExpiringFeUsers\Controller\ExtendController::class => 'extend',
            ]
        );

        // Add TSconfig to disable all RTE buttons and only show the basic ones
        TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            '
            RTE.config.tx_bpnexpiringfeusers_config.email_text {
                hideButtons = *
                showButtons = bold,italic,chMode
            }
        '
        );

        // Register scheduler task
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_bpnexpiringfeusers_scheduler'] = [
            'extension'   => 'bpn_expiring_fe_users',
            'title'       => 'LLL:EXT:bpn_expiring_fe_users/Resources/Private/Language/locallang_db.xlf/locallang_db.xlf:scheduler.title',
            'description' => 'LLL:EXT:bpn_expiring_fe_users/Resources/Private/Language/locallang_db.xlf/locallang_db.xlf:scheduler.description',
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1621627543] = [
            'nodeName' => 'expiringUsersallMatchingUsers',
            'priority' => 40,
            'class'    => \BPN\BpnExpiringFeUsers\Backend\UserFunction\AllMatchingUsers::class,
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1621627544] = [
            'nodeName' => 'expiringUsersNextMatching',
            'priority' => 40,
            'class'    => \BPN\BpnExpiringFeUsers\Backend\UserFunction\NextMatchingUsers::class,
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1621627545] = [
            'nodeName' => 'expiringUserslog',
            'priority' => 40,
            'class'    => \BPN\BpnExpiringFeUsers\Backend\UserFunction\Log::class,
        ];

        $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_bpnexpiringfeusers'] = \BPN\BpnExpiringFeUsers\Start::class.'::process';

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\BPN\BpnExpiringFeUsers\Backend\DataProviders\ExtendLinkDataProvider::class] = [
            'depends' => [TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class],
        ];
    }
);
