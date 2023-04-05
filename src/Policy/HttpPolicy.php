<?php

declare(strict_types=1);

namespace Aubes\LPFile\Policy;

class HttpPolicy implements PolicyInterface
{
    protected bool $secured;
    protected array $hosts;
    protected array $basePaths;
    protected bool $allowDotSegment;
    protected array $queryString;
    protected bool $followRedirect;

    /**
     * @param array|string $hosts
     * @param array|string $basePaths
     */
    public function __construct(
        bool $secured,
        $hosts,
        $basePaths,
        array $queryString = [],
        bool $allowDotSegment = false,
        bool $followRedirect = false
    ) {
        $this->secured = $secured;
        $this->hosts = \array_map('mb_strtolower', (array) $hosts);
        $this->basePaths = (array) $basePaths;
        $this->allowDotSegment = $allowDotSegment;
        $this->queryString = $queryString;
        $this->followRedirect = $followRedirect;
    }

    public function getProtocole(): string
    {
        if ($this->secured) {
            return 'https';
        }

        return 'http';
    }

    public function getSupportedMode(): int
    {
        return PolicyInterface::MODE_READ;
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

        if (!$this->allowDotSegment && \str_contains($filename, '../')) {
            throw new PolicyException('Dot-segments are not allowed');
        }

        if (!$this->validateQueryString($parseUrl['query'] ?? '')) {
            throw new PolicyException('Query is not allowed');
        }

        if (!$this->followRedirect) {
            \stream_context_set_option($context, ['http' => ['follow_location' => 0]]);
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
            if (\str_starts_with($path, $basePath)) {
                return true;
            }
        }

        return false;
    }

    protected function validateQueryString(string $query): bool
    {
        if (empty($query)) {
            return true;
        }

        if (empty($this->queryString)) {
            return false;
        }

        \parse_str($query, $queryStringList);

        foreach (\array_keys($queryStringList) as $queryString) {
            if (!\in_array($queryString, $this->queryString)) {
                return false;
            }
        }

        return true;
    }
}
