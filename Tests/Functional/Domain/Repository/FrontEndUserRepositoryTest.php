<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 21-5-2021 13:44
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

namespace BPN\BpnExpiringFeUsers\Tests\Functional\Domain\Repository;

use BPN\BpnExpiringFeUsers\Domain\Models\Config;
use BPN\BpnExpiringFeUsers\Domain\Repository\ExpiringGroupRepository;
use BPN\BpnExpiringFeUsers\Domain\Repository\FrontEndUserRepository;
use BPN\BpnExpiringFeUsers\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class FrontEndUserRepositoryTest extends FunctionalTestCase
{
    const USER_ACCOUNT_NOT_LOGGEDIN_FOR = 1;
    const USER_ACCOUNT_OLDER_THAN = 2;
    const USER_ACCOUNT_IS_DISABLED = 3;
    const USER_ACCOUNT_EXPIRES_IN = 4;
    const USER_ACCOUNT_IS_EXPIRED = 5;
    const USER_ACCOUNT_HAS_BEEN_EXPIRED_FOR = 6;
    const USER_ACCOUNT_NEVER_LOGGED_IN = 7;
    const USER_ACCOUNT_NO_EXPIRATION = 8;
    const USER_EXPIRING_GROUP_EXPIRES = 20;
    const USER_NOT_MEMBER_OF_GROUP1 = 30;
    const USER_MEMBER_OF_GROUP2 = 31;

    const SYSFOLDER = 1;
    const GROUP1 = 1;
    const GROUP2 = 2;

    /**
     * @var array Have styleguide loaded
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/bpn_expiring_fe_groups',
        'typo3conf/ext/bpn_expiring_fe_users',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__.'/FrontEndUserRepositoryTestUserFixture.xml');
        $this->importDataSet(__DIR__.'/FrontEndUserRepositoryTestGroupFixture.xml');

        $this->updateValues('fe_users', ['lastlogin' => time() - 864000], ['username' => 'active-user-no-expiration']);
        $this->updateValues('fe_users', ['lastlogin' => time() - 86400], ['username' => 'active-user-older-one-year']);
        $this->updateValues('fe_users', ['endtime' => strtotime('+3 days')], ['username' => 'user-expires-one-week']);
        $this->updateValues('fe_users', ['endtime' => strtotime('-5 days')], ['username' => 'user-expired']);
        $this->updateValues(
            'fe_users',
            ['endtime' => strtotime('-400 days')],
            ['username' => 'user-has-been-expired-for']
        );
        $this->updateValues(
            'fe_users',
            [
                'tx_expiringfegroups_groups' => implode('|', [1, strtotime('-1 month'), strtotime('+1 month')]),
            ],
            ['username' => 'user-with-expiring-groups']
        );
        $this->updateValues(
            'fe_users',
            [
                'tx_expiringfegroups_groups' => implode(
                    '|',
                    [
                        1,
                        strtotime('-1 month'),
                        strtotime('+4 days'),
                    ]
                ),
            ],
            ['username' => 'user-with-expiring-groups_expiring']
        );
    }

    /**
     * @test
     * @dataProvider dataProvider_getUserByConfigTest
     */
    public function getUserByConfigWithoutTest(Config $config, array $expectedUserNames)
    {
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);

        $expiringGroupRepository = new ExpiringGroupRepository($objectManagerProphecy->reveal());
        $frontEndUserRepository = new FrontEndUserRepository($objectManagerProphecy->reveal());
        $frontEndUserRepository->injectExpiringGroupRepository($expiringGroupRepository);
        $users = $frontEndUserRepository->getUserByConfig($config);

//        echo $frontEndUserRepository->getSql()['statement'].PHP_EOL;

        foreach ($expectedUserNames as $expectedUserName) {
            $found = false;
            foreach ($users as $user) {
                if ($user['username'] === $expectedUserName) {
                    $found = true;
                    break;
                }
            }
            self::assertTrue(
                $found,
                'Expected to find '.$expectedUserName.', but the user was not in the resultset'
            );
        }
    }

    public function dataProvider_getUserByConfigTest()
    {
        return [
            [
                'config'            => $this->getConfig(self::USER_ACCOUNT_NOT_LOGGEDIN_FOR),
                'expectedUserNames' => ['user-not-loggedin-for-a-while',],
            ],
            [
                'config'            => $this->getConfig(self::USER_ACCOUNT_OLDER_THAN),
                'expectedUserNames' => [
                    'user-not-loggedin-for-a-while',
                    'active-user-older-one-year',
                ],
            ],
            [
                'config'            => $this->getConfig(self::USER_ACCOUNT_IS_DISABLED),
                'expectedUserNames' => ['user-disabled',],
            ],
            [
                'config'            => $this->getConfig(self::USER_ACCOUNT_EXPIRES_IN),
                'expectedUserNames' => ['user-expires-one-week',],
            ],
            [
                'config'            => $this->getConfig(self::USER_ACCOUNT_IS_EXPIRED),
                'expectedUserNames' => ['user-expired',],
            ],
            [
                'config'            => $this->getConfig(self::USER_ACCOUNT_HAS_BEEN_EXPIRED_FOR),
                'expectedUserNames' => ['user-has-been-expired-for',],
            ],
            [
                'config'            => $this->getConfig(self::USER_ACCOUNT_NEVER_LOGGED_IN),
                'expectedUserNames' => ['user-never-logged-in',],
            ],
            [
                'config'            => $this->getConfig(self::USER_ACCOUNT_NO_EXPIRATION),
                'expectedUserNames' => ['user-never-logged-in', 'active-user-no-expiration'],
            ],
            [
                'config'            => $this->getConfig(self::USER_EXPIRING_GROUP_EXPIRES),
                'expectedUserNames' => ['user-with-expiring-groups_expiring', 'user-with-expiring-groups'],
            ],
            [
                'config'            => $this->getConfig(self::USER_NOT_MEMBER_OF_GROUP1),
                'expectedUserNames' => ['user-not-memberof-group1'],
            ],
            [
                'config' => $this->getConfig(self::USER_MEMBER_OF_GROUP2),
                'expectedUserNames' => ['user-not-memberof-group1'],
            ],
        ];
    }

    private function getConfig(int $type): Config
    {
        $config = new Config();

        switch ($type) {
            case self::USER_ACCOUNT_NOT_LOGGEDIN_FOR:
                $config
                    ->setCondition1(1)
                    ->setDays(90)
                    ->setSysfolder(self::SYSFOLDER)
                    ->setAndor('AND');
                break;
            case self::USER_ACCOUNT_OLDER_THAN:
                $config
                    ->setCondition2(1)
                    ->setDays(90)
                    ->setSysfolder(self::SYSFOLDER)
                    ->setAndor('AND');
                break;
            case self::USER_ACCOUNT_IS_DISABLED:
                $config
                    ->setCondition3(1)
                    ->setDays(90)
                    ->setSysfolder(self::SYSFOLDER)
                    ->setAndor('AND');
                break;
            case self::USER_ACCOUNT_EXPIRES_IN:
                $config
                    ->setCondition4(1)
                    ->setDays(30)
                    ->setSysfolder(self::SYSFOLDER)
                    ->setAndor('AND');
                break;
            case self::USER_ACCOUNT_IS_EXPIRED:
                $config
                    ->setCondition5(1)
                    ->setDays(30)
                    ->setSysfolder(self::SYSFOLDER)
                    ->setAndor('AND');
                break;
            case self::USER_ACCOUNT_HAS_BEEN_EXPIRED_FOR:
                $config
                    ->setCondition6(1)
                    ->setDays(30)
                    ->setSysfolder(self::SYSFOLDER)
                    ->setAndor('AND');
                break;
            case self::USER_ACCOUNT_NEVER_LOGGED_IN:
                $config
                    ->setCondition7(1)
                    ->setDays(30)
                    ->setSysfolder(self::SYSFOLDER)
                    ->setAndor('AND');
                break;
            case self::USER_ACCOUNT_NO_EXPIRATION:
                $config
                    ->setCondition8(1)
                    ->setDays(30)
                    ->setSysfolder(self::SYSFOLDER)
                    ->setAndor('AND');
                break;
            case self::USER_EXPIRING_GROUP_EXPIRES:
                $config
                    ->setCondition20(1)
                    ->setDays(30)
                    ->setExpiringGroup('1')
                    ->setSysfolder(self::SYSFOLDER)
                    ->setAndor('AND');
                break;
            case self::USER_NOT_MEMBER_OF_GROUP1:
                $config
                    ->setSysfolder(self::SYSFOLDER)
                    ->setAndor('AND')
                    ->setNoMemberOf('1');
                break;
            case self::USER_MEMBER_OF_GROUP2:
                $config
                    ->setSysfolder(self::SYSFOLDER)
                    ->setAndor('AND')
                    ->setMemberOf('2');
                break;

            default:
                throw new \RuntimeException("Invalid type. {$type} is unknown.", 1621601234062);
        }

        return $config;
    }
}
