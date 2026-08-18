<?php
/**
 * Gutenberg block registration.
 *
 * @package Art_Portfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Art_Portfolio_Blocks
 */
class Art_Portfolio_Blocks {

	const BLOCK_NAME = 'art-portfolio/gallery';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'block_categories_all', array( __CLASS__, 'register_block_category' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );
		self::register_blocks();
	}

	/**
	 * Register the gallery block.
	 */
	public static function register_blocks() {
		$block_dir = ART_PORTFOLIO_PLUGIN_DIR . 'build/gallery';

		if ( ! file_exists( $block_dir . '/block.json' ) ) {
			return;
		}

		register_block_type(
			$block_dir,
			array(
				'render_callback' => array( __CLASS__, 'render_block' ),
			)
		);
	}

	/**
	 * Enqueue editor shell styles.
	 */
	public static function enqueue_editor_assets() {
		Art_Portfolio_Assets::enqueue_editor();
	}

	/**
	 * Register the ART Portfolio block category.
	 *
	 * @param array                   $categories Block categories.
	 * @param WP_Block_Editor_Context $context    Editor context.
	 * @return array
	 */
	public static function register_block_category( $categories, $context ) {
		unset( $context );

		foreach ( $categories as $category ) {
			if ( is_array( $category ) && isset( $category['slug'] ) && 'art-portfolio' === $category['slug'] ) {
				return $categories;
			}
		}

		array_unshift(
			$categories,
			array(
				'slug'  => 'art-portfolio',
				'title' => __( 'АРТ Портфолио', 'art-portfolio' ),
			)
		);

		return $categories;
	}

	/**
	 * Server-side render for the gallery block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function render_block( $attributes ) {
		$args = array(
			'columns'           => isset( $attributes['columns'] ) ? $attributes['columns'] : 2,
			'tablet_columns'    => isset( $attributes['tabletColumns'] ) ? $attributes['tabletColumns'] : 2,
			'mobile_columns'    => isset( $attributes['mobileColumns'] ) ? $attributes['mobileColumns'] : 1,
			'gap'               => isset( $attributes['gap'] ) ? $attributes['gap'] : 24,
			'per_page'          => self::resolve_per_page( $attributes ),
			'collection_id'     => isset( $attributes['collectionId'] ) ? $attributes['collectionId'] : 0,
			'show_filters'      => isset( $attributes['showFilters'] ) ? $attributes['showFilters'] : true,
			'show_badge'        => isset( $attributes['showBadge'] ) ? $attributes['showBadge'] : true,
			'show_description'  => isset( $attributes['showDescription'] ) ? $attributes['showDescription'] : true,
			'show_meta'         => isset( $attributes['showMeta'] ) ? $attributes['showMeta'] : true,
			'show_button'       => isset( $attributes['showButton'] ) ? $attributes['showButton'] : true,
			'button_text'       => isset( $attributes['buttonText'] ) ? $attributes['buttonText'] : '',
			'button_align'      => isset( $attributes['buttonAlign'] ) ? $attributes['buttonAlign'] : 'left',
			'layout'            => isset( $attributes['layout'] ) ? $attributes['layout'] : 'masonry',
			'color_title'       => isset( $attributes['colorTitle'] ) ? $attributes['colorTitle'] : '',
			'color_badge'       => isset( $attributes['colorBadge'] ) ? $attributes['colorBadge'] : '',
			'color_badge_bg'    => isset( $attributes['colorBadgeBg'] ) ? $attributes['colorBadgeBg'] : '',
			'color_description' => isset( $attributes['colorDescription'] ) ? $attributes['colorDescription'] : '',
			'color_meta_label'  => isset( $attributes['colorMetaLabel'] ) ? $attributes['colorMetaLabel'] : '',
			'color_meta_value'  => isset( $attributes['colorMetaValue'] ) ? $attributes['colorMetaValue'] : '',
			'color_button'      => isset( $attributes['colorButton'] ) ? $attributes['colorButton'] : '',
			'color_button_bg'   => isset( $attributes['colorButtonBg'] ) ? $attributes['colorButtonBg'] : '',
			'color_card_bg'     => isset( $attributes['colorCardBg'] ) ? $attributes['colorCardBg'] : '',
		);

		return Art_Portfolio_Renderer::render( $args );
	}

	/**
	 * Per-page count from the new attribute, with a fallback for old `limit`.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return int
	 */
	private static function resolve_per_page( $attributes ) {
		if ( isset( $attributes['perPage'] ) ) {
			return (int) $attributes['perPage'];
		}

		if ( isset( $attributes['limit'] ) && (int) $attributes['limit'] > 0 ) {
			return (int) $attributes['limit'];
		}

		return 10;
	}
}
