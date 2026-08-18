<?php
/**
 * Admin list columns for portfolio items.
 *
 * @package Art_Portfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Art_Portfolio_Admin_List
 */
class Art_Portfolio_Admin_List {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'manage_' . Art_Portfolio_Post_Type::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . Art_Portfolio_Post_Type::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'render_collection_filter' ), 10, 2 );
		add_action( 'parse_query', array( __CLASS__, 'filter_by_collection' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue list table styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || Art_Portfolio_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'art-portfolio-admin',
			ART_PORTFOLIO_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			ART_PORTFOLIO_VERSION
		);
	}

	/**
	 * Custom columns.
	 *
	 * @param array<string, string> $columns Default columns.
	 * @return array<string, string>
	 */
	public static function columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['title']               = __( 'Название', 'art-portfolio' );
				$new['art_portfolio_badge'] = __( 'Бейдж', 'art-portfolio' );
				$new['art_portfolio_url']   = __( 'URL', 'art-portfolio' );
				continue;
			}

			if ( 'date' === $key ) {
				$new['date'] = __( 'Дата', 'art-portfolio' );
				continue;
			}

			$new[ $key ] = $label;
		}

		return $new;
	}

	/**
	 * Dropdown to filter works by collection.
	 *
	 * @param string $post_type Current post type.
	 * @param string $which     Top or bottom bar.
	 */
	public static function render_collection_filter( $post_type, $which ) {
		if ( Art_Portfolio_Post_Type::POST_TYPE !== $post_type || 'top' !== $which ) {
			return;
		}

		$taxonomy = Art_Portfolio_Post_Type::TAXONOMY;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only works list filter; value is absint term ID.
		$selected = isset( $_GET[ $taxonomy ] ) ? absint( wp_unslash( $_GET[ $taxonomy ] ) ) : 0;

		wp_dropdown_categories(
			array(
				'show_option_all' => __( 'Все подборки', 'art-portfolio' ),
				'taxonomy'        => $taxonomy,
				'name'            => $taxonomy,
				'orderby'         => 'name',
				'selected'        => $selected,
				'hierarchical'    => true,
				'hide_empty'      => false,
				'value_field'     => 'term_id',
			)
		);
	}

	/**
	 * Apply the collection dropdown to the works list query.
	 *
	 * @param WP_Query $query Current query.
	 */
	public static function filter_by_collection( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( Art_Portfolio_Post_Type::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$taxonomy = Art_Portfolio_Post_Type::TAXONOMY;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only works list filter; value is absint term ID.
		$term_id = isset( $_GET[ $taxonomy ] ) ? absint( wp_unslash( $_GET[ $taxonomy ] ) ) : 0;

		if ( $term_id < 1 || ! term_exists( $term_id, $taxonomy ) ) {
			return;
		}

		$tax_query = $query->get( 'tax_query' );

		if ( ! is_array( $tax_query ) ) {
			$tax_query = array();
		}

		$tax_query[] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => array( $term_id ),
		);

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Filter works list by one collection term ID.
		$query->set( 'tax_query', $tax_query );
	}

	/**
	 * Render custom column values.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public static function column_content( $column, $post_id ) {
		if ( 'art_portfolio_badge' === $column ) {
			$badge = (string) get_post_meta( $post_id, Art_Portfolio_Meta_Boxes::META_BADGE, true );
			echo $badge ? esc_html( $badge ) : '&mdash;';
			return;
		}

		if ( 'art_portfolio_url' === $column ) {
			$url = Art_Portfolio_Meta_Boxes::get_preview_url( $post_id );

			if ( '' === $url ) {
				echo '&mdash;';
				return;
			}

			printf(
				'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="art-portfolio-admin-url">%2$s</a>',
				esc_url( Art_Portfolio_Meta_Boxes::encode_url_for_request( $url ) ),
				esc_html( Art_Portfolio_Meta_Boxes::decode_url_for_display( $url ) )
			);
		}
	}
}
