<?php

declare(strict_types=1);

namespace Aubes\LPFile\Tests;

use Aubes\LPFile\LPFile;
use Aubes\LPFile\Policy\PolicyInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class FileTest extends TestCase
{
    use ProphecyTrait;

    public function testInvokeDefault()
    {
        $policy = $this->prophesize(PolicyInterface::class);
        $policy->getProtocole()->willReturn('file');
        $policy->getSupportedMode()->willReturn(PolicyInterface::MODE_READ);
        $policy->validate(Argument::any(), Argument::any(), Argument::any())->willReturnArgument(0);

        $LPFile = new LPFile();
        $LPFile->addPolicy($policy->reveal());

        $this->assertEquals(['test'], $LPFile->file(__DIR__ . '/Files.txt/test.txt'));
    }

    public function testInvokeWithoutPolicy()
    {
        $LPFile = new LPFile();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Protocole is not allowed');

        $LPFile->file(__DIR__ . '/Files.txt/test.txt');
    }

    public function testInvokeUnknownProtocole()
    {
        $policy = $this->prophesize(PolicyInterface::class);
        $policy->getProtocole()->willReturn('file');
        $policy->getSupportedMode()->willReturn(PolicyInterface::MODE_READ);

        $LPFile = new LPFile();
        $LPFile->addPolicy($policy->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Protocole is not allowed');

        $LPFile->file('unknown://');
    }

    public function testInvokeWrongMode()
    {
        $policy = $this->prophesize(PolicyInterface::class);
        $policy->getProtocole()->willReturn('file');
        $policy->getSupportedMode()->willReturn(PolicyInterface::MODE_WRITE);

        $LPFile = new LPFile();
        $LPFile->addPolicy($policy->reveal(), PolicyInterface::MODE_WRITE);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No policy validated');

        $LPFile->file('file://');

        $this->assertContains('Invalid mode', $LPFile->getLastViolations());
    }
}
