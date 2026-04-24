<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function() {
    $svg_icon = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5" fill="black"></circle><circle cx="15.5" cy="8.5" r="1.5" fill="black"></circle><path d="M9 15c1 1 2 1.5 3 1.5s2-.5 3-1.5"></path></svg>');
    add_menu_page( 'Newsroom OS', 'Editorial Control', 'edit_posts', 'newsai-settings', 'newsai_render_admin', $svg_icon, 25 );
});

function newsai_render_admin() {
    global $wpdb;
    $table = $wpdb->prefix . 'newsai_alerts';
    
    // 🚀 FIX: Caching των στηλών της βάσης για αποφυγή heavy DESC queries
    $table_cols = get_transient('newsai_table_cols_cache');
    if (false === $table_cols) {
        $table_cols = $wpdb->get_col("DESC {$table}", 0);
        set_transient('newsai_table_cols_cache', $table_cols, DAY_IN_SECONDS);
    }

    $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'alerts'; 
    $lang = get_option('newsai_lang', 'el');
    $geo_opt = get_option('newsai_geo', 'GR');

    // 🚀 FIX: 1 Πεδίο Πόλης, Δυναμική Μετάφραση!
 $i18n = [
        'settings_title' => ($lang == 'en') ? 'Site Details' : 'Στοιχεία Ιστότοπου',
        'schema_title'   => ($lang == 'en') ? 'Schema & SEO Settings' : 'Ρυθμίσεις Schema & SEO',
        'prompts_title'  => ($lang == 'en') ? '🧠 Prompt Engineering (Customization)' : '🧠 Prompt Engineering (Προσαρμογή)',
        'prompts_desc'   => ($lang == 'en') ? 'Customize the commands copied to your AI.' : 'Προσαρμόστε τις εντολές που αντιγράφουν οι συντάκτες στο AI.',
        'timeline_title' => ($lang == 'en') ? '⏳ Auto-Timeline Settings' : '⏳ Ρυθμίσεις Αυτόματου Χρονικού',
        'btn_save'       => ($lang == 'en') ? 'Save Settings' : 'Αποθήκευση',
        'add_alert'      => ($lang == 'en') ? '➕ Assign New Task' : '➕ Ανάθεση Νέου Θέματος',
        'edit_alert'     => ($lang == 'en') ? '✏️ Edit Task' : '✏️ Επεξεργασία Task',
        'topic'          => ($lang == 'en') ? 'Story Topic' : 'Θέμα / Γεγονός',
        'topic_ph'       => ($lang == 'en') ? 'e.g. Local Elections' : 'π.χ. Δημοτικές Εκλογές',
        'notes'          => ($lang == 'en') ? '📝 Briefing / Notes' : '📝 Οδηγίες προς Συντάκτη',
        'notes_ph'       => ($lang == 'en') ? 'e.g. Ask the Mayor for a statement...' : 'π.χ. Ζήτα δήλωση από τον Δήμαρχο...',
        'date'           => ($lang == 'en') ? '📅 Date' : '📅 Ημερομηνία',
        'recurrence'     => ($lang == 'en') ? 'Recurrence:' : 'Επανάληψη:',
        'yearly'         => ($lang == 'en') ? 'Yearly' : 'Κάθε χρόνο',
        'monthly'        => ($lang == 'en') ? 'Monthly' : 'Κάθε μήνα',
        'once'           => ($lang == 'en') ? 'One-time' : 'Μία φορά (Χωρίς)',
        'lead_days'      => ($lang == 'en') ? '⏳ Lead Days' : '⏳ Προειδοποίηση',
        'days_before'    => ($lang == 'en') ? 'days before' : 'ημέρες πριν',
        'assign_to'      => ($lang == 'en') ? '👤 Assign to Writer' : '👤 Ανάθεση σε Συντάκτη',
        'all_users'      => ($lang == 'en') ? 'All Authors' : 'Όλοι (All)',
        'btn_add_task'   => ($lang == 'en') ? '+ Assign Task' : '+ Ανάθεση Task',
        'btn_update_task'=> ($lang == 'en') ? '💾 Save Changes' : '💾 Αποθήκευση Αλλαγών',
        'btn_cancel'     => ($lang == 'en') ? 'Cancel' : 'Ακύρωση',
        'msg_task_ok'    => ($lang == 'en') ? '✅ Task assigned successfully!' : '✅ Το Task ανατέθηκε με επιτυχία!',
        'msg_task_upd'   => ($lang == 'en') ? '✅ Task updated successfully!' : '✅ Το Task ενημερώθηκε με επιτυχία!',
        'feed_pages'     => ($lang == 'en') ? 'Custom Feed Page IDs' : 'ID Σελίδων Ροής (Custom Feeds)',
        'feed_pages_d'   => ($lang == 'en') ? 'Comma-separated IDs for Page Builders (Enables ItemList Carousel).' : 'Διαχωρισμένα με κόμμα π.χ. 14, 25 (Ενεργοποιεί το ItemList Carousel σε Page Builders).',
        'speakable'      => ($lang == 'en') ? 'Speakable CSS Classes' : 'Κλάσεις Speakable (Voice SEO)',
        'speakable_d'    => ($lang == 'en') ? 'e.g. .headline, .summary. Leave empty to disable.' : 'π.χ. .headline, .summary. Κενό για απενεργοποίηση.',
        // 🚀 ΑΥΤΑ ΤΑ ΔΥΟ ΕΛΕΙΠΑΝ ΚΑΙ ΕΚΑΝΑΝ ΤΟ ΚΕΝΟ:
        'city'           => ($lang == 'en') ? 'Primary City (Local SEO)' : 'Κύρια Πόλη (Local SEO)',
        'city_desc'      => ($lang == 'en') ? 'Enter only the city name (e.g. Patras).' : 'Γράψτε μόνο το όνομα της πόλης (π.χ. Πάτρα).'
    ];

    // ==========================================
    // SAVE SETTINGS
    // ==========================================
    if (isset($_POST['newsai_save_settings'])) {
        if (!isset($_POST['newsai_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['newsai_settings_nonce'])), 'newsai_save_settings_action')) { wp_die('Security check failed'); }
        
        $old_lang = get_option('newsai_lang', 'el');
        $new_lang = isset($_POST['lang']) ? sanitize_text_field(wp_unslash($_POST['lang'])) : 'el';
        
        update_option('nros_site_name', isset($_POST['sn']) ? sanitize_text_field(wp_unslash($_POST['sn'])) : ''); 
        update_option('newsai_site_desc', isset($_POST['sd']) ? sanitize_text_field(wp_unslash($_POST['sd'])) : ''); 
        update_option('newsai_lang', $new_lang);
        update_option('newsai_geo', isset($_POST['geo']) ? sanitize_text_field(wp_unslash($_POST['geo'])) : 'GR');
        
        update_option('nros_primary_city', isset($_POST['primary_city']) ? sanitize_text_field(wp_unslash($_POST['primary_city'])) : '');
        
        update_option('nros_schema_type', isset($_POST['schema_type']) ? sanitize_text_field(wp_unslash($_POST['schema_type'])) : 'newsarticle');
        update_option('nros_org_type', isset($_POST['org_type']) ? sanitize_text_field(wp_unslash($_POST['org_type'])) : 'NewsMediaOrganization');
        update_option('nros_social_links', isset($_POST['social_links']) ? sanitize_textarea_field(wp_unslash($_POST['social_links'])) : '');
        update_option('newsai_custom_feed_pages', isset($_POST['feed_pages']) ? sanitize_text_field(wp_unslash($_POST['feed_pages'])) : '');
        update_option('newsai_speakable_selectors', isset($_POST['speakable']) ? sanitize_text_field(wp_unslash($_POST['speakable'])) : '');
        
        update_option('newsai_timeline_days', isset($_POST['t_days']) ? intval($_POST['t_days']) : 30);
        update_option('newsai_timeline_para', isset($_POST['t_para']) ? intval($_POST['t_para']) : -1);
        update_option('newsai_timeline_heading', isset($_POST['t_heading']) ? sanitize_text_field(wp_unslash($_POST['t_heading'])) : '');
        update_option('newsai_timeline_mode', isset($_POST['t_mode']) ? sanitize_text_field(wp_unslash($_POST['t_mode'])) : 'auto');
        update_option('newsai_timeline_limit', isset($_POST['t_limit']) ? intval($_POST['t_limit']) : 4);

        if ($old_lang !== $new_lang && function_exists('newsai_get_default_prompt')) {
            update_option('newsai_prompt_title', newsai_get_default_prompt('title', $new_lang));
            update_option('newsai_prompt_rewrite', newsai_get_default_prompt('rewrite', $new_lang));
            update_option('newsai_prompt_seo', newsai_get_default_prompt('seo', $new_lang));
        } else {
            update_option('newsai_prompt_title', isset($_POST['p_title']) ? sanitize_textarea_field(wp_unslash($_POST['p_title'])) : '');
            update_option('newsai_prompt_rewrite', isset($_POST['p_rewrite']) ? sanitize_textarea_field(wp_unslash($_POST['p_rewrite'])) : '');
            update_option('newsai_prompt_seo', isset($_POST['p_seo']) ? sanitize_textarea_field(wp_unslash($_POST['p_seo'])) : '');
        }
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nros_timeline_html_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_nros_timeline_html_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_newsai_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_newsai_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nros_schema_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_nros_schema_%'");

        echo "<script>window.location.href = window.location.href;</script>"; 
    }

    if (isset($_POST['newsai_save_competitor']) && wp_verify_nonce($_POST['newsai_comp_nonce'], 'save_comp')) {
        update_option('newsai_competitor_rss', sanitize_textarea_field(wp_unslash($_POST['comp_rss'])));
        echo "<script>window.location.href = window.location.href;</script>"; 
    }
    $comp_rss = get_option('newsai_competitor_rss', '');

    // ==========================================
    // 🚀 FIX: SAVE / UPDATE TASK & CACHE CLEARING
    // ==========================================
    // Λειτουργία καθαρισμού της Sidebar μνήμης όταν αλλάζουν τα Tasks
    function nros_clear_task_transients() {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nros_tasks_user_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_nros_tasks_user_%'");
    }

    if (isset($_POST['newsai_add_alert'])) {
        if (!isset($_POST['newsai_alert_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['newsai_alert_nonce'])), 'newsai_add_alert_action')) { wp_die('Security check failed'); }
        $date = isset($_POST['task_date']) ? sanitize_text_field(wp_unslash($_POST['task_date'])) : '';
        if (!empty($date)) {
            $parts = explode('-', $date);
            $recurrence = isset($_POST['recurrence']) ? sanitize_text_field(wp_unslash($_POST['recurrence'])) : 'yearly';
            if ($recurrence === 'monthly') { $year = 0; $month = 0; } elseif ($recurrence === 'yearly') { $year = 0; $month = intval($parts[1]); } else { $year = intval($parts[0]); $month = intval($parts[1]); }
            
            $day = intval($parts[2]);
            $alert_id = isset($_POST['newsai_alert_id']) ? intval($_POST['newsai_alert_id']) : 0;
            
            $data = [
                'title'          => sanitize_text_field(wp_unslash($_POST['t'])),
                'day'            => $day,
                'month'          => $month,
                'year'           => $year,
                'lead_days'      => isset($_POST['ld']) ? intval($_POST['ld']) : 7,
                'author_id'      => intval($_POST['author_id']),
                'notes'          => isset($_POST['n']) ? sanitize_textarea_field(wp_unslash($_POST['n'])) : '',
                'target_keyword' => isset($_POST['tk']) ? sanitize_text_field(wp_unslash($_POST['tk'])) : '',
                'ref_link'       => isset($_POST['rl']) ? sanitize_url(wp_unslash($_POST['rl'])) : '',
                'status'         => 'pending'
            ];

            if (!in_array('target_keyword', $table_cols)) unset($data['target_keyword']);
            if (!in_array('ref_link', $table_cols)) unset($data['ref_link']);
            if (!in_array('status', $table_cols)) unset($data['status']);

            if ($alert_id > 0) {
                $wpdb->update($table, $data, ['id' => $alert_id]); echo '<div class="updated"><p>' . esc_html($i18n['msg_task_upd']) . '</p></div>';
            } else {
                $wpdb->insert($table, $data); echo '<div class="updated"><p>' . esc_html($i18n['msg_task_ok']) . '</p></div>';
            }
            
            nros_clear_task_transients(); // 🚀 FIX: Καθαρίζει τη μνήμη της Sidebar!
        }
    }
    
    if (isset($_GET['mark_done']) && isset($_GET['id'])) {
        if (in_array('status', $table_cols)) { $wpdb->update($table, ['status' => 'done'], ['id' => intval($_GET['id'])]); }
        nros_clear_task_transients(); // 🚀 FIX: Καθαρίζει τη μνήμη
    }
    
    if (isset($_GET['del'])) { 
        $wpdb->delete($table, ['id' => intval($_GET['del'])]); 
        nros_clear_task_transients(); // 🚀 FIX: Καθαρίζει τη μνήμη
    }

    if (isset($_POST['newsai_bulk_delete']) && isset($_POST['newsai_alert_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['newsai_alert_nonce'])), 'newsai_add_alert_action')) {
        if ($_POST['newsai_bulk_delete'] === 'done') { $wpdb->query("DELETE FROM {$table} WHERE status = 'done'"); } 
        elseif ($_POST['newsai_bulk_delete'] === 'all') { $wpdb->query("TRUNCATE TABLE {$table}"); }
        nros_clear_task_transients(); // 🚀 FIX: Καθαρίζει τη μνήμη
    }

    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    $edit_data = ($edit_id > 0) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $edit_id)) : null; 
    
    $def_t = $edit_data ? $edit_data->title : ''; 
    $def_n = $edit_data ? $edit_data->notes : '';
    $def_tk = ($edit_data && isset($edit_data->target_keyword)) ? $edit_data->target_keyword : ''; 
    $def_rl = ($edit_data && isset($edit_data->ref_link)) ? $edit_data->ref_link : ''; 
    $def_ld = $edit_data ? $edit_data->lead_days : 7; 
    $def_author = $edit_data ? $edit_data->author_id : 0;
    
    $def_date = ''; $def_rec = 'yearly';
    if ($edit_data) {
        $y = $edit_data->year > 0 ? $edit_data->year : gmdate('Y'); 
        $m = $edit_data->month > 0 ? sprintf('%02d', $edit_data->month) : gmdate('m'); 
        $d = sprintf('%02d', $edit_data->day);
        $def_date = "$y-$m-$d";
        if ($edit_data->year == 0 && $edit_data->month == 0) $def_rec = 'monthly'; elseif ($edit_data->year == 0 && $edit_data->month > 0) $def_rec = 'yearly'; else $def_rec = 'none';
    }

    // ==========================================
    // UI RENDER
    // ==========================================
    ?>
    <style>
        .nros-dashboard-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 24px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .nros-card { background: #fff; border: 1px solid #e2e4e7; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .nros-card h3 { margin-top: 0; font-size: 16px; color: #1d2327; border-bottom: 1px solid #f0f0f1; padding-bottom: 12px; margin-bottom: 15px; font-weight: 600; }
        
        .nros-col-8 { grid-column: span 8; }
        .nros-col-4 { grid-column: span 4; }
        @media (max-width: 1024px) { .nros-col-8, .nros-col-4 { grid-column: span 12; } }
        
        .nros-kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
        .nros-kpi { background: #fff; border: 1px solid #e2e4e7; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between; }
        .nros-kpi-label { font-size: 12px; color: #50575e; font-weight: 600; margin-bottom: 8px; display: flex; justify-content: space-between; }
        .nros-kpi-val { font-size: 32px; font-weight: 700; color: #1d2327; }
        .nros-kpi-sub { font-size: 11px; color: #888; margin-top: 5px; }
        
        .nros-status-badge { font-size: 11px; padding: 3px 8px; border-radius: 12px; font-weight: 600; }
        .nros-status-pending { background: #fff8e5; color: #926c0a; border: 1px solid #f0b849; }
        .nros-status-overdue { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .nros-status-done { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        
        .nros-pro-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.85); backdrop-filter: blur(2px); border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10; text-align: center; padding: 20px; }
        .nros-pro-badge { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 10px; box-shadow: 0 4px 6px rgba(99, 102, 241, 0.2); }
        .nros-avatar { width: 32px; height: 32px; background: #f0f6fc; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #2271b1; font-weight: bold; font-size: 12px; }
        .nros-perf-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f0f1; }
    </style>

    <div class="wrap">
       <h1>Newsroom OS <span style="font-size:12px; background:#888; color:#fff; padding:3px 8px; border-radius:4px; vertical-align: middle;">v<?php echo esc_html(NEWSAI_VERSION); ?></span></h1>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'imported'): ?>
            <div class="notice notice-success is-dismissible"><p>✅ Εισαγωγή γεγονότων επιτυχής!</p></div>
        <?php endif; ?>

        <h2 class="nav-tab-wrapper" style="margin-bottom: 24px; border-bottom: 1px solid #e2e4e7;">
            <a href="?page=newsai-settings&tab=alerts" class="nav-tab <?php echo $tab == 'alerts' ? 'nav-tab-active' : ''; ?>" style="font-size: 15px;">📊 Dashboard</a>
            <a href="?page=newsai-settings&tab=settings" class="nav-tab <?php echo $tab == 'settings' ? 'nav-tab-active' : ''; ?>" style="font-size: 15px;">⚙️ Settings & Prompts</a>
            <a href="?page=newsai-settings&tab=pro" class="nav-tab <?php echo $tab == 'pro' ? 'nav-tab-active' : ''; ?>" style="color: #6366f1; font-weight: bold; font-size: 15px;">🚀 PRO</a>
        </h2>

        <?php if ($tab == 'settings'): ?>
        
        <?php
        $p_title = get_option('newsai_prompt_title', '');
        if (empty(trim($p_title)) && function_exists('newsai_get_default_prompt')) { $p_title = newsai_get_default_prompt('title', $lang); }

        $p_rewrite = get_option('newsai_prompt_rewrite', '');
        if (empty(trim($p_rewrite)) && function_exists('newsai_get_default_prompt')) { $p_rewrite = newsai_get_default_prompt('rewrite', $lang); }

        $p_seo = get_option('newsai_prompt_seo', '');
        if (empty(trim($p_seo)) && function_exists('newsai_get_default_prompt')) { $p_seo = newsai_get_default_prompt('seo', $lang); }
        ?>

        <div class="nros-card" style="max-width: 800px;">
            <form method="post">
                <?php wp_nonce_field('newsai_save_settings_action', 'newsai_settings_nonce'); ?>
                
                <h3><?php echo esc_html($i18n['settings_title']); ?></h3>
                <table class="form-table">
                    <tr><th>Site Name</th><td><input type="text" name="sn" value="<?php echo esc_attr(get_option('nros_site_name', get_bloginfo('name'))); ?>" class="regular-text"></td></tr>
                    <tr><th><?php echo esc_html($lang == 'en' ? 'Site Niche' : 'Περιγραφή Site'); ?></th><td><input type="text" name="sd" value="<?php echo esc_attr(newsai_get_best_site_description()); ?>" class="regular-text" style="width:100%;"></td></tr>
                    <tr><th>Language</th><td><select name="lang"><option value="el" <?php selected(get_option('newsai_lang'), 'el'); ?>>Ελληνικά</option><option value="en" <?php selected(get_option('newsai_lang'), 'en'); ?>>English</option></select></td></tr>
                    <tr><th>Target Geo</th><td>
                        <select name="geo">
                            <option value="GR" <?php selected($geo_opt, 'GR'); ?>>Greece (GR)</option>
                            <option value="CY" <?php selected($geo_opt, 'CY'); ?>>Cyprus (CY)</option>
                            <option value="US" <?php selected($geo_opt, 'US'); ?>>USA (US)</option>
                            <option value="GB" <?php selected($geo_opt, 'GB'); ?>>UK (GB)</option>
                            <option value="CA" <?php selected($geo_opt, 'CA'); ?>>Canada (CA)</option>
                            <option value="AU" <?php selected($geo_opt, 'AU'); ?>>Australia (AU)</option>
                            <option value="DE" <?php selected($geo_opt, 'DE'); ?>>Germany (DE)</option>
                            <option value="FR" <?php selected($geo_opt, 'FR'); ?>>France (FR)</option>
                            <option value="IT" <?php selected($geo_opt, 'IT'); ?>>Italy (IT)</option>
                            <option value="ES" <?php selected($geo_opt, 'ES'); ?>>Spain (ES)</option>
                            <option value="NL" <?php selected($geo_opt, 'NL'); ?>>Netherlands (NL)</option>
                            <option value="BE" <?php selected($geo_opt, 'BE'); ?>>Belgium (BE)</option>
                            <option value="CH" <?php selected($geo_opt, 'CH'); ?>>Switzerland (CH)</option>
                            <option value="SE" <?php selected($geo_opt, 'SE'); ?>>Sweden (SE)</option>
                            <option value="PT" <?php selected($geo_opt, 'PT'); ?>>Portugal (PT)</option>
                            <option value="BR" <?php selected($geo_opt, 'BR'); ?>>Brazil (BR)</option>
                            <option value="MX" <?php selected($geo_opt, 'MX'); ?>>Mexico (MX)</option>
                            <option value="RU" <?php selected($geo_opt, 'RU'); ?>>Russia (RU)</option>
                            <option value="JP" <?php selected($geo_opt, 'JP'); ?>>Japan (JP)</option>
                            <option value="IN" <?php selected($geo_opt, 'IN'); ?>>India (IN)</option>
                            <option value="ZA" <?php selected($geo_opt, 'ZA'); ?>>South Africa (ZA)</option>
                        </select>
                    </td></tr>
                </table>

                <hr style="margin: 20px 0; border-top: 1px solid #f0f0f1;">

                <h3><?php echo esc_html($i18n['schema_title']); ?></h3>
                <table class="form-table">
                    <tr><th>Schema Engine</th><td>
                        <select name="schema_type">
                            <option value="newsarticle" <?php selected(get_option('nros_schema_type', 'newsarticle'), 'newsarticle'); ?>>📰 NewsArticle (Publishers)</option>
                            <option value="article" <?php selected(get_option('nros_schema_type', 'newsarticle'), 'article'); ?>>🏢 Article (Blogs)</option>
                            <option value="none" <?php selected(get_option('nros_schema_type', 'newsarticle'), 'none'); ?>>🚫 Disabled</option>
                        </select>
                    </td></tr>
                    
                    <tr>
                        <th><?php echo esc_html($i18n['city']); ?></th>
                        <td>
                            <input type="text" name="primary_city" value="<?php echo esc_attr(get_option('nros_primary_city', '')); ?>" class="regular-text">
                            <p class="description"><?php echo esc_html($i18n['city_desc']); ?></p>
                        </td>
                    </tr>

                    <tr><th>Organization Type</th><td>
                        <select name="org_type">
                            <option value="NewsMediaOrganization" <?php selected(get_option('nros_org_type', 'NewsMediaOrganization'), 'NewsMediaOrganization'); ?>>📡 NewsMediaOrganization</option>
                            <option value="Organization" <?php selected(get_option('nros_org_type', 'NewsMediaOrganization'), 'Organization'); ?>>🏢 Organization</option>
                        </select>
                    </td></tr>
                    <tr><th>Social Media Links</th><td>
                        <textarea name="social_links" rows="3" style="width:100%;"><?php echo esc_textarea(get_option('nros_social_links', '')); ?></textarea>
                    </td></tr>
                    <tr><th><label>Custom Feed Page IDs</label></th><td>
                        <input type="text" name="feed_pages" value="<?php echo esc_attr(get_option('newsai_custom_feed_pages', '')); ?>" class="regular-text" style="width:100%;">
                        <p class="description">Comma-separated IDs for Page Builders.</p>
                    </td></tr>
                    <tr><th><label>Speakable CSS Classes</label></th><td>
                        <input type="text" name="speakable" value="<?php echo esc_attr(get_option('newsai_speakable_selectors', '')); ?>" class="regular-text" style="width:100%;">
                        <p class="description">e.g. .headline, .summary</p>
                    </td></tr>
                </table>

                <hr style="margin: 20px 0; border-top: 1px solid #f0f0f1;">

                <h3><?php echo esc_html($i18n['timeline_title']); ?></h3>
                <table class="form-table">
                    <tr><th>Injection Mode</th><td>
                        <select name="t_mode">
                            <option value="auto" <?php selected(get_option('newsai_timeline_mode', 'auto'), 'auto'); ?>>⚡ Auto-Pilot</option>
                            <option value="manual" <?php selected(get_option('newsai_timeline_mode', 'auto'), 'manual'); ?>>✋ Manual Only</option>
                        </select>
                    </td></tr>
                    <tr><th>Αριθμός Άρθρων στο Χρονικό</th><td><input type="number" name="t_limit" value="<?php echo esc_attr(get_option('newsai_timeline_limit', 4)); ?>" class="small-text" min="1" max="10"></td></tr>
                    <tr><th>Search Past Days</th><td><input type="number" name="t_days" value="<?php echo esc_attr(get_option('newsai_timeline_days', 30)); ?>" class="small-text"></td></tr>
                    <tr><th>Auto-Insert After Paragraph</th><td><input type="number" name="t_para" value="<?php echo esc_attr(get_option('newsai_timeline_para', -1)); ?>" class="small-text"></td></tr>
                    <tr><th>Timeline Heading</th><td>
                        <input type="text" name="t_heading" value="<?php echo esc_attr(get_option('newsai_timeline_heading', '⏳ Το Χρονικό της Είδησης: {tag}')); ?>" class="regular-text" style="width: 100%;">
                        <p class="description">Χρησιμοποιήστε το <code>{tag}</code> για να εμφανίζεται δυναμικά το όνομα του θέματος.</p>
                    </td></tr>
                </table>

                <hr style="margin: 20px 0; border-top: 1px solid #f0f0f1;">

                <h3><?php echo esc_html($i18n['prompts_title']); ?></h3>
                <table class="form-table">
                    <tr><th>Title Prompt</th><td><textarea name="p_title" rows="4" style="width:100%; font-family:monospace;"><?php echo esc_textarea($p_title); ?></textarea></td></tr>
                    <tr><th>Rewrite Prompt</th><td><textarea name="p_rewrite" rows="5" style="width:100%; font-family:monospace;"><?php echo esc_textarea($p_rewrite); ?></textarea></td></tr>
                    <tr><th>SEO Tags & Meta</th><td><textarea name="p_seo" rows="4" style="width:100%; font-family:monospace;"><?php echo esc_textarea($p_seo); ?></textarea></td></tr>
                </table>
                <p class="submit"><input type="submit" name="newsai_save_settings" class="button button-primary button-large" value="<?php echo esc_attr($i18n['btn_save']); ?>"></p>
            </form>
        </div>

       <?php elseif ($tab == 'alerts'): ?>
        
        <?php 
            $has_status = in_array('status', $table_cols);
            
            $total_tasks = $wpdb->get_var("SELECT COUNT(*) FROM {$table}" . ($has_status ? " WHERE status != 'done'" : ""));
            
            $now_time = strtotime(gmdate('Y-m-d'));
            $overdue_count = 0;
            $due_today_count = 0;
            $active_rows = $wpdb->get_results("SELECT * FROM {$table} WHERE status != 'done'");
            if ($active_rows) {
                foreach ($active_rows as $r) {
                    $t_year = ($r->year > 0) ? $r->year : (int)gmdate('Y');
                    $t_month = ($r->month > 0) ? $r->month : (int)gmdate('m');
                    $task_time = strtotime(sprintf('%04d-%02d-%02d', $t_year, $t_month, $r->day));
                    if ($r->year == 0 && $r->month > 0) { if (($now_time - $task_time) > (15 * 86400)) { $t_year++; $task_time = strtotime(sprintf('%04d-%02d-%02d', $t_year, $t_month, $r->day)); } } 
                    elseif ($r->year == 0 && $r->month == 0) { if (($now_time - $task_time) > (5 * 86400)) { $t_month++; if ($t_month > 12) { $t_month = 1; $t_year++; } $task_time = strtotime(sprintf('%04d-%02d-%02d', $t_year, $t_month, $r->day)); } }
                    
                    $diff_days = ($task_time - $now_time) / 86400;
                    if ($diff_days < 0) $overdue_count++;
                    elseif ($diff_days == 0) $due_today_count++;
                }
            }

            $posts_this_week = (int) $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND post_date > DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $posts_last_week = (int) $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND post_date > DATE_SUB(NOW(), INTERVAL 14 DAY) AND post_date <= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $week_diff = $posts_this_week - $posts_last_week;
            $week_trend = ($posts_last_week > 0) ? round(($week_diff / $posts_last_week) * 100) : ($posts_this_week > 0 ? 100 : 0);
            $week_trend_color = $week_trend >= 0 ? '#10b981' : '#dc2626';
            $week_trend_arrow = $week_trend >= 0 ? '↑' : '↓';

            $missing_seo = function_exists('newsroom_os_get_missing_seo_count') ? newsroom_os_get_missing_seo_count() : 0;
            $total_posts_30d = (int) $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND post_date > DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $seo_health = 100;
            if ($total_posts_30d > 0) {
                $seo_health = max(0, round((($total_posts_30d - $missing_seo) / $total_posts_30d) * 100));
            }
            $health_color = $seo_health >= 80 ? '#10b981' : ($seo_health >= 60 ? '#f59e0b' : '#dc2626');
            
            $google_update_info = function_exists('newsroom_os_check_google_updates') ? newsroom_os_check_google_updates() : ['state' => 'clear', 'message' => 'Status API Clear'];
        ?>

       <div style="margin-bottom: 24px;">
            <?php 
            $is_el = (strpos(get_bloginfo('language'), 'el') !== false) || ($lang == 'el'); 
            if ($google_update_info['state'] === 'active'): 
            ?>
                <div style="padding: 15px 20px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; box-shadow: 0 4px 6px rgba(239, 68, 68, 0.1);">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span style="font-size: 24px;">🚨</span>
                        <div>
                            <strong style="font-size: 15px;">Ενεργό: <?php echo esc_html($google_update_info['message']); ?></strong><br>
                            <span style="font-size: 13px; font-weight: normal;"><?php echo $is_el ? 'Αναμένεται υψηλή μεταβλητότητα στα αποτελέσματα. Αποφύγετε τις μεγάλες αλλαγές SEO.' : 'Expect high volatility in SERPs. Avoid major SEO changes.'; ?></span>
                        </div>
                    </div>
                    <?php if(!empty($google_update_info['link'])): ?>
                        <a href="<?php echo esc_url($google_update_info['link']); ?>" target="_blank" style="display:inline-block; padding:6px 12px; font-size:13px; font-weight:600; border-radius:4px; text-decoration:none; border:1px solid #fca5a5; color:#b91c1c; background:transparent;"><?php echo $is_el ? 'Δείτε Περισσότερα' : 'View Details'; ?></a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="padding: 15px 20px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.05);">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span style="font-size: 24px;">✅</span>
                        <div>
                            <strong style="font-size: 15px;"><?php echo $is_el ? 'Καμία Ενεργή Ενημέρωση Αλγορίθμου' : 'No Active Core Updates'; ?></strong><br>
                            <span style="font-size: 13px; font-weight: normal;"><?php echo $is_el ? 'Τα συστήματα αναζήτησης της Google λειτουργούν κανονικά.' : 'Google search systems are operating normally.'; ?></span>
                        </div>
                    </div>
                    <a href="https://status.search.google.com/" target="_blank" style="display:inline-block; padding:6px 12px; font-size:13px; font-weight:600; border-radius:4px; text-decoration:none; border:1px solid #6ee7b7; color:#047857; background:transparent;">Google Status</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="nros-kpi-row">
            <div class="nros-kpi">
                <div class="nros-kpi-label"><span>Active Tasks</span> <span>⏱️</span></div>
                <div class="nros-kpi-val"><?php echo intval($total_tasks); ?></div>
                <div class="nros-kpi-sub" style="color: <?php echo ($overdue_count > 0) ? '#dc2626' : '#646970'; ?>;">
                    <?php echo ($lang == 'el') ? "{$overdue_count} ληγμένα, {$due_today_count} για σήμερα" : "{$overdue_count} overdue, {$due_today_count} due today"; ?>
                </div>
            </div>
            <div class="nros-kpi">
                <div class="nros-kpi-label"><span>Articles This Week</span> <span>📄</span></div>
                <div class="nros-kpi-val"><?php echo intval($posts_this_week); ?></div>
                <div class="nros-kpi-sub" style="color: <?php echo $week_trend_color; ?>;"><?php echo $week_trend_arrow . ' ' . abs($week_trend); ?>% vs last week</div>
            </div>
            <a href="<?php echo admin_url('edit.php?newsai_filter=missing_seo'); ?>" style="text-decoration:none;" class="nros-kpi">
                <div class="nros-kpi-label"><span>SEO Issues</span> <span>⚠️</span></div>
                <div class="nros-kpi-val" style="color: <?php echo ($missing_seo > 0) ? '#dc2626' : '#1d2327'; ?>;">
                    <?php echo intval($missing_seo); ?>
                </div>
                <div class="nros-kpi-sub" style="color: <?php echo ($missing_seo > 0) ? '#dc2626' : '#646970'; ?>;">
                    <?php echo ($missing_seo > 0) ? (($lang == 'el') ? 'Απαιτείται διόρθωση' : 'Needs immediate action') : (($lang == 'el') ? 'Κανένα πρόβλημα' : 'All clear'); ?>
                </div>
            </a>
            <div class="nros-kpi">
                <div class="nros-kpi-label"><span>Site SEO Health</span> <span>🏆</span></div>
                <div class="nros-kpi-val"><?php echo intval($seo_health); ?><span style="font-size: 16px; color:#888;">/100</span></div>
                <div class="nros-kpi-sub" style="color: <?php echo $health_color; ?>;">
                    <?php echo ($lang == 'el') ? 'Βάσει των τελευταίων 30 ημερών' : 'Based on last 30 days'; ?>
                </div>
            </div>
        </div>

        <div class="nros-dashboard-grid">
            
            <div class="nros-col-8">
                
                <div class="nros-card" style="margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e4e7; padding-bottom: 12px; margin-bottom: 15px;">
                        <h3 id="nros-task-form-title" style="border: none; margin: 0; padding: 0;">
                            <?php echo ($edit_id > 0) ? esc_html($i18n['edit_alert']) : esc_html($i18n['add_alert']); ?>
                        </h3>
                        
                        <?php if ($edit_id == 0): ?>
                        <form method="post" style="margin: 0;">
                            <?php wp_nonce_field('newsai_bulk_import', 'newsai_import_nonce'); ?>
                            <button type="submit" name="newsai_bulk_import_btn" class="button button-small" style="display: flex; align-items: center; gap: 5px;" onclick="return confirm('Import default holidays for your country?');">
                                📅 <?php echo ($lang == 'el') ? 'Εισαγωγή Εορτών' : 'Import Holidays'; ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    
                    <form method="post" action="?page=newsai-settings&tab=alerts">
                        <?php wp_nonce_field('newsai_add_alert_action', 'newsai_alert_nonce'); ?>
                        <input type="hidden" name="newsai_alert_id" value="<?php echo esc_attr($edit_id); ?>">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            
                            <div style="grid-column: span 2;">
                                <label style="font-weight:600; font-size:12px; display:block; margin-bottom:4px; color:#50575e;"><?php echo esc_html($i18n['topic']); ?> (Required)</label>
                                <input name="t" id="nros-task-input" value="<?php echo esc_attr($def_t); ?>" required class="regular-text" style="width:100%; border-radius:6px;">
                            </div>
                            
                            <div>
                                <label style="font-weight:600; font-size:12px; display:block; margin-bottom:4px; color:#50575e;">Target Keyword (SEO)</label>
                                <input name="tk" value="<?php echo esc_attr($def_tk); ?>" placeholder="e.g. OpenAI Sora" class="regular-text" style="width:100%; border-radius:6px;">
                            </div>
                            <div>
                                <label style="font-weight:600; font-size:12px; display:block; margin-bottom:4px; color:#50575e;">Competitor / Reference Link</label>
                                <input type="url" name="rl" value="<?php echo esc_attr($def_rl); ?>" placeholder="https://..." class="regular-text" style="width:100%; border-radius:6px;">
                            </div>

                            <div style="grid-column: span 2;">
                                <label style="font-weight:600; font-size:12px; display:block; margin-bottom:4px; color:#50575e;"><?php echo esc_html($i18n['notes']); ?></label>
                                <textarea name="n" rows="2" style="width:100%; border-radius:6px;"><?php echo esc_textarea($def_n); ?></textarea>
                            </div>
                            
                            <div>
                                <label style="font-weight:600; font-size:12px; display:block; margin-bottom:4px; color:#50575e;"><?php echo esc_html($i18n['date']); ?></label>
                                <input type="date" name="task_date" value="<?php echo esc_attr($def_date); ?>" required style="width:100%; border-radius:6px;">
                            </div>
                            <div>
                                <label style="font-weight:600; font-size:12px; display:block; margin-bottom:4px; color:#50575e;"><?php echo esc_html($i18n['assign_to']); ?></label>
                                <?php wp_dropdown_users([
                                    'show_option_all' => $i18n['all_users'], 
                                    'name' => 'author_id', 
                                    'selected' => $def_author,
                                    'who' => 'authors',
                                    'number' => 100
                                ]); ?>
                            </div>
                            
                            <div>
                                <label style="font-weight:600; font-size:12px; display:block; margin-bottom:4px; color:#50575e;"><?php echo esc_html($i18n['lead_days']); ?></label>
                                <input type="number" name="ld" value="<?php echo esc_attr($def_ld); ?>" min="1" max="90" style="width:100%; border-radius:6px;">
                            </div>
                            <div>
                                <label style="font-weight:600; font-size:12px; display:block; margin-bottom:4px; color:#50575e;"><?php echo esc_html($i18n['recurrence']); ?></label>
                                <select name="recurrence" style="width:100%; border-radius:6px;">
                                    <option value="none" <?php selected($def_rec, 'none'); ?>><?php echo esc_html($i18n['once']); ?></option>
                                    <option value="yearly" <?php selected($def_rec, 'yearly'); ?>><?php echo esc_html($i18n['yearly']); ?></option>
                                    <option value="monthly" <?php selected($def_rec, 'monthly'); ?>><?php echo esc_html($i18n['monthly']); ?></option>
                                </select>
                            </div>

                            <div style="grid-column: span 2; margin-top: 10px;">
                                <input type="submit" name="newsai_add_alert" class="button button-primary" value="<?php echo ($edit_id > 0) ? esc_attr($i18n['btn_update_task']) : esc_attr($i18n['btn_add_task']); ?>">
                                <?php if ($edit_id > 0): ?>
                                    <a href="?page=newsai-settings&tab=alerts" class="button"><?php echo esc_html($i18n['btn_cancel']); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="nros-card" style="margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e4e7; padding-bottom: 12px; margin-bottom: 15px;">
                        <h3 style="margin:0; border:none; padding:0;">📋 Tasks & Workflow</h3>
                        
                        <form method="post" style="margin:0; display:flex; gap:5px;">
                            <?php wp_nonce_field('newsai_add_alert_action', 'newsai_alert_nonce'); ?>
                            <button type="submit" name="newsai_bulk_delete" value="done" class="button button-small" onclick="return confirm('<?php echo ($lang == 'el') ? 'Διαγραφή όλων των ολοκληρωμένων;' : 'Delete all completed tasks?'; ?>');">🗑️ <?php echo ($lang == 'el') ? 'Καθαρισμός Ολοκληρωμένων' : 'Clear Completed'; ?></button>
                            <button type="submit" name="newsai_bulk_delete" value="all" class="button button-small" style="color:#d63638; border-color:#d63638;" onclick="return confirm('<?php echo ($lang == 'el') ? 'ΠΡΟΣΟΧΗ: Μαζική διαγραφή ΟΛΩΝ των tasks;' : 'WARNING: Delete ALL tasks?'; ?>');">🚨 <?php echo ($lang == 'el') ? 'Διαγραφή Όλων' : 'Delete All'; ?></button>
                        </form>
                    </div>

                    <table class="wp-list-table widefat striped" style="border:none; box-shadow:none;">
                        <thead><tr><th>Task</th><th>Date</th><th>Writer</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php 
                            $order_clause = $has_status ? "ORDER BY status DESC, month, day" : "ORDER BY month, day";
                            // 🚀 ENTERPRISE FIX: Ασφαλές LIMIT 100 για αποφυγή PHP Memory Exhaustion
$rows = $wpdb->get_results("SELECT * FROM {$table} {$order_clause} LIMIT 100");
                            $now_time = strtotime(gmdate('Y-m-d'));

                            if($rows): foreach($rows as $r): 
                                $user = get_userdata($r->author_id);
                                $assignee = $user ? $user->display_name : $i18n['all_users'];
                                
                                $t_year = ($r->year > 0) ? $r->year : (int)gmdate('Y');
                                $t_month = ($r->month > 0) ? $r->month : (int)gmdate('m');
                                $task_time = strtotime(sprintf('%04d-%02d-%02d', $t_year, $t_month, $r->day));
                                
                                if ($r->year == 0 && $r->month > 0) { 
                                    if (($now_time - $task_time) > (15 * 86400)) { 
                                        $t_year++; 
                                        $task_time = strtotime(sprintf('%04d-%02d-%02d', $t_year, $t_month, $r->day));
                                    }
                                } elseif ($r->year == 0 && $r->month == 0) { 
                                    if (($now_time - $task_time) > (5 * 86400)) { 
                                        $t_month++; 
                                        if ($t_month > 12) { $t_month = 1; $t_year++; }
                                        $task_time = strtotime(sprintf('%04d-%02d-%02d', $t_year, $t_month, $r->day));
                                    }
                                }
                                
                                $diff_days = ($task_time - $now_time) / 86400; 

                                $status_val = $has_status ? $r->status : 'pending';
                                $status_ui = '';
                                if ($status_val === 'done') {
                                    $status_ui = '<span class="nros-status-badge nros-status-done">Done</span>';
                                } else {
                                    if ($diff_days < 0) { $status_ui = '<span class="nros-status-badge nros-status-overdue">OVERDUE</span>'; }
                                    elseif ($diff_days <= $r->lead_days) { $status_ui = '<span class="nros-status-badge nros-status-pending">Due in '.$diff_days.'d</span>'; }
                                    else { $status_ui = '<span style="font-size:11px; color:#888;">Scheduled</span>'; }
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($r->title); ?></strong>
                                        <?php if(isset($r->target_keyword) && !empty($r->target_keyword)) echo '<br><span style="font-size:10px; color:#2271b1;">🔑 ' . esc_html($r->target_keyword) . '</span>'; ?>
                                    </td>
                                    <td><?php echo sprintf('%02d/%02d', $r->day, $r->month); ?></td>
                                    <td><?php echo esc_html($assignee); ?></td>
                                    <td><?php echo $status_ui; ?></td>
                                    <td>
                                        <a href="?page=newsai-settings&tab=alerts&edit=<?php echo (int)$r->id; ?>" class="button button-small">Edit</a>
                                        <?php if ($status_val !== 'done' && $has_status): ?>
                                            <a href="?page=newsai-settings&tab=alerts&mark_done=1&id=<?php echo (int)$r->id; ?>" class="button button-small" style="color:#00a32a; border-color:#00a32a;" title="Mark as Done">✓</a>
                                        <?php endif; ?>
                                        <a href="?page=newsai-settings&tab=alerts&del=<?php echo (int)$r->id; ?>" class="button button-small" style="color:#d63638;" title="Delete">X</a>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="5" style="text-align:center; padding: 20px; color:#646970;">No tasks found. Create one above!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="nros-card" style="margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e4e7; padding-bottom: 12px; margin-bottom: 15px;">
                        <h3 style="margin:0; border:none; padding:0;">📰 <?php echo ($lang == 'el') ? 'Τελευταία Άρθρα (Live)' : 'Latest Published Articles'; ?></h3>
                        <a href="<?php echo admin_url('edit.php'); ?>" class="button button-small"><?php echo ($lang == 'el') ? 'Προβολή Όλων' : 'View All'; ?></a>
                    </div>
                    <ul style="margin:0; padding:0; list-style:none;">
                        <?php
                        $latest_posts = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 5]);
                        if ($latest_posts) {
                            foreach ($latest_posts as $lp) {
                                $author_name = get_the_author_meta('display_name', $lp->post_author);
                                $time_diff = human_time_diff(get_post_time('U', false, $lp->ID), current_time('timestamp')) . ' ' . (($lang == 'el') ? 'πριν' : 'ago');
                                echo '<li style="margin-bottom:12px; border-bottom:1px solid #f0f0f1; padding-bottom:10px; display:flex; justify-content:space-between; align-items:center;">';
                                echo '<div style="padding-right: 15px;">';
                                echo '<a href="'.get_edit_post_link($lp->ID).'" style="font-weight:600; color:#1d2327; text-decoration:none; font-size:13px; display:block; margin-bottom:4px; line-height: 1.3;">'.esc_html($lp->post_title).'</a>';
                                echo '<span style="font-size:11px; color:#646970;">👤 '.esc_html($author_name).' &nbsp;|&nbsp; 🕒 '.$time_diff.'</span>';
                                echo '</div>';
                                echo '<a href="'.get_permalink($lp->ID).'" target="_blank" class="button button-small" style="flex-shrink: 0;">View</a>';
                                echo '</li>';
                            }
                        } else {
                            echo '<li style="font-size:12px; color:#646970;">No articles found.</li>';
                        }
                        ?>
                    </ul>
                </div>

            </div>

            <div class="nros-col-4">
                
                <div class="nros-card" style="margin-bottom: 24px; position: relative; overflow: hidden; padding: 0;">
                    <div style="padding: 20px;">
                        <h3 style="margin-bottom: 5px; border:none; padding:0;">Writer Performance</h3>
                        <p style="font-size: 12px; color: #646970; margin-top: 0; margin-bottom: 20px;">Average quality scores (E-E-A-T)</p>
                        
                        <div class="nros-perf-row">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div class="nros-avatar">JD</div>
                                <span style="font-size:13px; font-weight:600;">John Doe</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <strong style="font-size:15px;">88</strong>
                                <span style="color:#10b981; font-size:12px;">↗</span>
                            </div>
                        </div>
                        <div class="nros-perf-row">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div class="nros-avatar" style="background:#fef2f2; color:#dc2626;">MS</div>
                                <span style="font-size:13px; font-weight:600;">Maria Smith</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <strong style="font-size:15px;">82</strong>
                                <span style="color:#10b981; font-size:12px;">↗</span>
                            </div>
                        </div>
                        <div class="nros-perf-row" style="border:none;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div class="nros-avatar" style="background:#fcfcfc; color:#646970;">AC</div>
                                <span style="font-size:13px; font-weight:600;">Alex Chen</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <strong style="font-size:15px;">75</strong>
                                <span style="color:#dc2626; font-size:12px;">↘</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="nros-pro-overlay">
                        <div class="nros-pro-badge">🔒 PRO FEATURE</div>
                        <h4 style="margin:0 0 5px 0; font-size:14px;">Editorial Analytics</h4>
                        <p style="font-size:12px; color:#50575e; margin:0;">Track author E-E-A-T scores, SEO performance, and output metrics automatically.</p>
                    </div>
                </div>

                <div class="nros-card postbox" style="margin-bottom: 24px; padding: 0;">
                    <div style="padding: 15px 20px 0 20px;">
                        <h3 style="font-size: 14px; border:none; padding:0; margin-bottom:10px; display:flex; align-items:center; gap:8px;">
                            🔥 Trending & News
                            <span style="font-size: 9px; background: #fee2e2; color: #dc2626; padding: 2px 6px; border-radius: 4px; font-weight: bold;">LIVE</span>
                        </h3>
                        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid #f0f0f1;">
                            <div style="display:flex; gap:20px;">
                                <span class="newsai-tab active" data-target="newsai-trends" style="font-size:12px; font-weight:600; cursor:pointer; color:#2271b1; border-bottom:2px solid #2271b1; padding-bottom: 8px; margin-bottom: -1px;">TRENDS</span>
                                <span class="newsai-tab" data-target="newsai-news" style="font-size:12px; font-weight:600; cursor:pointer; color:#646970; padding-bottom: 8px; margin-bottom: -1px;">NEWS</span>
                            </div>
                            <select id="newsai-trends-geo" style="font-size:11px; padding: 2px 24px 2px 8px; min-height:26px; border-radius:4px; margin-bottom: 5px;">
    <option value="GR" <?php selected($geo_opt, 'GR'); ?>>Greece (GR)</option>
    <option value="CY" <?php selected($geo_opt, 'CY'); ?>>Cyprus (CY)</option>
    <option value="US" <?php selected($geo_opt, 'US'); ?>>USA (US)</option>
    <option value="GB" <?php selected($geo_opt, 'GB'); ?>>UK (GB)</option>
    <option value="CA" <?php selected($geo_opt, 'CA'); ?>>Canada (CA)</option>
    <option value="AU" <?php selected($geo_opt, 'AU'); ?>>Australia (AU)</option>
    <option value="DE" <?php selected($geo_opt, 'DE'); ?>>Germany (DE)</option>
    <option value="FR" <?php selected($geo_opt, 'FR'); ?>>France (FR)</option>
    <option value="IT" <?php selected($geo_opt, 'IT'); ?>>Italy (IT)</option>
    <option value="ES" <?php selected($geo_opt, 'ES'); ?>>Spain (ES)</option>
    <option value="NL" <?php selected($geo_opt, 'NL'); ?>>Netherlands (NL)</option>
    <option value="BE" <?php selected($geo_opt, 'BE'); ?>>Belgium (BE)</option>
    <option value="CH" <?php selected($geo_opt, 'CH'); ?>>Switzerland (CH)</option>
    <option value="SE" <?php selected($geo_opt, 'SE'); ?>>Sweden (SE)</option>
    <option value="PT" <?php selected($geo_opt, 'PT'); ?>>Portugal (PT)</option>
    <option value="BR" <?php selected($geo_opt, 'BR'); ?>>Brazil (BR)</option>
    <option value="MX" <?php selected($geo_opt, 'MX'); ?>>Mexico (MX)</option>
    <option value="RU" <?php selected($geo_opt, 'RU'); ?>>Russia (RU)</option>
    <option value="JP" <?php selected($geo_opt, 'JP'); ?>>Japan (JP)</option>
    <option value="IN" <?php selected($geo_opt, 'IN'); ?>>India (IN)</option>
    <option value="ZA" <?php selected($geo_opt, 'ZA'); ?>>South Africa (ZA)</option>
</select>
                        </div>
                    </div>
                    <div id="newsai-trends-list" class="newsai-tab-content" style="max-height:350px; overflow-y:auto; padding: 15px 20px;">Loading...</div>
                    <div id="newsai-news-list" class="newsai-tab-content" style="display:none; max-height:350px; overflow-y:auto; padding: 15px 20px;">Loading...</div>
                </div>

                <div class="nros-card" style="padding: 20px;">
                    <h3 style="font-size: 14px; margin-bottom:5px; border:none; padding:0;">🕵️ Competitor Watch</h3>
                    <p style="font-size:12px; color:#646970; margin-top:0; margin-bottom:15px;">Latest articles from tracked competitors</p>
                    <form method="post" style="margin-bottom:15px;">
                        <?php wp_nonce_field('save_comp', 'newsai_comp_nonce'); ?>
                        <textarea name="comp_rss" placeholder="RSS URLs (1 per line)" style="width:100%; font-size:12px; min-height:60px; border-radius:6px;"><?php echo esc_textarea($comp_rss); ?></textarea>
                        <input type="submit" name="newsai_save_competitor" class="button button-small" value="Save Feeds" style="margin-top:8px;">
                    </form>
                    
                    <div style="max-height:280px; overflow-y:auto;">
                        <ul style="margin:0; padding:0; list-style:none;">
                        <?php
                        $comp_rss_array = array_filter(array_map('trim', explode("\n", $comp_rss)));
                        if (!empty($comp_rss_array)) {
                            add_filter('wp_feed_cache_transient_lifetime', function(){ return 120; });
                            
                            $nros_timeout = function() { return 3; };
                            add_filter( 'http_request_timeout', $nros_timeout, 999 );
                            
                            $comp_feed = fetch_feed($comp_rss_array);
                            
                            remove_filter( 'http_request_timeout', $nros_timeout, 999 );
                            remove_all_filters('wp_feed_cache_transient_lifetime');

                            if (!is_wp_error($comp_feed)) {
                                $maxitems = $comp_feed->get_item_quantity(10);
                                $rss_items = $comp_feed->get_items(0, $maxitems);
                                if (empty($rss_items)) {
                                    echo '<li style="color:#888; text-align:center; font-size:12px;">No articles found.</li>';
                                } else {
                                    foreach ($rss_items as $item) {
                                        $safe_title = esc_attr($item->get_title()); 
                                        $feed = $item->get_feed();
                                        $raw_site_name = $feed ? $feed->get_title() : '';
                                        if (empty($raw_site_name)) {
                                            $parsed_url = parse_url($item->get_permalink());
                                            $raw_site_name = isset($parsed_url['host']) ? str_replace('www.', '', $parsed_url['host']) : 'News';
                                        }
                                        $site_name = mb_strlen($raw_site_name) > 18 ? mb_substr($raw_site_name, 0, 15) . '...' : $raw_site_name;
                                        
                                        $hue = abs(crc32($raw_site_name) % 360);
                                        $color = "hsl({$hue}, 70%, 35%)";
                                        $bg = "hsl({$hue}, 70%, 95%)";

                                        echo '<li style="margin-bottom:15px; border-bottom:1px solid #f0f0f1; padding-bottom:12px;">';
                                        echo '<div style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">';
                                        echo '<span style="font-size:10px; font-weight:700; text-transform:uppercase; padding:3px 8px; border-radius:12px; background:' . $bg . '; color:' . $color . '; border:1px solid ' . $color . ';">' . esc_html($site_name) . '</span>';
                                        echo '<span style="font-size:11px; color:#888;">' . esc_html($item->get_date('H:i')) . '</span>';
                                        echo '</div>';
                                        echo '<div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">';
                                        echo '<a href="' . esc_url($item->get_permalink()) . '" target="_blank" style="text-decoration:none; color:#1d2327; font-weight:500; font-size:13px; display:block; line-height:1.4;">' . esc_html($item->get_title()) . '</a>';
                                        echo '<button type="button" class="button button-small nros-copy-task-btn" data-title="' . $safe_title . '" title="Assign Topic" style="padding:0; min-width:32px; height:32px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">➕</button>';
                                        echo '</div></li>';
                                    }
                                }
                            } else { echo '<li style="color:#d63638; text-align:center; font-size:12px;">Invalid RSS feed.</li>'; }
                        } else { 
                            echo '<div style="text-align:center; padding: 20px; color:#646970; background:#f8f9fa; border-radius:6px; font-size:12px;">Add competitor RSS URLs to track content gaps here.</div>'; 
                        }
                        ?>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.addEventListener('click', function(e) {
                const btn = e.target.closest('.nros-copy-task-btn');
                if (btn) {
                    e.preventDefault();
                    const taskInput = document.getElementById('nros-task-input');
                    const formTitle = document.getElementById('nros-task-form-title');
                    if(taskInput) {
                        taskInput.value = btn.getAttribute('data-title');
                        if(formTitle) { formTitle.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                        taskInput.focus();
                        taskInput.style.boxShadow = '0 0 0 3px rgba(34, 113, 177, 0.4)';
                        setTimeout(() => { taskInput.style.boxShadow = 'none'; }, 1200);
                    }
                }
            });
        });
        </script>

        <?php elseif ($tab == 'pro'): ?>
        <div class="nros-card" style="max-width: 800px; margin-top: 24px; padding: 0; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; padding: 50px 40px; text-align: center;">
                <h2 style="color: white; font-size: 32px; margin-top: 0; font-weight: 700; letter-spacing: -0.5px;">🚀 Upgrade to PRO</h2>
                <p style="font-size: 16px; opacity: 0.9; max-width: 500px; margin: 0 auto;">Unlock the full potential of your newsroom with AI-driven analytics, multi-author tracking, and advanced content gap detection.</p>
            </div>
            <div style="padding: 40px; background: #fff; text-align: center;">
                <button class="button button-primary button-large" disabled style="opacity:0.5; padding: 10px 30px; font-size: 16px; height: auto;">Coming Soon</button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

function newsai_get_best_site_description() {
    $desc = get_bloginfo('description');
    if (class_exists('WPSEO_Options')) { $yoast = WPSEO_Options::get('og_frontpage_desc', ''); if ($yoast) $desc = $yoast; }
    return $desc;
}

function newsai_get_default_prompt($type, $lang) {
    if ($lang == 'el') {
        if ($type == 'title') return "Είσαι SEO expert. Γράψε 5 εναλλακτικούς click-worthy τίτλους (κάτω από 60 χαρακτήρες) για το εξής άρθρο:\nΤίτλος: {title}\nΠεριεχόμενο: {content}\n\nΣτόχος: Google Discover.";
        if ($type == 'rewrite') return "Ως έμπειρος δημοσιογράφος για το site {site} ({site_desc}), διόρθωσε το παρακάτω κείμενο και πρόσθεσε H2 υπότιτλους. Στο τέλος πρόσθεσε 3 Ερωτήσεις/Απαντήσεις (FAQ):\n{content}";
        if ($type == 'seo') return "Δώσε μου 5 SEO keywords, 1 Meta Description (max 150 chars) και 3 ιδανικά Tags για το άρθρο:\nΤίτλος: {title}\nΚείμενο: {content}";
    } else {
        if ($type == 'title') return "Act as an SEO expert. Write 5 click-worthy alternative titles (under 60 chars) for this article:\nTitle: {title}\nContent: {content}\n\nGoal: Google Discover.";
        if ($type == 'rewrite') return "As a senior journalist for {site} ({site_desc}), proofread this text, add H2 subheadings, and append 3 FAQs at the end:\n{content}";
        if ($type == 'seo') return "Provide 5 SEO keywords, 1 Meta Description (max 150 chars), and 3 perfect Tags for this article:\nTitle: {title}\nContent: {content}";
    }
    return "";
}