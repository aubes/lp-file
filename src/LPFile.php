<?php

declare(strict_types=1);

namespace Aubes\LPFile;

use Aubes\LPFile\Policy\PolicyException;
use Aubes\LPFile\Policy\PolicyInterface;

class LPFile
{
    protected array $policies = [];
    protected array $violations = [];

    public function addPolicy(PolicyInterface $policy, int $mode = PolicyInterface::MODE_READ): void
    {
        if (($policy->getSupportedMode() & $mode) !== $mode) {
            throw new \InvalidArgumentException('Unsupported mode');
        }

        $this->policies[$policy->getProtocole()][] = [
            'policy' => $policy,
            'mode' => $mode,
        ];
    }

    /**
     * @param resource $context
     */
    public function validatePolicies(string $filename, $context, int $mode): string
    {
        $separatorIndex = \mb_strpos($filename, '://');

        $protocole = $separatorIndex === false ? 'file' : \mb_strtolower(\mb_substr($filename, 0, $separatorIndex));

        if (\array_key_exists($protocole, $this->policies)) {
            $this->violations = [];

            foreach ($this->policies[$protocole] as $policy) {
                try {
                    if (($policy['mode'] & $mode) !== $mode) {
                        throw new PolicyException('Invalid mode');
                    }

                    return $policy['policy']->validate($filename, $context, $mode);
                } catch (PolicyException $e) {
                    $this->violations[] = $e->getMessage();
                }
            }

            throw new \RuntimeException('No policy validated');
        }

        throw new \RuntimeException('Protocole is not allowed');
    }

    public function getLastViolations(): array
    {
        return $this->violations;
    }

    /**
     * @param null|resource $context
     */
    public function file(
        string $filename,
        int $flags = 0,
        $context = null
    ): array {
        if ($context === null) {
            $context = \stream_context_create();
        }

        $filename = $this->validatePolicies($filename, $context, PolicyInterface::MODE_READ);

        // Remove flag FILE_USE_INCLUDE_PATH
        $flags &= ~\FILE_USE_INCLUDE_PATH;

        $content = \file($filename, $flags, $context);

        if ($content === false) {
            throw new \RuntimeException('file error');
        }

        return $content;
    }

    /**
     * @param null|resource $context
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function fileGetContents(
        string $filename,
        $context = null,
        int $offset = 0,
        ?int $length = null
    ): string {
        if ($context === null) {
            $context = \stream_context_create();
        }

        $filename = $this->validatePolicies($filename, $context, PolicyInterface::MODE_READ);

        if ($length === null) {
            $content = \file_get_contents($filename, false, $context, $offset);
        } else {
            $content = \file_get_contents($filename, false, $context, $offset, $length);
        }

        if ($content === false) {
            throw new \RuntimeException('file_get_contents error');
        }

        return $content;
    }

    /**
     * @param mixed    $data
     * @param resource $context
     */
    public function filePutContents(
        string $filename,
        $data,
        int $flags = 0,
        $context = null
    ): int {
        if ($context === null) {
            $context = \stream_context_create();
        }

        $filename = $this->validatePolicies($filename, $context, PolicyInterface::MODE_WRITE);

        // Remove flag FILE_USE_INCLUDE_PATH
        $flags &= ~\FILE_USE_INCLUDE_PATH;

        $length = \file_put_contents($filename, $data, $flags, $context);

        if ($length === false) {
            throw new \RuntimeException('file_put_contents error');
        }

        return $length;
    }

    /**
     * @param null|resource $context
     *
     * @return resource
     */
    public function fopen(
        string $filename,
        string $mode,
        $context = null
    ) {
        if ($context === null) {
            $context = \stream_context_create();
        }

        $filename = $this->validatePolicies($filename, $context, $this->resolveFopenMode($mode));

        $resource = \fopen($filename, $mode, false, $context);

        if ($resource === false) {
            throw new \RuntimeException('fopen error');
        }

        return $resource;
    }

    protected function resolveFopenMode(string $mode): int
    {
        if ($mode === 'r') {
            return PolicyInterface::MODE_READ;
        }

        if (\in_array($mode, ['r+', 'w+', 'a+', 'x+', 'c+'])) {
            return PolicyInterface::MODE_READ | PolicyInterface::MODE_WRITE;
        }

        if (\in_array($mode, ['w', 'a', 'x', 'c'])) {
            return PolicyInterface::MODE_WRITE;
        }

        throw new \DomainException('Unknown mode');
    }
}
