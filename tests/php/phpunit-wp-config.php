<?php

// Resolve WordPress root: two levels up from plugins dir, or one from the standard structure.
$wordpress_dir = dirname( __DIR__, 4 ) . '/';

/* Path to the WordPress codebase you'd like to test. Add a forward slash in the end. */
define( 'ABSPATH', $wordpress_dir );

define( 'WP_DEFAULT_THEME', 'default' );

define( 'WP_DEBUG', true );

define( 'DB_NAME', getenv( 'WP_DB_NAME' ) ?: 'wp_phpunit_tests' );
define( 'DB_USER', getenv( 'WP_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_DB_PASS' ) ?: 'password' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

define( 'AUTH_KEY',         'wca-et-test-auth-key' );
define( 'SECURE_AUTH_KEY',  'wca-et-test-secure-auth-key' );
define( 'LOGGED_IN_KEY',    'wca-et-test-logged-in-key' );
define( 'NONCE_KEY',        'wca-et-test-nonce-key' );
define( 'AUTH_SALT',        'wca-et-test-auth-salt' );
define( 'SECURE_AUTH_SALT', 'wca-et-test-secure-auth-salt' );
define( 'LOGGED_IN_SALT',   'wca-et-test-logged-in-salt' );
define( 'NONCE_SALT',       'wca-et-test-nonce-salt' );

$table_prefix = 'unit_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

define( 'WP_PHP_BINARY', 'php' );

define( 'WPLANG', '' );
