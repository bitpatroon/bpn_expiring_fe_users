<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 21-5-2021 11:32
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

namespace BPN\BpnExpiringFeUsers\Tests\Unit\Domain\Repository;

use BPN\BpnExpiringFeUsers\Domain\Model\ExpiringGroupModel;
use BPN\BpnExpiringFeUsers\Domain\Repository\ExpiringGroupRepository;
use PHPUnit\Framework\Assert;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExpiringGroupRepositoryTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     * @dataProvider dataProvider_testGetAllExpiringGroups
     */
    public function testGetAllExpiringGroups(string $feExpGroups, array $expectedResult)
    {
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);

        /** @var ExpiringGroupRepository $expiringGroupRepository */
        $expiringGroupRepository = new ExpiringGroupRepository($objectManagerProphecy->reveal());

        $result = $expiringGroupRepository->getAllExpiringGroups($feExpGroups);

        Assert::assertEquals($expectedResult, $result);
    }

    public function dataProvider_testGetAllExpiringGroups()
    {
        return [
            [
                'feExpGroups'    => '',
                'expectedResult' => []
            ],
            [
                'feExpGroups'    => '1|1619636760|1619895960',
                'expectedResult' => [
                    (new ExpiringGroupModel())->setUid(1)->setStart(1619636760)->setEnd(1619895960),
                ]
            ],
            [
                'feExpGroups'    => '1|1619636760|1619895960*',
                'expectedResult' => [
                    (new ExpiringGroupModel())->setUid(1)->setStart(1619636760)->setEnd(1619895960),
                ]
            ],
            [
                'feExpGroups'    => '1|1619636760|1619895960*' . '1|1620307146|1620393546*' . '1|1620314611|1622993011',
                'expectedResult' => [
                    (new ExpiringGroupModel())->setUid(1)->setStart(1619636760)->setEnd(1619895960),
                    (new ExpiringGroupModel())->setUid(1)->setStart(1620307146)->setEnd(1620393546),
                    (new ExpiringGroupModel())->setUid(1)->setStart(1620314611)->setEnd(1622993011),
                ]
            ]
        ];
    }
}
