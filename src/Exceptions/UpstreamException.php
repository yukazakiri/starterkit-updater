<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Exceptions;

use RuntimeException;
use Throwable;

final class UpstreamException extends RuntimeException
{
    public static function configurationMissing(string $key): self
    {
        return new self("Required configuration key '$key' is missing or empty.");
    }

    public static function invalidStrategy(string $strategy): self
    {
        return new self("Invalid strategy '$strategy'. Must be 'merge' or 'rebase'.");
    }

    public static function gitCommandFailed(string $command, string $error = '', ?Throwable $previous = null): self
    {
        $message = "Git command failed: $command";

        if ($error !== '') {
            $message .= " ($error)";
        }

        return new self($message, 0, $previous);
    }

    public static function remoteNotFound(string $remote): self
    {
        return new self("Git remote '$remote' not found.");
    }

    public static function branchNotFound(string $branch): self
    {
        return new self("Git branch '$branch' not found.");
    }

    public static function workingDirectoryNotWritable(string $directory): self
    {
        return new self("Working directory '$directory' is not writable.");
    }

    public static function notAGitRepository(string $directory): self
    {
        return new self("Directory '$directory' is not a git repository.");
    }

    public static function hookFailed(string $command, ?Throwable $previous = null): self
    {
        return new self("Pre/post update hook failed: $command", 0, $previous);
    }
}
