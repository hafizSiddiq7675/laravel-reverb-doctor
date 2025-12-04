<?php

declare(strict_types=1);

namespace Bitsoftsolutions\LaravelReverbDoctor\Checks;

use Bitsoftsolutions\LaravelReverbDoctor\Results\DiagnosticResult;

abstract class BaseCheck implements CheckInterface
{
    protected bool $verbose = false;

    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;

        return $this;
    }

    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    abstract public function getName(): string;

    abstract public function getDescription(): string;

    abstract public function run(): DiagnosticResult;
}
