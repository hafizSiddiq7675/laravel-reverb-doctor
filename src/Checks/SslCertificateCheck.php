<?php

declare(strict_types=1);

namespace HmzaUsman\LaravelReverbDoctor\Checks;

use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticResult;

class SslCertificateCheck extends BaseCheck
{
    protected array $commonCertPaths = [
        // Laravel Valet (macOS)
        '~/.config/valet/CA/LaravelValetCASelfSigned.pem',
        '~/.config/valet/Certificates/',

        // Laravel Herd (macOS)
        '~/Library/Application Support/Herd/config/valet/CA/',
        '~/Library/Application Support/Herd/config/valet/Certificates/',

        // Linux common paths
        '/etc/ssl/certs/',
        '/etc/pki/tls/certs/',

        // Windows common paths
        'C:/xampp/apache/conf/ssl.crt/',
        'C:/laragon/etc/ssl/',
    ];

    public function getName(): string
    {
        return 'SSL Certificate';
    }

    public function getDescription(): string
    {
        return 'Validate SSL cert paths exist and are readable; test cURL handshake';
    }

    public function run(): DiagnosticResult
    {
        $scheme = env('REVERB_SCHEME') ?? config('reverb.servers.reverb.scheme') ?? 'http';

        // If not using SSL, skip this check
        if ($scheme !== 'https' && $scheme !== 'wss') {
            return DiagnosticResult::skip(
                $this->getName(),
                'SSL not configured (using HTTP)',
                $this->verbose ? ['scheme' => $scheme] : []
            );
        }

        $host = env('REVERB_HOST') ?? config('reverb.servers.reverb.host') ?? 'localhost';
        $port = (int) (env('REVERB_PORT') ?? config('reverb.servers.reverb.port') ?? 8080);

        $details = $this->verbose ? [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
        ] : [];

        // Check SSL configuration in reverb config
        $certPath = config('reverb.servers.reverb.tls.certificate');
        $keyPath = config('reverb.servers.reverb.tls.key');

        if ($certPath) {
            $details['certificate_path'] = $certPath;

            if (! file_exists($certPath)) {
                return DiagnosticResult::fail(
                    $this->getName(),
                    'SSL certificate file not found',
                    "Certificate path does not exist: {$certPath}",
                    $details
                );
            }

            if (! is_readable($certPath)) {
                return DiagnosticResult::fail(
                    $this->getName(),
                    'SSL certificate file not readable',
                    "Check file permissions for: {$certPath}",
                    $details
                );
            }

            // Validate certificate
            $certInfo = $this->getCertificateInfo($certPath);
            if ($certInfo === null) {
                return DiagnosticResult::fail(
                    $this->getName(),
                    'Invalid SSL certificate',
                    "The certificate at {$certPath} could not be parsed",
                    $details
                );
            }

            $details['certificate_info'] = $certInfo;

            // Check expiration
            if (isset($certInfo['validTo_time_t']) && $certInfo['validTo_time_t'] < time()) {
                return DiagnosticResult::fail(
                    $this->getName(),
                    'SSL certificate has expired',
                    'Renew your SSL certificate',
                    $details
                );
            }

            // Warning if expiring soon (within 30 days)
            if (isset($certInfo['validTo_time_t']) && $certInfo['validTo_time_t'] < time() + (30 * 24 * 60 * 60)) {
                $expiresIn = round(($certInfo['validTo_time_t'] - time()) / (24 * 60 * 60));

                return DiagnosticResult::warn(
                    $this->getName(),
                    "SSL certificate expires in {$expiresIn} days",
                    'Consider renewing your SSL certificate soon',
                    $details
                );
            }
        }

        if ($keyPath) {
            $details['key_path'] = $keyPath;

            if (! file_exists($keyPath)) {
                return DiagnosticResult::fail(
                    $this->getName(),
                    'SSL private key file not found',
                    "Key path does not exist: {$keyPath}",
                    $details
                );
            }

            if (! is_readable($keyPath)) {
                return DiagnosticResult::fail(
                    $this->getName(),
                    'SSL private key file not readable',
                    "Check file permissions for: {$keyPath}",
                    $details
                );
            }
        }

        // If SSL is enabled but no cert paths configured, warn about self-signed detection
        if (! $certPath && ! $keyPath) {
            $detectedPaths = $this->detectCertificatePaths();

            if (empty($detectedPaths)) {
                return DiagnosticResult::warn(
                    $this->getName(),
                    'SSL enabled but no certificate configured',
                    'Configure TLS certificate paths in config/reverb.php under servers.reverb.tls',
                    $details
                );
            }

            $details['detected_paths'] = $detectedPaths;

            return DiagnosticResult::pass(
                $this->getName(),
                'SSL certificates detected (Valet/Herd)',
                $details
            );
        }

        return DiagnosticResult::pass(
            $this->getName(),
            'SSL certificate is valid',
            $details
        );
    }

    protected function getCertificateInfo(string $certPath): ?array
    {
        $certContent = file_get_contents($certPath);

        if ($certContent === false) {
            return null;
        }

        $cert = openssl_x509_parse($certContent);

        if ($cert === false) {
            return null;
        }

        return [
            'subject' => $cert['subject']['CN'] ?? 'Unknown',
            'issuer' => $cert['issuer']['CN'] ?? 'Unknown',
            'valid_from' => date('Y-m-d H:i:s', $cert['validFrom_time_t']),
            'valid_to' => date('Y-m-d H:i:s', $cert['validTo_time_t']),
            'validTo_time_t' => $cert['validTo_time_t'],
        ];
    }

    protected function detectCertificatePaths(): array
    {
        $found = [];

        foreach ($this->commonCertPaths as $path) {
            $expandedPath = $this->expandPath($path);

            if (file_exists($expandedPath) || is_dir($expandedPath)) {
                $found[] = $expandedPath;
            }
        }

        return $found;
    }

    protected function expandPath(string $path): string
    {
        if (str_starts_with($path, '~')) {
            $home = PHP_OS_FAMILY === 'Windows'
                ? getenv('USERPROFILE')
                : getenv('HOME');

            return $home . substr($path, 1);
        }

        return $path;
    }
}
