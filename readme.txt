=== WP AutoOps Agent ===
Contributors: aplactuan
Tags: monitoring, maintenance, remote management, autoops
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Remote monitoring and maintenance agent for WP AutoOps.

== Description ==

WP AutoOps Agent is the WordPress-side agent for WP AutoOps. It provides the foundation for remote site status reporting and maintenance jobs.

This release contains the plugin bootstrap only: environment checks, core classes, and extension points. REST endpoints and business logic will ship in later versions.

== Installation ==

1. Upload the `wp-autoops-agent` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Confirm your site runs PHP 7.4+ and WordPress 6.5+.

== Frequently Asked Questions ==

= What does this version do? =

It provides authenticated ping and site status endpoints for WP AutoOps. No settings UI is included yet.

= What are the minimum requirements? =

PHP 7.4 or higher and WordPress 6.5 or higher.

== Changelog ==

= 0.1.0 =
* Initial plugin bootstrap.
* Activation checks for PHP 7.4+ and WordPress 6.5+.
* Core loader, plugin, API, auth, response, status, and job service scaffolds.
* Authenticated ping and provider-based status endpoints.

== Upgrade Notice ==

= 0.1.0 =
Initial release of the WP AutoOps Agent bootstrap.
