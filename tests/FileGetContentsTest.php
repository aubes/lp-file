<?php

declare(strict_types=1);

namespace Aubes\LPFile\Tests;

use Aubes\LPFile\LPFile;
use Aubes\LPFile\Policy\PolicyInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class FileGetContentsTest extends TestCase
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

        $this->assertEquals('test', $LPFile->fileGetContents(__DIR__ . '/Files.txt/test.txt'));
        $this->assertEquals('test', $LPFile->fileGetContents('file://' . __DIR__ . '/Files.txt/test.txt'));
        $this->assertEquals('test', $LPFile->fileGetContents('FILE://' . __DIR__ . '/Files.txt/test.txt'));
        $this->assertEquals('es', $LPFile->fileGetContents(__DIR__ . '/Files.txt/test.txt', null, 1, 2));
    }

    public function testInvokeWithoutPolicy()
    {
        $LPFile = new LPFile();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Protocole is not allowed');

        $LPFile->fileGetContents(__DIR__ . '/Files.txt/test.txt');
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

        $LPFile->fileGetContents('unknown://');
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

        $LPFile->fileGetContents('file://');

        $this->assertContains('Invalid mode', $LPFile->getLastViolations());
    }

    /**
     * @dataProvider dataProviderSupportedMode
     */
    public function testSupportedMode($supportedMode, $policyMode)
    {
        $policy = $this->prophesize(PolicyInterface::class);
        $policy->getProtocole()->willReturn('file');
        $policy->getSupportedMode()->willReturn($supportedMode);

        $LPFile = new LPFile();
        $LPFile->addPolicy($policy->reveal(), $policyMode);

        $this->addToAssertionCount(1);
    }

    public function dataProviderSupportedMode()
    {
        return [
            [
                PolicyInterface::MODE_READ,
                PolicyInterface::MODE_READ,
            ],
            [
                PolicyInterface::MODE_READ | PolicyInterface::MODE_WRITE,
                PolicyInterface::MODE_READ,
            ],
            [
                PolicyInterface::MODE_READ | PolicyInterface::MODE_WRITE,
                PolicyInterface::MODE_WRITE,
            ],
            [
                PolicyInterface::MODE_WRITE,
                PolicyInterface::MODE_WRITE,
            ],
            [
                PolicyInterface::MODE_READ | PolicyInterface::MODE_WRITE,
                PolicyInterface::MODE_READ | PolicyInterface::MODE_WRITE,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderUnsupportedMode
     */
    public function testUnsupportedMode($policyMode, $mode)
    {
        $policy = $this->prophesize(PolicyInterface::class);
        $policy->getProtocole()->willReturn('file');
        $policy->getSupportedMode()->willReturn($policyMode);

        $LPFile = new LPFile();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported mode');

        $LPFile->addPolicy($policy->reveal(), $mode);
    }

    public function dataProviderUnsupportedMode()
    {
        return [
            [
                PolicyInterface::MODE_READ,
                PolicyInterface::MODE_WRITE,
            ],
            [
                PolicyInterface::MODE_WRITE,
                PolicyInterface::MODE_READ,
            ],
            [
                PolicyInterface::MODE_READ,
                PolicyInterface::MODE_WRITE | PolicyInterface::MODE_READ,
            ],
            [
                PolicyInterface::MODE_WRITE,
                PolicyInterface::MODE_WRITE | PolicyInterface::MODE_READ,
            ],
        ];
    }
}
