<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="<?php echo esc_attr($classes); ?>">
    <?php if ($show_head) : ?>
    <div class="snowy-wp-head">
        <<?php echo esc_attr($tag); ?>><?php echo esc_html($title); ?></<?php echo esc_attr($tag); ?>>
        <span class="snowy-wp-tag"><?php echo esc_html($subtitle); ?></span>
    </div>
    <?php endif; ?>
    <div class="snowy-wp-climate-body">
        <dl class="snowy-wp-figures">
            <?php if ($year['warmShare'] !== null) : ?>
            <div><dt><?php esc_html_e('Días cálidos para su fecha (p ≥ 80)', 'snowy-wp'); ?></dt><dd><?php echo esc_html(snowy_wp_num($year['warmShare'], 0) . SNOWY_WP_NBSP . '%'); ?></dd></div>
            <?php endif; ?>
            <?php if ($year['coldShare'] !== null) : ?>
            <div><dt><?php esc_html_e('Días fríos para su fecha (p ≤ 20)', 'snowy-wp'); ?></dt><dd><?php echo esc_html(snowy_wp_num($year['coldShare'], 0) . SNOWY_WP_NBSP . '%'); ?></dd></div>
            <?php endif; ?>
        </dl>
        <div class="snowy-wp-cal">
            <div class="snowy-wp-cal-days" aria-hidden="true">
                <?php foreach ([1, 5, 10, 15, 20, 25, 30] as $day) : ?>
                <span style="left:<?php echo esc_attr(snowy_wp_pct(($day - 0.5) / 31 * 100)); ?>%"><?php echo esc_html($day); ?></span>
                <?php endforeach; ?>
            </div>
            <div class="snowy-wp-cal-body">
                <ol class="snowy-wp-cal-months" aria-hidden="true">
                    <?php foreach (SNOWY_WP_MONTHS_SHORT as $month) : ?>
                    <li><?php echo esc_html($month); ?></li>
                    <?php endforeach; ?>
                </ol>
                <?php echo $svg; ?>
            </div>
        </div>
        <div class="snowy-wp-scale" aria-hidden="true">
            <div class="snowy-wp-scale-bar">
                <?php foreach (['1', '2', '3', '4', '5', 'n', '6', '7', '8', '9', '10'] as $step) : ?>
                <span class="snowy-wp-s<?php echo esc_attr($step); ?>"></span>
                <?php endforeach; ?>
            </div>
            <div class="snowy-wp-scale-labels">
                <span><?php esc_html_e('Muy frío para la fecha', 'snowy-wp'); ?></span>
                <span><?php esc_html_e('Normal', 'snowy-wp'); ?></span>
                <span><?php esc_html_e('Muy cálido', 'snowy-wp'); ?></span>
            </div>
        </div>
        <p class="snowy-wp-note"><?php
        if ($last_date !== '') {
            printf(
                /* translators: %s: fecha */
                esc_html__('Datos hasta el %s.', 'snowy-wp'),
                esc_html(snowy_wp_long_date($last_date))
            );
        }
        if ($provisional !== '') {
            echo ' ';
            printf(
                /* translators: %s: fecha */
                esc_html__('Desde el %s son provisionales, más claros en el calendario, y se sustituyen por los consolidados cuando se publican.', 'snowy-wp'),
                esc_html(snowy_wp_long_date($provisional))
            );
        }
        ?></p>
        <?php echo $table; ?>
    </div>
    <?php echo $credit; ?>
</div>
