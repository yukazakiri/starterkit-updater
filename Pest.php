<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses()->in('Unit');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
