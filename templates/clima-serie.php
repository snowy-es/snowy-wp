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
            <?php foreach ($figures as $figure) : ?>
            <div><dt><?php echo esc_html($figure[0]); ?></dt><dd><?php echo esc_html($figure[1]); ?></dd><dd class="snowy-wp-figure-note"><?php echo esc_html($figure[2]); ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <div class="snowy-wp-line">
            <div class="snowy-wp-line-y" aria-hidden="true">
                <?php foreach ($y_labels as $label) : ?>
                <span style="top:<?php echo esc_attr($label['top']); ?>%"><?php echo esc_html($label['label']); ?></span>
                <?php endforeach; ?>
            </div>
            <div class="snowy-wp-line-plot">
                <?php echo $svg; ?>
                <?php foreach ($dots as $dot) : ?>
                <span class="snowy-wp-dot snowy-wp-s<?php echo esc_attr($dot['step']); ?>" style="left:<?php echo esc_attr($dot['left']); ?>%;top:<?php echo esc_attr($dot['top']); ?>%" title="<?php echo esc_attr($dot['title']); ?>" aria-hidden="true"></span>
                <?php endforeach; ?>
            </div>
            <div class="snowy-wp-line-x" aria-hidden="true">
                <?php foreach ($x_labels as $label) : ?>
                <span style="left:<?php echo esc_attr($label['left']); ?>%"><?php echo esc_html($label['label']); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="snowy-wp-legend" aria-hidden="true">
            <span class="snowy-wp-legend-dots"><?php echo esc_html($legend[0]); ?></span>
            <span class="snowy-wp-legend-trend"><?php echo esc_html($legend[1]); ?></span>
        </div>
        <?php echo $table; ?>
    </div>
    <?php echo $credit; ?>
</div>
