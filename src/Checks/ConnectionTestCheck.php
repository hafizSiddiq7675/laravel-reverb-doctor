<?php

declare(strict_types=1);

namespace HmzaUsman\LaravelReverbDoctor\Checks;

use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticResult;

class ConnectionTestCheck extends BaseCheck
{
    public function getName(): string
    {
        return 'Connection Test';
    }

    public function getDescription(): string
    {
        return 'Attempt actual WebSocket handshake to configured endpoint';
    }

    public function run(): DiagnosticResult
    {
        $scheme = env('REVERB_SCHEME') ?? config('reverb.servers.reverb.scheme') ?? 'http';
        $host = env('REVERB_HOST') ?? config('reverb.servers.reverb.host') ?? 'localhost';
        $port = (int) (env('REVERB_PORT') ?? config('reverb.servers.reverb.port') ?? 8080);
        $appKey = env('REVERB_APP_KEY') ?? config('reverb.apps.0.key') ?? '';

        $wsScheme = $scheme === 'https' ? 'wss' : 'ws';
        $httpScheme = $scheme === 'https' ? 'https' : 'http';

        // Build WebSocket URL
        $wsUrl = "{$wsScheme}://{$host}:{$port}/app/{$appKey}";
        $httpUrl = "{$httpScheme}://{$host}:{$port}";

        $details = $this->verbose ? [
            'websocket_url' => $wsUrl,
            'http_url' => $httpUrl,
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
        ] : [];

        // First check if port is reachable at all
        $portReachable = $this->isPortReachable($host, $port);

        if (! $portReachable) {
            return DiagnosticResult::fail(
                $this->getName(),
                "Cannot connect to {$host}:{$port}",
                "Ensure Reverb is running: php artisan reverb:start\nOr check if the port is blocked by a firewall.",
                $details
            );
        }

        // Try HTTP request to check if Reverb is responding
        $httpResult = $this->testHttpConnection($httpUrl);

        $details['http_test'] = $httpResult;

        if ($httpResult['error']) {
            // Categorize the error
            $suggestion = $this->getSuggestionForError($httpResult['error']);

            return DiagnosticResult::fail(
                $this->getName(),
                'Connection failed: ' . $this->truncateError($httpResult['error']),
                $suggestion,
                $details
            );
        }

        // Try WebSocket upgrade request
        $wsResult = $this->testWebSocketUpgrade($host, $port, $appKey, $scheme === 'https');

        $details['websocket_test'] = $wsResult;

        if (! $wsResult['success']) {
            return DiagnosticResult::fail(
                $this->getName(),
                'WebSocket handshake failed',
                $wsResult['error'] ?? 'Unable to establish WebSocket connection',
                $details
            );
        }

        return DiagnosticResult::pass(
            $this->getName(),
            "Connected to {$host}:{$port}",
            $details
        );
    }

    protected function isPortReachable(string $host, int $port): bool
    {
        $testHost = $host === '0.0.0.0' ? '127.0.0.1' : $host;
        $socket = @fsockopen($testHost, $port, $errno, $errstr, 3);

        if ($socket !== false) {
            fclose($socket);

            return true;
        }

        return false;
    }

    protected function testHttpConnection(string $url): array
    {
        if (! function_exists('curl_init')) {
            return [
                'success' => false,
                'error' => 'cURL extension not available',
            ];
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false, // Allow self-signed certs for local dev
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($errno !== 0) {
            return [
                'success' => false,
                'error' => $error,
                'errno' => $errno,
            ];
        }

        return [
            'success' => true,
            'http_code' => $httpCode,
            'response_length' => strlen($response ?: ''),
        ];
    }

    protected function testWebSocketUpgrade(string $host, int $port, string $appKey, bool $ssl): array
    {
        $testHost = $host === '0.0.0.0' ? '127.0.0.1' : $host;

        // Create socket connection
        $protocol = $ssl ? 'ssl' : 'tcp';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $socket = @stream_socket_client(
            "{$protocol}://{$testHost}:{$port}",
            $errno,
            $errstr,
            5,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($socket === false) {
            return [
                'success' => false,
                'error' => "Socket connection failed: {$errstr} ({$errno})",
            ];
        }

        // Generate WebSocket key
        $wsKey = base64_encode(random_bytes(16));

        // Build WebSocket upgrade request
        $path = "/app/{$appKey}";
        $request = "GET {$path} HTTP/1.1\r\n";
        $request .= "Host: {$testHost}:{$port}\r\n";
        $request .= "Upgrade: websocket\r\n";
        $request .= "Connection: Upgrade\r\n";
        $request .= "Sec-WebSocket-Key: {$wsKey}\r\n";
        $request .= "Sec-WebSocket-Version: 13\r\n";
        $request .= "Origin: http://{$testHost}\r\n";
        $request .= "\r\n";

        // Send request
        fwrite($socket, $request);

        // Read response
        stream_set_timeout($socket, 5);
        $response = '';
        $headerEnd = false;

        while (! $headerEnd && ! feof($socket)) {
            $line = fgets($socket, 1024);

            if ($line === false) {
                break;
            }

            $response .= $line;

            if ($line === "\r\n") {
                $headerEnd = true;
            }
        }

        fclose($socket);

        // Check for successful upgrade
        if (str_contains($response, '101')) {
            return [
                'success' => true,
                'status' => 'WebSocket upgrade successful',
            ];
        }

        // Extract status code from response
        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $response, $matches)) {
            $statusCode = $matches[1];

            return [
                'success' => false,
                'error' => "Received HTTP {$statusCode} instead of 101 Switching Protocols",
                'http_code' => $statusCode,
            ];
        }

        return [
            'success' => false,
            'error' => 'Invalid response from server',
            'response' => substr($response, 0, 200),
        ];
    }

    protected function getSuggestionForError(string $error): string
    {
        $errorLower = strtolower($error);

        if (str_contains($errorLower, 'ssl certificate problem') || str_contains($errorLower, 'certificate')) {
            return "SSL certificate issue detected.\nFor local development with self-signed certs (Valet/Herd), try:\n- Add certificate to trusted store\n- Or use HTTP for local development";
        }

        if (str_contains($errorLower, 'connection refused')) {
            return "Connection refused - Reverb server may not be running.\nStart with: php artisan reverb:start";
        }

        if (str_contains($errorLower, 'timed out') || str_contains($errorLower, 'timeout')) {
            return "Connection timed out.\n- Check if Reverb is running\n- Check firewall settings\n- Verify host/port configuration";
        }

        if (str_contains($errorLower, 'could not resolve')) {
            return "DNS resolution failed.\nCheck REVERB_HOST in your .env file.";
        }

        return "Check your Reverb configuration and ensure the server is running.";
    }

    protected function truncateError(string $error): string
    {
        return strlen($error) > 60 ? substr($error, 0, 57) . '...' : $error;
    }
}
