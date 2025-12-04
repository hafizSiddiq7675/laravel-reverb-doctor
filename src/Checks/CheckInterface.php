<?php

declare(strict_types=1);

namespace Bitsoftsolutions\LaravelReverbDoctor\Checks;

use Bitsoftsolutions\LaravelReverbDoctor\Results\DiagnosticResult;

interface CheckInterface
{
    /**
     * Get the name of this diagnostic check.
     */
    public function getName(): string;

    /**
     * Get a short description of what this check does.
     */
    public function getDescription(): string;

    /**
     * Run the diagnostic check.
     */
    public function run(): DiagnosticResult;
}
