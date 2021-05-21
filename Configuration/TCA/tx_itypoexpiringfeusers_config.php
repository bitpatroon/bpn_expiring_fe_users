<?php
defined('TYPO3_MODE') or die('¯\_(ツ)_/¯');

return [
    'ctrl'      => [
        'title'          => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config',
        'label'          => 'title',
        'tstamp'         => 'tstamp',
        'crdate'         => 'crdate',
        'cruser_id'      => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'delete'         => 'deleted',
        'type'           => 'todo',
        'enablecolumns'  => [
            'disabled' => 'hidden',
        ],
        'iconfile'       => 'EXT:itypo_expiring_fe_users/ext_icon.gif',
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,testmode,title,sysfolder,memberOf,andor,noMemberOf,andor_not,conditions,days,todo,email_fromName,email_from,email_bcc,email_subject,email_text,expires_in,reactivate_link,extend_by,page'
    ],
    'columns'   => [
        'todo'              => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.todo',
            'config' => [
                'type'       => 'select',
                'renderType' => 'selectSingle',
                'items'      => [
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.todo.I.0', 0],
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.todo.I.1', 1],
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.todo.I.2', 2],
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.todo.I.3', 3],
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.todo.I.4', 4],
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.todo.I.5', 5],
                ],
                'size'       => 1,
                'maxitems'   => 1,
            ]
        ],
        'hidden'            => [
            'exclude' => 1,
            'label'   => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.disabled',
            'config'  => [
                'type'    => 'check',
                'default' => '1'
            ]
        ],
        'testmode'          => [
            'exclude' => 1,
            'label'   => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.testmode',
            'config'  => [
                'type'    => 'check',
                'default' => '1'
            ]
        ],
        'limiter'           => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.limiter',
            'config' => [
                'type'    => 'input',
                'size'    => 5,
                'default' => '100',
                'eval'    => 'required,int',
            ]
        ],
        'title'             => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required,trim',
            ]
        ],
        'excludesummer'     => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.excludesummer',
            'config' => [
                'type' => 'check',
            ]
        ],
        'sysfolder'         => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.sysfolder',
            'config' => [
                'type'          => 'group',
                'internal_type' => 'db',
                'allowed'       => 'pages',
                'size'          => 5,
                'maxitems'      => 99,
            ]
        ],
        'memberOf'          => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.memberOf',
            'config' => [
                'type'                             => 'select',
                'renderType'                       => 'selectMultipleSideBySide',
                'enableMultiSelectFilterTextfield' => 1,
                'size'                             => 15,
                'maxitems'                         => 99,
                'foreign_table'                    => 'fe_groups',
                'foreign_table_where'              => 'ORDER BY fe_groups.title',
            ]
        ],
        'andor'             => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.andor',
            'config' => [
                'type'       => 'select',
                'renderType' => 'selectSingle',
                'items'      => [
                    ['AND, users should be a member of all groups selected', 'AND'],
                    ['OR, users should have at least one of the groups selected', 'OR'],
                ],
                'size'       => 1,
                'maxitems'   => 1,
            ]
        ],
        'noMemberOf'        => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.noMemberOf',
            'config' => [
                'type'                             => 'select',
                'renderType'                       => 'selectMultipleSideBySide',
                'enableMultiSelectFilterTextfield' => 1,
                'size'                             => 10,
                'maxitems'                         => 99,
                'foreign_table'                    => 'fe_groups',
                'foreign_table_where'              => 'ORDER BY fe_groups.title',
            ]
        ],
        'andor_not'         => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.andor_not',
            'config' => [
                'type'       => 'select',
                'renderType' => 'selectSingle',
                'items'      => [
                    ['AND, users should NOT be a member of all groups selected', 'AND'],
                    ['OR, users should NOT be a member of one of the groups selected', 'OR'],
                ],
                'size'       => 1,
                'maxitems'   => 1,
            ]
        ],
        'expiringGroup'     => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.expiringGroup',
            'config' => [
                'type'                             => 'select',
                'renderType'                       => 'selectMultipleSideBySide',
                'enableMultiSelectFilterTextfield' => 1,
                'size'                             => 10,
                'maxitems'                         => 99,
                'foreign_table'                    => 'fe_groups',
                'foreign_table_where'              => 'ORDER BY fe_groups.title',
            ]
        ],
        'groupsToRemove'    => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.groupsToRemove',
            'config' => [
                'type'                             => 'select',
                'renderType'                       => 'selectMultipleSideBySide',
                'enableMultiSelectFilterTextfield' => 1,
                'size'                             => 10,
                'maxitems'                         => 99,
                'foreign_table'                    => 'fe_groups',
                'foreign_table_where'              => 'ORDER BY fe_groups.title',
            ]
        ],
        'condition1'        => [
            'label'  => '',
            'config' => [
                'type'  => 'check',
                'items' => [
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.condition1', ''],
                ],
            ]
        ],
        'condition2'        => [
            'label'  => '',
            'config' => [
                'type'  => 'check',
                'items' => [
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.condition2', ''],
                ],
            ]
        ],
        'condition3'        => [
            'label'  => '',
            'config' => [
                'type'  => 'check',
                'items' => [
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.condition3', ''],
                ],
            ]
        ],
        'condition4'        => [
            'label'  => '',
            'config' => [
                'type'  => 'check',
                'items' => [
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.condition4', ''],
                ],
            ]
        ],
        'condition5'        => [
            'label'  => '',
            'config' => [
                'type'  => 'check',
                'items' => [
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.condition5', ''],
                ],
            ]
        ],
        'condition6'        => [
            'label'  => '',
            'config' => [
                'type'  => 'check',
                'items' => [
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.condition6', ''],
                ],
            ]
        ],
        'condition7'        => [
            'label'  => '',
            'config' => [
                'type'  => 'check',
                'items' => [
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.condition7', ''],
                ],
            ]
        ],
        'condition8'        => [
            'label'  => '',
            'config' => [
                'type'  => 'check',
                'items' => [
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.condition8', ''],
                ],
            ]
        ],
        'days'              => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.days',
            'config' => [
                'type'    => 'input',
                'size'    => 5,
                'default' => '365',
                'eval'    => 'required,int',
            ]
        ],
        'email_test'        => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.email_test',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,nospace,lower',
            ]
        ],
        'email_fromName'    => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.email_fromName',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required,trim',
            ]
        ],
        'email_from'        => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.email_from',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required,trim,nospace,lower',
            ]
        ],
        'email_bcc'         => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.email_bcc',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,nospace,lower',
            ]
        ],
        'email_subject'     => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.email_subject',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required,trim',
            ]
        ],
        'email_text'        => [
            'label'         => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.email_text',
            'config'        => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 5,
                'eval' => 'required',
                'enableRichtext'        => 1,
                'richtextConfiguration' => 'default',
            ],
        ],
        'expires_in'        => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.expires_in',
            'config' => [
                'type'    => 'input',
                'size'    => 5,
                'default' => '0',
                'eval'    => 'required,num',
            ]
        ],
        'reactivate_link'   => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.reactivate_link',
            'config' => [
                'type'    => 'radio',
                'items'   => [
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.reactivate_link.I.0', 0],
                    ['LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.reactivate_link.I.1', 1],
                ],
                'default' => 0,
            ]
        ],
        'extend_by'         => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.extend_by',
            'config' => [
                'type'    => 'input',
                'size'    => 5,
                'default' => 365,
                'eval'    => 'required,int',
            ]
        ],
        'page'              => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.page',
            'config' => [
                'type'    => 'input',
                'size'    => 48,
                'default' => '',
                'eval'    => 'trim',
            ],
        ],
        'allmatchingusers'  => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.allmatchingusers',
            'config' => [
                'type'     => 'user',
                'userFunc' => \tx_allmatchingusers_tca::class . '->field',
            ]
        ],
        'nextmatchingusers' => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.nextmatchingusers',
            'config' => [
                'type'     => 'user',
                'userFunc' => \tx_nextmatchingusers_tca::class . '->field',
            ]
        ],
        'log'               => [
            'label'  => 'LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.log',
            'config' => [
                'type'     => 'user',
                'userFunc' => \tx_log_tca::class . '->field',
            ]
        ],
    ],
    'types'     => [
        0 => ['showitem' => 'todo'],
        1 => ['showitem' => 'todo,hidden,testmode,limiter,title,excludesummer,--div--;Conditions,sysfolder,--palette--;;2,--palette--;;4,expiringGroup,--palette--;LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.conditions;3,days,--div--;E-mail,email_test,email_fromName,email_from,email_bcc,email_subject,email_text,--div--;Account expiration,expires_in,reactivate_link,extend_by,page,--div--;All Matching users,allmatchingusers,--div--;Next Run Matching users,nextmatchingusers,--div--;Log,log'],
        2 => ['showitem' => 'todo,hidden,testmode,limiter,title,--div--;Conditions,sysfolder,--palette--;;2,--palette--;;4,--palette--;LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.conditions;3,days,--div--;All Matching users,allmatchingusers,--div--;Next Run Matching users,nextmatchingusers,--div--;Log,log'],
        3 => ['showitem' => 'todo,hidden,testmode,limiter,title,--div--;Conditions,sysfolder,--palette--;;2,--palette--;;4,--palette--;LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.conditions;3,days,--div--;All Matching users,allmatchingusers,--div--;Next Run Matching users,nextmatchingusers,--div--;Log,log'],
        4 => ['showitem' => 'todo,hidden,testmode,limiter,title,--div--;Conditions,sysfolder,--palette--;;2,--palette--;;4,--palette--;LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.conditions;3,days,--div--;Groups to remove,groupsToRemove,--div--;All Matching users,allmatchingusers,--div--;Next Run Matching users,nextmatchingusers,--div--;Log,log'],
        5 => ['showitem' => 'todo,hidden,testmode,limiter,title,--div--;Conditions,sysfolder,--palette--;;2,--palette--;;4,--palette--;LLL:EXT:itypo_expiring_fe_users/locallang_db.xlf:tx_bpnexpiringfeusers_config.conditions;3,days,--div--;Account expiration,expires_in,--div--;All Matching users,allmatchingusers,--div--;Next Run Matching users,nextmatchingusers,--div--;Log,log'],
    ],
    'palettes'  => [
        2 => ['showitem' => 'memberOf,--linebreak--,andor'],
        3 => ['showitem' => 'condition1,--linebreak--,condition2,--linebreak--,condition3,--linebreak--,condition4,--linebreak--,condition5,--linebreak--,condition6,--linebreak--,condition7,--linebreak--,condition8'],
        4 => ['showitem' => 'noMemberOf,--linebreak--,andor_not'],
    ]
];
