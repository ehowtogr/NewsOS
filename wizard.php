<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_menu', 'newsroom_os_register_wizard_page');
function newsroom_os_register_wizard_page() {
    add_submenu_page( null, 'Welcome to Newsroom OS', 'Newsroom Setup', 'manage_options', 'newsroom-os-wizard', 'newsroom_os_wizard_screen_render' );
}

function newsroom_os_wizard_screen_render() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    $site_name = get_bloginfo('name');
    $site_niche = get_bloginfo('description');
    $comp_rss = get_option('newsai_competitor_rss', ''); 
    $primary_city = get_option('nros_primary_city', '');
    $lang = get_bloginfo('language');
    $is_gr = (strpos($lang, 'el') !== false);
    
    $wizard_nonce = wp_create_nonce('nros_wizard_nonce');

    $i18n = [
        's1_title' => $is_gr ? 'Καλώς ήρθατε στο Newsroom OS' : 'Welcome to Newsroom OS',
        's1_desc'  => $is_gr ? 'Βήμα 1 από 4: Ας γνωρίσουμε το site σας.' : 'Step 1 of 4: Let\'s get to know your site.',
        'org_name' => $is_gr ? 'Όνομα Οργανισμού / Portal' : 'Organization / Portal Name',
        'niche'    => $is_gr ? 'Κύρια Θεματολογία (Niche)' : 'Main Niche / Topics',
        'city'     => $is_gr ? '📍 Κύρια Πόλη / Τοποθεσία' : '📍 Primary City / Location',
        'city_p'   => $is_gr ? 'Αυτό θα χρησιμοποιηθεί για το Local SEO.' : 'Used for Local SEO & AreaServed.',
        'comp'     => $is_gr ? 'Ανταγωνιστές (RSS Feeds)' : 'Competitors to Monitor (RSS Feeds)',
        'work'     => $is_gr ? 'Πώς εργάζεστε;' : 'How do you work?',
        'team'     => $is_gr ? 'Ομάδα' : 'Team',
        'team_p'   => $is_gr ? 'Tasks & Πληροφορίες' : 'Tasks & Intel',
        'solo'     => $is_gr ? 'Solo Blogger' : 'Solo Blogger',
        'solo_p'   => $is_gr ? 'Μόνο SEO Εργαλεία' : 'SEO Tools Only',
        
        's2_title' => $is_gr ? 'SEO & Μηχανή Schema' : 'SEO & Schema Engine',
        's2_desc'  => $is_gr ? 'Βήμα 2 από 4: Ενισχύστε το E-E-A-T & Voice SEO.' : 'Step 2 of 4: Boost your E-E-A-T & Voice SEO.',
        'sch_type' => $is_gr ? 'Κύριος τύπος Schema:' : 'Primary Schema markup type:',
        'org_type' => $is_gr ? 'Τύπος Οργανισμού:' : 'Organization Type:',
        'social'   => $is_gr ? 'Social Links (1 ανά γραμμή)' : 'Social Links (1 per line)',
        'c_feed'   => $is_gr ? 'ID Σελίδων Ροής (Για Carousel)' : 'Custom Feed Page IDs (For Carousel)',
        'c_feed_p' => $is_gr ? 'π.χ. 14, 25 (Αν χρησιμοποιείτε Elementor για τη ροή ειδήσεων)' : 'e.g. 14, 25 (If using a Page Builder for news feeds)',
        'speak'    => $is_gr ? 'Voice SEO (Speakable CSS Classes)' : 'Voice SEO (Speakable CSS Classes)',
        'speak_p'  => $is_gr ? 'π.χ. .headline, .summary (Ποιο κείμενο να διαβάζει η Google)' : 'e.g. .headline, .summary (What Google Assistant should read aloud)',
        'disab'    => $is_gr ? 'Απενεργοποιημένο' : 'Disabled',

        's3_title' => $is_gr ? 'Story Timeline Engine' : 'Story Timeline Engine',
        's3_desc'  => $is_gr ? 'Βήμα 3 από 4: Ρύθμιση Internal Linking.' : 'Step 3 of 4: Configure automated Internal Linking.',
        'head'     => $is_gr ? 'Τίτλος Χρονικού' : 'Timeline Heading',
        'inj'      => $is_gr ? 'Λειτουργία Εμφάνισης (Πού θα μπαίνει;)' : 'Injection Mode (How should we display it?)',
        'auto'     => $is_gr ? 'Αυτόματα' : 'Auto-Pilot',
        'auto_p'   => $is_gr ? 'Σε όλα τα άρθρα δυναμικά' : 'Show on all articles automatically',
        'man'      => $is_gr ? 'Μόνο Χειροκίνητα' : 'Manual Only',
        'man_p'    => $is_gr ? 'Μόνο όπου βάζω το shortcode' : 'Only where I use the shortcode',
        'safe_tl'  => $is_gr ? 'Εγγύηση Ασφαλούς Απεγκατάστασης:' : 'Safe Uninstall Guarantee:',
        'safe_ds'  => $is_gr ? 'Η αυτόματη λειτουργία είναι 100% ασφαλής. Δεν πειράζει τη βάση δεδομένων σας.' : 'Auto-Pilot is 100% safe. It does not modify your database.',

        's4_title' => $is_gr ? 'Όλα έτοιμα! 🎉' : 'You\'re all set! 🎉',
        's4_desc'  => $is_gr ? 'Βήμα 4 από 4: Γρήγορος οδηγός επιβίωσης.' : 'Step 4 of 4: Quick survival guide.',
        'h_eeat_t' => $is_gr ? '👤 Author E-E-A-T Profiles (SEO)' : '👤 Author E-E-A-T Profiles (SEO)',
        'h_eeat_d' => $is_gr ? 'Πηγαίνετε στο μενού <strong>Χρήστες &rarr; Προφίλ</strong> και συμπληρώστε τα Social Links και την Ιδιότητα κάθε συντάκτη.' : 'Go to <strong>Users &rarr; Profile</strong> and fill in the Social Links and Job Title of each author.',
        'h_trnd_t' => $is_gr ? '🔥 Trend Radar & Tasks' : '🔥 Trend Radar & Tasks',
        'h_trnd_d' => $is_gr ? 'Βρείτε τα "Επείγοντα Tasks" και τις αναζητήσεις της Google (Trends) στη δεξιά μπάρα κάθε άρθρου.' : 'Find your urgent Tasks and live Google Trends directly in the post editor sidebar.',
        'h_clus_t' => $is_gr ? '🔗 Story Clusters' : '🔗 Story Clusters',
        'h_clus_d' => $is_gr ? 'Χρησιμοποιήστε την αναζήτηση Internal Links στο Sidebar για να προσθέσετε "Διαβάστε Επίσης".' : 'Use the Internal Links search in the Sidebar to insert "Read Also" clusters.',

        'next'     => $is_gr ? 'Επόμενο Βήμα &rarr;' : 'Next Step &rarr;',
        'back'     => $is_gr ? '&larr; Πίσω' : '&larr; Back',
        'launch'   => $is_gr ? 'Εκκίνηση Newsroom OS 🚀' : 'Launch Newsroom OS 🚀',
        'def_head' => $is_gr ? '⏳ Το Χρονικό της Είδησης: {tag}' : '⏳ Story Timeline: {tag}'
    ];

    if (empty($site_niche) || $site_niche === 'Just another WordPress site' || $site_niche === 'Ένας ακόμα ιστότοπος WordPress') {
        $yoast_options = get_option('wpseo_titles');
        if (!empty($yoast_options['metadesc-home-wpseo'])) { $site_niche = $yoast_options['metadesc-home-wpseo']; } 
        elseif (get_option('rank_math_description_home')) { $site_niche = get_option('rank_math_description_home'); } 
        else { $site_niche = ''; }
    }
    ?>
    <style>
        #adminmenumain, #wpadminbar, #wpfooter { display: none !important; }
        #wpcontent, #wpbody-content { margin-left: 0 !important; padding: 0 !important; background: #f3f4f6; }
        .nros-wizard-wrapper { display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 40px 20px; box-sizing: border-box; }
        .nros-wizard-card { background: #ffffff; width: 100%; max-width: 650px; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; overflow: hidden; }
        .nros-wizard-header { text-align: center; margin-bottom: 30px; }
        .nros-wizard-header h1 { font-size: 24px; font-weight: 600; color: #111827; margin: 0 0 10px 0; }
        .nros-wizard-header p { color: #6b7280; font-size: 15px; margin: 0; }
        .nros-wizard-step { display: none; animation: fadeIn 0.4s ease-in-out; }
        .nros-wizard-step.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .nros-form-group { margin-bottom: 20px; }
        .nros-form-group label { display: block; font-weight: 500; color: #374151; margin-bottom: 8px; font-size: 14px;}
        .nros-form-group input[type="text"], .nros-form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        .nros-form-group input:focus, .nros-form-group textarea:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); outline: none; }
        .nros-form-desc { font-size: 12px; color: #6b7280; margin-top: 5px; display: block; }
        .nros-radio-group { display: flex; gap: 15px; flex-wrap: wrap; }
        .nros-radio-card { flex: 1; min-width: 150px; border: 1px solid #d1d5db; border-radius: 8px; padding: 15px; cursor: pointer; text-align: center; transition: all 0.2s; }
        .nros-radio-card:hover { border-color: #2563eb; }
        .nros-radio-card input[type="radio"] { display: none; }
        .nros-radio-card.active { border-color: #2563eb; background: #eff6ff; }
        .nros-radio-card h4 { margin: 0 0 5px 0; font-size: 14px; color: #111827; }
        .nros-radio-card p { margin: 0; font-size: 12px; color: #6b7280; }
        .nros-warning-box { background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 15px; border-radius: 6px; margin-top: 15px; font-size: 13px; color: #92400e; }
        .nros-safe-box { background: #e0f2fe; border: 1px solid #bfdbfe; border-left: 4px solid #3b82f6; padding: 12px 15px; border-radius: 6px; margin-top: 15px; font-size: 13px; color: #1e40af; }
        .nros-help-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; margin-bottom: 10px; text-align: left; }
        .nros-help-card h4 { margin: 0 0 5px 0; font-size: 14px; color: #111827; }
        .nros-help-card p { margin: 0; font-size: 13px; color: #6b7280; }
        .nros-buttons { display: flex; gap: 10px; margin-top: 30px; }
        .nros-btn { padding: 12px 20px; border: none; border-radius: 6px; font-size: 15px; font-weight: 500; cursor: pointer; flex: 1; text-align: center; transition: background 0.2s; }
        .nros-btn-primary { background: #2563eb; color: white; }
        .nros-btn-primary:hover { background: #1d4ed8; }
        .nros-btn-secondary { background: #e5e7eb; color: #374151; }
        .nros-btn-secondary:hover { background: #d1d5db; }
        .nros-progress { height: 4px; background: #e5e7eb; border-radius: 2px; margin-bottom: 30px; overflow: hidden; }
        .nros-progress-bar { height: 100%; background: #2563eb; width: 25%; transition: width 0.3s ease; }
    </style>

    <div class="nros-wizard-wrapper">
        <div class="nros-wizard-card">
            
            <div class="nros-progress"><div class="nros-progress-bar" id="nros-progress-bar"></div></div>
            
            <div id="nros-step-1" class="nros-wizard-step active">
                <div class="nros-wizard-header"><h1><?php echo esc_html($i18n['s1_title']); ?></h1><p><?php echo esc_html($i18n['s1_desc']); ?></p></div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="nros-form-group"><label><?php echo esc_html($i18n['org_name']); ?></label><input type="text" id="nros_site_name" value="<?php echo esc_attr($site_name); ?>"></div>
                    <div class="nros-form-group"><label><?php echo esc_html($i18n['niche']); ?></label><input type="text" id="nros_site_niche" value="<?php echo esc_attr($site_niche); ?>"></div>
                </div>
                
                <div class="nros-form-group">
                    <label><?php echo esc_html($i18n['city']); ?></label>
                    <input type="text" id="nros_primary_city" value="<?php echo esc_attr($primary_city); ?>" placeholder="π.χ. Πάτρα">
                    <span class="nros-form-desc"><?php echo esc_html($i18n['city_p']); ?></span>
                </div>

                <div class="nros-form-group">
                    <label><?php echo esc_html($i18n['comp']); ?></label>
                    <textarea id="nros_comp_rss" rows="2" placeholder="https://example.com/feed/"><?php echo esc_textarea($comp_rss); ?></textarea>
                </div>
                <div class="nros-form-group">
                    <label><?php echo esc_html($i18n['work']); ?></label>
                    <div class="nros-radio-group">
                        <label class="nros-radio-card active" onclick="selectRadio(this, 'nros_team_size')"><input type="radio" name="nros_team_size" value="team" checked><h4><?php echo esc_html($i18n['team']); ?></h4><p><?php echo esc_html($i18n['team_p']); ?></p></label>
                        <label class="nros-radio-card" onclick="selectRadio(this, 'nros_team_size')"><input type="radio" name="nros_team_size" value="solo"><h4><?php echo esc_html($i18n['solo']); ?></h4><p><?php echo esc_html($i18n['solo_p']); ?></p></label>
                    </div>
                </div>
                <div class="nros-buttons"><button type="button" class="nros-btn nros-btn-primary" onclick="nextStep(2)"><?php echo $i18n['next']; ?></button></div>
            </div>

            <div id="nros-step-2" class="nros-wizard-step">
                <div class="nros-wizard-header"><h1><?php echo esc_html($i18n['s2_title']); ?></h1><p><?php echo esc_html($i18n['s2_desc']); ?></p></div>
                <div class="nros-form-group">
                    <label><?php echo esc_html($i18n['sch_type']); ?></label>
                    <div class="nros-radio-group" style="margin-bottom: 15px;">
                        <label class="nros-radio-card active" onclick="selectRadio(this, 'nros_schema_type')"><input type="radio" name="nros_schema_type" value="newsarticle" checked><h4>📰 NewsArticle</h4></label>
                        <label class="nros-radio-card" onclick="selectRadio(this, 'nros_schema_type')"><input type="radio" name="nros_schema_type" value="article"><h4>🏢 Article</h4></label>
                        <label class="nros-radio-card" onclick="selectRadio(this, 'nros_schema_type')"><input type="radio" name="nros_schema_type" value="none"><h4>🚫 <?php echo esc_html($i18n['disab']); ?></h4></label>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="nros-form-group">
                        <label><?php echo esc_html($i18n['org_type']); ?></label>
                        <div class="nros-radio-group" style="display:flex; gap:10px;">
                            <label class="nros-radio-card active" onclick="selectRadio(this, 'nros_org_type')" style="padding:10px;"><input type="radio" name="nros_org_type" value="NewsMediaOrganization" checked><h4 style="font-size:13px;">📡 News Media</h4></label>
                            <label class="nros-radio-card" onclick="selectRadio(this, 'nros_org_type')" style="padding:10px;"><input type="radio" name="nros_org_type" value="Organization"><h4 style="font-size:13px;">🏢 General Org</h4></label>
                        </div>
                    </div>
                    <div class="nros-form-group"><label><?php echo esc_html($i18n['social']); ?></label><textarea id="nros_social_links" rows="2" placeholder="https://facebook.com/..."></textarea></div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="nros-form-group">
                        <label><?php echo esc_html($i18n['c_feed']); ?></label>
                        <input type="text" id="nros_custom_feed_pages" placeholder="e.g. 14, 25">
                        <span class="nros-form-desc"><?php echo esc_html($i18n['c_feed_p']); ?></span>
                    </div>
                    <div class="nros-form-group">
                        <label><?php echo esc_html($i18n['speak']); ?></label>
                        <input type="text" id="nros_speakable_selectors" placeholder=".headline, .summary">
                        <span class="nros-form-desc"><?php echo esc_html($i18n['speak_p']); ?></span>
                    </div>
                </div>
                <div class="nros-buttons">
                    <button type="button" class="nros-btn nros-btn-secondary" onclick="nextStep(1)"><?php echo $i18n['back']; ?></button>
                    <button type="button" class="nros-btn nros-btn-primary" onclick="nextStep(3)"><?php echo $i18n['next']; ?></button>
                </div>
            </div>

            <div id="nros-step-3" class="nros-wizard-step">
                <div class="nros-wizard-header"><h1><?php echo esc_html($i18n['s3_title']); ?></h1><p><?php echo esc_html($i18n['s3_desc']); ?></p></div>
                <div class="nros-form-group">
                    <label><?php echo esc_html($i18n['head']); ?></label>
                    <input type="text" id="nros_t_heading" value="<?php echo esc_attr($i18n['def_head']); ?>">
                </div>
                <div class="nros-form-group">
                    <label><?php echo esc_html($i18n['inj']); ?></label>
                    <div class="nros-radio-group">
                        <label class="nros-radio-card active" onclick="selectRadio(this, 'nros_t_mode')">
                            <input type="radio" name="nros_t_mode" value="auto" checked>
                            <h4>⚡ <?php echo esc_html($i18n['auto']); ?></h4><p><?php echo esc_html($i18n['auto_p']); ?></p>
                        </label>
                        <label class="nros-radio-card" onclick="selectRadio(this, 'nros_t_mode')">
                            <input type="radio" name="nros_t_mode" value="manual">
                            <h4>✋ <?php echo esc_html($i18n['man']); ?></h4><p><?php echo esc_html($i18n['man_p']); ?></p>
                        </label>
                    </div>
                </div>
                <div class="nros-safe-box">
                    <strong>💡 <?php echo esc_html($i18n['safe_tl']); ?></strong> <?php echo esc_html($i18n['safe_ds']); ?>
                </div>
                <div class="nros-buttons">
                    <button type="button" class="nros-btn nros-btn-secondary" onclick="nextStep(2)"><?php echo $i18n['back']; ?></button>
                    <button type="button" class="nros-btn nros-btn-primary" onclick="nextStep(4)"><?php echo $i18n['next']; ?></button>
                </div>
            </div>

            <div id="nros-step-4" class="nros-wizard-step">
                <div class="nros-wizard-header"><h1><?php echo esc_html($i18n['s4_title']); ?></h1><p><?php echo esc_html($i18n['s4_desc']); ?></p></div>
                
                <div class="nros-help-card">
                    <h4><?php echo esc_html($i18n['h_eeat_t']); ?></h4>
                    <p><?php echo wp_kses_post($i18n['h_eeat_d']); ?></p>
                </div>
                
                <div class="nros-help-card">
                    <h4><?php echo esc_html($i18n['h_trnd_t']); ?></h4>
                    <p><?php echo esc_html($i18n['h_trnd_d']); ?></p>
                </div>
                
                <div class="nros-help-card">
                    <h4><?php echo esc_html($i18n['h_clus_t']); ?></h4>
                    <p><?php echo esc_html($i18n['h_clus_d']); ?></p>
                </div>
                
                <div class="nros-buttons">
                    <button type="button" class="nros-btn nros-btn-secondary" onclick="nextStep(3)"><?php echo $i18n['back']; ?></button>
                    <button type="button" class="nros-btn nros-btn-primary" id="nros-launch-btn"><?php echo esc_html($i18n['launch']); ?></button>
                </div>
            </div>

        </div>
    </div>

    <script>
        function selectRadio(element, groupName) {
            const inputs = document.querySelectorAll(`input[name="${groupName}"]`);
            inputs.forEach(input => input.closest('.nros-radio-card').classList.remove('active'));
            element.classList.add('active');
            element.querySelector('input').checked = true;
        }

        function nextStep(step) {
            document.querySelectorAll('.nros-wizard-step').forEach(el => el.classList.remove('active'));
            document.getElementById(`nros-step-${step}`).classList.add('active');
            const progress = step === 1 ? '25%' : (step === 2 ? '50%' : (step === 3 ? '75%' : '100%'));
            document.getElementById('nros-progress-bar').style.width = progress;
            window.scrollTo(0,0);
        }

        document.getElementById('nros-launch-btn').addEventListener('click', function() {
            const btn = this;
            btn.innerHTML = 'Saving...';
            btn.disabled = true;

            const data = new URLSearchParams();
            data.append('action', 'nros_save_wizard_data');
            data.append('nonce', '<?php echo esc_js($wizard_nonce); ?>');
            data.append('site_name', document.getElementById('nros_site_name').value);
            data.append('site_niche', document.getElementById('nros_site_niche').value);
            data.append('primary_city', document.getElementById('nros_primary_city').value);
            data.append('team_size', document.querySelector('input[name="nros_team_size"]:checked').value);
            data.append('schema_type', document.querySelector('input[name="nros_schema_type"]:checked').value);
            data.append('comp_rss', document.getElementById('nros_comp_rss').value);
            data.append('org_type', document.querySelector('input[name="nros_org_type"]:checked').value);
            data.append('social_links', document.getElementById('nros_social_links').value);
            data.append('feed_pages', document.getElementById('nros_custom_feed_pages').value);
            data.append('speakable', document.getElementById('nros_speakable_selectors').value);
            data.append('t_heading', document.getElementById('nros_t_heading').value);
            data.append('t_mode', document.querySelector('input[name="nros_t_mode"]:checked').value);

            fetch(ajaxurl, { method: 'POST', body: data })
            .then(response => response.json())
            .then(res => {
                if(res.success) { window.location.href = '<?php echo admin_url("admin.php?page=newsai-settings&tab=alerts"); ?>'; } 
                else { alert('Error: ' + (res.data || 'Try again.')); btn.innerHTML = '<?php echo esc_attr($i18n['launch']); ?>'; btn.disabled = false; }
            });
        });
    </script>
    <?php
}

add_action('wp_ajax_nros_save_wizard_data', 'newsroom_os_ajax_save_wizard_data');
function newsroom_os_ajax_save_wizard_data() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'nros_wizard_nonce')) {
        wp_send_json_error('Security check failed.');
        return;
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access.');
        return;
    }

    if (isset($_POST['site_name'])) update_option('nros_site_name', sanitize_text_field(wp_unslash($_POST['site_name'])));
    if (isset($_POST['site_niche'])) update_option('nros_site_niche', sanitize_text_field(wp_unslash($_POST['site_niche'])));
    if (isset($_POST['primary_city'])) update_option('nros_primary_city', sanitize_text_field(wp_unslash($_POST['primary_city'])));
    if (isset($_POST['team_size'])) update_option('nros_team_size', sanitize_text_field(wp_unslash($_POST['team_size'])));
    if (isset($_POST['schema_type'])) update_option('nros_schema_type', sanitize_text_field(wp_unslash($_POST['schema_type'])));
    if (isset($_POST['comp_rss'])) update_option('newsai_competitor_rss', sanitize_textarea_field(wp_unslash($_POST['comp_rss'])));
    if (isset($_POST['org_type'])) update_option('nros_org_type', sanitize_text_field(wp_unslash($_POST['org_type'])));
    if (isset($_POST['social_links'])) update_option('nros_social_links', sanitize_textarea_field(wp_unslash($_POST['social_links'])));
    if (isset($_POST['feed_pages'])) update_option('newsai_custom_feed_pages', sanitize_text_field(wp_unslash($_POST['feed_pages'])));
    if (isset($_POST['speakable'])) update_option('newsai_speakable_selectors', sanitize_text_field(wp_unslash($_POST['speakable'])));
    if (isset($_POST['t_heading'])) update_option('newsai_timeline_heading', sanitize_text_field(wp_unslash($_POST['t_heading'])));
    if (isset($_POST['t_mode'])) update_option('newsai_timeline_mode', sanitize_text_field(wp_unslash($_POST['t_mode'])));
    
    update_option('nros_wizard_completed', true);
    wp_send_json_success();
}