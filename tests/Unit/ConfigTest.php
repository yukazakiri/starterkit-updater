<?php

declare(strict_types=1);

it('has valid upstream configuration structure', function () {
    $config = config('upstream');
    
    expect($config)->toBeArray();
    expect($config)->toHaveKeys([
        'upstream_url',
        'upstream_branch',
        'local_branch',
        'strategy',
        'git_binary',
        'working_dir',
        'commit_message',
        'allow_unrelated_histories',
        'pre_update',
        'post_update',
        'presets',
        'auto_detect',
        'default_preset',
        'create_pr',
        'pr_branch_prefix',
        'pr_title',
        'pr_body',
        'github_token',
        'github_owner',
        'github_repo',
    ]);
});

it('has valid strategy configuration', function () {
    $config = config('upstream');
    
    expect($config['strategy'])->toBeIn(['merge', 'rebase']);
});

it('has valid git binary configuration', function () {
    $config = config('upstream');
    
    expect($config['git_binary'])->toBeString();
    expect($config['git_binary'])->not->toBeEmpty();
});

it('has valid working directory configuration', function () {
    $config = config('upstream');
    
    expect($config['working_dir'])->toBeString();
    expect($config['working_dir'])->not->toBeEmpty();
});

it('has valid commit message configuration', function () {
    $config = config('upstream');
    
    expect($config['commit_message'])->toBeString();
    expect($config['commit_message'])->not->toBeEmpty();
});

it('has valid allow unrelated histories configuration', function () {
    $config = config('upstream');
    
    expect($config['allow_unrelated_histories'])->toBeBool();
});

it('has valid pre_update hooks configuration', function () {
    $config = config('upstream');
    
    expect($config['pre_update'])->toBeArray();
});

it('has valid post_update hooks configuration', function () {
    $config = config('upstream');
    
    expect($config['post_update'])->toBeArray();
});

it('has valid presets configuration', function () {
    $config = config('upstream');
    
    expect($config['presets'])->toBeArray();
    expect($config['presets'])->toHaveKeys(['vue', 'react', 'livewire']);
});

it('has valid vue preset configuration', function () {
    $config = config('upstream');
    $vuePreset = $config['presets']['vue'];
    
    expect($vuePreset)->toBeArray();
    expect($vuePreset)->toHaveKeys(['upstream_url', 'upstream_branch', 'post_update']);
    expect($vuePreset['upstream_url'])->toBeString();
    expect($vuePreset['upstream_branch'])->toBeString();
    expect($vuePreset['post_update'])->toBeArray();
});

it('has valid react preset configuration', function () {
    $config = config('upstream');
    $reactPreset = $config['presets']['react'];
    
    expect($reactPreset)->toBeArray();
    expect($reactPreset)->toHaveKeys(['upstream_url', 'upstream_branch', 'post_update']);
    expect($reactPreset['upstream_url'])->toBeString();
    expect($reactPreset['upstream_branch'])->toBeString();
    expect($reactPreset['post_update'])->toBeArray();
});

it('has valid livewire preset configuration', function () {
    $config = config('upstream');
    $livewirePreset = $config['presets']['livewire'];
    
    expect($livewirePreset)->toBeArray();
    expect($livewirePreset)->toHaveKeys(['upstream_url', 'upstream_branch', 'post_update']);
    expect($livewirePreset['upstream_url'])->toBeString();
    expect($livewirePreset['upstream_branch'])->toBeString();
    expect($livewirePreset['post_update'])->toBeArray();
});

it('has valid auto_detect configuration', function () {
    $config = config('upstream');
    
    expect($config['auto_detect'])->toBeBool();
});

it('has valid default_preset configuration', function () {
    $config = config('upstream');
    
    expect($config['default_preset'])->toBeString();
    expect($config['default_preset'])->toBeIn(['vue', 'react', 'livewire']);
});

it('has valid create_pr configuration', function () {
    $config = config('upstream');
    
    expect($config['create_pr'])->toBeBool();
});

it('has valid pr_branch_prefix configuration', function () {
    $config = config('upstream');
    
    expect($config['pr_branch_prefix'])->toBeString();
    expect($config['pr_branch_prefix'])->not->toBeEmpty();
});

it('has valid pr_title configuration', function () {
    $config = config('upstream');
    
    expect($config['pr_title'])->toBeString();
    expect($config['pr_title'])->not->toBeEmpty();
});

it('has valid pr_body configuration', function () {
    $config = config('upstream');
    
    expect($config['pr_body'])->toBeString();
    expect($config['pr_body'])->not->toBeEmpty();
});

it('has valid github configuration', function () {
    $config = config('upstream');
    
    expect($config['github_token'])->toBeString();
    expect($config['github_owner'])->toBeString();
    expect($config['github_repo'])->toBeString();
});

it('has valid upstream URLs in presets', function () {
    $config = config('upstream');
    
    foreach ($config['presets'] as $presetName => $preset) {
        expect($preset['upstream_url'])
            ->toStartWith('https://github.com/laravel/')
            ->toEndWith('.git');
    }
});

it('has main branch for all presets', function () {
    $config = config('upstream');
    
    foreach ($config['presets'] as $presetName => $preset) {
        expect($preset['upstream_branch'])->toBe('main');
    }
});

it('has valid post_update hooks in presets', function () {
    $config = config('upstream');
    
    foreach ($config['presets'] as $presetName => $preset) {
        expect($preset['post_update'])->toBeArray();
        
        foreach ($preset['post_update'] as $hook) {
            expect($hook)->toBeString();
            expect($hook)->not->toBeEmpty();
        }
    }
});
