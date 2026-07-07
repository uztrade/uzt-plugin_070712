<?php
/**
 * Шаблон: подробная карточка брокера (для [uzt_broker_card]).
 * @var array $broker
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$logo   = ! empty( $broker['logo_wide']['url'] ) ? $broker['logo_wide'] : $broker['logo_compact'];
$rating = number_format( (float) $broker['rating'], 1, '.', '' );
$year   = ! empty( $broker['founded'] ) ? $broker['founded'] : '—';
$spread = $broker['spread_eurusd_std'] !== '' ? $broker['spread_eurusd_std'] : '—';

$instruments = ! empty( $broker['instruments'] ) ? $broker['instruments'] : array();
$plats       = ! empty( $broker['platforms'] )   ? $broker['platforms']   : array();
$loss_pct    = trim( (string) $broker['loss_pct'] );
?>
<article class="uzt-detail-card" id="broker-<?php echo esc_attr( $broker['rank'] ); ?>">
	<h3 class="uzt-detail-card__heading"><?php echo (int) $broker['rank']; ?>. <?php echo esc_html( $broker['name'] ); ?></h3>

	<div class="uzt-detail-card__header">

		<div class="uzt-detail-col uzt-detail-col--brand">
			<div class="uzt-detail-rank-tag"><?php echo (int) $broker['rank']; ?></div>
			<?php if ( ! empty( $logo['url'] ) ) : ?>
				<img src="<?php echo esc_url( $logo['url'] ); ?>" alt="<?php echo esc_attr( $broker['name'] ); ?>" loading="lazy" decoding="async" />
			<?php endif; ?>
			<?php if ( $spread !== '—' ) : ?>
				<div class="uzt-detail-spread">
					Спред от EUR/USD<br>
					<strong><?php echo esc_html( $spread ); ?> пт</strong>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $instruments ) ) : ?>
			<div class="uzt-detail-col uzt-detail-col--list">
				<span class="uzt-col-label">Торговые инструменты</span>
				<ul class="uzt-check-list">
					<?php foreach ( $instruments as $inst ) : ?>
						<li><?php echo esc_html( $inst ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $plats ) ) : ?>
			<div class="uzt-detail-col uzt-detail-col--list">
				<span class="uzt-col-label">Торговые платформы</span>
				<ul class="uzt-check-list">
					<?php foreach ( $plats as $pl ) : ?>
						<li><?php echo esc_html( $pl ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div class="uzt-detail-col uzt-detail-col--cta">
			<div class="uzt-stars-text uzt-stars-text--lg">
				<span class="uzt-star-icon">★</span> <strong><?php echo esc_html( $rating ); ?></strong><span class="uzt-stars-max">/5</span>
			</div>
			<a class="uzt-btn-primary uzt-btn-primary--lg" href="<?php echo esc_url( $broker['affiliate_url'] ); ?>" target="_blank" rel="sponsored nofollow noopener">Перейти на сайт брокера</a>
			<?php if ( $loss_pct !== '' ) : ?>
				<div class="uzt-risk-note"><?php echo esc_html( $loss_pct ); ?>% розничных счетов теряют средства</div>
			<?php else : ?>
				<div class="uzt-risk-note">Торговля CFD связана с высокими рисками</div>
			<?php endif; ?>
			<div class="uzt-avail-note">✅ <?php echo esc_html( $broker['name'] ); ?> доступен в 🇺🇿</div>
			<a href="<?php echo esc_url( $broker['permalink'] ); ?>" class="uzt-review-link">Читайте обзор <?php echo esc_html( $broker['name'] ); ?></a>
		</div>

	</div>

	<div class="uzt-detail-card__info">
		<div class="uzt-info-text">Основан в <?php echo esc_html( $year ); ?> году</div>
		<?php if ( ! empty( $broker['regulators'] ) ) : ?>
			<div class="uzt-info-text">Регуляторы: <span><?php echo esc_html( $broker['regulators'] ); ?></span></div>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $broker['pros'] ) || ! empty( $broker['cons'] ) ) : ?>
		<details class="uzt-accordion">
			<summary class="uzt-accordion__toggle">
				<span class="uzt-accordion__icon">+</span> Преимущества и недостатки <?php echo esc_html( $broker['name'] ); ?>
			</summary>
			<div class="uzt-accordion__content">
				<?php if ( ! empty( $broker['pros'] ) ) : ?>
					<div class="uzt-pros">
						<h4 class="uzt-pros__title">Преимущества</h4>
						<ul class="uzt-pros__list">
							<?php foreach ( $broker['pros'] as $line ) : ?>
								<li><?php echo esc_html( $line ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $broker['cons'] ) ) : ?>
					<div class="uzt-cons">
						<h4 class="uzt-cons__title">Недостатки</h4>
						<ul class="uzt-cons__list">
							<?php foreach ( $broker['cons'] as $line ) : ?>
								<li><?php echo esc_html( $line ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		</details>
	<?php endif; ?>
</article>