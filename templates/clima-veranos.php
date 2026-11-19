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
        <ol class="snowy-wp-hbars" aria-hidden="true">
            <?php foreach ($rows as $row) : ?>
            <li>
                <span class="snowy-wp-hbars-label"><?php echo esc_html($row['label']); ?></span>
                <span class="snowy-wp-hbars-track"><span class="snowy-wp-hbars-bar <?php echo esc_attr($row['class']); ?>" style="width:<?php echo esc_attr($row['width']); ?>%"></span><span class="snowy-wp-hbars-normal" style="left:<?php echo esc_attr($normal['left']); ?>%"></span></span>
                <span class="snowy-wp-hbars-value"><?php echo esc_html($row['text']); ?></span>
            </li>
            <?php endforeach; ?>
        </ol>
        <p class="snowy-wp-note snowy-wp-hbars-key" aria-hidden="true"><span class="snowy-wp-hbars-normal-key"></span><?php echo esc_html($normal['label']); ?></p>
        <?php echo $table; ?>
    </div>
    <?php echo $credit; ?>
</div>
