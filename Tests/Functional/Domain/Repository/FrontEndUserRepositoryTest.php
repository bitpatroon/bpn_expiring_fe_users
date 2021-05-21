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
use BPN\BpnExpiringFeUsers\Domain\Repository\FrontEndUserRepository;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use \BPN\BpnExpiringFeUsers\Tests\Functional\FunctionalTestCase;

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

    const SYSFOLDER = 1;
    const GROUP1 = 1;
    const GROUP2 = 2;
    const GROUP3 = 3;

    /**
     * @var array Have styleguide loaded
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/bpn_expiring_fe_groups',
        'typo3conf/ext/bpn_expiring_fe_users',
    ];

    protected function setUp() : void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/FrontEndUserRepositoryTestUserFixture.xml');
        $this->importDataSet(__DIR__ . '/FrontEndUserRepositoryTestGroupFixture.xml');
    }

    /**
     * @test
     * @dataProvider dataProvider_getUserByConfigTest
     */
    public function getUserByConfigWithoutTest(Config $config, array $expectedUserNames)
    {
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $frontEndUserRepository = new FrontEndUserRepository($objectManagerProphecy->reveal());
        $users = $frontEndUserRepository->getUserByConfig($config);

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
                'Expected to find ' . $expectedUserName . ', but the user was not in the resultset'
            );
        }
    }

    public function dataProvider_getUserByConfigTest()
    {
        return [
            [
                'config'            => $this->getConfig(self::USER_ACCOUNT_NOT_LOGGEDIN_FOR),
                'expectedUserNames' => [
                    'user-not-loggedin-for-a-while',
                ],
            ],
        ];
    }

    private function getConfig(int $type) : Config
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

            default:
                throw new \RuntimeException("Invalid type. {$type} is unknown.", 1621601234062);
        }

        return $config;
    }

    private function updateValues(string $table, int $uid, array $updateFields)
    {
        // todo: implement to ensure some the users are updated correctly
    }
}
