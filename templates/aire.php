<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="<?php echo esc_attr($classes); ?>">
    <?php if ($show_head) : ?>
    <div class="snowy-wp-head">
        <<?php echo esc_attr($tag); ?>><?php echo esc_html($title); ?></<?php echo esc_attr($tag); ?>>
        <span class="snowy-wp-tag"><?php esc_html_e('en vivo', 'snowy-wp'); ?></span>
    </div>
    <?php endif; ?>
    <div class="snowy-wp-air">
        <div class="snowy-wp-air-index <?php echo esc_attr($level['class'] ?? ''); ?>">
            <strong><?php echo esc_html((int) $now['aqi']); ?></strong>
            <span><?php echo esc_html($level ? $level['label'] : __('Sin clasificar', 'snowy-wp')); ?></span>
        </div>
        <dl class="snowy-wp-card-grid">
            <?php foreach ($pollutants as $key => $p) : ?>
                <?php if (isset($now[$key]) && $now[$key] !== null) : ?>
                    <div>
                        <dt><?php echo esc_html($p['label']); ?></dt>
                        <dd><?php echo esc_html(number_format((float) $now[$key], 1, ',', '.')); ?>
                            <small><?php echo esc_html($p['unit']); ?></small></dd>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </dl>
    </div>
    <p class="snowy-wp-credit"><?php printf(
        /* translators: %s: enlace a snowy.es */
        esc_html__('Índice europeo de calidad del aire, servido por %s.', 'snowy-wp'),
        '<a href="' . esc_url(SNOWY_WP_SITE) . '" target="_blank" rel="noopener"><strong>Snowy</strong></a>'
    ); ?></p>
</div>
