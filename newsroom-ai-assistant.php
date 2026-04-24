<?php
/**
 * Plugin Name: Newsroom OS – Editorial Control (AI)
 * Plugin URI: https://wordpress.org/plugins/newsroom-ai-assistant/
 * Description: ⚡ Built & tested on real high-traffic news websites. The First Newsroom OS for WordPress. Discover trends, assign stories, optimize for SEO, and publish faster.
 * Version: 1.4.4
 * Author: Kostas Karapapas
 * Text Domain: newsroom-ai-assistant
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define('NEWSAI_VERSION', '1.4.4'); 
define('NEWSAI_PATH', plugin_dir_path(__FILE__));
define('NEWSAI_URL', plugin_dir_url(__FILE__));

// ==========================================
// 1. ΕΝΕΡΓΟΠΟΙΗΣΗ, DB & AUTO-UPDATER
// ==========================================
define('NEWSAI_DB_VERSION', '1.1'); // Αυξάνουμε αυτό το νούμερο κάθε φορά που αλλάζουμε τη βάση

// Η συνάρτηση που χτίζει ή αναβαθμίζει τη βάση
if (!function_exists('newsroom_os_install_or_update_db')) {
    function newsroom_os_install_or_update_db() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'newsai_alerts';
        $charset_collate = $wpdb->get_charset_collate();
        
        // Το νέο σχήμα της βάσης με τα Composite Indexes
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            day tinyint(2) NOT NULL,
            month tinyint(2) NOT NULL,
            year smallint(4) NOT NULL,
            lead_days tinyint(2) DEFAULT 7 NOT NULL,
            author_id bigint(20) DEFAULT 0 NOT NULL,
            notes text DEFAULT '' NOT NULL,
            target_keyword varchar(255) DEFAULT '' NOT NULL,
            ref_link varchar(255) DEFAULT '' NOT NULL,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_status_date (status, year, month, day),
            KEY idx_author_status (author_id, status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Αποθηκεύουμε τη νέα έκδοση της βάσης
        update_option('newsai_db_version', NEWSAI_DB_VERSION);
    }
}

// Τρέχει κατά τη ΝΕΑ ΕΓΚΑΤΑΣΤΑΣΗ
register_activation_hook(__FILE__, 'newsroom_os_activate_plugin');
if (!function_exists('newsroom_os_activate_plugin')) {
    function newsroom_os_activate_plugin() {
        newsroom_os_install_or_update_db();
        add_option('nros_do_activation_redirect', true);
    }
}

// 🚀 FIX: Ο ΑΥΤΟΜΑΤΟΣ ΕΛΕΓΚΤΗΣ ΑΝΑΒΑΘΜΙΣΕΩΝ (Seamless Update Routine)
// Ελέγχει στο παρασκήνιο αν ο χρήστης έκανε Update το plugin. Αν ναι, αναβαθμίζει τη βάση!
add_action('plugins_loaded', 'newsroom_os_update_db_check');
if (!function_exists('newsroom_os_update_db_check')) {
    function newsroom_os_update_db_check() {
        if (get_option('newsai_db_version') != NEWSAI_DB_VERSION) {
            newsroom_os_install_or_update_db();
        }
    }
}
// 🚀 FIX: Ανακατεύθυνση στο Wizard αμέσως μετά την ενεργοποίηση!
add_action('admin_init', 'nros_activation_redirect');
if (!function_exists('nros_activation_redirect')) {
    function nros_activation_redirect() {
        if (get_option('nros_do_activation_redirect', false)) {
            delete_option('nros_do_activation_redirect');
            if (!isset($_GET['activate-multi'])) {
                wp_safe_redirect(admin_url('admin.php?page=newsroom-os-wizard'));
                exit;
            }
        }
    }
}

// ==========================================
// 2. ΦΟΡΤΩΣΗ ΑΡΧΕΙΩΝ & ASSETS
// ==========================================
$files_to_include = [
    'wizard.php',
    'admin-ui.php',
    'alerts-manager.php',
    'author-meta.php',
    'class-schema-engine.php'
];

foreach ($files_to_include as $file) {
    if (file_exists(NEWSAI_PATH . $file)) {
        require_once NEWSAI_PATH . $file;
    } elseif (file_exists(NEWSAI_PATH . 'includes/' . $file)) {
        require_once NEWSAI_PATH . 'includes/' . $file;
    }
}

add_action('admin_enqueue_scripts', 'newsai_enqueue_assets');
if (!function_exists('newsai_enqueue_assets')) {
    function newsai_enqueue_assets($hook) {
        if ($hook == 'post.php' || $hook == 'post-new.php' || strpos($hook, 'newsai-settings') !== false) {
            $css_path = file_exists(NEWSAI_PATH . 'assets/style.css') ? 'assets/style.css' : 'style.css';
            $js_path = file_exists(NEWSAI_PATH . 'assets/script.js') ? 'assets/script.js' : 'script.js';
            wp_enqueue_style('newsai-style', NEWSAI_URL . $css_path, [], NEWSAI_VERSION);
            wp_enqueue_script('newsai-script', NEWSAI_URL . $js_path, ['jquery'], NEWSAI_VERSION, true);
            global $post;
            wp_localize_script('newsai-script', 'newsai_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('newsai_ajax_nonce'),
                'post_id'  => isset($post->ID) ? $post->ID : 0,
                'lang'     => get_bloginfo('language'),
                'geo'      => get_option('newsai_geo', 'GR')
            ]);
        }
    }
}

// ==========================================
// 3. EDITOR SIDEBAR 
// ==========================================
add_action('add_meta_boxes', 'newsai_add_sidebar');
if (!function_exists('newsai_add_sidebar')) {
    function newsai_add_sidebar() {
        add_meta_box('newsai_sidebar', '🚀 Newsroom OS: Editorial Control', 'newsai_render_sidebar', ['post'], 'side', 'high');
    }
}

if (!function_exists('newsai_render_sidebar')) {
    function newsai_render_sidebar() {
        if (file_exists(NEWSAI_PATH . 'sidebar.php')) {
            require NEWSAI_PATH . 'sidebar.php';
        } elseif (file_exists(NEWSAI_PATH . 'includes/sidebar.php')) {
            require NEWSAI_PATH . 'includes/sidebar.php';
        }
    }
}

// ------------------------------------------------------------------
// AJAX ENDPOINTS & SERVER PROTECTION (Caching)
// ------------------------------------------------------------------
add_action('wp_ajax_newsai_mark_task_done', 'newsai_ajax_mark_task_done');
if (!function_exists('newsai_ajax_mark_task_done')) {
    function newsai_ajax_mark_task_done() {
        check_ajax_referer('newsai_ajax_nonce', 'nonce');
        $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        if ($task_id) { 
            global $wpdb; 
            $wpdb->update($wpdb->prefix . 'newsai_alerts', ['status' => 'done'], ['id' => $task_id]); 
            // Καθαρίζουμε την Cache του χρήστη ώστε να ενημερωθεί η μπάρα του
            delete_transient('nros_tasks_user_' . get_current_user_id());
            delete_transient('nros_tasks_user_0'); 
            wp_send_json_success(); 
        }
        wp_send_json_error();
    }
}

add_action('wp_ajax_newsai_get_related_posts', 'newsai_ajax_get_related_posts');
if (!function_exists('newsai_ajax_get_related_posts')) {
    function newsai_ajax_get_related_posts() {
        check_ajax_referer('newsai_ajax_nonce', 'nonce');
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $query = new WP_Query(['s' => $keyword, 'post_type' => 'post', 'posts_per_page' => 5, 'post__not_in' => [$post_id], 'post_status' => 'publish']);
        $results = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) { $query->the_post(); $results[] = ['title' => get_the_title(), 'url' => get_permalink()]; }
        }
        wp_reset_postdata();
        wp_send_json_success($results);
    }
}

// 🚀 NROS GOOGLE TRENDS (With 5-Min Cache Protection)
add_action('wp_ajax_newsai_get_trends', 'newsai_ajax_get_trends');
if (!function_exists('newsai_ajax_get_trends')) {
    function newsai_ajax_get_trends() {
        check_ajax_referer('newsai_ajax_nonce', 'nonce');
        $geo = isset($_POST['geo']) ? sanitize_text_field($_POST['geo']) : 'GR';
        
        // Caching: Προστασία του Server από Rate Limiting
        $cache_key = 'nros_trends_html_v2_' . $geo;
        $cached_html = get_transient($cache_key);
        if (false !== $cached_html) { wp_send_json_success($cached_html); }

        add_filter('wp_feed_cache_transient_lifetime', '__return_false'); 
        
        // 🚀 FIX: Μειώνουμε το timeout στα 3 δευτερόλεπτα για να μην κρεμάει ο Editor
        $nros_timeout = function() { return 3; };
        add_filter( 'http_request_timeout', $nros_timeout, 999 );
        
        $rss = fetch_feed("https://trends.google.com/trending/rss?geo=" . $geo);
        
        remove_filter( 'http_request_timeout', $nros_timeout, 999 );
        
        if (!is_wp_error($rss)) {
            $html = '<style>@keyframes nros-pulse{0%{opacity:1;}50%{opacity:0.6;}100%{opacity:1;}}</style>';
            $lang = get_bloginfo('language');
            $is_gr = (strpos($lang, 'el') !== false);
            $srch_txt = $is_gr ? ' αναζητήσεις' : ' searches';
            $tr_txt = $is_gr ? 'Τάση' : 'Trend';

            foreach ($rss->get_items(0, 8) as $item) { 
                $traffic = '';
                if ($ht_data = $item->get_item_tags('https://trends.google.com/trending/rss', 'approx_traffic')) {
                    $traffic = $ht_data[0]['data'];
                } elseif ($ht_data = $item->get_item_tags('http://trends.google.com/trending/rss', 'approx_traffic')) {
                    $traffic = $ht_data[0]['data'];
                }
                
                $numeric_traffic = (int) preg_replace('/[^0-9]/', '', $traffic);
                $display_traffic = $traffic ? $traffic . $srch_txt : $tr_txt;
                
                if ($numeric_traffic >= 20000) {
                    $badge = '<span style="font-size:10px; background:#dc2626; color:#fff; padding:2px 6px; border-radius:4px; font-weight:bold; margin-bottom:6px; display:inline-block; animation: nros-pulse 1.5s infinite;">🚨 MEGA TREND (' . $display_traffic . ')</span><br>';
                } elseif ($numeric_traffic >= 5000) {
                    $badge = '<span style="font-size:10px; background:#ea580c; color:#fff; padding:2px 6px; border-radius:4px; font-weight:bold; margin-bottom:6px; display:inline-block;">🔥 HOT (' . $display_traffic . ')</span><br>';
                } else {
                    $badge = '<span style="font-size:10px; background:#f0f6fc; color:#2271b1; border:1px solid #cce0f0; padding:2px 6px; border-radius:4px; font-weight:600; margin-bottom:6px; display:inline-block;">📈 ' . $display_traffic . '</span><br>';
                }

                $html .= '<div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid #f0f0f1; padding:12px 0;">';
                $html .= '<div style="padding-right:10px; line-height: 1.3;">';
                $html .= $badge;
                $html .= '<a href="'.esc_url($item->get_permalink()).'" target="_blank" style="font-size:13px; color:#1d2327; text-decoration:none; font-weight:600;">' . esc_html($item->get_title()) . '</a>';
                $html .= '</div>';
                $html .= '<button type="button" class="nros-btn-outline nros-copy-task-btn" data-title="'.esc_attr($item->get_title()).'" style="min-width:32px; height:32px; padding:0; display:flex; align-items:center; justify-content:center; flex-shrink:0; border: 1px solid #2271b1; background: #fff; border-radius: 4px; cursor: pointer; color: #2271b1;" title="Copy to prompt">➕</button>';
                $html .= '</div>';
            }
            // Αποθήκευση στη μνήμη για 5 λεπτά
            set_transient($cache_key, $html, 5 * MINUTE_IN_SECONDS);
            wp_send_json_success($html);
        } else { wp_send_json_error(); }
    }
}

// 🚀 NROS GOOGLE NEWS (With 5-Min Cache Protection)
add_action('wp_ajax_newsai_get_news', 'newsai_ajax_get_news');
if (!function_exists('newsai_ajax_get_news')) {
    function newsai_ajax_get_news() {
        check_ajax_referer('newsai_ajax_nonce', 'nonce');
        $geo = isset($_POST['geo']) ? sanitize_text_field($_POST['geo']) : 'GR';
        
        $cache_key = 'nros_news_html_v2_' . $geo;
        $cached_html = get_transient($cache_key);
        if (false !== $cached_html) { wp_send_json_success($cached_html); }

        $geo_map = [
            'GR' => 'el', 'CY' => 'el', 'US' => 'en-US', 'GB' => 'en-GB', 
            'FR' => 'fr', 'DE' => 'de', 'IT' => 'it', 'ES' => 'es',
            'NL' => 'nl', 'BE' => 'nl', 'PT' => 'pt-PT', 'RU' => 'ru', 
            'IN' => 'en-IN', 'AU' => 'en-AU', 'CA' => 'en-CA', 'BR' => 'pt-BR', 
            'MX' => 'es-419', 'JP' => 'ja', 'SE' => 'sv', 'CH' => 'de-CH', 'ZA' => 'en-ZA'
        ];
        $hl = isset($geo_map[$geo]) ? $geo_map[$geo] : 'en-US';
        $lang_short = explode('-', $hl)[0];

        add_filter('wp_feed_cache_transient_lifetime', '__return_false'); 
        
        // 🚀 FIX: Μειώνουμε το timeout στα 3 δευτερόλεπτα για να μην κρεμάει ο Editor
        $nros_timeout = function() { return 3; };
        add_filter( 'http_request_timeout', $nros_timeout, 999 );
        
        $rss = fetch_feed("https://news.google.com/rss?hl={$hl}&gl={$geo}&ceid={$geo}:{$lang_short}");
        
        remove_filter( 'http_request_timeout', $nros_timeout, 999 );
        
        if (!is_wp_error($rss)) {
            $html = '';
            $now = current_time('timestamp');
            $lang = get_bloginfo('language');
            $is_gr = (strpos($lang, 'el') !== false);
            $min_txt = $is_gr ? 'λ πριν' : 'm ago';
            
            foreach ($rss->get_items(0, 8) as $item) { 
                $pub_date = strtotime($item->get_date('Y-m-d H:i:s'));
                $diff_mins = round(($now - $pub_date) / 60);
                
                $is_fresh = ($diff_mins > 0 && $diff_mins <= 60); 
                
                if ($is_fresh) {
                    $badge = '<span style="font-size:10px; background:#10b981; color:#fff; padding:2px 6px; border-radius:4px; font-weight:bold; margin-bottom:6px; display:inline-block;">⚡ FRESH (' . $diff_mins . $min_txt . ')</span><br>';
                } else {
                    $badge = '<span style="font-size:10px; color:#646970; margin-bottom:6px; display:inline-block; font-weight: 500;">📰 ' . esc_html($item->get_date('H:i')) . '</span><br>';
                }

                $html .= '<div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid #f0f0f1; padding:12px 0;">';
                $html .= '<div style="padding-right:10px; line-height: 1.3;">';
                $html .= $badge;
                $html .= '<a href="'.esc_url($item->get_permalink()).'" target="_blank" style="font-size:13px; color:#1d2327; text-decoration:none; font-weight:500;">' . esc_html($item->get_title()) . '</a>';
                $html .= '</div>';
                $html .= '<button type="button" class="nros-btn-outline nros-copy-task-btn" data-title="'.esc_attr($item->get_title()).'" style="min-width:32px; height:32px; padding:0; display:flex; align-items:center; justify-content:center; flex-shrink:0; border: 1px solid #2271b1; background: #fff; border-radius: 4px; cursor: pointer; color: #2271b1;" title="Copy to prompt">➕</button>';
                $html .= '</div>';
            }
            set_transient($cache_key, $html, 5 * MINUTE_IN_SECONDS);
            wp_send_json_success($html);
        } else { wp_send_json_error(); }
    }
}

// ==========================================
// 4. STORY CLUSTERING ENGINE (NEW SMART TIMELINE)
// ==========================================
if (!function_exists('nros_get_significant_words')) {
    function nros_get_significant_words($string) {
        $stop_words = ['και','με','το','τα','της','του','των','στο','στα','στη','στην','στον','για','απο','από','σε','ειναι','μια','ενα','πως','που','μην','οτι','ότι', 'τις', 'τους', 'the', 'and', 'is', 'in', 'at', 'of', 'on', 'for', 'with', 'a', 'an'];
        $string = mb_strtolower(wp_strip_all_tags($string), 'UTF-8');
        $string = preg_replace('/[^\p{L}\p{N}\s]/u', '', $string);
        $words = preg_split('/\s+/u', $string, -1, PREG_SPLIT_NO_EMPTY);
        return array_diff($words, $stop_words);
    }
}

if (!function_exists('nros_generate_timeline_html')) {
    function nros_generate_timeline_html($post_id, $manual_tag_id = 0, $manual_keyword = '') {
        global $wpdb;
        $limit = (int)get_option('newsai_timeline_limit', 4);
        $lang = get_option('newsai_lang', 'el');
        $days_back = (int)get_option('newsai_timeline_days', 30);
        
        $scored_posts = [];
        $topic_name = '';
        $topic_link = '';

        if ($manual_tag_id || !empty($manual_keyword)) {
            $args = [
                'post_type' => 'post', 
                'posts_per_page' => $limit, 
                'post__not_in' => [$post_id], 
                'orderby' => 'date', 
                'order' => 'DESC', 
                'post_status' => 'publish',
                'date_query' => [
                    ['after' => $days_back . ' days ago']
                ]
            ];
            
            if ($manual_tag_id) {
                $args['tag_id'] = $manual_tag_id;
                $tag = get_tag($manual_tag_id);
                $topic_name = $tag->name;
                $topic_link = get_term_link($tag);
            } else {
                $args['s'] = $manual_keyword;
                $topic_name = $manual_keyword;
                $topic_link = home_url('/?s=' . urlencode($manual_keyword));
            }
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $scored_posts[] = [ 'id' => get_the_ID(), 'title' => get_the_title(), 'date' => get_the_date('Y-m-d H:i:s') ];
                }
            }
            wp_reset_postdata();

        } else {
            $post_tags = wp_get_post_tags($post_id, ['fields' => 'ids']);
            if (empty($post_tags)) return ''; 

            $best_tag_id = $post_tags[0];
            $post_title = mb_strtolower(get_the_title($post_id), 'UTF-8');
            $all_tags_objs = wp_get_post_tags($post_id);
            foreach ($all_tags_objs as $t) {
                if (mb_strpos($post_title, mb_strtolower($t->name, 'UTF-8')) !== false) {
                    $best_tag_id = $t->term_id; break;
                }
            }
            $tag_obj = get_tag($best_tag_id);
            $topic_name = $tag_obj->name;
            $topic_link = get_term_link($tag_obj);

            // ==========================================
            // FIX: SQL INJECTION PREVENTION (AUDIT FIX)
            // ==========================================
            $tag_in_array = array_map('intval', $post_tags);
            $placeholders = implode(', ', array_fill(0, count($tag_in_array), '%d')); 
            
            $cat_ids = wp_get_post_categories($post_id, ['fields' => 'ids']);
            
            // 🚀 ENTERPRISE FIX: Χρήση DISTINCT αντί για GROUP BY για ταχύτερο Table Scan
            $sql = "
                SELECT DISTINCT p.ID, p.post_title, p.post_date
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tt.term_id IN ($placeholders) AND tt.taxonomy = 'post_tag'
                AND p.ID != %d AND p.post_status = 'publish' AND p.post_type = 'post'
                AND p.post_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
                LIMIT 30
            ";
            
            $prepare_args = array_merge($tag_in_array, [$post_id, $days_back]);
            $candidates = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_args));
            // ==========================================

            if ($candidates) {
                $current_title_words = nros_get_significant_words(get_the_title($post_id));
                
                foreach ($candidates as $cand) {
                    $score = 0;
                    
                    $cand_tags = wp_get_post_tags($cand->ID, ['fields' => 'ids']);
                    $shared_tags = array_intersect($post_tags, $cand_tags);
                    $score += count($shared_tags) * 3; 
                    
                    $cand_cats = wp_get_post_categories($cand->ID, ['fields' => 'ids']);
                    if (!empty(array_intersect($cat_ids, $cand_cats))) {
                        $score += 2; 
                    }
                    
                    $cand_title_words = nros_get_significant_words($cand->post_title);
                    $shared_words = array_intersect($current_title_words, $cand_title_words);
                    $score += count($shared_words) * 2; 
                    
                    $days_old = (current_time('timestamp') - strtotime($cand->post_date)) / DAY_IN_SECONDS;
                    if ($days_old < 2) $score += 3;
                    elseif ($days_old < 7) $score += 2;
                    elseif ($days_old < 30) $score += 1;
                    
                    if ($score >= 5) { 
                        $scored_posts[] = [ 'id' => $cand->ID, 'title' => $cand->post_title, 'date' => $cand->post_date, 'score' => $score ];
                    }
                }
                
                if (!empty($scored_posts)) {
                    usort($scored_posts, function($a, $b) { return $b['score'] <=> $a['score']; });
                    $scored_posts = array_slice($scored_posts, 0, $limit);
                    usort($scored_posts, function($a, $b) { return strtotime($b['date']) <=> strtotime($a['date']); });
                }
            }
        }

        if (empty($scored_posts)) return '';

        $def_h = ($lang == 'el') ? 'Η εξέλιξη της είδησης: {tag}' : 'Story Evolution: {tag}';
        $t_heading = get_option('newsai_timeline_heading', $def_h);
        $heading_final = str_replace('{tag}', '<a href="'.esc_url($topic_link).'" style="color:#2271b1;text-decoration:none;">'.esc_html($topic_name).'</a>', $t_heading);

        $html = '<div class="newsai-story-cluster" style="margin:25px 0; padding:20px; background:#fcfcfc; border:1px solid #e2e4e7; border-radius:8px; font-family:-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">';
        $html .= '<h3 style="margin-top:0; font-size:16px; border-bottom:2px solid #2271b1; padding-bottom:10px; margin-bottom:20px; color:#1d2327;">📍 '.$heading_final.'</h3>';
        $html .= '<div style="border-left:2px solid #e2e4e7; padding-left:15px; margin-left:8px;">';
        
        $total_posts = count($scored_posts);
        $now = current_time('timestamp');

        foreach ($scored_posts as $index => $p) {
            $post_time = strtotime($p['date']);
            $diff_days = floor(($now - $post_time) / DAY_IN_SECONDS);
            
            if ($diff_days == 0) $time_label = ($lang == 'el') ? 'Σήμερα' : 'Today';
            elseif ($diff_days == 1) $time_label = ($lang == 'el') ? 'Χθες' : 'Yesterday';
            elseif ($diff_days <= 7) $time_label = ($lang == 'el') ? "Πριν $diff_days ημέρες" : "$diff_days days ago";
            else $time_label = date_i18n('j M', $post_time);

            if ($index === $total_posts - 1 && $total_posts > 1) {
                $time_label = ($lang == 'el') ? '📍 Η Αρχή' : '📍 The Start';
            }

            $thumb = get_the_post_thumbnail_url($p['id'], 'thumbnail');
            
            $html .= '<div style="position:relative; margin-bottom:20px; display:flex; align-items:flex-start;">';
            $html .= '<span style="position:absolute; left:-21px; top:6px; width:10px; height:10px; background:#2271b1; border-radius:50%; border:2px solid #fcfcfc;"></span>';
            
            if($thumb) {
                // Προσθήκη δυναμικού alt attribute με τον τίτλο του άρθρου για τέλειο SEO
                $html .= '<img src="'.esc_url($thumb).'" alt="'.esc_attr($p['title']).'" style="width:60px; height:60px; object-fit:cover; border-radius:4px; margin-right:15px; border:1px solid #ddd; margin-top:2px;">';
            }
            
            $html .= '<div>';
            $html .= '<div style="font-size:10px; font-weight:700; color:#2271b1; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">'.$time_label.'</div>';
            $html .= '<a href="'.get_permalink($p['id']).'" style="font-weight:600; color:#1d2327; text-decoration:none; line-height: 1.4; display: block; font-size:14px;">'.esc_html($p['title']).'</a>';
            $html .= '</div></div>';
        }
        
        $html .= '</div>';
        
        $btn_text = ($lang == 'el') ? 'Όλο το χρονικό &rarr;' : 'Full timeline &rarr;';
        $html .= '<div style="margin-top: 10px; text-align: left; padding-left: 25px;">';
        $html .= '<a href="'.esc_url($topic_link).'" style="font-size: 12px; font-weight: bold; color: #2271b1; text-decoration: none;">'.$btn_text.'</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }
}

add_shortcode('newsroom_timeline', 'newsai_timeline_shortcode_callback');
if (!function_exists('newsai_timeline_shortcode_callback')) {
    function newsai_timeline_shortcode_callback($atts = []) {
        global $post; 
        if (!$post) return '';
        
        $a = shortcode_atts(['tag_id' => 0, 'keyword' => ''], $atts);
        
        $cache_key = 'nros_timeline_html_' . $post->ID;
        $cached = get_transient($cache_key);
        if (false !== $cached && empty($a['tag_id']) && empty($a['keyword'])) return $cached;
        
        $html = nros_generate_timeline_html($post->ID, (int)$a['tag_id'], sanitize_text_field($a['keyword']));
        if (empty($a['tag_id']) && empty($a['keyword'])) {
            set_transient($cache_key, $html, 12 * HOUR_IN_SECONDS);
        }
        return $html;
    }
}

add_filter('the_content', 'newsai_timeline_auto_inject', 20);
if (!function_exists('newsai_timeline_auto_inject')) {
    function newsai_timeline_auto_inject($content) {
        if (!is_single() || is_admin() || get_option('newsai_timeline_mode', 'auto') !== 'auto') return $content;
        if (get_post_meta(get_the_ID(), '_newsai_disable_auto_timeline', true) === 'yes' || has_shortcode($content, 'newsroom_timeline')) return $content;
        
        $timeline = newsai_timeline_shortcode_callback();
        if (empty($timeline)) return $content;
        
        $paragraphs = explode('</p>', $content); 
        $para_index = (int)get_option('newsai_timeline_para', 3);
        
        if ($para_index <= 0 || $para_index >= count($paragraphs)) {
            return $content . $timeline;
        }
        
        $new_content = '';
        foreach ($paragraphs as $index => $para) { 
            $new_content .= $para . '</p>'; 
            if (($index + 1) === $para_index) $new_content .= $timeline; 
        }
        return $new_content;
    }
}
add_action('wp_ajax_newsai_build_timeline', 'newsai_manual_timeline_ajax');
if (!function_exists('newsai_manual_timeline_ajax')) {
    function newsai_manual_timeline_ajax() {
        check_ajax_referer('newsai_ajax_nonce', 'nonce');
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $html = nros_generate_timeline_html($post_id, $tag_id, $keyword);
        if($html) wp_send_json_success($html); else wp_send_json_error('Not enough context found for a story cluster.');
    }
}

// 🤖 AI PROMPTS GENERATOR 
add_action('wp_ajax_newsai_get_prompt', 'newsai_ajax_get_prompt');
if (!function_exists('newsai_ajax_get_prompt')) {
    function newsai_ajax_get_prompt() {
        check_ajax_referer('newsai_ajax_nonce', 'nonce');
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
        
        $lang = get_bloginfo('language');
        
        $prompt_template = get_option('newsai_prompt_' . $type, '');
        if (empty(trim($prompt_template)) && function_exists('newsai_get_default_prompt')) {
            $prompt_template = newsai_get_default_prompt($type, $lang);
        }
        
        // 🚀 FIX: Προσθήκη Site Name και Site Desc για το Rewrite Prompt!
        $site_name = get_option('nros_site_name', get_bloginfo('name'));
        $site_desc = get_option('newsai_site_desc', get_bloginfo('description'));
        
        $prompt = str_replace(
            ['{title}', '{content}', '{site}', '{site_desc}'], 
            [$title, $content, $site_name, $site_desc], 
            $prompt_template
        );
        
        if ($prompt) { wp_send_json_success($prompt); } else { wp_send_json_error(); }
    }
}

// ==========================================
// 5. CACHE CLEARING & SEO SCANNER RESTORATION
// ==========================================
add_action('save_post', 'newsroom_os_clear_timeline_cache');
if (!function_exists('newsroom_os_clear_timeline_cache')) {
    function newsroom_os_clear_timeline_cache($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nros_timeline_html_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_nros_timeline_html_%'");
    }
}

if (!function_exists('newsroom_os_get_missing_seo_count')) {
    function newsroom_os_get_missing_seo_count() {
        global $wpdb;
        // 🚀 FIX: Αγνόησε άρθρα που έχουν αρκετό κείμενο (πάνω από 600 chars), αφήνοντας το Rank Math να κάνει auto-generate.
        return (int)$wpdb->get_var("SELECT COUNT(p.ID) FROM {$wpdb->posts} p WHERE p.post_type = 'post' AND p.post_status = 'publish' AND p.post_date > DATE_SUB(NOW(), INTERVAL 30 DAY) AND p.post_excerpt = '' AND LENGTH(p.post_content) < 600 AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.ID AND pm.meta_key IN ('_yoast_wpseo_metadesc', 'rank_math_description') AND pm.meta_value != '')");
    }
}

add_action( 'pre_get_posts', 'newsroom_os_filter_missing_seo_posts' );
if (!function_exists('newsroom_os_filter_missing_seo_posts')) {
    function newsroom_os_filter_missing_seo_posts( $query ) {
        global $pagenow, $wpdb;
        if ( is_admin() && $pagenow === 'edit.php' && $query->is_main_query() && isset( $_GET['newsai_filter'] ) && $_GET['newsai_filter'] === 'missing_seo' ) {
            $post_ids = $wpdb->get_col("SELECT p.ID FROM {$wpdb->posts} p WHERE p.post_type = 'post' AND p.post_status = 'publish' AND p.post_date > DATE_SUB(NOW(), INTERVAL 30 DAY) AND p.post_excerpt = '' AND LENGTH(p.post_content) < 600 AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.ID AND pm.meta_key IN ('_yoast_wpseo_metadesc', 'rank_math_description') AND pm.meta_value != '')");
            if (empty($post_ids)) { $post_ids = [0]; } 
            $query->set('post__in', $post_ids);
            add_action('admin_notices', function() { echo '<div class="notice notice-error"><p>🚨 <strong>Newsroom OS:</strong> Προβολή άρθρων με "Thin Content" (κάτω από ~100 λέξεις) που δεν έχουν ούτε χειροκίνητο Meta Description.</p></div>'; });
        }
    }
}

// 🚀 FIX: Ασφαλής αποθήκευση του Checkbox για την Απενεργοποίηση του Timeline με Nonce Check
add_action('save_post', 'newsai_save_timeline_meta');
if (!function_exists('newsai_save_timeline_meta')) {
    function newsai_save_timeline_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        // Έλεγχος Ασφαλείας: Σιγουρευόμαστε ότι το request έρχεται από τη δεξιά μπάρα
        if (!isset($_POST['newsai_sidebar_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['newsai_sidebar_nonce'])), 'newsai_sidebar_save')) {
            return;
        }
        
        // Αν το checkbox είναι τσεκαρισμένο, σώσε το. Αλλιώς διέγραψέ το.
        if (isset($_POST['newsai_disable_auto_timeline'])) {
            update_post_meta($post_id, '_newsai_disable_auto_timeline', 'yes');
        } else {
            delete_post_meta($post_id, '_newsai_disable_auto_timeline');
        }
    }
}

// ==========================================
// 6. GOOGLE SEARCH STATUS API (CORE UPDATES)
// ==========================================
if (!function_exists('newsroom_os_check_google_updates')) {
    function newsroom_os_check_google_updates() {
        // 🚀 FIX: v9 για άμεση ενημέρωση και σωστό Rollout Date
        $cache_key = 'nros_google_status_v9';
        $cached_status = get_transient($cache_key);
        if (false !== $cached_status) { return $cached_status; }

        $status = ['state' => 'clear', 'message' => 'No active Google updates detected', 'link' => 'https://status.search.google.com/'];
        $response = wp_remote_get('https://status.search.google.com/incidents.json', ['timeout' => 5]);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $incidents = json_decode(wp_remote_retrieve_body($response), true);
            if (is_array($incidents) && !empty($incidents)) {
                $latest_incident = null;
                $highest_time = 0;
                
                foreach ($incidents as $incident) {
                    $begin_timestamp = !empty($incident['begin']) ? strtotime($incident['begin']) : (!empty($incident['created']) ? strtotime($incident['created']) : 0);
                    if ($begin_timestamp >= $highest_time) {
                        $highest_time = $begin_timestamp;
                        $latest_incident = $incident;
                    }
                }
                
                if ($latest_incident) {
                    $is_resolved = (!empty($latest_incident['end'])) ? true : false;
                    $formatted_end_date = $is_resolved ? date('j/n', strtotime($latest_incident['end'])) : '';

                    $incident_title = !empty($latest_incident['summary']) ? $latest_incident['summary'] : (!empty($latest_incident['title']) ? $latest_incident['title'] : 'Google Update');
                    if (mb_strlen($incident_title) > 60) { $incident_title = mb_substr($incident_title, 0, 57) . '...'; }
                    
                    $link = 'https://status.search.google.com/';
                    if (!empty($latest_incident['uri'])) { $link = 'https://status.search.google.com/' . ltrim($latest_incident['uri'], '/'); }
                    
                    $is_gr = (strpos(get_bloginfo('language'), 'el') !== false);

                    if (!$is_resolved) {
                        $status = ['state' => 'active', 'message' => '🚨 ' . sanitize_text_field($incident_title), 'link' => esc_url_raw($link)];
                    } else {
                        $msg = ($is_gr ? "✅ Ολοκληρώθηκε: " : "✅ Completed: ") . $incident_title;
                        if ($formatted_end_date) { $msg .= " (Rollout: $formatted_end_date)"; }
                        $status = ['state' => 'clear', 'message' => sanitize_text_field($msg), 'link' => esc_url_raw($link)];
                    }
                }
            }
        }
        set_transient($cache_key, $status, 12 * HOUR_IN_SECONDS);
        return $status;
    }
}
// 🚀 FIX: Αυτόματος καθαρισμός παλιών Tasks (Database Hygiene)
add_action('newsai_weekly_cleanup', 'newsai_cleanup_old_tasks');
function newsai_cleanup_old_tasks() {
    global $wpdb;
    $table = $wpdb->prefix . 'newsai_alerts';
    // Διαγραφή tasks που ολοκληρώθηκαν πριν από 90 ημέρες
    $wpdb->query("DELETE FROM {$table} WHERE status = 'done' AND year > 0 AND STR_TO_DATE(CONCAT(year, '-', month, '-', day), '%Y-%m-%d') < DATE_SUB(NOW(), INTERVAL 90 DAY)");
}

// Προγραμματισμός του cleanup αν δεν υπάρχει
if (!wp_next_scheduled('newsai_weekly_cleanup')) {
    wp_schedule_event(time(), 'weekly', 'newsai_weekly_cleanup');
}