<?php

if (!defined('ABSPATH')) {
    exit;
}

const SNOWY_WP_HEALTH_CRON = 'snowy_wp_check_key';
const SNOWY_WP_HEALTH_STATE = 'snowy_wp_key_state';

/**
 * Estado de la conexion, en una sola llamada reutilizable.
 *
 * Devuelve el codigo HTTP y la cuota que anuncia la API, si la anuncia: las
 * cabeceras solo llegan cuando la clave tiene limite por hora.
 */
function snowy_wp_connection_status()
{
    $key = snowy_wp_api_key();
    if (!$key) {
        return ['estado' => 'sin-clave', 'code' => 0, 'limite' => null, 'restantes' => null];
    }

    $res = wp_remote_get(SNOWY_WP_API . '/stations/markers', [
        'timeout' => 10,
        'headers' => snowy_wp_auth_headers(),
    ]);

    if (is_wp_error($res)) {
        return ['estado' => 'sin-red', 'code' => 0, 'limite' => null, 'restantes' => null, 'mensaje' => $res->get_error_message()];
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    $estado = 'error';
    if ($code === 200) {
        $estado = 'ok';
    } elseif ($code === 401 || $code === 403) {
        $estado = 'rechazada';
    } elseif ($code === 429) {
        $estado = 'sin-cuota';
    }

    $limite    = wp_remote_retrieve_header($res, 'x-ratelimit-limit');
    $restantes = wp_remote_retrieve_header($res, 'x-ratelimit-remaining');

    return [
        'estado'    => $estado,
        'code'      => $code,
        'limite'    => $limite === '' ? null : (int) $limite,
        'restantes' => $restantes === '' ? null : (int) $restantes,
    ];
}

function snowy_wp_status_label($estado)
{
    $textos = [
        'ok'         => __('La conexión con Snowy funciona.', 'snowy-wp'),
        'sin-clave'  => __('No hay clave de API configurada, así que los widgets no muestran nada.', 'snowy-wp'),
        'rechazada'  => __('La API rechaza la clave. Puede estar revocada o no tener permiso para estos datos.', 'snowy-wp'),
        'sin-cuota'  => __('La clave ha agotado su cuota de peticiones por hora.', 'snowy-wp'),
        'sin-red'    => __('No se ha podido contactar con la API desde este servidor.', 'snowy-wp'),
        'error'      => __('La API ha respondido con un error.', 'snowy-wp'),
    ];

    return $textos[$estado] ?? $textos['error'];
}

/**
 * Prueba en Herramientas > Salud del sitio.
 *
 * Es donde el responsable de la web y quien le da soporte ya miran cuando algo
 * va mal, asi que el diagnostico aparece sin tener que saber que existe este
 * plugin.
 */
function snowy_wp_site_health_test()
{
    $s = snowy_wp_connection_status();
    $ok = $s['estado'] === 'ok';

    $resultado = [
        'label'       => $ok ? __('Snowy sirve los datos correctamente', 'snowy-wp') : __('Los widgets de Snowy no están mostrando datos', 'snowy-wp'),
        'status'      => $ok ? 'good' : 'critical',
        'badge'       => ['label' => __('Snowy', 'snowy-wp'), 'color' => 'blue'],
        'description' => '<p>' . esc_html(snowy_wp_status_label($s['estado'])) . '</p>',
        'test'        => 'snowy_wp_api',
    ];

    if ($ok && $s['limite'] !== null) {
        $resultado['description'] .= '<p>' . sprintf(
            /* translators: 1: peticiones restantes, 2: limite por hora */
            esc_html__('Cuota: quedan %1$s peticiones de %2$s esta hora.', 'snowy-wp'),
            '<strong>' . esc_html($s['restantes']) . '</strong>',
            esc_html($s['limite'])
        ) . '</p>';
    }

    if (!$ok) {
        $resultado['actions'] = sprintf(
            '<p><a href="%s">%s</a></p>',
            esc_url(admin_url('options-general.php?page=snowy-wp-settings')),
            esc_html__('Revisar los ajustes de Snowy', 'snowy-wp')
        );
    }

    return $resultado;
}

function snowy_wp_register_site_health($tests)
{
    $tests['direct']['snowy_wp_api'] = [
        'label' => __('Conexión con Snowy', 'snowy-wp'),
        'test'  => 'snowy_wp_site_health_test',
    ];

    return $tests;
}
add_filter('site_status_tests', 'snowy_wp_register_site_health');

/**
 * Revision diaria de la clave.
 *
 * Sin esto, una clave revocada se descubre cuando alguien mira la web y ve los
 * huecos. El aviso se manda solo al pasar de funcionar a no funcionar, para no
 * convertirlo en un correo diario que se acaba filtrando.
 */
function snowy_wp_schedule_check()
{
    if (!wp_next_scheduled(SNOWY_WP_HEALTH_CRON)) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', SNOWY_WP_HEALTH_CRON);
    }
}
add_action('init', 'snowy_wp_schedule_check');

function snowy_wp_run_check()
{
    $s = snowy_wp_connection_status();
    $ahora = $s['estado'];
    $antes = get_option(SNOWY_WP_HEALTH_STATE, 'ok');

    update_option(SNOWY_WP_HEALTH_STATE, $ahora);

    if ($ahora === $antes || $ahora === 'ok') {
        return;
    }

    $sitio = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    wp_mail(
        get_option('admin_email'),
        sprintf(
            /* translators: %s: nombre del sitio */
            __('[%s] Los widgets de Snowy han dejado de mostrar datos', 'snowy-wp'),
            $sitio
        ),
        implode("\n\n", [
            snowy_wp_status_label($ahora),
            sprintf(
                /* translators: %s: url de los ajustes */
                __('Ajustes del plugin: %s', 'snowy-wp'),
                admin_url('options-general.php?page=snowy-wp-settings')
            ),
            sprintf(
                /* translators: %s: direccion de correo de contacto */
                __('Si necesitas una clave nueva o ampliar la cuota, escribe a %s.', 'snowy-wp'),
                SNOWY_WP_CONTACT
            ),
        ])
    );
}
add_action(SNOWY_WP_HEALTH_CRON, 'snowy_wp_run_check');
