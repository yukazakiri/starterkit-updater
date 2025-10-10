<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Console\Commands;

use Exception;
use Illuminate\Support\Str;
use LaravelPlus\LaravelUpdater\Exceptions\UpstreamException;
use LaravelPlus\LaravelUpdater\Services\GitService;
use RuntimeException;

final class UpstreamSyncCommand extends AbstractUpstreamCommand
{
    protected $signature = 'laravelplus:sync
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

    private readonly GitService $gitService;

    public function __construct()
    {
        parent::__construct();
        $this->gitService = new GitService($this->versionControl);
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
                $this->error('❌ Tests failed. Aborting sync.');

                return self::FAILURE;
            }
            $this->newLine();
        }

        $remoteName = (string) $this->option('remote-name');
        $upstreamUrl = (string) ($this->option('upstream') ?: $cfg['upstream_url']);
        $upstreamBranch = (string) ($this->option('branch') ?: $cfg['upstream_branch']);
        $localBranch = (string) ($this->option('local') ?: $cfg['local_branch']);
        $strategy = Str::lower((string) ($this->option('strategy') ?: $cfg['strategy']));
        $allowUnrelated = (bool) $cfg['allow_unrelated_histories'];
        $dry = (bool) $this->option('dry-run');

        if (! in_array($strategy, ['merge', 'rebase'], true)) {
            throw UpstreamException::invalidStrategy($strategy);
        }

        try {
            // Ensure remote exists
            if (! $this->versionControl->hasRemote($remoteName)) {
                $this->line("<info>Adding remote '$remoteName'</info>");
                if (! $dry) {
                    $this->versionControl->run(['remote', 'add', $remoteName, $upstreamUrl]);
                }
            } else {
                $this->line("<info>Updating remote '$remoteName' URL</info>");
                if (! $dry) {
                    $this->versionControl->run(['remote', 'set-url', $remoteName, $upstreamUrl]);
                }
            }

            // Pre hooks
            if (! $this->option('no-pre')) {
                $this->execHooks('pre_update', $dry);
            }

            // Fetch upstream
            $this->line('<info>Fetching from upstream</info>');
            if (! $dry) {
                $this->gitService->fetch($remoteName);
            }

            // Ensure local branch exists and checkout
            $this->line("<info>Ensuring local branch '$localBranch' exists</info>");
            if (! $dry) {
                $this->gitService->ensureBranchExists($localBranch);
            }

            // Merge or rebase
            if ($strategy === 'merge') {
                $this->line("<info>Merging $remoteName/$upstreamBranch into $localBranch</info>");
                if (! $dry) {
                    $this->gitService->merge("$remoteName/$upstreamBranch", $allowUnrelated);
                }
            } else {
                $this->line("<info>Rebasing $localBranch onto $remoteName/$upstreamBranch</info>");
                if (! $dry) {
                    $this->gitService->rebase("$remoteName/$upstreamBranch");
                }
            }

            // Commit (only if there are changes to commit)
            if (! $dry && $this->gitService->hasUncommittedChanges()) {
                $this->line('<info>Committing changes</info>');
                $this->gitService->commit($cfg['commit_message']);
            }

            // Push (optional: only if origin exists)
            if ($this->versionControl->hasRemote('origin')) {
                $this->line("<info>Pushing to origin/$localBranch</info>");
                if (! $dry) {
                    $this->gitService->push('origin', $localBranch);
                }
            }

            // Post hooks
            if (! $this->option('no-post')) {
                $this->execHooks('post_update', $dry);
            }

            $this->newLine();
            $this->info('✅ Sync completed successfully!');

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error("❌ Sync failed: {$e->getMessage()}");

            return self::FAILURE;
        }
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
}
