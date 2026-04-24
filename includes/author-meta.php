<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Προσθήκη Πεδίων στο Προφίλ Χρήστη
add_action('show_user_profile', 'newsai_add_author_eeat_fields');
add_action('edit_user_profile', 'newsai_add_author_eeat_fields');
function newsai_add_author_eeat_fields($user) {
    ?>
    <h3>🚀 Newsroom OS: E-E-A-T & Schema Profile</h3>
    <p class="description" style="background: #e0f2fe; padding: 12px; border-left: 4px solid #3b82f6; color: #1e40af; border-radius: 4px; max-width: 800px;">
        <strong>💡 Σημαντικό για το SEO (Google E-E-A-T):</strong> Συμπληρώστε τα παρακάτω πεδία για να χτίσετε το Authority του συντάκτη. Το Newsroom OS θα τα ενσωματώσει αυτόματα στο "God-Tier" Semantic Schema του site σας!
    </p>
    <table class="form-table">
        <tr>
            <th><label for="nros_job_title">Job Title (Ιδιότητα)</label></th>
            <td>
                <input type="text" name="nros_job_title" id="nros_job_title" value="<?php echo esc_attr(get_the_author_meta('nros_job_title', $user->ID)); ?>" class="regular-text" placeholder="π.χ. Αρχισυντάκτης / Οικονομικός Αναλυτής" /><br>
                <span class="description">Η ιδιότητα του συντάκτη. Εμφανίζεται στο JSON-LD Schema.</span>
            </td>
        </tr>
        <tr>
            <th><label for="nros_alumni_of">Εκπαίδευση (Alumni Of)</label></th>
            <td>
                <input type="text" name="nros_alumni_of" id="nros_alumni_of" value="<?php echo esc_attr(get_the_author_meta('nros_alumni_of', $user->ID)); ?>" class="regular-text" placeholder="π.χ. National and Kapodistrian University of Athens | https://www.uoa.gr/" /><br>
                <span class="description">Μορφή: <code>Όνομα Πανεπιστημίου | URL</code> (Ο διαχωρισμός γίνεται με |)</span>
            </td>
        </tr>
        <tr>
            <th><label for="nros_knows_about">Εξειδίκευση (Knows About)</label></th>
            <td>
                <textarea name="nros_knows_about" id="nros_knows_about" rows="3" class="regular-text" placeholder="ΟΠΕΚΑ | https://www.wikidata.org/wiki/Q56276856&#10;ΣΥΝΤΑΞΕΙΣ | https://www.wikidata.org/wiki/Q179264"><?php echo esc_textarea(get_the_author_meta('nros_knows_about', $user->ID)); ?></textarea><br>
                <span class="description">1 ανά γραμμή. Μορφή: <code>Keyword | Wikidata/Wikipedia URL (προαιρετικό)</code>. <br>Δείχνει στη Google ότι ο συντάκτης είναι Expert σε αυτά τα θέματα.</span>
            </td>
        </tr>
        <tr>
            <th><label for="nros_social_facebook">Facebook Profile URL</label></th>
            <td>
                <input type="url" name="nros_social_facebook" id="nros_social_facebook" value="<?php echo esc_attr(get_the_author_meta('nros_social_facebook', $user->ID)); ?>" class="regular-text" /><br>
                <span class="description">Το Social Proof αποδεικνύει στη Google ότι είναι πραγματικό πρόσωπο.</span>
            </td>
        </tr>
        <tr>
            <th><label for="nros_social_twitter">X (Twitter) URL</label></th>
            <td><input type="url" name="nros_social_twitter" id="nros_social_twitter" value="<?php echo esc_attr(get_the_author_meta('nros_social_twitter', $user->ID)); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="nros_social_linkedin">LinkedIn URL</label></th>
            <td>
                <input type="url" name="nros_social_linkedin" id="nros_social_linkedin" value="<?php echo esc_attr(get_the_author_meta('nros_social_linkedin', $user->ID)); ?>" class="regular-text" /><br>
                <span class="description">Πολύ ισχυρό E-E-A-T σήμα για επαγγελματίες δημοσιογράφους.</span>
            </td>
        </tr>
    </table>
    <?php
}

// 2. Αποθήκευση των Πεδίων
add_action('personal_options_update', 'newsai_save_author_eeat_fields');
add_action('edit_user_profile_update', 'newsai_save_author_eeat_fields');
function newsai_save_author_eeat_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) return false;

    // 🚀 FIX: Έλεγχος isset() για αποφυγή PHP Notices (Audit Fix)
    if (isset($_POST['nros_job_title'])) {
        update_user_meta($user_id, 'nros_job_title', sanitize_text_field($_POST['nros_job_title']));
    }
    
    if (isset($_POST['nros_alumni_of'])) {
        update_user_meta($user_id, 'nros_alumni_of', sanitize_text_field($_POST['nros_alumni_of']));
    }
    
    if (isset($_POST['nros_knows_about'])) {
        update_user_meta($user_id, 'nros_knows_about', sanitize_textarea_field($_POST['nros_knows_about']));
    }
    
    if (isset($_POST['nros_social_facebook'])) {
        update_user_meta($user_id, 'nros_social_facebook', sanitize_url($_POST['nros_social_facebook']));
    }
    
    if (isset($_POST['nros_social_twitter'])) {
        update_user_meta($user_id, 'nros_social_twitter', sanitize_url($_POST['nros_social_twitter']));
    }
    
    if (isset($_POST['nros_social_linkedin'])) {
        update_user_meta($user_id, 'nros_social_linkedin', sanitize_url($_POST['nros_social_linkedin']));
    }
}