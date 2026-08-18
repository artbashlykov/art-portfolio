<?php
/**
 * Main plugin bootstrap.
 *
 * @package Art_Portfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Art_Portfolio_Plugin
 */
class Art_Portfolio_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Art_Portfolio_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Whether admin modules were initialized.
	 *
	 * @var bool
	 */
	private static $admin_initialized = false;

	/**
	 * @return Art_Portfolio_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();
	}

	/**
	 * Load required class files.
	 */
	private function load_dependencies() {
		require_once ART_PORTFOLIO_PLUGIN_DIR . 'includes/class-post-type.php';
		require_once ART_PORTFOLIO_PLUGIN_DIR . 'includes/class-meta-boxes.php';
		require_once ART_PORTFOLIO_PLUGIN_DIR . 'includes/class-assets.php';
		require_once ART_PORTFOLIO_PLUGIN_DIR . 'includes/class-renderer.php';
		require_once ART_PORTFOLIO_PLUGIN_DIR . 'includes/class-shortcode.php';
		require_once ART_PORTFOLIO_PLUGIN_DIR . 'includes/class-blocks.php';
		require_once ART_PORTFOLIO_PLUGIN_DIR . 'includes/class-preview-mode.php';
		require_once ART_PORTFOLIO_PLUGIN_DIR . 'includes/class-updater.php';

		if ( is_admin() ) {
			require_once ART_PORTFOLIO_PLUGIN_DIR . 'admin/class-admin-menu.php';
			require_once ART_PORTFOLIO_PLUGIN_DIR . 'admin/class-admin-list.php';
		}
	}

	/**
	 * Register hooks and initialize modules.
	 */
	public function run() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		Art_Portfolio_Post_Type::init();
		Art_Portfolio_Assets::init();
		add_action( 'init', array( $this, 'init' ) );

		if ( is_admin() ) {
			$this->init_admin();
		}
	}

	/**
	 * Load translations for GitHub distribution.
	 */
	public function load_textdomain() {
		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Not hosted on wordpress.org; needed for bundled translations.
		load_plugin_textdomain( 'art-portfolio', false, dirname( ART_PORTFOLIO_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Initialize plugin modules.
	 */
	public function init() {
		Art_Portfolio_Meta_Boxes::init();
		Art_Portfolio_Shortcode::init();
		Art_Portfolio_Blocks::init();
		Art_Portfolio_Preview_Mode::init();
	}

	/**
	 * Initialize admin modules (registers admin_menu and related hooks).
	 */
	public function init_admin() {
		if ( self::$admin_initialized || ! is_admin() ) {
			return;
		}

		self::$admin_initialized = true;

		Art_Portfolio_Updater::init();
		Art_Portfolio_Admin_Menu::init();
		Art_Portfolio_Admin_List::init();
	}
}
