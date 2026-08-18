<?php
/**
 * Frontend and editor asset registration.
 *
 * @package Art_Portfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Art_Portfolio_Assets
 */
class Art_Portfolio_Assets {

	const STYLE_HANDLE  = 'art-portfolio-frontend';
	const SCRIPT_HANDLE = 'art-portfolio-frontend';
	const EDITOR_STYLE  = 'art-portfolio-blocks-editor';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register public assets (enqueued only when a gallery is rendered).
	 */
	public static function register() {
		wp_register_style(
			self::STYLE_HANDLE,
			ART_PORTFOLIO_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			ART_PORTFOLIO_VERSION
		);

		wp_register_script(
			self::SCRIPT_HANDLE,
			ART_PORTFOLIO_PLUGIN_URL . 'assets/js/frontend.js',
			array(),
			ART_PORTFOLIO_VERSION,
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'artPortfolio',
			array(
				'debug'          => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'timeout'        => 12000,
				'viewportWidth'  => 1440,
				'viewportHeight' => 900,
			)
		);

		wp_register_style(
			self::EDITOR_STYLE,
			ART_PORTFOLIO_PLUGIN_URL . 'assets/css/blocks-editor.css',
			array(),
			ART_PORTFOLIO_VERSION
		);
	}

	/**
	 * Enqueue frontend gallery assets.
	 */
	public static function enqueue_frontend() {
		wp_enqueue_style( self::STYLE_HANDLE );
		wp_enqueue_script( self::SCRIPT_HANDLE );
	}

	/**
	 * Enqueue Gutenberg editor shell styles.
	 */
	public static function enqueue_editor() {
		wp_enqueue_style( self::EDITOR_STYLE );
	}
}
