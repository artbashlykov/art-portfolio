<?php
/**
 * Plugin activation.
 *
 * @package Art_Portfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Art_Portfolio_Activator
 */
class Art_Portfolio_Activator {

	/**
	 * Run on plugin activation.
	 */
	public static function activate() {
		require_once ART_PORTFOLIO_PLUGIN_DIR . 'includes/class-post-type.php';

		Art_Portfolio_Post_Type::register_post_type();
		Art_Portfolio_Post_Type::register_taxonomy();
		flush_rewrite_rules();
	}
}
