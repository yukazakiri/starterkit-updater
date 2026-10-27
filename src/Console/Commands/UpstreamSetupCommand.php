<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Console\Commands;

use Illuminate\Support\Facades\File;
use LaravelPlus\LaravelUpdater\Support\GitHubRepository;
use LaravelPlus\LaravelUpdater\Traits\HasPrompts;

final class UpstreamSetupCommand extends AbstractUpstreamCommand
{
    use HasPrompts;

    protected $signature = 'laravelplus:setup
        {--preset= : Preset to use (vue, react, livewire, custom)}
        {--url= : Custom upstream URL}
        {--upstream-branch= : Upstream branch to pull from}
        {--local-branch= : Local branch that receives update PRs}
        {--force : Overwrite existing configuration}';

    protected $description = 'Setup upstream sync configuration for your Laravel starter kit.';

    public function handle(): int
    {
        $this->displayLogo();
        $this->displayWelcomeMessage();

        // Check Laravel version compatibility
        if (! $this->checkLaravelVersion()) {
            return self::FAILURE;
        }

        $this->info('🚀 Laravel Updater Setup');
        $this->newLine();

        $preset = $this->stringOption('preset') ?: $this->select(
            'Which Laravel starter kit are you using?',
            [
                'vue' => 'Vue Starter Kit - Vue 3 + Inertia.js + TypeScript + Tailwind CSS',
                'react' => 'React Starter Kit - React 19 + Inertia.js + TypeScript + Tailwind CSS',
                'livewire' => 'Livewire Starter Kit - Livewire 3 + Laravel Volt + TypeScript + Tailwind CSS',
                'custom' => 'Custom Repository - Any Git repository URL',
            ],
            'vue'
        );

        if ($preset === 'custom') {
            return $this->setupCustom();
        }

        return $this->setupPreset($preset);
    }

    private function displayWelcomeMessage(): void
    {
        $this->line('<fg=green>   🌟 Welcome to the LaravelPlus Community! 🌟</>');
        $this->line('');
        $this->line('<fg=yellow>   LaravelPlus is a vibrant community of Laravel developers who are</>');
        $this->line('<fg=yellow>   passionate about building amazing applications with Laravel.</>');
        $this->line('');
        $this->line('<fg=cyan>   🎯 What we offer:</>');
        $this->line('<fg=white>   • High-quality Laravel packages and tools</>');
        $this->line('<fg=white>   • Best practices and development guidelines</>');
        $this->line('<fg=white>   • Community support and collaboration</>');
        $this->line('<fg=white>   • Regular updates and improvements</>');
        $this->line('');
        $this->line('<fg=magenta>   🔗 Join our community:</>');
        $this->line('<fg=white>   • GitHub: https://github.com/LaravelPlus</>');
        $this->line('<fg=white>   • Discord: Coming soon!</>');
        $this->line('<fg=white>   • Twitter: @LaravelPlus</>');
        $this->line('');
        $this->line('<fg=green>   💡 Laravel Updater helps you keep your Laravel starter kits</>');
        $this->line('<fg=green>      up to date with the latest changes from upstream repositories.</>');
        $this->line('');
        $this->line('<fg=blue>   ═══════════════════════════════════════════════════════════ </>');
        $this->line('');
    }

    private function checkLaravelVersion(): bool
    {
        $laravelVersion = app()->version();

        if (version_compare($laravelVersion, '12.0.0', '<')) {
            $this->error('❌ Laravel Version Incompatible');
            $this->newLine();
            $this->line('🚫 <error>Laravel Updater requires Laravel 12.0 or higher</error>');
            $this->line("📍 Your current version: <comment>$laravelVersion</comment>");
            $this->line('📍 Required version: <info>12.0+</info>');
            $this->newLine();
            $this->line('🔧 <comment>To update Laravel:</comment>');
            $this->line('   1. Update your composer.json to require "laravel/framework": "^12.0|^13.0"');
            $this->line('   2. Run: <info>composer update laravel/framework</info>');
            $this->line('   3. Update your application code for Laravel 12/13 compatibility');
            $this->line('   4. Run this setup command again');
            $this->newLine();
            $this->line('📚 <comment>Laravel Upgrade Guides:</comment>');
            $this->line('   • <info>https://laravel.com/docs/12.x/upgrade</info>');
            $this->line('   • <info>https://laravel.com/docs/13.x/upgrade</info>');
            $this->newLine();

            return false;
        }

        $this->line("✅ Laravel version check passed: <info>$laravelVersion</info>");
        $this->newLine();

        return true;
    }

    private function askUpstreamBranch(): string
    {
        $option = $this->option('upstream-branch');

        if (is_string($option) && $option !== '') {
            return $option;
        }

        return $this->text(
            'Which branch should we pull from in the upstream repository?',
            default: 'main',
            placeholder: 'e.g., main, master, develop (this is the branch in the upstream repo)'
        );
    }

    private function askLocalBranch(): string
    {
        $option = $this->option('local-branch');

        if (is_string($option) && $option !== '') {
            return $option;
        }

        $currentBranch = $this->versionControl->run(['rev-parse', '--abbrev-ref', 'HEAD'], throw: false);
        $default = $currentBranch !== '' && ! str_contains($currentBranch, 'fatal:') ? $currentBranch : 'main';

        return $this->text(
            'Which local branch should receive the updates? (PRs will be created against this branch)',
            default: $default,
            placeholder: 'e.g., main, master, dev (your main project branch)'
        );
    }

    private function setupPreset(string $preset): int
    {
        $config = config("upstream.presets.$preset");

        if (! $config) {
            $this->error("Unknown preset: $preset");

            return self::FAILURE;
        }

        $this->info("Setting up upstream sync for Laravel $preset starter kit...");
        $this->newLine();

        // Ask for upstream branch (what branch to pull from in the upstream repo)
        $upstreamBranch = $this->askUpstreamBranch();

        $this->line("🌿 Upstream branch: <info>$upstreamBranch</info> (branch in {$config['upstream_url']})");

        // Ask for local branch (what branch to create PRs against)
        $localBranch = $this->askLocalBranch();

        // Update .env file
        $this->updateEnvFile([
            'UPSTREAM_URL' => $config['upstream_url'],
            'UPSTREAM_BRANCH' => $upstreamBranch,
            'UPSTREAM_LOCAL_BRANCH' => $localBranch,
            'UPSTREAM_DEFAULT_PRESET' => $preset,
        ]);

        // Optional GitHub PR auto-creation setup
        $this->setupGitHubPrOption();

        $this->newLine();
        $this->info("✅ Setup complete for Laravel $preset starter kit!");
        $this->line('📝 Configuration added to .env file');
        $this->line("🔗 Upstream URL: {$config['upstream_url']}");
        $this->line("🌿 Upstream Branch: $upstreamBranch (branch in upstream repo)");
        $this->line("📍 Local Branch: $localBranch (your main project branch)");

        $this->newLine();
        $this->line('🚀 <comment>Next Steps:</comment>');
        $this->line('   1. 💡 Run <info>php artisan laravelplus:check</info> to see what updates are available');
        $this->line('   2. 💡 Run <info>php artisan laravelplus:update</info> to create a PR with upstream changes');
        $this->newLine();
        $this->line('🔍 <comment>What will happen when you run these commands:</comment>');
        $this->line('   • <info>laravelplus:check</info> - Shows detailed information about available updates');
        $this->line('   • <info>laravelplus:update</info> - Creates a new branch and PR with upstream changes');
        $this->line('   • PRs target your <info>'.$localBranch.'</info> branch for safe review');
        $this->line('   • You can review changes on GitHub before merging');
        $this->newLine();
        $this->line('🛡️ <comment>Safety Features:</comment>');
        $this->line('   • Always creates PRs by default (no direct merges)');
        $this->line('   • Dry-run mode available with <info>--dry-run</info> flag');
        $this->line('   • Comprehensive testing before applying changes');

        return self::SUCCESS;
    }

    private function setupCustom(): int
    {
        $url = $this->stringOption('url') ?: $this->text(
            'Enter your custom upstream repository URL',
            placeholder: 'https://github.com/username/repository.git'
        );

        if (! $url) {
            $this->error('Upstream URL is required for custom setup.');

            return self::FAILURE;
        }

        $upstreamBranch = $this->askUpstreamBranch();

        $localBranch = $this->askLocalBranch();

        $this->info('Setting up upstream sync for custom repository...');
        $this->newLine();

        // Update .env file
        $this->updateEnvFile([
            'UPSTREAM_URL' => $url,
            'UPSTREAM_BRANCH' => $upstreamBranch,
            'UPSTREAM_LOCAL_BRANCH' => $localBranch,
            'UPSTREAM_DEFAULT_PRESET' => 'custom',
        ]);

        // Optional GitHub PR auto-creation setup
        $this->setupGitHubPrOption();

        $this->newLine();
        $this->info('✅ Custom setup complete!');
        $this->line('📝 Configuration added to .env file');
        $this->line("🔗 Upstream URL: $url");
        $this->line("🌿 Upstream Branch: $upstreamBranch (branch in upstream repo)");
        $this->line("📍 Local Branch: $localBranch (your main project branch)");

        $this->newLine();
        $this->line('🚀 <comment>Next Steps:</comment>');
        $this->line('   1. 💡 Run <info>php artisan laravelplus:check</info> to see what updates are available');
        $this->line('   2. 💡 Run <info>php artisan laravelplus:update</info> to create a PR with upstream changes');
        $this->newLine();
        $this->line('🔍 <comment>What will happen when you run these commands:</comment>');
        $this->line('   • <info>laravelplus:check</info> - Shows detailed information about available updates');
        $this->line('   • <info>laravelplus:update</info> - Creates a new branch and PR with upstream changes');
        $this->line('   • PRs target your <info>'.$localBranch.'</info> branch for safe review');
        $this->line('   • You can review changes on GitHub before merging');
        $this->newLine();
        $this->line('🛡️ <comment>Safety Features:</comment>');
        $this->line('   • Always creates PRs by default (no direct merges)');
        $this->line('   • Dry-run mode available with <info>--dry-run</info> flag');
        $this->line('   • Comprehensive testing before applying changes');

        return self::SUCCESS;
    }

    /**
     * Update the .env file with configuration values.
     *
     * @param  array<string, string>  $config  The configuration to add
     */
    private function updateEnvFile(array $config): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->warn('.env file not found. Please add these variables manually:');
            foreach ($config as $key => $value) {
                $this->line("$key=$value");
            }

            return;
        }

        $envContent = File::get($envPath);

        foreach ($config as $key => $value) {
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            $replacement = "$key=$value";

            if (preg_match($pattern, $envContent) === 1) {
                $updated = preg_replace($pattern, $replacement, $envContent);

                if ($updated === null) {
                    $this->warn("Could not update $key in the .env file.");

                    continue;
                }

                $envContent = $updated;
            } else {
                $envContent .= "\n$replacement";
            }
        }

        File::put($envPath, $envContent);
    }

    /**
     * Optionally configure GitHub PR auto-creation.
     */
    private function setupGitHubPrOption(): void
    {
        if (! $this->input->isInteractive()) {
            return;
        }

        $this->newLine();
        $this->line('<comment>Optional: Auto-create GitHub Pull Requests</comment>');
        $this->line('If enabled, the updater will call the GitHub API to create the PR for you.');
        $this->line('If disabled, it will push a branch and print a compare URL for manual PR creation.');
        $this->newLine();

        $enable = $this->confirmPrompt('Enable auto-creating PRs via GitHub API?', default: false);
        if (! $enable) {
            return;
        }

        // Try to infer owner/repo from origin remote
        $owner = (string) (config('upstream.github_owner') ?: '');
        $repo = (string) (config('upstream.github_repo') ?: '');

        if ($owner === '' || $repo === '') {
            $origin = $this->versionControl->run(['remote', 'get-url', 'origin'], throw: false);
            $parsed = GitHubRepository::parse($origin);

            if ($parsed !== null) {
                $owner = $owner !== '' ? $owner : $parsed['owner'];
                $repo = $repo !== '' ? $repo : $parsed['repo'];
            }
        }

        $owner = $this->text('GitHub owner (organization or username)', default: $owner ?: '');
        $repo = $this->text('GitHub repository name', default: $repo ?: '');

        $this->line('You need a GitHub token to create PRs automatically:');
        $this->line('- Classic PAT: grant repo scope');
        $this->line('- Fine-grained PAT: grant Pull requests: Read and write, Contents: Read and write');
        $this->line('You can also use CI’s built-in GITHUB_TOKEN with appropriate permissions.');
        $token = $this->secret('GitHub token (leave blank to skip for now)');

        // Save what we have; token can be empty (manual PR fallback)
        $this->updateEnvFile([
            'GITHUB_OWNER' => $owner,
            'GITHUB_REPO' => $repo,
        ] + ($token ? ['GITHUB_TOKEN' => $token] : []));

        $this->newLine();
        if ($token) {
            $this->info('✅ GitHub PR auto-creation enabled.');
        } else {
            $this->warn('⚠️  Token not provided. PRs will be pushed and a compare URL will be printed.');
        }
    }
}
