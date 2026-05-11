<?php
/**
 * Plugin Name: NewsOS License API (MU-plugin)
 * Description: REST licensing for Newsroom Pro — key format NROS-XXXX-XXXX-XXXX, subscription end date per key (monthly renewals).
 * Version: 1.2.0
 *
 * WHERE THIS FILE LIVES: only on YOUR sales site (e.g. newsos.io), NOT inside the customer Newsroom plugin zip.
 * Install on NewsOS: copy to wp-content/mu-plugins/newsos-license-api.php
 * Admin: Settings → NewsOS Licenses
 *
 * Inventory line format (one per line):
 *   NROS-ABCD-1234-XY9Z	2026-06-30
 * Delimiter: TAB or | between key and expiry (YYYY-MM-DD, UTC "paid through" date, inclusive).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Key pattern: NROS- + 3×4 alphanumerics (no I,O,0,1 for readability). */
const NEWSOS_LICENSE_KEY_PATTERN = '/^NROS-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/';

/**
 * @param string $url Raw site URL from client.
 * @return string
 */
function newsos_license_normalize_site( $url ) {
	$u = esc_url_raw( (string) $url );
	$p   = wp_parse_url( $u );
	$host = isset( $p['host'] ) ? strtolower( (string) $p['host'] ) : '';
	if ( strlen( $host ) > 4 && substr( $host, 0, 4 ) === 'www.' ) {
		$host = substr( $host, 4 );
	}
	$path = isset( $p['path'] ) ? '/' . trim( (string) $p['path'], '/' ) : '';
	if ( $path === '/' ) {
		$path = '';
	}

	return $host . $path;
}

/**
 * @param string $key Raw key.
 * @return string
 */
function newsos_license_normalize_key( $key ) {
	return strtoupper( preg_replace( '/\s+/', '', (string) $key ) );
}

/**
 * @param string $key Normalized key.
 * @return bool
 */
function newsos_license_key_valid_format( $key ) {
	return (bool) preg_match( NEWSOS_LICENSE_KEY_PATTERN, $key );
}

/**
 * @param string $ymd Y-m-d.
 * @return bool True if subscription still valid (paid through date inclusive, UTC calendar day).
 */
function newsos_license_subscription_active( $ymd ) {
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
		return false;
	}
	$today = gmdate( 'Y-m-d' );

	return $ymd >= $today;
}

/**
 * Blog ID where license inventory and activations live (network main site on multisite).
 *
 * @return int
 */
function newsos_license_storage_blog_id() {
	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		return (int) get_main_site_id();
	}

	return (int) get_current_blog_id();
}

/**
 * Run a callback in the storage blog context when on multisite.
 *
 * @template T
 * @param callable(): T $callback
 * @return T
 */
function newsos_license_with_storage_blog( callable $callback ) {
	if ( ! function_exists( 'is_multisite' ) || ! is_multisite() ) {
		return $callback();
	}

	$target = newsos_license_storage_blog_id();
	$here   = (int) get_current_blog_id();

	if ( $target === $here ) {
		return $callback();
	}

	switch_to_blog( $target );
	$out = $callback();
	restore_current_blog();

	return $out;
}

/**
 * @param string $option_name Option name.
 * @param mixed  $default     Default.
 * @return mixed
 */
function newsos_license_get_storage_option( $option_name, $default = false ) {
	return newsos_license_with_storage_blog(
		static function () use ( $option_name, $default ) {
			return get_option( $option_name, $default );
		}
	);
}

/**
 * @param string      $option_name Option name.
 * @param mixed       $value       Value.
 * @param bool|string $autoload    Autoload flag.
 * @return bool
 */
function newsos_license_update_storage_option( $option_name, $value, $autoload = false ) {
	return newsos_license_with_storage_blog(
		static function () use ( $option_name, $value, $autoload ) {
			$ok = update_option( $option_name, $value, $autoload );
			wp_cache_delete( $option_name, 'options' );

			return $ok;
		}
	);
}

/**
 * Parse textarea into key => [ 'expires_at' => 'Y-m-d' ].
 *
 * @return array<string, array{expires_at:string}>
 */
function newsos_license_parse_inventory() {
	$raw   = (string) newsos_license_get_storage_option( 'newsos_license_keys_raw', '' );
	$lines = preg_split( '/\r\n|\r|\n/', $raw, -1, PREG_SPLIT_NO_EMPTY );
	$out   = [];
	foreach ( $lines as $line ) {
		$line = trim( (string) $line );
		if ( $line === '' || ( strlen( $line ) > 0 && '#' === $line[0] ) ) {
			continue;
		}
		$parts = preg_split( '/\t|\|/', $line, 2 );
		if ( count( $parts ) < 2 ) {
			continue;
		}
		$key = newsos_license_normalize_key( $parts[0] );
		$exp = trim( (string) $parts[1] );
		if ( ! newsos_license_key_valid_format( $key ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $exp ) ) {
			continue;
		}
		$out[ $key ] = [ 'expires_at' => $exp ];
	}

	return $out;
}

/**
 * @return array<string, string[]>
 */
function newsos_license_get_activations() {
	$a = newsos_license_get_storage_option( 'newsos_license_activations', [] );

	return is_array( $a ) ? $a : [];
}

/**
 * @param array<string, string[]> $activations .
 */
function newsos_license_save_activations( $activations ) {
	newsos_license_update_storage_option( 'newsos_license_activations', $activations, false );
}

/**
 * @return array<string, string>
 */
function newsos_license_get_billing_emails() {
	$m = newsos_license_get_storage_option( 'newsos_license_billing_emails', [] );

	return is_array( $m ) ? $m : [];
}

/**
 * @param array<string, string> $map .
 */
function newsos_license_save_billing_emails( $map ) {
	newsos_license_update_storage_option( 'newsos_license_billing_emails', $map, false );
}

/**
 * @return WP_REST_Response|array{success:bool, code?:string, message?:string, license?:array}
 */
function newsos_license_evaluate_request( array $body ) {
	$key    = isset( $body['license_key'] ) ? newsos_license_normalize_key( (string) $body['license_key'] ) : '';
	$site   = isset( $body['site_url'] ) ? (string) $body['site_url'] : '';
	$action = isset( $body['action'] ) ? sanitize_key( (string) $body['action'] ) : 'check';
	$email  = isset( $body['customer_email'] ) ? sanitize_email( (string) $body['customer_email'] ) : '';

	if ( ! newsos_license_key_valid_format( $key ) ) {
		return new WP_REST_Response(
			[
				'success' => false,
				'code'    => 'invalid_key_format',
				'message' => 'License key must look like NROS-XXXX-XXXX-XXXX (letters A–Z and digits, no I/O/0/1).',
			],
			200
		);
	}

	if ( $site === '' || ! filter_var( $site, FILTER_VALIDATE_URL ) ) {
		return new WP_REST_Response(
			[
				'success' => false,
				'code'    => 'missing_fields',
				'message' => 'license_key and valid site_url are required.',
			],
			400
		);
	}

	$inventory = newsos_license_parse_inventory();
	if ( ! isset( $inventory[ $key ] ) ) {
		return new WP_REST_Response(
			[
				'success' => false,
				'code'    => 'invalid_key',
				'message' => 'Unknown or revoked license key.',
			],
			200
		);
	}

	$expires_at = $inventory[ $key ]['expires_at'];
	if ( ! newsos_license_subscription_active( $expires_at ) ) {
		return new WP_REST_Response(
			[
				'success' => false,
				'code'    => 'subscription_expired',
				'message' => 'Subscription ended on ' . $expires_at . '. Renew at NewsOS.io to continue.',
				'license' => [
					'status'     => 'expired',
					'expires_at' => $expires_at,
					'billing'    => 'monthly',
				],
			],
			200
		);
	}

	$max_sites = (int) apply_filters( 'newsos_license_max_activations_per_key', 1 );
	if ( $max_sites < 1 ) {
		$max_sites = 1;
	}

	$activations = newsos_license_get_activations();
	if ( ! isset( $activations[ $key ] ) || ! is_array( $activations[ $key ] ) ) {
		$activations[ $key ] = [];
	}

	$sites     = &$activations[ $key ];
	$norm_site = newsos_license_normalize_site( $site );

	if ( $action === 'deactivate' ) {
		$sites = array_values(
			array_filter(
				$sites,
				static function ( $u ) use ( $norm_site ) {
					return newsos_license_normalize_site( (string) $u ) !== $norm_site;
				}
			)
		);
		newsos_license_save_activations( $activations );

		return new WP_REST_Response(
			[
				'success' => true,
				'license' => [
					'status'     => 'deactivated',
					'expires_at' => $expires_at,
					'billing'    => 'monthly',
				],
			],
			200
		);
	}

	if ( $action === 'activate' ) {
		if ( $email !== '' ) {
			$emap = newsos_license_get_billing_emails();
			$emap[ $key ] = $email;
			newsos_license_save_billing_emails( $emap );
		}

		$already = false;
		foreach ( $sites as $u ) {
			if ( newsos_license_normalize_site( (string) $u ) === $norm_site ) {
				$already = true;
				break;
			}
		}
		if ( ! $already && count( $sites ) >= $max_sites ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'code'    => 'activation_limit',
					'message' => 'This license reached its site limit. Deactivate another site first.',
				],
				200
			);
		}
		if ( ! $already ) {
			$sites[] = esc_url_raw( $site );
			newsos_license_save_activations( $activations );
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'license' => [
					'status'     => 'active',
					'expires_at' => $expires_at,
					'billing'    => 'monthly',
				],
			],
			200
		);
	}

	// check
	$found = false;
	foreach ( $sites as $u ) {
		if ( newsos_license_normalize_site( (string) $u ) === $norm_site ) {
			$found = true;
			break;
		}
	}
	if ( ! $found ) {
		return new WP_REST_Response(
			[
				'success' => false,
				'code'    => 'not_activated',
				'message' => 'This site is not activated for that key. Run Activate in WordPress first.',
			],
			200
		);
	}

	return new WP_REST_Response(
		[
			'success' => true,
			'license' => [
				'status'     => 'valid',
				'expires_at' => $expires_at,
				'billing'    => 'monthly',
			],
		],
		200
	);
}

/**
 * REST: POST /wp-json/newsos/v1/license/validate
 */
function newsos_license_rest_validate( WP_REST_Request $request ) {
	$body = $request->get_json_params();
	if ( ! is_array( $body ) || $body === [] ) {
		$raw = $request->get_body();
		if ( $raw !== '' ) {
			$decoded = json_decode( $raw, true );
			$body    = is_array( $decoded ) ? $decoded : [];
		}
	}
	if ( ! is_array( $body ) || $body === [] ) {
		return new WP_REST_Response(
			[
				'success' => false,
				'code'    => 'bad_json',
				'message' => 'Invalid JSON body.',
			],
			400
		);
	}

	return newsos_license_evaluate_request( $body );
}

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'newsos/v1',
			'/license/validate',
			[
				'methods'             => 'POST',
				'callback'            => 'newsos_license_rest_validate',
				'permission_callback' => '__return_true',
			]
		);
	}
);

/**
 * @return string
 */
function newsos_license_generate_key_string() {
	$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	$seg   = static function () use ( $chars ) {
		$out = '';
		$max = strlen( $chars ) - 1;
		for ( $i = 0; $i < 4; $i++ ) {
			$out .= $chars[ random_int( 0, $max ) ];
		}

		return $out;
	};

	return 'NROS-' . $seg() . '-' . $seg() . '-' . $seg();
}

/**
 * Add calendar months to a UTC Y-m-d anchor (paid-through style).
 *
 * @param string $ymd    Y-m-d.
 * @param int    $months Months to add (1–120).
 * @return string Y-m-d
 */
function newsos_license_add_months_to_ymd( $ymd, $months ) {
	$months = (int) $months;
	if ( $months < 1 ) {
		$months = 1;
	}
	if ( $months > 120 ) {
		$months = 120;
	}
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $ymd ) ) {
		$ymd = gmdate( 'Y-m-d' );
	}
	$ts = strtotime( $ymd . ' 12:00:00 UTC' );
	if ( ! is_int( $ts ) ) {
		$ts = strtotime( gmdate( 'Y-m-d' ) . ' 12:00:00 UTC' );
	}

	return gmdate( 'Y-m-d', strtotime( '+' . $months . ' months', $ts ) );
}

/**
 * Paid-through date when starting a new term from "today" (UTC).
 *
 * @param int $months Months ahead (1–120).
 * @return string Y-m-d
 */
function newsos_license_new_key_expires_from_today( $months ) {
	return newsos_license_add_months_to_ymd( gmdate( 'Y-m-d' ), $months );
}

/**
 * Upsert one inventory line: KEY + tab + expiry (replaces existing line for same key).
 *
 * @param string $key        Normalized NROS- key.
 * @param string $expires_ymd Y-m-d UTC.
 * @return void
 */
function newsos_license_inventory_set_key_line( $key, $expires_ymd ) {
	$key = newsos_license_normalize_key( $key );
	if ( ! newsos_license_key_valid_format( $key ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $expires_ymd ) ) {
		return;
	}
	$raw   = (string) newsos_license_get_storage_option( 'newsos_license_keys_raw', '' );
	$lines = preg_split( '/\r\n|\r|\n/', $raw, -1, PREG_SPLIT_NO_EMPTY );
	$found = false;
	$out   = [];
	foreach ( $lines as $line ) {
		$t = trim( (string) $line );
		if ( $t === '' || ( strlen( $t ) > 0 && '#' === $t[0] ) ) {
			$out[] = $line;
			continue;
		}
		$parts = preg_split( '/\t|\|/', $t, 2 );
		if ( count( $parts ) >= 2 && newsos_license_normalize_key( $parts[0] ) === $key ) {
			$out[] = $key . "\t" . $expires_ymd;
			$found = true;
		} else {
			$out[] = $line;
		}
	}
	if ( ! $found ) {
		$out[] = $key . "\t" . $expires_ymd;
	}
	$new_raw = implode( "\n", $out );
	if ( $new_raw !== '' ) {
		$new_raw .= "\n";
	}
	newsos_license_update_storage_option( 'newsos_license_keys_raw', $new_raw, false );
}

/**
 * Extend paid-through for an existing key by N months (from current paid-through if still active, else from today).
 *
 * @param string $key    Normalized key.
 * @param int    $months Months to add.
 * @return string|null New expiry Y-m-d or null if key invalid.
 */
function newsos_license_extend_key_by_months( $key, $months ) {
	$key = newsos_license_normalize_key( $key );
	if ( ! newsos_license_key_valid_format( $key ) ) {
		return null;
	}
	$inv = newsos_license_parse_inventory();
	$today = gmdate( 'Y-m-d' );
	if ( isset( $inv[ $key ]['expires_at'] ) ) {
		$cur = $inv[ $key ]['expires_at'];
		$base = ( $cur >= $today ) ? $cur : $today;
	} else {
		$base = $today;
	}
	$new_exp = newsos_license_add_months_to_ymd( $base, $months );
	newsos_license_inventory_set_key_line( $key, $new_exp );

	return $new_exp;
}

/**
 * Create a new key with paid-through = today + months (UTC).
 *
 * @param int $months Months (1–120).
 * @return array{key:string, expires_at:string}
 */
function newsos_license_create_new_key_months( $months ) {
	$months = (int) $months;
	if ( $months < 1 ) {
		$months = 1;
	}
	if ( $months > 120 ) {
		$months = 120;
	}
	$key   = newsos_license_generate_key_string();
	$until = newsos_license_new_key_expires_from_today( $months );
	newsos_license_inventory_set_key_line( $key, $until );

	return [
		'key'        => $key,
		'expires_at' => $until,
	];
}

/**
 * WooCommerce / automation: map customer user ID and billing email to the license key we issued.
 *
 * @param int    $user_id      WC customer user ID (0 = guest).
 * @param string $billing_email Lowercased billing email.
 * @param string $key           NROS- key.
 * @return void
 */
function newsos_license_wc_bind_customer_key( $user_id, $billing_email, $key ) {
	$key = newsos_license_normalize_key( $key );
	if ( ! newsos_license_key_valid_format( $key ) ) {
		return;
	}
	if ( $user_id > 0 ) {
		$by_user = newsos_license_get_storage_option( 'newsos_license_wc_user_key', [] );
		if ( ! is_array( $by_user ) ) {
			$by_user = [];
		}
		$by_user[ (int) $user_id ] = $key;
		newsos_license_update_storage_option( 'newsos_license_wc_user_key', $by_user, false );
	}
	$email = strtolower( trim( (string) $billing_email ) );
	if ( $email !== '' && is_email( $email ) ) {
		$by_email = newsos_license_get_storage_option( 'newsos_license_wc_email_key', [] );
		if ( ! is_array( $by_email ) ) {
			$by_email = [];
		}
		$by_email[ $email ] = $key;
		newsos_license_update_storage_option( 'newsos_license_wc_email_key', $by_email, false );
	}
}

/**
 * Resolve key to extend for a returning Woo customer (same user or same email).
 *
 * @param int    $user_id       WC customer ID.
 * @param string $billing_email Billing email.
 * @return string|null Normalized key or null.
 */
function newsos_license_wc_resolve_existing_key( $user_id, $billing_email ) {
	if ( $user_id > 0 ) {
		$by_user = newsos_license_get_storage_option( 'newsos_license_wc_user_key', [] );
		if ( is_array( $by_user ) && isset( $by_user[ $user_id ] ) ) {
			$k = newsos_license_normalize_key( (string) $by_user[ $user_id ] );
			if ( newsos_license_key_valid_format( $k ) ) {
				return $k;
			}
		}
	}
	$email = strtolower( trim( (string) $billing_email ) );
	if ( $email !== '' && is_email( $email ) ) {
		$by_email = newsos_license_get_storage_option( 'newsos_license_wc_email_key', [] );
		if ( is_array( $by_email ) && isset( $by_email[ $email ] ) ) {
			$k = newsos_license_normalize_key( (string) $by_email[ $email ] );
			if ( newsos_license_key_valid_format( $k ) ) {
				return $k;
			}
		}
	}

	return null;
}

/**
 * Issue new or extend existing key after a paid WooCommerce order.
 *
 * @param int    $user_id       WC customer user ID.
 * @param string $billing_email Order billing email.
 * @param int    $total_months  Sum of (months × qty) from license line items.
 * @return array{key:string, expires_at:string, extended:bool}|null Null if total_months < 1.
 */
function newsos_license_wc_issue_or_extend( $user_id, $billing_email, $total_months ) {
	$total_months = (int) $total_months;
	if ( $total_months < 1 ) {
		return null;
	}
	if ( $total_months > 120 ) {
		$total_months = 120;
	}

	$existing = newsos_license_wc_resolve_existing_key( (int) $user_id, $billing_email );
	if ( $existing !== null ) {
		$new_exp = newsos_license_extend_key_by_months( $existing, $total_months );
		if ( $new_exp === null ) {
			return null;
		}
		newsos_license_wc_bind_customer_key( (int) $user_id, $billing_email, $existing );

		return [
			'key'        => $existing,
			'expires_at' => $new_exp,
			'extended'   => true,
		];
	}

	$created = newsos_license_create_new_key_months( $total_months );
	newsos_license_wc_bind_customer_key( (int) $user_id, $billing_email, $created['key'] );

	return [
		'key'        => $created['key'],
		'expires_at' => $created['expires_at'],
		'extended'   => false,
	];
}

add_action(
	'admin_menu',
	static function () {
		add_options_page(
			'NewsOS Licenses',
			'NewsOS Licenses',
			'manage_options',
			'newsos-licenses',
			'newsos_license_render_settings'
		);
	}
);

function newsos_license_render_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['newsos_license_save'] ) && isset( $_POST['newsos_license_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['newsos_license_nonce'] ) ), 'newsos_license_save' ) ) {
		$raw = isset( $_POST['newsos_license_keys_raw'] ) ? wp_unslash( $_POST['newsos_license_keys_raw'] ) : '';
		newsos_license_update_storage_option( 'newsos_license_keys_raw', sanitize_textarea_field( $raw ), false );
		echo '<div class="updated"><p>Saved inventory.</p></div>';
	}

	if ( isset( $_POST['newsos_license_generate'] ) && isset( $_POST['newsos_license_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['newsos_license_nonce'] ) ), 'newsos_license_save' ) ) {
		$months = isset( $_POST['newsos_gen_months'] ) ? (int) $_POST['newsos_gen_months'] : 1;
		if ( $months < 1 ) {
			$months = 1;
		}
		if ( $months > 36 ) {
			$months = 36;
		}
		$new_key   = newsos_license_generate_key_string();
		$end_ts    = strtotime( '+' . $months . ' months', strtotime( gmdate( 'Y-m-d' ) . ' 12:00:00 UTC' ) );
		$expires   = gmdate( 'Y-m-d', $end_ts );
		$raw       = (string) newsos_license_get_storage_option( 'newsos_license_keys_raw', '' );
		$line      = $new_key . "\t" . $expires . "\n";
		newsos_license_update_storage_option( 'newsos_license_keys_raw', $raw . $line, false );
		echo '<div class="updated"><p>Generated: <code>' . esc_html( $new_key ) . '</code> — paid through <strong>' . esc_html( $expires ) . '</strong> (UTC). Copy into your customer email.</p></div>';
	}

	$raw = (string) newsos_license_get_storage_option( 'newsos_license_keys_raw', '' );
	?>
	<div class="wrap">
		<h1>NewsOS Pro — subscriptions</h1>
		<p><strong>Endpoint:</strong> <code><?php echo esc_html( rest_url( 'newsos/v1/license/validate' ) ); ?></code></p>
		<p><strong>WooCommerce:</strong> With MU-plugin <code>newsos-wc-licenses.php</code>, set <em>NewsOS license months</em> on each simple product or variation (e.g. <code>1</code> = monthly, <code>12</code> = yearly). Paid orders add that many months to the customer's key automatically.</p>
		<p><strong>Inventory format</strong> (one license per line): <code>NROS-XXXX-XXXX-XXXX</code> then TAB or <code>|</code> then <code>YYYY-MM-DD</code> = last valid day of the current billing period (UTC). When the customer pays the next month, <strong>extend that date</strong> on the same line and Save.</p>
		<p><strong>Example:</strong><br><code>NROS-A1B2-C3D4-E5F6	2026-07-01</code></p>

		<form method="post" style="margin-bottom:24px;padding:12px;background:#fff;border:1px solid #ccd0d4;max-width:720px;">
			<?php wp_nonce_field( 'newsos_license_save', 'newsos_license_nonce' ); ?>
			<h2>Generate new monthly key</h2>
			<p>
				<label>Months paid ahead: <input type="number" name="newsos_gen_months" value="1" min="1" max="36" style="width:4rem;"></label>
				<button type="submit" name="newsos_license_generate" class="button">Generate &amp; append to list</button>
			</p>
		</form>

		<form method="post">
			<?php wp_nonce_field( 'newsos_license_save', 'newsos_license_nonce' ); ?>
			<h2>License inventory</h2>
			<p><textarea name="newsos_license_keys_raw" rows="16" cols="70" class="large-text code" placeholder="NROS-XXXX-XXXX-XXXX	2026-12-31"><?php echo esc_textarea( $raw ); ?></textarea></p>
			<p><button type="submit" name="newsos_license_save" class="button button-primary">Save inventory</button></p>
		</form>

		<h2>Parsed keys (read-only check)</h2>
		<?php
		$parsed = newsos_license_parse_inventory();
		if ( empty( $parsed ) ) {
			echo '<p><em>No valid lines. Each line needs KEY + tab + expiry date.</em></p>';
		} else {
			echo '<table class="widefat striped"><thead><tr><th>Key</th><th>Paid through (UTC)</th><th>Active</th></tr></thead><tbody>';
			foreach ( $parsed as $k => $meta ) {
				$exp = $meta['expires_at'];
				$ok  = newsos_license_subscription_active( $exp ) ? '✓' : '✗ expired';
				echo '<tr><td><code>' . esc_html( $k ) . '</code></td><td>' . esc_html( $exp ) . '</td><td>' . esc_html( $ok ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}
		?>

		<h2>Activations (sites)</h2>
		<p><strong>Note:</strong> You do <em>not</em> put domains in the inventory box above. Each customer site registers here automatically when their WordPress admin runs <strong>Activate</strong> (or a successful re-bind) against this API — until then this list stays empty and <code>check</code> returns <code>not_activated</code>.</p>
		<p>Max sites per key: <?php echo (int) apply_filters( 'newsos_license_max_activations_per_key', 1 ); ?> — filter <code>newsos_license_max_activations_per_key</code>.</p>
		<?php
		$act = newsos_license_get_activations();
		$em  = newsos_license_get_billing_emails();
		if ( empty( $act ) ) {
			echo '<p><em>No sites activated yet.</em></p>';
		} else {
			echo '<table class="widefat striped"><thead><tr><th>Key (prefix)</th><th>Billing email</th><th>Sites</th></tr></thead><tbody>';
			foreach ( $act as $k => $urls ) {
				if ( ! is_array( $urls ) ) {
					continue;
				}
				$prefix = strlen( $k ) > 12 ? substr( $k, 0, 10 ) . '…' : $k;
				$mail   = isset( $em[ $k ] ) ? $em[ $k ] : '—';
				echo '<tr><td><code>' . esc_html( $prefix ) . '</code></td><td>' . esc_html( (string) $mail ) . '</td><td>';
				foreach ( $urls as $u ) {
					echo esc_html( (string) $u ) . '<br>';
				}
				echo '</td></tr>';
			}
			echo '</tbody></table>';
		}
		?>
	</div>
	<?php
}
