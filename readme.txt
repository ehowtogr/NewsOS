=== Newsroom OS – Editorial Control & AI Assistant ===
Contributors: koskar22
Tags: newsroom, editorial workflow, google discover seo, schema markup, ai content assistant, internal linking
Requires at least: 5.8
Tested up to: 6.9.4
Requires PHP: 7.4
Stable tag: 1.4.4
License: GPLv2 or later

Run your entire newsroom inside WordPress. Assign stories, guide your writers, automatically generate AI-Ready Schema (NewsArticle), and publish faster for Google Discover & Search.

== Description ==

**The Operating System for Modern Publishers**

Newsroom OS transforms WordPress into a complete editorial command center. Instead of juggling external task managers and fragmented SEO tools, you get a unified platform built specifically for high-traffic news websites and content teams.

---

## 🚀 100% Compatible with Yoast SEO & Rank Math
Newsroom OS does not try to replace your favorite SEO plugin. It acts as an **Enterprise Schema Extension**. 
By bridging the gap between editorial work and technical SEO, Newsroom OS automatically reads data from Yoast or Rank Math and builds a "God-Tier" Semantic Knowledge Graph (JSON-LD) that connects your Authors, Tags, Geo-Locations, and Articles.

---

## 🧠 The Decision Engine (Your biggest advantage)

Stop overwhelming writers with checklists. Newsroom OS analyzes every article in real-time and shows:

- **Publish Confidence Score (0–100%)**
- **Next Best Action** (what to fix first)
- Live SEO and structure insights

👉 Writers always know exactly what to do next without leaving the Gutenberg or Classic Editor.

---

## ✍️ Built for Editorial Teams

Manage your entire newsroom from one place:
- Assign stories with deadlines and priority
- Track active and overdue tasks
- Monitor competitor content via Live RSS feeds
- Detect missing SEO data instantly across your site

---

## 🤖 Smart Writer Assistant (Inside the Editor)

A powerful sidebar that helps every writer perform better:
- Headline Score (optimize titles instantly for CTR)
- **Story Timeline:** Automatically link related articles into a visual, chronological timeline that improves internal linking and user engagement.
- SEO checks (live meta description, links, length)
- Smart Tag suggestions with **1-Click Silent Insertion**
- Internal link suggestions (Insert "Read Also" clusters instantly)
- Trend Radar (Live Google Trends integrated)
- Quick actions (Auto-generate FAQ Blocks, Key Points, Timelines)

---

## ⚡ Built for AI Search (AIO) & Voice SEO

Newsroom OS generates the most advanced Schema Markup available on WordPress:
- **Hybrid Knowledge Graph:** Manually define Tag Entity Types (Person, Place, Organization) or let the built-in AI auto-detect them.
- **Local SEO Mastery:** Add Latitude/Longitude to Tags. Your articles automatically generate precise "dateline" and GeoCoordinates markup.
- **Voice SEO:** Built-in `Speakable` schema integration for Google Assistant and Smart Speakers.
- **Full Article Parsing:** Serves complete, clean JSON-LD (NewsArticle / Article) tailored for Large Language Models (LLMs) and Search Generative Experience (SGE).

---

## 🛡️ E-E-A-T & Author Authority

- Advanced author profiles (Job Title, Education, Social Links)
- Dynamic expertise mapping (`knowsAbout` with Wikidata linking)
- Stronger trust signals mapped directly into the core Schema of every article.

---

## ⚡ Performance First

- Lightweight and insanely fast.
- Built for high-traffic news websites.
- Event-driven Javascript (Zero CPU idle load in the editor).
- Smart Database caching system (no bloat).

👉 Faster publishing, better content, full control. All inside WordPress.

---

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/newsroom-os` directory, or install through the WordPress plugins screen.
2. Activate the plugin.
3. Run the onboarding wizard to configure your team and Schema Engine.
4. Start assigning tasks and optimizing your content.

---

== Frequently Asked Questions ==

= Does it work alongside Yoast SEO or Rank Math? =
Yes! Newsroom OS is designed to complement your existing SEO plugins. It automatically reads their meta data and uses it to build a highly advanced Semantic Knowledge Graph (JSON-LD), without causing any conflicts.

= Does the ItemList (Carousel) schema work with Page Builders? =
Absolutely. If you use Elementor, WPBakery, Divi, or any other page builder for your custom news feeds, you can specify the page IDs in the settings. The plugin will automatically generate the required CollectionPage and ItemList schema for Google's Top Stories carousel.

= Will the Auto-Timeline slow down my site or bloat my database? =
No. The Story Timeline uses an intelligent, lightweight caching system (Transients) and event-driven JavaScript, ensuring zero CPU idle load on your server and lightning-fast load times. It does not create messy shortcodes in your database unless manually triggered.

= Does it work with the Classic Editor and Gutenberg? =
Yes. The entire Sidebar Assistant, including the 1-Click Tag insertion and Real-Time Decision Engine, is fully compatible with both the WordPress Classic Editor and the Gutenberg Block Editor.

---

== Screenshots ==

1. The Newsroom OS Decision Engine inside the WordPress Editor.
2. Assigning tasks and tracking team performance in the Editorial Dashboard.
3. Real-time Google Trends and Competitor RSS tracking.
4. The Hybrid Entity Engine settings for precise Schema markup.
5. Auto-generated Story Timelines for maximum user engagement.

---
---

== 🚀 Roadmap: What's Coming Next ==

Newsroom OS is under active, heavy development. Here is a sneak peek at what we are building for the upcoming versions:

* **Live Event SEO:** 1-Click transformation of articles into `LiveBlogPosting` schema for breaking news and real-time coverage.
* **Content Decay Radar:** Automated background checks that alert the editorial team when high-performing evergreen articles need refreshing.
* **Google Discover Image Validator:** Real-time checks inside the editor to ensure your Featured Image meets Google's strict 1200px requirement for Discover feeds.
* **Instant Indexing:** Direct integration with the Google Indexing API to push breaking news to search results in seconds, not hours.

---
== Changelog ==
= 1.4.4 =
🌍 **The Global Expansion & Enterprise Update**

* NEW (Markets): Added 8 new Geo-Targets for the live Trends & News engine (Canada, Australia, Brazil, Mexico, Japan, Sweden, Switzerland, South Africa).
* NEW (Editorial): Included complete, localized Holiday & Event calendars (JSON) for all new regions, available for 1-click bulk import into your editorial dashboard.
* FIX (API): Implemented a smart bypass for the Google Search Status API. The dashboard will now automatically ignore "stuck" Core Update alerts that Google has left unresolved for over 45 days.
* PERFORMANCE (Editor): Completely eliminated UI lag in the Gutenberg/Classic editor on sites with 10,000+ tags, by strictly limiting the AI Suggester payload to the top 500 most impactful terms.
* PERFORMANCE (Database): Replaced heavy `GROUP BY` operations with `SELECT DISTINCT` in the Timeline Engine, drastically reducing temporary disk usage during full table scans.
* PERFORMANCE (Dashboard): Added strict pagination limits (LIMIT 100) to the active tasks query to prevent PHP memory exhaustion on enterprise sites with thousands of alerts.

= 1.4.3.3 =
🚀 **The "State of Perfection" Update**

* FIX (Sync): Full synchronization of version 1.4.3.3 across all files (PHP, JS, CSS) for proper cache busting.
* FIX (Real-time): The Editor Sidebar is now 100% live. Removed transient caching from Tasks for instant synchronization between the Admin dashboard and the Editor.
* ENHANCEMENT (Performance): The AI Tag Suggester now strictly activates only after 100 characters of text are written, ensuring zero browser lag even with 4,000 tags loaded in memory.
* ENHANCEMENT (SEO): Full integration of the "Primary City" field into the Schema engine for automated `areaServed` and `location` markup generation (Local SEO).
* ENHANCEMENT (UI): Dynamic translation (English/Greek) of the settings fields based on the active site language.
* PERFORMANCE: Optimized Composite SQL Indexes (`idx_author_status`, `idx_status_date`) for enterprise-grade query speed on large datasets.
* HYGIENE: Automated weekly database cleanup routine that quietly removes completed tasks older than 90 days, keeping the database fast and lean.

= 1.4.3.2 =
🚀 **The Ultimate Enterprise Performance Update**

* FIX (Performance): Massively optimized the Gutenberg and Classic editor load times (TTFB) for high-traffic news websites. The Tag Suggester AI now strictly limits its payload to the top 500 most-used tags and utilizes a 12-hour Transient Cache. This eliminates PHP memory exhaustion and UI lag on mature sites with tens of thousands of tags.
* FIX (Database): Added a new Composite SQL Index (`idx_author_status`) to the alerts table. This prevents full-table scans and ensures lightning-fast editorial task retrieval, even if your site has 10,000+ active alerts.
* FIX (Scalability): Optimized user selection queries in the Admin Dashboard. The system now limits dropdowns to 100 active authors, ensuring the settings page never hangs on sites with massive user bases.
* FIX (UX): Resolved an issue where the Onboarding Wizard redirect wouldn't correctly trigger upon fresh plugin activation.
* ENHANCEMENT: Tag suggestions are now significantly more accurate. By utilizing the 500 highest-count tags, the AI naturally filters out typos and one-off terms, suggesting only high-value entities for your Knowledge Graph.
* MAINTENANCE: Added an automatic weekly database cleanup routine that quietly removes completed tasks older than 90 days, keeping your database lean and fast.
* SYSTEM: Implemented a seamless background database updater. The plugin now automatically applies new SQL indexes upon updating, without requiring users to deactivate and reactivate the plugin.

= 1.4.3.1 =
* FIX: Resolved an issue where the onboarding wizard redirect wouldn't trigger upon fresh plugin activation.

= 1.4.3 =
🔥 **The "Hybrid Enterprise" Update (AI, Voice & Performance)**

* NEW (SEO): Hybrid Knowledge Graph! You can now manually define Tag Entity Types (Person, Place, Organization, etc.) or let the AI auto-detect them.
* NEW (Local SEO): Added Geo-Coordinates (Latitude/Longitude) directly to Tags. Your articles now automatically generate "dateline" and precise local schema markup.
* NEW (Voice SEO): Added "Speakable" Schema support. Define CSS classes in settings to make your news articles Google Assistant & Smart Speaker ready.
* NEW (SEO): Added support for ItemList (Carousel) generation even on Custom Feed Pages built with Page Builders (Elementor, WPBakery).
* FIX (Performance): Completely rebuilt the JavaScript Decision Engine. Removed heavy `setIntervals` and implemented a silent, event-driven Gutenberg/Classic listener (Zero CPU idle load).
* FIX (Performance): Implemented strict 3-second timeouts for all external RSS fetching (Competitors & Google Trends) to prevent your server from hanging if external APIs go down.
* FIX (Schema): The `articleBody` is now rendered in full (removed the 150-word truncation) for maximum AI & LLM extractability.
* FIX (Schema): Smart "Logo Hunter" added. The plugin now falls back to Yoast, Rank Math, or Site Icon if your theme doesn't define a custom logo.
* FIX (Schema): Added Unicode Normalization (`nros_global_unaccent`) to safely match entities across all languages, bypassing transliteration/Greeklish plugins.
* FIX (UI): The "Disable Auto-Timeline" checkbox in the sidebar now successfully saves your preference.
* FIX (UX): The Tag Suggester now uses a 1-click silent AJAX adder (no scrolling, no manual copy-pasting required).

= 1.4.2 =
🛡️ **Security, Schema & Stability Update**

* FIX (Security): Patched an SQL Injection vulnerability in the Story Timeline generator.
* FIX (Security): Added missing cryptographic Nonces and authorization checks to the Onboarding Wizard AJAX endpoints.
* FIX (Schema/GSC): Resolved Google Search Console validation errors by strictly attributing news events as `Thing` instead of `Event`.
* FIX (Compatibility): Fixed a critical TypeError (Fatal Error) that occurred when disabling Rank Math's native schema.
* FIX (Cleanup): The plugin now performs a 100% clean uninstall, automatically removing all residual User Meta, Term Meta, and Post Meta fields.
* NEW (UX): Restored the Live Google Core Update Tracker!
* NEW (Performance): Drastically reduced database load on the Admin Dashboard by caching schema column checks (`DESC`).

= 1.4.1 =
🚀 **The Entity Engine & AIO Update**

* NEW: Deterministic Entity Engine based on Tags (no NLP guessing)
* NEW: Dynamic Entity Typing (Person, Organization, GovernmentService)
* NEW: Fully linked Schema Graph with @id nodes
* NEW: Auto-Citations from external links (boosts trust signals)
* NEW: Full AMP schema compatibility
* FIX: Fixed schema date formatting (Google News compliant)
* FIX: Enforced HTTPS for all media URLs

= 1.4.0 =
* Added Decision Engine (Publish Score + Next Best Action)
* Added FAQ & Key Takeaways generation
* Upgraded dashboard with real-time metrics

= 1.1.0 =
* Initial release