<?php
/**
 * Plugin Name:       ART Portfolio
 * Description:       Портфолио в виде сетки карточек с живым превью внутренних страниц сайта через iframe.
 * Version:           1.3.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Арт Башлыков
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       art-portfolio
 * Domain Path:       /languages
 *
 * @package Art_Portfolio
 */

defined( 'ABSPATH' ) || exit;

define( 'ART_PORTFOLIO_VERSION', '1.3.0' );
define( 'ART_PORTFOLIO_ADMIN_MENU_SLUG', 'art-portfolio' );
define( 'ART_PORTFOLIO_AUTHOR_URL', 'https://forge.artbashlykov.ru' );
define( 'ART_PORTFOLIO_PLUGIN_FILE', __FILE__ );
define( 'ART_PORTFOLIO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ART_PORTFOLIO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ART_PORTFOLIO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

add_filter( 'puc_view_details_link-' . ART_PORTFOLIO_ADMIN_MENU_SLUG, '__return_empty_string' );

require_once ART_PORTFOLIO_PLUGIN_DIR . 'includes/class-activator.php';
require_once ART_PORTFOLIO_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once ART_PORTFOLIO_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook( ART_PORTFOLIO_PLUGIN_FILE, array( 'Art_Portfolio_Activator', 'activate' ) );
register_deactivation_hook( ART_PORTFOLIO_PLUGIN_FILE, array( 'Art_Portfolio_Deactivator', 'deactivate' ) );

/**
 * Returns the main plugin instance.
 *
 * @return Art_Portfolio_Plugin
 */
function art_portfolio() {
	return Art_Portfolio_Plugin::instance();
}

art_portfolio()->run();
