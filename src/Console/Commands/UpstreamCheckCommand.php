<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Console\Commands;

use LaravelPlus\LaravelUpdater\Contracts\VersionControlInterface;
use Throwable;

final class UpstreamCheckCommand extends AbstractUpstreamCommand
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

        $cfg = $this->applyDefaultPreset((array) config('upstream', []));

        $git = $this->versionControl;
        $remoteName = $this->stringOption('remote-name', 'upstream');
        $upstreamUrl = $this->stringOption('upstream', $this->stringConfig($cfg, 'upstream_url'));
        $upstreamBranch = $this->stringOption('branch', $this->stringConfig($cfg, 'upstream_branch', 'main'));
        $localBranch = $this->stringOption('local', $this->stringConfig($cfg, 'local_branch', 'main'));

        $this->info('🔍 Checking for upstream updates...');

        $this->showRepositoryInfo($git, $upstreamUrl);
        $this->newLine();

        try {
            if (! $git->hasRemote($remoteName)) {
                $this->line("📡 Adding upstream remote: <info>$upstreamUrl</info>");
                $git->run(['remote', 'add', $remoteName, $upstreamUrl]);
            } elseif ($git->run(['remote', 'get-url', $remoteName], throw: false) !== $upstreamUrl) {
                $this->line("📡 Updating upstream remote URL: <info>$upstreamUrl</info>");
                $git->run(['remote', 'set-url', $remoteName, $upstreamUrl]);
            }

            $this->line('📥 Fetching latest from upstream...');
            $git->run(['fetch', $remoteName]);

            $localCommit = $git->run(['rev-parse', $localBranch]);
            $upstreamCommit = $git->run(['rev-parse', "$remoteName/$upstreamBranch"]);

            $this->line('📍 Local commit ('.$localBranch.'): <info>'.mb_substr($localCommit, 0, 8).'</info>');
            $this->line("📍 Upstream commit ($remoteName/$upstreamBranch): <info>".mb_substr($upstreamCommit, 0, 8).'</info>');

            $this->showCommitDetails($git, $localCommit, $upstreamCommit);

            if (! $this->isBehind($git, $localBranch, "$remoteName/$upstreamBranch")) {
                $this->newLine();
                $this->info('✅ You are up to date! No updates available.');
                $this->line('📋 Last sync was from upstream commit: <info>'.mb_substr($upstreamCommit, 0, 8).'</info>');

                return self::SUCCESS;
            }

            $this->newLine();
            $this->info('🆕 Updates available!');
            $this->showCommitDifferences($git, $localBranch, "$remoteName/$upstreamBranch");

            $this->newLine();
            $this->line('💡 To pull the updates, run:');
            $this->line('   <info>php artisan laravelplus:update</info> (creates PR by default)');
            $this->line('   <info>php artisan laravelplus:update --direct</info> (to apply directly)');
            $this->newLine();
            $this->line('🔗 PR will sync from upstream commit: <info>'.mb_substr($upstreamCommit, 0, 8).'</info>');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Error checking for updates: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function isBehind(VersionControlInterface $git, string $localBranch, string $upstreamBranch): bool
    {
        $result = $git->run(['rev-list', '--count', "$localBranch..$upstreamBranch"], throw: false);

        return (int) mb_trim($result) > 0;
    }

    private function showCommitDifferences(VersionControlInterface $git, string $localBranch, string $upstreamBranch): void
    {
        try {
            $this->line('<comment>📋 Recent commits from upstream:</comment>');

            $commits = $git->run(['log', '--oneline', '--no-merges', "$localBranch..$upstreamBranch", '--max-count=10'], throw: false);

            if ($commits === '') {
                $this->line('   No commits to show');

                return;
            }

            foreach (preg_split('/\R/', $commits) ?: [] as $commit) {
                if ($commit !== '') {
                    $this->line("   <info>$commit</info>");
                }
            }

            $this->newLine();
            $this->line('<comment>📁 Files changed:</comment>');

            $files = $git->run(['diff', '--name-status', "$localBranch..$upstreamBranch"], throw: false);

            if ($files === '') {
                return;
            }

            $added = $modified = $deleted = 0;

            foreach (preg_split('/\R/', $files) ?: [] as $file) {
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
        } catch (Throwable $e) {
            $this->line("   <error>Could not fetch commit details: {$e->getMessage()}</error>");
        }
    }

    private function showRepositoryInfo(VersionControlInterface $git, string $upstreamUrl): void
    {
        $localUrl = $git->run(['remote', 'get-url', 'origin'], throw: false);

        if ($localUrl === '') {
            $localUrl = 'Local repository (no origin remote)';
        }

        $this->line('🏠 <comment>Repository Information:</comment>');
        $this->line("   Local: <info>$localUrl</info>");
        $this->line("   Upstream: <info>$upstreamUrl</info>");
    }

    private function showCommitDetails(VersionControlInterface $git, string $localCommit, string $upstreamCommit): void
    {
        try {
            $localMessage = $git->run(['log', '--format=%s', '-n', '1', $localCommit], throw: false);
            $upstreamMessage = $git->run(['log', '--format=%s', '-n', '1', $upstreamCommit], throw: false);
            $localDate = $git->run(['log', '--format=%ci', '-n', '1', $localCommit], throw: false);
            $upstreamDate = $git->run(['log', '--format=%ci', '-n', '1', $upstreamCommit], throw: false);

            $this->newLine();
            $this->line('📝 <comment>Commit Details:</comment>');
            $this->line('   Local: <info>'.mb_substr($localCommit, 0, 8).'</info> - '.mb_trim($localMessage));
            $this->line('   Upstream: <info>'.mb_substr($upstreamCommit, 0, 8).'</info> - '.mb_trim($upstreamMessage));

            if ($localDate !== '' && $upstreamDate !== '') {
                $this->line('📅 <comment>Last Updated:</comment>');
                $this->line('   Local: <info>'.date('Y-m-d H:i:s', (int) strtotime($localDate)).'</info>');
                $this->line('   Upstream: <info>'.date('Y-m-d H:i:s', (int) strtotime($upstreamDate)).'</info>');
            }
        } catch (Throwable) {
            // Commit details are informational only.
        }
    }
}
