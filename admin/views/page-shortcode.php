<?php
/**
 * Shortcode help page.
 *
 * @package Art_Portfolio
 *
 * @var WP_Term[] $collections Collection terms.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap art-portfolio-admin">
	<h1><?php esc_html_e( 'ART Portfolio — Шорткод', 'art-portfolio' ); ?></h1>

	<div class="art-portfolio-panel">
		<h2><?php esc_html_e( 'Как вставить', 'art-portfolio' ); ?></h2>
		<p><?php esc_html_e( 'Шорткод показывает сетку работ портфолио. Его можно вставить в контент страницы, записи, виджета или в блок «Короткий код».', 'art-portfolio' ); ?></p>
		<ul class="art-portfolio-help-list">
			<li><?php esc_html_e( 'В Gutenberg: блок «Короткий код» или, удобнее, блок «АРТ Портфолио: Галерея».', 'art-portfolio' ); ?></li>
			<li><?php esc_html_e( 'В классическом редакторе: прямо в текст страницы. Работают оба тега: [art_portfolio] и [art-portfolio].', 'art-portfolio' ); ?></li>
			<li><?php esc_html_e( 'Атрибуты необязательны: достаточно одного тега, остальное подставится по умолчанию.', 'art-portfolio' ); ?></li>
			<li><?php esc_html_e( 'Значения с пробелами бери в кавычки: button_text="Смотреть проект".', 'art-portfolio' ); ?></li>
		</ul>
	</div>

	<div class="art-portfolio-panel">
		<h2><?php esc_html_e( 'Базовый шорткод', 'art-portfolio' ); ?></h2>
		<p><?php esc_html_e( 'Все опубликованные работы, masonry, 2 колонки на компьютере, 10 карточек на страницу, фильтры подборок включены.', 'art-portfolio' ); ?></p>
		<?php Art_Portfolio_Admin_Menu::render_copy_code( 'art-portfolio-shortcode-basic', '[art_portfolio]' ); ?>
	</div>

	<div class="art-portfolio-panel">
		<h2><?php esc_html_e( 'Примеры', 'art-portfolio' ); ?></h2>

		<h3><?php esc_html_e( 'Одна подборка', 'art-portfolio' ); ?></h3>
		<p><?php esc_html_e( 'ID подборки смотри ниже или в ART Portfolio → Подборки. Фильтры в этом режиме скрываются.', 'art-portfolio' ); ?></p>
		<?php Art_Portfolio_Admin_Menu::render_copy_code( 'art-portfolio-shortcode-collection', '[art_portfolio collection="12"]' ); ?>

		<h3><?php esc_html_e( 'Сетка с пагинацией', 'art-portfolio' ); ?></h3>
		<p><?php esc_html_e( 'Одинаковая высота карточек, 3 колонки на компьютере, 8 работ до пагинации.', 'art-portfolio' ); ?></p>
		<?php Art_Portfolio_Admin_Menu::render_copy_code( 'art-portfolio-shortcode-grid', '[art_portfolio layout="grid" columns="3" per_page="8"]' ); ?>

		<h3><?php esc_html_e( 'Без фильтров и без кнопки', 'art-portfolio' ); ?></h3>
		<?php Art_Portfolio_Admin_Menu::render_copy_code( 'art-portfolio-shortcode-minimal-card', '[art_portfolio show_filters="0" show_button="0"]' ); ?>

		<h3><?php esc_html_e( 'Свой текст кнопки', 'art-portfolio' ); ?></h3>
		<?php Art_Portfolio_Admin_Menu::render_copy_code( 'art-portfolio-shortcode-button', '[art_portfolio button_text="Открыть проект" button_align="center"]' ); ?>
	</div>

	<div class="art-portfolio-panel">
		<h2><?php esc_html_e( 'Подборки и их ID', 'art-portfolio' ); ?></h2>
		<?php if ( empty( $collections ) ) : ?>
			<p><?php esc_html_e( 'Подборок пока нет. Создай их в ART Portfolio → Подборки, затем подставь ID в атрибут collection.', 'art-portfolio' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'Готовый шорткод для каждой подборки:', 'art-portfolio' ); ?></p>
			<table class="widefat striped art-portfolio-help-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Подборка', 'art-portfolio' ); ?></th>
						<th scope="col"><?php esc_html_e( 'ID', 'art-portfolio' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Шорткод', 'art-portfolio' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $collections as $term ) : ?>
						<?php if ( ! $term instanceof WP_Term ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<tr>
							<td><?php echo esc_html( $term->name ); ?></td>
							<td><?php echo esc_html( (string) absint( $term->term_id ) ); ?></td>
							<td>
								<?php
								Art_Portfolio_Admin_Menu::render_copy_code(
									'art-portfolio-shortcode-term-' . absint( $term->term_id ),
									'[art_portfolio collection="' . absint( $term->term_id ) . '"]'
								);
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<div class="art-portfolio-panel">
		<h2><?php esc_html_e( 'Параметры', 'art-portfolio' ); ?></h2>
		<p><?php esc_html_e( 'Логические значения: 1 или 0. Цвета — в формате #1e1e1e. Если атрибут не указан, действует значение по умолчанию.', 'art-portfolio' ); ?></p>
		<table class="widefat striped art-portfolio-help-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Атрибут', 'art-portfolio' ); ?></th>
					<th scope="col"><?php esc_html_e( 'По умолчанию', 'art-portfolio' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Что делает', 'art-portfolio' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>layout</code></td>
					<td><code>masonry</code></td>
					<td><?php esc_html_e( 'Вид сетки: masonry или grid.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>columns</code></td>
					<td><code>2</code></td>
					<td><?php esc_html_e( 'Колонки на компьютере: 1–4.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>tablet_columns</code></td>
					<td><code>2</code></td>
					<td><?php esc_html_e( 'Колонки на планшете: 1–3.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>mobile_columns</code></td>
					<td><code>1</code></td>
					<td><?php esc_html_e( 'Колонки на телефоне: 1–2.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>gap</code></td>
					<td><code>24</code></td>
					<td><?php esc_html_e( 'Расстояние между карточками в пикселях: 0–80.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>per_page</code></td>
					<td><code>10</code></td>
					<td><?php esc_html_e( 'Сколько работ показывать до пагинации: 1–50.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>collection</code></td>
					<td><code>0</code></td>
					<td><?php esc_html_e( 'ID подборки. 0 — все работы.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>show_filters</code></td>
					<td><code>1</code></td>
					<td><?php esc_html_e( 'Фильтры подборок. Появляются только если показаны все работы и подборок минимум две.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>show_badge</code></td>
					<td><code>1</code></td>
					<td><?php esc_html_e( 'Бейдж на превью карточки.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>show_description</code></td>
					<td><code>1</code></td>
					<td><?php esc_html_e( 'Описание работы.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>show_meta</code></td>
					<td><code>1</code></td>
					<td><?php esc_html_e( 'Параметры проекта (стоимость, срок и т. д.).', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>show_button</code></td>
					<td><code>1</code></td>
					<td><?php esc_html_e( 'Кнопка на карточке.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>button_text</code></td>
					<td><code>Посмотреть</code></td>
					<td><?php esc_html_e( 'Текст кнопки для всех карточек в этой галерее.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>button_align</code></td>
					<td><code>left</code></td>
					<td><?php esc_html_e( 'Выравнивание кнопки: left, center, right или full.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>color_title</code></td>
					<td><?php esc_html_e( 'пусто', 'art-portfolio' ); ?></td>
					<td><?php esc_html_e( 'Цвет заголовка, например #1e1e1e.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>color_badge</code></td>
					<td><?php esc_html_e( 'пусто', 'art-portfolio' ); ?></td>
					<td><?php esc_html_e( 'Цвет текста бейджа.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>color_badge_bg</code></td>
					<td><?php esc_html_e( 'пусто', 'art-portfolio' ); ?></td>
					<td><?php esc_html_e( 'Цвет фона бейджа.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>color_description</code></td>
					<td><?php esc_html_e( 'пусто', 'art-portfolio' ); ?></td>
					<td><?php esc_html_e( 'Цвет описания.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>color_meta_label</code></td>
					<td><?php esc_html_e( 'пусто', 'art-portfolio' ); ?></td>
					<td><?php esc_html_e( 'Цвет названия параметра.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>color_meta_value</code></td>
					<td><?php esc_html_e( 'пусто', 'art-portfolio' ); ?></td>
					<td><?php esc_html_e( 'Цвет значения параметра.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>color_button</code></td>
					<td><?php esc_html_e( 'пусто', 'art-portfolio' ); ?></td>
					<td><?php esc_html_e( 'Цвет текста кнопки.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>color_button_bg</code></td>
					<td><?php esc_html_e( 'пусто', 'art-portfolio' ); ?></td>
					<td><?php esc_html_e( 'Цвет фона кнопки.', 'art-portfolio' ); ?></td>
				</tr>
				<tr>
					<td><code>color_card_bg</code></td>
					<td><?php esc_html_e( 'пусто', 'art-portfolio' ); ?></td>
					<td><?php esc_html_e( 'Цвет фона карточки.', 'art-portfolio' ); ?></td>
				</tr>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Старый атрибут limit по-прежнему принимается: положительное число работает как per_page.', 'art-portfolio' ); ?></p>
	</div>
</div>
