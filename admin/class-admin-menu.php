<?php
/**
 * Admin menu, plugin row meta, and shortcode help page.
 *
 * @package Art_Portfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Art_Portfolio_Admin_Menu
 */
class Art_Portfolio_Admin_Menu {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_submenu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_shortcode_assets' ) );
		add_filter( 'plugin_action_links_' . ART_PORTFOLIO_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta_forge' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta_strip_details' ), 100, 2 );
	}

	/**
	 * Shortcode help page under ART Portfolio.
	 */
	public static function register_submenu() {
		add_submenu_page(
			'edit.php?post_type=' . Art_Portfolio_Post_Type::POST_TYPE,
			__( 'Шорткод', 'art-portfolio' ),
			__( 'Шорткод', 'art-portfolio' ),
			'edit_posts',
			'art-portfolio-shortcode',
			array( __CLASS__, 'render_shortcode_page' )
		);
	}

	/**
	 * Assets for the shortcode help page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_shortcode_assets( $hook ) {
		if ( false === strpos( $hook, 'art-portfolio-shortcode' ) ) {
			return;
		}

		wp_enqueue_style(
			'art-portfolio-admin',
			ART_PORTFOLIO_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			ART_PORTFOLIO_VERSION
		);

		wp_enqueue_script(
			'art-portfolio-admin',
			ART_PORTFOLIO_PLUGIN_URL . 'assets/js/admin.js',
			array(),
			ART_PORTFOLIO_VERSION,
			true
		);

		wp_localize_script(
			'art-portfolio-admin',
			'artPortfolioAdmin',
			array(
				'strings' => array(
					'copy'   => __( 'Скопировать', 'art-portfolio' ),
					'copied' => __( 'Скопировано', 'art-portfolio' ),
				),
			)
		);
	}

	/**
	 * Render the shortcode documentation screen.
	 */
	public static function render_shortcode_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'art-portfolio' ) );
		}

		$collections = get_terms(
			array(
				'taxonomy'   => Art_Portfolio_Post_Type::TAXONOMY,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $collections ) || ! is_array( $collections ) ) {
			$collections = array();
		}

		require ART_PORTFOLIO_PLUGIN_DIR . 'admin/views/page-shortcode.php';
	}

	/**
	 * Copyable shortcode snippet.
	 *
	 * @param string $id   Unique element id.
	 * @param string $code Shortcode text.
	 */
	public static function render_copy_code( $id, $code ) {
		?>
		<div class="art-portfolio-copy-field">
			<code id="<?php echo esc_attr( $id ); ?>" class="art-portfolio-copy-field__code"><?php echo esc_html( $code ); ?></code>
			<button
				type="button"
				class="button"
				data-art-portfolio-copy="#<?php echo esc_attr( $id ); ?>"
				data-art-portfolio-copy-text="<?php echo esc_attr( $code ); ?>"
			>
				<?php esc_html_e( 'Скопировать', 'art-portfolio' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Add a shortcut to portfolio items on the plugins list page.
	 *
	 * @param array<int, string> $links Plugin action links.
	 * @return array<int, string>
	 */
	public static function plugin_action_links( $links ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return $links;
		}

		$portfolio_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'edit.php?post_type=' . Art_Portfolio_Post_Type::POST_TYPE ) ),
			esc_html__( 'Работы', 'art-portfolio' )
		);

		$collections_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url(
				admin_url(
					'edit-tags.php?taxonomy=' . Art_Portfolio_Post_Type::TAXONOMY . '&post_type=' . Art_Portfolio_Post_Type::POST_TYPE
				)
			),
			esc_html__( 'Подборки', 'art-portfolio' )
		);

		$shortcode_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url(
				admin_url(
					'edit.php?post_type=' . Art_Portfolio_Post_Type::POST_TYPE . '&page=art-portfolio-shortcode'
				)
			),
			esc_html__( 'Шорткод', 'art-portfolio' )
		);

		return array_merge( array( $portfolio_link, $collections_link, $shortcode_link ), $links );
	}

	/**
	 * Add author materials link on plugins page (before PUC «Check for updates»).
	 *
	 * @param array<int, string> $links Plugin row meta links.
	 * @param string             $file  Plugin basename.
	 * @return array<int, string>
	 */
	public static function plugin_row_meta_forge( $links, $file ) {
		if ( ART_PORTFOLIO_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( ART_PORTFOLIO_AUTHOR_URL ),
			esc_html__( 'Больше материалов автора', 'art-portfolio' )
		);

		return $links;
	}

	/**
	 * Remove PUC «View details» link from plugin row meta.
	 *
	 * @param array<int, string> $links Plugin row meta links.
	 * @param string             $file  Plugin basename.
	 * @return array<int, string>
	 */
	public static function plugin_row_meta_strip_details( $links, $file ) {
		if ( ART_PORTFOLIO_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		return array_values(
			array_filter(
				$links,
				static function ( $link ) {
					return false === strpos( $link, 'open-plugin-details-modal' )
						&& false === strpos( $link, 'plugin-install.php?tab=plugin-information' );
				}
			)
		);
	}
}
