<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="<?php echo esc_attr($classes); ?>">
    <?php if ($show_head) : ?>
    <div class="snowy-wp-head">
        <<?php echo esc_attr($tag); ?>><?php echo esc_html($title); ?></<?php echo esc_attr($tag); ?>>
        <span class="snowy-wp-tag"><?php esc_html_e('agua embalsada', 'snowy-wp'); ?></span>
    </div>
    <?php endif; ?>
    <div class="snowy-wp-total">
        <div class="snowy-wp-total-figure">
            <strong><?php echo esc_html(number_format($percentage, 1, ',', '.')); ?> %</strong>
            <span><?php echo esc_html($overall['label']); ?></span>
        </div>
        <div class="snowy-wp-total-body">
            <?php echo snowy_wp_fill_bar($percentage, $overall['class']); ?>
            <p class="snowy-wp-total-meta">
                <?php printf(
                    /* translators: 1: volumen embalsado, 2: capacidad total, 3: numero de embalses */
                    esc_html__('%1$s de %2$s en %3$s embalses.', 'snowy-wp'),
                    '<strong>' . esc_html(snowy_wp_volume($volume)) . '</strong>',
                    esc_html(snowy_wp_volume($capacity)),
                    esc_html($count)
                ); ?>
                <?php if ($week != 0.0) : ?>
                    <?php printf(
                        /* translators: %s: variacion de volumen en la ultima semana */
                        esc_html__('Esta semana %s.', 'snowy-wp'),
                        $week > 0
                            ? '<span class="snowy-wp-trend is-up">' . esc_html(sprintf(__('ha ganado %s', 'snowy-wp'), snowy_wp_volume(abs($week)))) . '</span>'
                            : '<span class="snowy-wp-trend is-down">' . esc_html(sprintf(__('ha perdido %s', 'snowy-wp'), snowy_wp_volume(abs($week)))) . '</span>'
                    ); ?>
                <?php endif; ?>
            </p>
            <?php if ($basin_forecast && isset($basin_forecast['totalPrecipitation7d'])) : ?>
                <p class="snowy-wp-total-meta"><?php
                    $mm = (float) $basin_forecast['totalPrecipitation7d'];
                    if ($mm < 1) {
                        printf(
                            /* translators: %s: nombre de la cuenca */
                            esc_html__('No se espera lluvia sobre la cuenca del %s en los próximos siete días.', 'snowy-wp'),
                            esc_html($basin)
                        );
                    } else {
                        printf(
                            /* translators: 1: nombre de la cuenca, 2: precipitacion prevista */
                            esc_html__('Lluvia prevista en la cuenca del %1$s: %2$s en siete días.', 'snowy-wp'),
                            esc_html($basin),
                            '<strong>' . esc_html(number_format($mm, 1, ',', '.') . ' mm') . '</strong>'
                        );
                    }
                ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="snowy-wp-scroll"><table class="snowy-wp-table">
        <thead><tr>
            <th><?php esc_html_e('Embalse', 'snowy-wp'); ?></th>
            <th><?php esc_html_e('Llenado', 'snowy-wp'); ?></th>
            <th><?php esc_html_e('Embalsado', 'snowy-wp'); ?></th>
            <th><?php esc_html_e('Capacidad', 'snowy-wp'); ?></th>
            <th><?php esc_html_e('Esta semana', 'snowy-wp'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($reservoirs as $e) : ?>
            <?php $nivel = snowy_wp_fill_level($e); ?>
            <tr>
                <td><?php echo esc_html($e['name'] ?? '—'); ?></td>
                <td class="snowy-wp-val">
                    <?php echo esc_html(number_format((float) ($e['fillPercentage'] ?? 0), 1, ',', '.')); ?> %
                    <?php echo snowy_wp_fill_bar($e['fillPercentage'] ?? 0, $nivel['class']); ?>
                </td>
                <td><?php echo esc_html(snowy_wp_volume($e['currentVolume'] ?? null)); ?></td>
                <td><?php echo esc_html(snowy_wp_volume($e['totalCapacity'] ?? null)); ?></td>
                <td><?php echo snowy_wp_reservoir_change($e); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <p class="snowy-wp-credit"><?php
        printf(
            /* translators: %s: enlace a la pagina de embalses en snowy.es */
            esc_html__('Datos del boletín hidrológico del MITECO, servidos por %s.', 'snowy-wp'),
            '<a href="' . esc_url($link) . '" target="_blank" rel="noopener"><strong>Snowy</strong></a>'
        );
        if ($date) {
            echo ' ';
            printf(
                /* translators: %s: fecha del ultimo boletin */
                esc_html__('Última lectura del %s.', 'snowy-wp'),
                esc_html(wp_date('j \d\e F \d\e Y', strtotime($date)))
            );
        }
    ?></p>
</div>
