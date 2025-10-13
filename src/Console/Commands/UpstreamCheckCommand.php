<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Console\Commands;

use Illuminate\Console\Command;
use LaravelPlus\LaravelUpdater\Support\VersionControl;
use Throwable;

final class UpstreamCheckCommand extends Command
{
    protected $signature = 'laravelplus:check
        {--upstream= : Override upstream URL}
        {--branch= : Override upstream branch}
        {--local= : Override local branch}
        {--remote-name=upstream : Remote name to use}';

    protected $description = 'Check if there are updates available from upstream repository.';

    public function handle(): int
    {
        $this->displayLogo();

        $cfg = config('upstream');

        // Apply default preset if no specific configuration is set
        $cfg = $this->applyDefaultPreset($cfg);

        $git = new VersionControl(
            bin: (string) $cfg['git_binary'],
            cwd: (string) $cfg['working_dir']
        );

        $remoteName = (string) $this->option('remote-name');
        $upstreamUrl = (string) ($this->option('upstream') ?: $cfg['upstream_url']);
        $upstreamBranch = (string) ($this->option('branch') ?: $cfg['upstream_branch']);
        $localBranch = (string) ($this->option('local') ?: $cfg['local_branch']);

        // Always use the current branch as local branch (could be master, main, dev, etc.)
        $currentBranch = mb_trim($git->run(['rev-parse', '--abbrev-ref', 'HEAD']));
        if ($localBranch !== $currentBranch) {
            $this->line("📍 Using current branch: <info>$currentBranch</info> (configured: $localBranch)");
            $localBranch = $currentBranch;
        }

        // Upstream branch is always 'main' for Laravel starter kits
        if ($upstreamBranch !== 'main') {
            $this->line("📍 Using upstream branch: <info>main</info> (configured: $upstreamBranch)");
            $upstreamBranch = 'main';
        }

        $this->info('🔍 Checking for upstream updates...');
        
        // Show repository information
        $this->showRepositoryInfo($git, $upstreamUrl);
        
        $this->newLine();

        try {
            // Ensure remote exists
            if (! $git->hasRemote($remoteName)) {
                $this->line("📡 Adding upstream remote: <info>$upstreamUrl</info>");
                $git->run(['remote', 'add', $remoteName, $upstreamUrl]);
            } else {
                // Update remote URL if needed
                $currentUrl = mb_trim($git->run(['remote', 'get-url', $remoteName]));
                if ($currentUrl !== $upstreamUrl) {
                    $this->line("📡 Updating upstream remote URL: <info>$upstreamUrl</info>");
                    $git->run(['remote', 'set-url', $remoteName, $upstreamUrl]);
                }
            }

            // Fetch latest from upstream
            $this->line('📥 Fetching latest from upstream...');
            $git->run(['fetch', $remoteName]);

            // Get current local commit
            $localCommit = $git->run(['rev-parse', $localBranch]);
            $this->line('📍 Current local commit: <info>'.mb_substr($localCommit, 0, 8).'</info>');

            // Get upstream commit
            $upstreamCommit = $git->run(['rev-parse', "$remoteName/$upstreamBranch"]);
            $this->line('📍 Latest upstream commit: <info>'.mb_substr($upstreamCommit, 0, 8).'</info>');
            
            // Show more detailed commit information
            $this->showCommitDetails($git, $localCommit, $upstreamCommit);

            // Check if we're behind
            $isBehind = $this->isBehind($git, $localBranch, "$remoteName/$upstreamBranch");

            if ($isBehind) {
                $this->newLine();
                $this->info('🆕 Updates available!');

                // Show commit differences
                $this->showCommitDifferences($git, $localBranch, "$remoteName/$upstreamBranch");

                $this->newLine();
                $this->line('💡 To pull the updates, run:');
                $this->line('   <info>php artisan laravelplus:update</info> (creates PR by default)');
                $this->line('   <info>php artisan laravelplus:update --direct</info> (to apply directly)');
                $this->newLine();
                $this->line("🔗 PR will sync from upstream commit: <info>".mb_substr($upstreamCommit, 0, 8)."</info>");

                return self::SUCCESS;
            }
            $this->newLine();
            $this->info('✅ You are up to date! No updates available.');
            $this->line("📋 Last sync was from upstream commit: <info>".mb_substr($upstreamCommit, 0, 8)."</info>");

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error('❌ Error checking for updates: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function displayLogo(): void
    {
        $this->line('');
        $this->line('<fg=blue>   ═══════════════════════════════════════════════════════════ </>');
        $this->line('<fg=blue>   🚀 Laravel Updater - Keep your projects up to date!        </>');
        $this->line('<fg=blue>   ═══════════════════════════════════════════════════════════ </>');
        $this->line('');
    }

    private function isBehind(VersionControl $git, string $localBranch, string $upstreamBranch): bool
    {
        try {
            // Check if local branch is behind upstream
            $result = $git->run(['rev-list', '--count', "$localBranch..$upstreamBranch"], throw: false);

            return (int) mb_trim($result) > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function showCommitDifferences(VersionControl $git, string $localBranch, string $upstreamBranch): void
    {
        try {
            $this->line('<comment>📋 Recent commits from upstream:</comment>');

            // Get commit log between local and upstream
            $commits = $git->run(['log', '--oneline', '--no-merges', "$localBranch..$upstreamBranch", '--max-count=10'], throw: false);

            if (in_array(mb_trim($commits), ['', '0'], true)) {
                $this->line('   No commits to show');

                return;
            }

            $commitLines = explode("\n", mb_trim($commits));
            foreach ($commitLines as $commit) {
                if (! in_array(mb_trim($commit), ['', '0'], true)) {
                    $this->line("   <info>$commit</info>");
                }
            }

            // Show file changes summary
            $this->newLine();
            $this->line('<comment>📁 Files changed:</comment>');

            $files = $git->run(['diff', '--name-status', "$localBranch..$upstreamBranch"], throw: false);
            if (! in_array(mb_trim($files), ['', '0'], true)) {
                $fileLines = explode("\n", mb_trim($files));
                $added = 0;
                $modified = 0;
                $deleted = 0;

                foreach ($fileLines as $file) {
                    if (str_starts_with($file, 'A')) {
                        $added++;
                    } elseif (str_starts_with($file, 'M')) {
                        $modified++;
                    } elseif (str_starts_with($file, 'D')) {
                        $deleted++;
                    }
                }

                if ($added > 0) {
                    $this->line("   <info>+$added</info> added");
                }
                if ($modified > 0) {
                    $this->line("   <comment>~$modified</comment> modified");
                }
                if ($deleted > 0) {
                    $this->line("   <error>-$deleted</error> deleted");
                }
            }

        } catch (Throwable $e) {
            $this->line("   <error>Could not fetch commit details: {$e->getMessage()}</error>");
        }
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
            
        } catch (Throwable) {
            // If we can't get repository info, just continue silently
        }
    }

    private function showCommitDetails(VersionControl $git, string $localCommit, string $upstreamCommit): void
    {
        try {
            // Get commit messages
            $localMessage = $git->run(['log', '--format=%s', '-n', '1', $localCommit]);
            $upstreamMessage = $git->run(['log', '--format=%s', '-n', '1', $upstreamCommit]);
            
            // Get commit dates
            $localDate = $git->run(['log', '--format=%ci', '-n', '1', $localCommit]);
            $upstreamDate = $git->run(['log', '--format=%ci', '-n', '1', $upstreamCommit]);
            
            $this->newLine();
            $this->line('📝 <comment>Commit Details:</comment>');
            $this->line("   Local: <info>".mb_substr($localCommit, 0, 8)."</info> - ".mb_trim($localMessage));
            $this->line("   Upstream: <info>".mb_substr($upstreamCommit, 0, 8)."</info> - ".mb_trim($upstreamMessage));
            
            // Show dates
            $this->line('📅 <comment>Last Updated:</comment>');
            $this->line("   Local: <info>".date('Y-m-d H:i:s', strtotime($localDate))."</info>");
            $this->line("   Upstream: <info>".date('Y-m-d H:i:s', strtotime($upstreamDate))."</info>");
            
        } catch (Throwable) {
            // If we can't get commit details, just continue silently
        }
    }
}
