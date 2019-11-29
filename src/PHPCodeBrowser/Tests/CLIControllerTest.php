<?php
/**
 * Test case
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
 * @category   PHP_CodeBrowser
 *
 * @author     Simon Kohlmeyer <simon.kohlmeyer@mayflower.de
 *
 * @copyright  2007-2010 Mayflower GmbH
 *
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 *
 * @version    SVN: $Id$
 *
 * @link       http://www.phpunit.de/
 *
 * @since      File available since  0.1.0
 */

namespace PHPCodeBrowser\Tests;

use Monolog\Logger;
use PHPCodeBrowser\CLIController;
use PHPCodeBrowser\Helper\IOHelper;
use PHPCodeBrowser\Plugins\ErrorCRAP;

/**
 * CLIControllerTest
 *
 * @category   PHP_CodeBrowser
 *
 * @author     Simon Kohlmeyer <simon.kohlmeyer@mayflower.de>
 *
 * @copyright  2007-2010 Mayflower GmbH
 *
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 *
 * @version    Release: @package_version@
 *
 * @link       http://www.phpunit.de/
 *
 * @since      Class available since  0.1.0
 */
class CLIControllerTest extends AbstractTestCase
{
    /**
     * @var CLIController $controller
     */
    public $controller;

    /**
     * Create and configure a CLIController instance.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->controller = new CLIController(
            null,
            [self::$phpcbSourceDir],
            self::$testOutputDir,
            [],
            [],
            [ErrorCRAP::class => ['threshold' => 1]],
            new IOHelper(),
            new Logger('PHPCodeBrowser'),
            ['php']
        );

        $this->controller->addErrorPlugins(
            [
                'ErrorCheckstyle',
                'ErrorPMD',
                'ErrorCPD',
                'ErrorPadawan',
                'ErrorCoverage',
                'ErrorCRAP',
            ]
        );
    }

    /**
     * Assert a set of directories and files are present in output dir.
     */
    public function assertOutputIsPresent(): void
    {
        self::assertFileExists(self::$testOutputDir.'/index.html');
        self::assertFileExists(self::$testOutputDir.'/Bad.php.html');
        self::assertFileExists(self::$testOutputDir.'/Good.php.html');
        self::assertDirectoryExists(self::$testOutputDir.'/css');
        self::assertDirectoryExists(self::$testOutputDir.'/img');
        self::assertDirectoryExists(self::$testOutputDir.'/js');
    }

    /**
     * Run a full system test based on phpcs output.
     */
    public function testRunCreatesFilesAndDirs(): void
    {
        $this->controller->run();

        $this->assertOutputIsPresent();
    }

    /**
     * Assert existing files and directories within output dir are removed.
     */
    public function testRunCleansExistingOutputDir(): void
    {
        mkdir(self::$testOutputDir.'/clear-directory');
        touch(self::$testOutputDir.'/clear-file');
        touch(self::$testOutputDir.'/clear-directory/clear-file');

        $this->controller->run();

        $this->assertOutputIsPresent();
        $this->assertDirectoryNotExists(self::$testOutputDir.'/clear-directory');
        $this->assertFileNotExists(self::$testOutputDir.'/clear-file');
    }

    /**
     * Assert that if there is a file with outputs name, it is replaced with a directory.
     */
    public function testRunCleansExistingOutputFile(): void
    {
        rmdir(self::$testOutputDir);
        touch(self::$testOutputDir);

        $this->controller->run();

        $this->assertOutputIsPresent();
    }

    /**
     * Assert only index.html is present if all source files are excluded.
     */
    public function testRunExcludingAllSources(): void
    {
        $this->controller = new CLIController(
            null,
            [self::$phpcbSourceDir],
            self::$testOutputDir,
            ['/Bad.php/'],
            ['*Good.php'],
            [ErrorCRAP::class => ['threshold' => 1]],
            new IOHelper(),
            new Logger('PHPCodeBrowser'),
            ['php']
        );

        $this->controller->run();

        $this->assertFileExists(self::$testOutputDir.'/index.html');
        $this->assertFileNotExists(self::$testOutputDir.'/Bad.php.html');
        $this->assertFileNotExists(self::$testOutputDir.'/Good.php.html');
        $this->assertDirectoryNotExists(self::$testOutputDir.'/css');
        $this->assertDirectoryNotExists(self::$testOutputDir.'/img');
        $this->assertDirectoryNotExists(self::$testOutputDir.'/js');
    }
}
