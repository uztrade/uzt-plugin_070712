<?php
/**
 * Работа с таблицей ранжирования: create/read/update.
 *
 * Схема:
 *   {$prefix}uzt_rankings
 *     id          INT AUTO_INCREMENT PRIMARY KEY
 *     region      VARCHAR(8)   -- 'uz','kz',...
 *     type        VARCHAR(16)  -- 'forex','crypto'
 *     broker_slug VARCHAR(64)  -- совпадает с post_name у CPT broker
 *     position    INT          -- 1..N
 *     award       VARCHAR(64)  -- бейдж-награда (может быть пустой)
 *     buzz        TEXT         -- короткая фраза под наградой
 *     updated_at  DATETIME     -- когда правили
 *   UNIQUE (region, type, broker_slug)
 *   KEY    (region, type, position)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class UZT_Rankings {

	const TABLE = 'uzt_rankings';
	const DB_VERSION_OPTION = 'uzt_db_version';
	const DB_VERSION = '1.0.0';

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Хук активации плагина. Создаёт таблицу и сидит пилотный топ-10 форекс UZ.
	 */
	public static function activate() {
		self::maybe_create_table();
		self::maybe_seed();
	}

	public static function maybe_create_table() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table  = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			region VARCHAR(8) NOT NULL DEFAULT 'uz',
			type VARCHAR(16) NOT NULL DEFAULT 'forex',
			broker_slug VARCHAR(64) NOT NULL,
			position INT UNSIGNED NOT NULL,
			award VARCHAR(64) DEFAULT NULL,
			buzz TEXT DEFAULT NULL,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY region_type_slug (region, type, broker_slug),
			KEY region_type_position (region, type, position)
		) {$charset};";

		dbDelta( $sql );
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Пилотный топ-10 из Задачи 12.1. Заливается ТОЛЬКО если таблица пустая
	 * — редакторские правки не затираем.
	 */
	public static function maybe_seed() {
		global $wpdb;
		$table = self::table_name();
		$existing = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE region='uz' AND type='forex'" );
		if ( $existing > 0 ) {
			return;
		}

		$seed = self::default_seed_uz_forex();
		foreach ( $seed as $row ) {
			$wpdb->insert(
				$table,
				array(
					'region'      => $row['region'],
					'type'        => $row['type'],
					'broker_slug' => $row['slug'],
					'position'    => (int) $row['position'],
					'award'       => $row['award'],
					'buzz'        => $row['buzz'],
				),
				array( '%s', '%s', '%s', '%d', '%s', '%s' )
			);
		}
	}

	/**
	 * Топ-10 форекс-брокеров для Узбекистана 2026 из Задачи 12.1.
	 * Меняется руками через админку — редакторские правки не затираются.
	 */
	public static function default_seed_uz_forex() {
		return array(
			array( 'region' => 'uz', 'type' => 'forex', 'position' => 1,  'slug' => 'exness',     'award' => 'ВЫБОР УЗБЕКИСТАНА',   'buzz' => 'Высокий рейтинг, низкий вход и быстрый вывод' ),
			array( 'region' => 'uz', 'type' => 'forex', 'position' => 2,  'slug' => 'ic-markets', 'award' => 'НАДЁЖНОСТЬ И ECN',    'buzz' => 'Ультранизкие спреды на Raw Spread' ),
			array( 'region' => 'uz', 'type' => 'forex', 'position' => 3,  'slug' => 'fxpro',      'award' => 'ПРЕМИАЛЬНЫЙ СЕРВИС', 'buzz' => 'NDD-исполнение и сильная регуляция FCA' ),
			array( 'region' => 'uz', 'type' => 'forex', 'position' => 4,  'slug' => 'fpmarkets',  'award' => 'ЛУЧШИЕ СПРЕДЫ',      'buzz' => 'Спреды от 1.17 п. и 10 000+ активов' ),
			array( 'region' => 'uz', 'type' => 'forex', 'position' => 5,  'slug' => 'amarkets',   'award' => 'МГНОВЕННЫЙ ВЫВОД',   'buzz' => 'Быстрое исполнение и сильная поддержка' ),
			array( 'region' => 'uz', 'type' => 'forex', 'position' => 6,  'slug' => 'roboforex',  'award' => 'ДЛЯ НОВИЧКОВ',       'buzz' => 'Центовые счета и депозит от $10' ),
			array( 'region' => 'uz', 'type' => 'forex', 'position' => 7,  'slug' => 'admirals',   'award' => 'ОБУЧЕНИЕ',           'buzz' => 'Сильная регуляция и большой выбор активов' ),
			array( 'region' => 'uz', 'type' => 'forex', 'position' => 8,  'slug' => 'multibank',  'award' => 'ВЫСОКОЕ ПЛЕЧО',      'buzz' => 'Мультирегуляция и страховка $1M' ),
			array( 'region' => 'uz', 'type' => 'forex', 'position' => 9,  'slug' => 'xm-group',   'award' => 'БЕЗ КОМИССИЙ',       'buzz' => 'Минимальный депозит $5 и быстрый вывод' ),
			array( 'region' => 'uz', 'type' => 'forex', 'position' => 10, 'slug' => 'avatrade',   'award' => 'НАДЁЖНАЯ ЛИЦЕНЗИЯ', 'buzz' => 'Соцтрейдинг и уникальная защита AvaProtect' ),
		);
	}

	/**
	 * Получить упорядоченный список брокеров для (region, type).
	 * Возвращает массив: [ [ 'slug' => ..., 'position' => ..., 'award' => ..., 'buzz' => ... ], ... ]
	 */
	public static function get_ranking( $region = 'uz', $type = 'forex', $limit = 10 ) {
		global $wpdb;
		$table = self::table_name();

		$region = sanitize_key( $region );
		$type   = sanitize_key( $type );
		$limit  = max( 1, (int) $limit );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT broker_slug AS slug, position, award, buzz
				   FROM {$table}
				  WHERE region = %s AND type = %s
				  ORDER BY position ASC
				  LIMIT %d",
				$region, $type, $limit
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	public static function get_all_for_admin( $region = 'uz', $type = 'forex' ) {
		global $wpdb;
		$table = self::table_name();
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, broker_slug AS slug, position, award, buzz
				   FROM {$table}
				  WHERE region=%s AND type=%s
				  ORDER BY position ASC",
				$region, $type
			),
			ARRAY_A
		);
	}

	/**
	 * Полная замена ранжирования для пары (region, type).
	 * $items = [ [ 'slug' => ..., 'position' => 1, 'award' => ..., 'buzz' => ... ], ... ]
	 */
	public static function replace_ranking( $region, $type, array $items ) {
		global $wpdb;
		$table = self::table_name();

		$region = sanitize_key( $region );
		$type   = sanitize_key( $type );

		$wpdb->delete( $table, array( 'region' => $region, 'type' => $type ), array( '%s', '%s' ) );

		foreach ( $items as $item ) {
			$slug = isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : '';
			if ( ! $slug ) continue;
			$wpdb->insert(
				$table,
				array(
					'region'      => $region,
					'type'        => $type,
					'broker_slug' => $slug,
					'position'    => isset( $item['position'] ) ? (int) $item['position'] : 999,
					'award'       => isset( $item['award'] ) ? wp_strip_all_tags( (string) $item['award'] ) : null,
					'buzz'        => isset( $item['buzz'] )  ? wp_strip_all_tags( (string) $item['buzz'] )  : null,
				),
				array( '%s', '%s', '%s', '%d', '%s', '%s' )
			);
		}

		do_action( 'uzt/rankings/updated', $region, $type );
	}
}
