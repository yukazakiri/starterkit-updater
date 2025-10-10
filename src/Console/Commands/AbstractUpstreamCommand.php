<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use LaravelPlus\LaravelUpdater\Contracts\UpstreamCommandInterface;
use LaravelPlus\LaravelUpdater\Contracts\VersionControlInterface;
use LaravelPlus\LaravelUpdater\Support\VersionControl;

abstract class AbstractUpstreamCommand extends Command implements UpstreamCommandInterface
{
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
     * Apply default preset configuration if none is set.
     *
     * @param  array<string, mixed>  $config  The configuration array
     * @return array<string, mixed> The updated configuration
     */
    final public function applyDefaultPreset(array $config): array
    {
        // Check if we already have a specific upstream URL configured
        if (! empty($config['upstream_url']) && $config['upstream_url'] !== 'https://github.com/laravel/vue-starter-kit.git') {
            return $config;
        }

        // Check if we have a default preset configured
        $defaultPreset = $config['default_preset'] ?? null;
        if (! $defaultPreset || ! isset($config['presets'][$defaultPreset])) {
            return $config;
        }

        // Apply the default preset configuration
        $preset = $config['presets'][$defaultPreset];

        // Merge preset configuration with existing config
        $config['upstream_url'] = $preset['upstream_url'];
        $config['upstream_branch'] = $preset['upstream_branch'] ?? $config['upstream_branch'];

        // Merge hooks if they exist in the preset
        if (isset($preset['post_update'])) {
            $config['post_update'] = array_merge($config['post_update'], $preset['post_update']);
        }

        if (isset($preset['pre_update'])) {
            $config['pre_update'] = array_merge($config['pre_update'], $preset['pre_update']);
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
            'Git Binary' => fn (): bool => $this->testGitBinary(),
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
            } catch (Exception $e) {
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
     * Test git binary availability.
     */
    private function testGitBinary(): bool
    {
        $this->versionControl->run(['--version'], throw: false);

        return true;
    }

    /**
     * Test working directory.
     */
    private function testWorkingDirectory(array $config): bool
    {
        $workingDir = (string) ($config['working_dir'] ?? base_path());

        return is_dir($workingDir) && is_writable($workingDir);
    }

    /**
     * Test configuration validity.
     */
    private function testConfiguration(array $config): bool
    {
        $required = ['upstream_url', 'upstream_branch', 'local_branch', 'strategy'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                return false;
            }
        }

        $validStrategies = ['merge', 'rebase'];

        return in_array($config['strategy'], $validStrategies, true);
    }

    /**
     * Test remote connectivity.
     */
    private function testRemoteConnectivity(array $config): bool
    {
        $upstreamUrl = (string) $config['upstream_url'];
        $this->versionControl->run(['ls-remote', '--heads', $upstreamUrl], throw: false);

        return true;
    }

    /**
     * Test branch validation.
     */
    private function testBranchValidation(array $config): bool
    {
        $upstreamUrl = (string) $config['upstream_url'];
        $upstreamBranch = (string) $config['upstream_branch'];
        $output = $this->versionControl->run(['ls-remote', '--heads', $upstreamUrl], throw: false);

        return str_contains($output, "refs/heads/$upstreamBranch");
    }

    /**
     * Test local repository.
     */
    private function testLocalRepository(array $config): bool
    {
        $workingDir = (string) ($config['working_dir'] ?? base_path());

        return is_dir("$workingDir/.git");
    }
}
