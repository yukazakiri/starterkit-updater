<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use LaravelPlus\LaravelUpdater\Support\VersionControl;
use Throwable;

final class UpstreamUpgradeCommand extends Command
{
    protected $signature = 'laravelplus:update
        {--pr : Create a pull request instead of direct merge}
        {--dry-run : Show what would happen without executing}
        {--test : Test configuration and connectivity before upgrade}
        {--no-pre  : Skip pre_update hooks}
        {--no-post : Skip post_update hooks}
        {--strategy= : Override strategy (merge|rebase)}
        {--upstream= : Override upstream URL}
        {--branch= : Override upstream branch}
        {--local= : Override local branch}
        {--remote-name=upstream : Remote name to use}
        {--pr-branch= : Override PR branch name}
        {--pr-title= : Override PR title}
        {--pr-body= : Override PR body}';

    protected $description = 'Upgrade your project with the latest changes from upstream repository.';

    public function handle(): int
    {
        $this->displayLogo();

        $cfg = config('upstream');

        // Apply default preset if no specific configuration is set
        $cfg = $this->applyDefaultPreset($cfg);

        // Run tests if requested
        if ($this->option('test')) {
            if (! $this->runTests($cfg)) {
                $this->error('❌ Tests failed. Aborting upgrade.');

                return self::FAILURE;
            }
            $this->newLine();
        }

        // Check if updates are available first
        if (! $this->option('dry-run')) {
            $this->info('🔍 Checking for available updates...');
            if (! $this->hasUpdates($cfg)) {
                $this->info('✅ You are already up to date! No updates to apply.');

                return self::SUCCESS;
            }
            $this->newLine();
        }

        // Route to appropriate command
        if ($this->option('pr')) {
            return $this->createPullRequest();
        }

        return $this->directUpgrade();

    }

    private function displayLogo(): void
    {
        $this->line('');
        $this->line('<fg=blue>   ═══════════════════════════════════════════════════════════ </>');
        $this->line('<fg=blue>   🚀 Laravel Updater - Keep your projects up to date!        </>');
        $this->line('<fg=blue>   ═══════════════════════════════════════════════════════════ </>');
        $this->line('');
    }

    private function hasUpdates(array $cfg): bool
    {
        $git = new VersionControl(
            bin: (string) $cfg['git_binary'],
            cwd: (string) $cfg['working_dir']
        );

        $remoteName = (string) $this->option('remote-name');
        $upstreamBranch = (string) ($this->option('branch') ?: $cfg['upstream_branch']);
        $localBranch = (string) ($this->option('local') ?: $cfg['local_branch']);

        try {
            // Ensure remote exists and fetch latest
            if (! $git->hasRemote($remoteName)) {
                $upstreamUrl = (string) ($this->option('upstream') ?: $cfg['upstream_url']);
                $git->run(['remote', 'add', $remoteName, $upstreamUrl]);
            }

            $git->run(['fetch', $remoteName]);

            // Check if we're behind
            $result = $git->run(['rev-list', '--count', "$localBranch..$remoteName/$upstreamBranch"], throw: false);

            return (int) mb_trim($result) > 0;
        } catch (Throwable) {
            return true; // Assume updates available if we can't check
        }
    }

    private function createPullRequest(): int
    {
        $this->info('🚀 Creating pull request with upstream updates...');
        // Call the PR command with the same options
        $prCommand = new UpstreamPrCommand();
        // Transfer options to PR command
        $options = [
            'dry-run' => $this->option('dry-run'),
            'test' => false, // Already tested above
            'no-pre' => $this->option('no-pre'),
            'no-post' => $this->option('no-post'),
            'strategy' => $this->option('strategy'),
            'upstream' => $this->option('upstream'),
            'branch' => $this->option('branch'),
            'local' => $this->option('local'),
            'remote-name' => $this->option('remote-name'),
            'pr-branch' => $this->option('pr-branch'),
            'pr-title' => $this->option('pr-title'),
            'pr-body' => $this->option('pr-body'),
        ];
        // Set options on the PR command
        foreach ($options as $key => $value) {
            if ($value !== null) {
                $prCommand->setOption($key, $value);
            }
        }

        return $prCommand->handle();
    }

    private function directUpgrade(): int
    {
        $this->info('🚀 Upgrading with upstream updates...');
        // Call the sync command with the same options
        $syncCommand = new UpstreamSyncCommand();
        // Transfer options to sync command
        $options = [
            'dry-run' => $this->option('dry-run'),
            'test' => false, // Already tested above
            'no-pre' => $this->option('no-pre'),
            'no-post' => $this->option('no-post'),
            'strategy' => $this->option('strategy'),
            'upstream' => $this->option('upstream'),
            'branch' => $this->option('branch'),
            'local' => $this->option('local'),
            'remote-name' => $this->option('remote-name'),
        ];
        // Set options on the sync command
        foreach ($options as $key => $value) {
            if ($value !== null) {
                $syncCommand->setOption($key, $value);
            }
        }

        return $syncCommand->handle();
    }

    private function applyDefaultPreset(array $cfg): array
    {
        // Check if we already have a specific upstream URL configured
        if (! empty($cfg['upstream_url']) && $cfg['upstream_url'] !== 'https://github.com/laravel/vue-starter-kit.git') {
            return $cfg;
        }

        // Check if we have a default preset configured
        $defaultPreset = $cfg['default_preset'] ?? null;
        if (! $defaultPreset || ! isset($cfg['presets'][$defaultPreset])) {
            return $cfg;
        }

        // Apply the default preset configuration
        $preset = $cfg['presets'][$defaultPreset];

        // Merge preset configuration with existing config
        $cfg['upstream_url'] = $preset['upstream_url'];
        $cfg['upstream_branch'] = $preset['upstream_branch'] ?? $cfg['upstream_branch'];

        // Merge hooks if they exist in the preset
        if (isset($preset['post_update'])) {
            $cfg['post_update'] = array_merge($cfg['post_update'], $preset['post_update']);
        }

        if (isset($preset['pre_update'])) {
            $cfg['pre_update'] = array_merge($cfg['pre_update'], $preset['pre_update']);
        }

        return $cfg;
    }

    private function runTests(array $cfg): bool
    {
        $this->info('🔍 Testing upstream sync configuration...');
        $this->newLine();

        $tests = [
            'Git Binary' => fn (): bool => $this->testGitBinary($cfg),
            'Working Directory' => fn (): bool => $this->testWorkingDirectory($cfg),
            'Configuration' => fn (): bool => $this->testConfiguration($cfg),
            'Remote Connectivity' => fn (): bool => $this->testRemoteConnectivity($cfg),
            'Branch Validation' => fn (): bool => $this->testBranchValidation($cfg),
            'Local Repository' => fn (): bool => $this->testLocalRepository($cfg),
        ];

        $allPassed = true;
        foreach ($tests as $testName => $testFn) {
            $this->line("<comment>Testing {$testName}...</comment>");
            try {
                $result = $testFn();
                if ($result) {
                    $this->line("  ✅ <info>{$testName} - OK</info>");
                } else {
                    $this->line("  ❌ <error>{$testName} - FAILED</error>");
                    $allPassed = false;
                }
            } catch (Throwable $e) {
                $this->line("  ❌ <error>{$testName} - ERROR: {$e->getMessage()}</error>");
                $allPassed = false;
            }
            $this->newLine();
        }

        if ($allPassed) {
            $this->info('🎉 All tests passed! Proceeding with upgrade...');
        } else {
            $this->error('❌ Some tests failed. Please fix the issues before upgrading.');
        }

        return $allPassed;
    }

    private function testGitBinary(array $cfg): bool
    {
        $git = new VersionControl((string) $cfg['git_binary'], (string) $cfg['working_dir']);
        $version = $git->run(['--version'], throw: false);

        return $version !== '' && $version !== '0' && str_contains($version, 'git version');
    }

    private function testWorkingDirectory(array $cfg): bool
    {
        $workingDir = (string) $cfg['working_dir'];

        return is_dir($workingDir) && is_writable($workingDir);
    }

    private function testConfiguration(array $cfg): bool
    {
        $required = ['upstream_url', 'upstream_branch', 'local_branch', 'strategy'];
        foreach ($required as $key) {
            if (empty($cfg[$key])) {
                return false;
            }
        }

        $strategy = Str::lower((string) $cfg['strategy']);

        return in_array($strategy, ['merge', 'rebase'], true);
    }

    private function testRemoteConnectivity(array $cfg): bool
    {
        $upstreamUrl = (string) ($this->option('upstream') ?: $cfg['upstream_url']);

        $git = new VersionControl((string) $cfg['git_binary'], (string) $cfg['working_dir']);

        // Test if we can reach the remote URL
        try {
            $git->run(['ls-remote', $upstreamUrl], throw: true);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function testBranchValidation(array $cfg): bool
    {
        $upstreamUrl = (string) ($this->option('upstream') ?: $cfg['upstream_url']);
        $upstreamBranch = (string) ($this->option('branch') ?: $cfg['upstream_branch']);

        $git = new VersionControl((string) $cfg['git_binary'], (string) $cfg['working_dir']);

        try {
            $refs = $git->run(['ls-remote', '--heads', $upstreamUrl, $upstreamBranch], throw: false);

            return ! in_array(mb_trim($refs), ['', '0'], true);
        } catch (Throwable) {
            return false;
        }
    }

    private function testLocalRepository(array $cfg): bool
    {
        $git = new VersionControl((string) $cfg['git_binary'], (string) $cfg['working_dir']);

        try {
            // Check if this is a git repository
            $git->run(['rev-parse', '--git-dir'], throw: true);

            // Check if we have a clean working directory
            $status = $git->run(['status', '--porcelain'], throw: false);
            $isClean = in_array(mb_trim($status), ['', '0'], true);

            if (! $isClean) {
                $this->line('  ⚠️  <comment>Working directory has uncommitted changes</comment>');
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
