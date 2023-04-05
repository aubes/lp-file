<?php

declare(strict_types=1);

namespace Aubes\LPFile\Policy;

class FtpPolicy implements PolicyInterface
{
    protected bool $secured;
    protected array $hosts;
    protected array $basePaths;
    protected bool $allowDotSegment;

    /**
     * @param array|string $hosts
     * @param array|string $basePaths
     */
    public function __construct(
        bool $secured,
        $hosts,
        $basePaths,
        bool $allowDotSegment = false
    ) {
        $this->secured = $secured;
        $this->hosts = \array_map('mb_strtolower', (array) $hosts);
        $this->basePaths = (array) $basePaths;
        $this->allowDotSegment = $allowDotSegment;
    }

    public function getProtocole(): string
    {
        if ($this->secured) {
            return 'ftps';
        }

        return 'ftp';
    }

    public function getSupportedMode(): int
    {
        return PolicyInterface::MODE_READ | PolicyInterface::MODE_READ;
    }

    /**
     * @param resource $context
     */
    public function validate(string $filename, $context, int $mode): string
    {
        $parseUrl = \parse_url($filename);

        if (!$this->validateHost($parseUrl['host'] ?? '')) {
            throw new PolicyException('Host is not allowed');
        }

        if (!$this->validatePath($parseUrl['path'] ?? '')) {
            throw new PolicyException('Path is not allowed');
        }

        if (!$this->allowDotSegment && str_contains($filename, '../')) {
            throw new PolicyException('Dot-segments are not allowed');
        }

        return $filename;
    }

    protected function validateHost(string $host): bool
    {
        return \in_array(\mb_strtolower($host), $this->hosts);
    }

    protected function validatePath(string $path): bool
    {
        foreach ($this->basePaths as $basePath) {
            if (str_starts_with($path, $basePath)) {
                return true;
            }
        }

        return false;
    }
}
