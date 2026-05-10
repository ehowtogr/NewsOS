# AGENTS.md

## Cursor Cloud specific instructions

This is a WordPress plugin (Newsroom OS – Editorial Control & AI Assistant, v1.4.4). It has **no build step, no package manager, no test framework** — it is pure PHP + vanilla JS/CSS that runs inside WordPress.

### Development Environment

- **WordPress**: Installed at `/var/www/html` with the plugin symlinked from `/workspace` → `/var/www/html/wp-content/plugins/newsroom-ai-assistant/`
- **Database**: MariaDB with database `wordpress`, user `wp_user`, password `wp_pass123`
- **Web server**: Apache2 on port 80 (`http://localhost`)
- **Admin credentials**: username `admin`, password `admin123`
- **WP_DEBUG**: Enabled. Debug log at `/var/www/html/wp-content/debug.log`

### Starting services

After VM startup, services need to be started manually:

```bash
sudo mariadbd-safe --datadir=/var/lib/mysql &
sleep 2
sudo service apache2 start
```

### Linting

No dedicated linter config exists. Use PHP's built-in syntax checker:

```bash
php -l /workspace/newsroom-ai-assistant.php
php -l /workspace/includes/*.php
php -l /workspace/wizard.php
```

### Testing

No automated test suite exists. Testing is manual:

1. Verify PHP syntax with `php -l` on all `.php` files
2. Use WP-CLI to exercise plugin functions: `cd /var/www/html && wp eval '...' --allow-root`
3. Access `http://localhost/wp-admin/` (admin/admin123) to test the UI
4. The plugin's admin page is under "Editorial Control" in the WP sidebar (not "Newsroom OS")

### Key gotchas

- The plugin menu item in WP admin is labeled **"Editorial Control"**, not "Newsroom OS"
- On first activation, the plugin redirects to a setup wizard at `admin.php?page=newsroom-os-wizard` — delete the `nros_do_activation_redirect` option to suppress this: `wp option delete nros_do_activation_redirect --allow-root`
- Schema JSON-LD is output via a `wp_head` hook at priority 99 (`nros_output_json_ld_schema`)
- The Story Timeline needs at least 2 published posts sharing a tag within the configured time window (default 30 days) to generate output
- All AJAX endpoints require a valid `newsai_ajax_nonce` nonce
- External API calls (Google Trends, Google News, Google Search Status) have a 3-second timeout and 5-minute cache to avoid rate limiting
