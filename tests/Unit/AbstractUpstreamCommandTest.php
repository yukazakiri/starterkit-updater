<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use LaravelPlus\LaravelUpdater\Console\Commands\AbstractUpstreamCommand;
use LaravelPlus\LaravelUpdater\Contracts\VersionControlInterface;
use LaravelPlus\LaravelUpdater\Tests\Support\TestRepository;

final class TestUpstreamCommand extends AbstractUpstreamCommand
{
    protected $signature = 'test:upstream-command';

    protected $description = 'Test upstream command';

    public function handle(): int
    {
        return Command::SUCCESS;
    }

    public function testApplyDefaultPreset(array $cfg): array
    {
        return $this->applyDefaultPreset($cfg);
    }

    public function testRunTests(array $cfg): bool
    {
        return $this->runTests($cfg);
    }

    public function getVersionControl(): VersionControlInterface
    {
        return $this->versionControl;
    }
}

function makeTestUpstreamCommand(): TestUpstreamCommand
{
    $command = new TestUpstreamCommand();
    $command->setOutput(new Illuminate\Console\OutputStyle(
        new Symfony\Component\Console\Input\ArrayInput([]),
        new Symfony\Component\Console\Output\BufferedOutput()
    ));

    return $command;
}

it('can be instantiated', function () {
    $command = makeTestUpstreamCommand();

    expect($command)->toBeInstanceOf(AbstractUpstreamCommand::class)
        ->and($command)->toBeInstanceOf(Command::class);
});

it('has a version control instance', function () {
    $command = makeTestUpstreamCommand();

    expect($command->getVersionControl())->toBeInstanceOf(VersionControlInterface::class);
});

it('can display its logo', function () {
    $command = makeTestUpstreamCommand();

    expect(fn () => $command->displayLogo())->not->toThrow(Exception::class);
});

it('applies the default preset and its hooks when the URL is not custom', function () {
    $command = makeTestUpstreamCommand();

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

    expect($result['upstream_url'])->toBe('https://github.com/laravel/vue-starter-kit.git')
        ->and($result['upstream_branch'])->toBe('main')
        ->and($result['post_update'])->toContain('composer install')
        ->and($result['post_update'])->toContain('npm install');
});

it('applies preset hooks even when the built-in default URL is still configured', function () {
    $command = makeTestUpstreamCommand();

    $config = [
        'upstream_url' => 'https://github.com/laravel/vue-starter-kit.git',
        'default_preset' => 'vue',
        'presets' => [
            'vue' => [
                'upstream_url' => 'https://github.com/laravel/vue-starter-kit.git',
                'upstream_branch' => 'main',
                'post_update' => ['npm install'],
            ],
        ],
    ];

    expect($command->testApplyDefaultPreset($config)['post_update'])->toBe(['npm install']);
});

it('does not apply a preset to a custom upstream URL', function () {
    $command = makeTestUpstreamCommand();

    $config = [
        'upstream_url' => 'https://github.com/custom/repo.git',
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

    expect($result['upstream_url'])->toBe('https://github.com/custom/repo.git')
        ->and($result)->not->toHaveKey('post_update');
});

it('does not apply a preset when no default is configured', function () {
    $command = makeTestUpstreamCommand();

    $config = [
        'upstream_url' => '',
        'default_preset' => null,
        'presets' => [
            'vue' => ['upstream_url' => 'https://github.com/laravel/vue-starter-kit.git'],
        ],
    ];

    expect($command->testApplyDefaultPreset($config)['upstream_url'])->toBe('');
});

it('passes all configuration tests against real repositories', function () {
    $local = TestRepository::create();
    $local->write('README.md', 'local');
    $local->commit();

    $upstream = TestRepository::create();
    $upstream->write('README.md', 'upstream');
    $upstream->commit();

    config(['upstream.working_dir' => $local->path]);

    $command = makeTestUpstreamCommand();

    $result = $command->testRunTests([
        'git_binary' => 'git',
        'working_dir' => $local->path,
        'upstream_url' => $upstream->path,
        'upstream_branch' => 'main',
        'local_branch' => 'main',
        'strategy' => 'merge',
    ]);

    expect($result)->toBeTrue();
});

it('fails tests when the working directory is missing', function () {
    $command = makeTestUpstreamCommand();

    $result = $command->testRunTests([
        'git_binary' => 'git',
        'working_dir' => '/nonexistent-directory',
        'upstream_url' => 'https://github.com/laravel/vue-starter-kit.git',
        'upstream_branch' => 'main',
        'local_branch' => 'main',
        'strategy' => 'merge',
    ]);

    expect($result)->toBeFalse();
});

it('can handle command execution', function () {
    $command = makeTestUpstreamCommand();

    expect($command->handle())->toBe(Command::SUCCESS);
});
