<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Contracts;

interface UpstreamCommandInterface
{
    /**
     * Apply default preset configuration if none is set.
     *
     * @param  array<string, mixed>  $config  The configuration array
     * @return array<string, mixed> The updated configuration
     */
    public function applyDefaultPreset(array $config): array;

    /**
     * Run tests to validate configuration and connectivity.
     *
     * @param  array<string, mixed>  $config  The configuration array
     * @return bool True if all tests pass
     */
    public function runTests(array $config): bool;

    /**
     * Display the LaravelPlus logo.
     */
    public function displayLogo(): void;
}
