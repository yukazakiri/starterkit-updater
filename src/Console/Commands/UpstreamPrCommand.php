<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use LaravelPlus\LaravelUpdater\Support\VersionControl;
use RuntimeException;
use Throwable;

final class UpstreamPrCommand extends Command
{
    protected $signature = 'laravelplus:pr
        {--dry-run : Show what would happen without executing}
        {--test : Test configuration and connectivity before creating PR}
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

    protected $description = 'Create a pull request with upstream updates instead of direct merge.';

    private readonly VersionControl $versionControl;

    public function __construct()
    {
        parent::__construct();

        $config = config('upstream', []);
        $this->versionControl = new VersionControl(
            bin: (string) ($config['git_binary'] ?? 'git'),
            cwd: (string) ($config['working_dir'] ?? base_path())
        );
    }

    public function handle(): int
    {
        $this->displayLogo();

        $cfg = config('upstream');

        // Apply default preset if no specific configuration is set
        $cfg = $this->applyDefaultPreset($cfg);

        // Run tests if requested
        if ($this->option('test')) {
            if (! $this->runTests($cfg)) {
                $this->error('❌ Tests failed. Aborting PR creation.');

                return self::FAILURE;
            }
            $this->newLine();
        }

        $git = new VersionControl(
            bin: (string) $cfg['git_binary'],
            cwd: (string) $cfg['working_dir']
        );

        $remoteName = (string) $this->option('remote-name');
        $upstreamUrl = (string) ($this->option('upstream') ?: $cfg['upstream_url']);
        $upstreamBranch = (string) ($this->option('branch') ?: $cfg['upstream_branch']);
        $localBranch = (string) ($this->option('local') ?: $cfg['local_branch']);
        $strategy = Str::lower((string) ($this->option('strategy') ?: $cfg['strategy']));

        // Always use the current branch as local branch (could be master, main, dev, etc.)
        $currentBranch = mb_trim($this->versionControl->run(['rev-parse', '--abbrev-ref', 'HEAD']));
        if ($localBranch !== $currentBranch) {
            $this->line("📍 Using current branch: <info>$currentBranch</info> (configured: $localBranch)");
            $localBranch = $currentBranch;
        }

        // Use configured upstream branch (defaults to 'main' for Laravel starter kits)
        $this->line("📍 Using upstream branch: <info>$upstreamBranch</info>");
        $allowUnrelated = (bool) $cfg['allow_unrelated_histories'];
        $dry = (bool) $this->option('dry-run');

        // Generate PR branch name
        $prBranch = $this->generatePrBranchName($cfg);
        $prTitle = $this->option('pr-title') ?: $cfg['pr_title'];
        $prBody = $this->option('pr-body') ?: $cfg['pr_body'];

        if (! in_array($strategy, ['merge', 'rebase'], true)) {
            $this->error('Invalid strategy. Use merge|rebase.');

            return self::FAILURE;
        }

        $run = function (string $label, array $cmd) use ($git, $dry): void {
            $this->line('<info>$ '.$this->pretty($cmd)."</info>  <comment>($label)</comment>");
            if (! $dry) {
                // Remove the git binary from the command since VersionControl::run() adds it
                $args = array_slice($cmd, 1);
                $out = $git->run($args);
                if ($out !== '') {
                    $this->line($out);
                }
            }
        };

        $this->info('🚀 Creating pull request for upstream sync...');
        
        // Show repository information
        $this->showRepositoryInfo($git, $upstreamUrl);
        
        $this->line("📝 PR Branch: <info>$prBranch</info>");
        $this->line("🎯 Target Branch: <info>$localBranch</info>");
        $this->line("📋 PR Title: <info>$prTitle</info>");
        
        if ($dry) {
            $this->newLine();
            $this->line('🔍 <comment>What will happen:</comment>');
            $this->line('   1. 📡 Ensure upstream remote is configured');
            $this->line('   2. 📥 Fetch latest changes from upstream');
            $this->line('   3. 🌿 Create new PR branch from current branch');
            $this->line('   4. 🔀 Merge upstream changes into PR branch');
            $this->line('   5. 📤 Push PR branch to origin');
            $this->line('   6. 🎯 Create GitHub pull request');
            $this->newLine();
        }
        
        $this->newLine();

        // Ensure remote exists
        if (! $git->hasRemote($remoteName)) {
            $run('add remote', [$cfg['git_binary'], 'remote', 'add', $remoteName, $upstreamUrl]);
        } else {
            // keep URL up to date
            $run('set-url', [$cfg['git_binary'], 'remote', 'set-url', $remoteName, $upstreamUrl]);
        }

        // Pre hooks
        if (! $this->option('no-pre')) {
            $this->execHooks('pre_update', $dry);
        }

        // Fetch upstream
        $run('fetch upstream', [$cfg['git_binary'], 'fetch', $remoteName]);

        // Create and checkout PR branch
        $run('create PR branch', [$cfg['git_binary'], 'checkout', '-b', $prBranch]);

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
            if (! in_array(mb_trim($status), ['', '0'], true)) {
                $run('commit (ensure clean msg)', [$cfg['git_binary'], 'commit', '--no-edit', '-m', $cfg['commit_message']]);
            }
        } catch (Throwable) {
            // Ignore commit errors - might be nothing to commit
        }

        // Push PR branch
        $run('push PR branch', [$cfg['git_binary'], 'push', 'origin', $prBranch]);

        // Post hooks
        if (! $this->option('no-post')) {
            $this->execHooks('post_update', $dry);
        }

        // Create GitHub PR
        if (! $dry) {
            $this->createGitHubPr($cfg, $prBranch, $localBranch, $prTitle, $prBody, $upstreamUrl);
        }

        if ($dry) {
            $this->newLine();
            $this->info('✅ Dry run complete - no changes were made.');
            $this->line('💡 Run without <comment>--dry-run</comment> to execute the pull request creation.');
        } else {
            $this->info('✅ Pull request created successfully!');
        }

        return self::SUCCESS;
    }

    private function displayLogo(): void
    {
        $this->line('');
        $this->line('<fg=blue>   ═══════════════════════════════════════════════════════════ </>');
        $this->line('<fg=blue>   🚀 Laravel Updater - Keep your projects up to date!        </>');
        $this->line('<fg=blue>   ═══════════════════════════════════════════════════════════ </>');
        $this->line('');
    }

    private function showRepositoryInfo(VersionControl $git, string $upstreamUrl): void
    {
        try {
            // Get local repository URL
            $localUrl = $git->run(['remote', 'get-url', 'origin'], throw: false);
            if (!$localUrl) {
                $localUrl = 'Local repository (no origin remote)';
            }
            
            $this->line('🏠 <comment>Repository Information:</comment>');
            $this->line("   Local: <info>$localUrl</info>");
            $this->line("   Upstream: <info>$upstreamUrl</info>");
            $this->newLine();
            
        } catch (Throwable) {
            // If we can't get repository info, just continue silently
        }
    }

    private function generatePrBranchName(array $cfg): string
    {
        $customBranch = $this->option('pr-branch');
        if ($customBranch) {
            return $customBranch;
        }

        $prefix = $cfg['pr_branch_prefix'] ?? 'upstream-sync-';
        $timestamp = now()->format('Y-m-d-H-i-s');

        return $prefix.$timestamp;
    }

    private function createGitHubPr(array $cfg, string $prBranch, string $targetBranch, string $title, string $body, string $upstreamUrl): void
    {
        $token = $cfg['github_token'];
        $owner = $cfg['github_owner'];
        $repo = $cfg['github_repo'];

        if (! $token || ! $owner || ! $repo) {
            $this->warn('⚠️  GitHub credentials not configured. Please set GITHUB_TOKEN, GITHUB_OWNER, and GITHUB_REPO in your .env file.');
            $this->line("🔗 You can manually create a PR at: https://github.com/$owner/$repo/compare/$targetBranch...$prBranch");

            return;
        }

        $this->line('📤 Creating GitHub pull request...');

        $body = str_replace('{upstream_url}', $upstreamUrl, $body);

        $data = [
            'title' => $title,
            'body' => $body,
            'head' => $prBranch,
            'base' => $targetBranch,
        ];

        $response = $this->makeGitHubRequest($token, $owner, $repo, $data);

        if ($response && isset($response['html_url'])) {
            $this->info("✅ Pull request created: {$response['html_url']}");
        } else {
            $this->error('❌ Failed to create pull request.');
            $this->line("🔗 You can manually create a PR at: https://github.com/$owner/$repo/compare/$targetBranch...$prBranch");
        }
    }

    private function makeGitHubRequest(string $token, string $owner, string $repo, array $data): ?array
    {
        $url = "https://api.github.com/repos/$owner/$repo/pulls";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token '.$token,
            'Accept: application/vnd.github.v3+json',
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Laravel-Updater/1.0');

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 201) {
            return json_decode($response, true);
        }

        $this->error("GitHub API error: HTTP $httpCode");
        if ($response) {
            $error = json_decode($response, true);
            if (isset($error['message'])) {
                $this->error("Error: {$error['message']}");
            }
        }

        return null;
    }

    /**
     * Apply default preset configuration if none is set.
     */
    private function applyDefaultPreset(array $config): array
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
            if (! $dry) {
                $process = \Symfony\Component\Process\Process::fromShellCommandline(
                    $cmdline,
                    (string) config('upstream.working_dir'),
                    null,
                    null,
                    1800
                );
                $process->setTimeout(1800)->run(function ($type, $buffer): void {
                    echo $buffer;
                });

                if (! $process->isSuccessful()) {
                    throw new RuntimeException(in_array($process->getErrorOutput(), ['', '0'], true) ? $process->getOutput() : $process->getErrorOutput());
                }
            }
        }
        $this->newLine();
    }

    private function pretty(array $cmd): string
    {
        return collect($cmd)->map(fn ($p) => str_contains((string) $p, ' ') ? escapeshellarg((string) $p) : $p)->implode(' ');
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
            $this->info('🎉 All tests passed! Proceeding with PR creation...');
        } else {
            $this->error('❌ Some tests failed. Please fix the issues before creating PR.');
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
