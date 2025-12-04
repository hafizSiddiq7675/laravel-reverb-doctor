<?php

use HmzaUsman\LaravelReverbDoctor\Checks\PortAvailabilityCheck;
use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticStatus;

beforeEach(function () {
    $this->check = new PortAvailabilityCheck();
});

it('has correct name and description', function () {
    expect($this->check->getName())->toBe('Port Availability')
        ->and($this->check->getDescription())->toContain('8080');
});

it('returns a valid diagnostic result', function () {
    $result = $this->check->run();

    expect($result->status)->toBeIn([
        DiagnosticStatus::PASS,
        DiagnosticStatus::FAIL,
        DiagnosticStatus::WARN,
    ])
        ->and($result->checkName)->toBe('Port Availability');
});

it('uses configured port from env', function () {
    putenv('REVERB_PORT=9000');

    $this->check->setVerbose(true);
    $result = $this->check->run();

    // The check should use port 9000
    expect($result->message)->toContain('9000');

    putenv('REVERB_PORT');
});
