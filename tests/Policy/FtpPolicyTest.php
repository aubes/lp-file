<?php

declare(strict_types=1);

namespace Aubes\LPFile\Tests\Policy;

use Aubes\LPFile\Policy\FtpPolicy;
use Aubes\LPFile\Policy\PolicyException;
use Aubes\LPFile\Policy\PolicyInterface;
use PHPUnit\Framework\TestCase;

class FtpPolicyTest extends TestCase
{
    public function testGetProtocole()
    {
        $policyHttp = new FtpPolicy(false, 'example.com', '');

        $this->assertSame('ftp', $policyHttp->getProtocole());

        $policyHttps = new FtpPolicy(true, 'example.com', '');

        $this->assertSame('ftps', $policyHttps->getProtocole());
    }

    /**
     * @dataProvider dataProviderValidateSuccess
     */
    public function testValidateSuccess($url, $secured, $hosts, $basePaths, $allowDotSegment)
    {
        $policy = new FtpPolicy($secured, $hosts, $basePaths, $allowDotSegment);

        $context = \stream_context_create();
        $policy->validate($url, $context, PolicyInterface::MODE_READ);

        $this->addToAssertionCount(1);
    }

    public function dataProviderValidateSuccess()
    {
        return [
            [
                'ftps://example.com/example.txt',
                true,
                'example.com',
                '/',
                false,
            ],
            [
                'ftps://example.com/path/example.txt',
                true,
                'example.com',
                '/path',
                false,
            ],
            [
                'ftps://example.com/path/../example.txt',
                true,
                'example.com',
                '/path',
                true,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderValidateException
     */
    public function testValidateException($url, $secured, $hosts, $basePaths, $allowDotSegment, $message)
    {
        $policy = new FtpPolicy($secured, $hosts, $basePaths, $allowDotSegment);

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage($message);

        $policy->validate($url, \stream_context_create(), PolicyInterface::MODE_READ);
    }

    public function dataProviderValidateException()
    {
        return [
            [
                'ftps://unknown.com/example.txt',
                true,
                'example.com',
                '/',
                false,
                'Host is not allowed',
            ],
            [
                'ftps://example.com/unknown-path/example.txt',
                true,
                'example.com',
                '/path',
                false,
                'Path is not allowed',
            ],
            [
                'ftps://example.com/path/../example.txt',
                true,
                'example.com',
                '/path',
                false,
                'Dot-segments are not allowed',
            ],
        ];
    }
}
