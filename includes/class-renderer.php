<?php
/**
 * Gallery and card renderer.
 *
 * @package Art_Portfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Art_Portfolio_Renderer
 */
class Art_Portfolio_Renderer {

	const PAGE_QUERY_VAR       = 'art_portfolio_page';
	const COLLECTION_QUERY_VAR = 'art_portfolio_collection';

	/**
	 * Default display arguments.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_default_args() {
		return array(
			'columns'          => 2,
			'tablet_columns'   => 2,
			'mobile_columns'   => 1,
			'gap'              => 24,
			'per_page'         => 10,
			'collection_id'    => 0,
			'show_filters'     => true,
			'show_badge'       => true,
			'show_description' => true,
			'show_meta'        => true,
			'show_button'      => true,
			'button_text'      => '',
			'button_align'     => 'left',
			'layout'           => 'masonry',
			'color_title'        => '',
			'color_badge'        => '',
			'color_badge_bg'     => '',
			'color_description'  => '',
			'color_meta_label'   => '',
			'color_meta_value'   => '',
			'color_button'       => '',
			'color_button_bg'    => '',
			'color_card_bg'      => '',
		);
	}

	/**
	 * Normalize gallery arguments.
	 *
	 * @param array<string, mixed> $args Raw arguments.
	 * @return array<string, mixed>
	 */
	public static function parse_args( $args ) {
		$defaults = self::get_default_args();
		$args     = wp_parse_args( $args, $defaults );

		$args['columns']        = self::clamp_int( $args['columns'], 1, 4, 2 );
		$args['tablet_columns'] = self::clamp_int( $args['tablet_columns'], 1, 3, 2 );
		$args['mobile_columns'] = self::clamp_int( $args['mobile_columns'], 1, 2, 1 );
		$args['gap']            = self::clamp_int( $args['gap'], 0, 80, 24 );
		$args['per_page']       = self::resolve_per_page( $args );
		$args['collection_id']  = absint( $args['collection_id'] );

		if ( $args['collection_id'] && ! term_exists( $args['collection_id'], Art_Portfolio_Post_Type::TAXONOMY ) ) {
			$args['collection_id'] = 0;
		}

		$args['show_filters']     = self::to_bool( $args['show_filters'] );
		$args['show_badge']       = self::to_bool( $args['show_badge'] );
		$args['show_description'] = self::to_bool( $args['show_description'] );
		$args['show_meta']        = self::to_bool( $args['show_meta'] );
		$args['show_button']      = self::to_bool( $args['show_button'] );
		$args['button_text']      = sanitize_text_field( (string) $args['button_text'] );
		$args['button_align']     = sanitize_key( (string) $args['button_align'] );
		$args['layout']           = sanitize_key( (string) $args['layout'] );

		if ( ! in_array( $args['button_align'], array( 'left', 'center', 'right', 'full' ), true ) ) {
			$args['button_align'] = 'left';
		}

		if ( 'mosaic' === $args['layout'] ) {
			$args['layout'] = 'masonry';
		}

		if ( ! in_array( $args['layout'], array( 'grid', 'masonry' ), true ) ) {
			$args['layout'] = 'masonry';
		}

		if ( '' === $args['button_text'] || 'Смотреть' === $args['button_text'] ) {
			$args['button_text'] = __( 'Посмотреть', 'art-portfolio' );
		}

		if ( $args['collection_id'] > 0 ) {
			$args['show_filters'] = false;
		}

		foreach ( self::get_color_keys() as $key ) {
			$args[ $key ] = self::sanitize_color( $args[ $key ] );
		}

		return $args;
	}

	/**
	 * Render a gallery.
	 *
	 * @param array<string, mixed> $args Display arguments.
	 * @return string
	 */
	public static function render( $args = array() ) {
		$args    = self::parse_args( $args );
		$gallery = self::query_gallery( $args );
		$items   = $gallery['items'];

		if ( ! Art_Portfolio_Preview_Mode::is_preview_request() ) {
			Art_Portfolio_Assets::enqueue_frontend();
		}

		$html  = '<div class="' . esc_attr( implode( ' ', self::get_wrapper_classes( $args ) ) ) . '" style="' . esc_attr( self::build_style( $args ) ) . '">';
		$html .= self::render_filters( $args, $gallery['filter_collection'] );

		if ( empty( $items ) ) {
			$empty = $gallery['filter_collection'] > 0
				? __( 'В этой подборке пока нет работ.', 'art-portfolio' )
				: __( 'Пока нет опубликованных работ.', 'art-portfolio' );
			$html .= '<p class="art-portfolio-empty">' . esc_html( $empty ) . '</p>';
		} else {
			$html .= '<div class="art-portfolio-grid">';
			$html .= '<div class="art-portfolio-grid__inner">';

			foreach ( $items as $item ) {
				$html .= self::render_card( $item, $args );
			}

			$html .= '</div></div>';
		}

		$html .= self::render_pagination( $gallery['current_page'], $gallery['max_pages'], $gallery['filter_collection'] );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Query published portfolio items.
	 *
	 * @param array<string, mixed> $args Display arguments.
	 * @return WP_Post[]
	 */
	public static function query_items( $args ) {
		$gallery = self::query_gallery( $args );

		return $gallery['items'];
	}

	/**
	 * Query one gallery page and pagination data.
	 *
	 * @param array<string, mixed> $args Display arguments.
	 * @return array{items: WP_Post[], current_page: int, max_pages: int, filter_collection: int}
	 */
	public static function query_gallery( $args ) {
		$args              = self::parse_args( $args );
		$filter_collection = self::get_requested_collection_id( $args );
		$paged             = self::get_requested_page();
		$tax_id            = ! empty( $args['collection_id'] ) ? (int) $args['collection_id'] : $filter_collection;

		$query_args = array(
			'post_type'              => Art_Portfolio_Post_Type::POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => (int) $args['per_page'],
			'paged'                  => $paged,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => false,
			'update_post_term_cache' => true,
		);

		if ( $tax_id > 0 ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Filter gallery by one collection term ID.
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => Art_Portfolio_Post_Type::TAXONOMY,
					'field'    => 'term_id',
					'terms'    => array( $tax_id ),
				),
			);
		}

		/**
		 * Filters WP_Query arguments for the portfolio gallery.
		 *
		 * @param array<string, mixed> $query_args Query arguments.
		 * @param array<string, mixed> $args       Display arguments.
		 */
		$query_args = apply_filters( 'art_portfolio_query_args', $query_args, $args );

		$query     = new WP_Query( $query_args );
		$max_pages = (int) $query->max_num_pages;

		if ( $paged > 1 && $max_pages > 0 && $paged > $max_pages ) {
			$query_args['paged'] = $max_pages;
			$query               = new WP_Query( $query_args );
			$paged               = $max_pages;
			$max_pages           = (int) $query->max_num_pages;
		}

		return array(
			'items'             => $query->posts,
			'current_page'      => max( 1, $paged ),
			'max_pages'         => $max_pages,
			'filter_collection' => $filter_collection,
		);
	}

	/**
	 * Render a single card.
	 *
	 * @param WP_Post              $post Item.
	 * @param array<string, mixed> $args Display arguments.
	 * @return string
	 */
	public static function render_card( $post, $args ) {
		$data = self::get_card_data( $post, $args );

		$preview_url = $data['preview_url'];
		$has_preview = '' !== $preview_url && ! Art_Portfolio_Preview_Mode::is_preview_request();
		$iframe_url  = $has_preview ? Art_Portfolio_Preview_Mode::get_iframe_url( $preview_url ) : '';
		$link_url    = self::get_work_open_url( $data['preview_url'] );
		$title       = $data['title'];
		$badge       = $data['badge'];
		$description = $data['description'];
		$meta_rows   = $data['meta_rows'];
		$button_text = $data['button_text'];
		$button_url  = self::get_work_open_url( $data['button_url'] );
		$image_html  = $data['image_html'];
		$term_ids    = isset( $data['collection_ids'] ) && is_array( $data['collection_ids'] )
			? $data['collection_ids']
			: array();

		$preview_attrs = ' class="art-portfolio-card__preview"';

		if ( $has_preview ) {
			$preview_label = sprintf(
				/* translators: %s: work title */
				__( 'Живое превью: %s', 'art-portfolio' ),
				$title
			);

			$preview_attrs .= ' tabindex="0" role="group"';
			$preview_attrs .= ' data-preview-url="' . esc_url( $iframe_url ) . '"';
			$preview_attrs .= ' aria-label="' . esc_attr( $preview_label ) . '"';
		}

		$collection_attr = implode( ',', array_map( 'strval', $term_ids ) );

		$html  = '<article class="art-portfolio-card" data-collections="' . esc_attr( $collection_attr ) . '">';
		$html .= '<div' . $preview_attrs . '>';
		$html .= '<div class="art-portfolio-card__image">';

		if ( $has_preview ) {
			$html .= '<a class="art-portfolio-card__image-link" href="' . esc_url( $link_url ) . '">';
			$html .= $image_html;
			$html .= '</a>';
		} else {
			$html .= $image_html;
		}

		$html .= '</div>';
		$html .= '<div class="art-portfolio-card__live-preview"></div>';
		$html .= '<div class="art-portfolio-card__loader" hidden>' . esc_html__( 'Загрузка…', 'art-portfolio' ) . '</div>';
		$html .= '<p class="art-portfolio-card__error" hidden>' . esc_html__( 'Не удалось загрузить живое превью', 'art-portfolio' ) . '</p>';

		if ( $args['show_badge'] && '' !== $badge ) {
			$html .= '<div class="art-portfolio-card__badge">' . esc_html( $badge ) . '</div>';
		}

		$html .= '</div>';

		$html .= '<div class="art-portfolio-card__content">';

		$html .= '<h3 class="art-portfolio-card__title">';

		if ( $has_preview ) {
			$html .= '<a class="art-portfolio-card__title-link" href="' . esc_url( $link_url ) . '">' . esc_html( $title ) . '</a>';
		} else {
			$html .= esc_html( $title );
		}

		$html .= '</h3>';

		if ( $args['show_description'] && '' !== $description ) {
			$html .= '<div class="art-portfolio-card__description">' . esc_html( $description ) . '</div>';
		}

		if ( $args['show_meta'] && ! empty( $meta_rows ) ) {
			$html .= '<dl class="art-portfolio-card__meta">';

			foreach ( $meta_rows as $row ) {
				$html .= '<div class="art-portfolio-card__meta-row">';
				$html .= '<dt>' . esc_html( $row['label'] ) . '</dt>';
				$html .= '<dd>' . esc_html( $row['value'] ) . '</dd>';
				$html .= '</div>';
			}

			$html .= '</dl>';
		}

		if ( $args['show_button'] && '' !== $button_text && '' !== $button_url ) {
			$html .= '<a class="art-portfolio-card__button" href="' . esc_url( $button_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $button_text ) . '</a>';
		}

		$html .= '</div></article>';

		return $html;
	}

	/**
	 * Work URL without a fragment, so the destination does not jump to an anchor.
	 *
	 * @param string $url Preview or button URL.
	 * @return string
	 */
	private static function get_work_open_url( $url ) {
		$parts = explode( '#', (string) $url, 2 );

		return Art_Portfolio_Meta_Boxes::encode_url_for_request( $parts[0] );
	}

	/**
	 * Collect sanitized card data.
	 *
	 * @param WP_Post              $post Item.
	 * @param array<string, mixed> $args Display arguments.
	 * @return array<string, mixed>
	 */
	public static function get_card_data( $post, $args ) {
		$preview_url = Art_Portfolio_Meta_Boxes::get_preview_url( $post->ID );
		$badge       = (string) get_post_meta( $post->ID, Art_Portfolio_Meta_Boxes::META_BADGE, true );
		$button_text = isset( $args['button_text'] ) ? sanitize_text_field( (string) $args['button_text'] ) : '';
		$button_url  = $preview_url;

		if ( '' === $button_text ) {
			$button_text = __( 'Посмотреть', 'art-portfolio' );
		}

		$description = has_excerpt( $post ) ? $post->post_excerpt : '';
		$description = wp_strip_all_tags( $description );

		$alt = get_the_title( $post );

		if ( has_post_thumbnail( $post ) ) {
			$image_html = wp_kses_post(
				get_the_post_thumbnail(
					$post,
					'large',
					array(
						'class'    => 'art-portfolio-card__img',
						'alt'      => $alt,
						'loading'  => 'lazy',
						'decoding' => 'async',
					)
				)
			);
		} else {
			$image_html  = '<div class="art-portfolio-card__placeholder">';
			$image_html .= '<span class="art-portfolio-card__placeholder-text">' . esc_html__( 'Превью проекта', 'art-portfolio' ) . '</span>';
			$image_html .= '</div>';
		}

		$data = array(
			'title'          => get_the_title( $post ),
			'badge'          => $badge,
			'description'    => $description,
			'preview_url'    => $preview_url,
			'meta_rows'      => Art_Portfolio_Meta_Boxes::get_meta_rows( $post->ID ),
			'button_text'    => $button_text,
			'button_url'     => $button_url,
			'image_html'     => $image_html,
			'collection_ids' => self::get_collection_ids( $post->ID ),
		);

		/**
		 * Filters data used to render a portfolio card.
		 *
		 * @param array<string, mixed> $data Card data.
		 * @param WP_Post              $post Item.
		 * @param array<string, mixed> $args Display arguments.
		 */
		$data = apply_filters( 'art_portfolio_card_data', $data, $post, $args );

		if ( ! is_array( $data ) ) {
			return array();
		}

		return wp_parse_args(
			$data,
			array(
				'title'          => '',
				'badge'          => '',
				'description'    => '',
				'preview_url'    => '',
				'meta_rows'      => array(),
				'button_text'    => '',
				'button_url'     => '',
				'image_html'     => '',
				'collection_ids' => array(),
			)
		);
	}

	/**
	 * Collection term IDs assigned to a work.
	 *
	 * @param int $post_id Item ID.
	 * @return array<int, int>
	 */
	public static function get_collection_ids( $post_id ) {
		$ids = wp_get_post_terms(
			$post_id,
			Art_Portfolio_Post_Type::TAXONOMY,
			array(
				'fields' => 'ids',
			)
		);

		if ( is_wp_error( $ids ) || ! is_array( $ids ) ) {
			return array();
		}

		return array_values( array_map( 'absint', $ids ) );
	}

	/**
	 * Color argument keys.
	 *
	 * @return array<int, string>
	 */
	private static function get_color_keys() {
		return array(
			'color_title',
			'color_badge',
			'color_badge_bg',
			'color_description',
			'color_meta_label',
			'color_meta_value',
			'color_button',
			'color_button_bg',
			'color_card_bg',
		);
	}

	/**
	 * Wrapper classes for layout and column counts.
	 *
	 * @param array<string, mixed> $args Normalized arguments.
	 * @return array<int, string>
	 */
	private static function get_wrapper_classes( $args ) {
		return array(
			'art-portfolio',
			'art-portfolio--button-' . sanitize_html_class( $args['button_align'] ),
			'art-portfolio--layout-' . sanitize_html_class( $args['layout'] ),
			'art-portfolio--desktop-' . (int) $args['columns'],
			'art-portfolio--tablet-' . (int) $args['tablet_columns'],
			'art-portfolio--mobile-' . (int) $args['mobile_columns'],
		);
	}

	/**
	 * Inline CSS custom properties for a gallery.
	 *
	 * @param array<string, mixed> $args Normalized arguments.
	 * @return string
	 */
	private static function build_style( $args ) {
		$rules = array(
			'--art-portfolio-columns-desktop: ' . (int) $args['columns'],
			'--art-portfolio-columns-tablet: ' . (int) $args['tablet_columns'],
			'--art-portfolio-columns-mobile: ' . (int) $args['mobile_columns'],
			'--art-portfolio-gap: ' . (int) $args['gap'] . 'px',
		);

		$map = array(
			'color_title'       => '--art-portfolio-color-title',
			'color_badge'       => '--art-portfolio-color-badge',
			'color_badge_bg'    => '--art-portfolio-color-badge-bg',
			'color_description' => '--art-portfolio-color-description',
			'color_meta_label'  => '--art-portfolio-color-meta-label',
			'color_meta_value'  => '--art-portfolio-color-meta-value',
			'color_button'      => '--art-portfolio-color-button',
			'color_button_bg'   => '--art-portfolio-color-button-bg',
			'color_card_bg'     => '--art-portfolio-color-card-bg',
		);

		foreach ( $map as $key => $property ) {
			if ( '' !== $args[ $key ] ) {
				$rules[] = $property . ': ' . $args[ $key ];
			}
		}

		return implode( '; ', $rules ) . ';';
	}

	/**
	 * Filter chips for collections. Links keep pagination in sync.
	 *
	 * @param array<string, mixed> $args               Display arguments.
	 * @param int                  $active_collection  Active filter term ID.
	 * @return string
	 */
	private static function render_filters( $args, $active_collection ) {
		if ( empty( $args['show_filters'] ) ) {
			return '';
		}

		$terms = self::get_filter_terms();

		if ( count( $terms ) < 2 ) {
			return '';
		}

		$html  = '<nav class="art-portfolio-filters" aria-label="' . esc_attr__( 'Фильтр подборок', 'art-portfolio' ) . '">';
		$html .= self::render_filter_chip(
			__( 'Все', 'art-portfolio' ),
			self::get_gallery_url( 1, 0 ),
			0 === (int) $active_collection
		);

		foreach ( $terms as $term ) {
			$term_id = (int) $term->term_id;
			$html   .= self::render_filter_chip(
				$term->name,
				self::get_gallery_url( 1, $term_id ),
				$term_id === (int) $active_collection
			);
		}

		$html .= '</nav>';

		return $html;
	}

	/**
	 * One collection filter chip.
	 *
	 * @param string $label    Chip label.
	 * @param string $url      Target URL.
	 * @param bool   $is_active Whether this chip is selected.
	 * @return string
	 */
	private static function render_filter_chip( $label, $url, $is_active ) {
		$class = 'art-portfolio-filters__chip';

		if ( $is_active ) {
			$class .= ' is-active';
		}

		$html  = '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '"';
		if ( $is_active ) {
			$html .= ' aria-current="true"';
		}
		$html .= '>' . esc_html( $label ) . '</a>';

		return $html;
	}

	/**
	 * Collection terms that have published works.
	 *
	 * @return WP_Term[]
	 */
	private static function get_filter_terms() {
		$terms = get_terms(
			array(
				'taxonomy'   => Art_Portfolio_Post_Type::TAXONOMY,
				'hide_empty' => true,
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$terms,
				static function ( $term ) {
					return $term instanceof WP_Term;
				}
			)
		);
	}

	/**
	 * Numbered pagination under the grid.
	 *
	 * @param int $current       Current page.
	 * @param int $total         Total pages.
	 * @param int $collection_id Active collection filter.
	 * @return string
	 */
	private static function render_pagination( $current, $total, $collection_id ) {
		$current = (int) $current;
		$total   = (int) $total;

		if ( $total < 2 ) {
			return '';
		}

		$html  = '<nav class="art-portfolio-pagination" aria-label="' . esc_attr__( 'Пагинация портфолио', 'art-portfolio' ) . '">';
		$html .= '<ul class="art-portfolio-pagination__list">';

		if ( $current > 1 ) {
			$html .= '<li>' . self::render_pagination_link(
				self::get_gallery_url( $current - 1, $collection_id ),
				'←',
				'prev',
				__( 'Предыдущая страница', 'art-portfolio' )
			) . '</li>';
		}

		foreach ( self::get_pagination_pages( $current, $total ) as $page ) {
			if ( 0 === $page ) {
				$html .= '<li><span class="art-portfolio-pagination__link art-portfolio-pagination__link--dots">&hellip;</span></li>';
				continue;
			}

			if ( $page === $current ) {
				$html .= '<li><span class="art-portfolio-pagination__link is-current" aria-current="page">' . esc_html( (string) $page ) . '</span></li>';
				continue;
			}

			$page_label = sprintf(
				/* translators: %s: page number */
				__( 'Страница %s', 'art-portfolio' ),
				(string) absint( $page )
			);

			$html .= '<li>' . self::render_pagination_link(
				self::get_gallery_url( $page, $collection_id ),
				(string) $page,
				'',
				$page_label
			) . '</li>';
		}

		if ( $current < $total ) {
			$html .= '<li>' . self::render_pagination_link(
				self::get_gallery_url( $current + 1, $collection_id ),
				'→',
				'next',
				__( 'Следующая страница', 'art-portfolio' )
			) . '</li>';
		}

		$html .= '</ul></nav>';

		return $html;
	}

	/**
	 * Compact page number list with ellipses.
	 *
	 * @param int $current Current page.
	 * @param int $total   Total pages.
	 * @return array<int, int>
	 */
	private static function get_pagination_pages( $current, $total ) {
		if ( $total <= 7 ) {
			return range( 1, $total );
		}

		$pages = array( 1 );
		$start = max( 2, $current - 1 );
		$end   = min( $total - 1, $current + 1 );

		if ( $start > 2 ) {
			$pages[] = 0;
		}

		for ( $i = $start; $i <= $end; $i++ ) {
			$pages[] = $i;
		}

		if ( $end < $total - 1 ) {
			$pages[] = 0;
		}

		$pages[] = $total;

		return $pages;
	}

	/**
	 * One pagination link.
	 *
	 * @param string $url        Target URL.
	 * @param string $label      Visible label.
	 * @param string $rel        Optional rel attribute.
	 * @param string $aria_label Optional aria-label.
	 * @return string
	 */
	private static function render_pagination_link( $url, $label, $rel = '', $aria_label = '' ) {
		$class = 'art-portfolio-pagination__link';

		if ( 'prev' === $rel || 'next' === $rel ) {
			$class .= ' art-portfolio-pagination__link--' . $rel;
		}

		$html = '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '"';

		if ( '' !== $rel ) {
			$html .= ' rel="' . esc_attr( $rel ) . '"';
		}

		if ( '' !== $aria_label ) {
			$html .= ' aria-label="' . esc_attr( $aria_label ) . '"';
		}

		if ( 'prev' === $rel || 'next' === $rel ) {
			$html .= '><span class="art-portfolio-pagination__arrow" aria-hidden="true">' . esc_html( $label ) . '</span></a>';
		} else {
			$html .= '>' . esc_html( $label ) . '</a>';
		}

		return $html;
	}

	/**
	 * Current gallery URL with page/collection query args.
	 *
	 * @param int $page          Page number.
	 * @param int $collection_id Collection filter, 0 = all.
	 * @return string
	 */
	private static function get_gallery_url( $page, $collection_id ) {
		$url = self::get_current_url_without_fragment();
		$url = remove_query_arg( array( self::PAGE_QUERY_VAR, self::COLLECTION_QUERY_VAR ), $url );

		if ( (int) $collection_id > 0 ) {
			$url = add_query_arg( self::COLLECTION_QUERY_VAR, (int) $collection_id, $url );
		}

		if ( (int) $page > 1 ) {
			$url = add_query_arg( self::PAGE_QUERY_VAR, (int) $page, $url );
		}

		return $url;
	}

	/**
	 * Absolute current URL without a hash, so filter/pagination links do not inherit a fragment.
	 *
	 * @return string
	 */
	private static function get_current_url_without_fragment() {
		$uri = '/';

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}

		$uri = explode( '#', $uri, 2 )[0];

		if ( '' === $uri ) {
			$uri = '/';
		}

		$home = wp_parse_url( home_url( '/' ) );
		$host = isset( $home['host'] ) ? (string) $home['host'] : '';

		if ( ! empty( $home['port'] ) ) {
			$host .= ':' . $home['port'];
		}

		$scheme = isset( $home['scheme'] ) ? (string) $home['scheme'] : 'https';

		if ( '' === $host ) {
			return home_url( $uri );
		}

		return esc_url_raw( $scheme . '://' . $host . $uri );
	}

	/**
	 * Requested gallery page from the query string.
	 *
	 * @return int
	 */
	private static function get_requested_page() {
		if ( ! isset( $_GET[ self::PAGE_QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 1;
		}

		$page = absint( wp_unslash( $_GET[ self::PAGE_QUERY_VAR ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return $page > 0 ? $page : 1;
	}

	/**
	 * Requested collection filter from the query string.
	 *
	 * @param array<string, mixed> $args Display arguments.
	 * @return int
	 */
	private static function get_requested_collection_id( $args ) {
		if ( empty( $args['show_filters'] ) || ! empty( $args['collection_id'] ) ) {
			return 0;
		}

		if ( ! isset( $_GET[ self::COLLECTION_QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 0;
		}

		$term_id = absint( wp_unslash( $_GET[ self::COLLECTION_QUERY_VAR ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $term_id < 1 || ! term_exists( $term_id, Art_Portfolio_Post_Type::TAXONOMY ) ) {
			return 0;
		}

		return $term_id;
	}

	/**
	 * Per-page count with a fallback for the old `limit` argument.
	 *
	 * @param array<string, mixed> $args Raw merged arguments.
	 * @return int
	 */
	private static function resolve_per_page( $args ) {
		if ( isset( $args['per_page'] ) && is_numeric( $args['per_page'] ) && (int) $args['per_page'] > 0 ) {
			return self::clamp_int( (int) $args['per_page'], 1, 50, 10 );
		}

		if ( isset( $args['limit'] ) && (int) $args['limit'] > 0 ) {
			return self::clamp_int( (int) $args['limit'], 1, 50, 10 );
		}

		return 10;
	}

	/**
	 * Sanitize an optional hex color.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function sanitize_color( $value ) {
		$color = sanitize_hex_color( (string) $value );

		return is_string( $color ) ? $color : '';
	}

	/**
	 * Clamp an integer into a range.
	 *
	 * @param mixed $value   Raw value.
	 * @param int   $min     Minimum.
	 * @param int   $max     Maximum.
	 * @param int   $default Fallback.
	 * @return int
	 */
	private static function clamp_int( $value, $min, $max, $default ) {
		$value = is_numeric( $value ) ? (int) $value : $default;

		if ( $value < $min || $value > $max ) {
			return $default;
		}

		return $value;
	}

	/**
	 * Cast mixed values to bool, including shortcode strings.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private static function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return (int) $value > 0;
		}

		$value = strtolower( trim( (string) $value ) );

		return ! in_array( $value, array( '', '0', 'false', 'no', 'off' ), true );
	}
}
