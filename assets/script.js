/**
 * Newsroom OS (v1.4.4) - Client Side Logic (Global i18n & Local AI Decision Engine)
 * Event-Driven Performance Update, Smart FAQ Engine, Inception Fixes
 */

function nrosGetTitle() {
    if (window.wp && wp.data && wp.data.select("core/editor")) {
        let t = wp.data.select("core/editor").getEditedPostAttribute('title');
        if (t) return t;
    }
    return jQuery("#title").val() || jQuery(".wp-block-post-title").text() || jQuery(".editor-post-title__input").val() || "";
}

function nrosGetContent() {
    if (window.wp && wp.data && wp.data.select("core/editor")) {
        let c = wp.data.select("core/editor").getEditedPostContent();
        if (c) return c;
    }
    if (typeof tinymce !== 'undefined' && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
        return tinymce.activeEditor.getContent();
    }
    return jQuery("#content").val() || "";
}

function nrosCopyToClipboard(text, btnElement, originalText) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            if(btnElement) { btnElement.text('✅'); setTimeout(() => { btnElement.html(originalText); }, 2000); }
        }).catch(() => { nrosFallbackCopy(text, btnElement, originalText); });
    } else { nrosFallbackCopy(text, btnElement, originalText); }
}

function nrosFallbackCopy(text, btnElement, originalText) {
    let textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0"; textArea.style.left = "0"; textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus(); textArea.select();
    try { document.execCommand('copy'); if(btnElement) btnElement.text('✅'); } catch (err) { }
    document.body.removeChild(textArea);
    if(btnElement) { setTimeout(() => { btnElement.html(originalText); }, 2000); }
}

function nrosInsertIntoEditor(html) {
    var isGutenberg = jQuery('body').hasClass('block-editor-page');
    if (isGutenberg && typeof wp !== 'undefined' && wp.blocks && wp.data) {
        var blockEditor = wp.data.dispatch('core/block-editor') || wp.data.dispatch('core/editor');
        if (blockEditor && typeof blockEditor.insertBlocks === 'function') {
            var block = wp.blocks.createBlock('core/html', { content: html });
            blockEditor.insertBlocks([block]); return;
        }
    }
    if (typeof tinymce !== 'undefined' && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
        tinymce.activeEditor.execCommand('mceInsertContent', false, html); return;
    }
    jQuery('#content').val(jQuery('#content').val() + '\n\n' + html);
}

jQuery(document).ready(function($) {
    var isGr = (typeof newsai_vars !== 'undefined' && newsai_vars.lang.indexOf('el') !== -1);

    // --- TABS (Index-based Logic) ---
    $(document).on('click', '.newsai-tab', function(e) {
        e.preventDefault();
        var $this = $(this);
        var parentTabs = $this.parent();
        var container = $this.closest('.postbox, .nros-sb-card, .newsai-card, .wrap, .newsai-section');
        if (container.length === 0) container = $this.parent().parent();
        
        var tabIndex = parentTabs.find('.newsai-tab').index($this);
        parentTabs.find('.newsai-tab').removeClass('active').css({'border-bottom': 'none', 'color': '#646970'});
        $this.addClass('active').css({'border-bottom': '2px solid #2271b1', 'color': '#2271b1'});
        
        var contents = container.find('.newsai-tab-content');
        contents.hide();
        var targetPane = contents.eq(tabIndex);
        targetPane.show();
        
        var isNews = $this.text().toLowerCase().includes('news');
        var isTrends = $this.text().toLowerCase().includes('trend');

        if (isNews && (targetPane.is(':empty') || targetPane.text().includes('Loading'))) { fetchNews(); } 
        else if (isTrends && (targetPane.is(':empty') || targetPane.text().includes('Loading'))) { fetchTrends(); }
    });

    $(document).on('change', '#newsai-trends-geo', function(e) { fetchTrends(); fetchNews(); });
    $(document).on('click', '#newsai-refresh-trends', function(e) { e.preventDefault(); fetchTrends(); fetchNews(); });

    // --- AJAX MARK AS DONE ---
    $(document).on('click', '#nros-mark-done-btn', function(e) {
        e.preventDefault();
        var btn = $(this); var taskId = btn.data('id');
        var orgText = btn.html();
        btn.html('⏳...').prop('disabled', true);
        $.post(newsai_vars.ajax_url, { action: 'newsai_mark_task_done', task_id: taskId, nonce: newsai_vars.nonce }, function(response) {
            if (response.success) { btn.closest('div').slideUp(); } else { btn.html(orgText).prop('disabled', false); }
        }).fail(function() { btn.html(orgText).prop('disabled', false); });
    });


    // --- 🚀 THE DECISION ENGINE: PUBLISH CONFIDENCE & NEXT BEST ACTION ---
    let nrosLastEvalHash = '';
    function nrosEvaluateArticle() {
        if (!$('#nros-pub-score').length) return;
        
        let title = nrosGetTitle() || '';
        let content = nrosGetContent() || '';
        
        // 💡 GOD-MODE: Βρίσκει ΟΛΑ τα textareas που ανήκουν σε Rank Math / Yoast
        let seoTextForHash = '';
        jQuery('textarea').each(function() {
            if (this.id !== 'content' && jQuery(this).closest('[class*="rank-math"], [id*="rank-math"], [class*="yoast"], [id*="yoast"]').length > 0) {
                seoTextForHash += jQuery(this).val() || '';
            }
        });
        seoTextForHash += jQuery('#excerpt').val() || '';
        
        let evalHash = title + content + seoTextForHash.length;
        if (evalHash === nrosLastEvalHash && evalHash !== '') return;
        nrosLastEvalHash = evalHash;

        let score = 0;
        let actions = [];

        let hasMeta = false;
        
        // 1. Έλεγχος στο κλασικό "Απόσπασμα"
        let wpExcerpt = jQuery('#excerpt').val() || '';
        if (wpExcerpt.trim().length > 10) hasMeta = true;

        // 2. Έλεγχος στον Block Editor (Gutenberg)
        if (!hasMeta && window.wp && wp.data && wp.data.select('core/editor')) {
            let metaData = wp.data.select('core/editor').getEditedPostAttribute('meta');
            let excerptData = wp.data.select('core/editor').getEditedPostAttribute('excerpt');
            
            if (metaData && metaData._yoast_wpseo_metadesc && metaData._yoast_wpseo_metadesc.trim().length > 10) hasMeta = true;
            if (metaData && metaData.rank_math_description && metaData.rank_math_description.trim().length > 10) hasMeta = true;
            if (typeof excerptData === 'string' && excerptData.trim().length > 10) hasMeta = true;
        }

        // 3. ΖΩΝΤΑΝΟΣ ΕΛΕΓΧΟΣ (Classic Editor + React Modals)
        if (!hasMeta) {
            jQuery('textarea').each(function() {
                let val = jQuery(this).val() || '';
                if (this.id !== 'content' && val.trim().length > 10) {
                    if (jQuery(this).closest('[class*="rank-math"], [id*="rank-math"], [class*="yoast"], [id*="yoast"]').length > 0) {
                        hasMeta = true;
                    }
                }
            });
        }
        
        if (hasMeta) { 
            score += 25; 
            $('#check-meta').html('<span class="nros-icon-wrapper">📝</span> <span style="color:#00a32a;">' + (isGr ? 'Meta Description OK' : 'Meta Description OK') + '</span>'); 
        } else { 
            actions.push({ prio: 'HIGH', impact: 1, text: isGr ? 'Πρόσθεσε Meta Description ή Σύνοψη (Excerpt)' : 'Add a Meta Description or Excerpt' });
            $('#check-meta').html('<span class="nros-icon-wrapper">📝</span> <span style="color:#d63638;">' + (isGr ? 'Λείπει Meta Description' : 'Meta Description Missing') + '</span>'); 
        }

        let wordCount = content.replace(/<\/?[^>]+(>|$)/g, "").split(/\s+/).filter(word => word.length > 0).length;
        if (wordCount >= 300) { 
            score += 20; 
            $('#check-words').html('<span class="nros-icon-wrapper">✍️</span> <span style="color:#00a32a;">' + (isGr ? 'Λέξεις OK' : 'Word Count OK') + ' (' + wordCount + ')</span>'); 
        } else { 
            actions.push({ prio: 'HIGH', impact: 2, text: isGr ? 'Γράψε περισσότερο κείμενο (τουλάχιστον 300 λέξεις)' : 'Write more content (min 300 words)' });
            $('#check-words').html('<span class="nros-icon-wrapper">✍️</span> <span style="color:#d63638;">' + (isGr ? 'Πολύ μικρό κείμενο' : 'Content too short') + ' (' + wordCount + '/300)</span>'); 
        }

        let len = title.length;
        if (len >= 40 && len <= 70) { 
            score += 20; 
            $('#hc-length').html('<span class="nros-icon-wrapper">⏳</span> <span style="color:#00a32a;">' + (isGr ? 'Μήκος Τίτλου OK' : 'Title Length OK') + '</span>'); 
        } else { 
            actions.push({ prio: 'HIGH', impact: 3, text: isGr ? 'Διόρθωσε το μήκος του τίτλου (40-70 γράμματα)' : 'Fix title length (40-70 chars)' });
            $('#hc-length').html('<span class="nros-icon-wrapper">⏳</span> <span style="color:#d63638;">' + (isGr ? 'Λάθος μήκος τίτλου' : 'Bad title length') + '</span>'); 
        }

        let linkCount = (content.match(/<a href=/g) || []).length;
        if (linkCount >= 2) { 
            score += 15; 
            $('#check-links').html('<span class="nros-icon-wrapper">🔗</span> <span style="color:#00a32a;">' + (isGr ? 'Εσωτερικά Links OK' : 'Internal Links OK') + ' (' + linkCount + ')</span>'); 
        } else { 
            actions.push({ prio: 'MEDIUM', impact: 4, text: isGr ? 'Πρόσθεσε τουλάχιστον 2 σχετικά άρθρα (Links)' : 'Add at least 2 internal links' });
            $('#check-links').html('<span class="nros-icon-wrapper">🔗</span> <span style="color:#d63638;">' + (isGr ? 'Λείπουν Εσωτερικά Links' : 'Internal Links Missing') + ' (' + linkCount + '/2)</span>'); 
        }

        if (/\d/.test(title)) { 
            score += 10; 
            $('#hc-number').html('<span class="nros-icon-wrapper">🔢</span> <span style="color:#00a32a;">' + (isGr ? 'Περιέχει Αριθμό' : 'Contains Number') + '</span>'); 
        } else { 
            actions.push({ prio: 'LOW', impact: 5, text: isGr ? 'Βάλε έναν αριθμό στον τίτλο για +30% CTR' : 'Add a number to title for +30% CTR' });
            $('#hc-number').html('<span class="nros-icon-wrapper">🔢</span> <span style="color:#646970;">' + (isGr ? 'Χωρίς Αριθμό' : 'No number in title') + '</span>'); 
        }

        if (/[!?]/.test(title)) { 
            score += 10; 
            $('#hc-power').html('<span class="nros-icon-wrapper">⚡</span> <span style="color:#00a32a;">' + (isGr ? 'Power Character OK' : 'Power Character OK') + '</span>'); 
        } else { 
            actions.push({ prio: 'LOW', impact: 6, text: isGr ? 'Βάλε ένα Power Symbol (! ή ?) στον τίτλο' : 'Add a Power Symbol (! or ?) to title' });
            $('#hc-power').html('<span class="nros-icon-wrapper">⚡</span> <span style="color:#646970;">' + (isGr ? 'Χωρίς Power Character' : 'No Power Character') + '</span>'); 
        }

        let color = score >= 85 ? '#10b981' : (score >= 60 ? '#f59e0b' : '#dc2626');
        $('#nros-pub-score').text(score + '%').css('color', color);
        $('#nros-pub-bar').css({'width': score + '%', 'background': color});

        let actionBox = $('#nros-next-action-box');
        let actionBadge = $('#nros-action-priority');
        let actionText = $('#nros-next-action-text');

        if (score === 100 || actions.length === 0) {
            actionBox.css({'background':'#ecfdf5', 'border-color':'#a7f3d0', 'border-left-color':'#10b981'});
            actionBadge.text('READY').css('background', '#10b981');
            actionText.text(isGr ? 'Όλα τέλεια! Είσαι έτοιμος για δημοσίευση.' : 'Everything looks perfect! Ready to publish.').css('color', '#065f46');
        } else {
            actions.sort((a, b) => a.impact - b.impact); 
            let bestAction = actions[0];
            
            actionText.text(bestAction.text);
            
            if (bestAction.prio === 'HIGH') {
                actionBox.css({'background':'#fef2f2', 'border-color':'#fecaca', 'border-left-color':'#dc2626'});
                actionBadge.text(isGr ? 'ΥΨΗΛΗ ΠΡΟΤΕΡΑΙΟΤΗΤΑ' : 'HIGH PRIORITY').css('background', '#dc2626');
                actionText.css('color', '#991b1b');
            } else if (bestAction.prio === 'MEDIUM') {
                actionBox.css({'background':'#fffbeb', 'border-color':'#fde68a', 'border-left-color':'#f59e0b'});
                actionBadge.text(isGr ? 'ΜΕΣΑΙΑ' : 'MEDIUM').css('background', '#d97706');
                actionText.css('color', '#92400e');
            } else {
                actionBox.css({'background':'#f8f9fa', 'border-color':'#e2e4e7', 'border-left-color':'#646970'});
                actionBadge.text(isGr ? 'ΧΑΜΗΛΗ' : 'LOW').css('background', '#646970');
                actionText.css('color', '#50575e');
            }
        }
    }

    // --- 🧠 LOCAL AI: TAG SUGGESTER (Upgraded Smart Engine) ---
    // --- 🧠 LOCAL AI: TAG SUGGESTER (Upgraded Smart Engine) ---
    let nrosLastText = '';
    function nrosSuggestTags() {
        if(typeof nros_site_tags === 'undefined') return;

        let rawTitle = nrosGetTitle() || '';
        // Προστασία #1: Διαβάζουμε μόνο τους πρώτους 2000 χαρακτήρες για να μην παγώσει ο browser σε τεράστια κείμενα
        let rawContent = nrosGetContent().replace(/<[^>]*>?/gm, '').substring(0, 2000); 
        
        // 🚀 FIX: Προστασία #2 - Αν το κείμενο είναι πολύ μικρό (< 100 γράμματα), μην τρέχεις το AI Regex Loop!
        if (rawContent.length < 100) {
            let placeholder = isGr ? 'Γράψτε κείμενο για προτάσεις...' : 'Write text for suggestions...';
            $('#nros-tag-suggestions').html('<span style="font-size:11px; color:#a7aaad; font-style:italic;">'+placeholder+'</span>');
            return;
        }

        let textToAnalyze = (rawTitle + " " + rawContent);

        if(textToAnalyze === nrosLastText) return;
        nrosLastText = textToAnalyze;

        let matches = [];

        // Βοηθητική συνάρτηση για escape χαρακτήρων στο Regex
        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        let titleLower = rawTitle.toLowerCase();

        nros_site_tags.forEach(function(tag) {
            let tagName = tag.name.trim();

            // 1. Αγνοούμε εντελώς τα "σκουπίδια" του 1 γράμματος
            if (tagName.length < 2) return;

            // 2. Έξυπνη αναζήτηση με Word Boundaries για να μην πιάνει υπολέξεις (π.χ. "ΓΗ" στο "προηγήθηκε")
            let regexStr = '(^|\\s|[.,!?;:\'"()\\-\\[\\]])' + escapeRegExp(tagName) + '(?=\\s|[.,!?;:\'"()\\-\\[\\]]|$)';
            let regex = new RegExp(regexStr, 'gi');

            let matchArray = textToAnalyze.match(regex);
            let matchCount = matchArray ? matchArray.length : 0;

            if (matchCount > 0) {
                let score = matchCount;
                
                // 3. Βαθμολογία (Scoring): Δίνουμε τεράστιο μπόνους αν το Tag υπάρχει στον Τίτλο!
                if (titleLower.includes(tagName.toLowerCase())) {
                    score += 10;
                }
                
                matches.push({ data: tag, score: score });
            }
        });

        // 4. Ταξινομούμε με βάση το Score (Φέρνουμε τα πιο σχετικά πάνω)
        matches.sort((a, b) => b.score - a.score);

        if(matches.length > 0) {
            let html = '';
            // Παίρνουμε τα top 5
            matches.slice(0, 5).forEach(function(m) {
                let tag = m.data;
                // Βάλαμε το data-id για να το περνάμε κατευθείαν στον Gutenberg!
                html += '<span class="nros-pill-btn nros-add-tag-btn" data-id="'+tag.id+'" data-name="'+tag.name+'" title="Προσθήκη στο άρθρο">+ '+tag.name+'</span>';
            });
            $('#nros-tag-suggestions').html(html);
        } else {
            let placeholder = isGr ? 'Γράψτε κείμενο για προτάσεις...' : 'Write text for suggestions...';
            $('#nros-tag-suggestions').html('<span style="font-size:11px; color:#a7aaad; font-style:italic;">'+placeholder+'</span>');
        }
    }


    // ========================================================
    // 🚀 THE MASTER EVENT LISTENER (Hybrid Gutenberg & Classic)
    // ========================================================
    let nrosEvalTimer;
    function nrosTriggerUpdate() {
        clearTimeout(nrosEvalTimer);
        nrosEvalTimer = setTimeout(function() {
            nrosEvaluateArticle();
            nrosSuggestTags();
        }, 1000); // 1 δευτερόλεπτο "ησυχίας" πριν τον έλεγχο
    }

    // 1. Για τον Classic Editor, τα SEO Meta Boxes και τον Τίτλο
    $(document).on('keyup change', 'textarea, input, [contenteditable="true"]', nrosTriggerUpdate);

    // 2. Η "Native" σύνδεση με τον Gutenberg (React State)
    // Παρακολουθεί κάθε αλλαγή στα blocks χωρίς να "γονατίζει" τον browser
    if (typeof wp !== 'undefined' && wp.data && wp.data.subscribe) {
        wp.data.subscribe(function() {
            nrosTriggerUpdate();
        });
    }

    // 3. Αρχική φόρτωση
    setTimeout(nrosTriggerUpdate, 1500);
    // ========================================================


    // --- 🚀 QUICK BLOCKS (HYBRID NLP & INCEPTION FIX) ---
    $(document).on('click', '.nros-quick-block', function(e) {
        e.preventDefault();
        var type = $(this).data('type');
        var contentText = nrosGetContent().replace(/<\/?[^>]+(>|$)/g, " ").replace(/\s+/g, ' ').trim();
        var html = '';

        if (type === 'faq') {
            var sentences = contentText.match(/[^.?!;\u037E\n]+[.?!;\u037E]+/g) || [];
            var questionsMatch = [];
            
            var qWordsGr = ['τι', 'πώς', 'πως', 'ποιος', 'ποια', 'ποιο', 'ποιες', 'ποιους', 'πόσο', 'ποσο', 'πόσα', 'ποσα', 'γιατί', 'γιατι', 'πότε', 'ποτε', 'πού', 'που', 'μήπως', 'μηπως', 'άραγε'];
            var qWordsEn = ['what', 'how', 'why', 'who', 'when', 'where', 'is', 'are', 'do', 'does', 'can', 'will', 'should', 'could', 'would'];
            var activeQWords = isGr ? qWordsGr : qWordsEn;

            sentences.forEach(function(s) {
                var cleanS = s.trim();
                if (cleanS.length < 10 || cleanS.length > 200) return; 
                
                var hasQuestionMark = /[?;¿\u037E]/.test(cleanS);
                
                var firstWord = cleanS.split(' ')[0].toLowerCase().replace(/[^a-zα-ωάέήίόύώ]/g, '');
                var hasQuestionWord = activeQWords.includes(firstWord);

                if (hasQuestionMark || hasQuestionWord) {
                    if (!/[?;¿\u037E]['"”)]*$/.test(cleanS)) { 
                        cleanS = cleanS.replace(/[.!,]+$/, '') + (isGr ? ';' : '?'); 
                    }
                    questionsMatch.push(cleanS);
                }
            });
            
            var q1 = isGr ? 'Ερώτηση 1;' : 'Question 1?';
            var q2 = isGr ? 'Ερώτηση 2;' : 'Question 2?';
            var ans = isGr ? 'Απάντηση εδώ...' : 'Answer here...';
            var title = isGr ? 'Συχνές Ερωτήσεις (FAQ)' : 'Frequently Asked Questions (FAQ)';

            if (questionsMatch.length > 0) q1 = questionsMatch[0];
            if (questionsMatch.length > 1) q2 = questionsMatch[1];

            html = '<div style="background:#f9fafb; padding:20px; border-radius:8px; border:1px solid #e5e7eb; margin:20px 0;" itemscope itemtype="https://schema.org/FAQPage">';
            html += '<h3 style="margin-top:0; margin-bottom:15px; color:#111827; font-size:18px;">' + title + '</h3>';
            html += '<div itemscope itemprop="mainEntity" itemtype="https://schema.org/Question" style="margin-bottom:15px;">';
            html += '<strong itemprop="name" style="color:#1d2327;">' + q1 + '</strong>';
            html += '<div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer" style="margin-top:5px;">';
            html += '<p itemprop="text" style="margin:0; color:#50575e;">' + ans + '</p></div></div>';
            html += '<div itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">';
            html += '<strong itemprop="name" style="color:#1d2327;">' + q2 + '</strong>';
            html += '<div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer" style="margin-top:5px;">';
            html += '<p itemprop="text" style="margin:0; color:#50575e;">' + ans + '</p></div></div></div>';

        } else if (type === 'keypoints') {
            
            var cleanText = contentText.replace(/(💡|Key Takeaways:|Βασικά Σημεία:)/gi, ' ');
            var sentences = cleanText.split(/[.?!;\n:]/).map(s => s.trim()).filter(s => s.length > 40 && s.length < 350);
            
            var s1 = isGr ? 'Σημείο 1...' : 'Point 1...';
            var s2 = isGr ? 'Σημείο 2...' : 'Point 2...';
            var title = isGr ? 'Βασικά Σημεία:' : 'Key Takeaways:';

            if (sentences.length > 0) {
                sentences.sort((a, b) => b.length - a.length);
                s1 = sentences[0] + '.';
                if(sentences.length > 1) s2 = sentences[1] + '.';
            }

            html = '<div style="background:#f0f6fc; border-left:4px solid #2271b1; padding:15px; border-radius:4px; margin:20px 0;">';
            html += '<strong style="color:#1d2327; display:block; margin-bottom:10px;">💡 ' + title + '</strong>';
            html += '<ul style="margin:0; padding-left:20px; color:#50575e;">';
            html += '<li style="margin-bottom:5px;">' + s1 + '</li>';
            html += '<li>' + s2 + '</li></ul></div>';
        }
        nrosInsertIntoEditor(html);
    });

    // --- 1-CLICK TAG ADDER (Hybrid: Gutenberg & Classic Editor - No Scroll!) ---
    $(document).on('click', '.nros-add-tag-btn', function(e) {
        e.preventDefault();
        let btn = $(this);
        let name = btn.data('name');
        let tagId = parseInt(btn.data('id'), 10);
        let originalText = btn.html();
        
        // Συνάρτηση για Οπτική Επιβεβαίωση (Πράσινο χρώμα - Χωρίς Scroll)
        function showSuccess() {
            btn.text('✅ Προστέθηκε');
            btn.css({'background':'#10b981', 'color':'#fff', 'border-color':'#10b981'});
            setTimeout(() => {
                btn.html(originalText);
                btn.css({'background':'', 'color':'', 'border-color':''});
            }, 2000);
        }

        // 1. Gutenberg (Block Editor) Logic - Native React (No Scroll)
        if (window.wp && wp.data && wp.data.select("core/editor") && wp.data.dispatch("core/editor")) {
            let currentTags = wp.data.select("core/editor").getEditedPostAttribute('tags') || [];
            if (!currentTags.includes(tagId)) {
                let newTags = [...currentTags, tagId];
                wp.data.dispatch("core/editor").editPost({ tags: newTags });
            }
            showSuccess();
        } 
        // 2. Classic Editor Logic - Silent AJAX (No Scroll)
        else if (jQuery('#tax-input-post_tag').length || jQuery('#new-tag-post_tag').length) {
            // Βρίσκουμε το κρυφό πεδίο που κρατάει τα Tags του Classic Editor
            let tagField = jQuery('#tax-input-post_tag');
            let currentTagsStr = tagField.val() || '';
            
            // Φτιάχνουμε Array από το String (π.χ. "Tag1, Tag2")
            let currentTagsArray = currentTagsStr.split(',').map(t => t.trim()).filter(t => t.length > 0);
            
            if (!currentTagsArray.includes(name)) {
                currentTagsArray.push(name);
                let newTagsStr = currentTagsArray.join(', ');
                
                // Ενημερώνουμε το κρυφό πεδίο (έτσι ώστε να σωθούν τα tags όταν πατηθεί "Ενημέρωση Άρθρου")
                tagField.val(newTagsStr);
                
                // Ενημερώνουμε το οπτικό UI του Classic Editor (ώστε να βλέπει ο χρήστης το Tag)
                if (typeof window.tagBox !== 'undefined') {
                    // Αυτό προσθέτει το Tag στο UI χωρίς να κάνει trigger το focus/scroll!
                    jQuery('.tagchecklist').append('<li><button type="button" id="post_tag-check-num-'+Date.now()+'" class="ntdelbutton"><span class="remove-tag-icon" aria-hidden="true"></span><span class="screen-reader-text">Remove term: '+name+'</span></button>&nbsp;'+name+'</li>');
                    tagBox.quickClicks(jQuery('#post_tag')); // Επανασυνδέει τα κουμπιά διαγραφής
                }
            }
            showSuccess();
        } 
        // 3. Fallback
        else {
            nrosCopyToClipboard(name, btn, '+ ' + name);
        }
    });

    // --- INTERNAL LINKS UI ---
    $(document).on('click', '#newsai-search-links', function(e) {
        e.preventDefault();
        var keyword = $('#newsai-keyword').val().trim();
        if (!keyword) return;
        $.post(newsai_vars.ajax_url, { action: 'newsai_get_related_posts', keyword: keyword, post_id: newsai_vars.post_id, nonce: newsai_vars.nonce }, function(response) {
            if (response.success) {
                var html = '';
                $.each(response.data, function(i, post) { html += '<label style="display:block; margin-bottom:5px;"><input type="checkbox" class="newsai-link-cb" value="' + post.url + '" data-title="' + post.title + '"> ' + post.title + '</label>'; });
                $('#newsai-links-results').html(html).slideDown(); $('#newsai-insert-links').show();
            }
        });
    });

    $(document).on('click', '#newsai-insert-links', function(e) {
        e.preventDefault();
        var selected = $('.newsai-link-cb:checked');
        var html = '<div class="newsroom-os-story-cluster"><ul>';
        selected.each(function() { html += '<li><a href="' + $(this).val() + '">' + $(this).data('title') + '</a></li>'; });
        html += '</ul></div>';
        nrosInsertIntoEditor(html);
    });

    // --- MANUAL TIMELINE INSERT ---
    $(document).on('click', '#newsai-insert-timeline', function(e) {
        e.preventDefault();
        var btn = $(this); var originalText = btn.html();
        btn.html('⏳...').prop('disabled', true);
        $.post(newsai_vars.ajax_url, { action: 'newsai_build_timeline', post_id: newsai_vars.post_id, tag_id: 0, keyword: '', nonce: newsai_vars.nonce }, function(response) {
            btn.html(originalText).prop('disabled', false);
            if (response.success) { nrosInsertIntoEditor(response.data); } 
            else { alert(isGr ? 'Δεν βρέθηκαν αρκετά σχετικά άρθρα.' : 'Not enough context found.'); }
        }).fail(function() { btn.html(originalText).prop('disabled', false); });
    });
    
    $(document).on('click', '.nros-copy-task-btn', function(e) {
        e.preventDefault();
        var btn = $(this); var text = btn.data('title'); var originalText = btn.html();
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => { btn.html('✅'); setTimeout(() => { btn.html(originalText); }, 2000); }).catch(() => { btn.html('❌'); setTimeout(() => { btn.html(originalText); }, 2000); });
        } else {
            let textArea = document.createElement("textarea"); textArea.value = text; textArea.style.position = "fixed"; textArea.style.top = "0"; textArea.style.left = "0"; document.body.appendChild(textArea); textArea.focus(); textArea.select();
            try { document.execCommand('copy'); btn.html('✅'); } catch (err) { btn.html('❌'); }
            document.body.removeChild(textArea); setTimeout(() => { btn.html(originalText); }, 2000);
        }
    });

    // --- 🚀 PROMPTS (Extended Limit 25.000 Chars) ---
    $(document).on('click', '.newsai-prompt-btn', function(e) {
        e.preventDefault(); e.stopPropagation();
        var btn = $(this); var type = btn.data('type'); var originalText = btn.html();
        var title = nrosGetTitle(); 
        
        var content = nrosGetContent().replace(/<\/?[^>]+(>|$)/g, " ").replace(/\s+/g, ' ').trim().substring(0, 25000); 
        
        btn.html('⏳...');
        $.post(newsai_vars.ajax_url, { action: 'newsai_get_prompt', type: type, post_id: newsai_vars.post_id, title: title, content: content, nonce: newsai_vars.nonce }, function(response) {
            if (response.success) nrosCopyToClipboard(response.data, btn, originalText); else btn.html(originalText);
        });
    });

    // --- FETCH DATA (Trends & News) ---
    function fetchNews() {
        var geo = $('#newsai-trends-geo').length ? $('#newsai-trends-geo').val() : (typeof newsai_vars !== 'undefined' ? newsai_vars.geo : 'GR');
        var targets = $('#nros-pane-news, #newsai-news-list, #sidebar-news-list');
        
        targets.html('<div style="text-align:center; padding:15px; color:#a7aaad;">⏳ Loading...</div>');
        $.post(newsai_vars.ajax_url, { action: 'newsai_get_news', geo: geo, nonce: newsai_vars.nonce }, function(response) {
            if(response.success) { targets.html(response.data); } 
            else { targets.html('<div style="color:#d63638; text-align:center; padding:10px;">⚠️ Error.</div>'); }
        });
    }

    function fetchTrends() {
        var geo = $('#newsai-trends-geo').length ? $('#newsai-trends-geo').val() : (typeof newsai_vars !== 'undefined' ? newsai_vars.geo : 'GR');
        var targets = $('#nros-pane-trends, #newsai-trends-list, #sidebar-trends-list');
        
        targets.html('<div style="text-align:center; padding:15px; color:#a7aaad;">⏳ Loading...</div>');
        $.post(newsai_vars.ajax_url, { action: 'newsai_get_trends', geo: geo, nonce: newsai_vars.nonce }, function(response) {
            if(response.success) { targets.html(response.data); } 
            else { targets.html('<div style="color:#d63638; text-align:center; padding:10px;">⚠️ Error.</div>'); }
        });
    }

    fetchTrends();
});