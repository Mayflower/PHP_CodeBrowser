<?php

namespace PHPCodeBrowser\Tests;

use PHPCodeBrowser\Application;
use PHPCodeBrowser\Command\RunCommand;

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
        $this->assertInstanceOf(RunCommand::class, $this->application->get('phpcb'));
    }
}
