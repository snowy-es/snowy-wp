( function ( blocks, element, serverSideRender, components, i18n ) {
	var el = element.createElement;
	var ServerSideRender = serverSideRender;
	var PanelBody = components.PanelBody;
	var RangeControl = components.RangeControl;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var __ = i18n.__;

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

	// El modo se guarda como la misma cadena que acepta el shortcode, para que
	// bloque y shortcode compartan render_callback sin traducir nada por medio.
	var MODO = { VIVO: 'vivo', SNAPSHOT: 'snapshot' };

	var CONGELAR = {
		type: 'toggle',
		attr: 'modo',
		label: __( 'Congelar los datos', 'snowy-wp' ),
		help: __(
			'Guarda el dato tal y como está al publicar. Para artículos de actualidad, que no deben cambiar con el tiempo.',
			'snowy-wp'
		),
		toValue: function ( on ) {
			return on ? MODO.SNAPSHOT : MODO.VIVO;
		},
		fromValue: function ( v ) {
			return v === MODO.SNAPSHOT;
		},
	};

	var BLOQUES = [
		{
			name: 'snowy/avisos',
			title: __( 'Snowy · Avisos de AEMET', 'snowy-wp' ),
			description: __( 'Avisos vigentes para hoy, mañana y pasado.', 'snowy-wp' ),
			keywords: [ 'snowy', 'avisos', 'aemet', 'alertas' ],
			attributes: { modo: { type: 'string', default: MODO.VIVO } },
			panel: __( 'Opciones', 'snowy-wp' ),
			controls: [ CONGELAR ],
		},
		{
			name: 'snowy/estaciones',
			title: __( 'Snowy · Estaciones', 'snowy-wp' ),
			description: __( 'Tabla con las estaciones de la red y su dato actual.', 'snowy-wp' ),
			keywords: [ 'snowy', 'estaciones', 'temperatura' ],
			attributes: {
				modo: { type: 'string', default: MODO.VIVO },
				ids: { type: 'string', default: '' },
				titulo: { type: 'string', default: '' },
			},
			panel: __( 'Opciones', 'snowy-wp' ),
			controls: [
				{
					type: 'text',
					attr: 'titulo',
					label: __( 'Título', 'snowy-wp' ),
					help: __( 'Vacío usa el de la región configurada.', 'snowy-wp' ),
				},
				{
					type: 'text',
					attr: 'ids',
					label: __( 'Solo estas estaciones', 'snowy-wp' ),
					help: __(
						'Identificadores separados por comas. Vacío muestra todas. Los tienes en Widgets Snowy.',
						'snowy-wp'
					),
				},
				CONGELAR,
			],
		},
		{
			name: 'snowy/extremos',
			title: __( 'Snowy · Extremos del día', 'snowy-wp' ),
			description: __( 'Las estaciones más cálidas y más frías de hoy.', 'snowy-wp' ),
			keywords: [ 'snowy', 'extremos', 'maximas', 'minimas' ],
			attributes: { limite: { type: 'number', default: 8 } },
			panel: __( 'Opciones', 'snowy-wp' ),
			controls: [
				{
					type: 'range',
					attr: 'limite',
					label: __( 'Cuántas estaciones mostrar', 'snowy-wp' ),
					min: 3,
					max: 40,
				},
			],
		},
		{
			name: 'snowy/viento',
			title: __( 'Snowy · Rachas de viento', 'snowy-wp' ),
			description: __( 'Ranking de rachas máximas registradas hoy.', 'snowy-wp' ),
			keywords: [ 'snowy', 'viento', 'rachas' ],
			attributes: { limite: { type: 'number', default: 8 } },
			panel: __( 'Opciones', 'snowy-wp' ),
			controls: [
				{
					type: 'range',
					attr: 'limite',
					label: __( 'Cuántas estaciones mostrar', 'snowy-wp' ),
					min: 3,
					max: 40,
				},
			],
		},
		{
			name: 'snowy/estacion',
			title: __( 'Snowy · Ficha de estación', 'snowy-wp' ),
			description: __(
				'Tarjeta con el dato de una estación concreta, para incrustar en un artículo.',
				'snowy-wp'
			),
			keywords: [ 'snowy', 'estacion', 'ficha' ],
			attributes: { id: { type: 'string', default: '' } },
			panel: __( 'Estación', 'snowy-wp' ),
			requires: 'id',
			requiresHint: __( 'Indica el identificador de la estación en el panel lateral.', 'snowy-wp' ),
			controls: [
				{
					type: 'text',
					attr: 'id',
					label: __( 'Identificador', 'snowy-wp' ),
					help: __( 'Los identificadores están listados en Widgets Snowy.', 'snowy-wp' ),
				},
			],
		},
	];

	function render( control, props ) {
		var value = props.attributes[ control.attr ];
		var set = function ( v ) {
			var patch = {};
			patch[ control.attr ] = control.toValue ? control.toValue( v ) : v;
			props.setAttributes( patch );
		};

		if ( control.type === 'range' ) {
			return el( RangeControl, {
				key: control.attr,
				label: control.label,
				value: value,
				min: control.min,
				max: control.max,
				onChange: set,
			} );
		}

		if ( control.type === 'toggle' ) {
			return el( ToggleControl, {
				key: control.attr,
				label: control.label,
				help: control.help,
				checked: control.fromValue( value ),
				onChange: set,
			} );
		}

		return el( TextControl, {
			key: control.attr,
			label: control.label,
			help: control.help,
			value: value,
			onChange: set,
		} );
	}

	BLOQUES.forEach( function ( b ) {
		blocks.registerBlockType( b.name, {
			apiVersion: 2,
			title: b.title,
			description: b.description,
			icon: icon,
			category: 'widgets',
			keywords: b.keywords,
			attributes: b.attributes,
			edit: function ( props ) {
				var falta = b.requires && ! props.attributes[ b.requires ];

				return el(
					'div',
					wp.blockEditor.useBlockProps(),
					el(
						InspectorControls,
						{},
						el(
							PanelBody,
							{ title: b.panel },
							b.controls.map( function ( c ) {
								return render( c, props );
							} )
						)
					),
					falta
						? el(
								'p',
								{ style: { padding: '1rem', background: '#f6f7f7', margin: 0 } },
								b.requiresHint
						  )
						: el( ServerSideRender, { block: b.name, attributes: props.attributes } )
				);
			},
			save: function () {
				return null;
			},
		} );
	} );
} )( wp.blocks, wp.element, wp.serverSideRender, wp.components, wp.i18n );
