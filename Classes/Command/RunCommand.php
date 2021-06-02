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

use BPN\BpnExpiringFeUsers\Domain\Model\Config;
use BPN\BpnExpiringFeUsers\Domain\Model\FrontEndUser;
use BPN\BpnExpiringFeUsers\Domain\Repository\ConfigRepository;
use BPN\BpnExpiringFeUsers\Service\DeleteActionService;
use BPN\BpnExpiringFeUsers\Service\DisableActionService;
use BPN\BpnExpiringFeUsers\Service\ExpireActionService;
use BPN\BpnExpiringFeUsers\Service\MailActionService;
use BPN\BpnExpiringFeUsers\Service\RemoveGroupActionService;
use BPN\BpnExpiringFeUsers\Traits\ConfigTrait;
use BPN\BpnExpiringFeUsers\Traits\FrontEndUserTrait;
use BPN\BpnExpiringFeUsers\Traits\LogTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    use LogTrait;
    use ConfigTrait;
    use FrontEndUserTrait;

    /** @var int */
    private $configId;

    /** @var MailActionService */
    protected $mailActionService;

    /** @var DeleteActionService */
    protected $deleteActionService;

    /** @var DisableActionService */
    protected $disableActionService;

    /** @var ExpireActionService */
    protected $expireActionService;

    /** @var RemoveGroupActionService */
    protected $removeGroupActionService;

    public function execute(InputInterface $input, OutputInterface $output): bool
    {
        $this->logRepository
            ->setInput($input)
            ->setOutput($output);

        $this->doExecute();

        return true;
    }

    /**
     * @internal
     */
    public function doExecute(): bool
    {
        try {
            $this->configRepository->allowAllStoragePages();

            $configRows = $this->configId
                ? [$this->configRepository->findByUid($this->configId)]
                : $this->configRepository->findAll();
            if (!$configRows) {
                // do nothing, still result is good!
                return true;
            }

            /** @var Config $configRow */
            foreach ($configRows as $configRow) {
                if($configRow->getTitle() === '[CODECEPTION]'){
                    // skip configurations with a title resembling an end 2 end test
                    continue;
                }
                /** @var FrontEndUser[]|int[] $users */
                $users = $this->frontEndUserRepository->findMatchingUsers($configRow);
                if (empty($users)) {
                    continue;
                }
                if (![$users]) {
                    continue;
                }

                switch ($configRow->getTodo()) {
                    case ConfigRepository::ACTION_MAIL:
                        $this->mailActionService->execute($configRow, $users);
                        break;

                    case ConfigRepository::ACTION_DISABLE:
                        $this->disableActionService->execute($configRow, $users);
                        break;

                    case ConfigRepository::ACTION_DELETE:
                        $this->deleteActionService->execute($configRow, $users);
                        break;

                    case ConfigRepository::ACTION_REMOVE_GROUP:
                        $this->removeGroupActionService->execute($configRow, $users);
                        break;

                    case ConfigRepository::ACTION_EXPIRE:
                        $this->expireActionService->execute($configRow, $users);
                        break;
                }
            }
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    public function injectMailActionService(MailActionService $mailActionService)
    {
        $this->mailActionService = $mailActionService;
    }

    public function injectDeleteActionService(DeleteActionService $deleteActionService)
    {
        $this->deleteActionService = $deleteActionService;
    }

    public function injectDisableActionService(DisableActionService $disableActionService)
    {
        $this->disableActionService = $disableActionService;
    }

    public function injectExpireActionService(ExpireActionService $expireActionService)
    {
        $this->expireActionService = $expireActionService;
    }

    public function injectRemoveGroupActionService(RemoveGroupActionService $removeGroupActionService)
    {
        $this->removeGroupActionService = $removeGroupActionService;
    }

    public function getConfigId(): int
    {
        return $this->configId;
    }

    public function setConfigId(int $configId): RunCommand
    {
        $this->configId = $configId;

        return $this;
    }

}
