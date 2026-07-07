<?php
/**
 * Bootstrap-класс: собирает все подсистемы плагина и подключает CSS.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class UZT_Plugin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	private function __construct() {}

	private function hooks() {
		add_action( 'init', array( 'UZT_Shortcodes', 'register' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_menu', array( 'UZT_Admin', 'menu' ) );
		add_action( 'admin_init', array( 'UZT_Admin', 'handle_post' ) );

		// Инвалидация кеша при правке брокера или ранжирования.
		add_action( 'save_post_broker',   array( 'UZT_Repository', 'flush_broker_cache' ), 10, 1 );
		add_action( 'deleted_post',       array( 'UZT_Repository', 'flush_broker_cache' ), 10, 1 );
		add_action( 'uzt/rankings/updated', array( 'UZT_Repository', 'flush_all_cache' ) );
	}

	/**
	 * Один статичный CSS-файл. Подключается только когда шорткод реально стоит на странице,
	 * чтобы не грузить CSS на всём сайте (LiteSpeed объединит его с общими стилями).
	 */
	public function enqueue_assets() {
		global $post;
		$needed = false;
		if ( is_singular() && $post instanceof WP_Post ) {
			if ( has_shortcode( $post->post_content, 'uzt_top_list' )
				|| has_shortcode( $post->post_content, 'uzt_broker_card' )
				|| has_shortcode( $post->post_content, 'uzt_faq' ) ) {
				$needed = true;
			}
		}

		// Позволяем разработчику принудительно подключить CSS (например, в шаблонах темы).
		$needed = apply_filters( 'uzt/enqueue_css', $needed );
		if ( ! $needed ) {
			return;
		}

		wp_enqueue_style(
			'uzt-toplist',
			UZT_PLUGIN_URL . 'assets/css/uzt-toplist.css',
			array(),
			UZT_PLUGIN_VERSION
		);
	}
}
