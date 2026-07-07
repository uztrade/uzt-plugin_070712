<?php
/**
 * Обёртка топ-листа: компактный список + подробные мега-карточки.
 * @var array $items
 * @var string $region
 * @var string $type
 * @var bool $show_table
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<section class="uzt-toplist" aria-labelledby="uzt-toplist-heading">

	<!-- 1. Компактный список (Введение) -->
	<div class="uzt-compact-list">
		<p class="uzt-compact-list__intro">
			Мы тщательно тестируем брокеров на реальных счетах и оцениваем их по нашей методологии. Вот топ-<?php echo esc_html( count( $items ) ); ?> брокеров на основе нашего анализа:
		</p>

		<div class="uzt-compact-list__items">
			<?php foreach ( $items as $broker ) : ?>
				<?php $logo_compact = ! empty( $broker['logo_compact']['url'] ) ? $broker['logo_compact'] : $broker['logo_wide']; ?>
				<div class="uzt-compact-item">
					<span class="uzt-compact-item__rank"><?php echo (int) $broker['rank']; ?></span>
					<div class="uzt-compact-item__logo">
						<?php if ( ! empty( $logo_compact['url'] ) ) : ?>
							<img src="<?php echo esc_url( $logo_compact['url'] ); ?>" alt="<?php echo esc_attr( $broker['name'] ); ?>" loading="lazy" decoding="async" />
						<?php endif; ?>
					</div>
					<div class="uzt-compact-item__content">
						<a href="<?php echo esc_url( $broker['permalink'] ); ?>" class="uzt-compact-item__name"><?php echo esc_html( $broker['name'] ); ?></a>
						<?php if ( ! empty( $broker['buzz'] ) ) : ?>
							<span class="uzt-compact-item__buzz"> — <?php echo esc_html( $broker['buzz'] ); ?></span>
						<?php endif; ?>
						<div class="uzt-compact-item__risk">Торговля CFD связана с высокими рисками</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- 2. Основной рейтинг с мега-карточками -->
	<?php if ( ! empty( $show_table ) ) : ?>
		<h2 id="uzt-toplist-heading" class="uzt-toplist__heading">Подробный рейтинг брокеров</h2>
		<div class="uzt-main-rating" role="list">
			<?php foreach ( $items as $broker ) : ?>
				<?php include UZT_PLUGIN_DIR . 'templates/table-row.php'; ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

</section>