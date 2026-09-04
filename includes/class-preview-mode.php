<?php
/**
 * Iframe preview mode for the current site pages.
 *
 * @package Art_Portfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Art_Portfolio_Preview_Mode
 */
class Art_Portfolio_Preview_Mode {

	const QUERY_ARG = 'art_portfolio_preview';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_hide_admin_bar' ) );
		add_action( 'wp_head', array( __CLASS__, 'print_early_script' ), 0 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Whether the current request is a live preview iframe.
	 *
	 * @return bool
	 */
	public static function is_preview_request() {
		if ( ! isset( $_GET[ self::QUERY_ARG ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public query flag.
			return false;
		}

		$flag = sanitize_text_field( wp_unslash( $_GET[ self::QUERY_ARG ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public query flag.

		return '1' === $flag;
	}

	/**
	 * Patch focus before the preview page body parses, so autofocus cannot scroll the parent.
	 */
	public static function print_early_script() {
		if ( ! self::is_preview_request() ) {
			return;
		}

		$script = '(function(){function wrap(p){if(!p||typeof p.focus!=="function"){return;}var n=p.focus;p.focus=function(o){var next={preventScroll:true};var k;if(o&&typeof o==="object"){for(k in o){if(Object.prototype.hasOwnProperty.call(o,k)){next[k]=o[k];}}}next.preventScroll=true;return n.call(this,next);};}wrap(HTMLElement.prototype);if(typeof SVGElement!=="undefined"){wrap(SVGElement.prototype);}if(Element.prototype.scrollIntoView){Element.prototype.scrollIntoView=function(){};}if(document.documentElement){document.documentElement.classList.add("art-portfolio-preview-pinned");}})();';

		wp_print_inline_script_tag( $script );
	}

	/**
	 * Add body class in preview mode.
	 *
	 * @param array<int, string> $classes Body classes.
	 * @return array<int, string>
	 */
	public static function body_class( $classes ) {
		if ( self::is_preview_request() ) {
			$classes[] = 'art-portfolio-preview-mode';
		}

		return $classes;
	}

	/**
	 * Discourage indexing of the preview query variant.
	 *
	 * @param array<string, mixed> $robots Robots directives.
	 * @return array<string, mixed>
	 */
	public static function robots( $robots ) {
		if ( self::is_preview_request() ) {
			$robots['noindex'] = true;
		}

		return $robots;
	}

	/**
	 * Hide the admin bar for administrators inside the preview iframe.
	 */
	public static function maybe_hide_admin_bar() {
		if ( ! self::is_preview_request() ) {
			return;
		}

		if ( current_user_can( 'manage_options' ) ) {
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}

	/**
	 * Assets that run only inside the preview iframe request.
	 */
	public static function enqueue_assets() {
		if ( ! self::is_preview_request() ) {
			return;
		}

		wp_enqueue_style(
			'art-portfolio-preview-mode',
			ART_PORTFOLIO_PLUGIN_URL . 'assets/css/preview-mode.css',
			array(),
			ART_PORTFOLIO_VERSION
		);

		wp_enqueue_script(
			'art-portfolio-preview-mode',
			ART_PORTFOLIO_PLUGIN_URL . 'assets/js/preview-mode.js',
			array(),
			ART_PORTFOLIO_VERSION,
			false
		);
	}

	/**
	 * Build the iframe src for a preview URL.
	 *
	 * @param string $url Source URL.
	 * @return string
	 */
	public static function get_iframe_url( $url ) {
		$url = Art_Portfolio_Meta_Boxes::normalize_url( $url );

		if ( '' === $url ) {
			return '';
		}

		if ( ! Art_Portfolio_Meta_Boxes::is_same_host( $url ) ) {
			return $url;
		}

		return add_query_arg( self::QUERY_ARG, '1', $url );
	}
}
