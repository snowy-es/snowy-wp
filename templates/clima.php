<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="<?php echo esc_attr($classes); ?>">
    <?php if ($show_head) : ?>
    <div class="snowy-wp-head">
        <<?php echo esc_attr($tag); ?>><?php echo esc_html($title); ?></<?php echo esc_attr($tag); ?>>
        <span class="snowy-wp-tag"><?php printf(
            /* translators: 1: primer año de la serie, 2: ultimo año */
            esc_html__('%1$s-%2$s', 'snowy-wp'),
            esc_html($climate['desde']),
            esc_html($climate['hasta'])
        ); ?></span>
    </div>
    <?php endif; ?>
    <div class="snowy-wp-total">
        <div class="snowy-wp-total-figure">
            <strong><?php echo esc_html(snowy_wp_signed($change, 1, '°C')); ?></strong>
            <span><?php esc_html_e('desde el inicio de la serie', 'snowy-wp'); ?></span>
        </div>
        <div class="snowy-wp-total-body">
            <p class="snowy-wp-total-meta">
                <?php printf(
                    /* translators: %s: variacion de temperatura por decada */
                    esc_html__('La temperatura media sube %s.', 'snowy-wp'),
                    '<strong>' . esc_html(snowy_wp_signed($per_decade, 2, '°C')) . '</strong> ' . esc_html__('por década', 'snowy-wp')
                ); ?>
                <?php echo esc_html(empty($climate['tendencia']['significant'])
                    ? __('La tendencia todavía no es estadísticamente firme.', 'snowy-wp')
                    : __('La tendencia es estadísticamente firme.', 'snowy-wp')); ?>
            </p>
            <?php if ($jump !== null) : ?>
                <p class="snowy-wp-total-meta"><?php printf(
                    /* translators: 1: media de la ultima decada, 2: media de las tres primeras decadas */
                    esc_html__('La última década promedia %1$s, frente a %2$s de las tres primeras.', 'snowy-wp'),
                    '<strong>' . esc_html(number_format($climate['ahora'], 1, ',', '.') . ' °C') . '</strong>',
                    esc_html(number_format($climate['antes'], 1, ',', '.') . ' °C')
                ); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($climate['indices']) : ?>
        <div class="snowy-wp-scroll"><table class="snowy-wp-table">
            <thead><tr>
                <th><?php esc_html_e('Días al año', 'snowy-wp'); ?></th>
                <th><?php printf(
                    /* translators: 1: primer año, 2: año treinta de la serie */
                    esc_html__('%1$s-%2$s', 'snowy-wp'),
                    esc_html($climate['desde']),
                    esc_html($climate['desde'] + 29)
                ); ?></th>
                <th><?php printf(
                    /* translators: 1: hace diez años, 2: ultimo año */
                    esc_html__('%1$s-%2$s', 'snowy-wp'),
                    esc_html($climate['hasta'] - 9),
                    esc_html($climate['hasta'])
                ); ?></th>
                <th><?php esc_html_e('Cambio', 'snowy-wp'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($indices as $key => $label) : ?>
                <?php
                $fila = $climate['indices'][$key] ?? null;
                if (!$fila || $fila['antes'] === null || $fila['ahora'] === null) {
                    continue;
                }
                $cambio = $fila['ahora'] - $fila['antes'];
                ?>
                <tr>
                    <td><?php echo esc_html($label); ?></td>
                    <td><?php echo esc_html(number_format($fila['antes'], 1, ',', '.')); ?></td>
                    <td class="snowy-wp-val"><?php echo esc_html(number_format($fila['ahora'], 1, ',', '.')); ?></td>
                    <td><span class="snowy-wp-trend <?php echo esc_attr($cambio > 0 ? 'is-up' : ($cambio < 0 ? 'is-down' : '')); ?>">
                        <?php echo esc_html(snowy_wp_signed($cambio, 1, __('días', 'snowy-wp'))); ?>
                    </span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
    <?php if ($show_records && $climate['records']) : ?>
        <dl class="snowy-wp-card-grid snowy-wp-records">
            <?php foreach ($records as $key => $meta) : ?>
                <?php
                $record = $climate['records'][$key] ?? null;
                if (!$record || !isset($record[0], $record[1])) {
                    continue;
                }
                ?>
                <div>
                    <dt><?php echo esc_html($meta['label']); ?></dt>
                    <dd><?php echo esc_html(number_format((float) $record[1], 1, ',', '.') . ' ' . $meta['unit']); ?>
                        <small><?php echo esc_html(wp_date('j M Y', strtotime($record[0]))); ?></small></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    <?php endif; ?>
    <p class="snowy-wp-credit"><?php printf(
        /* translators: %s: enlace a snowy.es */
        esc_html__('Serie de ERA5-Land procesada por %s. Cada celda cubre unos diez kilómetros de terreno.', 'snowy-wp'),
        '<a href="' . esc_url(SNOWY_WP_SITE . '/clima') . '" target="_blank" rel="noopener"><strong>Snowy</strong></a>'
    ); ?></p>
</div>
