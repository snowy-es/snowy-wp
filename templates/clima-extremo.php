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
        <p class="snowy-wp-verdict <?php echo esc_attr($tone); ?>"><?php echo esc_html($verdict); ?></p>
        <p class="snowy-wp-lead"><?php echo esc_html($detail); ?></p>
        <div class="snowy-wp-dist">
            <div class="snowy-wp-dist-plot">
                <?php echo $svg; ?>
                <?php foreach ($marks as $mark) : ?>
                <span class="snowy-wp-dist-rule is-<?php echo esc_attr($mark['key']); ?>" style="left:<?php echo esc_attr($mark['left']); ?>%" aria-hidden="true"></span>
                <?php endforeach; ?>
                <span class="snowy-wp-dist-marker<?php echo (float) $marker['left'] > 70 ? ' is-right' : ''; ?>" style="left:<?php echo esc_attr($marker['left']); ?>%" aria-hidden="true"><span><?php echo esc_html($marker['label']); ?></span></span>
            </div>
            <div class="snowy-wp-dist-labels" aria-hidden="true">
                <?php foreach ($marks as $mark) : ?>
                <span class="is-<?php echo esc_attr($mark['key']); ?>" style="left:<?php echo esc_attr($mark['left']); ?>%"><?php echo esc_html($mark['label']); ?><span class="snowy-wp-long"><?php echo esc_html($mark['value']); ?></span></span>
                <?php endforeach; ?>
            </div>
            <div class="snowy-wp-dist-axis" aria-hidden="true">
                <span>&larr; <?php echo esc_html($axis[0]); ?></span>
                <span><?php echo esc_html($axis[1]); ?> &rarr;</span>
            </div>
        </div>
    </div>
    <?php echo $credit; ?>
</div>
