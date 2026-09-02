<?php

if (!defined('ABSPATH')) {
    exit;
}

function snowy_wp_settings_menu()
{
    add_options_page(
        __('Snowy', 'snowy-wp'),
        __('Snowy', 'snowy-wp'),
        'manage_options',
        'snowy-wp-settings',
        'snowy_wp_settings_page'
    );
}
add_action('admin_menu', 'snowy_wp_settings_menu');

function snowy_wp_register_settings()
{
    register_setting('snowy_wp', SNOWY_WP_OPTION, [
        'type'              => 'array',
        'sanitize_callback' => 'snowy_wp_sanitize_settings',
        'default'           => SNOWY_WP_DEFAULTS,
    ]);
}
add_action('admin_init', 'snowy_wp_register_settings');

/**
 * Al guardar se vacia la cache: cambiar la region o la clave y seguir viendo
 * los datos anteriores durante diez minutos parece que los ajustes no funcionan.
 */
function snowy_wp_sanitize_settings($input)
{
    $clean = [
        'api_key'      => sanitize_text_field($input['api_key'] ?? ''),
        'region'       => sanitize_text_field($input['region'] ?? ''),
        'stations_url' => esc_url_raw($input['stations_url'] ?? ''),
        'heading_level' => SNOWY_WP_HEADING_LEVELS[$input['heading_level'] ?? ''] ?? 'h3',
        'accent'        => empty($input['accent_reset']) && preg_match('/^#[0-9a-fA-F]{6}$/', $input['accent'] ?? '') ? $input['accent'] : '',
        'attribution'  => !empty($input['attribution']),
        'css_siempre'  => !empty($input['css_siempre']),
        // El shortcode se guarda tal cual porque es lo que se va a ejecutar; lo
        // que decide que se puede ejecutar es do_shortcode, que solo atiende a
        // los que hay registrados.
        'auto_shortcode'  => wp_kses_post($input['auto_shortcode'] ?? ''),
        'auto_donde'      => SNOWY_WP_PLACEMENT_TARGETS[$input['auto_donde'] ?? ''] ?? 'post',
        'auto_posicion'   => SNOWY_WP_PLACEMENT_POSITIONS[$input['auto_posicion'] ?? ''] ?? 'despues',
        'auto_categorias' => sanitize_text_field($input['auto_categorias'] ?? ''),
    ];

    snowy_wp_flush_cache();

    return $clean;
}

/**
 * Valores distintos de `state` que devuelve la red, para no obligar a acertar
 * el nombre exacto de la region escribiendolo a mano.
 */
function snowy_wp_available_regions()
{
    $all = snowy_wp_get('/stations/markers');
    if (!is_array($all)) {
        return [];
    }

    $regions = array_unique(array_filter(array_map(static function ($s) {
        return $s['state'] ?? null;
    }, $all)));
    sort($regions);

    return $regions;
}

function snowy_wp_settings_page()
{
    $options  = snowy_wp_options();
    $key      = snowy_wp_api_key();
    $fromConst = snowy_wp_api_key_is_constant();
    $stations = $key ? snowy_wp_stations() : [];
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Snowy — datos meteorológicos en vivo', 'snowy-wp'); ?></h1>

        <?php if (!$key) : ?>
            <div class="notice notice-warning">
                <p><?php printf(
                    /* translators: %s: direccion de correo de contacto */
                    esc_html__('El plugin necesita una clave de API de Snowy para mostrar datos. Solicítala escribiendo a %s.', 'snowy-wp'),
                    '<a href="mailto:' . esc_attr(SNOWY_WP_CONTACT) . '"><strong>' . esc_html(SNOWY_WP_CONTACT) . '</strong></a>'
                ); ?></p>
            </div>
        <?php elseif (!$stations) : ?>
            <div class="notice notice-error">
                <p><?php esc_html_e('Hay clave configurada pero la API no devuelve estaciones. Puede estar revocada, sin permisos para este dominio de datos, o haber agotado su cuota.', 'snowy-wp'); ?></p>
            </div>
        <?php else : ?>
            <div class="notice notice-success">
                <p><?php printf(
                    /* translators: 1: numero de estaciones, 2: region configurada */
                    esc_html__('Conectado. %1$s estaciones disponibles en %2$s.', 'snowy-wp'),
                    '<strong>' . count($stations) . '</strong>',
                    '<strong>' . esc_html(snowy_wp_region_label()) . '</strong>'
                ); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields('snowy_wp'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="snowy_wp_api_key"><?php esc_html_e('Clave de API', 'snowy-wp'); ?></label></th>
                    <td>
                        <?php if ($fromConst) : ?>
                            <p><code>SNOWY_API_KEY</code> <?php esc_html_e('está definida en wp-config.php y tiene prioridad sobre este campo.', 'snowy-wp'); ?></p>
                        <?php else : ?>
                            <input type="password" class="regular-text" id="snowy_wp_api_key"
                                   name="<?php echo esc_attr(SNOWY_WP_OPTION); ?>[api_key]"
                                   value="<?php echo esc_attr($options['api_key']); ?>" autocomplete="off">
                            <p class="description"><?php printf(
                                /* translators: %s: direccion de correo de contacto */
                                esc_html__('¿No tienes clave? Escribe a %s contando qué vas a publicar y con qué datos.', 'snowy-wp'),
                                '<a href="mailto:' . esc_attr(SNOWY_WP_CONTACT) . '">' . esc_html(SNOWY_WP_CONTACT) . '</a>'
                            ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="snowy_wp_region"><?php esc_html_e('Región', 'snowy-wp'); ?></label></th>
                    <td>
                        <?php $regions = $key ? snowy_wp_available_regions() : []; ?>
                        <?php if ($regions) : ?>
                            <select id="snowy_wp_region" name="<?php echo esc_attr(SNOWY_WP_OPTION); ?>[region]">
                                <option value=""><?php esc_html_e('Toda la red', 'snowy-wp'); ?></option>
                                <?php foreach ($regions as $r) : ?>
                                    <option value="<?php echo esc_attr($r); ?>" <?php selected($options['region'], $r); ?>><?php echo esc_html($r); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else : ?>
                            <input type="text" class="regular-text" id="snowy_wp_region"
                                   name="<?php echo esc_attr(SNOWY_WP_OPTION); ?>[region]"
                                   value="<?php echo esc_attr($options['region']); ?>">
                        <?php endif; ?>
                        <p class="description"><?php esc_html_e('Filtra las estaciones y los avisos. Vacío muestra la red completa.', 'snowy-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="snowy_wp_heading_level"><?php esc_html_e('Encabezado de los widgets', 'snowy-wp'); ?></label></th>
                    <td>
                        <select id="snowy_wp_heading_level" name="<?php echo esc_attr(SNOWY_WP_OPTION); ?>[heading_level]">
                            <?php foreach (SNOWY_WP_HEADING_LEVELS as $tag) : ?>
                                <option value="<?php echo esc_attr($tag); ?>" <?php selected($options['heading_level'], $tag); ?>>
                                    <?php echo esc_html($tag === 'p' ? __('sin encabezado', 'snowy-wp') : $tag); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Nivel del título de cada widget. Elige el que encaje bajo los encabezados de tus artículos: un nivel que se salta un escalón rompe el orden de la página para los lectores de pantalla.', 'snowy-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="snowy_wp_accent"><?php esc_html_e('Color de acento', 'snowy-wp'); ?></label></th>
                    <td>
                        <input type="color" id="snowy_wp_accent"
                               name="<?php echo esc_attr(SNOWY_WP_OPTION); ?>[accent]"
                               value="<?php echo esc_attr($options['accent'] ?: '#0369a1'); ?>">
                        <label style="margin-left:1rem">
                            <input type="checkbox" name="<?php echo esc_attr(SNOWY_WP_OPTION); ?>[accent_reset]" value="1">
                            <?php esc_html_e('Volver al azul de Snowy', 'snowy-wp'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Se usa en enlaces, gráficos y detalles de los widgets, para que encajen con tu marca.', 'snowy-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="snowy_wp_stations_url"><?php esc_html_e('Página de estaciones', 'snowy-wp'); ?></label></th>
                    <td>
                        <input type="url" class="regular-text" id="snowy_wp_stations_url"
                               name="<?php echo esc_attr(SNOWY_WP_OPTION); ?>[stations_url]"
                               value="<?php echo esc_attr($options['stations_url']); ?>" placeholder="https://">
                        <p class="description"><?php esc_html_e('Opcional. Tu propia página de estaciones, si quieres enlazarla desde los widgets.', 'snowy-wp'); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e('Colocación automática', 'snowy-wp'); ?></h2>
            <p style="max-width:640px"><?php esc_html_e('Inserta un widget en las entradas sin tener que editarlas una a una. No se aplica a las que ya llevan un widget puesto a mano.', 'snowy-wp'); ?></p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="snowy_wp_auto_shortcode"><?php esc_html_e('Widget a insertar', 'snowy-wp'); ?></label></th>
                    <td>
                        <input type="text" class="large-text code" id="snowy_wp_auto_shortcode"
                               name="<?php echo esc_attr(SNOWY_WP_OPTION); ?>[auto_shortcode]"
                               value="<?php echo esc_attr($options['auto_shortcode']); ?>"
                               placeholder="[snowy_extremos limite=&quot;6&quot;]">
                        <p class="description"><?php esc_html_e('El shortcode completo, con sus atributos. Vacío desactiva la colocación automática.', 'snowy-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="snowy_wp_auto_donde"><?php esc_html_e('Dónde', 'snowy-wp'); ?></label></th>
                    <td>
                        <select id="snowy_wp_auto_donde" name="<?php echo esc_attr(SNOWY_WP_OPTION); ?>[auto_donde]">
                            <option value="post" <?php selected($options['auto_donde'], 'post'); ?>><?php esc_html_e('En las entradas', 'snowy-wp'); ?></option>
                            <option value="page" <?php selected($options['auto_donde'], 'page'); ?>><?php esc_html_e('En las páginas', 'snowy-wp'); ?></option>
                            <option value="ambos" <?php selected($options['auto_donde'], 'ambos'); ?>><?php esc_html_e('En ambas', 'snowy-wp'); ?></option>
                        </select>
                        <select name="<?php echo esc_attr(SNOWY_WP_OPTION); ?>[auto_posicion]">
                            <option value="despues" <?php selected($options['auto_posicion'], 'despues'); ?>><?php esc_html_e('debajo del contenido', 'snowy-wp'); ?></option>
                            <option value="antes" <?php selected($options['auto_posicion'], 'antes'); ?>><?php esc_html_e('encima del contenido', 'snowy-wp'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="snowy_wp_auto_categorias"><?php esc_html_e('Solo en estas categorías', 'snowy-wp'); ?></label></th>
                    <td>
                        <input type="text" class="regular-text" id="snowy_wp_auto_categorias"
                               name="<?php echo esc_attr(SNOWY_WP_OPTION); ?>[auto_categorias]"
                               value="<?php echo esc_attr($options['auto_categorias']); ?>" placeholder="nieve, temporal">
                        <p class="description"><?php esc_html_e('Slugs separados por comas. Vacío aplica a todas.', 'snowy-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Estilos', 'snowy-wp'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr(SNOWY_WP_OPTION); ?>[css_siempre]" value="1" <?php checked(!empty($options['css_siempre'])); ?>>
                            <?php esc_html_e('Cargar los estilos en todas las páginas', 'snowy-wp'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Normalmente no hace falta: los estilos viajan solo con las páginas que llevan un widget. Actívalo si tu tema llama a los shortcodes desde una plantilla y ves los widgets sin formato.', 'snowy-wp'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <hr>

        <h2><?php esc_html_e('Licencia y acceso a los datos', 'snowy-wp'); ?></h2>
        <p style="max-width:640px">
            <?php esc_html_e('Este plugin es software libre bajo GPLv2, pero los datos de la red de Snowy no son públicos: para que muestre algo necesitas una clave de API con su cuota y sus permisos.', 'snowy-wp'); ?>
        </p>
        <p style="max-width:640px">
            <?php printf(
                /* translators: %s: direccion de correo de contacto */
                esc_html__('Solicita la tuya escribiendo a %s, contando qué vas a publicar y qué datos necesitas.', 'snowy-wp'),
                '<a href="mailto:' . esc_attr(SNOWY_WP_CONTACT) . '"><strong>' . esc_html(SNOWY_WP_CONTACT) . '</strong></a>'
            ); ?>
        </p>
    </div>
    <?php
}
