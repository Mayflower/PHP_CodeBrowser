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
 * @package    PHP_CodeBrowser
 * @subpackage PHPUnit
 * @author     Simon Kohlmeyer <simon.kohlmeyer@mayflower.de
 * @copyright  2007-2010 Mayflower GmbH
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://www.phpunit.de/
 * @since      File available since  0.1.0
 */

require_once realpath(dirname(__FILE__) . '/../../AbstractTests.php');

/**
 * CbViewReviewTest
 *
 * @category   PHP_CodeBrowser
 * @package    PHP_CodeBrowser
 * @subpackage PHPUnit
 * @author     Simon Kohlmeyer <simon.kohlmeyer@mayflower.de>
 * @copyright  2007-2010 Mayflower GmbH
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since  0.1.0
 */
class CbViewReviewTest extends CbAbstractTests
{
    /**
     * The CbViewReview object to test.
     *
     * @var CbViewReview
     */
    protected $_cbViewReview;

    /**
     * IOHelper mock to simulate filesystem interaction.
     */
    protected $_ioMock;

    /**
     * (non-PHPdoc)
     * @see tests/cbAbstractTests#setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_ioMock = $this->getMock('CbIOHelper');

        $this->_cbViewReview = new CbViewReview(
            PHPCB_ROOT_DIR . '/templates/',
            PHPCB_TEST_OUTPUT,
            $this->_ioMock
        );
    }

    /**
     * Test the generate method without any issues
     *
     * @return void
     */
    public function test__generateNoIssues()
    {
        $expectedFile = PHPCB_TEST_OUTPUT . DIRECTORY_SEPARATOR . basename(__FILE__) . '.html';

        $this->_ioMock->expects($this->once())
                      ->method('loadFile')
                      ->with($this->equalTo(__FILE__))
                      ->will($this->returnValue(file_get_contents(__FILE__)));
        $this->_ioMock->expects($this->once())
                      ->method('createFile')
                      ->with($this->equalTo($expectedFile));

        $this->_cbViewReview->generate(
            array(),
            __FILE__,
            dirname(__FILE__) . DIRECTORY_SEPARATOR
        );
    }

    /**
     * Test the generate method with an issue
     *
     * @return void
     */
    public function test__generate()
    {
        $issueList = array(
            new CbIssue(
                __FILE__,
                80,
                85,
                'finder',
                'description',
                'severe'
            )
        );
        $file = new CbFile(__FILE__, $issueList);

        $expectedFile = PHPCB_TEST_OUTPUT . DIRECTORY_SEPARATOR . basename(__FILE__) . '.html';
        $this->_ioMock->expects($this->once())
                      ->method('loadFile')
                      ->with($this->equalTo(__FILE__))
                      ->will($this->returnValue(file_get_contents(__FILE__)));
        $this->_ioMock->expects($this->once())
                      ->method('createFile')
                      ->with($this->equalTo($expectedFile));

        $this->_cbViewReview->generate(
            $issueList,
            __FILE__,
            dirname(__FILE__) . DIRECTORY_SEPARATOR
        );
    }

    /**
     * Test the generate method with mutliple errors on one line.
     *
     * @return void
     */
    public function test__generateMultiple()
    {
        $issueList = array(
            new CbIssue(
                __FILE__,
                80,
                80,
                'finder',
                'description',
                'severe'
            ),
            new CbIssue(
                __FILE__,
                80,
                80,
                'other finder',
                'other description',
                'more severe'
            )
        );
        $file = new CbFile(__FILE__, $issueList);

        $expectedFile = PHPCB_TEST_OUTPUT . DIRECTORY_SEPARATOR . basename(__FILE__) . '.html';
        $this->_ioMock->expects($this->once())
                      ->method('loadFile')
                      ->with($this->equalTo(__FILE__))
                      ->will($this->returnValue(file_get_contents(__FILE__)));
        $this->_ioMock->expects($this->once())
                      ->method('createFile')
                      ->with($this->equalTo($expectedFile));

        $this->_cbViewReview->generate(
            $issueList,
            __FILE__,
            dirname(__FILE__) . DIRECTORY_SEPARATOR
        );
    }

    /**
     * Try to test highlighting with Text_Highlighter
     *
     * @return void
     */
    public function test__generateWithTextHighlighter()
    {
        if (!class_exists('Text_Highlighter')) {
            $this->markTestIncomplete();
        }

        $html = <<< EOT
<html>
    <head>
        <title>Title</title>
    </head>
    <body>
        <p>Body</p>
    </body>
</html>
EOT;
        $prefix = '/dir/';
        $fileName = $prefix . 'file.html';

        $expectedFile = PHPCB_TEST_OUTPUT . '/file.html.html';
        $this->_ioMock->expects($this->once())
                      ->method('loadFile')
                      ->with($this->equalTo($fileName))
                      ->will($this->returnValue($html));
        $this->_ioMock->expects($this->once())
                      ->method('createFile')
                      ->with($this->equalTo($expectedFile));

        $issues = array(
            new CbIssue(
                $fileName, 5,
                5, 'finder',
                'description',
                'severity'
            )
        );
        $file = new CbFile($fileName, $issues);
        $this->_cbViewReview->generate($issues, $fileName, $prefix);
    }

    /**
     * Test highlighting of unknown code files.
     *
     * @return void
     */
    public function test__generateUnknownType()
    {
        $expectedFile = PHPCB_TEST_OUTPUT
            . DIRECTORY_SEPARATOR .
            basename(self::$_cbXMLBasic) . '.html';

        $this->_ioMock->expects($this->once())
                      ->method('createFile')
                      ->with($this->equalTo($expectedFile));

        $issueList = array(
            new CbIssue(
                self::$_cbXMLBasic, 5,
                5, 'finder',
                'description',
                'severity'
            )
        );
        $file = new CbFile(self::$_cbXMLBasic, $issueList);

        $this->_cbViewReview->generate(
            $issueList,
            self::$_cbXMLBasic,
            dirname(self::$_cbXMLBasic) . DIRECTORY_SEPARATOR
        );
    }

    /**
     * Test if the ressource folders are copied.
     *
     * @return void
     */
    public function test__copyRessourceFolders()
    {
        $this->_ioMock->expects($this->exactly(3))
                      ->method('copyDirectory')
                      ->with(
                          $this->matchesRegularExpression(
                              '|^'
                              . realpath(
                                  dirname(__FILE__)
                                  . '/../../../templates/'
                              )
                              . '|'
                          )
                      );
        $this->_cbViewReview->copyRessourceFolders();
    }

    /**
     * Test the generateIndex function
     *
     * @return void
     */
    public function test__generateIndex()
    {
        $files = array(
            "s/A/somefile.php" => new CbFile("s/A/somefile.php"),
            "s/file.php" => new CbFile("s/file.php"),
            "s/B/anotherfile.php" => new CbFile("s/B/anotherfile.php")
        );

        $this->_ioMock->expects($this->once())
                      ->method('createFile')
                      ->with(
                          $this->logicalAnd(
                              $this->stringEndsWith('index.html')
                          )
                      );
        $this->_cbViewReview->generateIndex($files);
    }
}
