<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 21-5-2021 13:50
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

namespace BPN\BpnExpiringFeUsers\Tests\Functional;

use Exception;
use mysqli;
use Psr\Container\ContainerInterface;
use RuntimeException;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\DatabaseConnectionWrapper;
use TYPO3\TestingFramework\Core\Testbase;

class FunctionalTestCase extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    const OPERATION_SLEEP_SECONDS = 3;
    const START_RETRIES = 20;

    protected $bufferEnabled = true;
    protected $buffer = [];

    /**
     * This internal variable tracks if the given test is the first test of
     * that test case. This variable is set to current calling test case class.
     * Consecutive tests then optimize and do not create a full
     * database structure again but instead just truncate all tables which
     * is much quicker.
     *
     * @var string
     */
    private static $currestTestCaseClass;

    /**
     * @var ContainerInterface
     */
    private $container;

    /** @var Testbase */
    protected $testBase;

    /**
     * @var string Current databaseName
     */
    protected $testDatabaseName;

    /**
     * Sets up legacy TYPO3_DB database connection with the proper database name.
     */
    protected function setUp() : void
    {
        $start = $this->startTiming();
        $this->drawLine();

        $this->setOriginalRoot();
        $this->setProjectRoot();
        $this->instancePath = self::getInstancePath();

        $testbase = $this->prepareTestBase();
        if (!$testbase) {
            throw new Exception('Cannot continue. [1614068835065]');
        }
        $this->consoleLog('[INFO] Instance path: ' . $this->instancePath);

        // Database might not be setup yet.. Wait for completion of database & structure.
        if ($this->instancePath && file_exists($this->instancePath)) {
            $this->waitForDatabase(2);
        }

        $this->prepareSetup();

        $this->finishTiming($start, 2500, sprintf('General Prepare Set-up (%s)', __METHOD__));

        self::assertTrue(
            $this->waitForDatabase(null, true),
            '[WARNING] Stopping test. Database was not ready or no there. [1614085962083]'
        );

        $this->testBase = $testbase;
        $this->flushBuffer(true);
    }

    private function isWindows()
    {
        return 'WIN' === strtoupper(substr(PHP_OS, 0, 3));
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function prepareSetup() : void
    {
        try {
            if ($this->isWindows()) {
                $this->title('Running in context Windows');
                $this->ensureAutoloadIsDone();
                $this->alteredSetUp();
            } else {
                parent::setUp();
            }
        } catch (Exception $exception) {
            $this->consoleLog('[EXCEPTION Occurred] ' . $exception->getMessage() . ' [1614010234463]');
            // optionally dump:
            if (file_exists($this->instancePath . '/last_run.txt')) {
                $lastRunTxt = @file_get_contents($this->instancePath . '/last_run.txt');
                $this->consoleLog('last_run.txt: ' . $lastRunTxt);
            }

            throw $exception;
        }
    }

    /**
     * Set up creates a test instance and database.
     *
     * This method should be called with parent::setUp() in your test cases!
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function alteredSetUp()
    {
        if (!defined('ORIGINAL_ROOT')) {
            self::markTestSkipped('Functional tests must be called through phpunit on CLI');
        }

        $this->identifier = self::getInstanceIdentifier();
        $this->instancePath = self::getInstancePath();
        putenv('TYPO3_PATH_ROOT=' . $this->instancePath);
        putenv('TYPO3_PATH_APP=' . $this->instancePath);

        $testbase = new WindowsTestbase();
        $testbase->defineTypo3ModeBe();
        $testbase->setTypo3TestingContext();

        $isFirstTest = false;
        $currentTestCaseClass = get_called_class();
        if (self::$currestTestCaseClass !== $currentTestCaseClass) {
            $isFirstTest = true;
            self::$currestTestCaseClass = $currentTestCaseClass;
        }

        if (!$isFirstTest) {
            // Reusing an existing instance. This typically happens for the second, third, ... test
            // in a test case, so environment is set up only once per test case.
            GeneralUtility::purgeInstances();
            $this->container = $testbase->setUpBasicTypo3Bootstrap($this->instancePath);
            $testbase->initializeTestDatabaseAndTruncateTables();
            $testbase->loadExtensionTables();
        } else {
            $testbase->removeOldInstanceIfExists($this->instancePath);
            // Basic instance directory structure
            $testbase->createDirectory($this->instancePath . '/fileadmin');
            $testbase->createDirectory($this->instancePath . '/typo3temp/var/transient');
            $testbase->createDirectory($this->instancePath . '/typo3temp/assets');
            $testbase->createDirectory($this->instancePath . '/typo3conf/ext');
            // Additionally requested directories
            foreach ($this->additionalFoldersToCreate as $directory) {
                $testbase->createDirectory($this->instancePath . '/' . $directory);
            }
            $testbase->setUpInstanceCoreLinks($this->instancePath);
            $testbase->linkTestExtensionsToInstance($this->instancePath, $this->testExtensionsToLoad);
            $testbase->linkFrameworkExtensionsToInstance($this->instancePath, $this->frameworkExtensionsToLoad);
            $testbase->linkPathsInTestInstance($this->instancePath, $this->pathsToLinkInTestInstance);
            $testbase->providePathsInTestInstance($this->instancePath, $this->pathsToProvideInTestInstance);
            $localConfiguration['DB'] = $testbase->getOriginalDatabaseSettingsFromEnvironmentOrLocalConfiguration();

            $originalDatabaseName = '';
            $dbPath = '';
            $dbName = '';
            $dbDriver = $localConfiguration['DB']['Connections']['Default']['driver'];
            if ('pdo_sqlite' !== $dbDriver) {
                $originalDatabaseName = $localConfiguration['DB']['Connections']['Default']['dbname'];
                // Append the unique identifier to the base database name to end up with a single database per test case
                $dbName = $originalDatabaseName . '_ft' . $this->identifier;
                $localConfiguration['DB']['Connections']['Default']['dbname'] = $dbName;
                $localConfiguration['DB']['Connections']['Default']['wrapperClass'] = DatabaseConnectionWrapper::class;
                $testbase->testDatabaseNameIsNotTooLong($originalDatabaseName, $localConfiguration);
                if ('mysqli' === $dbDriver) {
                    $localConfiguration['DB']['Connections']['Default']['initCommands'] = 'SET SESSION sql_mode = \'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY\';';
                }
            } else {
                $dbPath = $this->instancePath . '/test.sqlite';
                $localConfiguration['DB']['Connections']['Default']['path'] = $dbPath;
            }

            // Set some hard coded base settings for the instance. Those could be overruled by
            // $this->configurationToUseInTestInstance if needed again.
            $localConfiguration['SYS']['displayErrors'] = '1';
            $localConfiguration['SYS']['debugExceptionHandler'] = '';
            $localConfiguration['SYS']['trustedHostsPattern'] = '.*';
            $localConfiguration['SYS']['encryptionKey'] = 'i-am-not-a-secure-encryption-key';
            $localConfiguration['SYS']['caching']['cacheConfigurations']['extbase_object']['backend'] = NullBackend::class;
            $localConfiguration['GFX']['processor'] = 'GraphicsMagick';
            $testbase->setUpLocalConfiguration(
                $this->instancePath,
                $localConfiguration,
                $this->configurationToUseInTestInstance
            );
            $defaultCoreExtensionsToLoad = [
                'core',
                'backend',
                'frontend',
                'extbase',
                'install',
                'recordlist',
                'fluid',
            ];
            $testbase->setUpPackageStates(
                $this->instancePath,
                $defaultCoreExtensionsToLoad,
                $this->coreExtensionsToLoad,
                $this->testExtensionsToLoad,
                $this->frameworkExtensionsToLoad
            );
            $this->container = $testbase->setUpBasicTypo3Bootstrap($this->instancePath);
            if ('pdo_sqlite' !== $dbDriver) {
                $testbase->setUpTestDatabase($dbName, $originalDatabaseName);
            } else {
                $testbase->setUpTestDatabase($dbPath, $originalDatabaseName);
            }
            $testbase->loadExtensionTables();
            $testbase->createDatabaseStructure();
        }

        $this->testBase = $testbase;
    }

    private function ensureAutoloadIsDone()
    {
        if (isset($GLOBALS['composerAutoload'])) {
            $composerAutoloader = realpath($GLOBALS['composerAutoload']);
        } else {
            $composerAutoloader = PROJECT_ROOT . 'vendor/autoload.php';
        }

        if (!file_exists($composerAutoloader)) {
            throw new RuntimeException('Autoloader was not found', 1611308527);
        }

        require $composerAutoloader;
    }

    protected function importDataSet($path)
    {
        $start = $this->startTiming();
        parent::importDataSet($path);
        $this->finishTiming($start, 1000, 'importDataSet');
    }

    protected function consoleLog(string $value)
    {
        if ($this->bufferEnabled) {
            $this->buffer[] = $value . PHP_EOL;
        } else {
            echo $value . PHP_EOL;
        }
    }

    protected function flushBuffer(bool $disable = false)
    {
        if ($this->buffer) {
            echo implode('', $this->buffer);
        }
        $this->buffer = [];
        if ($disable) {
            $this->bufferEnabled = false;
        }
    }

    protected function drawLine()
    {
        $this->consoleLog(str_repeat('-', 80));
    }

    protected function title(string $title)
    {
        $this->consoleLog('[INFO] >> ' . $title);
    }

    protected function startTiming() : float
    {
        return microtime(true);
    }

    protected function finishTiming(float $start, int $slowDurationThreshhold = 2000, string $processTitle = '')
    {
        $duration = microtime(true) - $start;
        $durationMs = floor($duration * 1000);
        if ($durationMs > $slowDurationThreshhold) {
            $this->consoleLog('[WARNING:SLOW]');
            if ($processTitle) {
                $this->consoleLog(sprintf(': %s', $processTitle));
            }
            $this->consoleLog(PHP_EOL);
        }
    }

    protected function checkForTYPO3DatabaseGlobals(string $title = 'After setting up everything')
    {
        if (!isset($GLOBALS['TYPO3_DB'])) {
            $this->consoleLog(
                '[WARNING] checkForDatabase: No TYPO3 database object for $GLOBALS["TYPO3_DB"]. (' . $title . ') [1613993548501]'
            );

            return;
        }
        if (!$GLOBALS['TYPO3_DB']->isConnected()) {
            $this->consoleLog(
                '[WARNING] checkForDatabase: No TYPO3 database object for $GLOBALS["TYPO3_DB"] (' . $title . ') [1613993556008]'
            );

            return;
        }
    }

    protected function waitForDatabase(?int $retries = null, bool $onlyErrors = false) : bool
    {
        if (null === $retries) {
            $retries = self::START_RETRIES;
            if (!$onlyErrors) {
                $this->consoleLog(
                    sprintf(
                        '[INFO] waitForDatabase: Testing with values : [%s,%s,%s,%s,%s]',
                        getenv('typo3DatabaseHost') ? getenv('typo3DatabaseHost') : '[no db host]',
                        getenv('typo3DatabaseUsername') ? getenv('typo3DatabaseUsername') : '[no user]',
                        getenv('typo3DatabasePassword') ? '********:' . strlen(
                                getenv('typo3DatabasePassword')
                            ) : '[no pwd]',
                        $this->testDatabaseName ? $this->testDatabaseName : '[no db name]',
                        getenv('typo3DatabasePort') ? getenv('typo3DatabasePort') : '[no port]'
                    )
                );
            }
        }

        try {
            $conn = @new mysqli(
                getenv('typo3DatabaseHost') ?? '',
                getenv('typo3DatabaseUsername') ?? '',
                getenv('typo3DatabasePassword') ?? '',
                $this->testDatabaseName,
                getenv('typo3DatabasePort') ?? 3306
            );
            // Check connection
            $ok = false;
            if ($conn->connect_error) {
                $this->consoleLog(
                    sprintf(
                        '[ERROR] waitForDatabase: Database connection failed. Retry [%d] [ts=%d]',
                        (self::START_RETRIES + 1) - $retries,
                        time()
                    )
                );
            } else {
                $mysqli_result = $conn->query('select uid from fe_users where deleted = 0 limit 1');
                if ($mysqli_result) {
                    $fieldCount = $mysqli_result->field_count;
                    if (false === $fieldCount || !is_numeric($fieldCount) || 1 !== $fieldCount) {
                        $this->consoleLog('[ERROR] waitForDatabase: TYPO3 Database structure missing [1614083127923]');
                    } else {
                        $ok = true;
                    }
                }
            }
        } finally {
            if ($conn) {
                @$conn->close();
            }
        }

        if (!$ok) {
            if ($retries > 0) {
                sleep(self::OPERATION_SLEEP_SECONDS);

                return $this->waitForDatabase($retries - 1);
            }

            $this->consoleLog('[ERROR] waitForDatabase: No more retries [1613998638790]');

            return false;
        }
        if (!$onlyErrors) {
            $this->consoleLog('[INFO] waitForDatabase: Database connection Ok. [1614005812014]');
        }

        return true;
    }

    /**
     * @return Testbase
     */
    protected function prepareTestBase() : ?Testbase
    {
        // Use a 7 char long hash of class name as identifier
        $this->identifier = substr(sha1(get_class($this)), 0, 7);

        putenv('TYPO3_PATH_ROOT=' . $this->instancePath);

        $testbase = new Testbase();
        $testbase->setTypo3TestingContext();

        $this->setUpTypo3DBGlobals();

        $localConfiguration = ['DB' => $testbase->getOriginalDatabaseSettingsFromEnvironmentOrLocalConfiguration()];
        $originalDatabaseName = $localConfiguration['DB']['Connections']['Default']['dbname'];

        // Append the unique identifier to the base database name to end up with a single database per test case
        $this->testDatabaseName = $originalDatabaseName . '_ft' . $this->identifier;

        return $testbase;
    }

    protected function setUpTypo3DBGlobals() : void
    {
        if (!getenv('typo3DatabaseHost')) {
            throw new RuntimeException('Missing database settings for "typo3DatabaseHost"', 1621602424813);
        }

        if (!getenv('typo3DatabaseUsername')) {
            throw new RuntimeException('Missing database settings for "typo3DatabaseUsername"', 1621602636299);
        }
    }

    protected function setOriginalRoot()
    {
        if (!defined('ORIGINAL_ROOT')) {
            $extFolder = dirname(__DIR__, 2);
            $publicFolder = dirname($extFolder, 3);
            $folder = $publicFolder;
            if (!file_exists($folder . '/typo3temp')) {
                $folder = dirname($folder);
            }

            if (!in_array(substr($folder, -1), ['\\', '/'])) {
                $folder .= DIRECTORY_SEPARATOR;
            }

            define('ORIGINAL_ROOT', $folder);
        }
    }

    protected function setProjectRoot()
    {
        if (!defined('PROJECT_ROOT')) {
            $extFolder = dirname(__DIR__, 2);
            $publicFolder = dirname($extFolder, 3);
            $folder = $publicFolder;
            if (!file_exists($folder . '/vendor')) {
                $folder = dirname($folder);
            }

            if (!in_array(substr($folder, -1), ['\\', '/'])) {
                $folder .= DIRECTORY_SEPARATOR;
            }

            define('PROJECT_ROOT', $folder);
        }
    }

    protected function updateValuesByUid(string $table, int $uid, array $updateFields)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $connection
            ->update($table, $updateFields, ['uid' => $uid]);
    }

    protected function updateValues(string $table, array $updateFields, array $whereFields)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $connection
            ->update($table, $updateFields, $whereFields);
    }

}
