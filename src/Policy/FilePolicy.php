<?php

declare(strict_types=1);

namespace Aubes\LPFile\Policy;

class FilePolicy implements PolicyInterface
{
    protected string $baseDirectory;
    protected ?string $realBaseDirectory = null;
    protected array $extensions;
    protected bool $allowParentDirectory;

    /**
     * @param array|string $extensions
     */
    public function __construct(string $baseDirectory, $extensions, bool $allowParentDirectory = false)
    {
        if (empty($baseDirectory)) {
            throw new \InvalidArgumentException('Base directory is empty');
        }

        $this->baseDirectory = $baseDirectory;
        $this->extensions = (array) $extensions;
        $this->allowParentDirectory = $allowParentDirectory;
    }

    public function getProtocole(): string
    {
        return 'file';
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
        if ($this->realBaseDirectory === null) {
            $this->realBaseDirectory = $this->validateBaseDirectory();
        }

        if (!$this->validateParentDirectory($filename)) {
            throw new PolicyException('Parent directory is not allowed');
        }

        if (\str_starts_with($filename, 'file://')) {
            $filename = \mb_substr($filename, 7);
        }

        $pathInfo = \pathinfo($filename);

        if (!$this->validateExtensions($pathInfo['extension'] ?? null)) {
            throw new PolicyException('File extensions not allowed');
        }

        $realPath = \realpath($filename);

        if ($realPath === false) {
            throw new PolicyException('File does not exist');
        }

        if (!\str_starts_with($realPath, $this->realBaseDirectory)) {
            throw new PolicyException('File is outside the base directory');
        }

        if (\is_dir($realPath)) {
            throw new PolicyException('File is a directory');
        }

        return 'file://' . $realPath;
    }

    protected function validateBaseDirectory(): string
    {
        $realBaseDirectory = \realpath($this->baseDirectory);

        if ($realBaseDirectory === false) {
            throw new PolicyException('Base directory is invalid');
        }

        if (!\is_dir($realBaseDirectory)) {
            throw new PolicyException('Base directory must be a directory');
        }

        return $realBaseDirectory;
    }

    protected function validateParentDirectory(string $filename): bool
    {
        return $this->allowParentDirectory || (!\str_contains($filename, '../') && !\str_contains($filename, '..\\'));
    }

    protected function validateExtensions(?string $extension): bool
    {
        if ($extension === null) {
            return false;
        }

        return \in_array($extension, $this->extensions);
    }
}
