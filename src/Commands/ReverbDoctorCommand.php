<?php

declare(strict_types=1);

namespace HmzaUsman\LaravelReverbDoctor\Commands;

use HmzaUsman\LaravelReverbDoctor\Checks\BaseCheck;
use HmzaUsman\LaravelReverbDoctor\Checks\BroadcastConnectionCheck;
use HmzaUsman\LaravelReverbDoctor\Checks\ConfigConsistencyCheck;
use HmzaUsman\LaravelReverbDoctor\Checks\ConnectionTestCheck;
use HmzaUsman\LaravelReverbDoctor\Checks\DockerDetectionCheck;
use HmzaUsman\LaravelReverbDoctor\Checks\EnvironmentVariablesCheck;
use HmzaUsman\LaravelReverbDoctor\Checks\FrontendSyncCheck;
use HmzaUsman\LaravelReverbDoctor\Checks\PortAvailabilityCheck;
use HmzaUsman\LaravelReverbDoctor\Checks\QueueWorkerCheck;
use HmzaUsman\LaravelReverbDoctor\Checks\ReverbProcessCheck;
use HmzaUsman\LaravelReverbDoctor\Checks\SslCertificateCheck;
use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticResult;
use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticStatus;
use Illuminate\Console\Command;

class ReverbDoctorCommand extends Command
{
    protected $signature = 'reverb:doctor
                            {--detailed : Show detailed diagnostic output}
                            {--json : Output results as JSON}';

    protected $description = 'Diagnose Laravel Reverb WebSocket configuration issues';

    private const VERSION = '1.0.0';

    /**
     * @var array<class-string<BaseCheck>>
     */
    protected array $checks = [
        EnvironmentVariablesCheck::class,
        ConfigConsistencyCheck::class,
        BroadcastConnectionCheck::class,
        PortAvailabilityCheck::class,
        ReverbProcessCheck::class,
        SslCertificateCheck::class,
        QueueWorkerCheck::class,
        FrontendSyncCheck::class,
        DockerDetectionCheck::class,
        ConnectionTestCheck::class,
    ];

    public function handle(): int
    {
        if ($this->option('json')) {
            return $this->runJsonOutput();
        }

        return $this->runTableOutput();
    }

    protected function runTableOutput(): int
    {
        $this->displayHeader();

        $results = $this->runAllChecks();

        $this->displayResultsTable($results);
        $this->displaySummary($results);

        if ($this->option('detailed')) {
            $this->displayVerboseDetails($results);
        }

        $this->displaySuggestions($results);

        return $this->getExitCode($results);
    }

    protected function runJsonOutput(): int
    {
        $results = $this->runAllChecks();

        $output = [
            'version' => self::VERSION,
            'timestamp' => now()->toIso8601String(),
            'summary' => $this->getSummaryData($results),
            'checks' => array_map(fn (DiagnosticResult $r) => $r->toArray(), $results),
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $this->getExitCode($results);
    }

    /**
     * @return DiagnosticResult[]
     */
    protected function runAllChecks(): array
    {
        $results = [];
        $verbose = $this->option('detailed');

        foreach ($this->checks as $checkClass) {
            /** @var BaseCheck $check */
            $check = new $checkClass();
            $check->setVerbose($verbose);

            $results[] = $check->run();
        }

        return $results;
    }

    protected function displayHeader(): void
    {
        $this->newLine();
        $this->line('<fg=magenta;options=bold>  Laravel Reverb Doctor</> <fg=gray>v' . self::VERSION . '</>');
        $this->newLine();
    }

    /**
     * @param DiagnosticResult[] $results
     */
    protected function displayResultsTable(array $results): void
    {
        if (empty($results)) {
            $this->warn('  No diagnostic checks registered.');
            $this->newLine();

            return;
        }

        $rows = [];
        $index = 1;

        foreach ($results as $result) {
            $statusColor = $result->status->getColor();
            $statusIcon = $result->status->getIcon();

            $rows[] = [
                $index++,
                $result->checkName,
                "<fg={$statusColor}>{$statusIcon} {$result->status->value}</>",
                $this->truncateMessage($result->message, 45),
            ];
        }

        $this->table(
            ['#', 'Check', 'Status', 'Message'],
            $rows
        );
    }

    /**
     * @param DiagnosticResult[] $results
     */
    protected function displaySummary(array $results): void
    {
        $summary = $this->getSummaryData($results);

        $parts = [];

        if ($summary['failed'] > 0) {
            $parts[] = "<fg=red>{$summary['failed']} failed</>";
        }

        if ($summary['warnings'] > 0) {
            $parts[] = "<fg=yellow>{$summary['warnings']} warning" . ($summary['warnings'] > 1 ? 's' : '') . '</>';
        }

        if ($summary['passed'] > 0) {
            $parts[] = "<fg=green>{$summary['passed']} passed</>";
        }

        if ($summary['skipped'] > 0) {
            $parts[] = "<fg=gray>{$summary['skipped']} skipped</>";
        }

        $icon = $summary['failed'] > 0 ? '<fg=red>✗</>' : '<fg=green>✓</>';

        $this->newLine();
        $this->line("  {$icon} " . implode(', ', $parts));
        $this->newLine();
    }

    /**
     * @param DiagnosticResult[] $results
     */
    protected function displayVerboseDetails(array $results): void
    {
        $hasDetails = false;

        foreach ($results as $result) {
            if (! empty($result->details)) {
                $hasDetails = true;

                break;
            }
        }

        if (! $hasDetails) {
            return;
        }

        $this->line('<fg=cyan;options=bold>  Detailed Output:</>');
        $this->newLine();

        foreach ($results as $result) {
            if (empty($result->details)) {
                continue;
            }

            $this->line("  <fg=white;options=bold>{$result->checkName}</>");

            foreach ($result->details as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $this->line("    <fg=gray>{$key}:</> {$value}");
            }

            $this->newLine();
        }
    }

    /**
     * @param DiagnosticResult[] $results
     */
    protected function displaySuggestions(array $results): void
    {
        $suggestions = [];

        foreach ($results as $result) {
            if ($result->suggestion !== null && ($result->isFailing() || $result->isWarning())) {
                $suggestions[] = [
                    'check' => $result->checkName,
                    'status' => $result->status,
                    'suggestion' => $result->suggestion,
                ];
            }
        }

        if (empty($suggestions)) {
            return;
        }

        $this->line('<fg=cyan;options=bold>  Suggested Fixes:</>');
        $this->newLine();

        foreach ($suggestions as $item) {
            $color = $item['status']->getColor();
            $icon = $item['status']->getIcon();

            $this->line("  <fg={$color}>{$icon}</> <fg=white;options=bold>{$item['check']}</>");
            $this->line("    {$item['suggestion']}");
            $this->newLine();
        }
    }

    /**
     * @param DiagnosticResult[] $results
     * @return array{passed: int, failed: int, warnings: int, skipped: int, total: int}
     */
    protected function getSummaryData(array $results): array
    {
        $summary = [
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'skipped' => 0,
            'total' => count($results),
        ];

        foreach ($results as $result) {
            match ($result->status) {
                DiagnosticStatus::PASS => $summary['passed']++,
                DiagnosticStatus::FAIL => $summary['failed']++,
                DiagnosticStatus::WARN => $summary['warnings']++,
                DiagnosticStatus::SKIP => $summary['skipped']++,
            };
        }

        return $summary;
    }

    /**
     * @param DiagnosticResult[] $results
     */
    protected function getExitCode(array $results): int
    {
        foreach ($results as $result) {
            if ($result->isFailing()) {
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    protected function truncateMessage(string $message, int $maxLength): string
    {
        if (mb_strlen($message) <= $maxLength) {
            return $message;
        }

        return mb_substr($message, 0, $maxLength - 3) . '...';
    }
}
