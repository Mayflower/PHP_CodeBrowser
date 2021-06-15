<?php

namespace PHPCodeBrowser\Tests;

use PHPCodeBrowser\Application;
use PHPCodeBrowser\Command\RunCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class ApplicationTest
 */
class ApplicationTest extends TestCase
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

    /**
     *
     */
    public function testGetDefinitionClearsArguments(): void
    {
        $this->application->getDefinition()->setArguments([new InputArgument('foo')]);

        $this->assertEquals(0, $this->application->getDefinition()->getArgumentCount());
    }
}
