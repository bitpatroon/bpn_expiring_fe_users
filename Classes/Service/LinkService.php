<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 22-5-2021 14:10
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

namespace BPN\BpnExpiringFeUsers\Service;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LinkService
{
    const CONVERT_NONE = 0;
    const CONVERT_INTO_HTTP = 1;
    const CONVERT_INTO_HTTPS = 2;

    /**
     * Makes absolute file path $path an absolute url
     *
     * @param string $relativePath
     * @param bool   $allowEmptyString
     *
     * @return string
     */
    public function makeAbsoluteHttpsUrl($relativePath, $allowEmptyString = true)
    {
        return $this->makeAbsoluteUrl($relativePath, $allowEmptyString, self::CONVERT_INTO_HTTPS);
    }

    /**
     * Makes absolute file path $path an absolute url
     *
     * @param string $relativePath
     * @param bool   $allowEmptyString
     * @param int    $changeProtocol \SPL\SplLibrary\Utility\LinkHelper::CONVERT_INTO_HTTP|\SPL\SplLibrary\Utility\LinkHelper::CONVERT_INTO_HTTPS
     *
     * @return string
     */
    public function makeAbsoluteUrl($relativePath, $allowEmptyString = true, $changeProtocol = self::CONVERT_NONE)
    {
        $baseUrl = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');

        switch ($changeProtocol) {
            case self::CONVERT_INTO_HTTP:
                $parts = explode('://', $baseUrl);
                $parts[0] = 'http';
                $baseUrl = implode('://', $parts);
                break;

            case self::CONVERT_INTO_HTTPS:
                $parts = explode('://', $baseUrl);
                $parts[0] = 'https';
                $baseUrl = implode('://', $parts);
                break;
        }

        return $baseUrl . $this->makeRelative($relativePath, $allowEmptyString);
    }

    /**
     * Makes absolute path $path relative to the site root
     * @param string $path
     * @param bool $allowEmptyString
     * @return string
     */
    public function makeRelative($path, $allowEmptyString = true)
    {
        if (empty($path)) {
            $path = '';
        }

        if (empty($path) && $allowEmptyString) {
            return '';
        }

        $absFilePath = GeneralUtility::getFileAbsFileName($path);
        if ($absFilePath) {
            return '/' . substr($absFilePath, strlen($this->getPublicRoot()));
        }

        $match = null;
        if (preg_match('/(\w+\:\/\/[^\/]+)?(\/?)(.*)/i', $path, $match)) {
            $relativeSlash = $match[2];
            if (empty($relativeSlash)) {
                $relativeSlash = '/';
            }
            return $relativeSlash . $match[3];
        }

        return $path;
    }

    public function getPublicRoot(){
        return Environment::getPublicPath();
    }

}
