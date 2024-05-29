<?php
/*
Plugin Name: Custom Webhook Handler
Description: A plugin to handle incoming webhooks.
Version: 1.0
Author: Your Name
*/
global $wpdb;
$table_name = $wpdb->prefix . 'periskope_messages';
$org_id = "8c815786-3b66-406f-ba13-9d3cbd8c18d6";
$chat_id = "120363302018317627@g.us";
$storage_domain = "https://wvtpktbhobaopdiawdli.supabase.co/";
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

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
    register_rest_route('webhook/v1', '/message_deleted', array(
        'methods' => 'POST',
        'callback' => 'handle_message_deleted',
    ));
    register_rest_route('webhook/v1', '/insert_bulk_messages', array(
        'methods' => 'POST',
        'callback' => 'insert_bulk_messages',
        'permission_callback' => '__return_true',
    ));
    register_rest_route( 'periskope/v1', '/messages', array(
        'methods'  => 'GET',
        'callback' => 'periskope_get_messages',
    ));
});

function handle_message_created(WP_REST_Request $request) {
    // Retrieve the parameters from the request
    $body_params = $request->get_params();
    // Call checkData and handle its response
    $check_response = checkData($body_params);
    error_log(print_r($check_response, true));
    if ($check_response instanceof WP_REST_Response) {
        return $check_response;
    }
    // Prepare data for insertion
    $data = $body_params['data'];
    $insert_data = array(
        'org_id' => $data['org_id'],
        'chat_id' => $data['chat_id'],
        'message_id' => $data['message_id'],
        'author' => $data['author'],
        'body' => $data['body'],
        'has_quoted_msg' => $data['has_quoted_msg'],
        'quoted_message_id' => $data['quoted_message_id'],
        'from_me' => $data['from_me'],
        'is_deleted' => $data['is_deleted'],
        'timestamp' => $data['timestamp'],
        'updated_at' => $data['updated_at'],
        'has_media' => $data['has_media'],
        'media' => json_encode((object)$data['media']),
    );

    // Insert data into the database
    global $wpdb, $table_name;
    $inserted = $wpdb->insert($table_name, $insert_data);

    if ($inserted) {
        return new WP_REST_Response('Message created successfully', 200);
    } else {
        return new WP_REST_Response('Failed to save message', 500);
    }
}


function handle_message_updated(WP_REST_Request $request) {
    // Retrieve the parameters from the request
    $body_params = $request->get_params();
    checkData($body_params);
    // Prepare data for insertion
    $data = $body_params['data'];
    $update_data = array(
        'body' => $data['body'],
        'has_quoted_msg' => $data['has_quoted_msg'],
        'quoted_message_id' => $data['quoted_message_id'],
        'updated_at' => $data['updated_at'],
        'has_media' => $data['has_media'],
        'media' => json_encode((object)$data['media']),
    );

    // Insert data into the database
    global $wpdb, $table_name;
    // Where clause to specify the row to update
    $where = array(
        'message_id' => $data['message_id']
    );

    // Update the row in the table
    $updated = $wpdb->update($table_name, $update_data, $where);

    if ($updated) {
        return new WP_REST_Response('Message updated successfully', 200);
    } else {
        return new WP_REST_Response('Failed to update message', 500);
    }
}


function handle_message_deleted(WP_REST_Request $request) {
    // Retrieve the parameters from the request
    $body_params = $request->get_params();
    checkData($body_params);
    // Prepare data for insertion
    $data = $body_params['data'];
    $delete_data = array(
        'updated_at' => $data['updated_at'],
        'is_deleted' => $data['is_deleted'],
    );

    // Insert data into the database
    global $wpdb, $table_name;
    // Where clause to specify the row to update
    $where = array(
        'message_id' => $data['message_id']
    );

    // Update the row in the table
    $updated = $wpdb->update($table_name, $delete_data, $where);

    if ($updated) {
        return new WP_REST_Response('Message deleted successfully', 200);
    } else {
        return new WP_REST_Response('Failed to delete message', 500);
    }
}

function checkData($params){
    if (empty($params) && !isset($params['data'])) {
        error_log(print_r($params, true));
        return new WP_REST_Response('No data received', 200);
    }
    $data = $params['data'];
    global $org_id, $chat_id;
    if($data['org_id'] !== $org_id || $data['chat_id'] !== $chat_id){
        return new WP_REST_Response('Another group/chat message received', 200);
    }
    return true;
}
function insert_bulk_messages(WP_REST_Request $request) {
    $body_params = $request->get_json_params();

    if (!isset($body_params['messages']) || !is_array($body_params['messages'])) {
        return new WP_REST_Response('Invalid payload', 400);
    }

    $messages = $body_params['messages'];
    $endpoint_url = site_url().'/wp-json/webhook/v1/message_created'; // Replace with your target endpoint

    foreach ($messages as $message) {
        send_message_to_endpoint($endpoint_url, array('data' => $message));
    }

    return new WP_REST_Response('Messages processed', 200);
}

function send_message_to_endpoint($url, $message) {
    $ch = curl_init($url);
    $payload = json_encode($message);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true); // Ensure the request is a POST

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($httpcode != 200) {
        error_log('Failed to send message: ' . print_r($response, true));
    }
}

function periskope_get_messages( WP_REST_Request $request ) {
    global $wpdb, $table_name;
    // Set headers to prevent caching
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    $results = $wpdb->get_results( "SELECT a.*, b.message_id as quote_message_id, b.author as quote_author, b.body as quote_body, b.has_media as quote_has_media, b.media as quote_media FROM $table_name as a LEFT JOIN $table_name as b ON a.quoted_message_id = b.message_id WHERE ISNULL(a.is_deleted) ORDER BY a.timestamp ASC", ARRAY_A );
    return new WP_REST_Response( $results, 200 );
}