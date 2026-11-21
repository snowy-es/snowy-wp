<?php

if (!defined('ABSPATH')) {
    exit;
}

const SNOWY_WP_REGION_PATTERN = '/^[A-Z]{2}-[A-Z0-9]{1,3}$/';
const SNOWY_WP_TTL_REGIONS    = WEEK_IN_SECONDS;

/**
 * Código ISO de la región configurada. El ajuste guarda el nombre que dan las
 * estaciones («Navarra», «Cataluña»): el engine lo pasa a código con
 * /regions?name=, que entiende los nombres de cada red.
 */
function snowy_wp_region_code()
{
    $region = trim((string) snowy_wp_option('region'));
    if ($region === '' || preg_match(SNOWY_WP_REGION_PATTERN, $region)) {
        return $region;
    }

    $found = snowy_wp_cached('region_code_' . md5($region), SNOWY_WP_TTL_REGIONS, static function () use ($region) {
        $listing = snowy_wp_get('/regions?' . http_build_query(['level' => 'ccaa', 'name' => $region]));
        if (!isset($listing['regions'])) {
            return null;
        }

        return ['code' => count($listing['regions']) === 1 ? (string) $listing['regions'][0]['code'] : ''];
    });

    return (string) ($found['code'] ?? '');
}

/**
 * Ruta de la API con la región configurada. Devuelve null si hay región pero
 * el engine no la reconoce: pedir la red entera pintaría toda España con el
 * título de la región.
 */
function snowy_wp_regional_path($path)
{
    if (trim((string) snowy_wp_option('region')) === '') {
        return $path;
    }

    $code = snowy_wp_region_code();

    return $code === '' ? null : $path . '?' . http_build_query(['region' => $code]);
}

const SNOWY_WP_OUTLINE_OPTION = 'snowy_wp_outline_';

/**
 * Contorno simplificado de una región (MultiPolygon de GeoJSON, en lon/lat).
 * La última copia buena se guarda como opción, sin caducidad: un tema que
 * recorta o dibuja con el contorno no puede quedarse sin él porque la API
 * falle más de un día.
 */
function snowy_wp_region_outline($code)
{
    if (!preg_match(SNOWY_WP_REGION_PATTERN, (string) $code)) {
        return null;
    }

    $stored = SNOWY_WP_OUTLINE_OPTION . $code;
    $geometry = snowy_wp_cached('region_outline_' . $code, SNOWY_WP_TTL_REGIONS, static function () use ($code, $stored) {
        $detail = snowy_wp_get('/regions/' . rawurlencode($code) . '?geometry=simplified');
        $fresh = $detail['region']['geometry'] ?? null;
        if (isset($fresh['coordinates'])) {
            update_option($stored, $fresh, false);
        }

        return $fresh;
    });

    if (isset($geometry['coordinates'])) {
        return $geometry;
    }

    $last = get_option($stored);

    return isset($last['coordinates']) ? $last : null;
}
