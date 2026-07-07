<?php
/**
 * Регистрация шорткодов:
 *   [uzt_top_list region="uz" type="forex" limit="10" show_table="true"]
 *   [uzt_broker_card slug="exness" variant="extended" rank="1" award="..."]
 *   [uzt_faq preset="uz-forex"]
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class UZT_Shortcodes {

	public static function register() {
		add_shortcode( 'uzt_top_list',    array( __CLASS__, 'top_list' ) );
		add_shortcode( 'uzt_broker_card', array( __CLASS__, 'broker_card' ) );
		add_shortcode( 'uzt_faq',         array( __CLASS__, 'faq' ) );
	}

	public static function top_list( $atts ) {
		$atts = shortcode_atts( array(
			'region'     => 'uz',
			'type'       => 'forex',
			'limit'      => 10,
			'show_table' => 'true',
		), $atts, 'uzt_top_list' );

		$region     = sanitize_key( $atts['region'] );
		$type       = sanitize_key( $atts['type'] );
		$limit      = max( 1, (int) $atts['limit'] );
		$show_table = filter_var( $atts['show_table'], FILTER_VALIDATE_BOOLEAN );

		$items = UZT_Repository::get_top_list( $region, $type, $limit );

		if ( empty( $items ) ) {
			return self::empty_state( $region, $type );
		}

		ob_start();
		$context = array(
			'items'      => $items,
			'region'     => $region,
			'type'       => $type,
			'show_table' => $show_table,
		);
		self::render_template( 'top-list.php', $context );

		// JSON-LD ItemList — в том же блоке, чтобы Rich Results Test видел его в исходном HTML.
		echo UZT_Schema::item_list( $items );

		return ob_get_clean();
	}

	public static function broker_card( $atts ) {
		$atts = shortcode_atts( array(
			'slug'    => '',
			'variant' => 'extended',
			'rank'    => '',
			'award'   => '',
			'buzz'    => '',
		), $atts, 'uzt_broker_card' );

		$slug = sanitize_title( $atts['slug'] );
		if ( ! $slug ) return '';

		$broker = UZT_Repository::get_broker_by_slug( $slug );
		if ( ! $broker ) {
			return self::missing_broker_notice( $slug );
		}

		// Переопределяем rank/award/buzz если переданы атрибутами шорткода.
		$broker['rank']  = $atts['rank']  !== '' ? (int) $atts['rank'] : ( isset( $broker['rank'] ) ? $broker['rank'] : 0 );
		$broker['award'] = $atts['award'] !== '' ? wp_strip_all_tags( $atts['award'] ) : ( isset( $broker['award'] ) ? $broker['award'] : '' );
		$broker['buzz']  = $atts['buzz']  !== '' ? wp_strip_all_tags( $atts['buzz'] )  : ( isset( $broker['buzz'] )  ? $broker['buzz']  : '' );

		ob_start();
		self::render_template( 'broker-card.php', array(
			'broker'  => $broker,
			'variant' => $atts['variant'] === 'compact' ? 'compact' : 'extended',
		) );
		return ob_get_clean();
	}

	public static function faq( $atts ) {
		$atts = shortcode_atts( array(
			'preset' => 'uz-forex',
		), $atts, 'uzt_faq' );

		require_once UZT_PLUGIN_DIR . 'includes/faq-presets.php';
		$items = uzt_faq_get_preset( $atts['preset'] );
		if ( empty( $items ) ) return '';

		ob_start();
		self::render_template( 'faq.php', array( 'items' => $items ) );
		echo UZT_Schema::faq_page( $items );
		return ob_get_clean();
	}

	private static function render_template( $file, array $context ) {
		$path = UZT_PLUGIN_DIR . 'templates/' . $file;
		if ( ! file_exists( $path ) ) return;
		extract( $context, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
		include $path;
	}

	private static function empty_state( $region, $type ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<div class="uzt-notice uzt-notice--warn"><strong>Uztrading:</strong> в топ-листе «' . esc_html( $region . '/' . $type ) . '» нет брокеров, залитых в CPT. Проверьте «Узтрейдинг &rarr; Топ-листы» в админке.</div>';
		}
		return '<!-- uzt: empty top list -->';
	}

	private static function missing_broker_notice( $slug ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<div class="uzt-notice uzt-notice--warn"><strong>Uztrading:</strong> брокер <code>' . esc_html( $slug ) . '</code> не найден в CPT <code>broker</code>. Создайте карточку с таким слагом.</div>';
		}
		return '<!-- uzt: broker ' . esc_html( $slug ) . ' not found -->';
	}
}
