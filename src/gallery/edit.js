import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	BaseControl,
	Button,
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const editorBlockHint = __(
	'Этот блок откроется на фронтенде. Здесь — только настройки.',
	'art-portfolio'
);

const desktopColumns = [
	{ label: '1', value: '1' },
	{ label: '2', value: '2' },
	{ label: '3', value: '3' },
	{ label: '4', value: '4' },
];

const tabletColumns = [
	{ label: '1', value: '1' },
	{ label: '2', value: '2' },
	{ label: '3', value: '3' },
];

const mobileColumns = [
	{ label: '1', value: '1' },
	{ label: '2', value: '2' },
];

const layoutOptions = [
	{ label: __( 'Сетка — одинаковые карточки', 'art-portfolio' ), value: 'grid' },
	{ label: __( 'Masonry — карточки разной высоты', 'art-portfolio' ), value: 'masonry' },
];

const buttonAlignOptions = [
	{ label: __( 'Слева', 'art-portfolio' ), value: 'left' },
	{ label: __( 'По центру', 'art-portfolio' ), value: 'center' },
	{ label: __( 'Справа', 'art-portfolio' ), value: 'right' },
	{ label: __( 'На всю ширину', 'art-portfolio' ), value: 'full' },
];

const colorDefaults = {
	colorTitle: '#1e1e1e',
	colorBadge: '#ffffff',
	colorBadgeBg: '#111111',
	colorDescription: '#50575e',
	colorMetaLabel: '#646970',
	colorMetaValue: '#1e1e1e',
	colorButton: '#ffffff',
	colorButtonBg: '#1e1e1e',
	colorCardBg: '#ffffff',
};

function normalizeHex( value, fallback ) {
	const raw = String( value || '' ).trim();

	if ( /^#[0-9A-Fa-f]{6}$/.test( raw ) ) {
		return raw;
	}

	if ( /^#[0-9A-Fa-f]{3}$/.test( raw ) ) {
		return '#' + raw[ 1 ] + raw[ 1 ] + raw[ 2 ] + raw[ 2 ] + raw[ 3 ] + raw[ 3 ];
	}

	return fallback;
}

function renderColorControl( label, attributeKey, attributes, setAttributes ) {
	const fallback = colorDefaults[ attributeKey ];
	const current = attributes[ attributeKey ] || '';

	return createElement(
		BaseControl,
		{ label: label, className: 'art-portfolio-editor-color-control' },
		createElement(
			'div',
			{ className: 'art-portfolio-editor-color-control__row' },
			createElement(
				'span',
				{ className: 'art-portfolio-editor-color-control__picker-wrap' },
				createElement( 'input', {
					type: 'color',
					className: 'art-portfolio-editor-color-control__picker',
					value: normalizeHex( current, fallback ),
					onChange: function ( event ) {
						const patch = {};
						patch[ attributeKey ] = event.target.value;
						setAttributes( patch );
					},
				} )
			),
			createElement( TextControl, {
				value: current,
				placeholder: fallback,
				onChange: function ( nextValue ) {
					const patch = {};
					patch[ attributeKey ] = nextValue;
					setAttributes( patch );
				},
			} ),
			createElement(
				Button,
				{
					variant: 'secondary',
					onClick: function () {
						const patch = {};
						patch[ attributeKey ] = '';
						setAttributes( patch );
					},
				},
				__( 'Сбросить', 'art-portfolio' )
			)
		)
	);
}

export default function Edit( props ) {
	const attributes = props.attributes;
	const setAttributes = props.setAttributes;
	const columns = attributes.columns;
	const tablet = attributes.tabletColumns;
	const mobile = attributes.mobileColumns;
	const gap = attributes.gap;
	const layout = attributes.layout;
	const perPage = attributes.perPage;
	const collectionId = attributes.collectionId;
	const showFilters = attributes.showFilters;
	const showBadge = attributes.showBadge;
	const showDescription = attributes.showDescription;
	const showMeta = attributes.showMeta;
	const showButton = attributes.showButton;
	const buttonText = attributes.buttonText;
	const buttonAlign = attributes.buttonAlign;

	const blockProps = useBlockProps( {
		className: 'art-portfolio-editor-shell',
	} );

	const itemsQuery = { per_page: 1, status: 'publish' };
	const collectionsQuery = { per_page: 100, hide_empty: false };

	const selection = useSelect(
		function ( select ) {
			const core = select( 'core' );
			const records =
				core.getEntityRecords( 'postType', 'art_portfolio_item', itemsQuery ) || [];
			const collections =
				core.getEntityRecords(
					'taxonomy',
					'art_portfolio_collection',
					collectionsQuery
				) || [];

			return {
				hasItems: records.length > 0,
				isResolved: core.hasFinishedResolution( 'getEntityRecords', [
					'postType',
					'art_portfolio_item',
					itemsQuery,
				] ),
				collections: collections,
			};
		},
		[]
	);

	const collectionOptions = [
		{
			label: __( 'Все работы', 'art-portfolio' ),
			value: '0',
		},
	].concat(
		selection.collections.map( function ( term ) {
			return {
				label: term.name,
				value: String( term.id ),
			};
		} )
	);

	const emptyNotice =
		selection.isResolved && ! selection.hasItems
			? createElement(
					'div',
					{ className: 'art-portfolio-editor-empty' },
					__( 'Добавьте работы в разделе ART Portfolio.', 'art-portfolio' )
			  )
			: null;

	const collectionHelp =
		selection.collections.length === 0
			? __( 'Создай подборки в ART Portfolio → Подборки, затем привяжи к ним работы.', 'art-portfolio' )
			: __( 'Можно показать все работы или одну подборку.', 'art-portfolio' );

	return createElement(
		Fragment,
		null,
		createElement(
			InspectorControls,
			null,
			createElement(
				PanelBody,
				{ title: __( 'Содержимое', 'art-portfolio' ), initialOpen: true },
				createElement( SelectControl, {
					label: __( 'Что показывать', 'art-portfolio' ),
					help: collectionHelp,
					value: String( collectionId || 0 ),
					options: collectionOptions,
					onChange: function ( value ) {
						setAttributes( { collectionId: parseInt( value, 10 ) || 0 } );
					},
				} ),
				createElement( ToggleControl, {
					label: __( 'Показывать фильтры подборок', 'art-portfolio' ),
					help: __(
						'Чипы над галереей. Появляются, если выбраны все работы и в них есть хотя бы две подборки.',
						'art-portfolio'
					),
					checked: showFilters,
					disabled: parseInt( collectionId, 10 ) > 0,
					onChange: function ( value ) {
						setAttributes( { showFilters: value } );
					},
				} )
			),
			createElement(
				PanelBody,
				{ title: __( 'Сетка', 'art-portfolio' ) },
				createElement( SelectControl, {
					label: __( 'Вид галереи', 'art-portfolio' ),
					help: __(
						'Masonry: карточки заполняют колонки снизу вверх, высота зависит от содержимого.',
						'art-portfolio'
					),
					value: layout === 'mosaic' || ! layout ? 'masonry' : layout,
					options: layoutOptions,
					onChange: function ( value ) {
						setAttributes( { layout: value || 'masonry' } );
					},
				} ),
				createElement( SelectControl, {
					label: __( 'Колонки на компьютере', 'art-portfolio' ),
					value: String( columns ),
					options: desktopColumns,
					onChange: function ( value ) {
						setAttributes( { columns: parseInt( value, 10 ) } );
					},
				} ),
				createElement( SelectControl, {
					label: __( 'Колонки на планшете', 'art-portfolio' ),
					value: String( tablet ),
					options: tabletColumns,
					onChange: function ( value ) {
						setAttributes( { tabletColumns: parseInt( value, 10 ) } );
					},
				} ),
				createElement( SelectControl, {
					label: __( 'Колонки на телефоне', 'art-portfolio' ),
					value: String( mobile ),
					options: mobileColumns,
					onChange: function ( value ) {
						setAttributes( { mobileColumns: parseInt( value, 10 ) } );
					},
				} ),
				createElement( RangeControl, {
					label: __( 'Расстояние между карточками', 'art-portfolio' ),
					value: gap,
					min: 0,
					max: 80,
					onChange: function ( value ) {
						setAttributes( { gap: value } );
					},
				} ),
				createElement( RangeControl, {
					label: __( 'Количество работ до пагинации', 'art-portfolio' ),
					help: __( 'Сколько карточек показывать на одной странице.', 'art-portfolio' ),
					value: perPage,
					min: 1,
					max: 50,
					onChange: function ( value ) {
						setAttributes( { perPage: parseInt( value, 10 ) || 10 } );
					},
				} )
			),
			createElement(
				PanelBody,
				{ title: __( 'Элементы карточки', 'art-portfolio' ) },
				createElement( ToggleControl, {
					label: __( 'Показывать бейдж', 'art-portfolio' ),
					checked: showBadge,
					onChange: function ( value ) {
						setAttributes( { showBadge: value } );
					},
				} ),
				createElement( ToggleControl, {
					label: __( 'Показывать описание', 'art-portfolio' ),
					checked: showDescription,
					onChange: function ( value ) {
						setAttributes( { showDescription: value } );
					},
				} ),
				createElement( ToggleControl, {
					label: __( 'Показывать параметры проекта', 'art-portfolio' ),
					checked: showMeta,
					onChange: function ( value ) {
						setAttributes( { showMeta: value } );
					},
				} ),
				createElement( ToggleControl, {
					label: __( 'Показывать кнопку', 'art-portfolio' ),
					checked: showButton,
					onChange: function ( value ) {
						setAttributes( { showButton: value } );
					},
				} ),
				showButton
					? createElement( TextControl, {
							label: __( 'Текст кнопки', 'art-portfolio' ),
							help: __( 'Один текст для всех карточек в этом блоке. Кнопка ведёт на страницу работы.', 'art-portfolio' ),
							value: buttonText,
							onChange: function ( value ) {
								setAttributes( { buttonText: value } );
							},
					  } )
					: null
			)
		),
		createElement(
			InspectorControls,
			{ group: 'styles' },
			createElement(
				PanelBody,
				{ title: __( 'Кнопка', 'art-portfolio' ), initialOpen: true },
				createElement( SelectControl, {
					label: __( 'Расположение кнопки', 'art-portfolio' ),
					value: buttonAlign || 'left',
					options: buttonAlignOptions,
					onChange: function ( value ) {
						setAttributes( { buttonAlign: value || 'left' } );
					},
				} )
			),
			createElement(
				PanelBody,
				{ title: __( 'Цвета', 'art-portfolio' ), initialOpen: true },
				renderColorControl( __( 'Заголовок', 'art-portfolio' ), 'colorTitle', attributes, setAttributes ),
				renderColorControl( __( 'Бейдж: текст', 'art-portfolio' ), 'colorBadge', attributes, setAttributes ),
				renderColorControl( __( 'Бейдж: фон', 'art-portfolio' ), 'colorBadgeBg', attributes, setAttributes ),
				renderColorControl( __( 'Описание', 'art-portfolio' ), 'colorDescription', attributes, setAttributes ),
				renderColorControl( __( 'Параметры: название', 'art-portfolio' ), 'colorMetaLabel', attributes, setAttributes ),
				renderColorControl( __( 'Параметры: значение', 'art-portfolio' ), 'colorMetaValue', attributes, setAttributes ),
				renderColorControl( __( 'Кнопка: текст', 'art-portfolio' ), 'colorButton', attributes, setAttributes ),
				renderColorControl( __( 'Кнопка: фон', 'art-portfolio' ), 'colorButtonBg', attributes, setAttributes ),
				renderColorControl( __( 'Карточка: фон', 'art-portfolio' ), 'colorCardBg', attributes, setAttributes )
			)
		),
		createElement(
			'div',
			blockProps,
			createElement(
				'div',
				{ className: 'art-portfolio-editor-title' },
				__( 'АРТ Портфолио: Галерея', 'art-portfolio' )
			),
			createElement( 'div', { className: 'art-portfolio-editor-hint' }, editorBlockHint ),
			emptyNotice
		)
	);
}
