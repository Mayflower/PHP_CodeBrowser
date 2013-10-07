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
        define('PHPCB_ROOT_DIR', realpath(dirname(__FILE__) . '/../'));
    }
    if (!defined('PHPCB_TEMPLATE_DIR')) {
        define('PHPCB_TEMPLATE_DIR', realpath(dirname(__FILE__) . '/../templates'));
    }
}

require_once dirname(__FILE__) . '/Autoload.php';
require_once 'File/Iterator/Autoload.php';

if (stream_resolve_include_path('ezc/Base/base.php')) {
    require_once 'ezc/Base/base.php';
    spl_autoload_register(array('ezcBase', 'autoload'));
}

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
    private $_projectSource;

    /**
     * Array of PCREs. Matching files will not appear in the output.
     *
     * @var Array
     */
    private $_excludeExpressions;

    /**
     * Array of glob patterns. Matching files will not appear in the output.
     *
     * @var Array
     */
    private $_excludePatterns;

    /**
     * The error plugin classes
     *
     * @var array
     */
    private $_registeredPlugins;

    /**
     * The IOHelper used for filesystem interaction.
     *
     * @var CbIOHelper
     */
    private $_ioHelper;

    /**
     * ezcConsoleOutput object where debug output should go to.
     *
     * @var ezcConsoleOutput
     */
    private $_debugLog;

    /**
     * Plugin-specific options. Formatted like
     *  array(
     *      'CbErrorCRAP' => array(
     *          'threshold' => 2
     *      )
     *  )
     *
     * @var array
     */
    private $_pluginOptions = array();

    /**
     * File extensions that we take as php files.
     *
     * @var array
     */
    private $_phpSuffixes;

    /**
     * We want to exclude files with no issues
     *
     * @var boolean
     */
    private $_excludeOK;

    /**
     * The constructor
     *
     * Standard setters are initialized
     *
     * @param string $logPath          The (path-to) xml log files. Can be null.
     * @param Array  $projectSource    The project sources. Can be null.
     * @param string $htmlOutputDir    The html output dir, where new files will
     *                                 be created
     * @param Array  $excludeExpressions
     *                                 A list of PCREs. Files matching will not
     *                                 appear in the output.
     * @param Array  $pluginOptions    Array of Arrays with plugin-specific
     *                                 options
     * @param Array  $excludePatterns  A list of glob patterns. Files matching
     *                                 will not appear in the output.
     * @param CbIOHelper $ioHelper     The CbIOHelper object to be used for
     *                                 filesystem interaction.
     */
    public function __construct($logPath,               Array $projectSource,
                                $htmlOutputDir,         Array $excludeExpressions,
                                Array $excludePatterns, Array $pluginOptions,
                                $ioHelper,                    $debugLog,
                                Array $phpSuffixes,
                                $excludeOK = false)
    {
        $this->_logDir             = $logPath;
        $this->_projectSource      = $projectSource;
        $this->_htmlOutputDir      = $htmlOutputDir;
        $this->_excludeExpressions = $excludeExpressions;
        $this->_excludePatterns    = $excludePatterns;
        foreach ($pluginOptions as $plugin => $options) {
            $this->_pluginOptions["CbError$plugin"] = $options;
        }
        $this->_ioHelper           = $ioHelper;
        $this->_debugLog           = $debugLog;
        $this->_registeredPlugins  = array();
        $this->_phpSuffixes        = $phpSuffixes;
        $this->_excludeOK          = $excludeOK;
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
            $this->_registeredPlugins[] = $className;
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
        // clear and create output directory
        if (is_dir($this->_htmlOutputDir)) {
            $this->_ioHelper->deleteDirectory($this->_htmlOutputDir);
        } else if (is_file($this->_htmlOutputDir)) {
            $this->_ioHelper->deleteFile($this->_htmlOutputDir);
        }
        $this->_ioHelper->createDirectory($this->_htmlOutputDir);

        // init needed classes
        $cbViewReview  = new CbViewReview(
            PHPCB_TEMPLATE_DIR,
            $this->_htmlOutputDir,
            $this->_ioHelper,
            $this->_phpSuffixes
        );

        $sourceHandler = new CbSourceHandler($this->_debugLog);

        if (isset($this->_logDir)) {
            $cbIssueXml    = new CbIssueXml();

            // merge xml files
            $cbIssueXml->addDirectory($this->_logDir);

            // conversion of XML file cc to cb format
            foreach ($this->_registeredPlugins as $className) {
                if (array_key_exists($className, $this->_pluginOptions)) {
                    $plugin = new $className(
                        $cbIssueXml,
                        $this->_pluginOptions[$className]
                    );
                } else {
                    $plugin = new $className($cbIssueXml);
                }
                $sourceHandler->addPlugin($plugin);
            }
        }

        if (isset($this->_projectSource)) {
            foreach ($this->_projectSource as $source) {
                if (is_dir($source)) {
                    $factory = new File_Iterator_Factory;

                    $suffixes = array_merge(
                        $this->_phpSuffixes,
                        array('php','js','css', 'html')
                    );

                    $sourceHandler->addSourceFiles(
                        $factory->getFileIterator(
                            $source,
                            $suffixes
                        )
                    );
                } else {
                    $sourceHandler->addSourceFile($source);
                }
            }
        }

        array_walk(
            $this->_excludeExpressions,
            array($sourceHandler, 'excludeMatchingPCRE')
        );
        array_walk(
            $this->_excludePatterns,
            array($sourceHandler, 'excludeMatchingPattern')
        );

        $files = $sourceHandler->getFiles();

        if (!$files) {
            $cbViewReview->copyNoErrorsIndex();
        } else {
            // Get the path prefix all files have in common
            $commonPathPrefix = $sourceHandler->getCommonPathPrefix();

            $error_reporting = ini_get('error_reporting');
            // Disable E_Strict, Text_Highlighter might throw up
            ini_set('error_reporting', $error_reporting & ~E_STRICT);
            foreach ($files as $file) {
                $cbViewReview->generate(
                    $file->getIssues(),
                    $file->name(),
                    $commonPathPrefix,
                    $this->_excludeOK
                );
            }
            ini_set('error_reporting', $error_reporting);

            // Copy needed ressources (eg js libraries) to output directory
            $cbViewReview->copyRessourceFolders();
            $cbViewReview->generateIndex($files, $this->_excludeOK);
        }
    }

    /**
     * Main method called by script
     *
     * @return void
     */
    public static function main()
    {
        $parser = self::createEzcCommandLineParser();
        $opts = $parser->getOptionValues(true);

        $errors = self::errorsForOpts($opts);
        if ($errors) {
            foreach ($errors as $e) {
                error_log("[Error] $e\n");
            }
            exit(1);
        }

        // Convert the --ignore arguments to patterns
        if ($opts['ignore']) {
            $dirSep = preg_quote(DIRECTORY_SEPARATOR, '/');
            foreach (explode(',', $opts['ignore']) as $ignore) {
                $ig = realpath($ignore);
                if (!$ig) {
                    error_log("[Warning] $ignore does not exists");
                } else {
                    $ig = preg_quote($ig, '/');
                    $opts['excludePCRE'][] = "/^$ig($dirSep|$)/";
                }
            }
        }

        $output = new ezcConsoleOutput();
        if (isset($opts['debugExcludes'])) {
            $output->setOptions(
                array("verbosityLevel" => LOG_DEBUG)
            );
        }
        
        // init new CLIController
        $controller = new CbCLIController(
            $opts['log'],
            $opts['source'] ? $opts['source'] : array(),
            $opts['output'],
            $opts['excludePCRE'] ? $opts['excludePCRE'] : array(),
            isset($opts['excludePattern']) ? $opts['excludePattern'] : array(),
            $opts['crapThreshold'] ? array('CRAP' => array(
                                        'threshold' => $opts['crapThreshold'])
                                     )
                                   : array(),
            new CbIOHelper(),
            $output,
            isset($opts['phpSuffixes']) ? explode(',', $opts['phpSuffixes'])
                                 : array('php'),
            isset($opts['excludeOK']) ? $opts['excludeOK'] : false
        );

        $plugins = self::getAvailablePlugins();

        if ($opts['disablePlugin']) {
            foreach ($opts['disablePlugin'] as $idx => $val) {
                $opts['disablePlugin'][$idx] = strtolower($val);
            }
            foreach ($plugins as $pluginKey => $plugin) {
                $name = substr($plugin, strlen('CbError'));
                if (in_array(strtolower($name), $opts['disablePlugin'])) {
                    // Remove it from the plugins list
                    unset($plugins[$pluginKey]);
                }
            }
        }
        $controller->addErrorPlugins($plugins);

        try {
            $controller->run();
        } catch (Exception $e) {
            error_log(
<<<HERE
[Error] {$e->getMessage()}

{$e->getTraceAsString()}
HERE
            );
        }
    }

    /**
     * Returns a list of available plugins.
     *
     * Currently hard-coded.
     *
     * @return array of string Classnames of error plugins
     */
    public static function getAvailablePlugins()
    {
        return array(
            'CbErrorCheckstyle',
            'CbErrorPMD',
            'CbErrorCPD',
            'CbErrorPadawan',
            'CbErrorCoverage',
            'CbErrorCRAP'
        );
    }

    /**
     * Checks the given options array for errors.
     *
     * @param Array Options as returned by Console_CommandLine->parse()
     *
     * @return Array of String Errormessages.
     */
    private static function errorsForOpts($opts)
    {
        $errors = array();

        if (!isset($opts['log'])) {
            if (!isset($opts['source'])) {
                $errors[] = 'Missing log or source argument.';
            }
        } else if (!file_exists($opts['log'])) {
            $errors[] = 'Log directory does not exist.';
        } else if (!is_dir($opts['log'])) {
            $errors[] = 'Log argument must be a directory, a file was given.';
        }

        if (!isset($opts['output'])) {
            $errors[] = 'Missing output argument.';
        } else if (file_exists($opts['output']) && !is_dir($opts['output'])) {
            $errors[] = 'Ouput argument must be a directory, a file was given.';
        }

        if (isset($opts['source'])) {
            foreach ($opts['source'] as $s) {
                if (!file_exists($s)) {
                    $errors[] = "Source '$s' does not exist";
                }
            }
        }

        return $errors;
    }

    /**
     * Creates a ezcConsoleInput object to parse options.
     * 
     * @return \ezcConsoleInput
     */
    private static function createEzcCommandLineParser()
    {
        $input = new \ezcConsoleInput();

        $optionLog = new \ezcConsoleOption(
            "l", "log", \ezcConsoleInput::TYPE_STRING, '', false,
            "<directory>", 
            "The path to the xml log files, e.g. generated from PHPUnit.\n"
                . "Either this or --source must be given\n"
        );
        $input->registerOption($optionLog);

        $optionPhpSuffixes = new \ezcConsoleOption(
            "S", "extensions", \ezcConsoleInput::TYPE_STRING, '', false,
            "<extensions>",
            "A comma separated list of php file extensions to include.\n"
        );
        $input->registerOption($optionPhpSuffixes);

        $optionOutput = new \ezcConsoleOption(
            "o", "output", \ezcConsoleInput::TYPE_STRING, '', false,
            "<directory>", 
            "Path to the output folder where generated files should be stored\n"
        );
        $input->registerOption($optionOutput);

        $optionSource = new \ezcConsoleOption(
            "s", "source", \ezcConsoleInput::TYPE_STRING, array(), true,
            "<dir|file>",
            "Path to the project source code.\n"
                . "Can either be a directory or a single file. Parse complete\n"
                . "source directory if set, else only files found in logs.\n"
                . "Either this or --log must be given.\n"
                . "Can be given multiple times.\n"
        );
        $input->registerOption($optionSource);

        $optionIgnore = new \ezcConsoleOption(
            "i", "ignore", \ezcConsoleInput::TYPE_STRING, '', false,
            "<files>", 
            "Comma separated string of files or directories\n"
                . "that will be ignored during the parsing process.\n"
        );
        $input->registerOption($optionIgnore);

        $optionExcludePattern = new \ezcConsoleOption(
            "e", "exclude", \ezcConsoleInput::TYPE_STRING, array(), true,
            "<pattern>",
            "Excludes all files matching the given glob pattern.\n"
                . "This is done after pulling the files in the source dir in\n"
                . "if one is given. Can be given multiple times.\n"
                . "Note that the match is run against absolute filenames.\n"
        );
        $input->registerOption($optionExcludePattern);

        $optionExcludePCRE = new \ezcConsoleOption(
            "E", "excludePCRE", \ezcConsoleInput::TYPE_STRING, array(), true,
            "<expression>",
            "Works like -e but takes PCRE instead of glob patterns.\n"
        );
        $input->registerOption($optionExcludePCRE);

        $optionDebugExcludes = new \ezcConsoleOption(
            "", "debugExcludes", \ezcConsoleInput::TYPE_NONE, null, false,
            "", 
            "Print which files are excluded by which expressions and patterns.",
            array(), array(), false
        );
        $input->registerOption($optionDebugExcludes);

        $optionExcludeOK = new \ezcConsoleOption(
            "", "excludeOK", \ezcConsoleInput::TYPE_NONE, null, false,
            "", 
            "Exclude files with no issues from the report.",
            array(), array(), false
        );
        $input->registerOption($optionExcludeOK);

        $plugins = array_map(
            array(__CLASS__, 'arrayMapCallback'),
            self::getAvailablePlugins()
        );
        $optionDisablePlugin = new \ezcConsoleOption(
            "", "disablePlugin", \ezcConsoleInput::TYPE_STRING, array(), true,
            "<plugin>",
            "Disable single Plugins. Can be one of\n"
                . implode(', ', $plugins)
        );
        $input->registerOption($optionDisablePlugin);

        $optionСrapThreshold = new \ezcConsoleOption(
            "", "crapThreshold", \ezcConsoleInput::TYPE_INT, 0, false,
            "<threshold>", 
            "The minimum value for CRAP errors to be recognized. Defaults to 0.\n"
                . "Regardless of this setting, values below 30 will be\n"
                . "considered notices, those above warnings."
        );
        $input->registerOption($optionСrapThreshold);

        $optionHelp = new \ezcConsoleOption(
            "h", "help", \ezcConsoleInput::TYPE_NONE, null, false,
            "",
            "",
            array(), array(), false, false, true
        );
        $input->registerOption($optionHelp);

        $optionVersion = new \ezcConsoleOption(
            "v", "version", \ezcConsoleInput::TYPE_NONE, null, false,
            "",
            "",
            array(), array(), false, false, true
        );
        $input->registerOption($optionVersion);
        
        try {
            $input->process();
        }
        catch (\ezcConsoleOptionException $e) {
            print $e->getMessage() . "\n";
            exit(1);
        }

        $arguments = $input->getOptionValues(true);
        $argumentsCount = 0;
        foreach ($arguments as $argument) {
            if ($argument) {
               $argumentsCount++;
            }
        }

        if (strpos('@package_version@', '@') === false) {
            $version = '@package_version@';
        } else {
            $version = 'from Git';
        }
        if (!$argumentsCount || $input->getOption('help')->value) {
            $desc = "A Code browser for PHP files with syntax highlighting"
                . " and colored error-sections found by quality assurance"
                . " tools like PHPUnit or PHP_CodeSniffer";
            
            print(
                implode(
                    "\n", array(
                        wordwrap($desc, 75, "\n", true), 
                        "",
                        "Usage:",
                        "  phpcb [options]",
                        "", 
                        "Options:",
                        ""
                    )
                )
            );
          
            foreach ($input->getOptions() as $option) {
                /* @var $option \ezcConsoleOption */
                $nameShort = $option->short ? "-" . $option->short : "";
                $nameLong = "--" . $option->long;
                $helpShort = $option->shorthelp;
                $helpLong = explode("\n", $option->longhelp);

                foreach ($helpLong as $helpLine) {
                    printf(
                        "  %-2s %-30s%s\n",
                        $nameShort,
                        $nameLong . " " . $helpShort, 
                        $helpLine
                    );
                    $nameShort = $nameLong = $helpShort = "";
                }
            }
            exit(0);
        } elseif ($input->getOption('version')->value) {
            print("phpcb version " . $version);
            
            exit(0);
        }

        return $input;
    }

    private static function arrayMapCallback($class)
    {
        return '"' . substr($class, strlen('CbError')) . '"';
    }
}
