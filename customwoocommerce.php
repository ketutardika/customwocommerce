<?php
/*
Plugin Name: Custom Order Fields for WooCommerce REST API
Description: Adds custom parameters (challengePricingId, stageId, userEmail, brandId) to the WooCommerce order REST endpoint.
Version: 1.0
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register custom order fields so that they are exposed via the REST API.
 */
add_action( 'rest_api_init', 'register_custom_order_fields' );
function register_custom_order_fields() {
    $custom_fields = array(
        'challengePricingId' => array(
            'description' => 'Challenge Pricing ID',
            'type'        => 'string',
        ),
        'stageId' => array(
            'description' => 'Stage ID',
            'type'        => 'string',
        ),
        'userEmail' => array(
            'description' => 'User Email',
            'type'        => 'string',
        ),
        'brandId' => array(
            'description' => 'Brand ID',
            'type'        => 'string',
        ),
    );
    
    // Register each custom field for the "shop_order" post type.
    foreach ( $custom_fields as $field => $args ) {
        register_rest_field( 'shop_order', $field, array(
            'get_callback'    => function( $order ) use ( $field ) {
                return get_post_meta( $order['id'], $field, true );
            },
            'update_callback' => function( $value, $order, $field_name ) {
                if ( ! empty( $value ) ) {
                    update_post_meta( $order->get_id(), $field_name, sanitize_text_field( $value ) );
                }
            },
            'schema'          => array(
                'description' => $args['description'],
                'type'        => $args['type'],
                'context'     => array( 'view', 'edit' ),
            ),
        ));
    }
}

/**
 * Validate that the required custom fields are present in the REST API request.
 */
add_filter( 'rest_pre_insert_shop_order', 'validate_custom_order_fields', 10, 2 );
function validate_custom_order_fields( $prepared_post, $request ) {
    $required_fields = array( 'challengePricingId', 'stageId', 'userEmail', 'brandId' );
    
    foreach ( $required_fields as $field ) {
        if ( empty( $request[ $field ] ) ) {
            return new WP_Error( 'rest_order_missing_field', sprintf( '%s is required.', $field ), array( 'status' => 400 ) );
        }
    }
    return $prepared_post;
}

/**
 * Save the custom order meta when an order is created or updated via the REST API.
 */
add_action( 'woocommerce_rest_insert_order', 'save_custom_order_fields', 10, 2 );
function save_custom_order_fields( $order, $request ) {
    $custom_fields = array( 'challengePricingId', 'stageId', 'userEmail', 'brandId' );
    
    foreach ( $custom_fields as $field ) {
        if ( isset( $request[ $field ] ) && ! empty( $request[ $field ] ) ) {
            $order->update_meta_data( $field, sanitize_text_field( $request[ $field ] ) );
        }
    }
}
?>
