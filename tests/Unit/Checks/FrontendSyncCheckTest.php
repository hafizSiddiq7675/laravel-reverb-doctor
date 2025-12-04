<?php

use HmzaUsman\LaravelReverbDoctor\Checks\FrontendSyncCheck;
use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticStatus;

beforeEach(function () {
    $this->check = new FrontendSyncCheck();
});

it('has correct name and description', function () {
    expect($this->check->getName())->toBe('Frontend Sync')
        ->and($this->check->getDescription())->toContain('VITE_REVERB_*');
});

it('warns when frontend variables are missing', function () {
    // Clear any frontend env vars
    putenv('VITE_REVERB_HOST');
    putenv('VITE_REVERB_PORT');
    putenv('VITE_REVERB_SCHEME');
    putenv('VITE_REVERB_APP_KEY');

    $result = $this->check->run();

    expect($result->status)->toBe(DiagnosticStatus::WARN)
        ->and($result->message)->toContain('Missing frontend variables');
});

it('passes when frontend and server configs match', function () {
    // Set server config
    putenv('REVERB_HOST=localhost');
    putenv('REVERB_PORT=8080');
    putenv('REVERB_SCHEME=http');
    putenv('REVERB_APP_KEY=test-key');

    // Set matching frontend config
    putenv('VITE_REVERB_HOST=localhost');
    putenv('VITE_REVERB_PORT=8080');
    putenv('VITE_REVERB_SCHEME=http');
    putenv('VITE_REVERB_APP_KEY=test-key');

    $result = $this->check->run();

    expect($result->status)->toBe(DiagnosticStatus::PASS);

    // Clean up
    putenv('REVERB_HOST');
    putenv('REVERB_PORT');
    putenv('REVERB_SCHEME');
    putenv('REVERB_APP_KEY');
    putenv('VITE_REVERB_HOST');
    putenv('VITE_REVERB_PORT');
    putenv('VITE_REVERB_SCHEME');
    putenv('VITE_REVERB_APP_KEY');
});

it('fails when frontend and server configs mismatch', function () {
    // Set server config
    putenv('REVERB_HOST=localhost');
    putenv('REVERB_PORT=8080');
    putenv('REVERB_SCHEME=http');
    putenv('REVERB_APP_KEY=server-key');

    // Set mismatching frontend config
    putenv('VITE_REVERB_HOST=example.com');
    putenv('VITE_REVERB_PORT=9090');
    putenv('VITE_REVERB_SCHEME=https');
    putenv('VITE_REVERB_APP_KEY=different-key');

    $result = $this->check->run();

    expect($result->status)->toBe(DiagnosticStatus::FAIL)
        ->and($result->message)->toContain('mismatch');

    // Clean up
    putenv('REVERB_HOST');
    putenv('REVERB_PORT');
    putenv('REVERB_SCHEME');
    putenv('REVERB_APP_KEY');
    putenv('VITE_REVERB_HOST');
    putenv('VITE_REVERB_PORT');
    putenv('VITE_REVERB_SCHEME');
    putenv('VITE_REVERB_APP_KEY');
});
