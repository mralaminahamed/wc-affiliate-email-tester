# Changelog

All notable changes to WC Affiliate Email Tester are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] — 2026-04-22

### Added
- Top-level admin menu with four sub-pages: Dashboard, Testing, Logs, Settings
- Send form supporting all 13 WC Affiliate core email types
- Auto-detection of WC Affiliate Pro — adds MLC commission, signup bonus, and referral bonus types
- Dry-run mode: captures fully-rendered email without dispatching via `wp_mail()`
- Override recipient: redirects all test sends to a specified address
- In-browser HTML preview via sandboxed `<iframe>`
- Collapsible source and headers panels per captured email
- Multi-email tab UI when a single trigger dispatches more than one message
- Unresolved placeholder detector (`%%token%%` warnings)
- Email logger: captures every `wp_mail()` call with status, recipient, subject, body, and headers
- Log list page using `WP_List_Table` with status/source badges, search, bulk delete
- Log detail view with full preview, source, and headers
- Log retention: purge by maximum count or by age (hourly cron)
- Settings page: logging toggle, test email capture, retention rules, uninstall option
- REST API endpoints: `GET /wca-email-tester/v1/affiliates|referrals|transactions`
- Select2-powered affiliate, referral, and transaction search dropdowns
- Live log count badge on the Logs submenu item
- Composer classmap autoloader with `optimize-autoloader`
- PHPUnit test suite: `SenderTest`, `LoggerTest`, `ApiTest`
