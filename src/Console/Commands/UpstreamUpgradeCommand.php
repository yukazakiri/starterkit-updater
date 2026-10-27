<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Console\Commands;

use Illuminate\Support\Str;
use LaravelPlus\LaravelUpdater\Support\GitHubRepository;
use LaravelPlus\LaravelUpdater\Support\VersionControl;
use Throwable;

final class UpstreamUpgradeCommand extends AbstractUpstreamCommand
{
    protected $signature = 'laravelplus:update
        {--direct : Apply changes directly without creating a pull request}
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
        {--pr-body= : Override PR body}
        {--force-push : Force push the PR branch using --force-with-lease}';

    protected $description = 'Upgrade your project with the latest changes from upstream repository (creates PR by default).';

    public function handle(): int
    {
        $this->displayLogo();

        $cfg = $this->applyDefaultPreset((array) config('upstream', []));

        if ($this->option('test') && ! $this->runTests($cfg)) {
            $this->error('❌ Tests failed. Aborting upgrade.');

            return self::FAILURE;
        }

        $git = $this->makeGit($cfg);

        if (! $this->option('dry-run') && ! $this->hasUpdates($cfg, $git)) {
            $this->info('✅ You are already up to date! No updates to apply.');

            return self::SUCCESS;
        }

        return $this->option('direct')
            ? $this->directUpgrade($cfg, $git)
            : $this->createPullRequest($cfg, $git);
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function makeGit(array $cfg): VersionControl
    {
        return new VersionControl(
            bin: (string) ($cfg['git_binary'] ?? 'git'),
            cwd: (string) ($cfg['working_dir'] ?? base_path())
        );
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function hasUpdates(array $cfg, VersionControl $git): bool
    {
        $remoteName = $this->remoteName();
        $upstreamUrl = $this->upstreamUrl($cfg);
        $upstreamBranch = $this->upstreamBranch($cfg);
        $localBranch = $this->localBranch($cfg);

        $this->line('🔍 Checking for available updates...');

        try {
            $this->ensureRemote($git, $remoteName, $upstreamUrl);
            $git->run(['fetch', $remoteName]);

            $count = $git->run([
                'rev-list',
                '--count',
                "$localBranch..$remoteName/$upstreamBranch",
            ], throw: false);

            return (int) mb_trim($count) > 0;
        } catch (Throwable $e) {
            $this->warn("⚠️  Could not check for updates: {$e->getMessage()}");

            // Assume there is something to do rather than silently skipping an update.
            return true;
        }
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function createPullRequest(array $cfg, VersionControl $git): int
    {
        $remoteName = $this->remoteName();
        $upstreamUrl = $this->upstreamUrl($cfg);
        $upstreamBranch = $this->upstreamBranch($cfg);
        $localBranch = $this->localBranch($cfg);
        $strategy = $this->strategy($cfg);

        if (! in_array($strategy, ['merge', 'rebase'], true)) {
            $this->error('Invalid strategy. Use merge|rebase.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $prBranch = $this->generatePrBranchName($cfg);
        $prTitle = $this->stringOption('pr-title', $this->stringConfig($cfg, 'pr_title', 'chore: sync from upstream'));
        $prBody = $this->stringOption('pr-body', $this->stringConfig($cfg, 'pr_body', 'Automated upstream sync from {upstream_url}'));

        $this->info('🚀 Creating pull request with upstream updates...');
        $this->line('📍 Current branch: <info>'.$this->currentBranch($git).'</info>');
        $this->line("📍 PR will target: <info>$localBranch</info>");
        $this->line("📍 Using upstream branch: <info>$upstreamBranch</info>");
        $this->line("📝 PR branch: <info>$prBranch</info>");
        $this->line("📋 PR title: <info>$prTitle</info>");

        $this->showRepositoryInfo($git, $upstreamUrl);

        if ($dry) {
            $this->printPullRequestPlan($cfg, $strategy);

            return self::SUCCESS;
        }

        if (! $this->ensureCleanWorkingTree($git)) {
            return self::FAILURE;
        }

        $originalBranch = $this->currentBranch($git);

        try {
            $this->ensureRemote($git, $remoteName, $upstreamUrl);

            if (! $this->option('no-pre')) {
                $this->execHooks('pre_update', $this->hooks($cfg, 'pre_update'));
            }

            $this->line("📥 Fetching <info>$remoteName</info>...");
            $git->run(['fetch', $remoteName]);

            if ($this->branchExists($git, $prBranch)) {
                $this->error("❌ Branch '$prBranch' already exists. Delete it or pass a different --pr-branch.");

                return self::FAILURE;
            }

            $git->run(['checkout', '-b', $prBranch, $localBranch]);

            $this->line("🔀 Applying upstream changes using <info>$strategy</info>...");

            if (! $this->applyUpstreamChanges($git, $remoteName, $upstreamBranch, $strategy, (bool) ($cfg['allow_unrelated_histories'] ?? false))) {
                return self::FAILURE;
            }

            if ($this->option('force-push')) {
                $git->run(['push', '--force-with-lease', 'origin', $prBranch]);
            } else {
                $git->run(['push', 'origin', $prBranch]);
            }

            $this->line("📤 Pushed <info>$prBranch</info> to origin.");

            $this->skipPostHooksInPullRequestMode($cfg);

            $body = str_replace('{upstream_url}', $upstreamUrl, $prBody);

            if (! $this->createGitHubPr($cfg, $git, $prBranch, $localBranch, $prTitle, $body)) {
                return self::FAILURE;
            }

            $this->info('✅ Pull request created successfully!');
            $this->line('💡 Review and merge the PR, then run your post-update steps (or use --direct next time).');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("❌ Update failed: {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            $this->restoreBranch($git, $originalBranch);
        }
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function directUpgrade(array $cfg, VersionControl $git): int
    {
        $remoteName = $this->remoteName();
        $upstreamUrl = $this->upstreamUrl($cfg);
        $upstreamBranch = $this->upstreamBranch($cfg);
        $localBranch = $this->localBranch($cfg);
        $strategy = $this->strategy($cfg);

        if (! in_array($strategy, ['merge', 'rebase'], true)) {
            $this->error('Invalid strategy. Use merge|rebase.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');

        $this->info('🚀 Applying upstream updates directly...');
        $this->line("📍 Target branch: <info>$localBranch</info>");
        $this->line("📍 Using upstream branch: <info>$upstreamBranch</info>");
        $this->line("🔀 Strategy: <info>$strategy</info>");

        $this->showRepositoryInfo($git, $upstreamUrl);

        if ($dry) {
            $this->newLine();
            $this->line('🔍 <comment>What will happen:</comment>');
            $this->line('   1. 📡 Ensure upstream remote is configured');
            $this->line('   2. 📥 Fetch latest changes from upstream');
            $this->line("   3. 🌿 Check out <info>$localBranch</info>");
            $this->line("   4. 🔀 $strategy upstream changes into <info>$localBranch</info>");
            $this->line('   5. 🪝 Run post_update hooks');
            $this->newLine();
            $this->info('✅ Dry run complete - no changes were made.');

            return self::SUCCESS;
        }

        if (! $this->ensureCleanWorkingTree($git)) {
            return self::FAILURE;
        }

        if ($this->currentBranch($git) !== $localBranch) {
            $git->run(['checkout', $localBranch]);
        }

        try {
            $this->ensureRemote($git, $remoteName, $upstreamUrl);

            if (! $this->option('no-pre')) {
                $this->execHooks('pre_update', $this->hooks($cfg, 'pre_update'));
            }

            $this->line("📥 Fetching <info>$remoteName</info>...");
            $git->run(['fetch', $remoteName]);

            $this->line("🔀 Applying upstream changes using <info>$strategy</info>...");

            if (! $this->applyUpstreamChanges($git, $remoteName, $upstreamBranch, $strategy, (bool) ($cfg['allow_unrelated_histories'] ?? false))) {
                return self::FAILURE;
            }

            if (! $this->option('no-post')) {
                $this->execHooks('post_update', $this->hooks($cfg, 'post_update'));
            }

            $this->info("✅ Upstream changes applied directly to <info>$localBranch</info>.");
            $this->line('💡 Review the changes and push when you are ready.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("❌ Update failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    private function applyUpstreamChanges(
        VersionControl $git,
        string $remoteName,
        string $upstreamBranch,
        string $strategy,
        bool $allowUnrelatedHistories
    ): bool {
        $upstreamRef = "$remoteName/$upstreamBranch";

        try {
            if ($strategy === 'rebase') {
                $git->run(['rebase', $upstreamRef]);
            } else {
                $args = ['merge', $upstreamRef, '--no-edit'];

                if ($allowUnrelatedHistories) {
                    $args[] = '--allow-unrelated-histories';
                }

                $git->run($args);
            }

            return true;
        } catch (Throwable $e) {
            $this->error("❌ {$strategy} failed: {$e->getMessage()}");
            $this->showConflictedFiles($git);

            $abortCommand = $strategy === 'rebase' ? ['rebase', '--abort'] : ['merge', '--abort'];

            try {
                $git->run($abortCommand, throw: false);
                $this->line('↩️  Aborted the failed '.$strategy.'; your branch is unchanged.');
            } catch (Throwable) {
                $this->warn('⚠️  Could not abort automatically. Resolve or abort the operation manually.');
            }

            $this->line('💡 Resolve the conflicts locally, commit the result, and run the command again.');

            return false;
        }
    }

    private function showConflictedFiles(VersionControl $git): void
    {
        $files = $git->run(['diff', '--name-only', '--diff-filter=U'], throw: false);

        if ($files === '') {
            return;
        }

        $this->line('🔧 Conflicted files:');

        foreach (preg_split('/\R/', $files) ?: [] as $file) {
            if ($file !== '') {
                $this->line('   - '.$file);
            }
        }
    }

    private function ensureRemote(VersionControl $git, string $remoteName, string $upstreamUrl): void
    {
        if (! $git->hasRemote($remoteName)) {
            $this->line("📡 Adding remote <info>$remoteName</info> → <info>$upstreamUrl</info>");
            $git->run(['remote', 'add', $remoteName, $upstreamUrl]);

            return;
        }

        $currentUrl = $git->run(['remote', 'get-url', $remoteName], throw: false);

        if ($currentUrl !== $upstreamUrl) {
            $this->line("📡 Updating remote <info>$remoteName</info> → <info>$upstreamUrl</info>");
            $git->run(['remote', 'set-url', $remoteName, $upstreamUrl]);
        }
    }

    private function ensureCleanWorkingTree(VersionControl $git): bool
    {
        $status = $git->run(['status', '--porcelain'], throw: false);

        if ($status === '') {
            return true;
        }

        $this->error('❌ Your working tree has uncommitted changes. Commit or stash them before updating.');
        $this->line('This prevents unrelated files from being included in the update.');

        foreach (preg_split('/\R/', $status) ?: [] as $line) {
            if ($line !== '') {
                $this->line('   '.$line);
            }
        }

        return false;
    }

    private function currentBranch(VersionControl $git): string
    {
        return mb_trim($git->run(['rev-parse', '--abbrev-ref', 'HEAD']));
    }

    private function branchExists(VersionControl $git, string $branch): bool
    {
        try {
            $git->run(['rev-parse', '--verify', 'refs/heads/'.$branch]);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function restoreBranch(VersionControl $git, string $branch): void
    {
        try {
            if ($this->currentBranch($git) !== $branch) {
                $git->run(['checkout', $branch]);
            }
        } catch (Throwable $e) {
            $this->warn("⚠️  Could not return to branch '$branch': {$e->getMessage()}");
        }
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function skipPostHooksInPullRequestMode(array $cfg): void
    {
        if ($this->option('no-post')) {
            return;
        }

        if ($this->hooks($cfg, 'post_update') === []) {
            return;
        }

        $this->newLine();
        $this->line('⏭️  <comment>Skipping post_update hooks in PR mode.</comment>');
        $this->line('They can run against unmerged code (for example migrations), so run them locally after the PR is merged.');
        $this->line('Use <info>laravelplus:update --direct</info> if you want them to run as part of the update.');
    }

    /**
     * @param  array<string, mixed>  $cfg
     * @return array<int, string>
     */
    private function hooks(array $cfg, string $key): array
    {
        $hooks = $cfg[$key] ?? [];

        if (! is_array($hooks)) {
            return [];
        }

        return array_values(array_filter($hooks, static fn (mixed $hook): bool => is_string($hook) && $hook !== ''));
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function printPullRequestPlan(array $cfg, string $strategy): void
    {
        $this->newLine();
        $this->line('🔍 <comment>What will happen:</comment>');
        $this->line('   1. 📡 Ensure upstream remote is configured');
        $this->line('   2. 📥 Fetch latest changes from upstream');
        $this->line('   3. 🌿 Create a new PR branch from the target branch');
        $this->line("   4. 🔀 $strategy upstream changes into the PR branch");
        $this->line('   5. 📤 Push the PR branch to origin');
        $this->line('   6. 🎯 Create the GitHub pull request');

        if ($this->hooks($cfg, 'post_update') !== []) {
            $this->line('   7. ⏭️  Skip post_update hooks until the PR is merged');
        }

        $this->newLine();
        $this->info('✅ Dry run complete - no changes were made.');
        $this->line('💡 Run without <comment>--dry-run</comment> to execute the pull request creation.');
    }

    private function showRepositoryInfo(VersionControl $git, string $upstreamUrl): void
    {
        $localUrl = $git->run(['remote', 'get-url', 'origin'], throw: false);

        if ($localUrl === '') {
            $localUrl = 'Local repository (no origin remote)';
        }

        $this->line('🏠 <comment>Repository Information:</comment>');
        $this->line("   Local: <info>$localUrl</info>");
        $this->line("   Upstream: <info>$upstreamUrl</info>");
        $this->newLine();
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function generatePrBranchName(array $cfg): string
    {
        $customBranch = $this->option('pr-branch');

        if (is_string($customBranch) && $customBranch !== '') {
            return $customBranch;
        }

        $prefix = (string) ($cfg['pr_branch_prefix'] ?? 'laravelplus-updater-');

        return $prefix.now()->format('Y-m-d-H-i-s');
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function createGitHubPr(
        array $cfg,
        VersionControl $git,
        string $prBranch,
        string $targetBranch,
        string $title,
        string $body
    ): bool {
        $repository = $this->resolveGitHubRepository($cfg, $git);
        $token = (string) ($cfg['github_token'] ?? '');

        if ($repository === null) {
            $this->warn('⚠️  Could not determine the GitHub repository. Set GITHUB_OWNER and GITHUB_REPO, or add an origin remote.');
            $this->line("🔗 The branch <info>$prBranch</info> was pushed. Create the PR manually on GitHub.");

            return false;
        }

        $compareUrl = GitHubRepository::compareUrl($repository, $targetBranch, $prBranch);

        if ($token === '') {
            $this->warn('⚠️  GitHub token not configured. Set GITHUB_TOKEN to create PRs automatically.');
            $this->line("🔗 You can manually create a PR at: $compareUrl");

            return false;
        }

        $this->line('📤 Creating GitHub pull request...');

        $response = $this->makeGitHubRequest($token, $repository, [
            'title' => $title,
            'body' => $body,
            'head' => $prBranch,
            'base' => $targetBranch,
        ]);

        if ($response !== null && isset($response['html_url'])) {
            $this->info("✅ Pull request created: {$response['html_url']}");

            return true;
        }

        $this->error('❌ Failed to create the pull request.');
        $this->line("🔗 You can create it manually at: $compareUrl");

        return false;
    }

    /**
     * Resolve the GitHub repository from configuration, falling back to the
     * origin remote so manual compare URLs never contain empty placeholders.
     *
     * @param  array<string, mixed>  $cfg
     * @return array{owner: string, repo: string}|null
     */
    private function resolveGitHubRepository(array $cfg, VersionControl $git): ?array
    {
        $owner = mb_trim((string) ($cfg['github_owner'] ?? ''));
        $repo = mb_trim((string) ($cfg['github_repo'] ?? ''));

        if ($owner !== '' && $repo !== '') {
            return ['owner' => $owner, 'repo' => $repo];
        }

        $parsed = GitHubRepository::parse($git->run(['remote', 'get-url', 'origin'], throw: false));

        if ($parsed === null) {
            return null;
        }

        return [
            'owner' => $owner !== '' ? $owner : $parsed['owner'],
            'repo' => $repo !== '' ? $repo : $parsed['repo'],
        ];
    }

    /**
     * @param  array{owner: string, repo: string}  $repository
     * @param  array<string, string>  $data
     * @return array<string, mixed>|null
     */
    private function makeGitHubRequest(string $token, array $repository, array $data): ?array
    {
        $url = "https://api.github.com/repos/{$repository['owner']}/{$repository['repo']}/pulls";

        $ch = curl_init();

        if ($ch === false) {
            $this->error('GitHub API request failed: could not initialize cURL.');

            return null;
        }

        $payload = json_encode($data);

        if ($payload === false) {
            $this->error('GitHub API request failed: could not encode request body.');

            return null;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$token,
            'Accept: application/vnd.github+json',
            'Content-Type: application/json',
            'X-GitHub-Api-Version: 2022-11-28',
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Laravel-Updater/1.0');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $this->error("GitHub API request failed: $curlError");

            return null;
        }

        $decoded = is_string($response) ? json_decode($response, true) : null;

        if ($httpCode === 201) {
            return is_array($decoded) ? $decoded : null;
        }

        $this->error("GitHub API error: HTTP $httpCode");

        if (is_array($decoded) && isset($decoded['message'])) {
            $this->error("Error: {$decoded['message']}");
        }

        if ($httpCode === 422) {
            $this->line('A pull request for this branch may already exist.');
        }

        return null;
    }

    private function remoteName(): string
    {
        return $this->stringOption('remote-name', 'upstream');
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function upstreamUrl(array $cfg): string
    {
        return $this->stringOption('upstream', $this->stringConfig($cfg, 'upstream_url'));
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function upstreamBranch(array $cfg): string
    {
        return $this->stringOption('branch', $this->stringConfig($cfg, 'upstream_branch', 'main'));
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function localBranch(array $cfg): string
    {
        return $this->stringOption('local', $this->stringConfig($cfg, 'local_branch', 'main'));
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function strategy(array $cfg): string
    {
        return Str::lower($this->stringOption('strategy', $this->stringConfig($cfg, 'strategy', 'merge')));
    }
}
