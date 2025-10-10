<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Traits;

trait HasPrompts
{
    /**
     * Check if Laravel Prompts is available.
     */
    protected function hasPrompts(): bool
    {
        return function_exists('Laravel\Prompts\select');
    }

    /**
     * Display a select prompt with fallback to choice.
     *
     * @param  string  $question  The question to ask
     * @param  array<string, string>  $options  The options array
     * @param  string|null  $default  The default option key
     * @return string The selected option key
     */
    protected function select(string $question, array $options, ?string $default = null): string
    {
        if ($this->hasPrompts()) {
            return \Laravel\Prompts\select($question, $options, $default);
        }

        return $this->choice($question, array_keys($options), $default);
    }

    /**
     * Display a text input prompt with fallback to ask.
     *
     * @param  string  $question  The question to ask
     * @param  string|null  $default  The default value
     * @param  string|null  $placeholder  The placeholder text
     * @return string The user input
     */
    protected function text(string $question, ?string $default = null, ?string $placeholder = null): string
    {
        if ($this->hasPrompts()) {
            return \Laravel\Prompts\text($question, $placeholder ?? '', $default ?? '');
        }

        return $this->ask($question, $default);
    }

    /**
     * Display a confirm prompt with fallback to confirm.
     *
     * @param  string  $question  The question to ask
     * @param  bool  $default  The default value
     * @return bool The user confirmation
     */
    protected function confirmPrompt(string $question, bool $default = true): bool
    {
        if ($this->hasPrompts()) {
            return \Laravel\Prompts\confirm($question, $default);
        }

        return $this->confirm($question, $default);
    }
}
