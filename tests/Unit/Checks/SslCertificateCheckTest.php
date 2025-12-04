<?php

use HmzaUsman\LaravelReverbDoctor\Checks\SslCertificateCheck;
use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticStatus;

beforeEach(function () {
    $this->check = new SslCertificateCheck();
});

it('has correct name and description', function () {
    expect($this->check->getName())->toBe('SSL Certificate')
        ->and($this->check->getDescription())->toContain('SSL');
});

it('skips when not using SSL', function () {
    putenv('REVERB_SCHEME=http');
    config(['reverb.servers.reverb.scheme' => 'http']);

    $result = $this->check->run();

    expect($result->status)->toBe(DiagnosticStatus::SKIP)
        ->and($result->message)->toContain('SSL not configured');

    putenv('REVERB_SCHEME');
});

it('checks SSL when scheme is https', function () {
    putenv('REVERB_SCHEME=https');
    config(['reverb.servers.reverb.scheme' => 'https']);

    $result = $this->check->run();

    // Should either pass, fail, or warn - but not skip
    expect($result->status)->not->toBe(DiagnosticStatus::SKIP);

    putenv('REVERB_SCHEME');
});
