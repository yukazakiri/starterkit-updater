<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Console\Commands;

use LaravelPlus\LaravelUpdater\Support\VersionControl;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class UpstreamSyncCommand extends Command
{
    protected $signature = 'upstream:sync
        {--dry-run : Show what would happen without executing}
        {--test : Test configuration and connectivity before sync}
        {--no-pre  : Skip pre_update hooks}
        {--no-post : Skip post_update hooks}
        {--strategy= : Override strategy (merge|rebase)}
        {--upstream= : Override upstream URL}
        {--branch= : Override upstream branch}
        {--local= : Override local branch}
        {--remote-name=upstream : Remote name to use}';

    protected $description = 'Sync the project with an upstream repository (merge or rebase) with pre/post hooks.';

    public function handle(): int
    {
        $cfg = config('upstream');

        // Run tests if requested
        if ($this->option('test')) {
            if (!$this->runTests($cfg)) {
                $this->error('❌ Tests failed. Aborting sync.');
                return self::FAILURE;
            }
            $this->newLine();
        }

        $git = new VersionControl(
            bin: (string) $cfg['git_binary'],
            cwd: (string) $cfg['working_dir']
        );

        $remoteName     = (string) $this->option('remote-name');
        $upstreamUrl    = (string) ($this->option('upstream') ?: $cfg['upstream_url']);
        $upstreamBranch = (string) ($this->option('branch')   ?: $cfg['upstream_branch']);
        $localBranch    = (string) ($this->option('local')    ?: $cfg['local_branch']);
        $strategy       = Str::lower((string) ($this->option('strategy') ?: $cfg['strategy']));
        $allowUnrelated = (bool) $cfg['allow_unrelated_histories'];
        $dry            = (bool) $this->option('dry-run');

        if (!in_array($strategy, ['merge', 'rebase'], true)) {
            $this->error('Invalid strategy. Use merge|rebase.');
            return self::FAILURE;
        }

        $run = function (string $label, array $cmd) use ($git, $dry): void {
            $this->line("<info>$ ".$this->pretty($cmd)."</info>  <comment>($label)</comment>");
            if (!$dry) {
                // Remove the git binary from the command since VersionControl::run() adds it
                $args = array_slice($cmd, 1);
                $out = $git->run($args);
                if ($out !== '') {
                    $this->line($out);
                }
            }
        };

        // Ensure remote exists
        if (!$git->hasRemote($remoteName)) {
            $run('add remote', [$cfg['git_binary'], 'remote', 'add', $remoteName, $upstreamUrl]);
        } else {
            // keep URL up to date
            $run('set-url', [$cfg['git_binary'], 'remote', 'set-url', $remoteName, $upstreamUrl]);
        }

        // Pre hooks
        if (!$this->option('no-pre')) {
            $this->execHooks('pre_update', $dry);
        }

        // Fetch upstream
        $run('fetch upstream', [$cfg['git_binary'], 'fetch', $remoteName]);

        // Ensure local branch exists (create tracking if needed)
        try {
            $git->run(['rev-parse', '--verify', $localBranch]);
            $run('checkout local', [$cfg['git_binary'], 'checkout', $localBranch]);
        } catch (\Throwable) {
            $run('create local branch', [$cfg['git_binary'], 'checkout', '-b', $localBranch]);
        }

        // Merge or rebase
        if ($strategy === 'merge') {
            $args = [$cfg['git_binary'], 'merge', "$remoteName/$upstreamBranch", '--no-edit'];
            if ($allowUnrelated) {
                $args[] = '--allow-unrelated-histories';
            }
            $run('merge', $args);
        } else {
            $run('rebase', [$cfg['git_binary'], 'rebase', "$remoteName/$upstreamBranch"]);
        }

        // Commit (only if there are changes to commit)
        try {
            $status = $git->run(['status', '--porcelain'], throw: false);
            if (!empty(trim($status))) {
                $run('commit (ensure clean msg)', [$cfg['git_binary'], 'commit', '--no-edit', '-m', $cfg['commit_message']]);
            }
        } catch (\Throwable) {
            // Ignore commit errors - might be nothing to commit
        }

        // Push (optional: only if origin exists)
        $hasOrigin = false;
        try {
            $hasOrigin = (new VersionControl($cfg['git_binary'], $cfg['working_dir']))->hasRemote('origin');
        } catch (\Throwable) { /* ignore */ }

        if ($hasOrigin) {
            $run('push origin', [$cfg['git_binary'], 'push', 'origin', $localBranch]);
        }

        // Post hooks
        if (!$this->option('no-post')) {
            $this->execHooks('post_update', $dry);
        }

        $this->info($dry ? 'Dry run complete.' : 'Upstream sync complete.');
        return self::SUCCESS;
    }

    private function execHooks(string $key, bool $dry): void
    {
        $hooks = (array) config("upstream.$key", []);
        if ($hooks === []) {
            return;
        }

        $this->newLine();
        $this->line("<comment>Running {$key} hooks...</comment>");
        foreach ($hooks as $cmdline) {
            $this->line("<info>$ {$cmdline}</info>");
            if (!$dry) {
                $process = \Symfony\Component\Process\Process::fromShellCommandline(
                    $cmdline,
                    (string) config('upstream.working_dir'),
                    null,
                    null,
                    1800
                );
                $process->setTimeout(1800)->run(function ($type, $buffer) {
                    echo $buffer;
                });

                if (!$process->isSuccessful()) {
                    throw new \RuntimeException($process->getErrorOutput() ?: $process->getOutput());
                }
            }
        }
        $this->newLine();
    }

    private function pretty(array $cmd): string
    {
        return collect($cmd)->map(fn ($p) => str_contains($p, ' ') ? escapeshellarg($p) : $p)->implode(' ');
    }

    private function runTests(array $cfg): bool
    {
        $this->info('🔍 Testing upstream sync configuration...');
        $this->newLine();

        $tests = [
            'Git Binary' => fn() => $this->testGitBinary($cfg),
            'Working Directory' => fn() => $this->testWorkingDirectory($cfg),
            'Configuration' => fn() => $this->testConfiguration($cfg),
            'Remote Connectivity' => fn() => $this->testRemoteConnectivity($cfg),
            'Branch Validation' => fn() => $this->testBranchValidation($cfg),
            'Local Repository' => fn() => $this->testLocalRepository($cfg),
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
            } catch (\Throwable $e) {
                $this->line("  ❌ <error>{$testName} - ERROR: {$e->getMessage()}</error>");
                $allPassed = false;
            }
            $this->newLine();
        }

        if ($allPassed) {
            $this->info('🎉 All tests passed! Proceeding with sync...');
        } else {
            $this->error('❌ Some tests failed. Please fix the issues before running sync.');
        }

        return $allPassed;
    }

    private function testGitBinary(array $cfg): bool
    {
        $git = new VersionControl((string) $cfg['git_binary'], (string) $cfg['working_dir']);
        $version = $git->run(['--version'], throw: false);
        return !empty($version) && str_contains($version, 'git version');
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
        } catch (\Throwable) {
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
            return !empty(trim($refs));
        } catch (\Throwable) {
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
            $isClean = empty(trim($status));
            
            if (!$isClean) {
                $this->line("  ⚠️  <comment>Working directory has uncommitted changes</comment>");
            }
            
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
