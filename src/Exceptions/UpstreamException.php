<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Exceptions;

use Exception;

final class UpstreamException extends Exception
{
    public static function configurationMissing(string $key): self
    {
        return new self("Required configuration key '$key' is missing.");
    }

    public static function invalidStrategy(string $strategy): self
    {
        return new self("Invalid strategy '$strategy'. Must be 'merge' or 'rebase'.");
    }

    public static function gitCommandFailed(string $command, string $error): self
    {
        return new self("Git command failed: '$command'. Error: $error");
    }

    public static function remoteNotFound(string $remote): self
    {
        return new self("Remote '$remote' not found.");
    }

    public static function branchNotFound(string $branch): self
    {
        return new self("Branch '$branch' not found.");
    }

    public static function workingDirectoryNotWritable(string $directory): self
    {
        return new self("Working directory '$directory' is not writable.");
    }

    public static function notAGitRepository(string $directory): self
    {
        return new self("Directory '$directory' is not a git repository.");
    }
}
