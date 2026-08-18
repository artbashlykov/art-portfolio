<?php
/**
 * Portfolio gallery shortcode.
 *
 * @package Art_Portfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Art_Portfolio_Shortcode
 */
class Art_Portfolio_Shortcode {

	const TAG = 'art_portfolio';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_shortcode( self::TAG, array( __CLASS__, 'render' ) );
		add_shortcode( 'art-portfolio', array( __CLASS__, 'render' ) );
		add_filter( 'widget_text', 'do_shortcode' );
		add_filter( 'widget_block_content', 'do_shortcode' );
		add_filter( 'render_block', array( __CLASS__, 'expand_in_block' ), 9, 2 );
	}

	/**
	 * Render [art_portfolio].
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts ) {
		$raw  = is_array( $atts ) ? $atts : array();
		$atts = shortcode_atts(
			array(
				'columns'           => 2,
				'tablet_columns'    => 2,
				'mobile_columns'    => 1,
				'gap'               => 24,
				'per_page'          => 10,
				'collection'        => 0,
				'show_filters'      => '1',
				'show_badge'        => '1',
				'show_description'  => '1',
				'show_meta'         => '1',
				'show_button'       => '1',
				'button_text'       => '',
				'button_align'      => 'left',
				'layout'            => 'masonry',
				'color_title'       => '',
				'color_badge'       => '',
				'color_badge_bg'    => '',
				'color_description' => '',
				'color_meta_label'  => '',
				'color_meta_value'  => '',
				'color_button'      => '',
				'color_button_bg'   => '',
				'color_card_bg'     => '',
			),
			$raw,
			self::TAG
		);

		if ( ! isset( $raw['per_page'] ) && isset( $raw['limit'] ) && (int) $raw['limit'] > 0 ) {
			$atts['per_page'] = (int) $raw['limit'];
		}

		$atts['collection_id'] = $atts['collection'];
		unset( $atts['collection'] );

		return Art_Portfolio_Renderer::render( $atts );
	}

	/**
	 * Expand the shortcode inside Gutenberg HTML/paragraph blocks.
	 *
	 * @param string               $content Block HTML.
	 * @param array<string, mixed> $block   Block data.
	 * @return string
	 */
	public static function expand_in_block( $content, $block ) {
		unset( $block );

		if ( ! is_string( $content ) || false === strpos( $content, '[' ) ) {
			return $content;
		}

		if ( ! has_shortcode( $content, self::TAG ) && ! has_shortcode( $content, 'art-portfolio' ) ) {
			return $content;
		}

		return do_shortcode( $content );
	}
}
