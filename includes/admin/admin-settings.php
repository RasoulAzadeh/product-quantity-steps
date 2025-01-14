<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Add the quantity step field to the general product settings
add_action( 'woocommerce_product_options_pricing', 'add_quantity_step_field' );

function add_quantity_step_field() {
    // Generate nonce field within the hooked function
    wp_nonce_field( 'quantity_step_action', 'quantity_step_nonce' );

    woocommerce_wp_text_input( array(
        'id'                => '_quantity_step',
        'label'             => __( 'Quantity Step', 'product-quantity-steps' ),
        'desc_tip'          => true,
        'description'       => __( 'Set the quantity step for this product.', 'product-quantity-steps' ),
        'type'              => 'number',
        'custom_attributes' => array(
            'step' => 'any',
            'min'  => '0',
        ),
    ) );
}

// Save the quantity step field
add_action( 'woocommerce_process_product_meta', 'save_quantity_step_field' );

function save_quantity_step_field( $post_id ) {
    // Check if nonce is set, verify and sanitize
    if ( isset( $_POST['quantity_step_nonce'] ) ) {
        // Sanitize the nonce before verifying
        $nonce = sanitize_text_field( wp_unslash( $_POST['quantity_step_nonce'] ) );

        // Nonce verification
        if ( ! wp_verify_nonce( $nonce, 'quantity_step_action' ) ) {
            return; // Nonce verification failed
        }
    } else {
        return; // No nonce found, return early
    }

    // Check if the quantity step field is set and sanitize input before saving
    if ( isset( $_POST['_quantity_step'] ) ) {
        // Sanitize and validate the quantity step field
        $quantity_step = floatval( sanitize_text_field( wp_unslash( $_POST['_quantity_step'] ) ) );
    } else {
        $quantity_step = ''; // Default value if not set
    }

    // Update the post meta for the product
    update_post_meta( $post_id, '_quantity_step', $quantity_step );
}
