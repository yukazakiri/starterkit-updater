<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Console\Commands;

use Illuminate\Support\Facades\File;
use LaravelPlus\LaravelUpdater\Traits\HasPrompts;

final class UpstreamSetupCommand extends AbstractUpstreamCommand
{
    use HasPrompts;

    protected $signature = 'laravelplus:setup
        {--preset= : Preset to use (vue, react, livewire, custom)}
        {--url= : Custom upstream URL}
        {--force : Overwrite existing configuration}';

    protected $description = 'Setup upstream sync configuration for your Laravel starter kit.';

    public function handle(): int
    {
        $this->displayLogo();
        $this->displayWelcomeMessage();

        $this->info('🚀 Laravel Updater Setup');
        $this->newLine();

        $preset = $this->option('preset') ?: $this->select(
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

    private function setupPreset(string $preset): int
    {
        $config = config("upstream.presets.$preset");

        if (! $config) {
            $this->error("Unknown preset: $preset");

            return self::FAILURE;
        }

        $this->info("Setting up upstream sync for Laravel $preset starter kit...");
        $this->newLine();

        // Use main branch for Laravel starter kits (standard)
        $branch = 'main';
        $this->line("🌿 Upstream branch: <info>$branch</info> (standard for Laravel starter kits)");

        // Ask for local branch
        $localBranch = $this->text(
            'Which local branch should receive the updates?',
            default: $branch,
            placeholder: 'e.g., main, develop, feature-branch'
        );

        // Update .env file
        $this->updateEnvFile([
            'UPSTREAM_URL' => $config['upstream_url'],
            'UPSTREAM_BRANCH' => $branch,
            'UPSTREAM_LOCAL_BRANCH' => $localBranch,
            'UPSTREAM_PRESET' => $preset,
        ]);

        // Update config file if needed
        if ($this->option('force') || $this->confirmPrompt('Update config/upstream.php with preset hooks?', default: true)) {
            $this->updateConfigFile($config);
        }

        $this->newLine();
        $this->info("✅ Setup complete for Laravel $preset starter kit!");
        $this->line('📝 Configuration added to .env file');
        $this->line("🔗 Upstream URL: {$config['upstream_url']}");
        $this->line("🌿 Upstream Branch: $branch");
        $this->line("📍 Local Branch: $localBranch");

        $this->newLine();
        $this->line('💡 You can now run: <info>php artisan laravelplus:check</info>');

        return self::SUCCESS;
    }

    private function setupCustom(): int
    {
        $url = $this->option('url') ?: $this->text(
            'Enter your custom upstream repository URL',
            placeholder: 'https://github.com/username/repository.git'
        );

        if (! $url) {
            $this->error('Upstream URL is required for custom setup.');

            return self::FAILURE;
        }

        $branch = $this->text(
            'Enter upstream branch name',
            default: 'main',
            placeholder: 'usually "main" for Laravel projects'
        );

        $localBranch = $this->text(
            'Enter local branch name',
            default: 'main',
            placeholder: 'where updates will be applied'
        );

        $this->info('Setting up upstream sync for custom repository...');
        $this->newLine();

        // Update .env file
        $this->updateEnvFile([
            'UPSTREAM_URL' => $url,
            'UPSTREAM_BRANCH' => $branch,
            'UPSTREAM_LOCAL_BRANCH' => $localBranch,
            'UPSTREAM_PRESET' => 'custom',
        ]);

        $this->newLine();
        $this->info('✅ Custom setup complete!');
        $this->line('📝 Configuration added to .env file');
        $this->line("🔗 Upstream URL: $url");
        $this->line("🌿 Upstream Branch: $branch");
        $this->line("📍 Local Branch: $localBranch");

        $this->newLine();
        $this->line('💡 You can now run: <info>php artisan laravelplus:check</info>');

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
            $pattern = "/^$key=.*$/m";
            $replacement = "$key=$value";

            if (preg_match($pattern, (string) $envContent)) {
                $envContent = preg_replace($pattern, $replacement, (string) $envContent);
            } else {
                $envContent .= "\n$replacement";
            }
        }

        File::put($envPath, $envContent);
    }

    /**
     * Update the config file with preset configuration.
     *
     * @param  array<string, mixed>  $config  The preset configuration
     */
    private function updateConfigFile(array $config): void
    {
        $configPath = config_path('upstream.php');

        if (! File::exists($configPath)) {
            $this->warn('Config file not found. Run: php artisan vendor:publish --provider="LaravelPlus\LaravelUpdater\LaravelUpdaterServiceProvider" --tag="config"');

            return;
        }

        $configContent = File::get($configPath);

        // Update post_update hooks
        if (isset($config['post_update'])) {
            $hooks = collect($config['post_update'])->map(fn ($hook): string => "        // '$hook',")->implode("\n");
            $pattern = '/(\'post_update\' => \[\s*)(.*?)(\s*\],)/s';
            $replacement = "'post_update' => [\n$hooks\n    ],";
            $configContent = preg_replace($pattern, $replacement, $configContent);
        }

        File::put($configPath, $configContent);
    }
}
