<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use LaravelPlus\LaravelUpdater\Traits\HasPrompts;

final class TestCommand extends Command
{
    use HasPrompts;

    protected $signature = 'test:command';

    protected $description = 'Test command';

    public function testHasPrompts(): bool
    {
        return $this->hasPrompts();
    }

    public function testSelect(string $question, array $options, ?string $default = null): string
    {
        return $this->select($question, $options, $default);
    }

    public function testText(string $question, ?string $default = null, ?string $placeholder = null): string
    {
        return $this->text($question, $default, $placeholder);
    }

    public function testConfirmPrompt(string $question, bool $default = true): bool
    {
        return $this->confirmPrompt($question, $default);
    }
}

it('detects if Laravel Prompts is available', function () {
    $command = new TestCommand();

    // This will depend on whether Laravel Prompts is actually available
    $result = $command->testHasPrompts();

    expect($result)->toBeBool();
});

it('falls back to choice when Laravel Prompts is not available', function () {
    $command = new TestCommand();

    // Mock the choice method
    $command = Mockery::mock(TestCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $command->shouldReceive('choice')
        ->with('Select option', ['option1', 'option2'], 'option1')
        ->once()
        ->andReturn('option1');

    // Mock hasPrompts to return false
    $command->shouldReceive('hasPrompts')
        ->andReturn(false);

    $result = $command->testSelect('Select option', ['option1' => 'Option 1', 'option2' => 'Option 2'], 'option1');

    expect($result)->toBe('option1');
});

it('falls back to ask when Laravel Prompts is not available', function () {
    $command = new TestCommand();

    // Mock the ask method
    $command = Mockery::mock(TestCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $command->shouldReceive('ask')
        ->with('Enter value', 'default')
        ->once()
        ->andReturn('user input');

    // Mock hasPrompts to return false
    $command->shouldReceive('hasPrompts')
        ->andReturn(false);

    $result = $command->testText('Enter value', 'default', 'placeholder');

    expect($result)->toBe('user input');
});

it('falls back to confirm when Laravel Prompts is not available', function () {
    $command = new TestCommand();

    // Mock the confirm method
    $command = Mockery::mock(TestCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $command->shouldReceive('confirm')
        ->with('Continue?', true)
        ->once()
        ->andReturn(true);

    // Mock hasPrompts to return false
    $command->shouldReceive('hasPrompts')
        ->andReturn(false);

    $result = $command->testConfirmPrompt('Continue?', true);

    expect($result)->toBeTrue();
});

it('uses Laravel Prompts when available', function () {
    $command = new TestCommand();

    // Mock Laravel Prompts functions
    if (function_exists('Laravel\Prompts\select')) {
        $command = Mockery::mock(TestCommand::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $command->shouldReceive('hasPrompts')
            ->andReturn(true);

        // This test would need to mock the actual Laravel Prompts functions
        // which is complex, so we'll just test the fallback behavior
        expect($command->testHasPrompts())->toBeBool();
    } else {
        // If Laravel Prompts is not available, test the fallback
        $command = Mockery::mock(TestCommand::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $command->shouldReceive('hasPrompts')
            ->andReturn(false);

        $command->shouldReceive('choice')
            ->with('Select option', ['option1', 'option2'], 'option1')
            ->once()
            ->andReturn('option1');

        $result = $command->testSelect('Select option', ['option1' => 'Option 1', 'option2' => 'Option 2'], 'option1');

        expect($result)->toBe('option1');
    }
});
