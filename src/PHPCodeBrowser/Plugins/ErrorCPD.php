<?php
/**
 * Copy paste detection
 *
 * PHP Version 5.2.6
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
 * @category   PHP_CodeBrowser
 *
 * @author     Elger Thiele <elger.thiele@mayflower.de>
 * @author     Michel Hartmann <michel.hartmann@mayflower.de>
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

namespace PHPCodeBrowser\Plugins;

use DOMElement;
use DOMNode;
use DOMNodeList;
use PHPCodeBrowser\AbstractPlugin;
use PHPCodeBrowser\Issue;

/**
 * ErrorCPD
 *
 * @category   PHP_CodeBrowser
 *
 * @author     Elger Thiele <elger.thiele@mayflower.de>
 * @author     Michel Hartmann <michel.hartmann@mayflower.de>
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
class ErrorCPD extends AbstractPlugin
{
    /**
     * @var string $pluginName
     */
    public $pluginName = 'pmd-cpd';

    /**
     * Mapper method for this plugin.
     *
     * @param DOMNode $element  The XML plugin node with its errors
     * @param string  $filename
     *
     * @return array
     */
    public function mapIssues(DOMNode $element, string $filename): array
    {
        $parentNode = $element->parentNode;
        $files      = $this->issueXml->query(
            'file[@path="'.$filename.'"]',
            $parentNode
        );
        $lineCount  = (int) $parentNode->getAttribute('lines');

        $result = [];

        foreach ($files as $file) {
            $result[] = new Issue(
                $file->getAttribute('path'),
                (int) $file->getAttribute('line'),
                (int) $file->getAttribute('line') + $lineCount,
                'Duplication',
                htmlentities(
                    $this->getCpdDescription($parentNode->childNodes, $file)
                ),
                'notice'
            );
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getFilesWithIssues(): array
    {
        $fileNames = [];
        $nodes     = $this->issueXml->query(
            '/*/'.$this->pluginName.'/*/file[@path]'
        );

        foreach ($nodes as $node) {
            $fileNames[] = $node->getAttribute('path');
        }

        return array_unique($fileNames);
    }

    /**
     * Get all DOMNodes that represent issues for a specific file.
     *
     * @param string $filename Name of the file to get nodes for.
     *
     * @return DOMNodeList
     */
    protected function getIssueNodes(string $filename): DOMNodeList
    {
        return $this->issueXml->query(
            '/*/'.$this->pluginName.'/*/file[@path="'.$filename.'"]'
        );
    }

    /**
     * We need another version of getDescription, as we need $allNodes
     * to find duplicates.
     *
     * @param DOMNodeList $allNodes
     * @param DOMNode     $currentNode
     *
     * @return string
     */
    protected function getCpdDescription(DOMNodeList $allNodes, DOMNode $currentNode): string
    {
        $source = [];

        foreach ($allNodes as $node) {
            if (!($node instanceof DOMElement)
                || $node->isSameNode($currentNode)
            ) {
                continue;
            }

            $source[] = sprintf(
                '%s (%d)',
                $node->getAttribute('path'),
                $node->getAttribute('line')
            );
        }

        return "Copy paste from:\n".implode("\n", $source);
    }
}
