<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 22-5-2021 23:25
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

namespace BPN\BpnExpiringFeUsers;

use BPN\BpnExpiringFeUsers\Command\RunCommand;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class Start
{
    const FAILURE = 'failure';
    const SUCCESS = 'success';

    public function process()
    {
        if (!Environment::getContext()->isDevelopment()) {
            throw new \RuntimeException('Not allowed', 1621719795);
        }

        $operation = GeneralUtility::_GP('operation');

        switch ($operation) {
            case 'command':
                $result = $this->runCommand();
                break;
            default:
                $result = self::FAILURE;
                break;
        }

        return new HtmlResponse($result);
    }

    private function runCommand(): string
    {
        $stringInput = new StringInput('');
        $output = new BufferedOutput();

        /** @var RunCommand $runCommand */
        $runCommand = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(RunCommand::class);

        if ($runCommand->execute($stringInput, $output)) {
            $output->write(self::SUCCESS);
        } else {
            $output->write(self::FAILURE);
        }

        return $output->fetch();
    }
}
