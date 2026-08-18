<?php
/**
 * Portfolio item metabox and meta sanitization.
 *
 * @package Art_Portfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Art_Portfolio_Meta_Boxes
 */
class Art_Portfolio_Meta_Boxes {

	const META_BADGE         = '_art_portfolio_badge';
	const META_PREVIEW_POST  = '_art_portfolio_preview_post_id';
	const META_PREVIEW_URL   = '_art_portfolio_preview_url';
	const META_ROWS          = '_art_portfolio_meta_rows';
	const META_BUTTON_TEXT   = '_art_portfolio_button_text';
	const META_BUTTON_URL    = '_art_portfolio_button_url';
	const NONCE_ACTION       = 'art_portfolio_save_item';
	const NONCE_FIELD        = 'art_portfolio_item_nonce';

	/**
	 * Register hooks.
	 */
	public static function init() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'remove_unused_boxes' ), 99 );
		add_action( 'save_post_' . Art_Portfolio_Post_Type::POST_TYPE, array( __CLASS__, 'save_meta' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Register the item settings metabox.
	 */
	public static function register_meta_boxes() {
		add_meta_box(
			'art-portfolio-item-settings',
			__( 'Настройки работы', 'art-portfolio' ),
			array( __CLASS__, 'render_meta_box' ),
			Art_Portfolio_Post_Type::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Hide default WordPress boxes not used in the work editor.
	 */
	public static function remove_unused_boxes() {
		$screen = Art_Portfolio_Post_Type::POST_TYPE;

		remove_meta_box( 'postexcerpt', $screen, 'normal' );
		remove_meta_box( 'postexcerpt', $screen, 'side' );
		remove_meta_box( 'pageparentdiv', $screen, 'normal' );
		remove_meta_box( 'pageparentdiv', $screen, 'side' );
		remove_meta_box( 'postimagediv', $screen, 'normal' );
		remove_meta_box( 'postimagediv', $screen, 'side' );
	}

	/**
	 * Enqueue metabox scripts on the item editor screen.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || Art_Portfolio_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

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
				'homeHost' => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
				'strings'  => array(
					'removeRow'      => __( 'Удалить строку', 'art-portfolio' ),
					'label'          => __( 'Название', 'art-portfolio' ),
					'value'          => __( 'Значение', 'art-portfolio' ),
					'externalNotice' => __( 'Live Preview внешних сайтов может не работать из-за CSP или X-Frame-Options.', 'art-portfolio' ),
					'selectImage'    => __( 'Выбрать изображение', 'art-portfolio' ),
					'useImage'       => __( 'Использовать изображение', 'art-portfolio' ),
					'copy'           => __( 'Скопировать', 'art-portfolio' ),
					'copied'         => __( 'Скопировано', 'art-portfolio' ),
				),
			)
		);
	}

	/**
	 * Render the metabox.
	 *
	 * @param WP_Post $post Current post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$badge         = (string) get_post_meta( $post->ID, self::META_BADGE, true );
		$thumbnail_id  = (int) get_post_thumbnail_id( $post );
		$preview_id    = absint( get_post_meta( $post->ID, self::META_PREVIEW_POST, true ) );
		$preview_url   = (string) get_post_meta( $post->ID, self::META_PREVIEW_URL, true );
		$excerpt       = (string) $post->post_excerpt;
		$meta_rows    = self::get_meta_rows( $post->ID );
		$picker_items = self::get_picker_items();
		$is_external  = ( '' !== $preview_url && ! self::is_same_host( $preview_url ) );

		include ART_PORTFOLIO_PLUGIN_DIR . 'admin/views/meta-box-item.php';
	}

	/**
	 * Save metabox fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save_meta( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$badge = isset( $_POST['art_portfolio_badge'] )
			? sanitize_text_field( wp_unslash( $_POST['art_portfolio_badge'] ) )
			: '';

		$preview_post_id = isset( $_POST['art_portfolio_preview_post_id'] )
			? absint( wp_unslash( $_POST['art_portfolio_preview_post_id'] ) )
			: 0;

		$preview_url = '';
		if ( isset( $_POST['art_portfolio_preview_url'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in decode_url_for_display(); esc_url_raw strips Cyrillic slugs.
			$preview_url = wp_unslash( $_POST['art_portfolio_preview_url'] );
		}
		$preview_url = self::decode_url_for_display( $preview_url );

		$excerpt = isset( $_POST['art_portfolio_excerpt'] )
			? sanitize_textarea_field( wp_unslash( $_POST['art_portfolio_excerpt'] ) )
			: '';

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in sanitize_meta_rows().
		$raw_rows = isset( $_POST['art_portfolio_meta_rows'] ) ? wp_unslash( $_POST['art_portfolio_meta_rows'] ) : array();

		if ( $preview_post_id && ! self::is_allowed_preview_post( $preview_post_id ) ) {
			$preview_post_id = 0;
		}

		if ( $preview_post_id ) {
			$permalink = get_permalink( $preview_post_id );

			if ( is_string( $permalink ) && '' !== $permalink && '' === $preview_url ) {
				$preview_url = self::decode_url_for_display( $permalink );
			}
		}

		$thumbnail_id = isset( $_POST['art_portfolio_thumbnail_id'] )
			? absint( wp_unslash( $_POST['art_portfolio_thumbnail_id'] ) )
			: 0;

		if ( $thumbnail_id && wp_attachment_is_image( $thumbnail_id ) ) {
			set_post_thumbnail( $post_id, $thumbnail_id );
		} else {
			delete_post_thumbnail( $post_id );
		}

		update_post_meta( $post_id, self::META_BADGE, $badge );
		update_post_meta( $post_id, self::META_PREVIEW_POST, $preview_post_id );
		update_post_meta( $post_id, self::META_PREVIEW_URL, $preview_url );
		update_post_meta( $post_id, self::META_ROWS, self::sanitize_meta_rows( $raw_rows ) );
		delete_post_meta( $post_id, self::META_BUTTON_TEXT );
		delete_post_meta( $post_id, self::META_BUTTON_URL );

		$current_excerpt = $post instanceof WP_Post ? (string) $post->post_excerpt : '';

		if ( $excerpt !== $current_excerpt ) {
			remove_action( 'save_post_' . Art_Portfolio_Post_Type::POST_TYPE, array( __CLASS__, 'save_meta' ), 10 );
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_excerpt' => $excerpt,
				)
			);
			add_action( 'save_post_' . Art_Portfolio_Post_Type::POST_TYPE, array( __CLASS__, 'save_meta' ), 10, 2 );
		}
	}

	/**
	 * Stored metadata rows for a work.
	 *
	 * @param int $post_id Item ID.
	 * @return array<int, array{label: string, value: string}>
	 */
	public static function get_meta_rows( $post_id ) {
		$rows = get_post_meta( $post_id, self::META_ROWS, true );

		if ( ! is_array( $rows ) ) {
			$rows = array();
		}

		/**
		 * Filters metadata rows of a portfolio card.
		 *
		 * @param array<int, array{label: string, value: string}> $rows    Rows.
		 * @param int                                              $post_id Item ID.
		 */
		$rows = apply_filters( 'art_portfolio_meta_rows', $rows, $post_id );

		return self::sanitize_meta_rows( $rows );
	}

	/**
	 * Resolve the live preview URL for a work.
	 *
	 * @param int $post_id Item ID.
	 * @return string
	 */
	public static function get_preview_url( $post_id ) {
		$preview_post_id = absint( get_post_meta( $post_id, self::META_PREVIEW_POST, true ) );
		$url             = (string) get_post_meta( $post_id, self::META_PREVIEW_URL, true );

		if ( $preview_post_id && self::is_allowed_preview_post( $preview_post_id ) ) {
			$permalink = get_permalink( $preview_post_id );

			if ( is_string( $permalink ) && '' !== $permalink ) {
				$url = $permalink;
			}
		}

		$url = self::decode_url_for_display( $url );

		/**
		 * Filters the live preview URL of a portfolio card.
		 *
		 * @param string $url     Preview URL.
		 * @param int    $post_id Item ID.
		 */
		$url = (string) apply_filters( 'art_portfolio_preview_url', $url, $post_id );

		return self::decode_url_for_display( $url );
	}

	/**
	 * Public post types available in the page picker.
	 *
	 * @return array<string, WP_Post_Type>
	 */
	public static function get_picker_post_types() {
		$types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		unset( $types['attachment'], $types[ Art_Portfolio_Post_Type::POST_TYPE ] );

		/**
		 * Filters post types shown in the live preview picker.
		 *
		 * @param array<string, WP_Post_Type> $types Post type objects keyed by name.
		 */
		$types = apply_filters( 'art_portfolio_picker_post_types', $types );

		if ( ! is_array( $types ) ) {
			return array();
		}

		return $types;
	}

	/**
	 * Published items grouped for the picker <select>.
	 *
	 * @return array<int, array{type: string, label: string, items: array<int, array{id: int, title: string, permalink: string}>}>
	 */
	public static function get_picker_items() {
		$groups = array();

		foreach ( self::get_picker_post_types() as $post_type => $object ) {
			if ( ! $object instanceof WP_Post_Type ) {
				continue;
			}

			$query = new WP_Query(
				array(
					'post_type'              => $post_type,
					'post_status'            => 'publish',
					'posts_per_page'         => 200,
					'orderby'                => 'title',
					'order'                  => 'ASC',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			$items = array();

			foreach ( $query->posts as $item ) {
				if ( ! $item instanceof WP_Post ) {
					continue;
				}

				$permalink = get_permalink( $item );

				if ( ! is_string( $permalink ) || '' === $permalink ) {
					continue;
				}

				$items[] = array(
					'id'        => (int) $item->ID,
					'title'     => $item->post_title,
					'permalink' => self::decode_url_for_display( $permalink ),
				);
			}

			if ( empty( $items ) ) {
				continue;
			}

			$groups[] = array(
				'type'  => $post_type,
				'label' => $object->labels->name,
				'items' => $items,
			);
		}

		return $groups;
	}

	/**
	 * Whether a post can be used as a live preview target.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_allowed_preview_post( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
			return false;
		}

		$types = array_keys( self::get_picker_post_types() );

		return in_array( $post->post_type, $types, true );
	}

	/**
	 * Convert a user-entered URL to an absolute request-safe URL.
	 *
	 * Path segments are percent-encoded so iframe src and href stay ASCII.
	 *
	 * @param string $url Raw URL.
	 * @return string
	 */
	public static function normalize_url( $url ) {
		return self::encode_url_for_request( $url );
	}

	/**
	 * Sanitize a preview URL without stripping UTF-8 path segments.
	 *
	 * WordPress `esc_url_raw()` / `esc_url()` can drop Cyrillic slugs
	 * (`https://example.test/lp/проект/` → `https://example.test/lp//`).
	 *
	 * @param string $url Raw URL.
	 * @return string
	 */
	public static function sanitize_preview_url( $url ) {
		$url = trim( (string) $url );
		$url = wp_strip_all_tags( $url );
		$url = preg_replace( '/[\x00-\x1F\x7F]/', '', $url );

		if ( ! is_string( $url ) ) {
			return '';
		}

		$url = str_replace( ' ', '%20', $url );

		if ( '' === $url ) {
			return '';
		}

		if ( 0 === strpos( $url, '//' ) ) {
			$url = ( is_ssl() ? 'https:' : 'http:' ) . $url;
		} elseif ( 0 === strpos( $url, '/' ) ) {
			$url = home_url( $url );
		} elseif ( ! preg_match( '#^https?://#i', $url ) ) {
			$url = home_url( '/' . ltrim( $url, '/' ) );
		}

		if ( ! preg_match( '#^https?://#i', $url ) ) {
			return '';
		}

		$parts = wp_parse_url( $url );

		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '';
		}

		$scheme = strtolower( (string) $parts['scheme'] );

		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return '';
		}

		$parts['scheme'] = $scheme;

		return self::build_url_from_parts( $parts );
	}

	/**
	 * Decode percent-encoded UTF-8 path segments for admin fields.
	 *
	 * @param string $url Absolute or empty URL.
	 * @return string
	 */
	public static function decode_url_for_display( $url ) {
		$url = self::sanitize_preview_url( $url );

		if ( '' === $url ) {
			return '';
		}

		$parts = wp_parse_url( $url );

		if ( ! is_array( $parts ) || ! isset( $parts['path'] ) ) {
			return $url;
		}

		$parts['path'] = self::map_path_segments(
			$parts['path'],
			array( self::class, 'decode_path_segment_for_display' )
		);

		return self::build_url_from_parts( $parts );
	}

	/**
	 * Decode one path segment when it is valid UTF-8.
	 *
	 * @param string $segment Raw path segment.
	 * @return string
	 */
	private static function decode_path_segment_for_display( $segment ) {
		$decoded = rawurldecode( $segment );

		if ( $decoded !== $segment && self::is_valid_utf8( $decoded ) ) {
			return $decoded;
		}

		return $segment;
	}

	/**
	 * Check UTF-8 without deprecated seems_utf8() on WordPress 6.9+.
	 *
	 * @param string $value String to check.
	 * @return bool
	 */
	private static function is_valid_utf8( $value ) {
		$value = (string) $value;

		if ( function_exists( 'wp_is_valid_utf8' ) ) {
			return (bool) wp_is_valid_utf8( $value );
		}

		return (bool) preg_match( '//u', $value );
	}

	/**
	 * Percent-encode path segments so the URL is safe for requests and `esc_url()`.
	 *
	 * @param string $url Raw URL.
	 * @return string
	 */
	public static function encode_url_for_request( $url ) {
		$url = self::sanitize_preview_url( $url );

		if ( '' === $url ) {
			return '';
		}

		$parts = wp_parse_url( $url );

		if ( ! is_array( $parts ) || ! isset( $parts['path'] ) ) {
			return $url;
		}

		$parts['path'] = self::map_path_segments(
			$parts['path'],
			static function ( $segment ) {
				return rawurlencode( rawurldecode( $segment ) );
			}
		);

		return self::build_url_from_parts( $parts );
	}

	/**
	 * Apply a callback to each non-empty path segment.
	 *
	 * @param string   $path     URL path.
	 * @param callable $callback Receives a segment, returns a segment.
	 * @return string
	 */
	private static function map_path_segments( $path, $callback ) {
		$segments = explode( '/', (string) $path );

		foreach ( $segments as $index => $segment ) {
			if ( '' === $segment ) {
				continue;
			}

			$segments[ $index ] = (string) call_user_func( $callback, $segment );
		}

		return implode( '/', $segments );
	}

	/**
	 * Rebuild an absolute http(s) URL from parse_url parts.
	 *
	 * @param array<string, mixed> $parts parse_url parts.
	 * @return string
	 */
	private static function build_url_from_parts( $parts ) {
		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '';
		}

		$url = strtolower( (string) $parts['scheme'] ) . '://';

		if ( isset( $parts['user'] ) && '' !== (string) $parts['user'] ) {
			$url .= (string) $parts['user'];

			if ( isset( $parts['pass'] ) ) {
				$url .= ':' . (string) $parts['pass'];
			}

			$url .= '@';
		}

		$url .= (string) $parts['host'];

		if ( isset( $parts['port'] ) && '' !== (string) $parts['port'] ) {
			$url .= ':' . (string) $parts['port'];
		}

		if ( isset( $parts['path'] ) ) {
			$url .= (string) $parts['path'];
		}

		if ( isset( $parts['query'] ) && '' !== (string) $parts['query'] ) {
			$url .= '?' . (string) $parts['query'];
		}

		if ( isset( $parts['fragment'] ) && '' !== (string) $parts['fragment'] ) {
			$url .= '#' . (string) $parts['fragment'];
		}

		return $url;
	}

	/**
	 * Whether a URL belongs to the current site host.
	 *
	 * @param string $url Absolute URL.
	 * @return bool
	 */
	public static function is_same_host( $url ) {
		$url_host  = wp_parse_url( $url, PHP_URL_HOST );
		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! is_string( $url_host ) || ! is_string( $home_host ) ) {
			return false;
		}

		return strtolower( $url_host ) === strtolower( $home_host );
	}

	/**
	 * Sanitize repeater rows.
	 *
	 * @param mixed $rows Raw rows.
	 * @return array<int, array{label: string, value: string}>
	 */
	public static function sanitize_meta_rows( $rows ) {
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$clean = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$label = isset( $row['label'] ) ? sanitize_text_field( (string) $row['label'] ) : '';
			$value = isset( $row['value'] ) ? sanitize_text_field( (string) $row['value'] ) : '';

			if ( '' === $label && '' === $value ) {
				continue;
			}

			$clean[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		return $clean;
	}
}
