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
 * Previsión por días con el consenso que calcula el engine: ECMWF, GFS e ICON,
 * los mismos modelos y la misma fórmula de confianza que snowy.es.
 */
function snowy_wp_forecast($lat, $lon, $days)
{
    return snowy_wp_cached('fc2_' . md5("$lat|$lon|$days"), SNOWY_WP_TTL_FORECAST, static function () use ($lat, $lon, $days) {
        $consensus = snowy_wp_get('/weather/forecast/consensus?' . http_build_query([
            'lat'  => $lat,
            'lon'  => $lon,
            'days' => $days,
        ]));

        return isset($consensus['days']) && $consensus['days'] ? snowy_wp_forecast_view($consensus) : null;
    });
}

function snowy_wp_forecast_view($consensus)
{
    $dias = [];
    foreach ($consensus['days'] as $day) {
        $dias[] = [
            'fecha'      => $day['date'],
            'tmax'       => $day['temperatureMax'] ?? null,
            'tmin'       => $day['temperatureMin'] ?? null,
            'codigo'     => $day['weatherCode'] ?? 3,
            'mm'         => $day['precipitation'] ?? null,
            'racha'      => $day['windGust'] ?? null,
            'lluvia'     => (int) ($day['rainAgreement'] ?? 0),
            'conLluvia'  => (int) ($day['modelsWithRain'] ?? 0),
            'dispersion' => (float) ($day['temperatureSpread'] ?? 0),
            'confianza'  => snowy_wp_confidence_view($day['confidence'] ?? ''),
        ];
    }

    return [
        'modelos' => $consensus['models'] ?? [],
        'total'   => (int) ($consensus['totalModels'] ?? 0),
        'dias'    => $dias,
    ];
}

function snowy_wp_confidence_view($level)
{
    if ($level === 'high') {
        return ['label' => __('Alta', 'snowy-wp'), 'class' => 'is-good'];
    }
    if ($level === 'medium') {
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

const SNOWY_WP_TTL_SNOW_LINE = HOUR_IN_SECONDS;

/**
 * La cota de nieve de hoy y los cuatro días siguientes en una rejilla de 0,1° sobre la
 * región (/snow/line): la más baja con precipitación de cada punto, la más baja del día y
 * los litros. La calcula Snowy con la isocero de sus modelos.
 */
function snowy_wp_snow_line($region)
{
    $region = strtoupper(trim((string) $region));
    if (!preg_match(SNOWY_WP_REGION_PATTERN, $region)) {
        return [];
    }

    return snowy_wp_cached('snow_line_' . $region, SNOWY_WP_TTL_SNOW_LINE, static function () use ($region) {
        $raw = snowy_wp_get('/snow/line?' . http_build_query(['region' => $region]), 25);
        if (!is_array($raw) || empty($raw['days']) || !is_array($raw['days'])) {
            return null;
        }

        $days = [];
        foreach ($raw['days'] as $day) {
            if (!is_array($day) || empty($day['date']) || empty($day['cells'])) {
                continue;
            }
            $cells = [];
            foreach ((array) $day['cells'] as $cell) {
                if (!isset($cell['lat'], $cell['lon'])) {
                    continue;
                }
                $cells[] = [
                    'lat'       => (float) $cell['lat'],
                    'lon'       => (float) $cell['lon'],
                    'snowLine'  => isset($cell['snowLineM']) ? (int) $cell['snowLineM'] : null,
                    'lowest'    => isset($cell['lowestM']) ? (int) $cell['lowestM'] : null,
                    'precip'    => (float) ($cell['precipitationMm'] ?? 0),
                ];
            }
            $days[] = [
                'date'      => (string) $day['date'],
                'lowestWet' => isset($day['lowestWetM']) ? (int) $day['lowestWetM'] : null,
                'wetCells'  => (int) ($day['wetCells'] ?? 0),
                'cells'     => $cells,
            ];
        }

        return $days ? [
            'region'         => (string) ($raw['region'] ?? $region),
            'generatedAt'    => (string) ($raw['generatedAt'] ?? ''),
            'gridDegrees'    => (float) ($raw['gridDegrees'] ?? 0.1),
            'belowFreezingM' => (int) ($raw['belowFreezingM'] ?? 300),
            'days'           => $days,
        ] : null;
    });
}
