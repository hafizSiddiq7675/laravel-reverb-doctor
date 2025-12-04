<?php

use HmzaUsman\LaravelReverbDoctor\Checks\DockerDetectionCheck;
use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticStatus;

beforeEach(function () {
    $this->check = new DockerDetectionCheck();
});

it('has correct name and description', function () {
    expect($this->check->getName())->toBe('Docker Detection')
        ->and($this->check->getDescription())->toContain('Docker');
});

it('skips when not running in docker', function () {
    // In a normal test environment, we're not in Docker
    // Unless the tests are being run in Docker
    $result = $this->check->run();

    // Should either skip (not in Docker) or pass/warn (in Docker)
    expect($result->status)->toBeIn([
        DiagnosticStatus::SKIP,
        DiagnosticStatus::PASS,
        DiagnosticStatus::WARN,
    ]);
});

it('returns valid result regardless of environment', function () {
    $result = $this->check->run();

    expect($result->checkName)->toBe('Docker Detection')
        ->and($result->message)->not->toBeEmpty();
});
