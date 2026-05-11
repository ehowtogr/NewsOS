<?php
/**
 * Plugin Name: NewsOS WooCommerce licenses
 * Description: After payment, adds NewsOS Pro license months from product meta (_newsos_license_months). Pairs with NewsOS License API MU-plugin. WooCommerce 8+ / 10.x.
 * Version: 1.0.0
 *
 * Install on NewsOS (same site as WooCommerce): copy to wp-content/mu-plugins/newsos-wc-licenses.php
 * File load order: must load after newsos-license-api.php (alphabetically: license before wc — OK).
 *
 * Setup (merchant):
 * - Simple product: Inventory → "NewsOS license months" = 1 (monthly) or 12 (yearly).
 * - Variable product: set the same field on each variation (e.g. Monthly = 1, Yearly = 12).
 * - Sale price / coupons in WooCommerce apply as usual (this only reads months × qty).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooCommerce', false ) ) {
	return;
}

if ( ! function_exists( 'newsos_license_wc_issue_or_extend' ) ) {
	return;
}

/**
 * Sum license months for all line items (months meta × quantity).
 *
 * @param WC_Order $order Order.
 * @return int
 */
function newsos_wc_licenses_order_total_months( WC_Order $order ) {
	$total = 0;
	foreach ( $order->get_items( 'line_item' ) as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}
		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}
		$months = (int) $product->get_meta( '_newsos_license_months', true );
		if ( $months < 1 ) {
			continue;
		}
		if ( $months > 120 ) {
			$months = 120;
		}
		$qty = (int) $item->get_quantity();
		if ( $qty < 1 ) {
			$qty = 1;
		}
		$total += $months * $qty;
	}

	return $total;
}

/**
 * @param int $order_id Order ID.
 * @return void
 */
function newsos_wc_licenses_maybe_fulfill( $order_id ) {
	$order_id = (int) $order_id;
	if ( $order_id < 1 ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	if ( $order->get_meta( '_newsos_license_wc_fulfilled', true ) === 'yes' ) {
		return;
	}

	$months = newsos_wc_licenses_order_total_months( $order );
	if ( $months < 1 ) {
		return;
	}

	$email   = (string) $order->get_billing_email();
	$user_id = (int) $order->get_customer_id();

	$result = newsos_license_wc_issue_or_extend( $user_id, $email, $months );
	if ( $result === null ) {
		return;
	}

	$order->update_meta_data( '_newsos_license_wc_fulfilled', 'yes' );
	$order->update_meta_data( '_newsos_license_key', $result['key'] );
	$order->update_meta_data( '_newsos_license_expires_utc', $result['expires_at'] );
	$order->update_meta_data( '_newsos_license_extended', $result['extended'] ? 'yes' : 'no' );

	/* translators: 1: license key, 2: expiry date (UTC Y-m-d) */
	$note = sprintf(
		__( 'NewsOS Pro license: %1$s — paid through %2$s (UTC). Enter the key in WordPress → Editorial Control → PRO with this email.', 'newsos-wc-licenses' ),
		$result['key'],
		$result['expires_at']
	);
	$order->add_order_note( $note, true );

	$order->save();
}

add_action( 'woocommerce_payment_complete', 'newsos_wc_licenses_maybe_fulfill', 20, 1 );
add_action( 'woocommerce_order_status_processing', 'newsos_wc_licenses_maybe_fulfill', 20, 1 );
add_action( 'woocommerce_order_status_completed', 'newsos_wc_licenses_maybe_fulfill', 20, 1 );

// --- Product admin: months meta -------------------------------------------------

/**
 * @return void
 */
function newsos_wc_licenses_product_general_field() {
	echo '<div class="options_group show_if_simple">';
	woocommerce_wp_text_input(
		[
			'id'                => '_newsos_license_months',
			'label'             => __( 'NewsOS license months', 'newsos-wc-licenses' ),
			'description'       => __( 'Paid period added to the buyer key per quantity: 1 = one month, 12 = one year. Use 0 or leave empty if this product is not a NewsOS license.', 'newsos-wc-licenses' ),
			'type'              => 'number',
			'custom_attributes' => [
				'min'  => '0',
				'max'  => '120',
				'step' => '1',
			],
		]
	);
	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'newsos_wc_licenses_product_general_field' );

/**
 * @param int $post_id Product ID.
 * @return void
 */
function newsos_wc_licenses_save_simple_product( $post_id ) {
	if ( ! isset( $_POST['_newsos_license_months'] ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	$m = absint( wp_unslash( $_POST['_newsos_license_months'] ) );
	if ( $m > 120 ) {
		$m = 120;
	}
	update_post_meta( $post_id, '_newsos_license_months', $m );
}
add_action( 'woocommerce_process_product_meta', 'newsos_wc_licenses_save_simple_product' );

/**
 * @param int     $loop           Variation loop index.
 * @param array   $variation_data Variation data.
 * @param WP_Post $variation      Variation post.
 * @return void
 */
function newsos_wc_licenses_variation_field( $loop, $variation_data, $variation ) {
	$vid = is_object( $variation ) && isset( $variation->ID ) ? (int) $variation->ID : 0;
	$val = $vid ? (int) get_post_meta( $vid, '_newsos_license_months', true ) : 0;
	?>
	<p class="form-field form-row form-row-full newsos-wc-license-months-field">
		<label for="newsos_license_months_<?php echo esc_attr( (string) $loop ); ?>">
			<?php esc_html_e( 'NewsOS license months', 'newsos-wc-licenses' ); ?>
			<?php echo wc_help_tip( esc_html__( '1 = monthly, 12 = yearly, 0 = not a license line item.', 'newsos-wc-licenses' ) ); ?>
		</label>
		<input
			type="number"
			class="short"
			id="newsos_license_months_<?php echo esc_attr( (string) $loop ); ?>"
			name="variable_newsos_license_months[<?php echo esc_attr( (string) $loop ); ?>]"
			value="<?php echo esc_attr( (string) $val ); ?>"
			min="0"
			max="120"
			step="1"
		/>
	</p>
	<?php
}
add_action( 'woocommerce_variation_options_pricing', 'newsos_wc_licenses_variation_field', 15, 3 );

/**
 * @param int $variation_id Variation post ID.
 * @param int $loop         Loop index.
 * @return void
 */
function newsos_wc_licenses_save_variation( $variation_id, $loop ) {
	if ( ! isset( $_POST['variable_newsos_license_months'][ $loop ] ) ) {
		return;
	}
	$m = absint( wp_unslash( $_POST['variable_newsos_license_months'][ $loop ] ) );
	if ( $m > 120 ) {
		$m = 120;
	}
	update_post_meta( $variation_id, '_newsos_license_months', $m );
}
add_action( 'woocommerce_save_product_variation', 'newsos_wc_licenses_save_variation', 10, 2 );
