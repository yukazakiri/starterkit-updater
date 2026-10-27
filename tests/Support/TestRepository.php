<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Tests\Support;

use RuntimeException;
use Symfony\Component\Process\Process;

final class TestRepository
{
    /** @var array<int, string> */
    private static array $paths = [];

    private function __construct(public readonly string $path) {}

    public static function create(?string $path = null): self
    {
        $path ??= sys_get_temp_dir().'/laravel-updater-test-'.bin2hex(random_bytes(6));

        if (! is_dir($path) && ! mkdir($path, 0777, true) && ! is_dir($path)) {
            throw new RuntimeException("Could not create test repository at $path");
        }

        self::$paths[] = $path;

        $repository = new self($path);
        $repository->git(['init', '-b', 'main']);
        $repository->git(['config', 'user.email', 'tests@example.com']);
        $repository->git(['config', 'user.name', 'Updater Tests']);
        $repository->git(['config', 'commit.gpgsign', 'false']);

        return $repository;
    }

    public static function createBare(?string $path = null): self
    {
        $path ??= sys_get_temp_dir().'/laravel-updater-bare-'.bin2hex(random_bytes(6));

        if (! is_dir($path) && ! mkdir($path, 0777, true) && ! is_dir($path)) {
            throw new RuntimeException("Could not create bare test repository at $path");
        }

        self::$paths[] = $path;

        $repository = new self($path);
        $repository->git(['init', '--bare']);

        return $repository;
    }

    public static function cloneFrom(self $source, ?string $branch = 'main'): self
    {
        $path = sys_get_temp_dir().'/laravel-updater-test-'.bin2hex(random_bytes(6));
        self::$paths[] = $path;

        $args = ['git', 'clone'];

        if ($branch !== null) {
            $args[] = '--branch';
            $args[] = $branch;
        }

        $args[] = $source->path;
        $args[] = $path;

        $process = new Process($args, null, null, null, 120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(mb_trim($process->getErrorOutput()));
        }

        $repository = new self($path);
        $repository->git(['config', 'user.email', 'tests@example.com']);
        $repository->git(['config', 'user.name', 'Updater Tests']);
        $repository->git(['config', 'commit.gpgsign', 'false']);

        return $repository;
    }

    public static function cleanup(): void
    {
        foreach (self::$paths as $path) {
            self::deleteDirectory($path);
        }

        self::$paths = [];
    }

    /**
     * @param  array<int, string>  $args
     */
    public function git(array $args): string
    {
        $process = new Process(['git', ...$args], $this->path, null, null, 120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(mb_trim($process->getErrorOutput()) ?: mb_trim($process->getOutput()));
        }

        return mb_trim($process->getOutput());
    }

    public function write(string $file, string $contents): void
    {
        $target = $this->path.'/'.mb_ltrim($file, '/');
        $directory = dirname($target);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException("Could not create directory $directory");
        }

        file_put_contents($target, $contents);
    }

    public function commit(string $message = 'update'): void
    {
        $this->git(['add', '--all']);
        $this->git(['commit', '-m', $message]);
    }

    public function checkout(string $branch): void
    {
        $this->git(['checkout', $branch]);
    }

    public function addRemote(string $name, string $url): void
    {
        $this->git(['remote', 'add', $name, $url]);
    }

    public function setRemote(string $name, string $url): void
    {
        $this->git(['remote', 'set-url', $name, $url]);
    }

    public function createBranch(string $branch): void
    {
        $this->git(['checkout', '-b', $branch]);
    }

    public function push(string $branch = 'main'): void
    {
        $this->git(['push', 'origin', $branch]);
    }

    private static function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $target = $path.'/'.$item;

            if (is_dir($target) && ! is_link($target)) {
                self::deleteDirectory($target);
            } else {
                unlink($target);
            }
        }

        rmdir($path);
    }
}
