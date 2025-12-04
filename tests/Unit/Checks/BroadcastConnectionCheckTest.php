<?php

use Bitsoftsolutions\LaravelReverbDoctor\Checks\BroadcastConnectionCheck;
use Bitsoftsolutions\LaravelReverbDoctor\Results\DiagnosticStatus;

beforeEach(function () {
    $this->check = new BroadcastConnectionCheck();
});

it('has correct name and description', function () {
    expect($this->check->getName())->toBe('Broadcast Connection')
        ->and($this->check->getDescription())->toContain('BROADCAST_CONNECTION');
});

it('fails when broadcast connection is set to log', function () {
    config(['broadcasting.default' => 'log']);

    $result = $this->check->run();

    expect($result->status)->toBe(DiagnosticStatus::FAIL)
        ->and($result->message)->toContain('log');
});

it('fails when broadcast connection is set to null', function () {
    config(['broadcasting.default' => 'null']);

    $result = $this->check->run();

    expect($result->status)->toBe(DiagnosticStatus::FAIL)
        ->and($result->message)->toContain('null');
});

it('warns when broadcast connection is not reverb', function () {
    config(['broadcasting.default' => 'pusher']);

    $result = $this->check->run();

    expect($result->status)->toBe(DiagnosticStatus::WARN)
        ->and($result->message)->toContain('pusher');
});

it('passes when broadcast connection is reverb with valid config', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb' => [
            'driver' => 'reverb',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'app_id' => 'test-app',
            'options' => [
                'host' => 'localhost',
                'port' => 8080,
                'scheme' => 'http',
            ],
        ],
    ]);

    $result = $this->check->run();

    expect($result->status)->toBe(DiagnosticStatus::PASS);
});
