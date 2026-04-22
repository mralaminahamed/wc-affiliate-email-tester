# WC Affiliate Email Tester

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/mralaminahamed/wc-affiliate-email-tester/releases)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.0-8892BF.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-%3E%3D%206.5-21759B.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-%3E%3D%207.0-96588A.svg)](https://woocommerce.com)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)](LICENSE)

A developer tool to test, preview, and debug every WC Affiliate email notification without real triggers or live SMTP.

## Features

- **Fire any WC Affiliate email** against a real affiliate, referral, or transaction in one click
- **Dry-run mode** — capture and preview the fully-rendered email without dispatching it via `wp_mail()`
- **Override recipient** — redirect all test sends to a safe address instead of the real affiliate
- **Email logging** — capture every outgoing `wp_mail()` call with status, headers, and full body
- **Log retention** — auto-purge by count or by age via an hourly cron
- **In-browser preview** — renders the full HTML email inside a sandboxed `<iframe>`
- **Source & headers inspector** — collapsible panels for raw HTML source and mail headers
- **WC Affiliate Pro support** — auto-detects Pro and adds MLC commission, signup bonus, and referral bonus email types
- **REST API** — `/wp-json/wca-email-tester/v1/affiliates|referrals|transactions` endpoints power the Select2 search dropdowns

## Requirements

| Dependency | Minimum version |
|---|---|
| PHP | 8.0 |
| WordPress | 6.5 |
| WooCommerce | 7.0 |
| WC Affiliate | any active version |

## Installation

1. Clone or download this repository into `wp-content/plugins/wc-affiliate-email-tester/`
2. Run `composer install` to generate the autoloader
3. Activate the plugin from **Plugins → Installed Plugins**
4. Navigate to **WCA Email Tester** in the WordPress admin sidebar

```bash
git clone https://github.com/mralaminahamed/wc-affiliate-email-tester.git \
    wp-content/plugins/wc-affiliate-email-tester
cd wp-content/plugins/wc-affiliate-email-tester
composer install --no-dev --optimize-autoloader
```

## Email Types

### Core (WC Affiliate)

| Email type | Trigger |
|---|---|
| `affiliate_application_to_affiliate` | Application received (to affiliate) |
| `affiliate_application_to_admin` | Application received (to admin) |
| `affiliate_approved_to_affiliate` | Application approved |
| `affiliate_rejected_to_affiliate` | Application rejected |
| `new_referral_to_affiliate` | New commission earned |
| `referral_approved_to_affiliate` | Commission approved |
| `referral_rejected_to_affiliate` | Commission rejected |
| `new_payout_to_affiliate` | Payout processed |
| `withdrawal_request_to_admin` | Withdrawal requested (to admin) |
| `withdrawal_approved_to_affiliate` | Withdrawal approved |
| `withdrawal_rejected_to_affiliate` | Withdrawal rejected |
| `new_transaction_to_affiliate` | New transaction |
| `transaction_status_changed_to_affiliate` | Transaction status updated |

### Pro (WC Affiliate Pro, auto-detected)

| Email type | Trigger |
|---|---|
| `mlc_commission_to_affiliate` | MLC commission earned |
| `signup_bonus_to_affiliate` | Signup bonus awarded |
| `referral_bonus_to_affiliate` | Referral bonus awarded |

## Development

### Running tests

```bash
composer install
vendor/bin/phpunit
```

Tests require a working WordPress + WC Affiliate test environment. See `tests/php/bootstrap.php` for setup details.

### Project structure

```
wc-affiliate-email-tester/
├── assets/
│   ├── css/admin.css          # Admin stylesheet
│   └── js/admin.js            # Admin JavaScript
├── includes/
│   ├── class-wca-email-tester.php           # Main plugin singleton
│   ├── class-wca-email-tester-admin.php     # Admin pages & assets
│   ├── class-wca-email-tester-sender.php    # Email dispatch & capture
│   ├── class-wca-email-tester-logger.php    # DB logging
│   ├── class-wca-email-tester-api.php       # REST API endpoints
│   ├── class-wca-email-tester-log-list.php  # WP_List_Table for logs
│   └── class-wca-email-tester-icons.php     # SVG icon registry
├── templates/admin/
│   ├── dashboard.php
│   ├── testing.php
│   ├── result.php
│   ├── email-entry.php
│   ├── logs.php
│   ├── log-view.php
│   └── settings.php
├── tests/php/
│   ├── bootstrap.php
│   └── src/
│       ├── WCA_ET_TestCase.php
│       ├── SenderTest.php
│       ├── LoggerTest.php
│       └── ApiTest.php
├── composer.json
├── phpunit.xml.dist
└── wc-affiliate-email-tester.php
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

[GPL-2.0-or-later](LICENSE) © [Al Amin Ahamed](https://github.com/mralaminahamed)
