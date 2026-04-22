=== WC Affiliate Email Tester ===
Contributors: mralaminahamed
Tags: wc-affiliate, email, testing, developer, woocommerce
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Developer tool to test, preview, and debug WC Affiliate email notifications without real triggers or live SMTP.

== Description ==

WC Affiliate Email Tester lets developers and store owners fire, preview, and inspect every WC Affiliate email notification against real data — without needing actual affiliate actions or a live mail server.

**Key features:**

* Fire any WC Affiliate email against a real affiliate, referral, or transaction in one click
* Dry-run mode — capture and preview the fully-rendered email without dispatching it
* Override recipient — redirect test sends to a safe address
* Email logging — capture every outgoing `wp_mail()` call with status, headers, and full body
* Log retention — auto-purge by count or by age
* In-browser preview — renders full HTML emails inside a sandboxed iframe
* Source and headers inspector — collapsible panels for raw HTML and mail headers
* WC Affiliate Pro support — MLC commission, signup bonus, and referral bonus email types
* REST API — powers the affiliate/referral/transaction search dropdowns

== Installation ==

1. Upload the plugin folder to `wp-content/plugins/`
2. Run `composer install` inside the plugin folder (developer install)
3. Activate via **Plugins → Installed Plugins**
4. Go to **WCA Email Tester** in the admin sidebar

== Frequently Asked Questions ==

= Does this send real emails? =

By default yes — it fires real WC Affiliate email hooks which dispatch via `wp_mail()`. Enable **Dry Run** on the testing page to capture and preview without sending.

= Does it require WC Affiliate Pro? =

No. The plugin works with WC Affiliate core. Pro email types are automatically added when WC Affiliate Pro is detected.

= Is it safe to use on a production site? =

Yes. Use the **Override Recipient** field to redirect all test sends to your own address, and enable **Dry Run** to avoid sending anything at all.

== Screenshots ==

1. Dashboard — stats overview and quick links
2. Testing — send form with affiliate, referral, and transaction selectors
3. Email result — iframe preview with source and headers panels
4. Logs — WP_List_Table with status and source badges
5. Settings — logging, retention, and uninstall options

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.
