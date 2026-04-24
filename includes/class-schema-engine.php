<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Newsroom OS: God-Tier Entity & Graph Schema Engine (v1.4.5 - Authority, Local SEO & Global NLP Update)
 * Includes: Dedicated City Field, Home Page Organization Schema, Global Unaccent, Hybrid GeoCoordinates, BCP47 Locale
 */

$current_schema_setting = get_option('nros_schema_type', 'newsarticle');
if ( $current_schema_setting !== 'none' ) {
    add_filter( 'wpseo_json_ld_output', '__return_empty_array', 99 ); 
    add_filter( 'rank_math/json_ld', '__return_empty_array', 99 ); 
}

// ==========================================
// 0.1. BULLETPROOF GLOBAL UNACCENT
// ==========================================
if (!function_exists('nros_global_unaccent')) {
    function nros_global_unaccent($string) {
        $string = mb_strtolower($string, 'UTF-8');
        if (class_exists('Normalizer')) {
            $string = Normalizer::normalize($string, Normalizer::FORM_D);
            $string = preg_replace('/\p{M}/u', '', $string);
        } else {
            $accents = [
                'ά' => 'α', 'έ' => 'ε', 'ή' => 'η', 'ί' => 'ι', 'ό' => 'ο', 'ύ' => 'υ', 'ώ' => 'ω',
                'ϊ' => 'ι', 'ϋ' => 'υ', 'ΐ' => 'ι', 'ΰ' => 'υ',
                'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
                'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
                'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
                'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
                'ñ' => 'n', 'ç' => 'c'
            ];
            $string = strtr($string, $accents);
        }
        return $string;
    }
}

// ==========================================
// 0.2. KNOWLEDGE GRAPH: THE ULTIMATE GLOBAL MAP
// ==========================================
if (!function_exists('nros_get_global_cities_graph')) {
    function nros_get_global_cities_graph() {
        return [
            'αθηνα' => ['lat' => 37.9838, 'lng' => 23.7275, 'wiki' => 'https://www.wikidata.org/wiki/Q1524'],
            'θεσσαλονικη' => ['lat' => 40.6401, 'lng' => 22.9444, 'wiki' => 'https://www.wikidata.org/wiki/Q17151'],
            'πατρα' => ['lat' => 38.2466, 'lng' => 21.7346, 'wiki' => 'https://www.wikidata.org/wiki/Q133123'],
            'ηρακλειο' => ['lat' => 35.3387, 'lng' => 25.1442, 'wiki' => 'https://www.wikidata.org/wiki/Q160544'],
            'λαρισα' => ['lat' => 39.6390, 'lng' => 22.4191, 'wiki' => 'https://www.wikidata.org/wiki/Q178836'],
            'βολος' => ['lat' => 39.3621, 'lng' => 22.9423, 'wiki' => 'https://www.wikidata.org/wiki/Q200050'],
            'ιωαννινα' => ['lat' => 39.6650, 'lng' => 20.8537, 'wiki' => 'https://www.wikidata.org/wiki/Q183199'],
            'τρικαλα' => ['lat' => 39.5557, 'lng' => 21.7679, 'wiki' => 'https://www.wikidata.org/wiki/Q200063'],
            'χαλκιδα' => ['lat' => 38.4636, 'lng' => 23.5955, 'wiki' => 'https://www.wikidata.org/wiki/Q200054'],
            'σερρες' => ['lat' => 41.0849, 'lng' => 23.5475, 'wiki' => 'https://www.wikidata.org/wiki/Q200049'],
            'αλεξανδρουπολη' => ['lat' => 40.8457, 'lng' => 25.8739, 'wiki' => 'https://www.wikidata.org/wiki/Q200042'],
            'ξανθη' => ['lat' => 41.1328, 'lng' => 24.8877, 'wiki' => 'https://www.wikidata.org/wiki/Q200044'],
            'κατερινη' => ['lat' => 40.2696, 'lng' => 22.5061, 'wiki' => 'https://www.wikidata.org/wiki/Q205051'],
            'καλαματα' => ['lat' => 37.0391, 'lng' => 22.1126, 'wiki' => 'https://www.wikidata.org/wiki/Q200040'],
            'καβαλα' => ['lat' => 40.9396, 'lng' => 24.4069, 'wiki' => 'https://www.wikidata.org/wiki/Q200046'],
            'χανια' => ['lat' => 35.5138, 'lng' => 24.0180, 'wiki' => 'https://www.wikidata.org/wiki/Q171092'],
            'λαμια' => ['lat' => 38.9000, 'lng' => 22.4333, 'wiki' => 'https://www.wikidata.org/wiki/Q200072'],
            'κομοτηνη' => ['lat' => 41.1192, 'lng' => 25.4054, 'wiki' => 'https://www.wikidata.org/wiki/Q200043'],
            'ροδος' => ['lat' => 36.4408, 'lng' => 28.2225, 'wiki' => 'https://www.wikidata.org/wiki/Q200048'],
            'αττικη' => ['lat' => 38.0000, 'lng' => 23.7000, 'wiki' => 'https://www.wikidata.org/wiki/Q12994'],
            'κρητη' => ['lat' => 35.2000, 'lng' => 24.8500, 'wiki' => 'https://www.wikidata.org/wiki/Q22201'],
            'πελοποννησος' => ['lat' => 37.5000, 'lng' => 22.3333, 'wiki' => 'https://www.wikidata.org/wiki/Q172774'],
            'λευκωσια' => ['lat' => 35.1855, 'lng' => 33.3822, 'wiki' => 'https://www.wikidata.org/wiki/Q3856'],
            'λεμεσος' => ['lat' => 34.6735, 'lng' => 33.0406, 'wiki' => 'https://www.wikidata.org/wiki/Q133315'],
            'london' => ['lat' => 51.5072, 'lng' => -0.1276, 'wiki' => 'https://www.wikidata.org/wiki/Q84'],
            'new york' => ['lat' => 40.7128, 'lng' => -74.0060, 'wiki' => 'https://www.wikidata.org/wiki/Q60'],
            'paris' => ['lat' => 48.8566, 'lng' => 2.3522, 'wiki' => 'https://www.wikidata.org/wiki/Q90'],
            'berlin' => ['lat' => 52.5200, 'lng' => 13.4050, 'wiki' => 'https://www.wikidata.org/wiki/Q64'],
        ];
    }
}

// ==========================================
// 0.3. SMART ENTITY TYPE DETECTOR (The AI Fallback)
// ==========================================
if (!function_exists('nros_detect_entity_type')) {
    function nros_detect_entity_type($name, $slug = '') {
        $name_clean = nros_global_unaccent($name);
        $slug_clean = nros_global_unaccent($slug);
        $combined = $name_clean . ' ' . $slug_clean;

        $gov_services = ['pass', 'επιδομα', 'voucher', 'προγραμμα', 'συνταξη', 'benefit', 'allowance', 'grant', 'scheme', 'welfare', 'pension', 'tax', 'fund'];
        foreach ($gov_services as $word) { if (mb_strpos($combined, $word, 0, 'UTF-8') !== false) return 'GovernmentService'; }

        $organizations = ['οπεκα', 'υπουργειο', 'τραπεζα', 'bank', 'εταιρεια', 'company', 'organization', 'ministry', 'department', 'agency', 'council', 'commission', 'committee', 'bureau', 'board', 'union', 'association', 'fed', 'ecb', 'imf', 'un', 'who', 'nato', 'university', 'institute', 'hospital', 'police', 'αστυνομια', 'πυροσβεστικη'];
        foreach ($organizations as $org) { if (mb_strpos($combined, $org, 0, 'UTF-8') !== false) return 'Organization'; }

        $persons = ['μητσοτακης', 'κασσελακης', 'ανδρουλακης', 'πρωθυπουργος', 'υπουργος', 'προεδρος', 'biden', 'trump', 'harris', 'sunak', 'starmer', 'macron', 'scholz', 'putin', 'zelensky', 'president', 'minister', 'senator', 'governor', 'mayor', 'chancellor'];
        foreach ($persons as $person) { if (mb_strpos($combined, $person, 0, 'UTF-8') !== false) return 'Person'; }

        $places = ['ελλαδα', 'κυπρος', 'αθηνα', 'θεσσαλονικη', 'πατρα', 'greece', 'cyprus', 'usa', 'uk', 'europe', 'city', 'country', 'state', 'london', 'paris'];
        foreach ($places as $place) { if (mb_strpos($combined, $place, 0, 'UTF-8') !== false) return 'Place'; }

        return 'Thing';
    }
}

// ==========================================
// 1. ΠΡΟΣΘΗΚΗ ΠΕΔΙΩΝ WIKIDATA, GEO & ΤΥΠΟΥ ΣΤΑ TAGS
// ==========================================
add_action( 'post_tag_edit_form_fields', 'nros_add_tag_entity_fields' );
add_action( 'post_tag_add_form_fields', 'nros_add_tag_entity_fields' );
if (!function_exists('nros_add_tag_entity_fields')) {
    function nros_add_tag_entity_fields( $term ) {
        $term_id = is_object($term) ? $term->term_id : 0;
        $wiki    = $term_id ? get_term_meta( $term_id, 'nros_wikidata_url', true ) : '';
        $lat     = $term_id ? get_term_meta( $term_id, 'nros_lat', true ) : '';
        $lng     = $term_id ? get_term_meta( $term_id, 'nros_lng', true ) : '';
        $type    = $term_id ? get_term_meta( $term_id, 'nros_entity_type', true ) : 'auto';
        ?>
        <div class="form-field">
            <label for="nros_entity_type">Τύπος Οντότητας (Entity Type)</label>
            <select name="nros_entity_type" id="nros_entity_type">
                <option value="auto" <?php selected($type, 'auto'); ?>>🤖 Αυτόματη Αναγνώριση (Προεπιλογή)</option>
                <option value="Person" <?php selected($type, 'Person'); ?>>👤 Πρόσωπο (Person)</option>
                <option value="Organization" <?php selected($type, 'Organization'); ?>>🏢 Οργανισμός / Εταιρεία</option>
                <option value="Place" <?php selected($type, 'Place'); ?>>📍 Τοποθεσία (Place)</option>
                <option value="Event" <?php selected($type, 'Event'); ?>>📅 Γεγονός (Event)</option>
                <option value="Thing" <?php selected($type, 'Thing'); ?>>📦 Αντικείμενο (Thing)</option>
            </select>
            <p class="description">Επιλέξτε χειροκίνητα τον τύπο για απόλυτη ακρίβεια στο Schema Markup.</p>
        </div>
        <div class="form-field">
            <label for="nros_wikidata_url">Wikidata URL (Newsroom OS Entity SEO)</label>
            <input name="nros_wikidata_url" id="nros_wikidata_url" type="url" value="<?php echo esc_attr( $wiki ); ?>" size="40" placeholder="π.χ. https://www.wikidata.org/wiki/Q94">
            <p class="description">Βοηθάει τη Google να κατανοήσει την "Οντότητα" (Knowledge Graph).</p>
        </div>
        <div class="form-field" style="display:flex; gap:15px; margin-top:15px;">
            <div style="flex:1;">
                <label for="nros_lat">Latitude (Γεωγρ. Πλάτος)</label>
                <input name="nros_lat" id="nros_lat" type="text" value="<?php echo esc_attr( $lat ); ?>" placeholder="π.χ. 37.9838">
            </div>
            <div style="flex:1;">
                <label for="nros_lng">Longitude (Γεωγρ. Μήκος)</label>
                <input name="nros_lng" id="nros_lng" type="text" value="<?php echo esc_attr( $lng ); ?>" placeholder="π.χ. 23.7275">
            </div>
        </div>
        <p class="description"><strong>Hybrid Geo-Location:</strong> Αν συμπληρώσετε συντεταγμένες εδώ, η ετικέτα θεωρείται αυτόματα Τοποθεσία (Place) και θα υπερισχύσει του παγκόσμιου χάρτη!</p>
        <?php
    }
}

add_action( 'edited_post_tag', 'nros_save_tag_entity_fields' );
add_action( 'create_post_tag', 'nros_save_tag_entity_fields' );
if (!function_exists('nros_save_tag_entity_fields')) {
    function nros_save_tag_entity_fields( $term_id ) {
        if ( isset( $_POST['nros_entity_type'] ) ) update_term_meta( $term_id, 'nros_entity_type', sanitize_text_field( $_POST['nros_entity_type'] ) );
        if ( isset( $_POST['nros_wikidata_url'] ) ) update_term_meta( $term_id, 'nros_wikidata_url', esc_url_raw( $_POST['nros_wikidata_url'] ) );
        if ( isset( $_POST['nros_lat'] ) ) update_term_meta( $term_id, 'nros_lat', sanitize_text_field( $_POST['nros_lat'] ) );
        if ( isset( $_POST['nros_lng'] ) ) update_term_meta( $term_id, 'nros_lng', sanitize_text_field( $_POST['nros_lng'] ) );
    }
}

// ==========================================
// 2. VideoObject Extractor
// ==========================================
if (!function_exists('nros_extract_video_schema')) {
    function nros_extract_video_schema( $content, $post ) {
        $video_url = '';
        if ( preg_match( '/https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=[\w\-]+|youtu\.be\/[\w\-]+|vimeo\.com\/\d+)/i', $content, $m ) ) { $video_url = esc_url_raw( $m[0] ); } 
        elseif ( preg_match( '/<iframe[^>]+src=["\']([^"\']+)["\']/i', $content, $m ) ) { $video_url = esc_url_raw( $m[1] ); }
        if ( empty( $video_url ) ) return null;

        $thumbnail_url = has_post_thumbnail($post) ? get_the_post_thumbnail_url($post, 'full') : '';
        $is_youtube = preg_match( '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $video_url, $yt_match );

        if ( $is_youtube ) {
            $video_id      = $yt_match[1];
            $thumbnail_url = "https://img.youtube.com/vi/$video_id/hqdefault.jpg";
            $embed_url     = "https://www.youtube.com/embed/$video_id";
        } else { $embed_url = $video_url; }

        return [
            '@type'        => 'VideoObject',
            '@id'          => get_permalink($post) . '#video',
            'name'         => get_the_title( $post ) . ' (Video)',
            'description'  => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), 20 ) ?: 'Video presentation',
            'thumbnailUrl' => [ $thumbnail_url ],
            'uploadDate'   => get_post_time( 'c', true, $post ),
            'embedUrl'     => $embed_url,
            'contentUrl'   => $video_url,
        ];
    }
}

// ==========================================
// 3. Dynamic Author Entity Generator
// ==========================================
if (!function_exists('nros_get_dynamic_author_entity')) {
    function nros_get_dynamic_author_entity( $author_id ) {
        $schema_version = get_option('nros_schema_global_v', '1');
        $cached_entity = get_transient('nros_schema_author_v145_' . $author_id . '_' . $schema_version);
        if (false !== $cached_entity) { return $cached_entity; }

        $author = get_userdata( $author_id );
        if ( ! $author ) return null;

        $avatar_url = set_url_scheme(get_avatar_url($author_id), 'https');

        $entity = [
            '@type'    => 'Person',
            '@id'      => trailingslashit(get_author_posts_url( $author_id )) . '#author',
            'name'     => $author->display_name,
            'url'      => get_author_posts_url( $author_id ),
            'image'    => [ '@type' => 'ImageObject', 'url' => $avatar_url ]
        ];

        $bio = get_the_author_meta('description', $author_id);
        if (!empty($bio)) { $entity['description'] = wp_trim_words(wp_strip_all_tags($bio), 45); }

        $job_title = get_the_author_meta( 'nros_job_title', $author_id );
        if ($job_title) $entity['jobTitle'] = $job_title;

        $education = get_the_author_meta( 'nros_alumni_of', $author_id );
        if ( $education && strpos($education, '|') !== false ) {
            $al_parts = explode('|', $education);
            $entity['alumniOf'] = [ '@type' => 'EducationalOrganization', 'name' => trim($al_parts[0]), 'sameAs'=> esc_url_raw(trim($al_parts[1])) ];
        }

        $sameAs = [];
        if ( $fb = get_the_author_meta( 'nros_social_facebook', $author_id ) ) $sameAs[] = esc_url_raw( $fb );
        if ( $li = get_the_author_meta( 'nros_social_linkedin', $author_id ) ) $sameAs[] = esc_url_raw( $li );
        if ( $tw = get_the_author_meta( 'nros_social_twitter', $author_id ) )  $sameAs[] = esc_url_raw( $tw );
        if ( $wb = $author->user_url ) $sameAs[] = esc_url_raw( $wb );
        if ( ! empty( $sameAs ) ) $entity['sameAs'] = array_values(array_unique($sameAs));

        $knows_array = [];
        $knows_raw = get_the_author_meta('nros_knows_about', $author_id);
        if ( ! empty( $knows_raw ) ) {
            $knows_raw = str_replace("\r", "", $knows_raw); 
            $knows_lines = array_filter(array_map('trim', explode("\n", $knows_raw)));
            foreach ($knows_lines as $line) {
                $k_parts = explode('|', $line);
                $topic_name = trim($k_parts[0]);
                $thing = [ '@type' => nros_detect_entity_type($topic_name), 'name' => $topic_name, 'url' => home_url('/tag/' . sanitize_title($topic_name)) ];
                if (isset($k_parts[1]) && !empty(trim($k_parts[1]))) { $thing['sameAs'] = esc_url_raw(trim($k_parts[1])); }
                $knows_array[] = $thing;
            }
        }
        if (!empty($knows_array)) { $entity['knowsAbout'] = $knows_array; }

        set_transient('nros_schema_author_v145_' . $author_id . '_' . $schema_version, $entity, 30 * DAY_IN_SECONDS);
        return $entity;
    }
}

// ==========================================
// 4. CACHE INVALIDATION
// ==========================================
add_action('save_post', 'nros_clear_schema_cache_on_save');
if (!function_exists('nros_clear_schema_cache_on_save')) {
    function nros_clear_schema_cache_on_save($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        $schema_version = get_option('nros_schema_global_v', '1');
        delete_transient('nros_schema_post_v145_' . $post_id . '_' . $schema_version);
    }
}

add_action('personal_options_update', 'nros_clear_author_cache_on_save');
add_action('edit_user_profile_update', 'nros_clear_author_cache_on_save');
if (!function_exists('nros_clear_author_cache_on_save')) {
    function nros_clear_author_cache_on_save($user_id) {
        update_option('nros_schema_global_v', time()); 
    }
}

// ==========================================
// 5. 🚀 THE ENTITY ENGINE (AI-FIRST GRAPH & FEEDS)
// ==========================================
add_action('wp_head', 'nros_output_json_ld_schema', 99);
add_action('amp_post_template_head', 'nros_output_json_ld_schema', 99);
add_action('ampforwp_head', 'nros_output_json_ld_schema', 99);

if (!function_exists('nros_output_json_ld_schema')) {
    function nros_output_json_ld_schema() {
        $schema_setting = get_option('nros_schema_type', 'newsarticle');
        if ( $schema_setting === 'none' ) return;

        $schema_version = get_option('nros_schema_global_v', '1');
        $org_type = get_option('nros_org_type', 'NewsMediaOrganization');
        $site_name = get_option('nros_site_name', get_bloginfo('name'));
        $site_desc = get_option('newsai_site_desc', get_bloginfo('description'));
        $social_links = get_option('nros_social_links', '');
        $primary_city = get_option('nros_primary_city', ''); 
        
        $geo_opt = get_option('newsai_geo', 'GR');
        $geo_names = [
            'GR' => 'Greece', 'CY' => 'Cyprus', 'US' => 'United States', 'GB' => 'United Kingdom', 
            'DE' => 'Germany', 'FR' => 'France', 'IT' => 'Italy', 'ES' => 'Spain', 
            'NL' => 'Netherlands', 'BE' => 'Belgium', 'PT' => 'Portugal', 'RU' => 'Russia', 
            'IN' => 'India', 'AU' => 'Australia', 'CA' => 'Canada', 'IL' => 'Israel',
            'CH' => 'Switzerland', 'SE' => 'Sweden', 'NO' => 'Norway', 'DK' => 'Denmark',
            'FI' => 'Finland', 'PL' => 'Poland', 'AT' => 'Austria', 'IE' => 'Ireland'
        ];
        $location_name = isset($geo_names[$geo_opt]) ? $geo_names[$geo_opt] : $geo_opt;
        
        $first_post = get_posts(['numberposts' => 1, 'order' => 'ASC', 'post_type' => 'post', 'post_status' => 'publish']);
        $founding_date = !empty($first_post) ? get_the_date('Y-m-d', $first_post[0]->ID) : '2015-01-01';

        // 🚀 BCP 47 Dynamic Locale Fix
        $wp_locale = get_locale();
        $schema_lang = str_replace('_', '-', $wp_locale);

        // 🚀 ENHANCED PUBLISHER (NewsMediaOrganization)
        $publisher = [
            '@type' => $org_type,
            '@id' => home_url() . '#organization',
            'name' => $site_name,
            'url' => home_url(),
            'description' => $site_desc,
            'foundingDate' => $founding_date,
            'areaServed' => [ [ '@type' => 'Country', 'name' => $location_name ] ]
        ];

        // Σύνδεση με την "Πόλη" αν έχει οριστεί (Local SEO Fix)
        if (!empty($primary_city)) {
            $publisher['areaServed'][] = [ '@type' => 'City', 'name' => $primary_city ];
            $publisher['location'] = [ '@type' => 'Place', 'name' => $primary_city ];
        }
        
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $img = wp_get_attachment_image_src($custom_logo_id, 'full');
            if ($img) { $logo_url = $img[0]; $logo_w = $img[1]; $logo_h = $img[2]; }
        }
        if (empty($logo_url)) {
            $rm_titles = get_option('rank_math_titles');
            if (!empty($rm_titles['knowledgegraph_logo'])) { $logo_url = $rm_titles['knowledgegraph_logo']; }
        }
        if (empty($logo_url)) {
            $yoast_titles = get_option('wpseo_titles');
            if (!empty($yoast_titles['company_logo'])) { $logo_url = $yoast_titles['company_logo']; }
        }
        if (empty($logo_url) && has_site_icon()) {
            $logo_url = get_site_icon_url(512); $logo_w = 512; $logo_h = 512;
        }

        if (!empty($logo_url)) {
            $publisher['logo'] = [ '@type' => 'ImageObject', 'url' => esc_url_raw($logo_url), 'width' => (int)$logo_w, 'height' => (int)$logo_h ];
        }

        if (!empty($social_links)) { 
            $clean_links = str_replace("\r\n", "\n", $social_links); 
            $links_array = array_filter(array_map('trim', explode("\n", $clean_links)));
            $valid_links = [];
            foreach ($links_array as $l) { if (filter_var($l, FILTER_VALIDATE_URL)) { $valid_links[] = esc_url_raw($l); } }
            if (!empty($valid_links)) { $publisher['sameAs'] = array_values($valid_links); }
        }

        $graph = [];

        $custom_feed_pages = get_option('newsai_custom_feed_pages', '');
        $custom_feed_array = !empty($custom_feed_pages) ? array_map('trim', explode(',', $custom_feed_pages)) : [];
        $is_custom_feed = (is_page() && in_array((string)get_queried_object_id(), $custom_feed_array));

        // 🚀 ΣΕΝΑΡΙΟ 1: Αρχική Σελίδα (Full Authority Organization Graph)
        if ( is_front_page() || is_home() || $is_custom_feed ) {
            $paged = get_query_var('paged') ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 1);
            $current_obj_id = is_home() ? get_option('page_for_posts') : get_queried_object_id();
            $cache_key = 'nros_schema_home_v145_' . $schema_version . '_p' . $paged . '_id' . $current_obj_id;
            
            $cached_schema = get_transient($cache_key);
            if ( false !== $cached_schema ) { echo $cached_schema; return; }

            $website = [
                '@type' => 'WebSite', '@id' => home_url() . '#website', 'url' => home_url(), 'name' => $site_name,
                'description' => $site_desc, 'inLanguage' => $schema_lang, 'publisher' => ['@id' => home_url() . '#organization'] 
            ];
            
            // Προσθήκη ItemList για Google Discover
            $latest_posts = get_posts(['numberposts' => 15, 'post_status' => 'publish']);
            $item_list = [ '@type' => 'ItemList', '@id' => home_url() . '#itemlist', 'itemListElement' => [] ];
            foreach ($latest_posts as $index => $lp) {
                $item_list['itemListElement'][] = [ '@type' => 'ListItem', 'position' => $index + 1, 'url' => get_permalink($lp->ID) ];
            }

            if ( is_home() || $is_custom_feed ) {
                $page_url = is_home() ? (get_option('page_for_posts') ? get_permalink(get_option('page_for_posts')) : home_url('/')) : get_permalink();
                $page_title = is_home() ? (get_option('page_for_posts') ? get_the_title(get_option('page_for_posts')) : $site_name) : get_the_title();

                $collection = [
                    '@type' => 'CollectionPage', '@id' => trailingslashit($page_url) . '#webpage', 'url' => $page_url,
                    'name' => $page_title . ($paged > 1 ? ' - ' . (strpos($schema_lang, 'el') !== false ? 'Σελίδα ' : 'Page ') . $paged : ''),
                    'inLanguage' => $schema_lang, 'isPartOf' => ['@id' => home_url() . '#website'],
                    'mainEntity' => ['@id' => home_url() . '#itemlist']
                ];
                $graph = [$publisher, $website, $item_list, $collection];
            } else {
                $graph = [$publisher, $website, $item_list];
            }

            $json_payload = wp_json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $output = "\n\n<script type=\"application/ld+json\">\n" . $json_payload . "\n</script>\n";
            set_transient($cache_key, $output, HOUR_IN_SECONDS); 
            echo $output; return;
        }

        if ( is_category() || is_tag() ) {
            $term = get_queried_object();
            if (!$term) return;
            $paged = get_query_var('paged') ? get_query_var('paged') : 1;
            $cache_key = 'nros_schema_term_v145_' . $term->term_id . '_' . $schema_version . '_p' . $paged;
            $cached_schema = get_transient($cache_key);
            if ( false !== $cached_schema ) { echo $cached_schema; return; }

            $collection = [
                '@type' => 'CollectionPage', '@id' => trailingslashit(get_term_link($term)) . '#webpage', 'url' => get_term_link($term),
                'name' => $term->name . ' - ' . $site_name . ($paged > 1 ? ' - ' . (strpos($schema_lang, 'el') !== false ? 'Σελίδα ' : 'Page ') . $paged : ''),
                'inLanguage' => $schema_lang, 'isPartOf' => ['@id' => home_url() . '#website']
            ];

            global $wp_query;
            if ( !empty($wp_query->posts) ) {
                $item_list = [ '@type' => 'ItemList', '@id' => trailingslashit(get_term_link($term)) . '#itemlist', 'itemListElement' => [] ];
                $pos = 1;
                foreach ($wp_query->posts as $p) {
                    $item_list['itemListElement'][] = [ '@type' => 'ListItem', 'position' => $pos, 'url' => get_permalink($p->ID) ];
                    $pos++;
                }
                $collection['mainEntity'] = $item_list;
            }

            $graph[] = $publisher; $graph[] = $collection;
            $json_payload = wp_json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $output = "\n<script type=\"application/ld+json\">\n" . $json_payload . "\n</script>\n";
            set_transient($cache_key, $output, HOUR_IN_SECONDS);
            echo $output; return;
        }

        if ( is_author() ) {
            $author_id = get_queried_object_id();
            if (!$author_id) return;
            $paged = get_query_var('paged') ? get_query_var('paged') : 1;
            $cache_key = 'nros_schema_author_page_v145_' . $author_id . '_' . $schema_version . '_p' . $paged;
            $cached_schema = get_transient($cache_key);
            if ( false !== $cached_schema ) { echo $cached_schema; return; }
            
            $author_url = get_author_posts_url($author_id);
            $profile_page = [
                '@type' => 'ProfilePage', '@id' => trailingslashit($author_url) . '#webpage', 'url' => $author_url,
                'name' => get_the_author_meta('display_name', $author_id) . ' - ' . $site_name,
                'inLanguage' => $schema_lang, 'isPartOf' => ['@id' => home_url() . '#website'],
                'mainEntity' => ['@id' => trailingslashit($author_url) . '#author'] 
            ];

            global $wp_query;
            if ( !empty($wp_query->posts) ) {
                $item_list = [ '@type' => 'ItemList', '@id' => trailingslashit($author_url) . '#itemlist', 'itemListElement' => [] ];
                $pos = 1;
                foreach ($wp_query->posts as $p) {
                    $item_list['itemListElement'][] = [ '@type' => 'ListItem', 'position' => $pos, 'url' => get_permalink($p->ID) ];
                    $pos++;
                }
                $graph[] = $item_list;
            }

            $graph[] = $publisher; $graph[] = $profile_page;
            if (function_exists('nros_get_dynamic_author_entity')) {
                $author_data = nros_get_dynamic_author_entity($author_id);
                if ($author_data) { $author_data['mainEntityOfPage'] = ['@id' => trailingslashit($author_url) . '#webpage']; $graph[] = $author_data; }
            }

            $json_payload = wp_json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $output = "\n<script type=\"application/ld+json\">\n" . $json_payload . "\n</script>\n";
            set_transient($cache_key, $output, HOUR_IN_SECONDS);
            echo $output; return;
        }

        if ( is_single() ) {
            global $post;
            $post_id = $post->ID;
            $cache_key = 'nros_schema_post_v145_' . $post_id . '_' . $schema_version;
            $cached_schema = get_transient($cache_key);
            if ( false !== $cached_schema ) { echo $cached_schema; return; }

            $schema_type_formatted = ($schema_setting === 'article') ? 'Article' : 'NewsArticle'; 
            $article_section = 'Blog';
            $categories = get_the_category($post_id);
            if (!empty($categories)) { $article_section = $categories[0]->name; }

            $website = [ '@type' => 'WebSite', '@id' => home_url() . '#website', 'url' => home_url(), 'name' => $site_name, 'publisher' => ['@id' => home_url() . '#organization'] ];
            $webpage = [ '@type' => 'WebPage', '@id' => trailingslashit(get_permalink()) . '#webpage', 'url' => get_permalink(), 'name' => get_the_title(), 'isPartOf' => ['@id' => home_url() . '#website'], 'mainEntity' => ['@id' => trailingslashit(get_permalink()) . '#article'], 'breadcrumb' => ['@id' => trailingslashit(get_permalink()) . '#breadcrumb'] ];

            $breadcrumb = [ '@type' => 'BreadcrumbList', '@id' => trailingslashit(get_permalink()) . '#breadcrumb', 'itemListElement' => [ [ '@type' => 'ListItem', 'position' => 1, 'name' => (strpos($schema_lang, 'el') !== false ? 'Αρχική' : 'Home'), 'item' => home_url() ] ] ];
            if (!empty($categories)) {
                $breadcrumb['itemListElement'][] = [ '@type' => 'ListItem', 'position' => 2, 'name' => $categories[0]->name, 'item' => get_category_link($categories[0]) ];
                $breadcrumb['itemListElement'][] = [ '@type' => 'ListItem', 'position' => 3, 'name' => get_the_title(), 'item' => get_permalink() ];
            }

            $clean_content = wp_strip_all_tags(strip_shortcodes($post->post_content));
            $meta_desc = get_post_meta($post_id, 'rank_math_description', true) ?: (get_post_meta($post_id, '_yoast_wpseo_metadesc', true) ?: (has_excerpt($post_id) ? wp_strip_all_tags(get_the_excerpt($post_id)) : wp_trim_words($clean_content, 40, '')));
            $meta_desc = trim(str_replace(['&hellip;', '...', '…'], '', $meta_desc));

            $tags = get_the_tags($post_id);
            $scored_tags = []; $keywords_array = []; $about = []; $mentions = [];
            $dynamic_place = null;

            if ($tags) {
                foreach ($tags as $tag) {
                    $score = 0;
                    $wiki_url = get_term_meta($tag->term_id, 'nros_wikidata_url', true);
                    $lat = get_term_meta($tag->term_id, 'nros_lat', true);
                    $lng = get_term_meta($tag->term_id, 'nros_lng', true);
                    $saved_type = get_term_meta($tag->term_id, 'nros_entity_type', true);

                    if ($wiki_url) $score += 3;
                    if (mb_stripos(get_the_title(), $tag->name) !== false) $score += 2;
                    if (substr_count(mb_strtolower($clean_content), mb_strtolower($tag->name)) >= 3) $score += 1;

                    $keywords_array[] = $tag->name;
                    $tag_type = (!empty($saved_type) && $saved_type !== 'auto') ? $saved_type : (nros_detect_entity_type($tag->name, $tag->slug));
                    if ($tag_type !== 'Place' && !empty($lat) && !empty($lng)) { $tag_type = 'Place'; }

                    $entity_node = [ '@type' => $tag_type, '@id' => trailingslashit(get_term_link($tag)) . '#entity', 'name' => $tag->name, 'url' => get_term_link($tag) ];
                    if ($wiki_url) $entity_node['sameAs'] = esc_url_raw($wiki_url);
                    if ($tag_type === 'Place') {
                        if (!empty($lat) && !empty($lng)) { $entity_node['geo'] = [ '@type' => 'GeoCoordinates', 'latitude' => (float)$lat, 'longitude' => (float)$lng ]; }
                        else {
                            $city_map = nros_get_global_cities_graph();
                            $search_key = nros_global_unaccent($tag->name);
                            if (isset($city_map[$search_key])) {
                                $entity_node['geo'] = [ '@type' => 'GeoCoordinates', 'latitude' => (float)$city_map[$search_key]['lat'], 'longitude' => (float)$city_map[$search_key]['lng'] ];
                                if (!$wiki_url && isset($city_map[$search_key]['wiki'])) { $entity_node['sameAs'] = $city_map[$search_key]['wiki']; }
                            }
                        }
                    }
                    $scored_tags[] = [ 'entity' => $entity_node, 'score' => $score ];
                }
                usort($scored_tags, function($a, $b) { return $b['score'] <=> $a['score']; });
                foreach ($scored_tags as $t) {
                    $ent = array_filter($t['entity']);
                    if ($ent['@type'] === 'Place' && !$dynamic_place) { $dynamic_place = $ent; } 
                    if ($t['score'] >= 3 || count($about) < 1) { if (count($about) < 3) { $about[] = $ent; } else { $mentions[] = $ent; } } else { $mentions[] = $ent; }
                }
            }

            $image_array = [];
            if (has_post_thumbnail($post_id)) {
                $thumb_id = get_post_thumbnail_id($post_id);
                foreach (['full', 'large', 'medium_large'] as $size) {
                    $img = wp_get_attachment_image_src($thumb_id, $size);
                    if ($img) $image_array[] = [ '@type' => 'ImageObject', 'url' => esc_url_raw($img[0]), 'width' => (int)$img[1], 'height' => (int)$img[2] ];
                }
            }
            if (empty($image_array) && !empty($publisher['logo'])) { $image_array[] = $publisher['logo']; }

            $final_location = ['@type' => 'Place', 'name' => $dynamic_place ? $dynamic_place['name'] : $location_name];
            if ($dynamic_place) { if (isset($dynamic_place['sameAs'])) $final_location['sameAs'] = $dynamic_place['sameAs']; if (isset($dynamic_place['geo'])) $final_location['geo'] = $dynamic_place['geo']; }

            // 🚀 The New Standardized NewsArticle / Article Schema
            $article = [
                '@type' => $schema_type_formatted, '@id' => trailingslashit(get_permalink()) . '#article',
                'isPartOf' => [ ['@id' => trailingslashit(get_permalink()) . '#webpage'], ['@id' => home_url() . '#website'] ],
                'mainEntityOfPage' => ['@id' => trailingslashit(get_permalink()) . '#webpage'],
                'headline' => mb_substr(get_the_title(), 0, 110), 'description' => $meta_desc, 'image' => $image_array,
                'datePublished' => get_post_time('c', true, $post_id), 'dateModified' => get_post_modified_time('c', true, $post_id), 'dateCreated' => get_post_time('c', true, $post_id),
                'contentLocation' => $final_location, 'publisher' => [ '@id' => home_url() . '#organization' ],
                'articleSection' => $article_section, 'wordCount' => count(preg_split('/\s+/u', $clean_content, -1, PREG_SPLIT_NO_EMPTY)),
                'inLanguage' => $schema_lang, 'articleBody' => $clean_content,
                'isAccessibleForFree' => (get_option('nros_paywall_active', 'no') === 'yes') ? false : true
            ];

            if (!empty($about)) { $article['about'] = $about; }
            if (!empty($mentions)) { $article['mentions'] = $mentions; }
            if (!empty($keywords_array)) { $article['keywords'] = array_values(array_unique($keywords_array)); }

            if ($schema_type_formatted === 'NewsArticle' && $dynamic_place) { $article['dateline'] = $dynamic_place['name']; }
            $speakable_classes = get_option('newsai_speakable_selectors', '');
            if (!empty($speakable_classes)) { $selectors = array_filter(array_map('trim', explode(',', $speakable_classes))); if (!empty($selectors)) { $article['speakable'] = [ '@type' => 'SpeakableSpecification', 'cssSelector' => array_values($selectors) ]; } }

            $author_id = $post->post_author;
            if ($author_id && function_exists('nros_get_dynamic_author_entity')) {
                $author_data = nros_get_dynamic_author_entity($author_id);
                if ($author_data) { $graph[] = $author_data; $article['author'] = [ '@id' => trailingslashit(get_author_posts_url($author_id)) . '#author' ]; }
            }

            if (function_exists('nros_extract_video_schema')) { $video = nros_extract_video_schema($post->post_content, $post); if ($video) { $graph[] = $video; $article['video'] = ['@id' => trailingslashit(get_permalink()) . '#video']; } }

            $graph[] = $publisher; $graph[] = $website; $graph[] = $webpage; $graph[] = $breadcrumb; $graph[] = $article;
            $json_payload = wp_json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $output = "\n<script type=\"application/ld+json\">\n" . $json_payload . "\n</script>\n";
            set_transient($cache_key, $output, 30 * DAY_IN_SECONDS); echo $output;
        }
    }
}