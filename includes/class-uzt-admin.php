<?php
/**
 * Мини-админка: «Узтрейдинг → Топ-листы». Позволяет редактору:
 *   - видеть текущее ранжирование для (region, type);
 *   - править порядок, award, buzz без правки кода;
 *   - чистить кеш одной кнопкой.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class UZT_Admin {

	const CAP  = 'edit_others_posts';
	const SLUG = 'uzt-top-lists';

	public static function menu() {
		add_menu_page(
			'Узтрейдинг',
			'Узтрейдинг',
			self::CAP,
			self::SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-chart-line',
			58
		);
	}

	public static function handle_post() {
		if ( empty( $_POST['uzt_action'] ) ) return;
		if ( ! current_user_can( self::CAP ) ) return;
		if ( empty( $_POST['_uzt_nonce'] ) || ! wp_verify_nonce( $_POST['_uzt_nonce'], 'uzt_save' ) ) return;

		$action = sanitize_key( wp_unslash( $_POST['uzt_action'] ) );
		$region = isset( $_POST['region'] ) ? sanitize_key( wp_unslash( $_POST['region'] ) ) : 'uz';
		$type   = isset( $_POST['type'] )   ? sanitize_key( wp_unslash( $_POST['type'] ) )   : 'forex';

		if ( 'save_ranking' === $action ) {
			$rows = isset( $_POST['rank'] ) && is_array( $_POST['rank'] ) ? wp_unslash( $_POST['rank'] ) : array();
			$items = array();
			foreach ( $rows as $row ) {
				$slug = isset( $row['slug'] ) ? sanitize_title( $row['slug'] ) : '';
				if ( ! $slug ) continue;
				$items[] = array(
					'slug'     => $slug,
					'position' => isset( $row['position'] ) ? (int) $row['position'] : 999,
					'award'    => isset( $row['award'] ) ? wp_strip_all_tags( (string) $row['award'] ) : '',
					'buzz'     => isset( $row['buzz'] )  ? wp_strip_all_tags( (string) $row['buzz'] )  : '',
				);
			}
			UZT_Rankings::replace_ranking( $region, $type, $items );
			wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'region' => $region, 'type' => $type, 'saved' => 1 ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( 'flush_cache' === $action ) {
			UZT_Repository::flush_all_cache();
			wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'region' => $region, 'type' => $type, 'flushed' => 1 ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	public static function render_page() {
		if ( ! current_user_can( self::CAP ) ) return;

		$region = isset( $_GET['region'] ) ? sanitize_key( $_GET['region'] ) : 'uz';
		$type   = isset( $_GET['type'] )   ? sanitize_key( $_GET['type'] )   : 'forex';

		$rows = UZT_Rankings::get_all_for_admin( $region, $type );
		$brokers_index = self::index_brokers();

		echo '<div class="wrap"><h1>Uztrading &mdash; Топ-листы</h1>';

		if ( isset( $_GET['saved'] ) )   echo '<div class="notice notice-success is-dismissible"><p>Порядок сохранён, кеш сброшен.</p></div>';
		if ( isset( $_GET['flushed'] ) ) echo '<div class="notice notice-success is-dismissible"><p>Кеш сброшен.</p></div>';

		echo '<form method="get" style="margin:16px 0">';
		echo '<input type="hidden" name="page" value="' . esc_attr( self::SLUG ) . '">';
		echo '<label>Регион: <input name="region" value="' . esc_attr( $region ) . '" size="4"></label> ';
		echo '<label>Тип: <input name="type" value="' . esc_attr( $type ) . '" size="8"></label> ';
		echo '<button class="button">Показать</button>';
		echo '</form>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ) . '">';
		wp_nonce_field( 'uzt_save', '_uzt_nonce' );
		echo '<input type="hidden" name="region" value="' . esc_attr( $region ) . '">';
		echo '<input type="hidden" name="type" value="' . esc_attr( $type ) . '">';

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th style="width:70px">#</th><th style="width:220px">Slug (post_name)</th><th style="width:220px">Награда (award)</th><th>Buzz — короткая фраза</th><th style="width:220px">CPT `broker`</th>';
		echo '</tr></thead><tbody>';

		// Показываем текущие строки + 3 дополнительные пустые для возможности добавить новые.
		$i = 0;
		$total = max( count( $rows ) + 3, 13 );
		for ( $n = 0; $n < $total; $n++ ) {
			$row = isset( $rows[ $n ] ) ? $rows[ $n ] : array( 'slug' => '', 'position' => $n + 1, 'award' => '', 'buzz' => '' );
			$slug = (string) $row['slug'];
			$in_cpt = $slug && isset( $brokers_index[ $slug ] );

			echo '<tr>';
			echo '<td><input type="number" name="rank[' . $n . '][position]" value="' . esc_attr( $row['position'] ) . '" min="0" max="999" style="width:70px"></td>';
			echo '<td><input type="text"   name="rank[' . $n . '][slug]" value="' . esc_attr( $slug ) . '" style="width:100%" placeholder="exness"></td>';
			echo '<td><input type="text"   name="rank[' . $n . '][award]" value="' . esc_attr( (string) $row['award'] ) . '" style="width:100%" placeholder="ВЫБОР УЗБЕКИСТАНА"></td>';
			echo '<td><input type="text"   name="rank[' . $n . '][buzz]" value="' . esc_attr( (string) $row['buzz'] ) . '" style="width:100%"></td>';
			echo '<td>';
			if ( $slug === '' ) {
				echo '<span style="color:#888">&mdash;</span>';
			} elseif ( $in_cpt ) {
				echo '<span style="color:#0A7A3B">✔ ' . esc_html( $brokers_index[ $slug ] ) . '</span>';
			} else {
				echo '<span style="color:#B42318">❌ не залит</span>';
			}
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '<p style="margin-top:16px"><button class="button button-primary" name="uzt_action" value="save_ranking">Сохранить и сбросить кеш</button> ';
		echo '<button class="button" name="uzt_action" value="flush_cache">Только сбросить кеш</button></p>';
		echo '</form>';

		echo '<h2>Справка</h2>';
		echo '<ul style="list-style:disc;margin-left:20px">';
		echo '<li><code>Slug</code> — это адрес брокера в админке: <code>/brokers/{slug}/</code> (например <code>exness</code>, не <code>Exness</code>).</li>';
		echo '<li>Незаполненные строки автоматически очищаются при сохранении.</li>';
		echo '<li>Как только вы зальёте карточку брокера в разделе <em>Брокеры</em> (CPT <code>broker</code>) с нужным slug’ом, она автоматически появится на пилотной странице.</li>';
		echo '<li>Шорткод для страницы: <code>[uzt_top_list region="uz" type="forex" limit="10"]</code>.</li>';
		echo '</ul>';

		echo '</div>';
	}

	/**
	 * Карта slug → название всех брокеров в CPT (чтобы в админке показывать, что залито).
	 */
	private static function index_brokers() {
		$posts = get_posts( array(
			'post_type'      => 'broker',
			'post_status'    => array( 'publish', 'private' ),
			'posts_per_page' => 500,
			'no_found_rows'  => true,
		) );
		$out = array();
		foreach ( $posts as $p ) {
			$out[ $p->post_name ] = $p->post_title;
		}
		return $out;
	}
}
