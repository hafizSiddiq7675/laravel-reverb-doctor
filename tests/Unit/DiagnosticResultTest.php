<?php

use Bitsoftsolutions\LaravelReverbDoctor\Results\DiagnosticResult;
use Bitsoftsolutions\LaravelReverbDoctor\Results\DiagnosticStatus;

describe('DiagnosticResult', function () {
    it('can create a pass result', function () {
        $result = DiagnosticResult::pass('Test Check', 'All good');

        expect($result->checkName)->toBe('Test Check')
            ->and($result->status)->toBe(DiagnosticStatus::PASS)
            ->and($result->message)->toBe('All good')
            ->and($result->suggestion)->toBeNull()
            ->and($result->isPassing())->toBeTrue()
            ->and($result->isFailing())->toBeFalse()
            ->and($result->isWarning())->toBeFalse();
    });

    it('can create a fail result with suggestion', function () {
        $result = DiagnosticResult::fail(
            'Test Check',
            'Something went wrong',
            'Fix it by doing X'
        );

        expect($result->checkName)->toBe('Test Check')
            ->and($result->status)->toBe(DiagnosticStatus::FAIL)
            ->and($result->message)->toBe('Something went wrong')
            ->and($result->suggestion)->toBe('Fix it by doing X')
            ->and($result->isPassing())->toBeFalse()
            ->and($result->isFailing())->toBeTrue();
    });

    it('can create a warn result', function () {
        $result = DiagnosticResult::warn(
            'Test Check',
            'Something might be wrong',
            'Consider doing Y'
        );

        expect($result->status)->toBe(DiagnosticStatus::WARN)
            ->and($result->isWarning())->toBeTrue()
            ->and($result->isPassing())->toBeFalse()
            ->and($result->isFailing())->toBeFalse();
    });

    it('can create a skip result', function () {
        $result = DiagnosticResult::skip('Test Check', 'Not applicable');

        expect($result->status)->toBe(DiagnosticStatus::SKIP)
            ->and($result->isPassing())->toBeFalse()
            ->and($result->isFailing())->toBeFalse()
            ->and($result->isWarning())->toBeFalse();
    });

    it('can include details', function () {
        $details = ['key' => 'value', 'nested' => ['a' => 1]];
        $result = DiagnosticResult::pass('Test Check', 'All good', $details);

        expect($result->details)->toBe($details);
    });

    it('can convert to array', function () {
        $result = DiagnosticResult::fail(
            'Test Check',
            'Error message',
            'Suggestion here',
            ['detail' => 'value']
        );

        $array = $result->toArray();

        expect($array)->toBe([
            'check' => 'Test Check',
            'status' => 'FAIL',
            'message' => 'Error message',
            'suggestion' => 'Suggestion here',
            'details' => ['detail' => 'value'],
        ]);
    });
});

describe('DiagnosticStatus', function () {
    it('has correct colors', function () {
        expect(DiagnosticStatus::PASS->getColor())->toBe('green')
            ->and(DiagnosticStatus::FAIL->getColor())->toBe('red')
            ->and(DiagnosticStatus::WARN->getColor())->toBe('yellow')
            ->and(DiagnosticStatus::SKIP->getColor())->toBe('gray');
    });

    it('has correct icons', function () {
        expect(DiagnosticStatus::PASS->getIcon())->toBe('✓')
            ->and(DiagnosticStatus::FAIL->getIcon())->toBe('✗')
            ->and(DiagnosticStatus::WARN->getIcon())->toBe('!')
            ->and(DiagnosticStatus::SKIP->getIcon())->toBe('-');
    });
});
