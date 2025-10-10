<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Console\Commands;

use LaravelPlus\LaravelUpdater\Support\VersionControl;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class UpstreamCheckCommand extends Command
{
    protected $signature = 'upstream:check
        {--upstream= : Override upstream URL}
        {--branch= : Override upstream branch}
        {--local= : Override local branch}
        {--remote-name=upstream : Remote name to use}';

    protected $description = 'Check if there are updates available from upstream repository.';

    public function handle(): int
    {
        $cfg = config('upstream');

        // Apply default preset if no specific configuration is set
        $cfg = $this->applyDefaultPreset($cfg);

        $git = new VersionControl(
            bin: (string) $cfg['git_binary'],
            cwd: (string) $cfg['working_dir']
        );

        $remoteName     = (string) $this->option('remote-name');
        $upstreamUrl    = (string) ($this->option('upstream') ?: $cfg['upstream_url']);
        $upstreamBranch = (string) ($this->option('branch')   ?: $cfg['upstream_branch']);
        $localBranch    = (string) ($this->option('local')    ?: $cfg['local_branch']);

        $this->info('🔍 Checking for upstream updates...');
        $this->newLine();

        try {
            // Ensure remote exists
            if (!$git->hasRemote($remoteName)) {
                $this->line("📡 Adding upstream remote: <info>$upstreamUrl</info>");
                $git->run(['remote', 'add', $remoteName, $upstreamUrl]);
            } else {
                // Update remote URL if needed
                $git->run(['remote', 'set-url', $remoteName, $upstreamUrl]);
            }

            // Fetch latest from upstream
            $this->line("📥 Fetching latest from upstream...");
            $git->run(['fetch', $remoteName]);

            // Get current local commit
            $localCommit = $git->run(['rev-parse', $localBranch]);
            $this->line("📍 Current local commit: <info>" . substr($localCommit, 0, 8) . "</info>");

            // Get upstream commit
            $upstreamCommit = $git->run(['rev-parse', "$remoteName/$upstreamBranch"]);
            $this->line("📍 Latest upstream commit: <info>" . substr($upstreamCommit, 0, 8) . "</info>");

            // Check if we're behind
            $isBehind = $this->isBehind($git, $localBranch, "$remoteName/$upstreamBranch");

            if ($isBehind) {
                $this->newLine();
                $this->info('🆕 Updates available!');
                
                // Show commit differences
                $this->showCommitDifferences($git, $localBranch, "$remoteName/$upstreamBranch");
                
                $this->newLine();
                $this->line('💡 To pull the updates, run:');
                $this->line('   <info>php artisan upstream:upgrade</info>');
                $this->line('   <info>php artisan upstream:upgrade --pr</info> (to create a PR)');
                
                return self::SUCCESS;
            } else {
                $this->newLine();
                $this->info('✅ You are up to date! No updates available.');
                return self::SUCCESS;
            }

        } catch (\Throwable $e) {
            $this->error('❌ Error checking for updates: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function isBehind(VersionControl $git, string $localBranch, string $upstreamBranch): bool
    {
        try {
            // Check if local branch is behind upstream
            $result = $git->run(['rev-list', '--count', "$localBranch..$upstreamBranch"], throw: false);
            return (int) trim($result) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function showCommitDifferences(VersionControl $git, string $localBranch, string $upstreamBranch): void
    {
        try {
            $this->line('<comment>📋 Recent commits from upstream:</comment>');
            
            // Get commit log between local and upstream
            $commits = $git->run(['log', '--oneline', '--no-merges', "$localBranch..$upstreamBranch", '--max-count=10'], throw: false);
            
            if (empty(trim($commits))) {
                $this->line('   No commits to show');
                return;
            }

            $commitLines = explode("\n", trim($commits));
            foreach ($commitLines as $commit) {
                if (!empty(trim($commit))) {
                    $this->line("   <info>$commit</info>");
                }
            }

            // Show file changes summary
            $this->newLine();
            $this->line('<comment>📁 Files changed:</comment>');
            
            $files = $git->run(['diff', '--name-status', "$localBranch..$upstreamBranch"], throw: false);
            if (!empty(trim($files))) {
                $fileLines = explode("\n", trim($files));
                $added = 0;
                $modified = 0;
                $deleted = 0;

                foreach ($fileLines as $file) {
                    if (str_starts_with($file, 'A')) $added++;
                    elseif (str_starts_with($file, 'M')) $modified++;
                    elseif (str_starts_with($file, 'D')) $deleted++;
                }

                if ($added > 0) $this->line("   <info>+$added</info> added");
                if ($modified > 0) $this->line("   <comment>~$modified</comment> modified");
                if ($deleted > 0) $this->line("   <error>-$deleted</error> deleted");
            }

        } catch (\Throwable $e) {
            $this->line("   <error>Could not fetch commit details: {$e->getMessage()}</error>");
        }
    }

    private function applyDefaultPreset(array $cfg): array
    {
        // Check if we already have a specific upstream URL configured
        if (!empty($cfg['upstream_url']) && $cfg['upstream_url'] !== 'https://github.com/laravel/vue-starter-kit.git') {
            return $cfg;
        }

        // Check if we have a default preset configured
        $defaultPreset = $cfg['default_preset'] ?? null;
        if (!$defaultPreset || !isset($cfg['presets'][$defaultPreset])) {
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
}
