<?php

if (!defined('TYPO3_MODE')) {
    die('¯\_(ツ)_/¯');
}

call_user_func(
    function () {
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

        // Register pi1 plugin (extend account)
        TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
            'bpn_expiring_fe_users',
            'pi1_extend/class.tx_bpnexpiringfeusers_pi1.php',
            '_pi1',
            'list_type',
            0
        );

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1621627543] = [
            'nodeName' => 'expiringUsersallMatchingUsers',
            'priority' => 40,
            'class'    => \BPN\BpnExpiringFeUsers\Backend\UserFunction\AllMatchingUsers::class,
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1621627544] = [
            'nodeName' => 'expiringUsersNextMatching',
            'priority' => 40,
            'class'    => \BPN\BpnExpiringFeUsers\Backend\UserFunction\NextMatchingUsers::class
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1621627545] = [
            'nodeName' => 'expiringUserslog',
            'priority' => 40,
            'class'    => \BPN\BpnExpiringFeUsers\Backend\UserFunction\Log::class
        ];

        // TODO: replace with event !
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FrontendUserService::class]['on_notify_expired_user'][100]
            = BPN\BpnExpiringFeUsers\Hooks\NotifyExpiringUsersHook::class . '->onNotifyExpiredUser';
    }
);
