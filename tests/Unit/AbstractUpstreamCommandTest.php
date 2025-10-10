<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use LaravelPlus\LaravelUpdater\Console\Commands\AbstractUpstreamCommand;
use LaravelPlus\LaravelUpdater\Contracts\VersionControlInterface;

final class TestUpstreamCommand extends AbstractUpstreamCommand
{
    protected $signature = 'test:upstream-command';

    protected $description = 'Test upstream command';

    public function handle(): int
    {
        return Command::SUCCESS;
    }

    // Expose protected methods for testing
    public function testApplyDefaultPreset(array $cfg): array
    {
        return $this->applyDefaultPreset($cfg);
    }

    public function testRunTests(array $cfg): bool
    {
        return $this->runTests($cfg);
    }

    public function testDisplayLogo(): void
    {
        $this->displayLogo();
    }

    public function getVersionControl(): VersionControlInterface
    {
        return $this->versionControl;
    }
}

it('can be instantiated', function () {
    $command = new TestUpstreamCommand();

    expect($command)->toBeInstanceOf(AbstractUpstreamCommand::class);
    expect($command)->toBeInstanceOf(Command::class);
});

it('has version control instance', function () {
    $command = new TestUpstreamCommand();

    expect($command->getVersionControl())->toBeInstanceOf(VersionControlInterface::class);
});

it('can display logo', function () {
    $command = new TestUpstreamCommand();

    // Test that displayLogo doesn't throw an exception
    expect(fn () => $command->testDisplayLogo())->not->toThrow();
});

it('applies default preset when no upstream URL is set', function () {
    $command = new TestUpstreamCommand();

    $config = [
        'upstream_url' => '',
        'default_preset' => 'vue',
        'presets' => [
            'vue' => [
                'upstream_url' => 'https://github.com/laravel/vue-starter-kit.git',
                'upstream_branch' => 'main',
                'post_update' => ['npm install'],
            ],
        ],
    ];

    $result = $command->testApplyDefaultPreset($config);

    expect($result['upstream_url'])->toBe('https://github.com/laravel/vue-starter-kit.git');
    expect($result['upstream_branch'])->toBe('main');
});

it('does not apply default preset when upstream URL is already set', function () {
    $command = new TestUpstreamCommand();

    $config = [
        'upstream_url' => 'https://github.com/custom/repo.git',
        'default_preset' => 'vue',
        'presets' => [
            'vue' => [
                'upstream_url' => 'https://github.com/laravel/vue-starter-kit.git',
                'upstream_branch' => 'main',
            ],
        ],
    ];

    $result = $command->testApplyDefaultPreset($config);

    expect($result['upstream_url'])->toBe('https://github.com/custom/repo.git');
});

it('does not apply default preset when no default preset is configured', function () {
    $command = new TestUpstreamCommand();

    $config = [
        'upstream_url' => '',
        'default_preset' => null,
        'presets' => [
            'vue' => [
                'upstream_url' => 'https://github.com/laravel/vue-starter-kit.git',
                'upstream_branch' => 'main',
            ],
        ],
    ];

    $result = $command->testApplyDefaultPreset($config);

    expect($result['upstream_url'])->toBe('');
});

it('merges preset hooks with existing hooks', function () {
    $command = new TestUpstreamCommand();

    $config = [
        'upstream_url' => '',
        'default_preset' => 'vue',
        'post_update' => ['composer install'],
        'presets' => [
            'vue' => [
                'upstream_url' => 'https://github.com/laravel/vue-starter-kit.git',
                'upstream_branch' => 'main',
                'post_update' => ['npm install'],
            ],
        ],
    ];

    $result = $command->testApplyDefaultPreset($config);

    expect($result['post_update'])->toContain('composer install');
    expect($result['post_update'])->toContain('npm install');
});

it('can run tests with valid configuration', function () {
    $command = new TestUpstreamCommand();

    $config = [
        'git_binary' => 'git',
        'working_dir' => base_path(),
        'upstream_url' => 'https://github.com/laravel/vue-starter-kit.git',
        'upstream_branch' => 'main',
        'local_branch' => 'main',
        'strategy' => 'merge',
    ];

    // Mock the version control to return successful results
    $versionControl = Mockery::mock(VersionControlInterface::class);
    $versionControl->shouldReceive('run')
        ->with(['--version'], false)
        ->andReturn('git version 2.30.0');
    $versionControl->shouldReceive('run')
        ->with(['ls-remote', 'https://github.com/laravel/vue-starter-kit.git'], false)
        ->andReturn('abc123 refs/heads/main');
    $versionControl->shouldReceive('run')
        ->with(['ls-remote', '--heads', 'https://github.com/laravel/vue-starter-kit.git', 'main'], false)
        ->andReturn('abc123 refs/heads/main');
    $versionControl->shouldReceive('run')
        ->with(['rev-parse', '--git-dir'], false)
        ->andReturn('.git');
    $versionControl->shouldReceive('run')
        ->with(['status', '--porcelain'], false)
        ->andReturn('');

    // Replace the version control instance
    $reflection = new ReflectionClass($command);
    $property = $reflection->getProperty('versionControl');
    $property->setAccessible(true);
    $property->setValue($command, $versionControl);

    $result = $command->testRunTests($config);

    expect($result)->toBeTrue();
});

it('returns false when tests fail', function () {
    $command = new TestUpstreamCommand();

    $config = [
        'git_binary' => 'git',
        'working_dir' => '/nonexistent',
        'upstream_url' => 'https://github.com/laravel/vue-starter-kit.git',
        'upstream_branch' => 'main',
        'local_branch' => 'main',
        'strategy' => 'merge',
    ];

    $result = $command->testRunTests($config);

    expect($result)->toBeFalse();
});

it('can handle command execution', function () {
    $command = new TestUpstreamCommand();

    $result = $command->handle();

    expect($result)->toBe(Command::SUCCESS);
});
