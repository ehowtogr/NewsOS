<?php
// Security Check: Make sure it's WordPress calling the uninstall, not a direct hit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

global $wpdb;

// 1. Delete Custom Database Table (Tasks)
$table_name = $wpdb->prefix . 'newsai_alerts';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// 2. Delete All Options saved in wp_options table
$options_to_delete = [
    'newsai_site_name',
    'newsai_site_desc',
    'newsai_lang',
    'newsai_geo',
    'newsai_tone',
    'newsai_prompt_title',
    'newsai_prompt_rewrite',
    'newsai_prompt_seo',
    'newsai_competitor_rss',
    'nros_wizard_completed',
    'nros_do_activation_redirect',
    'nros_team_size',
    'nros_schema_type',
    'nros_site_name',
    'nros_site_niche',
    'nros_org_type',
    'nros_social_links',
    'newsai_timeline_days',
    'newsai_timeline_para',
    'newsai_timeline_heading',
    'newsai_timeline_mode',
    'newsai_timeline_limit',
    'nros_schema_global_v'
];

foreach ( $options_to_delete as $option ) {
    delete_option( $option );
}

// Ασφαλιστική δικλείδα: Διαγραφή οποιουδήποτε option μας ξέφυγε
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'newsai_%' OR option_name LIKE 'nros_%'");

// 3. Delete Transients (Cached Data)
delete_transient( 'nros_google_update_status' );
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nros_timeline_html_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_nros_timeline_html_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_newsai_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_newsai_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nros_schema_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_nros_schema_%'");

// ==========================================
// 🚀 FIX: MISSING CLEANUP (AUDIT RECOMMENDATIONS)
// ==========================================

// 4. Delete User Meta (E-E-A-T Profiles)
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ('nros_job_title', 'nros_alumni_of', 'nros_knows_about', 'nros_social_facebook', 'nros_social_twitter', 'nros_social_linkedin')");

// 5. Delete Term Meta (Entity SEO, Geo & Wikidata)
$wpdb->query("DELETE FROM {$wpdb->termmeta} WHERE meta_key IN ('nros_wikidata_url', 'nros_lat', 'nros_lng', 'nros_entity_type')");

// 6. Delete Post Meta (Timeline Exclusion Flags)
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_newsai_disable_auto_timeline'");