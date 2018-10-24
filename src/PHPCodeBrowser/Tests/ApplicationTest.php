<?php

namespace PHPCodeBrowser\Tests;

use PHPCodeBrowser\Application;

/**
 * Class ApplicationTest
 */
class ApplicationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Application
     */
    private $application;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->application = new Application();
    }

    /**
     *
     */
    public function testCommand(): void
    {
        $this->assertInstanceOf('PHPCodeBrowser\\Command\\RunCommand', $this->application->get('phpcb'));
    }
}
