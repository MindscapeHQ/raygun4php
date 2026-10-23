## Runtime and security requirements

Use PHP 8.2.35+, 8.3.36+, 8.4.27+, or 8.5.12+ within the corresponding minor branch. These releases fix [CVE-2026-109618](https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w) and [CVE-2026-109619](https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp). Earlier patch releases in those branches are not supported by this sample.

Verify that the PHP runtime serving web requests meets this requirement, not just the CLI runtime used by Composer. Restart PHP workers after upgrading. Updating Raygun4PHP or running `composer install` does not patch PHP; do not bypass the platform requirement with `--ignore-platform-reqs`.

This sample deliberately triggers errors. Run it locally or in an access-restricted test environment, not as an unrestricted public site. It is not a production-ready application.

## Setup instructions

Run these steps from this sample's directory:

1. Install dependencies with `composer install`.
2. Copy `config-sample.php` and rename to `config.php`
3. Insert your API key from Raygun into the `API_KEY` field
4. Start a local-only server with `php -S 127.0.0.1:8000`.
5. Open `http://127.0.0.1:8000` and submit the form with a zero value to trigger an error.
