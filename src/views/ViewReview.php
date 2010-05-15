<?php

@include_once 'Text/Highlighter.php';
@include_once 'Text/Highlighter/Renderer/Html.php';

class CbViewReview extends CbViewAbstract
{

    /**
     *
     * @param array  $issueList
     * @param string $filePath
     */
    public function generate(Array $issueList, $fileName, $commonPathPrefix)
    {
        if (!is_array($issueList)) {
            throw new Exception('Wrong data format for errorlist!');
        }

        $issues = $this->_formatIssues($issueList);

        $shortFilename = substr($fileName, strlen($commonPathPrefix));

        $data['issues']   = $issues;
        $data['title']    = 'Code Browser - ViewReview View';
        $data['filepath'] = $shortFilename;
        $data['csspath']  = '';
        $data['source']   = $this->_formatSourceCode($fileName, $issues);
        $data['jsCode']   = $this->_grenerateJSCode($issues);

        $depth = substr_count($shortFilename, DIRECTORY_SEPARATOR);
        $data['csspath'] = str_repeat('../', $depth-1 >= 0 ? $depth -1: 0);

        $dataGenerate['title']   = $data['title'];
        $dataGenerate['csspath'] = $data['csspath'];

        $dataGenerate['content'] = $this->_render('review', $data);

        $this->_generateView($dataGenerate, $shortFilename . '.html');
    }


    private function _grenerateJSCode($issueList)
    {
        $jsCode = '';

        foreach ($issueList as $num=>$lineIssues) {

            $htmlMessages[$num] = '';

            foreach ($lineIssues as $issue) {

                $htmlMessages[$num] .= addcslashes("<span class=\"title ".$issue->foundBy."\">".
                                      $issue->foundBy . "</span><span class=\"message\">".
                                      (string)$issue->description."</span>", "\"\'\0..\37!@\177..\377");
            }

            $jsCode .= "$('#line_".$num."').cluetip({splitTitle: '|', activation: 'click', tracking: true, cluetipClass: 'default'});";

        }

        return $jsCode;

    }

    /**
     *
     * @param unknown_type $filename
     * @param unknown_type $outputIssues
     */
    private function _formatSourceCode($filename, $outputIssues)
    {
        $sourceDom = $this->_highlightCode($filename);

        $xpath = new DOMXPath($sourceDom);
        $lines = $xpath->query('li');
        $lineNumber = 0;
        foreach ($lines as $line) {
            ++$lineNumber;
            $line->setAttribute('id', 'line_' . $lineNumber);

            if (isset($outputIssues[$lineNumber])) {
                $message = '|';
                foreach ($outputIssues[$lineNumber] as $issue) {
                    $message .= '<span class="tooltip"><div class="title ' . $issue->foundBy . '">' .
                                $issue->foundBy . '</div><span class="text">' .
                                $issue->description . '</span>';
                }
                $line->setAttribute('title', $message);
            }


            //create anchor for the new line
            $anchor = $sourceDom->createElement('a');
            $anchor->setAttribute('name', 'line_' . $lineNumber);
            $line->appendChild($anchor);

            $line->setAttribute('class', $lineNumber % 2 ? 'white' : 'even');

            // set li css class depending on line errors
            if (isset($outputIssues[$lineNumber])) {
                if (1 === count($outputIssues[$lineNumber])) {
                    $line->setAttribute('class', $outputIssues[$lineNumber][0]->foundBy);
                } else if(1 <= count($outputIssues[$lineNumber])) {
                    $line->setAttribute('class', 'moreErrors');
                }
            }
        }
        return $sourceDom->saveHTML();
    }

    protected function _highlightPhpCode($sourceCode)
    {
        $code = highlight_string($sourceCode, true);

        $sourceDom = new DOMDocument();
        $sourceDom->loadHTML($code);

        //fetch <code>-><span>->children from php generated html
        $sourceElements = $sourceDom->getElementsByTagname('code')->item(0)
                                    ->childNodes->item(0)->childNodes;

        //create target dom
        $targetDom = new DOMDocument();

        $targetNode = $targetDom->createElement('ol');
        $targetNode->setAttribute('class', 'code');

        $targetDom->appendChild($targetNode);

        $li = $targetDom->createElement('li');
        $targetNode->appendChild($li);
        // iterate through all <span> elements
        foreach ($sourceElements as $sourceElement) {
            if (!$sourceElement instanceof DOMElement) {
                $span = $targetDom->createElement('span');
                $span->nodeValue = htmlspecialchars($sourceElement->wholeText);
                $li->appendChild($span);

                continue;
            }

            if ('br' === $sourceElement->tagName) {
                // create new li and new line
                $li = $targetDom->createElement('li');
                $targetNode->appendChild($li);
                continue;
            }

            $elementStyle = $sourceElement->getAttribute('style');

            foreach ($sourceElement->childNodes as $sourceChildElement) {
                if (
                    $sourceChildElement instanceof DOMElement
                    && 'br' === $sourceChildElement->tagName
                ) {
                    // create new li and new line
                    $li = $targetDom->createElement('li');
                    $targetNode->appendChild($li);
                } else {
                    // apend content to current li element
                    // apend content to urrent li element
                    $span = $targetDom->createElement('span');
                    $span->nodeValue = htmlspecialchars($sourceChildElement->wholeText);
                    $span->setAttribute('style', $elementStyle);
                    $li->appendChild($span);
                }
            }
        }

        return $targetDom;
    }

    protected function _highlightCode($file)
    {
        $highlightmap = array(
            '.js' => 'JAVASCRIPT',
            '.html' => 'HTML',
            '.css' => 'CSS',
        );

        $extenstion = strrchr($file, '.');
        $sourceCode = $this->_cbIOHelper->loadFile($file);

        if ('.php' === $extenstion) {
            return $this->_highlightPhpCode($sourceCode);
        } else if (
            class_exists('Text_Highlighter', false)
            && isset($highlightmap[$extenstion])
        ) {
            $renderer = new Text_Highlighter_Renderer_Html(array(
                'numbers' => HL_NUMBERS_LI,
                'tabsize' => 4,
                'class_map' => array(
                    'comment'    => 'comment',
                    'main'       => 'code',
                    'table'      => 'table',
                    'gutter'     => 'gutter',
                    'brackets'   => 'brackets',
                    'builtin'    => 'builtin',
                    'code'       => 'code',
                    'default'    => 'default',
                    'identifier' => 'identifier',
                    'inlinedoc'  => 'inlinedoc',
                    'inlinetags' => 'inlinetags',
                    'mlcomment'  => 'mlcomment',
                    'number'     => 'number',
                    'quotes'     => 'quotes',
                    'reserved'   => 'reserved',
                    'special'    => 'special',
                    'string'     => 'string',
                    'url'        => 'url',
                    'var'        => 'var',
                )
            ));
            $highlighter = Text_Highlighter::factory($highlightmap[$extenstion]);
            $highlighter->setRenderer($renderer);

            $doc = new DOMDocument();
            $doc->loadHTML($highlighter->highlight($sourceCode));
            return $doc;
        } else {
            $sourceCode = preg_replace('/.*/', '<li>$0</li>', htmlentities($sourceCode));
            $sourceCode = '<div class="code"><ol class="code">'.$sourceCode.'</ol></div>';

            $doc = new DOMDocument();
            $doc->loadHTML($sourceCode);

            return $doc;
        }
    }

    private function _formatIssues($issueList)
    {
        $outputIssues = array();

        foreach ($issueList as $issues) foreach ($issues as $error) {
            for ($i = $error->lineStart; $i <= $error->lineEnd; $i++) {
                $outputIssues[$i][] = $error;
            }
        }

        return $outputIssues;
    }

}
