<?php
/**
 * Шаблон FAQ на нативных <details>. JS не требуется.
 *
 * @var array<int,array{q:string,a:string}> $items
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<section class="uzt-faq" aria-labelledby="uzt-faq-heading">
	<h2 id="uzt-faq-heading" class="uzt-faq__heading">Часто задаваемые вопросы</h2>
	<?php foreach ( $items as $item ) : ?>
		<details class="uzt-faq__item">
			<summary class="uzt-faq__q"><?php echo esc_html( $item['q'] ); ?></summary>
			<div class="uzt-faq__a"><?php echo wp_kses_post( wpautop( $item['a'] ) ); ?></div>
		</details>
	<?php endforeach; ?>
</section>
