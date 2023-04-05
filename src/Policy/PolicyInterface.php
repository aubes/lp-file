<?php

declare(strict_types=1);

namespace Aubes\LPFile\Policy;

interface PolicyInterface
{
    public const MODE_READ = 1;
    public const MODE_WRITE = 2;

    public function getProtocole(): string;

    public function getSupportedMode(): int;

    /**
     * @param resource $context
     */
    public function validate(string $filename, $context, int $mode): string;
}
