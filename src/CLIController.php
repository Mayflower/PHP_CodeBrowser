<?php
/**
 * Cli controller
 *
 * PHP Version 5.3.2
 *
 * Copyright (c) 2007-2010, Mayflower GmbH
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Mayflower GmbH nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  PHP_CodeBrowser
 * @package   PHP_CodeBrowser
 * @author    Elger Thiele <elger.thiele@mayflower.de>
 * @author    Simon Kohlmeyer <simon.kohlmeyer@mayflower.de>
 * @copyright 2007-2010 Mayflower GmbH
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   SVN: $Id$
 * @link      http://www.phpunit.de/
 * @since     File available since  0.1.0
 */

if (strpos('@php_dir@', '@php_dir') === false) {
    if (!defined('PHPCB_ROOT_DIR')) {
        define('PHPCB_ROOT_DIR', '@php_dir@/PHP_CodeBrowser');
    }
    if (!defined('PHPCB_TEMPLATE_DIR')) {
        define('PHPCB_TEMPLATE_DIR', '@data_dir@/PHP_CodeBrowser/templates');
    }
} else {
    if (!defined('PHPCB_ROOT_DIR')) {
        define('PHPCB_ROOT_DIR', dirname(__FILE__) . '/../');
    }
    if (!defined('PHPCB_TEMPLATE_DIR')) {
        define('PHPCB_TEMPLATE_DIR', dirname(__FILE__) . '/../templates');
    }
}

require_once dirname(__FILE__) . '/Util/Autoloader.php';
require_once 'PHP/Timer.php';

/**
 * CbCLIController
 *
 * @category  PHP_CodeBrowser
 * @package   PHP_CodeBrowser
 * @author    Elger Thiele <elger.thiele@mayflower.de>
 * @author    Michel Hartmann <michel.hartmann@mayflower.de>
 * @author    Simon Kohlmeyer <simon.kohlmeyer@mayflower.de>
 * @copyright 2007-2010 Mayflower GmbH
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://www.phpunit.de/
 * @since     Class available since  0.1.0
 */
class CbCLIController
{
    /**
     * Path to the Cruise Control input xml file
     *
     * @var string
     */
    private $_logDir;

    /**
     * Path to the generated code browser xml file
     *
     * @var string
     */
    private $_xmlFile;

    /**
     * Path to the code browser html output folder
     *
     * @var string
     */
    private $_htmlOutputDir;

    /**
     * Path to the project source code files
     *
     * @var string
     */
    private $_projectSourceDir;

    /**
     * Array of PCREs. Matching files will not appear in the output.
     *
     * @var Array
     */
    private $_excludeExpressions;

    /**
     * The error plugin classes
     *
     * @var array
     */
    private $_registeredErrorPlugins;

    /**
     * The IOHelper used for filesystem interaction.
     *
     * @var CbIOHelper
     */
    private $_ioHelper;

    /**
     * The constructor
     *
     * Standard setters are initialized
     *
     * @param string $logPath          The (path-to) xml log files
     * @param string $projectSourceDir The project source directory
     * @param string $htmlOutputDir    The html output dir, where new files will
     *                                 be created
     * @param Array  $excludeExpressions
     *                                 A list of PCREs. Files matching will not
     *                                 appear in the output.
     * @param CbIOHelper $ioHelper     The CbIOHelper object to be used for
     *                                 filesystem interaction.
     */
    public function __construct($logPath,       $projectSourceDir,
                                $htmlOutputDir, Array $excludeExpressions,
                                $ioHelper)
    {
        $this->_logDir = $logPath;
        $this->_projectSourceDir = $projectSourceDir;
        $this->_htmlOutputDir = $htmlOutputDir;
        $this->_excludeExpressions = $excludeExpressions;
        $this->_ioHelper = $ioHelper;
    }

    /**
     * Setter/adder method for the used plugin classes.
     * For each plugin to use, add it to this array
     *
     * @param mixed $classNames Definition of plugin classes
     *
     * @return void
     */
    public function addErrorPlugins($classNames)
    {
        foreach ((array) $classNames as $className) {
            $this->_registeredErrorPlugins[] = $className;
        }
    }

    /**
     * Main execute function for PHP_CodeBrowser.
     *
     * Following steps are resolved:
     * 1. Clean-up output directory
     * 2. Merge xml log files
     * 3. Generate cbXML file via errorlist from plugins
     * 4. Save the cbErrorList as XML file
     * 5. Generate HTML output from cbXML
     * 6. Copy ressources (css, js, images) from template directory to output
     *
     * @return void
     */
    public function run()
    {
        // init needed classes
        $cbIssueXml    = new CbIssueXml();
        $cbViewReview  = new CbViewReview(
            PHPCB_TEMPLATE_DIR,
            $this->_htmlOutputDir,
            $this->_ioHelper
        );

        // clear and create output directory
        $this->_ioHelper->deleteDirectory($this->_htmlOutputDir);
        $this->_ioHelper->createDirectory($this->_htmlOutputDir);

        CbLogger::log('Load XML files', CbLogger::PRIORITY_DEBUG);

        // merge xml files
        $cbIssueXml->addDirectory($this->_logDir);

        CbLogger::log('Load Plugins', CbLogger::PRIORITY_DEBUG);

        // conversion of XML file cc to cb format
        $plugins = array();
        foreach ($this->_registeredErrorPlugins as $className) {
            $plugins[] = new $className($cbIssueXml);
        }

        $sourceHandler = new CbSourceHandler($cbIssueXml, $plugins);

        CbLogger::log(
            sprintf(
                'Found %d files with issues.',
                count($sourceHandler->getFiles())
            ),
            CbLogger::PRIORITY_INFO
        );

        if (isset($this->_projectSourceDir)) {
            $sourceHandler->addSourceDir($this->_projectSourceDir);
        }

        foreach ($this->_excludeExpressions as $expr) {
            $sourceHandler->excludeMatching($expr);
        }

        $files = $sourceHandler->getFiles();

        // Get the path prefix all files have in common
        $commonPathPrefix = $sourceHandler->getCommonPathPrefix();

        foreach($files as $file) {
            CbLogger::log(
                sprintf(
                    'Get issues for "...%s"',
                    substr($file->name(), strlen($commonPathPrefix))
                ),
                CbLogger::PRIORITY_DEBUG
            );
            $issues = $file->getIssues();

            // @TODO Timer::start() only for logging check performace and remove if neccessary
            PHP_Timer::start();
            CbLogger::log(
                sprintf('Generating source view for [...%s]', $file->name()),
                CbLogger::PRIORITY_DEBUG
            );

            $cbViewReview->generate($issues, $file->name(), $commonPathPrefix);

            CbLogger::log(
                sprintf('completed in %s', PHP_Timer::stop()),
                CbLogger::PRIORITY_DEBUG
            );
        }

        // Copy needed ressources (eg js libraries) to output directory
        $cbViewReview->copyRessourceFolders();
        $cbViewReview->generateIndex($files);
    }

    /**
     * Main method called by script
     *
     * @return void
     */
    public static function main()
    {
        PHP_Timer::start();

        // register autoloader
        spl_autoload_register(array(new CbAutoloader(), 'autoload'));

        // Parse arguments
        $opts = getopt('l:o:e:s:hv', array(
            // Mandatory
            'log:',
            'output:',
            // Optional
            'exclude:',
            'log-file:',
            'log-level:',
            'source:',
            // General args
            'help',
            'version'
        ));

        $excludeExpressions = array();

        foreach ($opts as $opt => $val) switch ($opt) {
            // Mandatory
            case 'l':
            case 'log':
                if (isset($xmlLogDir) || is_array($val)) {
                    print 'Only one log folder may be given';
                    self::printHelp();
                    exit();
                }
                $xmlLogDir = $val;
                break;

            case 'output':
            case 'o':
                if (isset($htmlOutput) || is_array($val)) {
                    print 'Only one output folder may be given';
                    self::printHelp();
                    exit();
                }
                $htmlOutput = $val;
                break;

            // Optional
            case 'e':
            case 'exclude':
                $excludeExpressions[] = $val;
                break;

            case 'log-file':
                if (is_array($val)) {
                    print 'Only one logfile may be given';
                    self::printHelp();
                    exit();
                }
                CbLogger::setLogFile($val);
                break;

            case 'log-level':
                if (is_array($val)) {
                    print 'Only one loglevel may be given';
                    self::printHelp();
                    exit();
                }
                $loglevel = $val;
                break;

            case 's':
            case 'source':
                if (isset($sourceFolder) || is_array($val)) {
                    print 'Only one source folder may be given';
                    self::printHelp();
                    exit();
                }
                $sourceFolder = $val;
                break;

            // General
            case 'h':
            case 'help':
                self::printHelp();
                exit();

            case 'v':
            case 'version':
                self::printVersion();
                exit();
        }

        if (isset($loglevel)) {
            try {
                CbLogger::setLogLevel($loglevel);
            } catch (InvalidArgumentException $e) {
                print $e->getMessage() . "\n\n";
                print "See `{$_SERVER['PHP_SELF']} --help` for more help\n";
                exit();
            }
        } else {
            CbLogger::setLogLevel(CbLogger::PRIORITY_DEBUG);
        }

        // Check if given parameters are valid.
        $errors = array();
        if (!isset($xmlLogDir)) {
            $errors[] = 'Log folder must be given.';
        } else if (!is_dir($xmlLogDir)) {
            $errors[] = 'Log folder is no directory.';
        }
        if (!isset($htmlOutput)) {
            $errors[] = 'Output folder must be given.';
        } else if (file_exists($xmlLogDir) && !is_dir($xmlLogDir)) {
            $errors[] = 'Output folder exists and is no directory.';
        }
        if (isset($sourceFolder) && !is_dir($sourceFolder)) {
            $errors[] = "Source folder '$sourceFolder' does not exist "
                      . 'or is no directory';
        }

        if ($errors) {
            foreach ($errors as $e) {
                print $e . "\n";
            }
            print 'Try `' . $_SERVER['PHP_SELF']
                . " --help` for more information.\n";
            exit();
        }

        CbLogger::log('Generating PHP_CodeBrowser files', CbLogger::PRIORITY_INFO);

        // init new CLIController
        $controller = new CbCLIController(
            $xmlLogDir,
            $sourceFolder,
            $htmlOutput,
            $excludeExpressions,
            new CbIOHelper()
        );

        $controller->addErrorPlugins(array(
            'CbErrorCheckstyle',
            'CbErrorPMD',
            'CbErrorCPD',
            'CbErrorPadawan',
            'CbErrorCoverage')
        );

        try {
            $controller->run();
        } catch (Exception $e) {
            CbLogger::log(
                sprintf("PHP-CodeBrowser Error: \n%s\n", $e->getMessage())
            );
        }

        CbLogger::log(PHP_Timer::resourceUsage(), CbLogger::PRIORITY_INFO);
    }

    /**
     * Print help menu for shell
     *
     * @return void
     */
    public static function printHelp()
    {
        print <<<USAGE
Usage: phpcb --log <dir> --output <dir> [--source <dir>] [--logfile <dir>]

Mandatory Arguments:
--------------------
-l <dir>    --log <dir>     The path to the xml log files, e.g. generated
                            from phpunit. Mandatory.

-o <dir>    --output <dir>  Path to the output folder where generated
                            files should be stored. Mandatory.

Optional Arguments:
-------------------
-e <regexp> --exclude <regexp>
                            Excludes all files matching the PCRE <regexp>.
                            This is done after pulling the files in the
                            source dir in if one is given.
                            NOTE: The match is run against absolute filenames.
                            Can be given multiple times.

--log-file <dir>            Path of the file to use for logging the output.
                            If not given, stdout will be used. Optional.

--log-level <level>         Specify the log level. Available values for level,
                            sorted from most to least noise:
                            DEBUG, INFO, WARN, ERROR.
                            Comparision is case insensitive.
                            Defaults to DEBUG in this release.

-s <dir>    --source <dir>  Path to the project source code. Parse complete
                            source directory if set, else only files found
                            in logs. Optional.

General arguments:
------------------
--help                  Print this help.
--version               Print actual verison.

USAGE;
    }

    /**
     * Print version information to shell
     *
     * @return void
     */
    public static function printVersion()
    {
        print <<<USAGE
PHP_CodeBrowser by Mayflower GmbH
Version 1.2  21.Mai.2010

USAGE;
    }
}
