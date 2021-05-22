<?php

declare(strict_types=1);

use BPN\BpnExpiringFeUsers\Domain\Model\Config;
use BPN\BpnExpiringFeUsers\Domain\Model\FrontEndUserGroup;
use BPN\BpnExpiringFeUsers\Domain\Model\FrontEndUser;
use BPN\BpnExpiringFeUsers\Domain\Model\Log;

return [
    FrontEndUserGroup::class => [
        'tableName' => 'fe_groups',
    ],
    FrontEndUser::class      => [
        'tableName' => 'fe_users',
    ],
    Config::class            => [
        'tableName' => 'tx_bpnexpiringfeusers_config',
    ],
    Log::class               => [
        'tableName' => 'tx_bpnexpiringfeusers_log',
    ]
];