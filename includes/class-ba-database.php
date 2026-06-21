<?php
/**
 * Database operations for membership applications.
 *
 * @package BotegaAdesioni
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BA_Database
 */
class BA_Database {

	/** Database table name (without prefix). */
	const TABLE_NAME = 'ba_adesioni';

	/** Current schema version. */
	const SCHEMA_VERSION = '1.0';

	/**
	 * Return the full table name including wpdb prefix.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Create the custom database table on activation.
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id           bigint(20)   NOT NULL AUTO_INCREMENT,
			nome         varchar(100) NOT NULL DEFAULT '',
			cognome      varchar(100) NOT NULL DEFAULT '',
			data_nascita date         DEFAULT NULL,
			luogo_nascita varchar(150) NOT NULL DEFAULT '',
			codice_fiscale varchar(16) NOT NULL DEFAULT '',
			indirizzo    varchar(200) NOT NULL DEFAULT '',
			cap          varchar(10)  NOT NULL DEFAULT '',
			citta        varchar(100) NOT NULL DEFAULT '',
			provincia    varchar(5)   NOT NULL DEFAULT '',
			email        varchar(150) NOT NULL DEFAULT '',
			telefono     varchar(30)  NOT NULL DEFAULT '',
			motivazione  text         NOT NULL DEFAULT '',
			accetta_statuto tinyint(1) NOT NULL DEFAULT 0,
			accetta_privacy tinyint(1) NOT NULL DEFAULT 0,
			stato        varchar(20)  NOT NULL DEFAULT 'in_attesa',
			note_admin   text         NOT NULL DEFAULT '',
			ip_address   varchar(45)  NOT NULL DEFAULT '',
			data_invio   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			data_modifica datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY stato (stato),
			KEY email (email),
			KEY data_invio (data_invio)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'ba_db_version', self::SCHEMA_VERSION );
	}

	/**
	 * Drop the table (used by uninstall).
	 */
	public static function drop_table() {
		global $wpdb;
		$table_name = self::get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
		delete_option( 'ba_db_version' );
	}

	/**
	 * Insert a new membership application.
	 *
	 * @param array $data Sanitised data array.
	 * @return int|false New row ID or false on failure.
	 */
	public static function insert_adesione( array $data ) {
		global $wpdb;

		$defaults = array(
			'nome'           => '',
			'cognome'        => '',
			'data_nascita'   => null,
			'luogo_nascita'  => '',
			'codice_fiscale' => '',
			'indirizzo'      => '',
			'cap'            => '',
			'citta'          => '',
			'provincia'      => '',
			'email'          => '',
			'telefono'       => '',
			'motivazione'    => '',
			'accetta_statuto' => 0,
			'accetta_privacy' => 0,
			'stato'          => 'in_attesa',
			'note_admin'     => '',
			'ip_address'     => '',
		);

		$data = wp_parse_args( $data, $defaults );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			self::get_table_name(),
			$data,
			array(
				'%s', // nome
				'%s', // cognome
				'%s', // data_nascita
				'%s', // luogo_nascita
				'%s', // codice_fiscale
				'%s', // indirizzo
				'%s', // cap
				'%s', // citta
				'%s', // provincia
				'%s', // email
				'%s', // telefono
				'%s', // motivazione
				'%d', // accetta_statuto
				'%d', // accetta_privacy
				'%s', // stato
				'%s', // note_admin
				'%s', // ip_address
			)
		);

		if ( false === $result ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get a single membership application by ID.
	 *
	 * @param int $id Application ID.
	 * @return object|null
	 */
	public static function get_adesione( $id ) {
		global $wpdb;
		$table = self::get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
	}

	/**
	 * Update an existing application.
	 *
	 * @param int   $id   Application ID.
	 * @param array $data Fields to update.
	 * @return bool
	 */
	public static function update_adesione( $id, array $data ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			self::get_table_name(),
			$data,
			array( 'id' => $id ),
			null,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete an application.
	 *
	 * @param int $id Application ID.
	 * @return bool
	 */
	public static function delete_adesione( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			self::get_table_name(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Get a list of applications with optional filters and pagination.
	 *
	 * @param array $args Query arguments.
	 * @return array {
	 *     @type array $items  List of application objects.
	 *     @type int   $total  Total number of matching records.
	 * }
	 */
	public static function get_adesioni( array $args = array() ) {
		global $wpdb;

		$defaults = array(
			'stato'    => '',
			'search'   => '',
			'orderby'  => 'data_invio',
			'order'    => 'DESC',
			'per_page' => 20,
			'paged'    => 1,
		);

		$args     = wp_parse_args( $args, $defaults );
		$table    = self::get_table_name();
		$where    = array( '1=1' );
		$values   = array();

		// Filter by status.
		if ( ! empty( $args['stato'] ) ) {
			$where[]  = 'stato = %s';
			$values[] = $args['stato'];
		}

		// Search by name/email/fiscal code.
		if ( ! empty( $args['search'] ) ) {
			$like       = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]    = '( nome LIKE %s OR cognome LIKE %s OR email LIKE %s OR codice_fiscale LIKE %s )';
			$values[]   = $like;
			$values[]   = $like;
			$values[]   = $like;
			$values[]   = $like;
		}

		$where_clause = implode( ' AND ', $where );

		// Validate orderby.
		$allowed_orderby = array( 'id', 'nome', 'cognome', 'email', 'stato', 'data_invio' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'data_invio';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$per_page = max( 1, (int) $args['per_page'] );
		$offset   = max( 0, ( (int) $args['paged'] - 1 ) * $per_page );

		// Get total count.
		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}", $values ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}" );
		}

		// Get items.
		$query_values   = array_merge( $values, array( $per_page, $offset ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				$query_values
			)
		);

		return array(
			'items' => $items ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * Get counts grouped by status.
	 *
	 * @return array Keys are status slugs, values are counts.
	 */
	public static function get_status_counts() {
		global $wpdb;
		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows   = $wpdb->get_results( "SELECT stato, COUNT(*) AS cnt FROM {$table} GROUP BY stato" );
		$counts = array();

		foreach ( $rows as $row ) {
			$counts[ $row->stato ] = (int) $row->cnt;
		}

		return $counts;
	}

	/**
	 * Get all applications for CSV export.
	 *
	 * @param string $stato Filter by status (empty = all).
	 * @return array
	 */
	public static function get_all_for_export( $stato = '' ) {
		global $wpdb;
		$table = self::get_table_name();

		if ( ! empty( $stato ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE stato = %s ORDER BY data_invio DESC", $stato ), ARRAY_A );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY data_invio DESC", ARRAY_A );
	}
}
