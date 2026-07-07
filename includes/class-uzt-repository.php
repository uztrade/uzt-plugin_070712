<?php
/**
 * Слой данных: читает брокеров из CPT `broker` + ACF-полей и объединяет с таблицей ранжирования.
 * Кеширует через transient. Одним batch-запросом получает всех брокеров топ-листа (без N+1).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class UZT_Repository {

	const CACHE_TTL               = HOUR_IN_SECONDS;
	const TRANSIENT_TOP_PREFIX    = 'uzt_top_';
	const TRANSIENT_BROKER_PREFIX = 'uzt_broker_';

	public static function get_top_list( $region = 'uz', $type = 'forex', $limit = 10 ) {
		$region = sanitize_key( $region );
		$type   = sanitize_key( $type );
		$limit  = max( 1, (int) $limit );

		$key    = self::TRANSIENT_TOP_PREFIX . $region . '_' . $type . '_' . $limit;
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$ranking = UZT_Rankings::get_ranking( $region, $type, $limit );
		if ( empty( $ranking ) ) {
			set_transient( $key, array(), self::CACHE_TTL );
			return array();
		}

		$slugs = array_map( function ( $r ) { return $r['slug']; }, $ranking );
		$posts = get_posts( array(
			'post_type'      => 'broker',
			'post_status'    => array( 'publish' ),
			'post_name__in'  => $slugs,
			'posts_per_page' => count( $slugs ),
			'no_found_rows'  => true,
			'orderby'        => 'post_name__in',
		) );

		$by_slug = array();
		foreach ( $posts as $post ) {
			$by_slug[ $post->post_name ] = self::hydrate_broker( $post );
		}

		$result = array();
		foreach ( $ranking as $rank_row ) {
			$slug = $rank_row['slug'];
			if ( ! isset( $by_slug[ $slug ] ) ) continue;

			$broker          = $by_slug[ $slug ];
			$broker['rank']  = (int) $rank_row['position'];
			$broker['award'] = (string) $rank_row['award'];
			$broker['buzz']  = (string) $rank_row['buzz'];
			$result[]        = $broker;
		}

		set_transient( $key, $result, self::CACHE_TTL );
		return $result;
	}

	public static function get_broker_by_slug( $slug ) {
		$slug = sanitize_title( $slug );
		if ( ! $slug ) return null;

		$key    = self::TRANSIENT_BROKER_PREFIX . $slug;
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$posts = get_posts( array(
			'post_type'      => 'broker',
			'name'           => $slug,
			'post_status'    => array( 'publish' ),
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		) );
		if ( empty( $posts ) ) {
			return null;
		}
		$data = self::hydrate_broker( $posts[0] );
		set_transient( $key, $data, self::CACHE_TTL );
		return $data;
	}

	private static function hydrate_broker( WP_Post $post ) {
		$id  = (int) $post->ID;
		$acf = function_exists( 'get_field' );

		$g = function ( $key, $default = '' ) use ( $id, $acf ) {
			if ( $acf ) {
				$v = get_field( $key, $id );
				return ( $v === false || $v === null ) ? $default : $v;
			}
			$v = get_post_meta( $id, $key, true );
			return ( $v === '' || $v === false || $v === null ) ? $default : $v;
		};

		$slug = $post->post_name;

		$logo_compact = self::normalize_image( $g( 'broker_logo_compact', '' ) );
		$logo_wide    = self::normalize_image( $g( 'broker_logo_wide', '' ) );

		$affiliate = trim( (string) $g( 'broker_affiliate_url', '' ) );
		if ( ! $affiliate ) {
			$affiliate = home_url( '/go/' . $slug . '/' );
		}

		// --- Платформы: ACF checkbox → массив меток + авто-вырезание "Мобильного приложения" ---
		$platforms_raw = $g( 'broker_platforms', array() );
		$platforms_all = array();
		if ( is_array( $platforms_raw ) ) {
			foreach ( $platforms_raw as $p ) {
				$platforms_all[] = is_array( $p )
					? ( isset( $p['label'] ) ? $p['label'] : implode( ' ', $p ) )
					: (string) $p;
			}
		} elseif ( is_string( $platforms_raw ) && $platforms_raw !== '' ) {
			$platforms_all = array_map( 'trim', explode( ',', $platforms_raw ) );
		}

		// Если среди платформ есть "Мобильное приложение" / "Mobile app" — вырезаем и запоминаем
		$has_mobile_from_platforms = false;
		$platforms = array();
		foreach ( $platforms_all as $p ) {
			if ( preg_match( '/мобил|mobile\s*app/iu', $p ) ) {
				$has_mobile_from_platforms = true;
				continue;
			}
			$platforms[] = $p;
		}

		// has_mobile_app: если в ACF явно стоит false — верим; иначе true (по умолчанию все есть)
		$mobile_raw = $g( 'broker_has_mobile_app', null );
		if ( $mobile_raw === null || $mobile_raw === '' ) {
			$has_mobile_app = true; // умолчание для брокеров топа
		} else {
			$has_mobile_app = self::to_bool( $mobile_raw );
		}
		if ( $has_mobile_from_platforms ) {
			$has_mobile_app = true;
		}

		// --- Классы активов (Форекс, Индексы, ...) — новое ACF-поле ---
		$asset_classes_raw = $g( 'broker_asset_classes', array() );
		$asset_classes = array();
		if ( is_array( $asset_classes_raw ) ) {
			foreach ( $asset_classes_raw as $a ) {
				if ( is_array( $a ) ) {
					$asset_classes[] = isset( $a['label'] ) ? $a['label'] : ( isset( $a['value'] ) ? $a['value'] : implode( ' ', $a ) );
				} else {
					// Если ACF вернул value-ключи, конвертируем в человеческие метки
					$asset_classes[] = self::asset_class_label( (string) $a );
				}
			}
		} elseif ( is_string( $asset_classes_raw ) && $asset_classes_raw !== '' ) {
			foreach ( array_map( 'trim', explode( ',', $asset_classes_raw ) ) as $a ) {
				$asset_classes[] = self::asset_class_label( $a );
			}
		}

		// --- Количество инструментов (broker_instruments) — оставляем как отдельное поле ---
		$instruments_txt = trim( (string) $g( 'broker_instruments', '' ) );

		// FAQ на брокере (8 пар из ACF).
		$faq = array();
		for ( $i = 1; $i <= 8; $i++ ) {
			$q = trim( (string) $g( "faq_{$i}_q", '' ) );
			$a = trim( (string) $g( "faq_{$i}_a", '' ) );
			if ( $q !== '' && $a !== '' ) {
				$faq[] = array( 'q' => $q, 'a' => $a );
			}
		}

		return array(
			'id'                  => $id,
			'name'                => get_the_title( $id ),
			'slug'                => $slug,
			'permalink'           => get_permalink( $id ),
			'logo_compact'        => $logo_compact,
			'logo_wide'           => $logo_wide,
			'rating'              => (float) $g( 'broker_rating', 0 ),
			'data_status'         => (string) $g( 'broker_status', '' ),
			'affiliate_url'       => $affiliate,
			'official_url'        => (string) $g( 'broker_official_url', '' ),
			'pros'                => self::to_list( $g( 'broker_pros', array() ) ),
			'cons'                => self::to_list( $g( 'broker_cons', array() ) ),

			'legal_entity'        => (string) $g( 'broker_legal_entity', '' ),
			'founded'             => (string) $g( 'broker_founded', '' ),
			'countries'           => (string) $g( 'broker_countries', '' ),
			'regulators'          => (string) $g( 'broker_regulators', '' ),
			'tier1'               => self::to_bool( $g( 'broker_tier1', false ) ),
			'compensation_fund'   => (string) $g( 'broker_compensation_fund', '' ),
			'segregation'         => self::to_bool( $g( 'broker_segregation', false ) ),
			'kyc'                 => self::to_bool( $g( 'broker_kyc', false ) ),
			'risk_flags'          => (string) $g( 'broker_risk_flags', '' ),

			'min_deposit'         => (string) $g( 'broker_min_deposit', '' ),
			'max_leverage'        => (string) $g( 'broker_max_leverage', '' ),
			'base_currencies'     => (string) $g( 'broker_base_currencies', '' ),
			'spread_eurusd_std'   => (string) $g( 'broker_spread_eurusd_std', '' ),
			'spread_eurusd_ecn'   => (string) $g( 'broker_spread_eurusd_ecn', '' ),
			'commission_ecn'      => (string) $g( 'broker_commission_ecn', '' ),
			'account_types'       => (string) $g( 'broker_account_types', '' ),
			'swap_free'           => self::to_bool( $g( 'broker_swap_free', false ) ),
			'pairs'               => (string) $g( 'broker_pairs', '' ),
			'instruments_txt'     => $instruments_txt,  // "239 (Web) — 348 (MT5)"
			'asset_classes'       => $asset_classes,     // ["Форекс", "Индексы", "Крипта", ...]

			'platforms'           => $platforms,          // Без "Мобильного приложения"
			'copytrading'         => self::to_bool( $g( 'broker_copytrading', false ) ),
			'deposit_methods'     => (string) $g( 'broker_deposit_methods', '' ),
			'crypto_deposit'      => self::to_bool( $g( 'broker_crypto_deposit', false ) ),
			'withdraw_time'       => (string) $g( 'broker_withdraw_time', '' ),
			'trustpilot'          => (string) $g( 'broker_trustpilot', '' ),
			'aggregator_rating'   => (string) $g( 'broker_aggregator_rating', '' ),
			'loss_pct'            => (string) $g( 'broker_loss_pct', '' ),
			'languages'           => (string) $g( 'broker_languages', '' ),
			'has_mobile_app'      => $has_mobile_app,

			'faq'                 => $faq,
		);
	}

	/** Конвертирует value-ключ класса активов в русскую метку. */
	private static function asset_class_label( $key ) {
		$key = strtolower( trim( $key ) );
		$map = array(
			'forex'       => 'Форекс',
			'indices'     => 'Индексы',
			'stocks'      => 'Акции',
			'crypto'      => 'Криптовалюта',
			'metals'      => 'Металлы',
			'commodities' => 'Сырьё',
			'etf'         => 'ETF',
			'cfd'         => 'CFD',
		);
		return isset( $map[ $key ] ) ? $map[ $key ] : ucfirst( $key );
	}

	public static function normalize_image( $img ) {
		$default = array( 'url' => '', 'width' => 0, 'height' => 0, 'alt' => '' );
		if ( empty( $img ) ) return $default;

		if ( is_array( $img ) ) {
			return array(
				'url'    => isset( $img['url'] ) ? $img['url'] : '',
				'width'  => isset( $img['width'] )  ? (int) $img['width']  : 0,
				'height' => isset( $img['height'] ) ? (int) $img['height'] : 0,
				'alt'    => isset( $img['alt'] ) ? $img['alt'] : '',
			);
		}
		if ( is_numeric( $img ) ) {
			$id  = (int) $img;
			$url = wp_get_attachment_image_url( $id, 'full' );
			$m   = wp_get_attachment_metadata( $id );
			return array(
				'url'    => $url ?: '',
				'width'  => $m && ! empty( $m['width'] )  ? (int) $m['width']  : 0,
				'height' => $m && ! empty( $m['height'] ) ? (int) $m['height'] : 0,
				'alt'    => get_post_meta( $id, '_wp_attachment_image_alt', true ) ?: '',
			);
		}
		$id = attachment_url_to_postid( (string) $img );
		$m  = $id ? wp_get_attachment_metadata( $id ) : null;
		return array(
			'url'    => (string) $img,
			'width'  => $m && ! empty( $m['width'] )  ? (int) $m['width']  : 0,
			'height' => $m && ! empty( $m['height'] ) ? (int) $m['height'] : 0,
			'alt'    => '',
		);
	}

	private static function to_bool( $v ) {
		return ( $v === true || $v === 1 || $v === '1' || $v === 'yes' || $v === 'Да' || $v === 'true' );
	}

	public static function to_list( $v ) {
		$items = array();
		if ( is_array( $v ) ) {
			foreach ( $v as $row ) {
				if ( is_array( $row ) ) {
					if ( isset( $row['text'] ) ) $items[] = (string) $row['text'];
					elseif ( isset( $row['item'] ) ) $items[] = (string) $row['item'];
					else $items[] = implode( ' ', $row );
				} else {
					$items[] = (string) $row;
				}
			}
		} elseif ( is_string( $v ) && $v !== '' ) {
			foreach ( preg_split( "/\r\n|\r|\n|;/", $v ) as $line ) {
				$line = trim( $line );
				if ( $line !== '' ) $items[] = $line;
			}
		}
		return array_values( array_filter( array_map( 'trim', $items ), function ( $x ) { return $x !== ''; } ) );
	}

	public static function flush_all_cache() {
		global $wpdb;
		$prefixes = array(
			'_transient_' . self::TRANSIENT_TOP_PREFIX,
			'_transient_timeout_' . self::TRANSIENT_TOP_PREFIX,
			'_transient_' . self::TRANSIENT_BROKER_PREFIX,
			'_transient_timeout_' . self::TRANSIENT_BROKER_PREFIX,
		);
		foreach ( $prefixes as $p ) {
			$like = $wpdb->esc_like( $p ) . '%';
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		}
	}

	public static function flush_broker_cache( $post_id ) {
		global $wpdb;
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'broker' ) return;

		delete_transient( self::TRANSIENT_BROKER_PREFIX . $post->post_name );

		$table = UZT_Rankings::table_name();
		$pairs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT region, type FROM {$table} WHERE broker_slug = %s",
				$post->post_name
			),
			ARRAY_A
		);
		if ( empty( $pairs ) ) return;

		foreach ( $pairs as $p ) {
			$like = $wpdb->esc_like( '_transient_' . self::TRANSIENT_TOP_PREFIX . $p['region'] . '_' . $p['type'] . '_' ) . '%';
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
			$like2 = $wpdb->esc_like( '_transient_timeout_' . self::TRANSIENT_TOP_PREFIX . $p['region'] . '_' . $p['type'] . '_' ) . '%';
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like2 ) );
		}
	}
}