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
        <p class="snowy-wp-lead"><?php echo esc_html($summary); ?></p>
        <div class="snowy-wp-bars">
            <div class="snowy-wp-bars-scale" aria-hidden="true">
                <?php if ($spread[0] > 0) : ?><span style="top:0"><?php echo esc_html(snowy_wp_num_signed($spread[0], 1) . '°'); ?></span><?php endif; ?>
                <span style="top:<?php echo esc_attr(snowy_wp_pct($spread[2])); ?>%">0°</span>
                <?php if ($spread[1] < 0) : ?><span style="top:100%"><?php echo esc_html(snowy_wp_num_signed($spread[1], 1) . '°'); ?></span><?php endif; ?>
            </div>
            <?php echo $svg; ?>
        </div>
        <ol class="snowy-wp-bars-cols" aria-hidden="true">
            <?php foreach ($columns as $column) : ?>
            <li class="<?php echo esc_attr($column['class']); ?>">
                <span class="snowy-wp-bars-month"><span class="snowy-wp-long"><?php echo esc_html($column['month']); ?></span><span class="snowy-wp-short"><?php echo esc_html($column['initial']); ?></span></span>
                <span class="snowy-wp-bars-value"><?php echo esc_html($column['value']); ?></span>
            </li>
            <?php endforeach; ?>
        </ol>
        <?php echo $table; ?>
    </div>
    <?php echo $credit; ?>
</div>
