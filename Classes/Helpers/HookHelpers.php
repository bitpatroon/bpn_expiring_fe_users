<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Sjoerd Zonneveld <szonneveld@bitpatroon.nl>
 *  Date: 4-9-2017 14:54
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

namespace BPN\BpnExpiringFeUsers\Helpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class HookHelpers
 * @author  : SZO
 * @fqn     : SPL\Itypo\HookHelpers
 * @internal
 * @deprecated Use Event please!
 */
class HookHelpers
{

    /**
     * Method handles a hook.
     * Notice added to ensure extension independence.
     * @param object|string $class the instance of a class
     * @param string $hookID The ID of a hook
     * @param array $params the params to pass to the hooked function
     * @internal do not use outside extension
     */
    public static function processHook($class, $hookID, &$params = [])
    {
        if (empty($class)) {
            throw new \InvalidArgumentException('Invalid value for $class', 1504529722);
        }
        if (empty($hookID)) {
            throw new \InvalidArgumentException('Invalid value for $hookID', 1504529727);
        }

        $className = '';
        if (is_object($class)) {
            $className = get_class($class);
        } elseif (is_string($class)) {
            $className = $class;
        }
        if (empty($className)) {
            throw new \InvalidArgumentException('Invalid value for $class. Expected object instance.', 1504529731);
        }

        // Hook before revert
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$className][$hookID])) {
            ksort($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$className][$hookID]);
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$className][$hookID] as $_funcRef) {
                if ($_funcRef) {
                    GeneralUtility::callUserFunction($_funcRef, $params, $class);
                }
            }
        }
    }
}
