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
        <?php if ($figure !== '') : ?>
        <div class="snowy-wp-big">
            <strong><?php echo esc_html($figure); ?></strong>
            <span><?php echo esc_html($figure_note); ?></span>
        </div>
        <?php endif; ?>
        <div class="snowy-wp-stripes">
            <span class="snowy-wp-stripes-peak" style="left:<?php echo esc_attr($stripes['warmest']['left']); ?>%" aria-hidden="true"><span><?php echo esc_html($warmest); ?></span></span>
            <?php echo $svg; ?>
            <div class="snowy-wp-stripes-x" aria-hidden="true">
                <?php foreach ($ticks as $tick) : ?>
                <span style="left:<?php echo esc_attr($tick['left']); ?>%"><?php echo esc_html($tick['label']); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="snowy-wp-scale snowy-wp-scale--inline" aria-hidden="true">
            <span><?php echo esc_html($legend[0]); ?></span>
            <div class="snowy-wp-scale-bar">
                <?php for ($step = 1; $step <= 10; $step++) : ?>
                <span class="snowy-wp-s<?php echo esc_attr($step); ?>"></span>
                <?php endfor; ?>
            </div>
            <span><?php echo esc_html($legend[1]); ?></span>
        </div>
    </div>
    <?php echo $credit; ?>
</div>
