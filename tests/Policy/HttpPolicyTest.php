<?php

declare(strict_types=1);

namespace Aubes\LPFile\Tests\Policy;

use Aubes\LPFile\Policy\HttpPolicy;
use Aubes\LPFile\Policy\PolicyException;
use Aubes\LPFile\Policy\PolicyInterface;
use PHPUnit\Framework\TestCase;

class HttpPolicyTest extends TestCase
{
    public function testGetProtocole()
    {
        $policyHttp = new HttpPolicy(false, 'www.php.net', '');

        $this->assertSame('http', $policyHttp->getProtocole());

        $policyHttps = new HttpPolicy(true, 'www.php.net', '');

        $this->assertSame('https', $policyHttps->getProtocole());
    }

    /**
     * @dataProvider dataProviderValidateSuccess
     */
    public function testValidateSuccess($url, $secured, $hosts, $basePaths, $queryString, $allowDotSegment, $followRedirect)
    {
        $policy = new HttpPolicy($secured, $hosts, $basePaths, $queryString, $allowDotSegment, $followRedirect);

        $context = \stream_context_create();
        $policy->validate($url, $context, PolicyInterface::MODE_READ);

        $options = \stream_context_get_options($context);

        if (!$followRedirect) {
            $this->assertArrayHasKey('http', $options);
            $this->assertArrayHasKey('follow_location', $options['http']);
            $this->assertEquals(0, $options['http']['follow_location']);
        } else {
            $this->addToAssertionCount(1);
        }
    }

    public function dataProviderValidateSuccess()
    {
        return [
            [
                'https://www.php.net',
                true,
                'www.php.net',
                '',
                [],
                false,
                false,
            ],
            [
                'https://Www.Php.Net',
                true,
                'wwW.phP.neT',
                '',
                [],
                false,
                false,
            ],
            [
                'https://www.php.net',
                true,
                'www.php.net',
                '',
                [],
                false,
                true,
            ],
            [
                'https://www.php.net/manual/fr/function.file-get-contents.php',
                true,
                ['www.php.net'],
                ['', '/manual'],
                [],
                false,
                false,
            ],
            [
                'https://www.php.net/manual/../manual/fr/function.file-get-contents.php',
                true,
                ['www.php.net'],
                ['', '/manual'],
                [],
                true,
                false,
            ],
            [
                'https://www.php.net/manual-lookup.php?pattern=test',
                true,
                ['www.php.net'],
                '/manual',
                ['pattern'],
                false,
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderValidateException
     */
    public function testValidateException($url, $secured, $hosts, $basePaths, $queryString, $allowDotSegment, $followRedirect, $message)
    {
        $policy = new HttpPolicy($secured, $hosts, $basePaths, $queryString, $allowDotSegment, $followRedirect);

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage($message);

        $policy->validate($url, \stream_context_create(), PolicyInterface::MODE_READ);
    }

    public function dataProviderValidateException()
    {
        return [
            [
                'https://www.php.com',
                true,
                'www.php.net',
                '',
                [],
                false,
                false,
                'Host is not allowed',
            ],
            [
                'https://www.php.net',
                true,
                'www.php.net',
                '/manual',
                [],
                false,
                false,
                'Path is not allowed',
            ],
            [
                'https://www.php.net/manual/../',
                true,
                'www.php.net',
                '/manual',
                [],
                false,
                false,
                'Dot-segments are not allowed',
            ],
            [
                'https://www.php.net/manual-lookup.php?pattern=test',
                true,
                ['www.php.net'],
                '/manual',
                [],
                false,
                false,
                'Query is not allowed',
            ],
            [
                'https://www.php.net/manual-lookup.php?unknown=test',
                true,
                ['www.php.net'],
                '/manual',
                ['pattern'],
                false,
                false,
                'Query is not allowed',
            ],
        ];
    }
}
