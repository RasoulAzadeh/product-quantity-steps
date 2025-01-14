<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Enqueue the jQuery script on product pages
add_action( 'wp_enqueue_scripts', 'enqueue_quantity_step_script' );
function enqueue_quantity_step_script() {
    if ( is_product() ) {
        wp_enqueue_script(
            'quantity-step-script',
            plugin_dir_url( __FILE__ ) . '../../assets/js/public.js',
            array( 'jquery' ),
            filemtime( plugin_dir_path( __FILE__ ) . '../../assets/js/public.js' ),
            true
        );

        global $post;
        $product = wc_get_product( $post->ID );
        if ( ! $product ) {
            return;
        }

        $quantity_data = array(
            'product_id'  => $product->get_id(),
            'is_variable' => $product->is_type( 'variable' ),
            'steps'       => array(),
        );

        // Define a unique cache key based on product ID and product type
        $cache_key = 'quantity_step_data_' . $product->get_id();
        $cached_quantity_data = get_transient( $cache_key );

        if ( false === $cached_quantity_data ) {
            // Cache miss: Fetch and prepare the quantity step data

            if ( $product->is_type( 'variable' ) ) {
                // Get all variation IDs
                $variation_ids = $product->get_children();

                // Ensure all variation IDs are integers
                $variation_ids = array_map( 'intval', $variation_ids );

                if ( ! empty( $variation_ids ) ) {
                    global $wpdb;

                    // Prepare placeholders for SQL query
                    $placeholders = implode( ',', array_fill( 0, count( $variation_ids ), '%d' ) );

                    // Prepare the SQL query to fetch all _quantity_step meta values for the given post IDs

                    // Construct the SQL query with placeholders
                    $sql = "
                        SELECT post_id, meta_value
                        FROM {$wpdb->postmeta}
                        WHERE meta_key = %s
                        AND post_id IN ( $placeholders )
                    ";

                    // Merge the meta_key with the variation IDs for the prepare statement
                    $prepare_args = array_merge( [ $sql, '_quantity_step' ], $variation_ids );

                    // Prepare the SQL query securely
                    $query_string = call_user_func_array( [ $wpdb, 'prepare' ], $prepare_args );

                    // Execute the query and fetch the results
                    $results = $wpdb->get_results( $query_string );

                    // Initialize the meta_results array
                    $meta_results = array();

                    // Process each row in the results
                    if ( $results ) {
                        foreach ( $results as $row ) {
                            $meta_results[] = array(
                                'post_id'    => $row->post_id,
                                'meta_value' => maybe_unserialize( $row->meta_value ), // Handle serialized data if needed
                            );
                        }
                    }

                    // Map variation IDs to their quantity steps
                    foreach ( $meta_results as $meta ) {
                        $quantity_data['steps'][ $meta['post_id'] ] = intval( $meta['meta_value'] );
                    }
                }
            } else {
                // For simple products, fetch the _quantity_step meta
                $quantity_step = get_post_meta( $product->get_id(), '_quantity_step', true );
                if ( $quantity_step ) {
                    $quantity_data['steps']['simple'] = intval( $quantity_step );
                }
            }

            // Store the prepared data in the transient for 12 hours
            set_transient( $cache_key, $quantity_data, 12 * HOUR_IN_SECONDS );
        } else {
            // Cache hit: Use the cached quantity step data
            $quantity_data = $cached_quantity_data;
        }

        wp_localize_script( 'quantity-step-script', 'quantityStepData', $quantity_data );
    }
}

// Validate quantity steps on the server side at checkout
add_action( 'woocommerce_check_cart_items', 'validate_quantity_steps_in_cart' );
function validate_quantity_steps_in_cart() {
    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        $product_id = $cart_item['product_id'];
        $quantity_step = get_post_meta( $product_id, '_quantity_step', true );

        if ( $quantity_step && ( intval( $cart_item['quantity'] ) % intval( $quantity_step ) !== 0 ) ) {
            wc_add_notice( __( 'Please adjust quantities to match the required quantity steps.', 'product-quantity-steps' ), 'error' );
        }
    }
}

// Clear the transient cache when a product or its variations are updated
add_action( 'save_post_product', 'clear_quantity_step_cache', 10, 3 );
add_action( 'save_post_product_variation', 'clear_quantity_step_cache', 10, 3 );
function clear_quantity_step_cache( $post_id, $post, $update ) {
    // Only clear cache for published products
    if ( 'publish' !== $post->post_status ) {
        return;
    }

    // Determine the product ID
    if ( 'product_variation' === $post->post_type ) {
        $parent_id = wp_get_post_parent_id( $post_id );
        if ( $parent_id ) {
            $product_id = $parent_id;
        }
    } elseif ( 'product' === $post->post_type ) {
        $product_id = $post_id;
    } else {
        return;
    }

    // Delete the transient
    $cache_key = 'quantity_step_data_' . $product_id;
    delete_transient( $cache_key );
}
