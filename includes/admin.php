<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Catalogo de piezas disponibles. Es la fuente de la pagina de ayuda del admin
 * y evita que la lista se desincronice de lo que el plugin registra de verdad.
 */
const SNOWY_WP_WIDGETS = [
    'avisos' => [
        'code' => '[snowy_avisos]',
        'name' => 'Avisos de AEMET',
        'desc' => 'Avisos vigentes para hoy, mañana y pasado, con nivel y horario. Si no hay ninguno, lo dice.',
    ],
    'extremos' => [
        'code' => '[snowy_extremos limite="8"]',
        'name' => 'Extremos del día',
        'desc' => 'Las estaciones más cálidas y más frías de hoy. El atributo limite controla cuántas se muestran.',
    ],
    'estaciones' => [
        'code' => '[snowy_estaciones]',
        'name' => 'Todas las estaciones',
        'desc' => 'Tabla completa con temperatura actual, máxima, mínima y humedad. Acepta ids="..." para filtrar.',
    ],
    'viento' => [
        'code' => '[snowy_viento limite="8"]',
        'name' => 'Rachas de viento',
        'desc' => 'Ranking de rachas máximas registradas hoy.',
    ],
    'estacion' => [
        'code' => '[snowy_estacion id="9115X"]',
        'name' => 'Ficha de una estación',
        'desc' => 'Tarjeta con el dato de una estación concreta, para incrustar dentro de un artículo. Acepta id o nombre.',
    ],
];

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
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Widgets de Snowy', 'snowy-wp'); ?></h1>
        <p>
            <?php if ($stations) : ?>
                <?php printf(
                    /* translators: 1: numero de estaciones, 2: region configurada */
                    esc_html__('Conectado: %1$s estaciones disponibles en %2$s.', 'snowy-wp'),
                    '<strong>' . count($stations) . '</strong>',
                    '<strong>' . esc_html(snowy_wp_region_label()) . '</strong>'
                ); ?>
            <?php else : ?>
                <strong style="color:#b32d2e"><?php esc_html_e('Sin conexión con la API.', 'snowy-wp'); ?></strong>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=snowy-wp-settings')); ?>"><?php esc_html_e('Revisa los ajustes del plugin.', 'snowy-wp'); ?></a>
            <?php endif; ?>
        </p>

        <h2><?php esc_html_e('Cómo insertarlos', 'snowy-wp'); ?></h2>
        <p><?php esc_html_e('En el editor de bloques, escribe /snowy y elige el bloque. En el editor clásico, pega el shortcode directamente en el texto.', 'snowy-wp'); ?></p>

        <table class="widefat striped" style="max-width:900px">
            <thead><tr>
                <th style="width:270px"><?php esc_html_e('Shortcode', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Qué muestra', 'snowy-wp'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach (SNOWY_WP_WIDGETS as $w) : ?>
                <tr>
                    <td><code style="display:block;padding:.4rem;background:#f6f7f7;user-select:all"><?php echo esc_html($w['code']); ?></code></td>
                    <td>
                        <strong><?php echo esc_html($w['name']); ?></strong><br>
                        <span style="color:#646970"><?php echo esc_html($w['desc']); ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($stations) : ?>
            <h2><?php esc_html_e('Identificadores de estación', 'snowy-wp'); ?></h2>
            <p><?php esc_html_e('Para usar con [snowy_estacion id="..."] o [snowy_estaciones ids="..."]:', 'snowy-wp'); ?></p>
            <table class="widefat striped" style="max-width:640px">
                <thead><tr>
                    <th><?php esc_html_e('Estación', 'snowy-wp'); ?></th>
                    <th style="width:130px"><?php esc_html_e('id', 'snowy-wp'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($stations as $s) : ?>
                    <tr>
                        <td><?php echo esc_html($s['stationName'] ?? ''); ?></td>
                        <td><code style="user-select:all"><?php echo esc_html($s['stationId'] ?? ''); ?></code></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
