<?php
/**
 * Серверная генерация JSON-LD: ItemList для топ-листа и FAQPage для FAQ-блока.
 * Значения rating берём строго из базы, без накрутки.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class UZT_Schema {

	public static function item_list( array $items ) {
		if ( empty( $items ) ) return '';

		$elements = array();
		foreach ( $items as $b ) {
			$element = array(
				'@type'    => 'ListItem',
				'position' => (int) ( isset( $b['rank'] ) ? $b['rank'] : 0 ),
				'url'      => esc_url_raw( $b['permalink'] ),
				'name'     => wp_strip_all_tags( (string) $b['name'] ),
			);
			$elements[] = $element;
		}

		$data = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'ItemList',
			'itemListOrder'   => 'https://schema.org/ItemListOrderAscending',
			'numberOfItems'   => count( $elements ),
			'itemListElement' => $elements,
		);

		return self::render_json_ld( $data );
	}

	public static function faq_page( array $qa ) {
		if ( empty( $qa ) ) return '';

		$questions = array();
		foreach ( $qa as $row ) {
			if ( empty( $row['q'] ) || empty( $row['a'] ) ) continue;
			$questions[] = array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( $row['q'] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $row['a'] ),
				),
			);
		}
		if ( empty( $questions ) ) return '';

		$data = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $questions,
		);

		return self::render_json_ld( $data );
	}

	private static function render_json_ld( array $data ) {
		$json = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		// Защита от закрытия <script>: если в данных встретится </script> или <!--, оно не сломает страницу.
		$json = str_replace( array( '</', '<!--', '<script' ), array( '<\/', '<\!--', '<\script' ), $json );
		return '<script type="application/ld+json">' . $json . '</script>';
	}
}