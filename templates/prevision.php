<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="<?php echo esc_attr($classes); ?>">
    <?php if ($show_head) : ?>
    <div class="snowy-wp-head">
        <<?php echo esc_attr($tag); ?>><?php echo esc_html($title); ?></<?php echo esc_attr($tag); ?>>
        <span class="snowy-wp-tag"><?php
            if ($forecast['total'] > 1) {
                printf(
                    /* translators: %s: numero de modelos que han respondido */
                    esc_html__('consenso de %s modelos', 'snowy-wp'),
                    esc_html($forecast['total'])
                );
            } else {
                esc_html_e('un solo modelo', 'snowy-wp');
            }
        ?></span>
    </div>
    <?php endif; ?>
    <ul class="snowy-wp-days">
        <?php foreach ($forecast['dias'] as $i => $dia) : ?>
            <?php
            $ts     = strtotime($dia['fecha']);
            $codigo = snowy_wp_weather_code($dia['codigo']);
            ?>
            <li class="snowy-wp-day-card">
                <p class="snowy-wp-day-name">
                    <strong><?php echo esc_html($i === 0 ? __('Hoy', 'snowy-wp') : wp_date('l', $ts)); ?></strong>
                    <span><?php echo esc_html(wp_date('j M', $ts)); ?></span>
                </p>
                <?php echo snowy_wp_weather_icon($codigo['icon']); ?>
                <p class="snowy-wp-day-sky"><?php echo esc_html($codigo['label']); ?></p>
                <p class="snowy-wp-day-temps">
                    <strong><?php echo esc_html(snowy_wp_temp($dia['tmax'])); ?></strong>
                    <span><?php echo esc_html(snowy_wp_temp($dia['tmin'])); ?></span>
                </p>
                <p class="snowy-wp-day-extra">
                    <span class="snowy-wp-day-rain<?php echo $dia['lluvia'] > 0 ? ' is-wet' : ''; ?>"<?php echo $forecast['total'] > 1 ? ' title="' . esc_attr(sprintf(
                        /* translators: 1: modelos que predicen lluvia, 2: modelos consultados */
                        __('%1$s de %2$s modelos predicen precipitación', 'snowy-wp'),
                        $dia['conLluvia'],
                        $forecast['total']
                    )) . '"' : ''; ?>>
                        <?php echo esc_html($dia['lluvia']); ?> %<?php
                            echo $dia['mm'] !== null && (float) $dia['mm'] > 0
                                ? esc_html(' · ' . number_format((float) $dia['mm'], 1, ',', '.') . ' mm')
                                : '';
                        ?>
                    </span>
                    <?php if ($dia['racha'] !== null) : ?>
                        <span><?php echo esc_html(snowy_wp_speed($dia['racha'], 0)); ?></span>
                    <?php endif; ?>
                </p>
                <?php if ($forecast['total'] > 1 && $dia['confianza']['class'] !== 'is-good') : ?>
                    <p class="snowy-wp-day-trust">
                        <span class="snowy-wp-risk <?php echo esc_attr($dia['confianza']['class']); ?>"><?php
                            printf(
                                /* translators: %s: nivel de confianza del dia */
                                esc_html__('Confianza %s', 'snowy-wp'),
                                esc_html(strtolower($dia['confianza']['label']))
                            );
                        ?></span>
                    </p>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <p class="snowy-wp-credit"><?php
        $modelos = '<strong>' . esc_html(snowy_wp_join_models($forecast['modelos'])) . '</strong>';
        if ($forecast['total'] > 1) {
            printf(
                /* translators: %s: lista de modelos, por ejemplo ECMWF, GFS e ICON */
                esc_html__('Consenso de %s, los mismos modelos que cruza Snowy: el valor lo da el primero y los otros miden cuánto se ponen de acuerdo.', 'snowy-wp'),
                $modelos
            );
        } else {
            printf(
                /* translators: %s: nombre del modelo */
                esc_html__('Previsión de %s; el resto de modelos del consenso no ha respondido.', 'snowy-wp'),
                $modelos
            );
        }
        echo ' ';
        printf(
            /* translators: 1: enlace, 2: nombre del lugar */
            esc_html__('Es previsión de modelo, no dato medido: %1$s.', 'snowy-wp'),
            sprintf(
                '<a href="%s" target="_blank" rel="noopener">%s</a>',
                esc_url($link),
                esc_html($name !== ''
                    ? sprintf(
                        /* translators: %s: nombre del lugar */
                        __('ver el tiempo de %s en Snowy', 'snowy-wp'),
                        $name
                    )
                    : __('ver el tiempo en Snowy', 'snowy-wp'))
            )
        );
    ?></p>
</div>
