<?php

declare(strict_types=1);

use BPN\BpnExpiringFeUsers\Domain\Models\Config;
use BPN\BpnExpiringFeUsers\Domain\Models\FrontEndUserGroup;
use BPN\BpnExpiringFeUsers\Domain\Models\FrontEndUser;
use BPN\BpnExpiringFeUsers\Domain\Models\Log;

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