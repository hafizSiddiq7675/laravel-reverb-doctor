<?php

use HmzaUsman\LaravelReverbDoctor\Checks\ConfigConsistencyCheck;
use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticStatus;

beforeEach(function () {
    $this->check = new ConfigConsistencyCheck();
});

it('has correct name and description', function () {
    expect($this->check->getName())->toBe('Config Consistency')
        ->and($this->check->getDescription())->toContain('config/reverb.php');
});

it('fails when config values do not match env', function () {
    // Set up mismatched config
    config([
        'reverb.servers.reverb.host' => 'localhost',
        'reverb.servers.reverb.port' => 8080,
        'reverb.servers.reverb.scheme' => 'http',
    ]);

    // Set env values that don't match
    putenv('REVERB_HOST=different-host.example.com');
    putenv('REVERB_PORT=9999');
    putenv('REVERB_SCHEME=https');

    $result = $this->check->run();

    expect($result->status)->toBe(DiagnosticStatus::FAIL)
        ->and($result->message)->toContain('mismatch');

    // Clean up
    putenv('REVERB_HOST');
    putenv('REVERB_PORT');
    putenv('REVERB_SCHEME');
});

it('passes when config values match', function () {
    // Set up matching config
    config([
        'reverb.servers.reverb.host' => 'localhost',
        'reverb.servers.reverb.port' => 8080,
        'reverb.servers.reverb.scheme' => 'http',
    ]);

    // Mock env values to match
    putenv('REVERB_HOST=localhost');
    putenv('REVERB_PORT=8080');
    putenv('REVERB_SCHEME=http');

    $result = $this->check->run();

    expect($result->status)->toBe(DiagnosticStatus::PASS);

    // Clean up
    putenv('REVERB_HOST');
    putenv('REVERB_PORT');
    putenv('REVERB_SCHEME');
});
