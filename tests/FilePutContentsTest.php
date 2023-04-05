<?php

declare(strict_types=1);

namespace Aubes\LPFile\Tests;

use Aubes\LPFile\LPFile;
use Aubes\LPFile\Policy\PolicyInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class FilePutContentsTest extends TestCase
{
    use ProphecyTrait;

    public function testSuccess()
    {
        $policy = $this->prophesize(PolicyInterface::class);
        $policy->getProtocole()->willReturn('file');
        $policy->getSupportedMode()->willReturn(PolicyInterface::MODE_READ | PolicyInterface::MODE_WRITE);
        $policy->validate(Argument::any(), Argument::any(), Argument::any())->willReturnArgument(0);

        $LPFile = new LPFile();
        $LPFile->addPolicy($policy->reveal(), PolicyInterface::MODE_WRITE);

        $this->assertSame(4, $LPFile->filePutContents(__DIR__ . '/Files.txt/test-put.txt', 'test'));
    }

    public function testSuccesBothMode()
    {
        $policy = $this->prophesize(PolicyInterface::class);
        $policy->getProtocole()->willReturn('file');
        $policy->getSupportedMode()->willReturn(PolicyInterface::MODE_READ | PolicyInterface::MODE_WRITE);
        $policy->validate(Argument::any(), Argument::any(), Argument::any())->willReturnArgument(0);

        $LPFile = new LPFile();
        $LPFile->addPolicy($policy->reveal(), PolicyInterface::MODE_READ | PolicyInterface::MODE_WRITE);

        $this->assertSame(4, $LPFile->filePutContents(__DIR__ . '/Files.txt/test-put.txt', 'test'));
    }

    public function testWrongMode()
    {
        $policy = $this->prophesize(PolicyInterface::class);
        $policy->getProtocole()->willReturn('file');
        $policy->getSupportedMode()->willReturn(PolicyInterface::MODE_READ | PolicyInterface::MODE_WRITE);
        $policy->validate(Argument::any(), Argument::any(), Argument::any())->willReturnArgument(0);

        $LPFile = new LPFile();
        $LPFile->addPolicy($policy->reveal(), PolicyInterface::MODE_READ);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No policy validated');

        $this->assertSame(4, $LPFile->filePutContents(__DIR__ . '/Files.txt/test-put.txt', 'test'));
    }
}
