<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Add quantity step field to variations
add_action('woocommerce_product_after_variable_attributes', 'add_variation_quantity_step', 10, 3);
function add_variation_quantity_step($loop, $variation_data, $variation) {
    // Generate nonce field within the hooked function
    wp_nonce_field( 'save_quantity_step_action', 'quantity_step_nonce' );

    woocommerce_wp_text_input(array(
        'id' => "variable_quantity_step_{$loop}",
        'name' => "variable_quantity_step[{$loop}]",
        'label' => __('Quantity Step', 'product-quantity-steps'),
        'value' => get_post_meta($variation->ID, '_quantity_step', true),
        'desc_tip' => true,
        'description' => __('Set the quantity step for this variation.', 'product-quantity-steps'),
        'type' => 'number',
        'custom_attributes' => array(
            'step' => 'any',
            'min' => '0',
        ),
    ));
}

// Save variation quantity steps
add_action('woocommerce_save_product_variation', 'save_variation_quantity_step', 10, 2);
function save_variation_quantity_step( $variation_id, $i ) {
    // **1. Check if the nonce is set**
    if ( ! isset( $_POST['quantity_step_nonce'] ) ) {
        return; // Nonce not set, exit the function.
    }

    // **2. Unsash and Sanitize the Nonce in a Single Line**
    $nonce = sanitize_text_field( wp_unslash( $_POST['quantity_step_nonce'] ) );

    // **3. Verify the nonce**
    if ( ! wp_verify_nonce( $nonce, 'save_quantity_step_action' ) ) {
        return; // Nonce verification failed, exit the function.
    }

    // **4. Check if the current user has permission to edit the post**
    if ( ! current_user_can( 'edit_post', $variation_id ) ) {
        return; // User doesn't have permission, exit the function.
    }

    // **5. Ensure 'variable_quantity_step' is set and is an array**
    if ( ! isset( $_POST['variable_quantity_step'] ) || ! is_array( $_POST['variable_quantity_step'] ) ) {
        return; // Data not set or not in expected format, exit the function.
    }

    // **6. Check if the specific index exists in the array**
    if ( ! isset( $_POST['variable_quantity_step'][ $i ] ) ) {
        return; // Specific variation data not set, exit the function.
    }

    // **7. Unsash and Sanitize the Input**
    $quantity_step = sanitize_text_field( wp_unslash( $_POST['variable_quantity_step'][ $i ] ) );

    // **8. Update the post meta with the sanitized value**
    update_post_meta( $variation_id, '_quantity_step', $quantity_step );
}
