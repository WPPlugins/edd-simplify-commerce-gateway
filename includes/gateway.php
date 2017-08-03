<?php
/**
 * Gateway Functions
 *
 * @package         EDD\Gateway\Simplify_Commerce\Gateway
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright       Copyright (c) 2014, Daniel J Griffiths
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add settings section
 *
 * @since       1.0.3
 * @param       array $sections The existing extensions sections
 * @return      array The modified extensions settings
 */
function edd_simplify_commerce_add_settings_section( $sections ) {
	$sections['simplify-commerce'] = __( 'Simplify Commerce', 'edd-simplify-commerce' );

	return $sections;
}
add_filter( 'edd_settings_sections_gateways', 'edd_simplify_commerce_add_settings_section' );


/**
 * Register settings
 *
 * @since		1.0.0
 * @param		array $settings The existing plugin settings
 * @param		array The modified plugin settings array
 */
function edd_simplify_commerce_register_settings( $settings ) {
	$new_settings = array(
		'simplify-commerce' => array(
			array(
				'id'   => 'edd_simplify_commerce_settings',
				'name' => '<strong>' . __( 'Simplify Commerce Settings', 'edd-simplify-commerce' ) . '</strong>',
				'desc' => __( 'Configure your Simplify Commerce settings', 'edd-simplify-commerce' ),
				'type' => 'header'

			),
			array(
				'id'   => 'edd_simplify_commerce_public_key',
				'name' => __( 'API Public Key', 'edd-simplify-commerce' ),
				'desc' => __( 'Enter your Simplify Commerce API Public Key (found <a href="https://www.simplify.com/commerce/app#/account/apiKeys" target="_blank">here</a>)', 'edd-simplify-gateway' ),
				'type' => 'text'
			),
			array(
				'id'   => 'edd_simplify_commerce_private_key',
				'name' => __( 'API Private Key', 'edd-simplify-commerce' ),
				'desc' => __( 'Enter your Simplify Commerce API Private Key (found <a href="https://www.simplify.com/commerce/app#/account/apiKeys" target="_blank">here</a>)', 'edd-simplify-gateway' ),
				'type' => 'text'
			)
		)
	);

	return array_merge( $settings, $new_settings );
}
add_filter( 'edd_settings_gateways', 'edd_simplify_commerce_register_settings', 1 );


/**
 * Register our new gateway
 *
 * @since		1.0.0
 * @param		array $gateways The current gateway list
 * @return		array $gateways The updated gateway list
 */
function edd_simplify_commerce_register_gateway( $gateways ) {
	$gateways['simplify'] = array(
		'admin_label'    => 'Simplify Commerce',
		'checkout_label' => __( 'Credit Card', 'edd-simplify-commerce' )
	);

	return $gateways;
}
add_filter( 'edd_payment_gateways', 'edd_simplify_commerce_register_gateway' );


/**
 * Process payment submission
 *
 * @since		1.0.0
 * @param		array $purchase_data The data for a specific purchase
 * @return		void
 */
function edd_simplify_commerce_process_payment( $purchase_data ) {
	$errors = edd_get_errors();

	if( ! $errors ) {
		Simplify::$publicKey  = trim( edd_get_option( 'edd_simplify_commerce_public_key', '' ) );
		Simplify::$privateKey = trim( edd_get_option( 'edd_simplify_commerce_private_key', '' ) );

		try{
			$amount = number_format( $purchase_data['price'] * 100, 0 );

			$result = Simplify_Payment::createPayment( array(
				'card' => array(
					'number'       => str_replace( ' ', '', $purchase_data['card_info']['card_number'] ),
					'expMonth'     => $purchase_data['card_info']['card_exp_month'],
					'expYear'      => substr( $purchase_data['card_info']['card_exp_year'], -2 ),
					'cvc'          => $purchase_data['card_info']['card_cvc'],
					'addressLine1' => ( isset( $purchase_data['card_info']['card_address'] ) ? $purchase_data['card_info']['card_address'] : '' ),
					'addressLine2' => ( isset( $purchase_data['card_info']['card_address_2'] ) ? $purchase_data['card_info']['card_address_2'] : '' ),
					'addressCity'  => ( isset( $purchase_data['card_info']['card_city'] ) ? $purchase_data['card_info']['card_city'] : '' ),
					'addressState' => ( isset( $purchase_data['card_info']['card_state'] ) ? $purchase_data['card_info']['card_state'] : '' ),
					'addressZip'   => ( isset( $purchase_data['card_info']['card_zip'] ) ? $purchase_data['card_info']['card_zip'] : '' ),
					'name'         => ( isset( $purchase_data['card_info']['card_name'] ) ? $purchase_data['card_info']['card_name'] : '' ),
				),
				'amount'   => edd_sanitize_amount( $amount ),
				'currency' => edd_get_option( 'currency', 'USD' )
			) );
		} catch( Exception $e ) {
			edd_record_gateway_error( __( 'Simplify Commerce Error', 'edd-simplify-commerce' ), print_r( $e, true ), 0 );
			edd_set_error( 'card_declined', __( 'Your card was declined!', 'edd-balanced-gateway' ) );
			edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
		}

		if( $result->paymentStatus == 'APPROVED' ) {
			$payment_data = array(
				'price'        => $purchase_data['price'],
				'date'         => $purchase_data['date'],
				'user_email'   => $purchase_data['user_email'],
				'purchase_key' => $purchase_data['purchase_key'],
				'currency'     => edd_get_option( 'currency', 'USD' ),
				'downloads'    => $purchase_data['downloads'],
				'cart_details' => $purchase_data['cart_details'],
				'user_info'    => $purchase_data['user_info'],
				'status'       => 'pending'
			);

			$payment = edd_insert_payment( $payment_data );

			if( $payment ) {
				edd_insert_payment_note( $payment, sprintf( __( 'Simplify Commerce Transaction ID: %s', 'edd-simplify-commerce' ), $result->id ) );
				edd_update_payment_status( $payment, 'publish' );
				edd_send_to_success_page();
			} else {
				edd_set_error( 'authorize_error', __( 'Error: Your payment could not be recorded. Please try again.', 'edd-simplify-commerce' ) );
				edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
			}
		} else {
			wp_die( $result->paymentStatus );
		}
	} else {
		edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
	}
}
add_action( 'edd_gateway_simplify', 'edd_simplify_commerce_process_payment' );


/**
 * Output form errors
 *
 * @since		1.0.0
 * @return		void
 */
function edd_simplify_commerce_errors_div() {
	echo '<div id="edd-simplify-errors"></div>';
}
add_action( 'edd_after_cc_fields', 'edd_simplify_commerce_errors_div', 999 );