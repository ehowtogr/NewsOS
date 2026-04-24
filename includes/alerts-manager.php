<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_init', 'newsai_handle_bulk_import');
function newsai_handle_bulk_import() {
    if (isset($_POST['newsai_bulk_import_btn'])) {
        if (!isset($_POST['newsai_import_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['newsai_import_nonce'])), 'newsai_bulk_import')) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'newsai_alerts';
        
        $geo = strtolower(sanitize_text_field(get_option('newsai_geo', 'GR'))); 
        $json_file = NEWSAI_PATH . "data/holidays-{$geo}.json";

        if (file_exists($json_file)) {
            $json_data = file_get_contents($json_file);
            $alerts = json_decode($json_data, true);

            if (!empty($alerts)) {
                foreach ($alerts as $alert) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
                    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE title = %s AND day = %d AND month = %d", $alert['title'], $alert['day'], $alert['month']));

                    if (!$exists) {
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        $wpdb->insert($table, [
                            'title'     => sanitize_text_field($alert['title']),
                            'day'       => intval($alert['day']),
                            'month'     => intval($alert['month']),
                            'year'      => 0,
                            'lead_days' => intval($alert['lead']),
                            'author_id' => 0
                        ]);
                    }
                }
                wp_safe_redirect(admin_url('admin.php?page=newsai-settings&tab=alerts&msg=imported'));
                exit;
            }
       } else {
            $lang = get_option('newsai_lang', 'el');
            $msg = ($lang == 'el') ? "Το αρχείο για τη χώρα $geo δεν βρέθηκε." : "File for country $geo not found.";
            wp_die(esc_html($msg));
        }
    }
}
// ΤΕΛΟΣ ΑΡΧΕΙΟΥ