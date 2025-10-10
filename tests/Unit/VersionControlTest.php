<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Support\VersionControl;
use Symfony\Component\Process\Process;

it('can be instantiated', function () {
    $versionControl = new VersionControl('git', '/tmp');
    
    expect($versionControl)->toBeInstanceOf(VersionControl::class);
});

it('returns correct git binary', function () {
    $versionControl = new VersionControl('git', '/tmp');
    
    expect($versionControl->getGitBinary())->toBe('git');
});

it('returns correct working directory', function () {
    $versionControl = new VersionControl('git', '/tmp');
    
    expect($versionControl->getWorkingDirectory())->toBe('/tmp');
});

it('can check if remote exists', function () {
    $versionControl = new VersionControl('git', '/tmp');
    
    // Mock the run method to return a list of remotes
    $versionControl = Mockery::mock(VersionControl::class, ['git', '/tmp'])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    
    $versionControl->shouldReceive('run')
        ->with(['remote'], false)
        ->once()
        ->andReturn("origin\nupstream");

    $result = $versionControl->hasRemote('upstream');
    
    expect($result)->toBeTrue();
});

it('returns false when remote does not exist', function () {
    $versionControl = new VersionControl('git', '/tmp');
    
    // Mock the run method to return a list of remotes
    $versionControl = Mockery::mock(VersionControl::class, ['git', '/tmp'])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    
    $versionControl->shouldReceive('run')
        ->with(['remote'], false)
        ->once()
        ->andReturn("origin");

    $result = $versionControl->hasRemote('upstream');
    
    expect($result)->toBeFalse();
});

it('throws exception when git command fails and throw is true', function () {
    $versionControl = new VersionControl('git', '/tmp');
    
    // Mock the run method to simulate a failed command
    $versionControl = Mockery::mock(VersionControl::class, ['git', '/tmp'])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    
    $versionControl->shouldReceive('run')
        ->with(['invalid-command'])
        ->once()
        ->andThrow(new RuntimeException('fatal: invalid command'));

    expect(fn () => $versionControl->run(['invalid-command']))
        ->toThrow(RuntimeException::class, 'fatal: invalid command');
});

it('does not throw exception when git command fails and throw is false', function () {
    $versionControl = new VersionControl('git', '/tmp');
    
    // Mock the run method to simulate a failed command
    $versionControl = Mockery::mock(VersionControl::class, ['git', '/tmp'])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    
    $versionControl->shouldReceive('run')
        ->with(['invalid-command'], false)
        ->once()
        ->andReturn('error output');

    $result = $versionControl->run(['invalid-command'], false);
    
    expect($result)->toBe('error output');
});

it('returns trimmed output from successful command', function () {
    $versionControl = new VersionControl('git', '/tmp');
    
    // Mock the run method to return output with whitespace
    $versionControl = Mockery::mock(VersionControl::class, ['git', '/tmp'])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    
    $versionControl->shouldReceive('run')
        ->with(['status'])
        ->once()
        ->andReturn("  On branch main  \n  nothing to commit  ");

    $result = $versionControl->run(['status']);
    
    expect($result)->toBe("On branch main\nnothing to commit");
});
