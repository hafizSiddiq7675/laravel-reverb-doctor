<?php

declare(strict_types=1);

namespace Bitsoftsolutions\LaravelReverbDoctor\Results;

enum DiagnosticStatus: string
{
    case PASS = 'PASS';
    case FAIL = 'FAIL';
    case WARN = 'WARN';
    case SKIP = 'SKIP';

    public function getColor(): string
    {
        return match ($this) {
            self::PASS => 'green',
            self::FAIL => 'red',
            self::WARN => 'yellow',
            self::SKIP => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PASS => '✓',
            self::FAIL => '✗',
            self::WARN => '!',
            self::SKIP => '-',
        };
    }
}

final readonly class DiagnosticResult
{
    public function __construct(
        public string $checkName,
        public DiagnosticStatus $status,
        public string $message,
        public ?string $suggestion = null,
        public array $details = [],
    ) {}

    public static function pass(string $checkName, string $message, array $details = []): self
    {
        return new self(
            checkName: $checkName,
            status: DiagnosticStatus::PASS,
            message: $message,
            details: $details,
        );
    }

    public static function fail(string $checkName, string $message, ?string $suggestion = null, array $details = []): self
    {
        return new self(
            checkName: $checkName,
            status: DiagnosticStatus::FAIL,
            message: $message,
            suggestion: $suggestion,
            details: $details,
        );
    }

    public static function warn(string $checkName, string $message, ?string $suggestion = null, array $details = []): self
    {
        return new self(
            checkName: $checkName,
            status: DiagnosticStatus::WARN,
            message: $message,
            suggestion: $suggestion,
            details: $details,
        );
    }

    public static function skip(string $checkName, string $message, array $details = []): self
    {
        return new self(
            checkName: $checkName,
            status: DiagnosticStatus::SKIP,
            message: $message,
            details: $details,
        );
    }

    public function isPassing(): bool
    {
        return $this->status === DiagnosticStatus::PASS;
    }

    public function isFailing(): bool
    {
        return $this->status === DiagnosticStatus::FAIL;
    }

    public function isWarning(): bool
    {
        return $this->status === DiagnosticStatus::WARN;
    }

    public function toArray(): array
    {
        return [
            'check' => $this->checkName,
            'status' => $this->status->value,
            'message' => $this->message,
            'suggestion' => $this->suggestion,
            'details' => $this->details,
        ];
    }
}
