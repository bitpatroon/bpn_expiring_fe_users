<?php

declare(strict_types=1);

use BPN\BpnExpiringFeUsers\Domain\Model\Config;
use BPN\BpnExpiringFeUsers\Domain\Model\FrontEndUser;
use BPN\BpnExpiringFeUsers\Domain\Model\FrontEndUserGroup;
use BPN\BpnExpiringFeUsers\Domain\Model\Log;

return [
    FrontEndUserGroup::class => [
        'tableName' => 'fe_groups',
    ],
    FrontEndUser::class      => [
        'tableName' => 'fe_users',
    ],
    Config::class            => [
        'tableName'  => 'tx_bpnexpiringfeusers_config',
        'properties' => [
            'crdate'         => ['fieldName' => 'crdate'],
            'tstamp'         => ['fieldName' => 'tstamp'],
            'cruserId'       => ['fieldName' => 'cruser_id'],
            'deleted'        => ['fieldName' => 'deleted'],
            'hidden'         => ['fieldName' => 'hidden'],
            'testmode'       => ['fieldName' => 'testmode'],
            'limiter'        => ['fieldName' => 'limiter'],
            'title'          => ['fieldName' => 'title'],
            'excludesummer'  => ['fieldName' => 'excludesummer'],
            'sysfolder'      => ['fieldName' => 'sysfolder'],
            'memberOf'       => ['fieldName' => 'member_of'],
            'andor'          => ['fieldName' => 'andor'],
            'noMemberOf'     => ['fieldName' => 'no_member_of'],
            'andorNot'       => ['fieldName' => 'andor_not'],
            'expiringGroup'  => ['fieldName' => 'expiring_group'],
            'groupsToRemove' => ['fieldName' => 'groups_to_remove'],
            'condition1'     => ['fieldName' => 'condition1'],
            'condition2'     => ['fieldName' => 'condition2'],
            'condition3'     => ['fieldName' => 'condition3'],
            'condition4'     => ['fieldName' => 'condition4'],
            'condition5'     => ['fieldName' => 'condition5'],
            'condition6'     => ['fieldName' => 'condition6'],
            'condition7'     => ['fieldName' => 'condition7'],
            'condition8'     => ['fieldName' => 'condition8'],
            'condition20'    => ['fieldName' => 'condition20'],
            'days'           => ['fieldName' => 'days'],
            'todo'           => ['fieldName' => 'todo'],
            'emailTest'      => ['fieldName' => 'email_test'],
            'emailFromName'  => ['fieldName' => 'email_fromName'],
            'emailFrom'      => ['fieldName' => 'email_from'],
            'emailBcc'       => ['fieldName' => 'email_bcc'],
            'emailSubject'   => ['fieldName' => 'email_subject'],
            'emailText'      => ['fieldName' => 'email_text'],
            'expiresIn'      => ['fieldName' => 'expires_in'],
            'reactivateLink' => ['fieldName' => 'reactivate_link'],
            'extendBy'       => ['fieldName' => 'extend_by'],
            'page'           => ['fieldName' => 'page'],
        ],
    ],
    Log::class               => [
        'tableName' => 'tx_bpnexpiringfeusers_log',
    ],
];
