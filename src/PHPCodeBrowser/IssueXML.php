<?php

/**
 * Issue XML Document
 *
 * PHP Version 5.3.2
 *
 * Copyright (c) 2007-2009, Mayflower GmbH
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
 * @author Elger Thiele <elger.thiele@mayflower.de>
 * @author Michel Hartmann <michel.hartmann@mayflower.de>
 *
 * @copyright 2007-2010 Mayflower GmbH
 *
 * @license http://www.opensource.org/licenses/bsd-license.php  BSD License
 *
 * @version SVN: $Id$
 *
 * @link http://www.phpunit.de/
 *
 * @since File available since 0.1.0
 */

namespace PHPCodeBrowser;

use DOMDocument;
use DOMDocumentType;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use SebastianBergmann\FileIterator\Factory as FileIteratorFactory;

/**
 * IssueXML
 *
 * This class is a wrapper around DOMDocument to provide additional features
 * like simple xpath queries.
 * It is used to merge issue XML files and execute plugins
 * against it to retrieve the issues from them.
 *
 * @category PHP_CodeBrowser
 *
 * @author Elger Thiele <elger.thiele@mayflower.de>
 * @author Michel Hartmann <michel.hartmann@mayflower.de>
 *
 * @copyright 2007-2010 Mayflower GmbH
 *
 * @license http://www.opensource.org/licenses/bsd-license.php  BSD License
 *
 * @version Release: @package_version@
 *
 * @link http://www.phpunit.de/
 *
 * @since Class available since 0.1.0
 */
class IssueXML extends DOMDocument
{
    /**
     *
     *
     * @var DOMXPath
     */
    protected $xpath;

    /**
     * Default constructor
     *
     * @param string $version  The version definition for DomDocument
     * @param string $encoding The used encoding for DomDocument
     */
    public function __construct(string $version = '1.0', string $encoding = 'UTF-8')
    {
        parent::__construct($version, $encoding);
        $this->appendChild(
            $this->createElement('codebrowser')
        );
        $this->preserveWhiteSpace = false;
        $this->formatOutput       = true;
    }

    /**
     * Parses directory for XML report files, generating a single DomDocument
     * inheriting all files and issues.
     *
     * @param string $directory The path to directory where xml files are stored
     *
     * @return IssueXML This object
     */
    public function addDirectory(string $directory): IssueXML
    {
        $factory  = new FileIteratorFactory();
        $iterator = $factory->getFileIterator($directory, 'xml');

        foreach ($iterator as $current) {
            $realFileName         = \realpath($current);
            $xml                  = new DOMDocument('1.0', 'UTF-8');
            $xml->validateOnParse = true;

            if (@$xml->load(\realpath($current))) {
                $this->addXMLFile($xml);
            } else {
                \error_log(
                    "[Warning] Could not read file '{$realFileName}'. ".'Make sure it contains valid xml.'
                );
            }

            unset($xml);
        }

        if (!$this->documentElement->hasChildNodes()) {
            \error_log("[Warning] No valid log files found in '{$directory}'");
        }

        return $this;
    }

    /**
     * Add xml file to merge
     *
     * @param DOMDocument $domDocument The DOMDocument to merge.
     *
     * @return void
     */
    public function addXMLFile(DOMDocument $domDocument): void
    {
        foreach ($domDocument->childNodes as $node) {
            if ($node instanceof DOMDocumentType) {
                continue;
            }

            $this->documentElement->appendChild($this->importNode($node, true));
        }
    }

    /**
     * Perform a XPath-Query on the document.
     *
     * @see DOMXPath::query
     *
     * @param string       $expression  Xpath expression to query for.
     * @param DOMNode|null $contextNode Node to use as context (optional)
     *
     * @return DOMNodeList List of all matching nodes.
     */
    public function query(string $expression, ?DOMNode $contextNode = null): DOMNodeList
    {
        if (null === $this->xpath) {
            $this->xpath = new DOMXPath($this);
        }

        $result = $contextNode ? $this->xpath->query($expression, $contextNode) : $this->xpath->query($expression);

        return $result;
    }
}
