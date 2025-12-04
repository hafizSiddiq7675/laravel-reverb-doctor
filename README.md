# Laravel Reverb Doctor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bitsoftsolutions/laravel-reverb-doctor.svg?style=flat-square)](https://packagist.org/packages/bitsoftsolutions/laravel-reverb-doctor)
[![Total Downloads](https://img.shields.io/packagist/dt/bitsoftsolutions/laravel-reverb-doctor.svg?style=flat-square)](https://packagist.org/packages/bitsoftsolutions/laravel-reverb-doctor)
[![License](https://img.shields.io/packagist/l/bitsoftsolutions/laravel-reverb-doctor.svg?style=flat-square)](https://packagist.org/packages/bitsoftsolutions/laravel-reverb-doctor)

A CLI diagnostic tool that performs comprehensive health checks on Laravel Reverb WebSocket configurations. Stop spending hours debugging WebSocket issues — let `reverb:doctor` diagnose your configuration in seconds.

## Features

- **10 Diagnostic Checks** covering the most common Reverb configuration issues
- **Actionable Suggestions** for every detected problem
- **Color-coded Output** with PASS/FAIL/WARN/SKIP status indicators
- **JSON Output** for CI/CD integration
- **Docker/Sail Detection** with specific configuration advice
- **SSL Certificate Validation** including expiration warnings
- **Zero Dependencies** — uses only Laravel core packages

## Requirements

- PHP 8.1+
- Laravel 10.x, 11.x, or 12.x
- Laravel Reverb package installed

## Installation

```bash
composer require bitsoftsolutions/laravel-reverb-doctor --dev
```

The package will auto-register via Laravel's package discovery.

## Usage

### Basic Usage

```bash
php artisan reverb:doctor
```

### Output Example

```
  Laravel Reverb Doctor v1.0.0

+----+-----------------------+--------+-----------------------------------------------+
| #  | Check                 | Status | Message                                       |
+----+-----------------------+--------+-----------------------------------------------+
| 1  | Environment Variables | ✓ PASS | All variables present                         |
| 2  | Config Consistency    | ✓ PASS | .env matches config files                     |
| 3  | Broadcast Connection  | ✗ FAIL | Set to 'log', should be 'reverb'              |
| 4  | Port Availability     | ✓ PASS | Port 8080 is available                        |
| 5  | Reverb Process        | ! WARN | Reverb is not running                         |
| 6  | SSL Certificate       | - SKIP | SSL not configured (using HTTP)               |
| 7  | Queue Worker          | ✓ PASS | Queue worker is running                       |
| 8  | Frontend Sync         | ✓ PASS | Frontend and server configs are in sync       |
| 9  | Docker Detection      | - SKIP | Not running in Docker                         |
| 10 | Connection Test       | ✗ FAIL | Cannot connect to localhost:8080              |
+----+-----------------------+--------+-----------------------------------------------+

  ✗ 2 failed, 1 warning, 5 passed, 2 skipped

  Suggested Fixes:

  ✗ Broadcast Connection
    Set BROADCAST_CONNECTION=reverb in your .env file

  ! Reverb Process
    Start Reverb with: php artisan reverb:start

  ✗ Connection Test
    Ensure Reverb is running: php artisan reverb:start
```

### JSON Output (for CI/CD)

```bash
php artisan reverb:doctor --json
```

```json
{
    "version": "1.0.0",
    "timestamp": "2025-12-04T12:00:00+00:00",
    "summary": {
        "passed": 5,
        "failed": 2,
        "warnings": 1,
        "skipped": 2,
        "total": 10
    },
    "checks": [
        {
            "check": "Environment Variables",
            "status": "PASS",
            "message": "All variables present",
            "suggestion": null,
            "details": {}
        }
    ]
}
```

### Detailed Output

```bash
php artisan reverb:doctor --detailed
```

Shows additional diagnostic information for each check, useful for debugging complex issues.

## Diagnostic Checks

| # | Check | Description |
|---|-------|-------------|
| 1 | **Environment Variables** | Verifies all required `REVERB_*` and `VITE_REVERB_*` variables exist in `.env` |
| 2 | **Config Consistency** | Compares `.env` values against `config/reverb.php` and `config/broadcasting.php` |
| 3 | **Broadcast Connection** | Checks `BROADCAST_CONNECTION=reverb` (critical—defaults to 'log') |
| 4 | **Port Availability** | Tests if configured port (default 8080) is available or already in use |
| 5 | **Reverb Process** | Detects if `php artisan reverb:start` is currently running |
| 6 | **SSL Certificate** | Validates SSL cert paths exist and are readable; checks expiration |
| 7 | **Queue Worker** | Checks if queue worker is running (required for `ShouldBroadcast` events) |
| 8 | **Frontend Sync** | Compares `VITE_REVERB_*` variables with server-side configuration |
| 9 | **Docker Detection** | If Docker/Sail detected, verifies port exposure and host bindings |
| 10 | **Connection Test** | Attempts actual WebSocket handshake to configured endpoint |

## Common Issues Detected

The package recognizes and provides fixes for these common error patterns:

| Error Message | Root Cause |
|---------------|------------|
| `cURL error 60: SSL certificate problem` | Valet/Herd self-signed cert not trusted |
| `cURL error 7: Failed to connect` | Port mismatch or Reverb not running |
| `WebSocket closed before connection` | SSL handshake failure or Nginx misconfigured |
| `'reverb' is not a supported driver` | Using deprecated `BROADCAST_DRIVER` |

## CI/CD Integration

Use the `--json` flag and check the exit code:

```yaml
# GitHub Actions example
- name: Check Reverb Configuration
  run: |
    php artisan reverb:doctor --json > reverb-health.json
    if [ $? -ne 0 ]; then
      echo "Reverb configuration issues detected"
      cat reverb-health.json
      exit 1
    fi
```

Exit codes:
- `0` — All checks passed
- `1` — One or more checks failed

## Environment-Specific Notes

### Docker / Laravel Sail

When running in Docker, ensure:
- `REVERB_HOST` is set to `0.0.0.0` (not `localhost`)
- Port is exposed in `docker-compose.yml`
- Frontend uses correct host for WebSocket connections

### Laravel Valet / Herd

For HTTPS with self-signed certificates:
- The SSL check will detect Valet/Herd certificate paths
- Connection tests allow self-signed certs in local environments

### Production

For production deployments:
- Ensure SSL certificates are valid and not expiring soon
- Queue workers should be running via Supervisor or similar
- Consider using a process manager for `reverb:start`

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Hafiz Siddiq](https://github.com/hafizSiddiq7675)
- [All Contributors](../../contributors)
