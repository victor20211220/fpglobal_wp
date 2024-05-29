<?php
/*
Plugin Name: Custom Webhook Handler
Description: A plugin to handle incoming webhooks.
Version: 1.0
Author: Your Name
*/

// Hook to initialize our webhook handling
add_action('rest_api_init', function () {
    register_rest_route('webhook/v1', '/message_created', array(
        'methods' => 'POST',
        'callback' => 'handle_message_created',
    ));
    register_rest_route('webhook/v1', '/message_updated', array(
        'methods' => 'POST',
        'callback' => 'handle_message_updated',
    ));
});

function handle_message_created(WP_REST_Request $request) {
    // Retrieve the parameters from the request
    $parameters = $request->get_params();

    // Process the parameters as needed
    // For example, you can log them, save them to the database, etc.
    // Here, we're just logging the data
    if (!empty($parameters)) {
        error_log(print_r($parameters, true));
    }

    // Return a response
    return new WP_REST_Response('Message create webhook received', 200);
}


function handle_message_updated(WP_REST_Request $request) {
    // Retrieve the parameters from the request
    $parameters = $request->get_params();

    // Process the parameters as needed
    // For example, you can log them, save them to the database, etc.
    // Here, we're just logging the data
    if (!empty($parameters)) {
        error_log(print_r($parameters, true));
    }

    // Return a response
    return new WP_REST_Response('Message create webhook received', 200);
}
