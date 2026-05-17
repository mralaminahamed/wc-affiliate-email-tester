# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer install                  # install deps + generate classmap autoloader
composer test                     # run full PHPUnit suite (alias for vendor/bin/phpunit)
composer test:coverage            # HTML coverage report into ./coverage
composer lint                     # PHPCS against ./phpcs.xml.dist (WordPress + WPCS rules)
composer lint:fix                 # auto-fix what phpcbf can
composer make-pot                 # regenerate languages/wc-affiliate-email-tester.pot (requires wp-cli)
vendor/bin/phpunit --filter Foo   # single test method / class
vendor/bin/phpunit tests/php/src/SenderTest.php   # single file
```

Tests require **sibling plugin checkouts** at the same `wp-content/plugins/` level:
- `../woocommerce/`
- `../wc-affiliate/` (with its own `vendor/autoload.php` built)

`tests/php/bootstrap.php` boots WP test suite, loads WC + WC Affiliate + this plugin via `muplugins_loaded`, and re-creates DB tables on `setup_theme`. Set `WP_TESTS_DIR` or `WP_PHPUNIT__DIR` env var to point at your WP test lib.

## Architecture

WordPress plugin that **fires real WC Affiliate email-trigger hooks** and captures the resulting `wp_mail()` calls — it does not re-render templates itself. This is the central design choice; understanding it explains most of the code.

### Boot sequence (`wc-affiliate-email-tester.php`)

1. Composer classmap autoloader (`includes/` → `WCA_Email_Tester_*`).
2. `plugins_loaded` → `wca_et_init()` guards on PHP ≥ 8.0, `class_exists('WooCommerce')`, then `defined('WC_AFFILIATE_VERSION')`. Each failure shows its own admin notice and aborts.
3. `WCA_Email_Tester::instance()` (singleton) hooks `load_textdomain` on `init`, then instantiates `API`, `Logger`, `Admin` in that order.

Activation creates the log table and schedules the hourly cleanup cron; deactivation only unschedules. Uninstall (`uninstall.php`) drops the table only if the `logger_delete_on_uninstall` setting is on.

### Three independent subsystems

**Sender** (`class-wca-email-tester-sender.php`) — the trigger engine.
- `email_types()` is the source of truth for supported emails. Pro types are added only when `WC_AFFILIATE_PRO_VERSION` is defined. Filter: `wca_email_tester_email_types`.
- `field_for_type()` maps each email key to the required form field (`affiliate` | `referral` | `transaction`); the JS uses this to show/hide inputs.
- `send()` attaches transient `wp_mail` filters to capture args + override recipient, fires the matching `do_action(...)`, then detaches. Dry-run blocks dispatch by returning non-null from `pre_wp_mail`. Wraps the dispatch in `wca_email_tester_before_test_send` / `wca_email_tester_after_test_send` actions — Logger uses these to tag rows `test` vs `live`.
- Each `fire_*` method validates inputs, resolves the required model (`WC_Affiliate\Models\Affiliate|Referral|Transaction`) or raw `$wpdb` row, and fires the WC Affiliate action with the exact shape that core's email handlers expect.

**Logger** (`class-wca-email-tester-logger.php`) — separate, persistent capture for *all* `wp_mail()` traffic.
- Attaches `wp_mail` at `PHP_INT_MAX` so it sees the final args (after Sender's overrides).
- Distinguishes test vs live via the `is_test_send` flag toggled by the `wca_email_tester_before/after_test_send` actions.
- Table: `{$wpdb->prefix}wca_et_logs` (created via `dbDelta`). Settings in option `wca_email_tester_settings`. Cleanup cron hook: `wca_email_tester_log_cleanup` (hourly), purges by row count and/or age.
- `get_stats()` returns total/sent/failed/test_count in a single aggregate query — use this from the dashboard rather than four `count_logs()` calls.
- Settings are memoized per request in `$settings_cache`; the cache auto-flushes on `update_option_{OPTION_KEY}` / `add_option_{OPTION_KEY}`. Call `flush_settings_cache()` manually if you bypass `update_option()`.
- All static helpers (`create_table`, `table`, `table_exists`, `drop_table`, `schedule_cleanup`, `unschedule_cleanup`) are used from activation/deactivation/uninstall — keep them callable without an instance.

**Admin** (`class-wca-email-tester-admin.php`) — top-level menu `wca-email-tester` + 4 sub-pages (Dashboard / Testing / Logs / Settings).
- Asset enqueue is gated on `$this->page_hooks` (filled during `register_menus`). Always add new pages through `add_submenu_page()` and push the hook into `$page_hooks` if scripts/styles should load there.
- Select2 is borrowed from WooCommerce: prefers `selectWoo` handle, falls back to `select2`. The active handle is exposed to JS as `wcaetAdmin.select2Handle`.
- Templates live in `templates/admin/*.php` and are loaded via `load_template()` which `extract()`s the args array — keys become local vars inside the template.
- The Logs page handles `view` / `delete` / bulk-delete / clear-all directly in `render_logs()` with nonce checks per action; the list table is `WCA_Email_Tester_Log_List` (extends `WP_List_Table`).

**API** (`class-wca-email-tester-api.php`) — REST namespace `wca-email-tester/v1`, three endpoints (`/affiliates`, `/referrals`, `/transactions`) powering the Select2 dropdowns. All require `manage_options`. Static `get_*_option()` methods are reused by the Admin form to pre-fill Select2 with already-submitted IDs after a POST.

### Constants and conventions

- All plugin paths via `WCA_ET_PATH` / `WCA_ET_URL` / `WCA_ET_FILE`; version via `WCA_ET_VERSION`. Asset cache-busting uses file `mtime`, falling back to `WCA_ET_VERSION`.
- Class file naming: `class-wca-email-tester-{slug}.php` → class `WCA_Email_Tester_{Slug}`. The composer classmap (not PSR-4) means a fresh file requires `composer dump-autoload`.
- Text domain: `wc-affiliate-email-tester`. All user strings must be translated.

### Extending email types

Adding a new WC Affiliate email trigger requires three coordinated edits in `Sender`:
1. Add key + label to `email_types()`.
2. Add key → field requirement in `field_for_type()`.
3. Add a `case` in `fire_hook()` + a `fire_*` dispatcher that resolves the inputs and `do_action()`s the matching core hook.

JS picks up new types automatically from `wcaetAdmin.fieldMap`; no JS changes needed.

### Filter-isolation pattern

When a single core action fan-outs to multiple emails (e.g. `wc_affiliate_affiliate_applied` triggers both the affiliate-facing and admin emails plus verification), Sender disables the sibling emails by adding `__return_false` to their `_enabled` filters at priority 99, then wraps the `do_action()` in `try/finally` so the filters are always cleaned up — including on exception. Mirror this pattern for any new multi-email triggers.

### Hard requirement: paid_referral needs a transaction

`fire_paid_referral` short-circuits with an error if no `WCA\Transaction` exists for the referral's affiliate. WC Affiliate core's email handler dereferences the transaction object — passing `null` would fatal in core. Do not relax this guard without verifying the core handler tolerates a null/stub object.

### Known WCA core bugs worked around in Sender

**`Referral::load()` overwrites `$data['affiliate']` with `Affiliate::to_array()`** (an array instead of a scalar user ID), breaking `get_affiliate_id()` → `(int)[] = 0`. `fire_paid_referral()` uses a `wc_affiliate_referral_data` filter at `PHP_INT_MAX` to restore the scalar user ID during `new WCA_Referral_Model()` construction, then immediately removes the filter.

**`handle_paid_referral_emails()` passes recipients array to `Mailer::send(string $to)`** — a PHP 8 TypeError. `fire_paid_referral()` filters `wc_affiliate_email_affiliate_paid_referral_recipients` to return a string so the affiliate email succeeds, then catches the `TypeError` thrown when the admin email tries to pass `array($email)` to `Mailer::send()`.

**`handle_transaction_emails()` same array-to-string bug** for `transaction_created_admin`. `fire_transaction_created()` adds a priority-9 hook on `wc_affiliate_transaction_after_create` to fire `wp_mail()` directly (before WCA's priority-10 handler), then wraps `do_action()` in `try/catch \TypeError`.

**`compose_payout_processed()` requires `formatted_process_time` key** in the `$transaction_data` array (hardcoded array access with no null-safe). `fire_payout_processed()` builds this key from `$row->process_at` using `date_i18n()`.

**`wca_transactions` has no `type` column** — actual columns are `id, affiliate, amount, payment_method, payment_details, txn_id, status, notes, request_at, process_at`. The API queries use `status`, not `type`.
