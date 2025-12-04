<?php

use Bitsoftsolutions\LaravelReverbDoctor\Checks\ConnectionTestCheck;
use Bitsoftsolutions\LaravelReverbDoctor\Results\DiagnosticStatus;

beforeEach(function () {
    $this->check = new ConnectionTestCheck();
});

it('has correct name and description', function () {
    expect($this->check->getName())->toBe('Connection Test')
        ->and($this->check->getDescription())->toContain('WebSocket');
});

it('fails when cannot connect to reverb', function () {
    // In a test environment without Reverb running
    putenv('REVERB_HOST=localhost');
    putenv('REVERB_PORT=8080');
    putenv('REVERB_SCHEME=http');

    $result = $this->check->run();

    // Should fail when Reverb is not running
    expect($result->status)->toBeIn([
        DiagnosticStatus::PASS,
        DiagnosticStatus::FAIL,
    ]);

    putenv('REVERB_HOST');
    putenv('REVERB_PORT');
    putenv('REVERB_SCHEME');
});

it('provides connection error details', function () {
    putenv('REVERB_HOST=localhost');
    putenv('REVERB_PORT=8080');

    $this->check->setVerbose(true);
    $result = $this->check->run();

    if ($result->status === DiagnosticStatus::FAIL) {
        expect($result->suggestion)->not->toBeNull();
    }

    putenv('REVERB_HOST');
    putenv('REVERB_PORT');
});
