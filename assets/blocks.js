( function ( blocks, element, serverSideRender, components, i18n ) {
	var el = element.createElement;
	var ServerSideRender = serverSideRender;
	var PanelBody = components.PanelBody;
	var RangeControl = components.RangeControl;
	var TextControl = components.TextControl;
	var InspectorControls = wp.blockEditor.InspectorControls;

	var icon = el(
		'svg',
		{ width: 24, height: 24, viewBox: '0 0 24 24' },
		el( 'path', {
			d: 'M6.5 19a4.5 4.5 0 0 1-.5-8.97A6 6 0 0 1 17.7 9.2 3.9 3.9 0 0 1 17.5 19h-11Z',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 1.6,
			strokeLinejoin: 'round',
		} )
	);

	function preview( name, attributes ) {
		return el( ServerSideRender, { block: name, attributes: attributes } );
	}

	// Bloques sin opciones
	[
		{
			name: 'snowy/avisos',
			title: 'Snowy · Avisos de AEMET',
			description: 'Avisos vigentes en La Rioja para hoy, mañana y pasado.',
			keywords: [ 'snowy', 'avisos', 'aemet', 'alertas' ],
		},
		{
			name: 'snowy/estaciones',
			title: 'Snowy · Estaciones de La Rioja',
			description: 'Tabla con las estaciones riojanas y su dato actual.',
			keywords: [ 'snowy', 'estaciones', 'temperatura' ],
		},
	].forEach( function ( b ) {
		blocks.registerBlockType( b.name, {
			apiVersion: 2,
			title: b.title,
			description: b.description,
			icon: icon,
			category: 'widgets',
			keywords: b.keywords,
			edit: function ( props ) {
				return el( 'div', wp.blockEditor.useBlockProps(), preview( b.name, props.attributes ) );
			},
			save: function () {
				return null;
			},
		} );
	} );

	// Bloques con "limite"
	[
		{
			name: 'snowy/extremos',
			title: 'Snowy · Extremos del día',
			description: 'Las estaciones más cálidas y más frías de hoy.',
			keywords: [ 'snowy', 'extremos', 'maximas', 'minimas' ],
		},
		{
			name: 'snowy/viento',
			title: 'Snowy · Rachas de viento',
			description: 'Ranking de rachas máximas registradas hoy.',
			keywords: [ 'snowy', 'viento', 'rachas', 'cierzo' ],
		},
	].forEach( function ( b ) {
		blocks.registerBlockType( b.name, {
			apiVersion: 2,
			title: b.title,
			description: b.description,
			icon: icon,
			category: 'widgets',
			keywords: b.keywords,
			attributes: { limite: { type: 'number', default: 8 } },
			edit: function ( props ) {
				return el(
					'div',
					wp.blockEditor.useBlockProps(),
					el(
						InspectorControls,
						{},
						el(
							PanelBody,
							{ title: 'Opciones' },
							el( RangeControl, {
								label: 'Cuántas estaciones mostrar',
								value: props.attributes.limite,
								min: 3,
								max: 19,
								onChange: function ( v ) {
									props.setAttributes( { limite: v } );
								},
							} )
						)
					),
					preview( b.name, props.attributes )
				);
			},
			save: function () {
				return null;
			},
		} );
	} );

	// Ficha de una estación
	blocks.registerBlockType( 'snowy/estacion', {
		apiVersion: 2,
		title: 'Snowy · Ficha de estación',
		description: 'Tarjeta con el dato de una estación concreta, para incrustar en un artículo.',
		icon: icon,
		category: 'widgets',
		keywords: [ 'snowy', 'estacion', 'ficha' ],
		attributes: { id: { type: 'string', default: '' } },
		edit: function ( props ) {
			return el(
				'div',
				wp.blockEditor.useBlockProps(),
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: 'Estación' },
						el( TextControl, {
							label: 'Identificador',
							help: 'Por ejemplo 9115X. Los tienes en Widgets Snowy.',
							value: props.attributes.id,
							onChange: function ( v ) {
								props.setAttributes( { id: v } );
							},
						} )
					)
				),
				props.attributes.id
					? preview( 'snowy/estacion', props.attributes )
					: el(
							'p',
							{ style: { padding: '1rem', background: '#f6f7f7', margin: 0 } },
							'Indica el identificador de la estación en el panel lateral.'
					  )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( wp.blocks, wp.element, wp.serverSideRender, wp.components, wp.i18n );
