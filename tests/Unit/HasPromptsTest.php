<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Traits\HasPrompts;

final class PromptFallbackHarness
{
    use HasPrompts;

    /**
     * @param  array<string, string>  $options
     */
    public function selectWithFallback(string $question, array $options, ?string $default = null): string
    {
        return $this->select($question, $options, $default);
    }

    public function textWithFallback(string $question, ?string $default = null): string
    {
        return $this->text($question, $default);
    }

    public function confirmWithFallback(string $question, bool $default = true): bool
    {
        return $this->confirmPrompt($question, $default);
    }

    /**
     * @param  array<int, string>  $choices
     */
    public function choice(string $question, array $choices, ?string $default = null): string
    {
        return (string) $default;
    }

    public function ask(string $question, ?string $default = null): string
    {
        return (string) $default;
    }

    public function confirm(string $question, bool $default = false): bool
    {
        return $default;
    }

    protected function hasPrompts(): bool
    {
        return false;
    }
}

final class PromptAvailableHarness
{
    use HasPrompts;

    public function promptsAvailable(): bool
    {
        return $this->hasPrompts();
    }
}

it('detects whether Laravel Prompts is available', function () {
    $command = new PromptAvailableHarness();

    expect($command->promptsAvailable())->toBe(function_exists('Laravel\Prompts\select'));
});

it('falls back to choice when Laravel Prompts is not available', function () {
    $command = new PromptFallbackHarness();

    $result = $command->selectWithFallback('Select option', [
        'option1' => 'Option 1',
        'option2' => 'Option 2',
    ], 'option1');

    expect($result)->toBe('option1');
});

it('falls back to ask when Laravel Prompts is not available', function () {
    $command = new PromptFallbackHarness();

    expect($command->textWithFallback('Enter value', 'default'))->toBe('default');
});

it('falls back to confirm when Laravel Prompts is not available', function () {
    $command = new PromptFallbackHarness();

    expect($command->confirmWithFallback('Continue?', true))->toBeTrue()
        ->and($command->confirmWithFallback('Continue?', false))->toBeFalse();
});
