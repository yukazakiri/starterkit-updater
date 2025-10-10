<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Contracts;

use RuntimeException;

interface VersionControlInterface
{
    /**
     * Execute a git command with the given arguments.
     *
     * @param  array<string>  $args  The git command arguments
     * @param  bool  $throw  Whether to throw an exception on failure
     * @return string The command output
     *
     * @throws RuntimeException When the command fails and $throw is true
     */
    public function run(array $args, bool $throw = true): string;

    /**
     * Check if a remote exists.
     *
     * @param  string  $name  The remote name
     * @return bool True if the remote exists
     */
    public function hasRemote(string $name): bool;

    /**
     * Get the current working directory.
     */
    public function getWorkingDirectory(): string;

    /**
     * Get the git binary path.
     */
    public function getGitBinary(): string;
}
