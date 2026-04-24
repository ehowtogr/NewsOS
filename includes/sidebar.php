<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$lang = get_bloginfo('language');
$is_gr = (strpos($lang, 'el') !== false);
$geo_opt = get_option('newsai_geo', 'GR');

// Δίγλωσσο Λεξικό (i18n)
$i18n = [
    'active_task' => $is_gr ? 'Ενεργό Task / Event' : 'Active Event',
    'overdue_task' => $is_gr ? 'Εκπρόθεσμο Task' : 'Overdue Task',
    'keyword' => $is_gr ? 'Λέξη-κλειδί:' : 'Keyword:',
    'ref' => $is_gr ? 'Πηγή:' : 'Ref:',
    'brief' => $is_gr ? 'Οδηγίες:' : 'Brief:',
    'mark_done' => $is_gr ? 'Ολοκληρώθηκε' : 'Mark as Done',
    'readiness' => $is_gr ? 'Ετοιμότητα Άρθρου' : 'Editorial Readiness',
    'pub_score' => $is_gr ? 'Publish Confidence' : 'Publish Confidence',
    'next_action' => $is_gr ? '⚡ Next Best Action:' : '⚡ Next Best Action:',
    'details' => $is_gr ? 'Λεπτομέρειες Ελέγχου' : 'Score Details',
    'quick_actions' => $is_gr ? 'Γρήγορες Ενέργειες' : 'Quick Actions',
    'sug_tags' => $is_gr ? 'Προτεινόμενα Tags:' : 'Suggested Tags:',
    'tag_ph' => $is_gr ? 'Γράψτε κείμενο για προτάσεις...' : 'Write text for suggestions...',
    'btn_faq' => $is_gr ? 'FAQ (Ερωτήσεις)' : 'FAQ Block',
    'btn_kp' => $is_gr ? 'Βασικά Σημεία' : 'Key Points',
    'ins_cluster' => $is_gr ? 'Σχετικά Άρθρα:' : 'Insert Story Cluster:',
    'search_ph' => $is_gr ? 'Αναζήτηση...' : 'Search articles...',
    'find' => $is_gr ? 'Εύρεση' : 'Find',
    'ins_links' => $is_gr ? 'Εισαγωγή' : 'Insert Links',
    'ins_tl' => $is_gr ? 'Χειροκίνητο Χρονικό:' : 'Insert Manual Timeline:',
    'btn_tl' => $is_gr ? 'Εισαγωγή Χρονικού Εδώ' : 'Insert Timeline Here',
    'dis_tl' => $is_gr ? 'Απενεργοποίηση Αυτόματου Χρονικού' : 'Disable Auto-Timeline',
    'radar' => $is_gr ? 'Radar Τάσεων' : 'Trend Radar',
    'prompts' => $is_gr ? 'AI Prompts' : 'AI Prompts',
    'p_title' => $is_gr ? 'Ιδέες για Τίτλους' : 'Click-worthy Titles',
    'p_seo' => $is_gr ? 'SEO & Meta Data' : 'SEO & Meta Data',
    'p_rewrite' => $is_gr ? 'Διόρθωση & FAQs' : 'Proofread & FAQs'
];

global $post;
$post_id = $post->ID;

global $wpdb;
$table = $wpdb->prefix . 'newsai_alerts';
$current_user_id = get_current_user_id();

// 🚀 FIX: Transient Cache 5 λεπτών για τα Tasks του χρήστη
$cache_key_tasks = 'nros_tasks_user_' . $current_user_id;
$my_tasks = get_transient($cache_key_tasks);

if (false === $my_tasks) {
    $my_tasks = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE (author_id = 0 OR author_id = %d) AND status != 'done'", $current_user_id));
    set_transient($cache_key_tasks, $my_tasks, 5 * MINUTE_IN_SECONDS);
}

$active_tasks = []; 
$now_time = strtotime(gmdate('Y-m-d'));
if ($my_tasks) {
    foreach ($my_tasks as $t) {
        $t_year = ($t->year > 0) ? $t->year : (int)gmdate('Y');
        $t_month = ($t->month > 0) ? $t->month : (int)gmdate('m');
        $task_time = strtotime(sprintf('%04d-%02d-%02d', $t_year, $t_month, $t->day));
        
        if ($t->year == 0 && $t->month > 0) { 
            if (($now_time - $task_time) > (15 * 86400)) { $t_year++; $task_time = strtotime(sprintf('%04d-%02d-%02d', $t_year, $t_month, $t->day)); }
        } elseif ($t->year == 0 && $t->month == 0) { 
            if (($now_time - $task_time) > (5 * 86400)) { $t_month++; if ($t_month > 12) { $t_month = 1; $t_year++; } $task_time = strtotime(sprintf('%04d-%02d-%02d', $t_year, $t_month, $t->day)); }
        }
        
        $diff_days = ($task_time - $now_time) / 86400;
        if ($diff_days <= $t->lead_days) { 
            $t->is_overdue = ($diff_days < 0);
            $active_tasks[] = $t; 
        }
    }
}

// 🚀 ENTERPRISE FIX: Περιορισμός στα 500 top tags για μηδενικό lag στο Editor
    $all_tags = get_tags([
        'hide_empty' => false,
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => 3000 
    ]);
    $cached_tags = array_map(function($t) { return ['id' => $t->term_id, 'name' => $t->name]; }, $all_tags);
    set_transient('nros_top_tags_cache_v2', $cached_tags, HOUR_IN_SECONDS);

wp_nonce_field('newsai_sidebar_save', 'newsai_sidebar_nonce');
?>

<script>
    var nros_site_tags = <?php echo wp_json_encode($cached_tags, JSON_UNESCAPED_UNICODE); ?>;
</script>
<style>
    .nros-sb-wrapper { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 5px; }
    .nros-sb-card { background: #fff; border: 1px solid #e2e4e7; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .nros-sb-title { margin: 0 0 12px 0; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; color: #1d2327; }
    .nros-sb-ul { margin: 0; padding: 0; list-style: none; font-size: 12px; color: #50575e; }
    .nros-sb-ul li { margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
    .nros-btn-outline { background: #fff; border: 1px solid #2271b1; color: #2271b1; border-radius: 4px; padding: 6px 12px; font-size: 12px; cursor: pointer; text-align: center; font-weight: 500; transition: all 0.2s; }
    .nros-btn-outline:hover { background: #f0f6fc; }
    .nros-pill-btn { display: inline-block; padding: 4px 10px; border-radius: 12px; background: #f0f6fc; color: #2271b1; border: 1px solid #cce0f0; font-size: 11px; cursor: pointer; margin: 0 4px 6px 0; transition: all 0.2s; }
    .nros-pill-btn:hover { background: #2271b1; color: #fff; }
    .nros-icon-wrapper { display: inline-flex; align-items: center; justify-content: center; width: 22px; text-align: center; flex-shrink: 0; }
</style>

<div class="nros-sb-wrapper">

    <?php if (!empty($active_tasks)): ?>
        <?php foreach ($active_tasks as $task): ?>
        <div style="background: <?php echo $task->is_overdue ? '#fef2f2' : '#fffdf5'; ?>; border-left: 4px solid <?php echo $task->is_overdue ? '#dc2626' : '#f0b849'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-top: 1px solid #eee; border-right: 1px solid #eee; border-bottom: 1px solid #eee;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: <?php echo $task->is_overdue ? '#991b1b' : '#926c0a'; ?>; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                <span style="font-size:14px;"><?php echo $task->is_overdue ? '🚨' : '📌'; ?></span> 
                <span><?php echo $task->is_overdue ? $i18n['overdue_task'] : $i18n['active_task']; ?></span>
            </div>
            
            <strong style="font-size: 14px; color: #1d2327; display: block; margin-bottom: 12px;"><?php echo esc_html($task->title); ?></strong>        
            
            <?php if(!empty($task->target_keyword)): ?>
                <div style="font-size: 12px; margin-bottom: 8px; background: #f0f6fc; padding: 6px 10px; border-radius: 4px; border: 1px solid #cce0f0;">
                    <span style="color:#2271b1; font-weight:600;">🔑 <?php echo $i18n['keyword']; ?></span> 
                    <span style="color:#1d2327; font-weight: 500;"><?php echo esc_html($task->target_keyword); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($task->ref_link)): ?>
                <div style="font-size: 12px; margin-bottom: 10px; background: #f8f9fa; padding: 6px 10px; border-radius: 4px; border: 1px solid #e2e4e7;">
                    <span style="color:#50575e; font-weight:600;">🔗 <?php echo $i18n['ref']; ?></span> 
                    <a href="<?php echo esc_url($task->ref_link); ?>" target="_blank" style="color:#2271b1; text-decoration:none; word-break: break-all;"><?php echo esc_html($task->ref_link); ?></a>
                </div>
            <?php endif; ?>

            <?php if(!empty($task->notes)): ?>
                <div style="font-size: 12px; color: #555; margin-bottom: 12px;"><strong style="color:#1d2327;"><?php echo $i18n['brief']; ?></strong><br><?php echo nl2br(esc_html($task->notes)); ?></div>
            <?php endif; ?>
            
            <button type="button" id="nros-mark-done-btn" data-id="<?php echo (int)$task->id; ?>" class="nros-btn-outline" style="width: 100%; border-color: #f0b849; color: #926c0a; display:flex; align-items:center; justify-content:center; gap:6px;">
                <span style="font-size:13px;">✅</span> 
                <span><?php echo $i18n['mark_done']; ?></span>
            </button>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="nros-sb-card">
        <h4 class="nros-sb-title">
            <span class="nros-icon-wrapper" style="font-size:16px;">📊</span>
            <span><?php echo $i18n['readiness']; ?></span>
        </h4>
        
        <div style="text-align:center; margin-bottom:20px;">
            <div style="font-size:36px; font-weight:800; color:#d63638; line-height:1;" id="nros-pub-score">0%</div>
            <div style="font-size:11px; color:#646970; text-transform:uppercase; font-weight:600; letter-spacing:0.5px; margin-top:4px;"><?php echo $i18n['pub_score']; ?></div>
            <div style="background:#f0f0f1; border-radius:4px; height:8px; width:100%; margin-top:10px; overflow:hidden;">
                <div id="nros-pub-bar" style="background:#d63638; width:0%; height:100%; transition:all 0.5s ease;"></div>
            </div>
        </div>

        <div style="background:#f9fafb; border:1px solid #e5e7eb; padding:12px; border-radius:6px; margin-bottom:15px; border-left:4px solid #646970;" id="nros-next-action-box">
            <strong style="font-size:12px; color:#1d2327; display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <span><?php echo $i18n['next_action']; ?></span>
                <span id="nros-action-priority" style="font-size:9px; background:#646970; color:#fff; padding:2px 5px; border-radius:4px;">...</span>
            </strong>
            <span style="font-size:12px; color:#50575e; line-height:1.4;" id="nros-next-action-text">Γράψε τον τίτλο του άρθρου...</span>
        </div>

        <details style="font-size:12px; color:#50575e;">
            <summary style="cursor:pointer; font-weight:600; outline:none; color:#2271b1;"><?php echo $i18n['details']; ?></summary>
            <ul class="nros-sb-ul" style="margin-top:12px; padding-top:12px; border-top:1px dashed #e2e4e7;">
                <li id="hc-length"><span class="nros-icon-wrapper">⏳</span> <span>...</span></li>
                <li id="hc-number"><span class="nros-icon-wrapper">🔢</span> <span>...</span></li>
                <li id="hc-power"><span class="nros-icon-wrapper">⚡</span> <span>...</span></li>
                <li id="check-meta"><span class="nros-icon-wrapper">📝</span> <span>...</span></li>
                <li id="check-links"><span class="nros-icon-wrapper">🔗</span> <span>...</span></li>
                <li id="check-words"><span class="nros-icon-wrapper">✍️</span> <span>...</span></li>
            </ul>
        </details>
    </div>

    <div class="nros-sb-card">
        <h4 class="nros-sb-title">
            <span class="nros-icon-wrapper" style="font-size:16px;">⚡</span>
            <span><?php echo $i18n['quick_actions']; ?></span>
        </h4>
        <div style="margin-bottom: 15px;">
            <span style="font-size:12px; color:#646970; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                <span class="nros-icon-wrapper" style="font-size:14px;">🏷️</span> 
                <span><?php echo $i18n['sug_tags']; ?></span>
            </span>
            <div id="nros-tag-suggestions" style="min-height:20px;">
                <span style="font-size:11px; color:#a7aaad; font-style:italic;"><?php echo $i18n['tag_ph']; ?></span>
            </div>
        </div>

        <div style="display:flex; gap:8px; margin-bottom: 15px;">
            <button class="nros-btn-outline nros-quick-block" data-type="faq" style="flex:1; display:flex; align-items:center; justify-content:center; gap:5px;">
                <span style="font-size:14px;">💬</span> <span>+ <?php echo $i18n['btn_faq']; ?></span>
            </button>
            <button class="nros-btn-outline nros-quick-block" data-type="keypoints" style="flex:1; display:flex; align-items:center; justify-content:center; gap:5px;">
                <span style="font-size:14px;">💡</span> <span>+ <?php echo $i18n['btn_kp']; ?></span>
            </button>
        </div>

        <div style="background:#f8f9fa; padding:12px; border-radius:6px; border:1px solid #e2e4e7; margin-bottom:15px;">
            <span style="font-size:12px; color:#3c434a; font-weight:600; display:flex; align-items:center; gap:6px; margin-bottom:8px;">
                <span class="nros-icon-wrapper" style="font-size:14px;">🔗</span> 
                <span><?php echo $i18n['ins_cluster']; ?></span>
            </span>
            <div style="display:flex; gap:5px; margin-bottom:10px;">
                <input type="text" id="newsai-keyword" placeholder="<?php echo esc_attr($i18n['search_ph']); ?>" style="width: 70%; font-size:12px; border:1px solid #ccd0d4; border-radius:4px; padding:4px 8px;">
                <button class="nros-btn-outline" id="newsai-search-links" style="width: 30%;"><?php echo $i18n['find']; ?></button>
            </div>
            <div id="newsai-links-results" style="display:none; max-height:120px; overflow-y:auto; font-size:12px; margin-bottom:10px;"></div>
            <button class="button button-primary" id="newsai-insert-links" style="width: 100%; display:none;"><?php echo $i18n['ins_links']; ?></button>

            <hr style="margin: 15px 0; border:0; border-top:1px solid #e2e4e7;">
            <span style="font-size:12px; color:#3c434a; font-weight:600; display:flex; align-items:center; gap:6px; margin-bottom:8px;">
                <span class="nros-icon-wrapper" style="font-size:14px;">⏳</span> 
                <span><?php echo $i18n['ins_tl']; ?></span>
            </span>
            <button class="nros-btn-outline" id="newsai-insert-timeline" style="width:100%; display:flex; align-items:center; justify-content:center; gap:6px;">
                <span style="font-size:12px;">➕</span> 
                <span><?php echo $i18n['btn_tl']; ?></span>
            </button>
        </div>

        <?php $is_timeline_disabled = get_post_meta($post_id, '_newsai_disable_auto_timeline', true); ?>
        <label style="font-size: 12px; color: #50575e; cursor: pointer; display:flex; align-items:center; gap:6px;">
            <input type="checkbox" name="newsai_disable_auto_timeline" value="yes" <?php checked($is_timeline_disabled, 'yes'); ?> style="margin:0;">
            <span class="nros-icon-wrapper" style="font-size:14px;">🚫</span> 
            <span><?php echo $i18n['dis_tl']; ?></span>
        </label>
    </div>

    <div class="nros-sb-card" style="padding: 0;">
        <div style="padding: 15px 15px 0 15px;">
            <h4 class="nros-sb-title" style="margin-bottom:12px; border:none; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span class="nros-icon-wrapper" style="font-size:16px;">🔥</span> 
                    <span><?php echo $i18n['radar']; ?></span>
                </div>
                <span style="font-size: 10px; background: #fee2e2; color: #dc2626; padding: 2px 6px; border-radius: 4px; font-weight: bold;">LIVE</span>
            </h4>
            <div style="display:flex; gap:20px; border-bottom: 2px solid #f0f0f1;">
                <span class="newsai-tab active" data-target="nros-pane-trends" style="font-size:12px; font-weight:600; cursor:pointer; color:#2271b1; padding-bottom: 8px; margin-bottom: -2px; border-bottom:2px solid #2271b1;">TRENDS</span>
                <span class="newsai-tab" data-target="nros-pane-news" style="font-size:12px; font-weight:600; cursor:pointer; color:#646970; padding-bottom: 8px; margin-bottom: -2px;">NEWS</span>
            </div>
        </div>
        <div id="nros-pane-trends" class="newsai-tab-content" style="padding: 15px; max-height:280px; overflow-y:auto;">Loading...</div>
        <div id="nros-pane-news" class="newsai-tab-content" style="display:none; padding: 15px; max-height:280px; overflow-y:auto;">Loading...</div>
    </div>

    <div class="nros-sb-card">
        <h4 class="nros-sb-title">
            <span class="nros-icon-wrapper" style="font-size:16px;">🤖</span> 
            <span><?php echo $i18n['prompts']; ?></span>
        </h4>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <button class="nros-btn-outline newsai-prompt-btn" data-type="title" style="text-align:left; display:flex; align-items:center; gap:8px;">
                <span class="nros-icon-wrapper" style="font-size:14px;">✍️</span> 
                <span><?php echo $i18n['p_title']; ?></span>
            </button>
            <button class="nros-btn-outline newsai-prompt-btn" data-type="seo" style="text-align:left; display:flex; align-items:center; gap:8px;">
                <span class="nros-icon-wrapper" style="font-size:14px;">🔍</span> 
                <span><?php echo $i18n['p_seo']; ?></span>
            </button>
            <button class="nros-btn-outline newsai-prompt-btn" data-type="rewrite" style="text-align:left; display:flex; align-items:center; gap:8px;">
                <span class="nros-icon-wrapper" style="font-size:14px;">📝</span> 
                <span><?php echo $i18n['p_rewrite']; ?></span>
            </button>
        </div>
    </div>

</div>