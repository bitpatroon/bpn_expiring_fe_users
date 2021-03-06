<?php

$EM_CONF[$_EXTKEY] = [
    'title'            => 'BPN Expiring FE Users',
    'description'      => 'Detects an expiring user and sends that person an email with a link to extend the account.',
    'category'         => 'be',
    'author'           => 'Sjoerd Zonneveld',
    'author_email'     => 'typo3@bitpatroon.nl',
    'state'            => 'stable',
    'internal'         => '',
    'uploadfolder'     => 0,
    'clearCacheOnLoad' => 0,
    'author_company'   => 'Bitpatroon',
    'version'          => '10.4',
    'constraints'      => [
        'depends'   => [
            'typo3'                  => '10.4.0 - 10.9.99',
            'bpn_expiring_fe_groups' => '',
        ],
        'conflicts' => [
            'itypo_expiring_fe_user' => '',
        ],
    ],
];
