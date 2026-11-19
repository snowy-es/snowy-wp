<?php

if (!defined('ABSPATH')) {
    exit;
}

const SNOWY_WP_TTL_FORECAST = 1800;
const SNOWY_WP_TTL_LOCATION = DAY_IN_SECONDS;

/**
 * Codigos WMO con el texto que usa snowy.es, para que el mismo dia no se llame
 * de dos maneras distintas segun donde se lea.
 */
const SNOWY_WP_WEATHER_CODES = [
    0  => ['label' => 'Despejado',                   'icon' => 'sol'],
    1  => ['label' => 'Mayormente despejado',        'icon' => 'sol-nube'],
    2  => ['label' => 'Parcialmente nublado',        'icon' => 'sol-nube'],
    3  => ['label' => 'Nublado',                     'icon' => 'nube'],
    45 => ['label' => 'Niebla',                      'icon' => 'niebla'],
    48 => ['label' => 'Niebla con escarcha',         'icon' => 'niebla'],
    51 => ['label' => 'Llovizna ligera',             'icon' => 'lluvia'],
    53 => ['label' => 'Llovizna moderada',           'icon' => 'lluvia'],
    55 => ['label' => 'Llovizna densa',              'icon' => 'lluvia'],
    56 => ['label' => 'Llovizna helada ligera',      'icon' => 'lluvia'],
    57 => ['label' => 'Llovizna helada densa',       'icon' => 'lluvia'],
    61 => ['label' => 'Lluvia ligera',               'icon' => 'lluvia'],
    63 => ['label' => 'Lluvia moderada',             'icon' => 'lluvia'],
    65 => ['label' => 'Lluvia intensa',              'icon' => 'lluvia'],
    66 => ['label' => 'Lluvia helada ligera',        'icon' => 'lluvia'],
    67 => ['label' => 'Lluvia helada intensa',       'icon' => 'lluvia'],
    71 => ['label' => 'Nevada ligera',               'icon' => 'nieve'],
    73 => ['label' => 'Nevada moderada',             'icon' => 'nieve'],
    75 => ['label' => 'Nevada intensa',              'icon' => 'nieve'],
    77 => ['label' => 'Granos de nieve',             'icon' => 'nieve'],
    80 => ['label' => 'Chubascos ligeros',           'icon' => 'lluvia'],
    81 => ['label' => 'Chubascos moderados',         'icon' => 'lluvia'],
    82 => ['label' => 'Chubascos intensos',          'icon' => 'lluvia'],
    85 => ['label' => 'Chubascos de nieve ligeros',  'icon' => 'nieve'],
    86 => ['label' => 'Chubascos de nieve intensos', 'icon' => 'nieve'],
    95 => ['label' => 'Tormenta',                    'icon' => 'tormenta'],
    96 => ['label' => 'Tormenta con granizo ligero', 'icon' => 'tormenta'],
    99 => ['label' => 'Tormenta con granizo intenso', 'icon' => 'tormenta'],
];

/**
 * Iconos dibujados aqui y no cargados de fuera: un widget que se incrusta en
 * una web ajena no puede depender de una fuente de iconos ni de un CDN.
 */
const SNOWY_WP_WEATHER_ICONS = [
    'sol'      => '<circle cx="12" cy="12" r="5"/><path d="M12 1v3M12 20v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M1 12h3M20 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
    'sol-nube' => '<circle cx="8" cy="8" r="3.2"/><path d="M8 1.5v2M2.4 3.4l1.4 1.4M1.5 9h2M12.6 3.4l-1.4 1.4"/><path d="M8.5 19h9a3.5 3.5 0 0 0 .3-7 5 5 0 0 0-9.6 1.2A3 3 0 0 0 8.5 19Z"/>',
    'nube'     => '<path d="M7 19h10a4 4 0 0 0 .4-8 6 6 0 0 0-11.6 1.6A3.2 3.2 0 0 0 7 19Z"/>',
    'niebla'   => '<path d="M7 14h10a4 4 0 0 0 .4-8 6 6 0 0 0-11.6 1.6A3.2 3.2 0 0 0 7 14Z"/><path d="M4 18h16M6 21.5h12"/>',
    'lluvia'   => '<path d="M7 15h10a4 4 0 0 0 .4-8 6 6 0 0 0-11.6 1.6A3.2 3.2 0 0 0 7 15Z"/><path d="M8.5 18.5 7.5 21M12.5 18.5l-1 2.5M16.5 18.5l-1 2.5"/>',
    'nieve'    => '<path d="M7 15h10a4 4 0 0 0 .4-8 6 6 0 0 0-11.6 1.6A3.2 3.2 0 0 0 7 15Z"/><path d="M8 19h.01M12 20.5h.01M16 19h.01M10 21.5h.01M14 21.5h.01"/>',
    'tormenta' => '<path d="M7 14h10a4 4 0 0 0 .4-8 6 6 0 0 0-11.6 1.6A3.2 3.2 0 0 0 7 14Z"/><path d="m13 16-4 3.5h3L11 23l4-3.8h-3L13 16Z"/>',
];

function snowy_wp_weather_code($code)
{
    return SNOWY_WP_WEATHER_CODES[(int) $code] ?? ['label' => __('Sin datos', 'snowy-wp'), 'icon' => 'nube'];
}

function snowy_wp_weather_icon($icon)
{
    $paths = SNOWY_WP_WEATHER_ICONS[$icon] ?? SNOWY_WP_WEATHER_ICONS['nube'];

    return sprintf(
        '<svg class="snowy-wp-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">%s</svg>',
        $paths
    );
}

/**
 * Localidad de snowy.es por su identificador de URL. Cambia de año en año,
 * asi que se cachea un dia y no para siempre.
 */
function snowy_wp_location($slug)
{
    $slug = trim((string) $slug);
    if ($slug === '') {
        return null;
    }

    return snowy_wp_cached('loc_' . md5($slug), SNOWY_WP_TTL_LOCATION, static function () use ($slug) {
        return snowy_wp_get('/locations/' . rawurlencode($slug));
    });
}

/**
 * Los tres modelos con los que snowy.es forma su consenso. El primero que
 * responde manda en los valores y los otros dos miden el acuerdo: promediar
 * modelos distintos produce un tiempo que ninguno predice.
 */
const SNOWY_WP_CONSENSUS_MODELS = [
    'ecmwf_ifs'        => 'ECMWF',
    'gfs_global'       => 'GFS',
    'dwd_icon_global'  => 'ICON',
];

const SNOWY_WP_FORECAST_DAILY = 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,wind_gusts_10m_max';

/**
 * Un modelo por encima del umbral de traza ya cuenta como que predice lluvia,
 * igual que en snowy.es.
 */
const SNOWY_WP_RAIN_THRESHOLD = 0.1;

function snowy_wp_forecast_model($lat, $lon, $days, $model)
{
    $query = [
        'lat'           => $lat,
        'lon'           => $lon,
        'forecast_days' => $days,
        'model'         => $model,
        'daily'         => SNOWY_WP_FORECAST_DAILY,
    ];

    return snowy_wp_get('/weather/forecast?' . http_build_query($query));
}

/**
 * Previsión por días con el acuerdo entre modelos de cada uno.
 *
 * Se cachea el consenso ya calculado y no cada modelo por separado: lo que se
 * pinta es el conjunto, y guardar tres respuestas completas para volver a
 * recorrerlas en cada visita no aporta nada.
 */
function snowy_wp_forecast($lat, $lon, $days)
{
    return snowy_wp_cached('fc_' . md5("$lat|$lon|$days"), SNOWY_WP_TTL_FORECAST, static function () use ($lat, $lon, $days) {
        $respuestas = [];
        foreach (SNOWY_WP_CONSENSUS_MODELS as $model => $label) {
            $data = snowy_wp_forecast_model($lat, $lon, $days, $model);
            if (isset($data['daily']['time']) && $data['daily']['time']) {
                $respuestas[$label] = $data['daily'];
            }
        }

        return $respuestas ? snowy_wp_forecast_consensus($respuestas) : null;
    });
}

function snowy_wp_forecast_consensus($respuestas)
{
    $labels = array_keys($respuestas);
    $base   = $respuestas[$labels[0]];
    $total  = count($respuestas);

    $dias = [];
    foreach ($base['time'] as $i => $fecha) {
        $maximas = [];
        $conLluvia = 0;
        foreach ($respuestas as $daily) {
            if (isset($daily['temperature_2m_max'][$i]) && $daily['temperature_2m_max'][$i] !== null) {
                $maximas[] = (float) $daily['temperature_2m_max'][$i];
            }
            if ((float) ($daily['precipitation_sum'][$i] ?? 0) > SNOWY_WP_RAIN_THRESHOLD) {
                $conLluvia++;
            }
        }

        $acuerdo = snowy_wp_temperature_agreement($maximas);
        $lluvia  = $total ? (int) round(($conLluvia / $total) * 100) : 0;

        $dias[] = [
            'fecha'      => $fecha,
            'tmax'       => $base['temperature_2m_max'][$i] ?? null,
            'tmin'       => $base['temperature_2m_min'][$i] ?? null,
            'codigo'     => $base['weather_code'][$i] ?? 3,
            'mm'         => $base['precipitation_sum'][$i] ?? null,
            'racha'      => $base['wind_gusts_10m_max'][$i] ?? null,
            'lluvia'     => $lluvia,
            'conLluvia'  => $conLluvia,
            'dispersion' => snowy_wp_spread($maximas),
            'confianza'  => snowy_wp_confidence($acuerdo, $lluvia, $i),
        ];
    }

    return ['modelos' => $labels, 'total' => $total, 'dias' => $dias];
}

function snowy_wp_spread($valores)
{
    return count($valores) < 2 ? 0.0 : max($valores) - min($valores);
}

/**
 * Cuanto se parecen los modelos entre si, en porcentaje. Dos grados de
 * diferencia se llevan veinte puntos.
 */
function snowy_wp_temperature_agreement($valores)
{
    if (count($valores) < 2) {
        return 100;
    }

    return (int) max(0, round(100 - snowy_wp_spread($valores) * 10));
}

/**
 * Confianza del dia, con la misma formula que snowy.es: el acuerdo termico pesa
 * mas que el de lluvia, la lluvia solo suma cuando los modelos van todos a una
 * —da igual si es a llover o a no llover— y cada dia de distancia resta.
 */
function snowy_wp_confidence($acuerdo, $lluvia, $dia)
{
    $penalizacion = min($dia * 5, 25);
    $termica      = $acuerdo - $penalizacion;
    $precipita    = ($lluvia >= 80 || $lluvia <= 20) ? 100 : 60;
    $total        = $termica * 0.6 + $precipita * 0.4;

    if ($total >= 80) {
        return ['label' => __('Alta', 'snowy-wp'), 'class' => 'is-good'];
    }
    if ($total >= 50) {
        return ['label' => __('Media', 'snowy-wp'), 'class' => 'is-moderate'];
    }

    return ['label' => __('Baja', 'snowy-wp'), 'class' => 'is-poor'];
}

/**
 * Punto sobre el que se pide la prevision, por orden: la localidad de snowy.es,
 * unas coordenadas sueltas, o el centro de la red configurada.
 */
function snowy_wp_forecast_point($atts)
{
    $loc = snowy_wp_location($atts['loc']);
    if ($loc && isset($loc['latitude'], $loc['longitude'])) {
        return [
            'lat'  => (float) $loc['latitude'],
            'lon'  => (float) $loc['longitude'],
            'name' => $loc['name'] ?? '',
            'slug' => $loc['slug'] ?? '',
        ];
    }

    if ($atts['lat'] !== '' && $atts['lon'] !== '') {
        return [
            'lat'  => (float) $atts['lat'],
            'lon'  => (float) $atts['lon'],
            'name' => $atts['nombre'],
            'slug' => '',
        ];
    }

    $point = snowy_wp_reference_point();
    if (!$point) {
        return null;
    }

    return [
        'lat'  => $point['lat'],
        'lon'  => $point['lon'],
        'name' => $atts['nombre'] !== '' ? $atts['nombre'] : snowy_wp_region_label(),
        'slug' => '',
    ];
}

/**
 * [snowy_prevision] — prevision por dias de una localidad.
 *
 * Es el unico widget del plugin que no es dato medido sino modelo, asi que lo
 * dice en el pie: mezclar las dos cosas sin avisar es lo que hace que luego
 * nadie sepa si el numero que lee ya ha pasado o todavia no.
 */
function snowy_wp_shortcode_prevision($atts = [])
{
    $atts = snowy_wp_atts([
        'loc'    => '',
        'lat'    => '',
        'lon'    => '',
        'nombre' => '',
        'dias'   => 5,
        'titulo' => '',
        'nivel'  => '',
    ], $atts, 'snowy_prevision');

    snowy_wp_use_styles();

    $point = snowy_wp_forecast_point($atts);
    if (!$point) {
        return '';
    }

    $dias = max(1, min(10, (int) $atts['dias']));
    $data = snowy_wp_forecast($point['lat'], $point['lon'], $dias);
    if (!$data || empty($data['dias'])) {
        return '';
    }

    $nombre = $atts['nombre'] !== '' ? $atts['nombre'] : $point['name'];
    $titulo = $atts['titulo'] !== ''
        ? $atts['titulo']
        : ($nombre !== ''
            ? sprintf(
                /* translators: %s: nombre de la localidad */
                __('Previsión para %s', 'snowy-wp'),
                $nombre
            )
            : __('Previsión', 'snowy-wp'));

    $enlace = $point['slug'] !== ''
        ? SNOWY_WP_SITE . '/tiempo/' . rawurlencode($point['slug'])
        : SNOWY_WP_SITE . '/tiempo';

    return snowy_wp_render_widget('prevision', [
        'title'    => $titulo,
        'name'     => $nombre,
        'link'     => $enlace,
        'point'    => $point,
        'forecast' => $data,
    ], $atts);
}
add_shortcode('snowy_prevision', 'snowy_wp_shortcode_prevision');

/**
 * Lista los modelos en castellano: "ECMWF, GFS e ICON".
 */
function snowy_wp_join_models($modelos)
{
    if (count($modelos) < 2) {
        return (string) reset($modelos);
    }

    $ultimo = array_pop($modelos);
    $union  = stripos($ultimo, 'i') === 0 ? __('e', 'snowy-wp') : __('y', 'snowy-wp');

    return implode(', ', $modelos) . ' ' . $union . ' ' . $ultimo;
}
