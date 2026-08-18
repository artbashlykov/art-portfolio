<?php
/**
 * Custom post type for portfolio items.
 *
 * @package Art_Portfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Art_Portfolio_Post_Type
 */
class Art_Portfolio_Post_Type {

	const POST_TYPE = 'art_portfolio_item';
	const TAXONOMY  = 'art_portfolio_collection';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ), 0 );
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ), 1 );
	}

	/**
	 * Register the portfolio item post type.
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'ART Portfolio', 'art-portfolio' ),
			'singular_name'      => __( 'Работа', 'art-portfolio' ),
			'add_new'            => __( 'Добавить', 'art-portfolio' ),
			'add_new_item'       => __( 'Добавить работу', 'art-portfolio' ),
			'edit_item'          => __( 'Редактировать работу', 'art-portfolio' ),
			'new_item'           => __( 'Новая работа', 'art-portfolio' ),
			'view_item'          => __( 'Просмотреть работу', 'art-portfolio' ),
			'search_items'       => __( 'Искать работы', 'art-portfolio' ),
			'not_found'          => __( 'Работы не найдены', 'art-portfolio' ),
			'not_found_in_trash' => __( 'В корзине работ нет', 'art-portfolio' ),
			'all_items'          => __( 'Все работы', 'art-portfolio' ),
			'menu_name'          => __( 'ART Portfolio', 'art-portfolio' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => $labels,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'exclude_from_search'=> true,
				'has_archive'        => false,
				'rewrite'            => false,
				'query_var'          => false,
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'hierarchical'       => false,
				'menu_position'      => 26,
				'menu_icon'          => 'dashicons-portfolio',
				'supports'           => array( 'title', 'excerpt' ),
			)
		);
	}

	/**
	 * Register collections taxonomy (like categories).
	 */
	public static function register_taxonomy() {
		$labels = array(
			'name'              => __( 'Подборки', 'art-portfolio' ),
			'singular_name'     => __( 'Подборка', 'art-portfolio' ),
			'search_items'      => __( 'Искать подборки', 'art-portfolio' ),
			'all_items'         => __( 'Все подборки', 'art-portfolio' ),
			'parent_item'       => __( 'Родительская подборка', 'art-portfolio' ),
			'parent_item_colon' => __( 'Родительская подборка:', 'art-portfolio' ),
			'edit_item'         => __( 'Редактировать подборку', 'art-portfolio' ),
			'update_item'       => __( 'Обновить подборку', 'art-portfolio' ),
			'add_new_item'      => __( 'Добавить подборку', 'art-portfolio' ),
			'new_item_name'     => __( 'Название подборки', 'art-portfolio' ),
			'menu_name'         => __( 'Подборки', 'art-portfolio' ),
			'not_found'         => __( 'Подборки не найдены', 'art-portfolio' ),
		);

		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'             => $labels,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_admin_column'  => true,
				'show_in_rest'       => true,
				'hierarchical'       => true,
				'rewrite'            => false,
				'query_var'          => false,
				'show_in_nav_menus'  => false,
			)
		);
	}
}
