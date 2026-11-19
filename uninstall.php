<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

function snowy_wp_uninstall_site()
{
    global $wpdb;

    delete_option('snowy_wp_settings');

    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like('_transient_snowy_wp_') . '%',
        $wpdb->esc_like('_transient_timeout_snowy_wp_') . '%'
    ));
}

if (is_multisite()) {
    foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $site_id) {
        switch_to_blog($site_id);
        snowy_wp_uninstall_site();
        restore_current_blog();
    }
} else {
    snowy_wp_uninstall_site();
}

wp_cache_flush();
