<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class UpstreamSetupCommand extends Command
{
    protected $signature = 'upstream:setup
        {--preset= : Preset to use (vue, react, livewire, custom)}
        {--url= : Custom upstream URL}
        {--force : Overwrite existing configuration}';

    protected $description = 'Setup upstream sync configuration for your Laravel starter kit.';

    public function handle(): int
    {
        $this->info('🚀 Laravel Updater Setup');
        $this->newLine();

        $preset = $this->option('preset') ?: $this->choice(
            'Which Laravel starter kit are you using?',
            ['vue', 'react', 'livewire', 'custom'],
            'vue'
        );

        if ($preset === 'custom') {
            return $this->setupCustom();
        }

        return $this->setupPreset($preset);
    }

    private function setupPreset(string $preset): int
    {
        $config = config("upstream.presets.$preset");
        
        if (!$config) {
            $this->error("Unknown preset: $preset");
            return self::FAILURE;
        }

        $this->info("Setting up upstream sync for Laravel $preset starter kit...");
        $this->newLine();

        // Update .env file
        $this->updateEnvFile([
            'UPSTREAM_URL' => $config['upstream_url'],
            'UPSTREAM_BRANCH' => $config['upstream_branch'],
            'UPSTREAM_PRESET' => $preset,
        ]);

        // Update config file if needed
        if ($this->option('force') || $this->confirm('Update config/upstream.php with preset hooks?', true)) {
            $this->updateConfigFile($config);
        }

        $this->newLine();
        $this->info("✅ Setup complete for Laravel $preset starter kit!");
        $this->line("📝 Configuration added to .env file");
        $this->line("🔗 Upstream URL: {$config['upstream_url']}");
        $this->line("🌿 Branch: {$config['upstream_branch']}");
        
        $this->newLine();
        $this->line("💡 You can now run: <info>php artisan upstream:sync --test</info>");

        return self::SUCCESS;
    }

    private function setupCustom(): int
    {
        $url = $this->option('url') ?: $this->ask('Enter your custom upstream repository URL');
        
        if (!$url) {
            $this->error('Upstream URL is required for custom setup.');
            return self::FAILURE;
        }

        $branch = $this->ask('Enter upstream branch name', 'main');
        $localBranch = $this->ask('Enter local branch name', 'main');

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
        $this->line("📝 Configuration added to .env file");
        $this->line("🔗 Upstream URL: $url");
        $this->line("🌿 Branch: $branch");
        
        $this->newLine();
        $this->line("💡 You can now run: <info>php artisan upstream:sync --test</info>");

        return self::SUCCESS;
    }

    private function updateEnvFile(array $config): void
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
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
            
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n$replacement";
            }
        }
        
        File::put($envPath, $envContent);
    }

    private function updateConfigFile(array $config): void
    {
        $configPath = config_path('upstream.php');
        
        if (!File::exists($configPath)) {
            $this->warn('Config file not found. Run: php artisan vendor:publish --provider="LaravelPlus\LaravelUpdater\LaravelUpdaterServiceProvider" --tag="config"');
            return;
        }

        $configContent = File::get($configPath);
        
        // Update post_update hooks
        if (isset($config['post_update'])) {
            $hooks = collect($config['post_update'])->map(fn($hook) => "        // '$hook',")->implode("\n");
            $pattern = '/(\'post_update\' => \[\s*)(.*?)(\s*\],)/s';
            $replacement = "'post_update' => [\n$hooks\n    ],";
            $configContent = preg_replace($pattern, $replacement, $configContent);
        }
        
        File::put($configPath, $configContent);
    }
}
