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
- **REST API** — admin-only endpoints under `wca-email-tester/v1` power the Select2 search dropdowns (see [REST API](#rest-api))

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

Keys here are the exact `email_type` form values accepted by the tester. They map 1:1 to `WCA_Email_Tester_Sender::email_types()`.

### Core (WC Affiliate)

| Key | Required input | Underlying core hook |
|---|---|---|
| `affiliate_applied` | affiliate | `wc_affiliate_affiliate_applied` |
| `affiliate_applied_admin` | affiliate | `wc_affiliate_affiliate_applied` |
| `affiliate_approved` | affiliate | `wc_affiliate_account_reviewed` |
| `affiliate_rejected` | affiliate | `wc_affiliate_account_reviewed` |
| `email_verification` | affiliate | `wc_affiliate_resend_verification_email` |
| `commission_earned` | referral | `wc_affiliate_add_credit` |
| `commission_earned_admin` | referral | `wc_affiliate_add_credit` |
| `payout_request` | affiliate | `wc_affiliate_payout_request_created` |
| `payout_request_admin` | affiliate | `wc_affiliate_payout_request_created` |
| `payout_processed` | transaction | `wc_affiliate_payout_processed` |
| `transaction_created_admin` | transaction | `wc_affiliate_transaction_after_create` |
| `paid_referral` | referral | `wc_affiliate_referral_has_paid` |
| `paid_referral_admin` | referral | `wc_affiliate_referral_has_paid` |

### Pro (WC Affiliate Pro — auto-detected)

| Key | Required input | Underlying core hook |
|---|---|---|
| `mlc_commission` | referral | `wc_affiliate_mlc_commission_added` |
| `signup_bonus` | affiliate | `wc_affiliate_signup_bonus_added` |
| `referral_bonus` | affiliate | `wc_affiliate_referral_bonus_added` |

The "Required input" column indicates which form field (`affiliate_id`, `referral_id`, or `transaction_id`) the tester needs to fire the email. Pro keys appear automatically when `WC_AFFILIATE_PRO_VERSION` is defined.

## Extension points

| Hook | Type | Purpose |
|---|---|---|
| `wca_email_tester_email_types` | filter | Add/remove/rename email types shown in the tester. Receives `array<string, string>` keyed by email-type slug. |
| `wca_email_tester_before_test_send` | action | Fired right before the tester dispatches the underlying core hook. Receives `string $email_type`. Used internally to tag captured logs with `source=test`. |
| `wca_email_tester_after_test_send` | action | Fired right after dispatch (regardless of success). Receives `string $email_type`. |

Adding a custom type from another plugin:

```php
add_filter( 'wca_email_tester_email_types', function ( array $types ): array {
    $types['my_custom_email'] = __( 'Custom: My Email', 'my-plugin' );
    return $types;
} );
```

Note that adding a type to the dropdown does not by itself teach the tester how to fire it — you must also extend `WCA_Email_Tester_Sender::field_for_type()` and `fire_hook()` via a fork or PR.

## REST API

Namespace: `wca-email-tester/v1`. All endpoints require the `manage_options` capability.

| Method | Endpoint | Query params | Returns |
|---|---|---|---|
| GET | `/affiliates` | `search`, `per_page` (max 100) | `[{id:int, text:string}, …]` |
| GET | `/referrals` | `search`, `per_page` (max 100) | `[{id:int, text:string}, …]` |
| GET | `/transactions` | `search`, `per_page` (max 100) | `[{id:int, text:string}, …]` |

Responses are Select2-compatible (`{id, text}` pairs).

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
