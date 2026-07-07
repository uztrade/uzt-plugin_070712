<?php
/**
 * Мега-карточка брокера в топ-листе (используется циклом из top-list.php).
 * @var array $broker
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$logo    = ! empty( $broker['logo_wide']['url'] ) ? $broker['logo_wide'] : $broker['logo_compact'];
$rating  = number_format( (float) $broker['rating'], 1, '.', '' );
$min_dep = $broker['min_deposit'] !== '' ? '$' . ltrim( $broker['min_deposit'], '$' ) : '';
$year    = ! empty( $broker['founded'] ) ? $broker['founded'] : '';

$pros_list = ! empty( $broker['pros'] ) ? array_slice( $broker['pros'], 0, 4 ) : array();
$cons_list = ! empty( $broker['cons'] ) ? array_slice( $broker['cons'], 0, 4 ) : array();

$spread          = $broker['spread_eurusd_std'] !== '' ? $broker['spread_eurusd_std'] . ' пт' : '';
$platforms       = ! empty( $broker['platforms'] )   ? implode( ', ', (array) $broker['platforms'] )   : '';
$asset_classes   = ! empty( $broker['asset_classes'] ) ? implode( ', ', (array) $broker['asset_classes'] ) : '';
$instruments_txt = ! empty( $broker['instruments_txt'] ) ? $broker['instruments_txt'] : '';
$regulators      = ! empty( $broker['regulators'] ) ? $broker['regulators'] : '';
$loss_pct        = trim( (string) $broker['loss_pct'] );
$has_app         = ! empty( $broker['has_mobile_app'] );
$award           = trim( (string) $broker['award'] );
?>
<article class="uzt-mega-card" role="listitem">

	<?php if ( $award !== '' ) : ?>
		<div class="uzt-mega-card__ribbon"><?php echo esc_html( $award ); ?></div>
	<?php elseif ( (int) $broker['rank'] <= 3 ) : ?>
		<div class="uzt-mega-card__ribbon">ВЫБОР РЕДАКЦИИ</div>
	<?php endif; ?>

	<!-- ГЛАВНАЯ ЧАСТЬ -->
	<div class="uzt-mega-card__main-row">

		<div class="uzt-mega-brand">
			<div class="uzt-mega-logo-wrapper">
				<?php if ( ! empty( $logo['url'] ) ) : ?>
					<img src="<?php echo esc_url( $logo['url'] ); ?>" alt="<?php echo esc_attr( $broker['name'] ); ?>" loading="lazy" decoding="async" />
				<?php endif; ?>
			</div>
			<div class="uzt-mega-stars-row">
				<div class="uzt-stars-visual">
					<?php
					$full_stars = (int) floor( (float) $broker['rating'] );
					for ( $i = 0; $i < 5; $i++ ) {
						echo $i < $full_stars ? '<span class="star-f">★</span>' : '<span class="star-e">★</span>';
					}
					?>
				</div>
				<span class="uzt-score-num"><strong><?php echo esc_html( $rating ); ?></strong>/5</span>
			</div>
		</div>

		<div class="uzt-mega-pros-main">
			<span class="uzt-block-title-mini">Преимущества <?php echo esc_html( $broker['name'] ); ?></span>
			<?php if ( ! empty( $pros_list ) ) : ?>
				<ul>
					<?php foreach ( $pros_list as $pro ) : ?>
						<li>
							<span class="uzt-pro-icon">✓</span>
							<span class="uzt-pro-text"><?php echo esc_html( $pro ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="uzt-mega-cta-zone">
			<a href="<?php echo esc_url( $broker['affiliate_url'] ); ?>" target="_blank" rel="sponsored nofollow noopener" class="uzt-btn-visit">
				Перейти на сайт &raquo;
			</a>
			<a href="<?php echo esc_url( $broker['permalink'] ); ?>" class="uzt-btn-review">
				Обзор <?php echo esc_html( $broker['name'] ); ?>
			</a>
			<div class="uzt-status-avail">Доступен в 🇺🇿</div>
			<?php if ( $loss_pct !== '' ) : ?>
				<div class="uzt-micro-risk"><?php echo esc_html( $loss_pct ); ?></div>
			<?php else : ?>
				<div class="uzt-micro-risk">Торговля CFD связана с высокими рисками</div>
			<?php endif; ?>
		</div>

	</div>

	<!-- ИНФО-ПАНЕЛЬ: 3 пилюли в один ряд (как в старой версии) -->
	<div class="uzt-mega-card__visible-info">
		<div class="uzt-info-pill">
			<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 11h.01"/></svg>
			<span class="pill-label">Мин. вклад:</span>
			<span class="pill-value"><?php echo $min_dep !== '' ? esc_html( $min_dep ) : '—'; ?></span>
		</div>
		<div class="uzt-info-pill">
			<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
			<span class="pill-label">Год основания:</span>
			<span class="pill-value"><?php echo $year !== '' ? esc_html( $year ) : '—'; ?></span>
		</div>
		<div class="uzt-info-pill">
			<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>
			<span class="pill-label">Мобильное приложение:</span>
			<span class="pill-value"><?php echo $has_app ? 'Да' : 'Нет'; ?></span>
		</div>
	</div>

	<!-- АККОРДЕОН -->
	<details class="uzt-mega-accordion">
		<summary class="uzt-mega-accordion__summary">
			<span class="uzt-acc-toggle-text">Подробные условия торговли и минусы</span>
			<span class="uzt-acc-icon">▼</span>
		</summary>

		<div class="uzt-mega-accordion__content">

			<div class="uzt-premium-checklist">
				<span class="uzt-block-title-mini">Спецификации и условия</span>
				<ul>
					<?php if ( $spread !== '' ) : ?>
						<li>
							<span class="uzt-chk-icon">✓</span>
							<div class="uzt-chk-content">
								<span class="uzt-chk-label">Спред EUR/USD:</span>
								<span class="uzt-chk-value"><?php echo esc_html( $spread ); ?></span>
							</div>
						</li>
					<?php endif; ?>
					<?php if ( $asset_classes !== '' ) : ?>
						<li>
							<span class="uzt-chk-icon">✓</span>
							<div class="uzt-chk-content">
								<span class="uzt-chk-label">Торговые активы:</span>
								<span class="uzt-chk-value"><?php echo esc_html( $asset_classes ); ?></span>
							</div>
						</li>
					<?php endif; ?>
					<?php if ( $instruments_txt !== '' ) : ?>
						<li>
							<span class="uzt-chk-icon">✓</span>
							<div class="uzt-chk-content">
								<span class="uzt-chk-label">Количество инструментов:</span>
								<span class="uzt-chk-value"><?php echo esc_html( $instruments_txt ); ?></span>
							</div>
						</li>
					<?php endif; ?>
					<?php if ( $platforms !== '' ) : ?>
						<li>
							<span class="uzt-chk-icon">✓</span>
							<div class="uzt-chk-content">
								<span class="uzt-chk-label">Торговые платформы:</span>
								<span class="uzt-chk-value"><?php echo esc_html( $platforms ); ?></span>
							</div>
						</li>
					<?php endif; ?>
					<?php if ( $regulators !== '' ) : ?>
						<li>
							<span class="uzt-chk-icon">✓</span>
							<div class="uzt-chk-content">
								<span class="uzt-chk-label">Регуляторы:</span>
								<span class="uzt-chk-value"><?php echo esc_html( $regulators ); ?></span>
							</div>
						</li>
					<?php endif; ?>
					<?php if ( ! empty( $broker['swap_free'] ) ) : ?>
						<li>
							<span class="uzt-chk-icon">✓</span>
							<div class="uzt-chk-content">
								<span class="uzt-chk-label">Исламский счёт:</span>
								<span class="uzt-chk-value">Есть (swap-free)</span>
							</div>
						</li>
					<?php endif; ?>
				</ul>
			</div>

			<?php if ( ! empty( $cons_list ) ) : ?>
				<div class="uzt-acc-cons-block">
					<span class="uzt-cons-title-mini">Недостатки</span>
					<ul class="uzt-cons-list-ul">
						<?php foreach ( $cons_list as $con ) : ?>
							<li>
								<span class="uzt-con-icon">✕</span>
								<span class="uzt-con-text"><?php echo esc_html( $con ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

		</div>
	</details>

</article>