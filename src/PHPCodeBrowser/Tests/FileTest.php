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
 * @category PHP_CodeBrowser
 *
 * @author Simon Kohlmeyer <simon.kohlmeyer@mayflower.de
 *
 * @copyright 2007-2010 Mayflower GmbH
 *
 * @license http://www.opensource.org/licenses/bsd-license.php  BSD License
 *
 * @version SVN: $Id$
 *
 * @link http://www.phpunit.de/
 *
 * @since File available since  0.1.0
 */

namespace PHPCodeBrowser\Tests;

use PHPCodeBrowser\File;
use PHPCodeBrowser\Issue;

/**
 * FileTest
 *
 * @category PHP_CodeBrowser
 *
 * @author Simon Kohlmeyer <simon.kohlmeyer@mayflower.de>
 *
 * @copyright 2007-2010 Mayflower GmbH
 *
 * @license http://www.opensource.org/licenses/bsd-license.php  BSD License
 *
 * @version Release: @package_version@
 *
 * @link http://www.phpunit.de/
 *
 * @since Class available since  0.1.0
 */
class FileTest extends AbstractTestCase
{
    /**
     * File object to test
     *
     * @var File
     */
    protected $file;

    /**
     * Some issues to work with.
     *
     * @var array<Issue>
     */
    protected $issues;

    /**
     * (non-PHPDoc)
     *
     * @see AbstractTests#setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->issues = [
            new Issue('/some/file/name.php', 39, 39, 'Checkstyle', 'm3', 'error'),
            new Issue('/some/file/name.php', 50, 52, 'Checkstyle', 'm4', 'warning'),
            new Issue('/some/file/name.php', 40, 40, 'Checkstyle', 'm4', 'error'),
        ];
        $this->file   = new File('/some/file/name.php');
    }

    /**
     * Test constructor if variables are stored properly
     *
     * @return void
     */
    public function testInstantiation(): void
    {
        static::assertEquals('/some/file/name.php', $this->file->name());

        $this->file = new File('/some/file/name.php', $this->issues);

        static::assertEquals('/some/file/name.php', $this->file->name());
        static::assertEquals($this->issues, $this->file->getIssues());
    }

    /**
     * Test if adding issues works.
     *
     * @return void
     */
    public function testIssueAdding(): void
    {
        $this->file->addIssue($this->issues[0]);
        static::assertEquals(
            [$this->issues[0]],
            $this->file->getIssues()
        );
    }

    /**
     * Tries to add invalid issue to file.
     *
     * @return void
     */
    public function testAddingIssueToWrongFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $issue = new Issue('/the/wrong/file/name.php', 39, 39, 'Checkstyle', 'm3', 'error');
        $this->file->addIssue($issue);
    }

    /**
     * Test the basename function
     *
     * @return void
     */
    public function testBasename(): void
    {
        static::assertEquals('name.php', $this->file->basename());
    }

    /**
     * Test the dirName function
     *
     * @return void
     */
    public function testDirName(): void
    {
        static::assertEquals('/some/file', $this->file->dirName());
    }

    /**
     * Test if the issue count is returned correctly
     *
     * @return void
     */
    public function testIssueCount(): void
    {
        static::assertEquals(0, $this->file->getIssueCount());

        $this->file->addIssue($this->issues[0]);
        static::assertEquals(1, $this->file->getIssueCount());

        $this->file = new File(
            '/some/file/name.php',
            [$this->issues[0]]
        );
        static::assertEquals(1, $this->file->getIssueCount());

        $this->file->addIssue($this->issues[1]);
        static::assertEquals(2, $this->file->getIssueCount());
    }

    /**
     * Test the errorCount function
     *
     * @return void
     */
    public function testErrorCount(): void
    {
        $this->file = new File('/some/file/name.php', $this->issues);
        static::assertEquals(2, $this->file->getErrorCount());
    }

    /**
     * Test the warningCount function
     *
     * @return void
     */
    public function testEarningCount(): void
    {
        $this->file = new File('/some/file/name.php', $this->issues);
        static::assertEquals(1, $this->file->getWarningCount());
    }

    /**
     * Test the mergeWith function
     *
     * @return void
     */
    public function testMergeWith(): void
    {
        $this->file = new File(
            '/some/file/name.php',
            [$this->issues[0], $this->issues[1]]
        );
        $otherFile  = new File(
            '/some/file/name.php',
            [$this->issues[2]]
        );
        $this->file->mergeWith($otherFile);

        static::assertEquals(2, $this->file->getErrorCount());
        static::assertEquals(1, $this->file->getWarningCount());
        static::assertEquals(
            \array_values($this->issues),
            \array_values($this->file->getIssues())
        );
    }

    /**
     * Try to merge with a non-compatible file.
     *
     * @return void
     */
    public function testMergeWithDifferentFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->file->mergeWith(new File('/the/wrong/file/name.php'));
    }

    /**
     * Test the sort function.
     *
     * @return void
     */
    public function testSort(): void
    {
        $sorted = [
            new File('src/Helper/IOHelper.php'),
            new File('src/Plugins/ErrorCPD.php'),
            new File('src/Plugins/ErrorCheckstyle.php'),
            new File('src/Plugins/ErrorCoverage.php'),
            new File('src/Plugins/ErrorPMD.php'),
            new File('src/Plugins/ErrorPadawan.php'),
            new File('src/Util/Autoloader.php'),
            new File('src/Util/Logger.php'),
            new File('src/View/ViewAbstract.php'),
            new File('src/View/ViewReview.php'),
            new File('src/AbstractPlugin.php'),
            new File('src/CLIController.php'),
            new File('src/File.php'),
            new File('src/Issue.php'),
            new File('src/IssueXML.php'),
            new File('src/SourceHandler.php'),
            new File('src/SourceIterator.php'),
        ];

        $mixed = [
            new File('src/AbstractPlugin.php'),
            new File('src/Plugins/ErrorCheckstyle.php'),
            new File('src/CLIController.php'),
            new File('src/Plugins/ErrorPadawan.php'),
            new File('src/SourceIterator.php'),
            new File('src/SourceHandler.php'),
            new File('src/Issue.php'),
            new File('src/View/ViewReview.php'),
            new File('src/File.php'),
            new File('src/Util/Autoloader.php'),
            new File('src/Helper/IOHelper.php'),
            new File('src/IssueXML.php'),
            new File('src/Plugins/ErrorCoverage.php'),
            new File('src/View/ViewAbstract.php'),
            new File('src/Util/Logger.php'),
            new File('src/Plugins/ErrorPMD.php'),
            new File('src/Plugins/ErrorCPD.php'),
        ];

        File::sort($mixed);
        $mixed = \array_values($mixed);
        static::assertEquals($sorted, $mixed);
    }
}
