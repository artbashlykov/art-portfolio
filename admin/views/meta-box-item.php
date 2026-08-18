<?php
/**
 * Portfolio item settings metabox.
 *
 * @package Art_Portfolio
 *
 * @var WP_Post $post
 * @var string  $badge
 * @var int     $thumbnail_id
 * @var int     $preview_id
 * @var string  $preview_url
 * @var string  $excerpt
 * @var array   $meta_rows
 * @var array   $picker_items
 * @var bool    $is_external
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are passed in from Art_Portfolio_Meta_Boxes::render_meta_box().

if ( empty( $meta_rows ) ) {
	$meta_rows = array(
		array(
			'label' => '',
			'value' => '',
		),
	);
}
?>
<div class="art-portfolio-admin art-portfolio-metabox">
	<div class="art-portfolio-metabox__field" data-art-portfolio-image>
		<label><?php echo esc_html__( 'Превью-изображение', 'art-portfolio' ); ?></label>
		<input
			type="hidden"
			id="art-portfolio-thumbnail-id"
			name="art_portfolio_thumbnail_id"
			value="<?php echo esc_attr( (string) $thumbnail_id ); ?>"
		/>
		<div
			class="art-portfolio-metabox__image-preview<?php echo $thumbnail_id ? '' : ' is-empty'; ?>"
			data-art-portfolio-image-preview
		>
			<?php
			if ( $thumbnail_id ) {
				echo wp_kses_post(
					wp_get_attachment_image(
						$thumbnail_id,
						'medium',
						false,
						array(
							'alt' => '',
						)
					)
				);
			}
			?>
		</div>
		<p class="art-portfolio-metabox__image-actions">
			<button type="button" class="button" data-art-portfolio-image-select>
				<?php echo esc_html__( 'Выбрать изображение', 'art-portfolio' ); ?>
			</button>
			<button
				type="button"
				class="button<?php echo $thumbnail_id ? '' : ' is-hidden'; ?>"
				data-art-portfolio-image-remove
			>
				<?php echo esc_html__( 'Удалить', 'art-portfolio' ); ?>
			</button>
		</p>
		<span class="description"><?php echo esc_html__( 'Картинка на карточке до наведения. Живое превью страницы подгружается поверх неё.', 'art-portfolio' ); ?></span>
	</div>

	<p class="art-portfolio-metabox__field">
		<label for="art-portfolio-badge"><?php echo esc_html__( 'Бейдж', 'art-portfolio' ); ?></label>
		<input
			type="text"
			id="art-portfolio-badge"
			name="art_portfolio_badge"
			value="<?php echo esc_attr( $badge ); ?>"
			class="widefat"
			placeholder="<?php echo esc_attr__( 'Лендинг', 'art-portfolio' ); ?>"
		/>
	</p>

	<p class="art-portfolio-metabox__field">
		<label for="art-portfolio-preview-post"><?php echo esc_html__( 'Страница для Live Preview', 'art-portfolio' ); ?></label>
		<select id="art-portfolio-preview-post" name="art_portfolio_preview_post_id" class="widefat">
			<option value="0"><?php echo esc_html__( '— выбрать страницу или запись —', 'art-portfolio' ); ?></option>
			<?php foreach ( $picker_items as $group ) : ?>
				<optgroup label="<?php echo esc_attr( $group['label'] ); ?>">
					<?php foreach ( $group['items'] as $item ) : ?>
						<option
							value="<?php echo esc_attr( (string) $item['id'] ); ?>"
							data-permalink="<?php echo esc_attr( Art_Portfolio_Meta_Boxes::decode_url_for_display( $item['permalink'] ) ); ?>"
							<?php selected( $preview_id, $item['id'] ); ?>
						>
							<?php echo esc_html( $item['title'] ); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
	</p>

	<p class="art-portfolio-metabox__field">
		<label for="art-portfolio-preview-url"><?php echo esc_html__( 'URL вручную', 'art-portfolio' ); ?></label>
		<input
			type="text"
			id="art-portfolio-preview-url"
			name="art_portfolio_preview_url"
			value="<?php echo esc_attr( Art_Portfolio_Meta_Boxes::decode_url_for_display( $preview_url ) ); ?>"
			class="widefat"
			placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>"
		/>
		<span class="description"><?php echo esc_html__( 'Можно вставить абсолютный или относительный URL текущего сайта. Если выбрана страница выше, её адрес подставится автоматически.', 'art-portfolio' ); ?></span>
	</p>

	<p
		class="art-portfolio-metabox__notice<?php echo $is_external ? '' : ' is-hidden'; ?>"
		data-art-portfolio-external-notice
		role="status"
	>
		<?php echo esc_html__( 'Live Preview внешних сайтов может не работать из-за CSP или X-Frame-Options.', 'art-portfolio' ); ?>
	</p>

	<p class="art-portfolio-metabox__field">
		<label for="art-portfolio-excerpt"><?php echo esc_html__( 'Описание', 'art-portfolio' ); ?></label>
		<textarea
			id="art-portfolio-excerpt"
			name="art_portfolio_excerpt"
			class="widefat"
			rows="4"
		><?php echo esc_textarea( $excerpt ); ?></textarea>
		<span class="description"><?php echo esc_html__( 'Короткий текст под заголовком карточки. Можно оставить пустым.', 'art-portfolio' ); ?></span>
	</p>

	<div class="art-portfolio-metabox__repeater" data-art-portfolio-repeater>
		<p class="art-portfolio-metabox__heading"><?php echo esc_html__( 'Параметры проекта', 'art-portfolio' ); ?></p>
		<p class="description art-portfolio-metabox__intro">
			<?php echo esc_html__( 'Короткие пары «название — значение», которые показываются в карточке. Например: тип, ниша, год, срок разработки.', 'art-portfolio' ); ?>
		</p>
		<div class="art-portfolio-metabox__rows" data-art-portfolio-rows>
			<?php foreach ( $meta_rows as $index => $row ) : ?>
				<div class="art-portfolio-metabox__row" data-art-portfolio-row>
					<label class="screen-reader-text" for="art-portfolio-meta-label-<?php echo esc_attr( (string) $index ); ?>">
						<?php echo esc_html__( 'Название', 'art-portfolio' ); ?>
					</label>
					<input
						type="text"
						id="art-portfolio-meta-label-<?php echo esc_attr( (string) $index ); ?>"
						name="art_portfolio_meta_rows[<?php echo esc_attr( (string) $index ); ?>][label]"
						value="<?php echo esc_attr( $row['label'] ); ?>"
						placeholder="<?php echo esc_attr__( 'Название', 'art-portfolio' ); ?>"
					/>
					<label class="screen-reader-text" for="art-portfolio-meta-value-<?php echo esc_attr( (string) $index ); ?>">
						<?php echo esc_html__( 'Значение', 'art-portfolio' ); ?>
					</label>
					<input
						type="text"
						id="art-portfolio-meta-value-<?php echo esc_attr( (string) $index ); ?>"
						name="art_portfolio_meta_rows[<?php echo esc_attr( (string) $index ); ?>][value]"
						value="<?php echo esc_attr( $row['value'] ); ?>"
						placeholder="<?php echo esc_attr__( 'Значение', 'art-portfolio' ); ?>"
					/>
					<button type="button" class="button" data-art-portfolio-remove-row>
						<?php echo esc_html__( 'Удалить', 'art-portfolio' ); ?>
					</button>
				</div>
			<?php endforeach; ?>
		</div>
		<p>
			<button type="button" class="button" data-art-portfolio-add-row>
				<?php echo esc_html__( 'Добавить строку', 'art-portfolio' ); ?>
			</button>
		</p>
		<template data-art-portfolio-row-template>
			<div class="art-portfolio-metabox__row" data-art-portfolio-row>
				<input
					type="text"
					name="art_portfolio_meta_rows[__index__][label]"
					value=""
					placeholder="<?php echo esc_attr__( 'Название', 'art-portfolio' ); ?>"
				/>
				<input
					type="text"
					name="art_portfolio_meta_rows[__index__][value]"
					value=""
					placeholder="<?php echo esc_attr__( 'Значение', 'art-portfolio' ); ?>"
				/>
				<button type="button" class="button" data-art-portfolio-remove-row>
					<?php echo esc_html__( 'Удалить', 'art-portfolio' ); ?>
				</button>
			</div>
		</template>
	</div>
</div>
