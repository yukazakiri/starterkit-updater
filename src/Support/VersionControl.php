<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Support;

use Symfony\Component\Process\Process;

final class VersionControl
{
    private string $bin;
    private string $cwd;

    public function __construct(string $bin, string $cwd)
    {
        $this->bin = $bin;
        $this->cwd = $cwd;
    }

    public function run(array $args, bool $throw = true): string
    {
        $cmd = array_merge([$this->bin], $args);
        $process = new Process($cmd, $this->cwd, null, null, 600);
        $process->run();

        if ($throw && !$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput() ?: $process->getOutput());
        }

        return trim($process->getOutput() . $process->getErrorOutput());
    }

    public function hasRemote(string $name): bool
    {
        $out = $this->run(['remote'], throw: false);
        return collect(preg_split('/\R/', $out))->filter()->contains($name);
    }
}
