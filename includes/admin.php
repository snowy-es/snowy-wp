<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Catalogo de piezas disponibles. Es la fuente de la pagina de ayuda del admin
 * y evita que la lista se desincronice de lo que el plugin registra de verdad.
 */
function snowy_wp_widgets_help()
{
    return [
        'avisos' => [
            'code'  => '[snowy_avisos]',
            'name'  => __('Avisos de AEMET', 'snowy-wp'),
            'desc'  => __('Avisos vigentes para hoy, mañana y pasado, con nivel y horario. Si no hay ninguno, lo dice.', 'snowy-wp'),
            'attrs' => [
                'modo' => __('vivo (por defecto) o snapshot para congelar los avisos dentro de un artículo.', 'snowy-wp'),
                'nivel' => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
            'block'   => __('Snowy · Avisos de AEMET', 'snowy-wp'),
            'ejemplo' => '[snowy_avisos modo="snapshot"]',
        ],
        'extremos' => [
            'code'  => '[snowy_extremos limite="8"]',
            'name'  => __('Extremos del día', 'snowy-wp'),
            'desc'  => __('Las estaciones más cálidas y más frías de hoy.', 'snowy-wp'),
            'attrs' => [
                'limite' => __('Cuántas estaciones se muestran en cada columna. Por defecto 8.', 'snowy-wp'),
                'nivel' => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
            'block'   => __('Snowy · Extremos del día', 'snowy-wp'),
            'ejemplo' => '[snowy_extremos limite="5" nivel="h2"]',
        ],
        'estaciones' => [
            'code'  => '[snowy_estaciones]',
            'name'  => __('Todas las estaciones', 'snowy-wp'),
            'desc'  => __('Tabla completa con temperatura actual, máxima, mínima y humedad.', 'snowy-wp'),
            'attrs' => [
                'ids'    => __('Identificadores separados por comas para mostrar solo unas cuantas. Vacío las muestra todas.', 'snowy-wp'),
                'titulo' => __('Encabezado propio. Vacío usa el de la región configurada.', 'snowy-wp'),
                'modo'   => __('vivo (por defecto) o snapshot para congelar la tabla dentro de un artículo.', 'snowy-wp'),
                'nivel' => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
            'block'   => __('Snowy · Estaciones', 'snowy-wp'),
            'ejemplo' => '[snowy_estaciones ids="9115X,ILOGRO41" titulo="Nuestras estaciones"]',
        ],
        'viento' => [
            'code'  => '[snowy_viento limite="8"]',
            'name'  => __('Rachas de viento', 'snowy-wp'),
            'desc'  => __('Ranking de rachas máximas registradas hoy.', 'snowy-wp'),
            'attrs' => [
                'limite' => __('Cuántas estaciones se muestran. Por defecto 8.', 'snowy-wp'),
                'nivel' => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
            'block'   => __('Snowy · Rachas de viento', 'snowy-wp'),
            'ejemplo' => '[snowy_viento limite="10"]',
        ],
        'aire' => [
            'code'  => '[snowy_aire]',
            'name'  => __('Calidad del aire', 'snowy-wp'),
            'desc'  => __('Índice europeo con su nivel, más PM2,5, PM10, ozono y NO₂. Se ubica solo en el centro de tus estaciones.', 'snowy-wp'),
            'attrs' => [
                'lat' => __('Latitud del punto a consultar. Vacío usa el centro de la región configurada.', 'snowy-wp'),
                'lon' => __('Longitud del punto a consultar.', 'snowy-wp'),
                'nivel' => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
            'block'   => __('Snowy · Calidad del aire', 'snowy-wp'),
            'ejemplo' => '[snowy_aire]',
        ],
        'polen' => [
            'code'  => '[snowy_polen]',
            'name'  => __('Polen en el aire', 'snowy-wp'),
            'desc'  => __('Gramíneas, olivo, abedul, aliso, artemisa y ambrosía, con su nivel de riesgo. Solo lista los que tienen presencia.', 'snowy-wp'),
            'attrs' => [
                'todos' => __('si para listar también los que están a cero. Por defecto no.', 'snowy-wp'),
                'lat'   => __('Latitud del punto. Vacío usa el centro de la región configurada.', 'snowy-wp'),
                'lon'   => __('Longitud del punto.', 'snowy-wp'),
                'nivel' => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
            'block'   => __('Snowy · Polen', 'snowy-wp'),
            'ejemplo' => '[snowy_polen todos="si"]',
        ],
        'lluvia' => [
            'code'    => '[snowy_lluvia]',
            'name'    => __('Lluvia acumulada', 'snowy-wp'),
            'desc'    => __('Acumulados del día, solo de las estaciones que han recogido algo. Si no ha llovido en ninguna, lo dice.', 'snowy-wp'),
            'block'   => __('Snowy · Lluvia acumulada', 'snowy-wp'),
            'ejemplo' => '[snowy_lluvia limite="10"]',
            'attrs'   => [
                'limite' => __('Cuántas estaciones se muestran. Por defecto 10.', 'snowy-wp'),
                'nivel'  => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'ranking' => [
            'code'    => '[snowy_ranking variable="racha"]',
            'name'    => __('Ranking por variable', 'snowy-wp'),
            'desc'    => __('La misma tabla para cualquier magnitud de la red, ordenada como corresponda a cada una.', 'snowy-wp'),
            'block'   => __('Snowy · Ranking', 'snowy-wp'),
            'ejemplo' => '[snowy_ranking variable="humedad" limite="10" titulo="Las más húmedas"]',
            'attrs'   => [
                'variable' => __('temperatura, maxima, minima, lluvia, racha, humedad o presion.', 'snowy-wp'),
                'limite'   => __('Cuántas estaciones se muestran. Por defecto 8.', 'snowy-wp'),
                'orden'    => __('asc o desc para invertir el orden natural de la variable.', 'snowy-wp'),
                'titulo'   => __('Encabezado propio.', 'snowy-wp'),
                'nivel'    => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'comparador' => [
            'code'    => '[snowy_comparador ids="9115X,ILOGRO41"]',
            'name'    => __('Comparativa de estaciones', 'snowy-wp'),
            'desc'    => __('Dos o más estaciones en columnas, con temperatura, extremos, humedad, racha y lluvia. Para contrastar montaña y valle dentro de un artículo.', 'snowy-wp'),
            'block'   => __('Snowy · Comparativa', 'snowy-wp'),
            'ejemplo' => '[snowy_comparador ids="9115X,ILOGRO41" titulo="Sierra frente a valle"]',
            'attrs'   => [
                'ids'    => __('Identificadores separados por comas. Hacen falta al menos dos.', 'snowy-wp'),
                'titulo' => __('Encabezado propio.', 'snowy-wp'),
                'nivel'  => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'calima' => [
            'code'    => '[snowy_calima]',
            'name'    => __('Polvo sahariano', 'snowy-wp'),
            'desc'    => __('Intensidad prevista de calima por días. Si no se espera polvo, lo dice en una frase.', 'snowy-wp'),
            'block'   => __('Snowy · Polvo sahariano', 'snowy-wp'),
            'ejemplo' => '[snowy_calima dias="5"]',
            'attrs'   => [
                'dias'  => __('Cuántos días se resumen, hasta 5. Por defecto 4.', 'snowy-wp'),
                'lat'   => __('Latitud del punto. Vacío usa el centro de la región configurada.', 'snowy-wp'),
                'lon'   => __('Longitud del punto.', 'snowy-wp'),
                'nivel' => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'embalses' => [
            'code'    => '[snowy_embalses]',
            'name'    => __('Embalses', 'snowy-wp'),
            'desc'    => __('Agua embalsada de la región: el porcentaje del conjunto arriba y el desglose por embalse debajo, con la variación de la semana.', 'snowy-wp'),
            'block'   => __('Snowy · Embalses', 'snowy-wp'),
            'ejemplo' => '[snowy_embalses limite="10" cuenca="Ebro"]',
            'attrs'   => [
                'region' => __('Comunidad autónoma. Vacío usa la región configurada en los ajustes.', 'snowy-wp'),
                'cuenca' => __('Filtra por cuenca hidrográfica, por ejemplo Ebro.', 'snowy-wp'),
                'limite' => __('Cuántos embalses se listan. 0 o vacío los lista todos.', 'snowy-wp'),
                'lluvia' => __('no para ocultar la lluvia prevista sobre la cuenca. Por defecto se muestra.', 'snowy-wp'),
                'titulo' => __('Encabezado propio.', 'snowy-wp'),
                'nivel'  => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'prevision' => [
            'code'    => '[snowy_prevision loc="logrono-espana"]',
            'name'    => __('Previsión por días', 'snowy-wp'),
            'desc'    => __('Previsión de los próximos días para una localidad, con el consenso de ECMWF, GFS e ICON: los mismos tres modelos que cruza snowy.es. Cada día muestra cuántos modelos predicen lluvia y qué confianza hay. Es el único widget que no es dato medido, y lo dice en su pie.', 'snowy-wp'),
            'block'   => __('Snowy · Previsión', 'snowy-wp'),
            'ejemplo' => '[snowy_prevision loc="haro-espana" dias="7"]',
            'attrs'   => [
                'loc'    => __('Identificador de la localidad en snowy.es, el de su dirección: snowy.es/tiempo/logrono-espana.', 'snowy-wp'),
                'lat'    => __('Alternativa a loc: latitud del punto.', 'snowy-wp'),
                'lon'    => __('Longitud del punto.', 'snowy-wp'),
                'dias'   => __('Cuántos días se muestran, hasta 10. Por defecto 5.', 'snowy-wp'),
                'nombre' => __('Nombre del lugar a mostrar, si el de la localidad no encaja.', 'snowy-wp'),
                'titulo' => __('Encabezado propio.', 'snowy-wp'),
                'nivel'  => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'clima' => [
            'code'    => '[snowy_clima]',
            'name'    => __('Cambio climático del lugar', 'snowy-wp'),
            'desc'    => __('Cuánto se ha calentado el punto desde 1950 sobre la serie de ERA5-Land, con los días de calor y de helada de antes y de ahora, y los récords.', 'snowy-wp'),
            'block'   => __('Snowy · Cambio climático', 'snowy-wp'),
            'ejemplo' => '[snowy_clima loc="logrono-espana" records="no"]',
            'attrs'   => [
                'region'  => __('Código de una comunidad o provincia (ES-RI, ES-LO…): pinta la media de toda la región.', 'snowy-wp'),
                'loc'     => __('Identificador de la localidad en snowy.es. Vacío usa el centro de tus estaciones.', 'snowy-wp'),
                'lat'     => __('Alternativa a loc: latitud del punto.', 'snowy-wp'),
                'lon'     => __('Longitud del punto.', 'snowy-wp'),
                'records' => __('no para ocultar los récords históricos. Por defecto se muestran.', 'snowy-wp'),
                'nombre'  => __('Nombre del lugar a mostrar.', 'snowy-wp'),
                'titulo'  => __('Encabezado propio.', 'snowy-wp'),
                'nivel'   => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'clima-calendario' => [
            'code'    => '[snowy_clima_calendario region="ES-RI"]',
            'name'    => __('Calendario del año', 'snowy-wp'),
            'desc'    => __('Cada día del año frente a lo normal de su fecha: el percentil de su temperatura media entre los mismos días de 1991-2020, con la parte de días cálidos y fríos.', 'snowy-wp'),
            'block'   => __('Snowy · Clima: calendario del año', 'snowy-wp'),
            'ejemplo' => '[snowy_clima_calendario region="ES-RI"]',
            'attrs'   => [
                'region'    => __('Código de una comunidad o provincia del catálogo de la API (ES-RI, ES-LO…). Pinta la media de toda la región.', 'snowy-wp'),
                'ubicacion' => __('Sin región: identificador de la localidad en snowy.es. Vacío usa el centro de tus estaciones.', 'snowy-wp'),
                'lat'       => __('Alternativa: latitud del punto.', 'snowy-wp'),
                'lon'       => __('Longitud del punto.', 'snowy-wp'),
                'anio' => __('Año. Vacío usa el año en curso (el 1 de enero, el anterior).', 'snowy-wp'),
                'nombre'    => __('Nombre del lugar a mostrar.', 'snowy-wp'),
                'titulo'    => __('Encabezado propio.', 'snowy-wp'),
                'nivel'     => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'clima-meses' => [
            'code'    => '[snowy_clima_meses region="ES-RI"]',
            'name'    => __('Mes a mes', 'snowy-wp'),
            'desc'    => __('La temperatura media de cada mes del año frente a la de 1991-2020, en barras.', 'snowy-wp'),
            'block'   => __('Snowy · Clima: mes a mes', 'snowy-wp'),
            'ejemplo' => '[snowy_clima_meses region="ES-RI"]',
            'attrs'   => [
                'region'    => __('Código de una comunidad o provincia del catálogo de la API (ES-RI, ES-LO…). Pinta la media de toda la región.', 'snowy-wp'),
                'ubicacion' => __('Sin región: identificador de la localidad en snowy.es. Vacío usa el centro de tus estaciones.', 'snowy-wp'),
                'lat'       => __('Alternativa: latitud del punto.', 'snowy-wp'),
                'lon'       => __('Longitud del punto.', 'snowy-wp'),
                'anio' => __('Año. Vacío usa el año en curso.', 'snowy-wp'),
                'nombre'    => __('Nombre del lugar a mostrar.', 'snowy-wp'),
                'titulo'    => __('Encabezado propio.', 'snowy-wp'),
                'nivel'     => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'clima-extremo' => [
            'code'    => '[snowy_clima_extremo region="ES-RI"]',
            'name'    => __('¿Fue un año extremo?', 'snowy-wp'),
            'desc'    => __('El último año completo dentro de la campana de los años de 1991-2020, con su puesto en la serie.', 'snowy-wp'),
            'block'   => __('Snowy · Clima: ¿año extremo?', 'snowy-wp'),
            'ejemplo' => '[snowy_clima_extremo region="ES-RI"]',
            'attrs'   => [
                'region'    => __('Código de una comunidad o provincia del catálogo de la API (ES-RI, ES-LO…). Pinta la media de toda la región.', 'snowy-wp'),
                'ubicacion' => __('Sin región: identificador de la localidad en snowy.es. Vacío usa el centro de tus estaciones.', 'snowy-wp'),
                'lat'       => __('Alternativa: latitud del punto.', 'snowy-wp'),
                'lon'       => __('Longitud del punto.', 'snowy-wp'),
                'anio' => __('Año. Vacío usa el último año completo.', 'snowy-wp'),
                'variable' => __('media (por defecto), extremos (media de máxima y mínima), maxima o minima.', 'snowy-wp'),
                'nombre'    => __('Nombre del lugar a mostrar.', 'snowy-wp'),
                'titulo'    => __('Encabezado propio.', 'snowy-wp'),
                'nivel'     => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'clima-serie' => [
            'code'    => '[snowy_clima_serie region="ES-RI"]',
            'name'    => __('Serie anual con tendencia', 'snowy-wp'),
            'desc'    => __('La temperatura de cada año desde 1950 con su recta de tendencia, el año más cálido y el más frío.', 'snowy-wp'),
            'block'   => __('Snowy · Clima: serie anual', 'snowy-wp'),
            'ejemplo' => '[snowy_clima_serie region="ES-RI"]',
            'attrs'   => [
                'region'    => __('Código de una comunidad o provincia del catálogo de la API (ES-RI, ES-LO…). Pinta la media de toda la región.', 'snowy-wp'),
                'ubicacion' => __('Sin región: identificador de la localidad en snowy.es. Vacío usa el centro de tus estaciones.', 'snowy-wp'),
                'lat'       => __('Alternativa: latitud del punto.', 'snowy-wp'),
                'lon'       => __('Longitud del punto.', 'snowy-wp'),
                'variable' => __('media (por defecto), extremos, maxima o minima.', 'snowy-wp'),
                'nombre'    => __('Nombre del lugar a mostrar.', 'snowy-wp'),
                'titulo'    => __('Encabezado propio.', 'snowy-wp'),
                'nivel'     => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'clima-franjas' => [
            'code'    => '[snowy_clima_franjas region="ES-RI"]',
            'name'    => __('Franjas climáticas', 'snowy-wp'),
            'desc'    => __('Una franja por año, del azul al rojo, y cuánto ha subido la temperatura.', 'snowy-wp'),
            'block'   => __('Snowy · Clima: franjas', 'snowy-wp'),
            'ejemplo' => '[snowy_clima_franjas region="ES-RI"]',
            'attrs'   => [
                'region'    => __('Código de una comunidad o provincia del catálogo de la API (ES-RI, ES-LO…). Pinta la media de toda la región.', 'snowy-wp'),
                'ubicacion' => __('Sin región: identificador de la localidad en snowy.es. Vacío usa el centro de tus estaciones.', 'snowy-wp'),
                'lat'       => __('Alternativa: latitud del punto.', 'snowy-wp'),
                'lon'       => __('Longitud del punto.', 'snowy-wp'),
                'variable' => __('media (por defecto), extremos, maxima o minima.', 'snowy-wp'),
                'cifra' => __('no para quitar la cifra del cambio.', 'snowy-wp'),
                'nombre'    => __('Nombre del lugar a mostrar.', 'snowy-wp'),
                'titulo'    => __('Encabezado propio.', 'snowy-wp'),
                'nivel'     => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'clima-veranos' => [
            'code'    => '[snowy_clima_veranos region="ES-RI"]',
            'name'    => __('Veranos por décadas', 'snowy-wp'),
            'desc'    => __('Qué parte de los días de junio a agosto de cada década estuvo por encima del percentil 80 de su fecha.', 'snowy-wp'),
            'block'   => __('Snowy · Clima: veranos por décadas', 'snowy-wp'),
            'ejemplo' => '[snowy_clima_veranos region="ES-RI"]',
            'attrs'   => [
                'region'    => __('Código de una comunidad o provincia del catálogo de la API (ES-RI, ES-LO…). Pinta la media de toda la región.', 'snowy-wp'),
                'ubicacion' => __('Sin región: identificador de la localidad en snowy.es. Vacío usa el centro de tus estaciones.', 'snowy-wp'),
                'lat'       => __('Alternativa: latitud del punto.', 'snowy-wp'),
                'lon'       => __('Longitud del punto.', 'snowy-wp'),
                'nombre'    => __('Nombre del lugar a mostrar.', 'snowy-wp'),
                'titulo'    => __('Encabezado propio.', 'snowy-wp'),
                'nivel'     => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'clima-cambios' => [
            'code'    => '[snowy_clima_cambios region="ES-RI"]',
            'name'    => __('Antes y ahora', 'snowy-wp'),
            'desc'    => __('Temperatura media, días de calor, días tórridos, noches tropicales y heladas: los primeros treinta años de la serie frente a los diez últimos.', 'snowy-wp'),
            'block'   => __('Snowy · Clima: antes y ahora', 'snowy-wp'),
            'ejemplo' => '[snowy_clima_cambios region="ES-RI"]',
            'attrs'   => [
                'region'    => __('Código de una comunidad o provincia del catálogo de la API (ES-RI, ES-LO…). Pinta la media de toda la región.', 'snowy-wp'),
                'ubicacion' => __('Sin región: identificador de la localidad en snowy.es. Vacío usa el centro de tus estaciones.', 'snowy-wp'),
                'lat'       => __('Alternativa: latitud del punto.', 'snowy-wp'),
                'lon'       => __('Longitud del punto.', 'snowy-wp'),
                'variable' => __('media (por defecto), extremos, maxima o minima.', 'snowy-wp'),
                'nombre'    => __('Nombre del lugar a mostrar.', 'snowy-wp'),
                'titulo'    => __('Encabezado propio.', 'snowy-wp'),
                'nivel'     => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'clima-lluvia' => [
            'code'    => '[snowy_clima_lluvia region="ES-RI"]',
            'name'    => __('Lluvia del año', 'snowy-wp'),
            'desc'    => __('La lluvia acumulada desde el 1 de enero frente a la normal a la misma fecha, en los pluviómetros con serie larga de la región. Dice en cuántos se basa.', 'snowy-wp'),
            'block'   => __('Snowy · Clima: lluvia del año', 'snowy-wp'),
            'ejemplo' => '[snowy_clima_lluvia region="ES-RI"]',
            'attrs'   => [
                'region'    => __('Código de la comunidad o provincia (ES-RI…). Este widget solo funciona por región.', 'snowy-wp'),
                'anio' => __('Año. Vacío usa el año en curso.', 'snowy-wp'),
                'nombre'    => __('Nombre del lugar a mostrar.', 'snowy-wp'),
                'titulo'    => __('Encabezado propio.', 'snowy-wp'),
                'nivel'     => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
        ],
        'estacion' => [
            'code'  => '[snowy_estacion id="9115X"]',
            'name'  => __('Ficha de una estación', 'snowy-wp'),
            'desc'  => __('Tarjeta con el dato de una estación concreta, para incrustar dentro de un artículo.', 'snowy-wp'),
            'attrs' => [
                'id'     => __('Identificador de la estación. Los tienes en la tabla de abajo.', 'snowy-wp'),
                'nombre' => __('Alternativa a id: el nombre exacto de la estación.', 'snowy-wp'),
                'grafico' => __('no para ocultar la evolución de las últimas 24 horas. Por defecto se muestra.', 'snowy-wp'),
                'nivel'   => __('Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.', 'snowy-wp'),
            ],
            'block'   => __('Snowy · Ficha de estación', 'snowy-wp'),
            'ejemplo' => '[snowy_estacion id="9115X"]',
        ],
    ];
}

function snowy_wp_display_help()
{
    return [
        'cabecera="no"'      => __('Quita la cabecera con el título, para cuando el widget va bajo un encabezado tuyo.', 'snowy-wp'),
        'variante="compacta"' => __('Menos relleno y letra algo menor, para barras laterales y columnas estrechas.', 'snowy-wp'),
        'esquema="oscuro"'   => __('claro, oscuro o auto. Cambia el esquema solo de ese widget.', 'snowy-wp'),
        'clase="mi-clase"'   => __('Clases CSS propias en la caja del widget, separadas por espacios.', 'snowy-wp'),
    ];
}

function snowy_wp_tokens_help()
{
    return [
        '--snowy-accent'       => [__('Enlaces, iconos, gráficos y detalles. También desde los ajustes.', 'snowy-wp'), '#2254d3'],
        '--snowy-text'         => [__('Texto principal.', 'snowy-wp'), '#0c183b'],
        '--snowy-text-soft'    => [__('Texto de los resúmenes.', 'snowy-wp'), '#3d4662'],
        '--snowy-muted'        => [__('Metadato bajo el título, etiquetas, cabeceras de tabla y atribución.', 'snowy-wp'), '#585f74'],
        '--snowy-faint'        => [__('Texto secundario: fechas, unidades, mínimas.', 'snowy-wp'), '#6d7489'],
        '--snowy-line'         => [__('Bordes de la caja y separadores.', 'snowy-wp'), '#d3d7e4'],
        '--snowy-line-soft'    => [__('Separador entre filas y sobre la atribución.', 'snowy-wp'), '#e5e7ef'],
        '--snowy-surface'      => [__('Fondo de la caja.', 'snowy-wp'), '#fff'],
        '--snowy-surface-alt'  => [__('Fondo de la fila al pasar el ratón.', 'snowy-wp'), '#f8f9fc'],
        '--snowy-chip'         => [__('Fondo de etiquetas neutras y de las barras.', 'snowy-wp'), '#ebeef4'],
        '--snowy-official-bg'  => [__('Fondo de la etiqueta de red oficial.', 'snowy-wp'), '#e9eefb'],
        '--snowy-official-text' => [__('Texto de la etiqueta de red oficial.', 'snowy-wp'), '#2254d3'],
        '--snowy-up'           => [__('Flecha de subida.', 'snowy-wp'), '#2254d3'],
        '--snowy-down'         => [__('Flecha de bajada.', 'snowy-wp'), '#c24e0a'],
        '--snowy-wet'          => [__('Lluvia: probabilidad destacada en la previsión y acumulados.', 'snowy-wp'), '#7c3aed'],
        '--snowy-radius'       => [__('Redondeo de las esquinas. También desde los ajustes.', 'snowy-wp'), '6px'],
        '--snowy-font'         => [__('Tipografía de los widgets. También desde los ajustes.', 'snowy-wp'), 'Snowy Sans'],
        '--snowy-font-heading' => [__('Tipografía de títulos y cifras grandes, con la tipografía de Snowy.', 'snowy-wp'), 'Snowy Serif'],
        '--snowy-shadow'       => [__('Sombra de la caja. none la quita.', 'snowy-wp'), __('sombra fina', 'snowy-wp')],
        '--snowy-scale-1 … --snowy-scale-10' => [__('Escala divergente de los widgets de clima, del más frío al más cálido: calendario, franjas, puntos de la serie y campana.', 'snowy-wp'), '#053061 … #67001f'],
        '--snowy-scale-neutral' => [__('Días normales en el calendario (percentil 40-60).', 'snowy-wp'), '#ebeef4'],
        '--snowy-warm'         => [__('Barras por encima de lo normal en los widgets de clima.', 'snowy-wp'), '#d6604d'],
        '--snowy-cold'         => [__('Barras por debajo de lo normal.', 'snowy-wp'), '#4393c3'],
        '--snowy-series'       => [__('Línea de la serie anual.', 'snowy-wp'), __('texto de los resúmenes', 'snowy-wp')],
        '--snowy-trend'        => [__('Recta de tendencia.', 'snowy-wp'), __('acento', 'snowy-wp')],
    ];
}

function snowy_wp_hooks_help()
{
    return [
        'snowy_wp_widget_data'        => __('Filtro. Los datos de cualquier widget antes de pintarlo. Recibe $data, $widget y $atts.', 'snowy-wp'),
        'snowy_wp_widget_data_{widget}' => __('Filtro. Igual, solo para un widget: snowy_wp_widget_data_embalses.', 'snowy-wp'),
        'snowy_wp_widget_html'        => __('Filtro. El HTML final de cualquier widget. Recibe $html, $widget, $data y $atts.', 'snowy-wp'),
        'snowy_wp_widget_html_{widget}' => __('Filtro. Igual, solo para un widget.', 'snowy-wp'),
        'snowy_wp_widget_classes'     => __('Filtro. Las clases de la caja del widget. Recibe $classes, $widget y $atts.', 'snowy-wp'),
        'snowy_wp_locate_template'    => __('Filtro. La ruta de la plantilla elegida. Recibe $path y $widget.', 'snowy-wp'),
        'snowy_wp_before_widget'      => __('Acción. Justo antes del HTML del widget, dentro de lo que devuelve.', 'snowy-wp'),
        'snowy_wp_after_widget'       => __('Acción. Justo después.', 'snowy-wp'),
        'snowy_wp_css_tokens'         => __('Filtro. Las variables que el plugin escribe en :root desde los ajustes.', 'snowy-wp'),
        'snowy_wp_css'                => __('Filtro. La hoja completa que se imprime.', 'snowy-wp'),
        'snowy_wp_load_css'           => __('Filtro. false para no imprimir ninguna hoja, igual que la casilla de los ajustes.', 'snowy-wp'),
        'snowy_wp_climate_data'       => __('Filtro. La respuesta de clima ya recortada, de la caché o recién pedida, antes de calcular el widget. Recibe $data, $path, $place y $params.', 'snowy-wp'),
        'snowy_wp_station_name'       => __('Filtro. El nombre de un pluviómetro del widget de lluvia del año, ya pasado de mayúsculas a frase.', 'snowy-wp'),
        'snowy_wp_api_base'           => __('Filtro. La URL base de la API, para entornos de pruebas. También con la constante SNOWY_WP_API en wp-config.php. Tiene que ser https, salvo en localhost y dominios .localhost o .test; si no, se usa la de producción.', 'snowy-wp'),
    ];
}

function snowy_wp_admin_menu()
{
    add_menu_page(
        __('Widgets de Snowy', 'snowy-wp'),
        __('Widgets Snowy', 'snowy-wp'),
        'edit_posts',
        'snowy-wp-widgets',
        'snowy_wp_admin_page',
        'dashicons-cloud',
        58
    );
}
add_action('admin_menu', 'snowy_wp_admin_menu');

function snowy_wp_admin_page()
{
    $stations = snowy_wp_stations();
    $ajustes  = admin_url('options-general.php?page=snowy-wp-settings');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Widgets de Snowy', 'snowy-wp'); ?></h1>

        <?php if ($stations) : ?>
            <div class="notice notice-success inline" style="margin:1rem 0">
                <p><?php printf(
                    /* translators: 1: numero de estaciones, 2: region configurada */
                    esc_html__('Conectado: %1$s estaciones disponibles en %2$s.', 'snowy-wp'),
                    '<strong>' . count($stations) . '</strong>',
                    '<strong>' . esc_html(snowy_wp_region_label()) . '</strong>'
                ); ?></p>
            </div>
        <?php else : ?>
            <div class="notice notice-error inline" style="margin:1rem 0">
                <p>
                    <strong><?php esc_html_e('Sin conexión con la API.', 'snowy-wp'); ?></strong>
                    <a href="<?php echo esc_url($ajustes); ?>"><?php esc_html_e('Revisa los ajustes del plugin.', 'snowy-wp'); ?></a>
                </p>
            </div>
        <?php endif; ?>

        <h2><?php esc_html_e('Cómo insertar un widget', 'snowy-wp'); ?></h2>
        <p style="max-width:900px">
            <?php esc_html_e('Hay dos formas y pintan exactamente lo mismo, porque por dentro comparten el código:', 'snowy-wp'); ?>
        </p>
        <ul style="max-width:900px;list-style:disc;margin-left:1.5rem">
            <li><?php printf(
                /* translators: %s: el texto que se escribe en el editor */
                esc_html__('En el editor de bloques, escribe %s y elige el que quieras. Se previsualiza dentro del propio editor y sus opciones están en el panel lateral.', 'snowy-wp'),
                '<code>/snowy</code>'
            ); ?></li>
            <li><?php esc_html_e('En el editor clásico, o dentro de cualquier campo de texto, pega el shortcode tal cual.', 'snowy-wp'); ?></li>
        </ul>

        <h2><?php esc_html_e('Piezas disponibles', 'snowy-wp'); ?></h2>
        <table class="widefat striped" style="max-width:980px">
            <thead><tr>
                <th style="width:290px"><?php esc_html_e('Shortcode', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Qué muestra y qué admite', 'snowy-wp'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach (snowy_wp_widgets_help() as $w) : ?>
                <tr>
                    <td>
                        <code style="display:block;padding:.4rem;background:#f6f7f7;user-select:all"><?php echo esc_html($w['code']); ?></code>
                        <?php if (!empty($w['block'])) : ?>
                            <p style="margin:.4rem 0 0;color:#646970;font-size:.9em">
                                <?php esc_html_e('Bloque:', 'snowy-wp'); ?> <strong><?php echo esc_html($w['block']); ?></strong>
                            </p>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo esc_html($w['name']); ?></strong><br>
                        <span style="color:#646970"><?php echo esc_html($w['desc']); ?></span>
                        <?php if (!empty($w['attrs'])) : ?>
                            <ul style="margin:.5rem 0 0;color:#646970">
                                <?php foreach ($w['attrs'] as $attr => $help) : ?>
                                    <li><code><?php echo esc_html($attr); ?></code> — <?php echo esc_html($help); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!empty($w['ejemplo'])) : ?>
                            <p style="margin:.5rem 0 0">
                                <?php esc_html_e('Ejemplo:', 'snowy-wp'); ?>
                                <code style="user-select:all"><?php echo esc_html($w['ejemplo']); ?></code>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h2><?php esc_html_e('En vivo o congelado', 'snowy-wp'); ?></h2>
        <p style="max-width:900px">
            <?php esc_html_e('En una página de datos los widgets van en vivo y se actualizan solos. Dentro de un artículo de actualidad conviene congelarlos, con el interruptor del bloque o con modo="snapshot": así el texto y el dato siguen contando lo mismo dentro de un mes.', 'snowy-wp'); ?>
        </p>
        <ul style="max-width:900px;list-style:disc;margin-left:1.5rem;color:#646970">
            <li><?php esc_html_e('El dato se congela al publicar, no mientras editas.', 'snowy-wp'); ?></li>
            <li><?php esc_html_e('Nunca se congela una respuesta vacía: una caída pasajera dejaría el widget muerto para siempre.', 'snowy-wp'); ?></li>
            <li><?php esc_html_e('El widget congelado avisa de la fecha, para que el lector sepa a cuándo corresponde.', 'snowy-wp'); ?></li>
        </ul>

        <h2><?php esc_html_e('Qué esperar', 'snowy-wp'); ?></h2>
        <table class="widefat striped" style="max-width:980px">
            <tbody>
            <tr>
                <td style="width:290px"><strong><?php esc_html_e('Si la API no responde', 'snowy-wp'); ?></strong></td>
                <td><?php esc_html_e('Se sirve la última lectura buena, guardada durante 24 horas, indicando su antigüedad. Si tampoco la hay, el widget no pinta nada y la página se sirve igual: un fallo nuestro no puede tumbar tu web.', 'snowy-wp'); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Cada cuánto se actualiza', 'snowy-wp'); ?></strong></td>
                <td><?php esc_html_e('Las estaciones cada 10 minutos, los avisos cada 15 y el aire cada 30. Al guardar los ajustes la caché se vacía.', 'snowy-wp'); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Dónde va la clave', 'snowy-wp'); ?></strong></td>
                <td><?php esc_html_e('Las peticiones salen de tu servidor, nunca del navegador del visitante, así que la clave no queda expuesta. Se puede guardar en los ajustes o definir SNOWY_API_KEY en wp-config.php, que tiene prioridad.', 'snowy-wp'); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Atribución', 'snowy-wp'); ?></strong></td>
                <td><?php esc_html_e('Cada widget indica de dónde viene el dato y los avisos atribuyen a AEMET como fuente. Es la condición de uso y no se puede desactivar.', 'snowy-wp'); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Actualizaciones', 'snowy-wp'); ?></strong></td>
                <td><?php esc_html_e('El plugin comprueba por su cuenta si hay una versión nueva publicada y la ofrece en Plugins, como cualquier otro. No se instala nada sin que lo pidas.', 'snowy-wp'); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Si la clave deja de valer', 'snowy-wp'); ?></strong></td>
                <td><?php esc_html_e('Se revisa a diario y se avisa por correo al administrador la primera vez que falla. El diagnóstico también aparece en Herramientas > Salud del sitio.', 'snowy-wp'); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Encabezados', 'snowy-wp'); ?></strong></td>
                <td><?php printf(
                    /* translators: %s: enlace a los ajustes */
                    esc_html__('El título de cada widget usa el nivel elegido en %s, o el del atributo nivel. Conviene que encaje bajo los encabezados de tus artículos.', 'snowy-wp'),
                    '<a href="' . esc_url($ajustes) . '">' . esc_html__('los ajustes', 'snowy-wp') . '</a>'
                ); ?></td>
            </tr>
            </tbody>
        </table>

        <?php if ($stations) : ?>
            <h2><?php esc_html_e('Identificadores de estación', 'snowy-wp'); ?></h2>
            <p><?php esc_html_e('Para usar con los atributos id e ids. Haz clic sobre uno para seleccionarlo.', 'snowy-wp'); ?></p>
            <table class="widefat striped" style="max-width:720px">
                <thead><tr>
                    <th><?php esc_html_e('Estación', 'snowy-wp'); ?></th>
                    <th style="width:150px"><?php esc_html_e('id', 'snowy-wp'); ?></th>
                    <th style="width:170px"><?php esc_html_e('Red', 'snowy-wp'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($stations as $s) : ?>
                    <tr>
                        <td><?php echo esc_html($s['stationName'] ?? ''); ?></td>
                        <td><code style="user-select:all"><?php echo esc_html($s['stationId'] ?? ''); ?></code></td>
                        <td style="color:#646970"><?php echo esc_html(snowy_wp_network_label($s['network'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2 id="snowy-wp-personalizar"><?php esc_html_e('Personalizar el aspecto', 'snowy-wp'); ?></h2>
        <p style="max-width:900px">
            <?php esc_html_e('El plugin se puede vestir a tres alturas, de menos a más trabajo: los ajustes, las variables CSS y las plantillas. Lo que se pinta siempre lleva su atribución, que es la condición de uso de los datos.', 'snowy-wp'); ?>
        </p>

        <h3><?php esc_html_e('Atributos de presentación', 'snowy-wp'); ?></h3>
        <p style="max-width:900px"><?php esc_html_e('Valen para todos los shortcodes; en los bloques están en el panel Apariencia y la clase en Avanzado.', 'snowy-wp'); ?></p>
        <ul style="max-width:900px;list-style:disc;margin-left:1.5rem;color:#646970">
            <?php foreach (snowy_wp_display_help() as $attr => $help) : ?>
                <li><code><?php echo esc_html($attr); ?></code> — <?php echo esc_html($help); ?></li>
            <?php endforeach; ?>
        </ul>

        <h3><?php esc_html_e('Variables CSS', 'snowy-wp'); ?></h3>
        <p style="max-width:900px"><?php printf(
            /* translators: %s: ejemplo de regla CSS */
            esc_html__('Defínelas en tu tema sobre :root o sobre cualquier contenedor, por ejemplo %s. Mandan sobre el esquema claro u oscuro, así que si tu tema tiene modo oscuro defínelas también allí.', 'snowy-wp'),
            '<code>.entry-content{--snowy-accent:#b45309;--snowy-radius:4px}</code>'
        ); ?></p>
        <table class="widefat striped" style="max-width:980px">
            <thead><tr>
                <th style="width:290px"><?php esc_html_e('Variable', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Qué cambia', 'snowy-wp'); ?></th>
                <th style="width:220px"><?php esc_html_e('Por defecto', 'snowy-wp'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach (snowy_wp_tokens_help() as $token => $row) : ?>
                <tr>
                    <td><code style="user-select:all"><?php echo esc_html($token); ?></code></td>
                    <td><?php echo esc_html($row[0]); ?></td>
                    <td><code><?php echo esc_html($row[1]); ?></code></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p style="max-width:900px;color:#646970"><?php esc_html_e('Para un bloque oscuro dentro de una página clara, añade la clase snowy-wp--dark a cualquier contenedor; snowy-wp--light hace lo contrario y snowy-wp--auto sigue al dispositivo.', 'snowy-wp'); ?></p>

        <h3><?php esc_html_e('Plantillas del tema', 'snowy-wp'); ?></h3>
        <p style="max-width:900px"><?php printf(
            /* translators: 1: ruta en el tema, 2: carpeta del plugin */
            esc_html__('Para cambiar el HTML de un widget, copia su plantilla de %2$s a %1$s y edítala. Se busca primero en el tema hijo y después en el padre. Las plantillas reciben los datos ya preparados y las variables $classes, $tag, $show_head y $atts.', 'snowy-wp'),
            '<code>wp-content/themes/&lt;tu-tema&gt;/snowy-wp/&lt;widget&gt;.php</code>',
            '<code>wp-content/plugins/snowy-wp/templates/</code>'
        ); ?></p>
        <p style="max-width:900px;color:#646970"><?php esc_html_e('Plantillas disponibles:', 'snowy-wp'); ?>
            <?php echo esc_html(implode(', ', array_map(static fn($f) => basename($f, '.php'), glob(SNOWY_WP_DIR . 'templates/*.php') ?: []))); ?>.
        </p>

        <h3><?php esc_html_e('Filtros y acciones', 'snowy-wp'); ?></h3>
        <table class="widefat striped" style="max-width:980px">
            <tbody>
            <?php foreach (snowy_wp_hooks_help() as $hook => $help) : ?>
                <tr>
                    <td style="width:290px"><code style="user-select:all"><?php echo esc_html($hook); ?></code></td>
                    <td><?php echo esc_html($help); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <pre style="max-width:900px;background:#f6f7f7;padding:.75rem;overflow:auto"><?php echo esc_html("add_filter('snowy_wp_widget_data_embalses', function (\$data) {
    \$data['title'] = 'Nuestros pantanos';
    return \$data;
});

add_filter('snowy_wp_api_base', fn() => 'https://staging-api.ejemplo.es');"); ?></pre>

        <h2><?php esc_html_e('Composiciones listas', 'snowy-wp'); ?></h2>
        <p style="max-width:900px">
            <?php esc_html_e('En el editor de bloques, el botón de insertar tiene una pestaña de Patrones con una categoría Snowy. Ahí hay tres montajes que ya combinan varias piezas:', 'snowy-wp'); ?>
        </p>
        <ul style="max-width:900px;list-style:disc;margin-left:1.5rem;color:#646970">
            <?php foreach (SNOWY_WP_PATTERNS as $pat) : ?>
                <li><strong><?php echo esc_html($pat['title']); ?></strong> — <?php echo esc_html($pat['description']); ?></li>
            <?php endforeach; ?>
        </ul>

        <h2><?php esc_html_e('Ayuda', 'snowy-wp'); ?></h2>
        <p>
            <?php printf(
                /* translators: %s: direccion de correo de contacto */
                esc_html__('¿Algo no encaja, o necesitas un dato que aquí no está? Escribe a %s.', 'snowy-wp'),
                '<a href="mailto:' . esc_attr(SNOWY_WP_CONTACT) . '"><strong>' . esc_html(SNOWY_WP_CONTACT) . '</strong></a>'
            ); ?>
        </p>
    </div>
    <?php
}
