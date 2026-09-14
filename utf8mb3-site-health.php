<?php
/**
 * Plugin Name:       UTF8MB3 Site Health Check
 * Description:       Adds a Site Health check for database columns that still use the utf8mb3 character set.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Torsten Landsiedel
 * Text Domain:       utf8mb3-site-health
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the Site Health test.
 *
 * @param array $tests Site Health tests.
 * @return array
 */
function utf8mb3_site_health_add_test( $tests ) {
	$tests['direct']['utf8mb3_site_health_database_charset'] = array(
		'label' => __( 'Database character set', 'utf8mb3-site-health' ),
		'test'  => 'utf8mb3_site_health_test_database_charset',
	);

	return $tests;
}
add_filter( 'site_status_tests', 'utf8mb3_site_health_add_test' );

/**
 * Get database columns that still use the utf8mb3 character set.
 *
 * MySQL and MariaDB historically used "utf8" as the name for the
 * three-byte UTF-8 character set. Newer versions call it "utf8mb3".
 *
 * The check is performed on individual columns instead of table collations,
 * since columns may use a different character set than their table default.
 *
 * @return object[]|WP_Error Array of affected columns on success, or a WP_Error
 *                           object if the database query failed.
 */
function utf8mb3_site_health_get_utf8mb3_columns() {
	global $wpdb;

	/*
	 * Suppress the database error from being displayed, since Site Health
	 * should report the problem instead.
	 */
	$suppress_errors = $wpdb->suppress_errors();

	$columns = $wpdb->get_results(
		$wpdb->prepare(
			"
			SELECT
				TABLE_NAME,
				COLUMN_NAME,
				CHARACTER_SET_NAME,
				COLLATION_NAME
			FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = %s
				AND TABLE_NAME LIKE %s
				AND CHARACTER_SET_NAME IN ( 'utf8', 'utf8mb3' )
			ORDER BY TABLE_NAME, ORDINAL_POSITION
			",
			DB_NAME,
			$wpdb->esc_like( $wpdb->base_prefix ) . '%'
		)
	);

	$database_error = $wpdb->last_error;

	$wpdb->suppress_errors( $suppress_errors );

	if ( '' !== $database_error ) {
		return new WP_Error(
			'utf8mb3_site_health_database_query_failed',
			$database_error
		);
	}

	return $columns;
}

/**
 * Check for database columns using utf8mb3.
 *
 * @return array Site Health test result.
 */
function utf8mb3_site_health_test_database_charset() {
	$columns = utf8mb3_site_health_get_utf8mb3_columns();

	/*
	 * The database character sets could not be determined.
	 */
	if ( is_wp_error( $columns ) ) {
		return array(
			'label'       => __( 'Database character sets could not be determined', 'utf8mb3-site-health' ),
			'status'      => 'recommended',
			'badge'       => array(
				'label' => __( 'Database', 'utf8mb3-site-health' ),
				'color' => 'blue',
			),
			'description' => sprintf(
				'<p>%s</p>',
				__(
					'WordPress could not determine the character sets used by the database columns. The database server may not allow access to the information schema.',
					'utf8mb3-site-health'
				)
			),
			'actions'     => '',
			'test'        => 'utf8mb3_site_health_database_charset',
		);
	}

	/*
	 * No utf8mb3 columns were found.
	 */
	if ( empty( $columns ) ) {
		return array(
			'label'       => __( 'All database columns support four-byte Unicode characters', 'utf8mb3-site-health' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Database', 'utf8mb3-site-health' ),
				'color' => 'blue',
			),
			'description' => sprintf(
				'<p>%s</p>',
				__(
					'No WordPress database columns using the legacy three-byte UTF-8 character set were found.',
					'utf8mb3-site-health'
				)
			),
			'actions'     => '',
			'test'        => 'utf8mb3_site_health_database_charset',
		);
	}

	/*
	 * Group affected columns by table for the Site Health output.
	 */
	$tables = array();

	foreach ( $columns as $column ) {
		if ( ! isset( $tables[ $column->TABLE_NAME ] ) ) {
			$tables[ $column->TABLE_NAME ] = array();
		}

		$tables[ $column->TABLE_NAME ][] = $column;
	}

	$table_count  = count( $tables );
	$column_count = count( $columns );

	$description = sprintf(
		'<p>%s</p>',
		__(
			'Some database columns still use the legacy three-byte UTF-8 character set. These columns cannot store all Unicode characters, including many emoji and other characters outside the Basic Multilingual Plane.',
			'utf8mb3-site-health'
		)
	);

	$description .= sprintf(
		'<p>%s</p>',
		sprintf(
			/* translators: 1: Number of database tables. 2: Number of database columns. */
			_n(
				'%1$d database table contains %2$d affected column:',
				'%1$d database tables contain %2$d affected columns:',
				$table_count,
				'utf8mb3-site-health'
			),
			$table_count,
			$column_count
		)
	);

	$description .= '<ul>';

	foreach ( $tables as $table_name => $table_columns ) {
		$description .= sprintf(
			'<li><code>%s</code><ul>',
			esc_html( $table_name )
		);

		foreach ( $table_columns as $column ) {
			$description .= sprintf(
				'<li><code>%1$s</code> — <code>%2$s</code></li>',
				esc_html( $column->COLUMN_NAME ),
				esc_html( $column->COLLATION_NAME )
			);
		}

		$description .= '</ul></li>';
	}

	$description .= '</ul>';

	$description .= sprintf(
		'<p>%s</p>',
		__(
			'Consider converting these columns to utf8mb4 after creating a complete database backup and verifying that the affected indexes are compatible with the new character set.',
			'utf8mb3-site-health'
		)
	);

	return array(
		'label'       => sprintf(
			/* translators: %d: Number of database columns. */
			_n(
				'%d database column still uses utf8mb3',
				'%d database columns still use utf8mb3',
				$column_count,
				'utf8mb3-site-health'
			),
			$column_count
		),
		'status'      => 'recommended',
		'badge'       => array(
			'label' => __( 'Database', 'utf8mb3-site-health' ),
			'color' => 'blue',
		),
		'description' => $description,
		'actions'     => '',
		'test'        => 'utf8mb3_site_health_database_charset',
	);
}
