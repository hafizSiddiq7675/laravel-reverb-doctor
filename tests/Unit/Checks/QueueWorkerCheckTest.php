<?php

use HmzaUsman\LaravelReverbDoctor\Checks\QueueWorkerCheck;
use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticStatus;

beforeEach(function () {
    $this->check = new QueueWorkerCheck();
});

it('has correct name and description', function () {
    expect($this->check->getName())->toBe('Queue Worker')
        ->and($this->check->getDescription())->toContain('ShouldBroadcast');
});

it('passes when using sync queue driver', function () {
    config(['queue.default' => 'sync']);

    $result = $this->check->run();

    expect($result->status)->toBe(DiagnosticStatus::PASS)
        ->and($result->message)->toContain('sync');
});

it('checks for queue worker when not using sync', function () {
    config(['queue.default' => 'database']);

    $result = $this->check->run();

    // Will warn if no worker detected (expected in test environment)
    expect($result->status)->toBeIn([
        DiagnosticStatus::PASS,
        DiagnosticStatus::WARN,
    ]);
});
