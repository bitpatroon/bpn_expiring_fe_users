<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 20-5-2021 16:55
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

namespace BPN\BpnExpiringFeUsers\Command;

use BPN\BpnExpiringFeUsers\Controller\HandleActionController;
use BPN\BpnExpiringFeUsers\Domain\Models\Config;
use BPN\BpnExpiringFeUsers\Domain\Models\FrontEndUser;
use BPN\BpnExpiringFeUsers\Domain\Repository\ConfigRepository;
use BPN\BpnExpiringFeUsers\Domain\Repository\FrontEndUserRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \TYPO3\CMS\Core\Domain\Repository\PageRepository;
use Symfony\Component\Console\Command\Command;

class RunCommand extends Command
{
//    /**
//     * @var PageRepository
//     */
//    protected $pageRepository;
//
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

//    /**
//     * @var FrontEndUserRepository
//     */
//    protected $frontEndUserRepository;

    /**
     * @var HandleActionController
     */
    protected $handleActionController;

    /**
     * @return bool
     */
    public function execute(InputInterface $input, OutputInterface $output) : bool
    {
        $configRows = $this->configRepository->findAll();

        /** @var Config $configRow */
        foreach ($configRows as $configRow) {
            /** @var FrontEndUser[]|int[] $users */
            $users = $this->configRepository->findMatchingUsers($configRow);
            if (empty($users)) {
                continue;
            }
            if (!array($users)) {
                continue;
            }

            switch ($configRow->getTodo()) {
                case ConfigRepository::ACTION_MAIL:
                    $this->handleActionController->mailAction($configRow, $users);
                    break;

                case ConfigRepository::ACTION_DISABLE:
                    $this->handleActionController->disableAction($configRow, $users);
                    break;

                case ConfigRepository::ACTION_DELETE:
                    $this->handleActionController->deleteAction($configRow, $users);
                    break;

                case ConfigRepository::ACTION_REMOVE_GROUP:
                    $this->handleActionController->removeGroupAction($configRow, $users);
                    break;

                case ConfigRepository::ACTION_EXPIRE:
                    $this->handleActionController->expireAction($configRow, $users);
                    break;
            }
        }

        return true;
    }

    public function injectPageRepository(PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }

    public function injectConfigRepository(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    public function injectFrontEndUserRepository(FrontEndUserRepository $frontEndUserRepository)
    {
        $this->frontEndUserRepository = $frontEndUserRepository;
    }

    public function injectHandleActionController(HandleActionController $handleActionController)
    {
        $this->handleActionController = $handleActionController;
    }


}
