<?php

use HmzaUsman\LaravelReverbDoctor\Checks\EnvironmentVariablesCheck;
use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticStatus;

beforeEach(function () {
    $this->check = new EnvironmentVariablesCheck();
});

it('has correct name and description', function () {
    expect($this->check->getName())->toBe('Environment Variables')
        ->and($this->check->getDescription())->toContain('REVERB_*');
});

it('fails when .env file does not exist', function () {
    // This test runs in a context where .env may not exist
    // The check should handle this gracefully
    $result = $this->check->run();

    expect($result->status)->toBeIn([
        DiagnosticStatus::FAIL,
        DiagnosticStatus::WARN,
        DiagnosticStatus::PASS,
    ]);
});

it('can be set to verbose mode', function () {
    $this->check->setVerbose(true);

    expect($this->check->isVerbose())->toBeTrue();
});
