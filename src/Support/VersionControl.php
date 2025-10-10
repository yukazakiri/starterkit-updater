<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Support;

use LaravelPlus\LaravelUpdater\Contracts\VersionControlInterface;
use RuntimeException;
use Symfony\Component\Process\Process;

final readonly class VersionControl implements VersionControlInterface
{
    public function __construct(
        private string $bin,
        private string $cwd
    ) {}

    /**
     * Execute a git command with the given arguments.
     *
     * @param  array<string>  $args  The git command arguments
     * @param  bool  $throw  Whether to throw an exception on failure
     * @return string The command output
     *
     * @throws RuntimeException When the command fails and $throw is true
     */
    public function run(array $args, bool $throw = true): string
    {
        $cmd = array_merge([$this->bin], $args);
        $process = new Process($cmd, $this->cwd, null, null, 600);
        $process->run();

        if ($throw && ! $process->isSuccessful()) {
            throw new RuntimeException(in_array($process->getErrorOutput(), ['', '0'], true) ? $process->getOutput() : $process->getErrorOutput());
        }

        return mb_trim($process->getOutput().$process->getErrorOutput());
    }

    /**
     * Check if a remote exists.
     *
     * @param  string  $name  The remote name
     * @return bool True if the remote exists
     */
    public function hasRemote(string $name): bool
    {
        $out = $this->run(['remote'], throw: false);

        return collect(preg_split('/\R/', $out))->filter()->contains($name);
    }

    /**
     * Get the current working directory.
     */
    public function getWorkingDirectory(): string
    {
        return $this->cwd;
    }

    /**
     * Get the git binary path.
     */
    public function getGitBinary(): string
    {
        return $this->bin;
    }
}
