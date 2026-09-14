=== UTF8MB3 Site Health Check ===
Contributors: zodiac1978
Tags: site health, database, utf8mb3, utf8mb4, unicode
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a Site Health check that finds WordPress database columns still using the legacy utf8mb3 character set.

== Description ==

UTF8MB3 Site Health Check adds a direct test to **Tools > Site Health**. The test examines individual columns in WordPress database tables and reports columns that use MySQL or MariaDB's legacy three-byte UTF-8 character set, named `utf8` or `utf8mb3`.

Unlike `utf8mb4`, utf8mb3 cannot store every Unicode character. This affects many emoji and other characters outside the Basic Multilingual Plane.

When affected columns are found, the Site Health result groups them by table and shows each column's current collation. If none are found, the test confirms that all checked columns support four-byte Unicode characters.

The plugin is read-only: it reports affected columns but does not modify the database. Before converting any columns to `utf8mb4`, create a complete database backup and verify that affected indexes are compatible with the new character set.

== Installation ==

1. Upload the `utf8mb3-site-health` directory to the `/wp-content/plugins/` directory, or install the plugin through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Tools > Site Health** and review the **Database character set** test.

== Frequently Asked Questions ==

= Does this plugin convert database columns to utf8mb4? =

No. The plugin only identifies and reports affected columns. It never changes your database schema or data.

= Why does the check look for both utf8 and utf8mb3? =

MySQL and MariaDB historically used `utf8` as the name for their three-byte UTF-8 character set. Newer versions also identify it explicitly as `utf8mb3`.

= Why can the test not determine the database character sets? =

The database server may not allow access to `information_schema.COLUMNS`. In that case, Site Health displays a recommended-action result instead of treating the check as passed or failed.

= Which tables are checked? =

The plugin checks tables in the current WordPress database whose names begin with the configured WordPress base table prefix. It checks individual text columns because their character sets can differ from a table's default character set.

== Changelog ==

= 0.1.0 =

* Initial release.
* Add a Site Health test for database columns using utf8 or utf8mb3.

