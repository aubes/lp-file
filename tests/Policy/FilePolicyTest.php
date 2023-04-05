<?php

declare(strict_types=1);

namespace Aubes\LPFile\Tests\Policy;

use Aubes\LPFile\Policy\FilePolicy;
use Aubes\LPFile\Policy\PolicyException;
use Aubes\LPFile\Policy\PolicyInterface;
use PHPUnit\Framework\TestCase;

class FilePolicyTest extends TestCase
{
    public function testGetProtocole()
    {
        $policy = new FilePolicy(__DIR__ . '/../Files.txt', 'txt');

        $this->assertSame('file', $policy->getProtocole());
    }

    /**
     * @dataProvider dataProviderValidateSuccess
     */
    public function testValidateSuccess($filename, $basePath, $extensions, $allowParentDirectory)
    {
        $policy = new FilePolicy($basePath, $extensions, $allowParentDirectory);

        $policy->validate($filename, \stream_context_create(), PolicyInterface::MODE_READ);

        $this->addToAssertionCount(1);
    }

    public function dataProviderValidateSuccess()
    {
        return [
            [
                __DIR__ . '/../Files.txt/test.txt',
                __DIR__ . '/../Files.txt',
                'txt',
                true,
            ],
            [
                __DIR__ . '/../Files.txt/test.txt',
                __DIR__ . '/../Files.txt',
                ['csv', 'txt'],
                true,
            ],
            [
                'file://' . __DIR__ . '/../Files.txt/test.txt',
                __DIR__ . '/../Files.txt',
                'txt',
                true,
            ],
            [
                __DIR__ . '/../Files.txt/test.txt',
                __DIR__ . '/../Files.txt',
                'txt',
                true,
            ],
            [
                __DIR__ . '/../../tests/Files.txt/test.txt',
                __DIR__ . '/../Files.txt',
                'txt',
                true,
            ],
        ];
    }

    public function testEmptyBaseDirectory()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Base directory is empty');

        new FilePolicy('', 'txt');
    }

    /**
     * @dataProvider dataProviderValidateException
     */
    public function testValidateException($filename, $basePath, $extensions, $allowParentDirectory, $message)
    {
        $policy = new FilePolicy($basePath, $extensions, $allowParentDirectory);

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage($message);

        $policy->validate($filename, \stream_context_create(), PolicyInterface::MODE_READ);
    }

    public function dataProviderValidateException()
    {
        return [
            [
                __DIR__ . '/../Files.txt/test.txt',
                __DIR__ . '/../Unknown',
                'csv',
                true,
                'Base directory is invalid',
            ],
            [
                __DIR__ . '/../Files.txt/test.txt',
                __DIR__ . '/../Files.txt/test.txt',
                'csv',
                true,
                'Base directory must be a directory',
            ],
            [
                __DIR__ . '/../Files.txt/test.txt',
                __DIR__ . '/../Files.txt',
                'csv',
                true,
                'File extensions not allowed',
            ],
            [
                __DIR__ . '/../Files.txt/test.txt',
                __DIR__ . '/../Files.txt',
                'txt',
                false,
                'Parent directory is not allowed',
            ],
            [
                __DIR__ . '/../Files.txt/test.txt',
                __DIR__ . '/./',
                'txt',
                true,
                'File is outside the base directory',
            ],
            [
                __DIR__ . '/../Files.txt/Unknown.txt',
                __DIR__ . '/../Files.txt',
                'txt',
                true,
                'File does not exist',
            ],
            [
                __DIR__ . '/../Files.txt',
                __DIR__ . '/../Files.txt',
                'txt',
                true,
                'File is a directory',
            ],
        ];
    }
}
