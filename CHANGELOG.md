# Changelog

All notable changes to `laravel-reverb-doctor` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-12-04

### Added

- Initial release of Laravel Reverb Doctor
- 10 diagnostic checks for comprehensive Reverb configuration analysis:
  - Environment Variables check
  - Config Consistency check
  - Broadcast Connection check
  - Port Availability check
  - Reverb Process check
  - SSL Certificate check
  - Queue Worker check
  - Frontend Sync check
  - Docker Detection check
  - Connection Test check
- `php artisan reverb:doctor` command with color-coded table output
- `--json` flag for CI/CD integration
- `--detailed` flag for verbose diagnostic output
- Actionable fix suggestions for each detected issue
- Support for Laravel 10.x, 11.x, and 12.x
- Support for PHP 8.1+
- Docker/Sail environment detection
- SSL certificate validation with expiration warnings
- Cross-platform process detection (Windows/Unix)
- Pest test suite with mocked checks
