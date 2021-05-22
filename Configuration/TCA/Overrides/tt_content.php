<?php

if (!defined('TYPO3_MODE')) {
    die('¯\_(ツ)_/¯');
}

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

//ExtensionManagementUtility::addPlugin(
//    [
//        'LLL:EXT:bpn_expiring_fe_users/Resources/Private/Language/locallang_db.xlf:tt_content.list_type_pi1',
//        'bpn_expiring_fe_users_pi1',
//        'EXT:bpn_expiring_fe_users/ext_icon.png'
//    ],
//    'list_type',
//    'bpn_expiring_fe_users'
//);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3_MODE') or die('¯\_(ツ)_/¯');

ExtensionUtility::registerPlugin('bpn_expiring_fe_users', 'extend', 'BPN Extend User Handler');

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['bpnexpiringfeusers_extend'] = 'layout,select_key,recursive';

/** @var IconRegistry $iconRegistry */
$iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
$iconRegistry->registerIcon(
    'bpn_expiring_fe_users-expusers',
    BitmapIconProvider::class,
    ['source' => 'EXT:bpn_expiring_fe_users/Resources/Public/Images/user-and-clock_icon.png']
);

#--- Add Module types for pages
$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-expusers'] = 'bpn_expiring_fe_users-expusers';
$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = array(
    'Expiring users',
    'expusers',
    'bpn_expiring_fe_users-expusers'
);
