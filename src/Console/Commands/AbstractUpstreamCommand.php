<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Console\Commands;

use Illuminate\Console\Command;
use LaravelPlus\LaravelUpdater\Contracts\UpstreamCommandInterface;
use LaravelPlus\LaravelUpdater\Contracts\VersionControlInterface;
use LaravelPlus\LaravelUpdater\Support\VersionControl;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

abstract class AbstractUpstreamCommand extends Command implements UpstreamCommandInterface
{
    protected const DEFAULT_UPSTREAM_URL = 'https://github.com/laravel/vue-starter-kit.git';

    protected readonly VersionControlInterface $versionControl;

    public function __construct()
    {
        parent::__construct();

        $config = config('upstream', []);
        $this->versionControl = new VersionControl(
            bin: (string) ($config['git_binary'] ?? 'git'),
            cwd: (string) ($config['working_dir'] ?? base_path())
        );
    }

    /**
     * Apply the configured default preset to the configuration.
     *
     * A preset is only allowed to override the upstream URL when the user has
     * not configured a custom one, but its hooks are always merged in so that
     * the preset's install/build steps are actually executed.
     *
     * @param  array<string, mixed>  $config  The configuration array
     * @return array<string, mixed> The updated configuration
     */
    final public function applyDefaultPreset(array $config): array
    {
        $defaultPreset = $config['default_preset'] ?? null;

        if (! is_string($defaultPreset) || ! isset($config['presets'][$defaultPreset])) {
            return $config;
        }

        /** @var array<string, mixed> $preset */
        $preset = $config['presets'][$defaultPreset];

        $configuredUrl = (string) ($config['upstream_url'] ?? '');
        $presetUrl = (string) ($preset['upstream_url'] ?? '');
        $hasCustomUrl = $configuredUrl !== '' && $configuredUrl !== self::DEFAULT_UPSTREAM_URL;

        // A preset only applies when the user has not selected a different
        // upstream repository.
        if ($hasCustomUrl && $configuredUrl !== $presetUrl) {
            return $config;
        }

        $config['upstream_url'] = $presetUrl;
        $config['upstream_branch'] = $preset['upstream_branch'] ?? $config['upstream_branch'] ?? 'main';

        foreach (['pre_update', 'post_update'] as $hookKey) {
            if (! isset($preset[$hookKey]) || ! is_array($preset[$hookKey])) {
                continue;
            }

            $existing = isset($config[$hookKey]) && is_array($config[$hookKey]) ? $config[$hookKey] : [];

            $config[$hookKey] = array_values(array_unique([
                ...$existing,
                ...$preset[$hookKey],
            ]));
        }

        return $config;
    }

    /**
     * Run tests to validate configuration and connectivity.
     *
     * @param  array<string, mixed>  $config  The configuration array
     * @return bool True if all tests pass
     */
    final public function runTests(array $config): bool
    {
        $this->info('🧪 Running configuration tests...');
        $this->newLine();

        $tests = [
            'Git Binary' => $this->testGitBinary(...),
            'Working Directory' => fn (): bool => $this->testWorkingDirectory($config),
            'Configuration' => fn (): bool => $this->testConfiguration($config),
            'Remote Connectivity' => fn (): bool => $this->testRemoteConnectivity($config),
            'Branch Validation' => fn (): bool => $this->testBranchValidation($config),
            'Local Repository' => fn (): bool => $this->testLocalRepository($config),
        ];

        $passed = 0;
        $total = count($tests);

        foreach ($tests as $name => $test) {
            try {
                if ($test()) {
                    $this->line("✅ <info>$name</info>");
                    $passed++;
                } else {
                    $this->line("❌ <error>$name</error>");
                }
            } catch (Throwable $e) {
                $this->line("❌ <error>$name</error> - {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->line("📊 Tests passed: <info>$passed/$total</info>");

        return $passed === $total;
    }

    /**
     * Display the LaravelPlus logo.
     */
    final public function displayLogo(): void
    {
        $this->line('');
        $this->line('<fg=blue>   ═══════════════════════════════════════════════════════════ </>');
        $this->line('<fg=blue>   🚀 Laravel Updater - Keep your projects up to date!        </>');
        $this->line('<fg=blue>   ═══════════════════════════════════════════════════════════ </>');
        $this->line('');
    }

    /**
     * Read a command option as a string, ignoring non-string values.
     */
    final protected function stringOption(string $name, string $default = ''): string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * Read a configuration value as a string, ignoring non-string values.
     *
     * @param  array<string, mixed>  $config
     */
    final protected function stringConfig(array $config, string $key, string $default = ''): string
    {
        $value = $config[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * Execute a list of shell hooks in the configured working directory.
     *
     * @param  array<int, string>  $hooks
     */
    final protected function execHooks(string $key, array $hooks, bool $dry = false): void
    {
        if ($hooks === []) {
            return;
        }

        $this->newLine();
        $this->line("<comment>Running {$key} hooks...</comment>");

        foreach ($hooks as $cmdline) {
            $this->line("<info>$ {$cmdline}</info>");

            if ($dry) {
                continue;
            }

            $process = Process::fromShellCommandline(
                $cmdline,
                (string) config('upstream.working_dir', base_path()),
                null,
                null,
                1800
            );

            $process->setTimeout(1800)->run(function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });

            if (! $process->isSuccessful()) {
                $error = mb_trim($process->getErrorOutput());

                throw new RuntimeException($error !== '' ? $error : "Hook failed: $cmdline");
            }
        }

        $this->newLine();
    }

    /**
     * Test git binary availability.
     */
    private function testGitBinary(): bool
    {
        $output = $this->versionControl->run(['--version'], throw: false);

        return str_contains($output, 'git version');
    }

    /**
     * Test working directory.
     *
     * @param  array<string, mixed>  $config
     */
    private function testWorkingDirectory(array $config): bool
    {
        $workingDir = (string) ($config['working_dir'] ?? base_path());

        return is_dir($workingDir) && is_writable($workingDir);
    }

    /**
     * Test configuration validity.
     *
     * @param  array<string, mixed>  $config
     */
    private function testConfiguration(array $config): bool
    {
        foreach (['upstream_url', 'upstream_branch', 'local_branch', 'strategy'] as $key) {
            if (empty($config[$key])) {
                return false;
            }
        }

        return in_array(mb_strtolower((string) $config['strategy']), ['merge', 'rebase'], true);
    }

    /**
     * Test remote connectivity.
     *
     * @param  array<string, mixed>  $config
     */
    private function testRemoteConnectivity(array $config): bool
    {
        try {
            $this->versionControl->run(['ls-remote', '--heads', (string) $config['upstream_url']]);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Test branch validation.
     *
     * @param  array<string, mixed>  $config
     */
    private function testBranchValidation(array $config): bool
    {
        try {
            $output = $this->versionControl->run([
                'ls-remote',
                '--heads',
                (string) $config['upstream_url'],
                'refs/heads/'.$config['upstream_branch'],
            ]);

            return $output !== '';
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Test local repository.
     *
     * @param  array<string, mixed>  $config
     */
    private function testLocalRepository(array $config): bool
    {
        $workingDir = (string) ($config['working_dir'] ?? base_path());

        if (! is_dir("$workingDir/.git")) {
            return false;
        }

        try {
            $this->versionControl->run(['rev-parse', '--git-dir']);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
