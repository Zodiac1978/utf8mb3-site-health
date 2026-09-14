# UTF8MB3 Site Health Check

Adds a Site Health check for WordPress database columns that still use the legacy `utf8mb3` character set.

## What does UTF8MB3 Site Health Check do?

MySQL and MariaDB's `utf8` character set, now also called `utf8mb3`, can store only three bytes per character. It therefore cannot represent every Unicode character, including many emoji and other characters outside the Basic Multilingual Plane.

This plugin adds a direct test under **Tools > Site Health**. It checks individual columns in WordPress database tables for the `utf8` or `utf8mb3` character set. When affected columns are found, the result groups them by table and displays each column's current collation. If none are found, Site Health confirms that all checked columns support four-byte Unicode characters.

The check operates on columns rather than table defaults because individual columns can use a different character set from their table.

## Safety

The plugin is read-only. It reports affected columns but does not alter the database or convert data automatically.

Before converting columns to `utf8mb4`, create a complete database backup and verify that affected indexes are compatible with the new character set.

## Requirements

- WordPress 6.0 or later
- PHP 7.4 or later
- A MySQL or MariaDB database that permits reading `information_schema.COLUMNS`
