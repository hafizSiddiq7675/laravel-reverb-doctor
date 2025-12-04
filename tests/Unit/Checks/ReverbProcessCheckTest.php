<?php

use Bitsoftsolutions\LaravelReverbDoctor\Checks\ReverbProcessCheck;
use Bitsoftsolutions\LaravelReverbDoctor\Results\DiagnosticStatus;

beforeEach(function () {
    $this->check = new ReverbProcessCheck();
});

it('has correct name and description', function () {
    expect($this->check->getName())->toBe('Reverb Process')
        ->and($this->check->getDescription())->toContain('reverb:start');
});

it('warns when reverb is not running', function () {
    // In a test environment, Reverb is typically not running
    $result = $this->check->run();

    // Should either warn (not running) or pass (running)
    expect($result->status)->toBeIn([
        DiagnosticStatus::PASS,
        DiagnosticStatus::WARN,
    ]);
});

it('provides helpful suggestion when not running', function () {
    $result = $this->check->run();

    if ($result->status === DiagnosticStatus::WARN) {
        expect($result->suggestion)->toContain('reverb:start');
    }
});
