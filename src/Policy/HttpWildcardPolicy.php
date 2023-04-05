<?php

declare(strict_types=1);

namespace Aubes\LPFile\Policy;

class HttpWildcardPolicy extends HttpPolicy implements PolicyInterface
{
    /**
     * @param array|string $hosts
     * @param array|string $basePaths
     */
    public function __construct(bool $secured, $hosts, $basePaths, array $queryString = [], bool $allowDotSegment = false, bool $followRedirect = false)
    {
        $hosts = \array_map([$this, 'preparePattern'], (array) $hosts);
        $basePaths = \array_map([$this, 'preparePattern'], (array) $basePaths);

        parent::__construct($secured, $hosts, $basePaths, $queryString, $allowDotSegment, $followRedirect);
    }

    protected function validateHost(string $host): bool
    {
        foreach ($this->hosts as $pattern) {
            if (\preg_match($pattern . 'i', $host) === 1) {
                return true;
            }
        }

        return false;
    }

    protected function validatePath(string $path): bool
    {
        foreach ($this->basePaths as $pattern) {
            if (\preg_match($pattern, $path) === 1) {
                return true;
            }
        }

        return false;
    }

    protected function preparePattern(string $pattern): string
    {
        return '#^' . \str_replace('\*', '.*', \preg_quote($pattern, '#')) . '$#';
    }
}
