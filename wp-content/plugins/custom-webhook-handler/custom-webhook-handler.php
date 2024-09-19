<?php
/*
Plugin Name: Custom Webhook Handler
Description: A plugin to handle incoming webhooks.
Version: 1.0
Author: Your Name
*/
global $wpdb;
$org_phone = "41766998778";
$token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCIgOiAiODQwZDcxMDQtZGM1MC00YjU5LWEzMTAtYzFlMDZiMzkyYmVhIiwgInJvbGUiIDogImFwaSIsICJ0eXBlIiA6ICJhcGkiLCAibmFtZSIgOiAid29yZHByZXNzIiwgImV4cCIgOiAyMDMyMDI5MjY3LjU0MDAxNCwgImlhdCIgOiAxNzE2NDk2NDY3LjU0MDAxNCwgInN1YiIgOiAiOGM4MTU3ODYtM2I2Ni00MDZmLWJhMTMtOWQzY2JkOGMxOGQ2IiwgImlzcyIgOiAicGVyaXNrb3BlLmFwcCJ9.vjF-b9mrzYvTaVHXKWWKi32mFJyoB6F9Gt3Br7LUsuM";
$org_id = "8c815786-3b66-406f-ba13-9d3cbd8c18d6";
$chat_id = "120363321968692144@g.us"; //120363302018317627@g.us
$storage_domain = "";
$table_name = $wpdb->prefix . 'periskope_messages';
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$hashtags_table = $wpdb->prefix . 'periskope_hashtags';

function getHashTags($localeCheck = true)
{
    global $hashtags_table, $wpdb;
    $locale = get_locale();
    $where = $localeCheck ? " WHERE locale = '" . $locale . "'" : "";
    $hashtags = $wpdb->get_results("SELECT id, name, type FROM $hashtags_table" . $where, ARRAY_A);
    return $hashtags;
}

$hashtags = getHashTags(false);
// Initialize arrays to hold column names for type 0 and type 1
$region_hash_tags = [];
$challenge_hash_tags = [];
// Process the results
foreach ($hashtags as $row) {
    $name = $row['name'];
    $type = $row['type'];
    if ($type == 0) {
        $region_hash_tags[] = $name;
    } elseif ($type == 1) {
        $challenge_hash_tags[] = $name;
    }
}
$autoreply_message_body = "See https://fpglobal.ca/hashtags/ for a list of all hashtags.";

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
    register_rest_route('periskope/v1', '/messages', array(
        'methods' => 'GET',
        'callback' => 'periskope_get_messages',
    ));
});

function handle_message_created(WP_REST_Request $request)
{
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


function handle_message_updated(WP_REST_Request $request)
{
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


function handle_message_deleted(WP_REST_Request $request)
{
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

function checkData($params)
{
    if (empty($params) && !isset($params['data'])) {
        error_log(print_r($params, true));
        return new WP_REST_Response('No data received', 200);
    }
    $data = $params['data'];
    global $org_id, $chat_id;
    if ($data['org_id'] !== $org_id || $data['chat_id'] !== $chat_id) {
        return new WP_REST_Response('Another group/chat message received', 200);
    }
//    if ($params['event_type'] === "message.deleted" && site_url() === "http://127.0.0.13") { // add local check
    if ($params['event_type'] === "message.deleted") return true;
    $message_body = $data['body'];
    $has_region_hashtag = false;
    $has_challenge_hashtag = false;
    global $region_hash_tags, $challenge_hash_tags;
    // Check for type 0 hashtags
    foreach ($region_hash_tags as $hashtag) {
        if (stripos($message_body, $hashtag) !== false) {
            $has_region_hashtag = true;
            break;
        }
    }

// Check for type 1 hashtags
    foreach ($challenge_hash_tags as $hashtag) {
        if (stripos($message_body, $hashtag) !== false) {
            $has_challenge_hashtag = true;
            break;
        }
    }
    // Determine if $a includes at least one hashtag from each type
//    error_log(print_r($data, true));
    $hasHashTags = $has_region_hashtag && $has_challenge_hashtag;
    $isAudioMsg = $data['has_media'] && (strpos($data['media']['mimetype'], 'audio') === 0);
    if ($hasHashTags) return true;
    $curl = curl_init();
    global $token, $org_phone, $autoreply_message_body;

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://35.227.206.185/v1/message/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
                    "reply_to": "' . $data['message_id'] . '",
                    "message": "' . $autoreply_message_body . '",
                    "chat_id": "' . $data['author'] . '"
                }',
        CURLOPT_HTTPHEADER => array(
            'x-phone: ' . $org_phone,
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ),
    ));
    $response = curl_exec($curl);
    // Check for errors
    if (curl_errno($curl)) {
        $log_message = 'Curl error: ' . curl_error($curl);
    } else {
        // Get the HTTP status code
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Output the HTTP status code
        $log_message = "HTTP Status Code: " . $http_code;
    }
    curl_close($curl);

    // Output the HTTP status code
    error_log("autoreply message curl result: " . $log_message);
    return true;
}

function insert_bulk_messages(WP_REST_Request $request)
{
    $body_params = $request->get_json_params();

    if (!isset($body_params['messages']) || !is_array($body_params['messages'])) {
        return new WP_REST_Response('Invalid payload', 400);
    }

    $messages = $body_params['messages'];
    $endpoint_url = site_url() . '/wp-json/webhook/v1/message_created'; // Replace with your target endpoint

    foreach ($messages as $message) {
        send_message_to_endpoint($endpoint_url, array('data' => $message));
    }

    return new WP_REST_Response('Messages processed', 200);
}

function send_message_to_endpoint($url, $message)
{
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

function periskope_get_messages(WP_REST_Request $request)
{
    global $wpdb, $table_name, $hashtags_table;
    $hashtags = getHashTags();
    // Set headers to prevent caching
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');

    $offset = $request->get_param('offset');
    $limit = $request->get_param('limit');
    $hashtagsStr = $request->get_param('hashtags');

    $hashtagFilter = '';
    $hashtagsAry = [];
    if (!empty($hashtagsStr)) {
        $hashtagsAry = explode(',', $hashtagsStr);
//    } else {
//        foreach ($hashtags as $hashtag) {
//            $hashtagsAry[] = $hashtag['id'];
//        }
//    }


        $allSameHashTags = [];
        foreach ($hashtagsAry as $hashtag) {
            $oneSameHashTags = [$hashtag];
            $language = $wpdb->get_results("SELECT language FROM $hashtags_table WHERE id = " . $hashtag, ARRAY_A)[0]['language'];
            if (!$language) {
                $allSameHashTags[] = $oneSameHashTags;
                continue;
            }
            $sameHashTags = $wpdb->get_results("SELECT id FROM $hashtags_table WHERE language = '" . $language . "' AND id != " . $hashtag, ARRAY_A);
            foreach ($sameHashTags as $sameHashTag) {
                $oneSameHashTags[] = $sameHashTag['id'];
            }
            $allSameHashTags[] = $oneSameHashTags;
        }


        function get_all_child_ids($parent_ids, $wpdb)
        {
            global $hashtags_table;
            $all_ids = [];

            // Ensure parent_ids is an array
            if (!is_array($parent_ids)) {
                $parent_ids = [$parent_ids];
            }

            // Add the parent_ids to the all_ids array
            $all_ids = array_merge($all_ids, $parent_ids);

            // Prepare the query to fetch child IDs
            $placeholders = implode(',', array_fill(0, count($parent_ids), '%d'));
            $query = "SELECT id FROM $hashtags_table WHERE parent_id IN ($placeholders)";

            // Execute the query
            $child_ids = $wpdb->get_col($wpdb->prepare($query, ...$parent_ids));

            // If there are child IDs, recursively fetch their children
            if (!empty($child_ids)) {
                $child_ids = get_all_child_ids($child_ids, $wpdb);
                $all_ids = array_merge($all_ids, $child_ids);
            }

            return $all_ids;
        }

        function build_filter_query($allSameHashTags, $wpdb)
        {
            global $hashtags_table;
            $all_filter_blocks = [];
            foreach ($allSameHashTags as $sameHashTag) {
                $one_filter_block = [];
                foreach ($sameHashTag as $id) {
                    // Get all related IDs (including children) for the current ID
                    $related_ids = get_all_child_ids([$id], $wpdb);

                    // Create an "OR" block for these related IDs
                    $placeholders = implode(',', array_fill(0, count($related_ids), '%d'));
                    $query = "SELECT name FROM $hashtags_table WHERE id IN ($placeholders)";
                    $names = $wpdb->get_col($wpdb->prepare($query, ...$related_ids));

                    if (!empty($names)) {
                        // Manually escape the names and build the LIKE conditions
                        $name_conditions = array_map(function ($name) use ($wpdb) {
                            // Escape any special characters except %
                            $escaped_name = addslashes($name);
                            return "a.body LIKE '%" . $escaped_name . "%'";
                        }, $names);
                        $one_filter_block[] = '(' . implode(' OR ', $name_conditions) . ')';
                    }
                }
                $one_filter_block = implode(' OR ', $one_filter_block);
                $all_filter_blocks[] = '(' . $one_filter_block . ')';
            }
            // Join all filter blocks with "AND"
            $final_query = implode(' AND ', $all_filter_blocks);
            return $final_query;
        }

        // Construct the final WHERE clause for the query
        $hashtagFilter = 'AND (' . build_filter_query($allSameHashTags, $wpdb) . ')';
    }
//    print_r($hashtagFilter);exit();
    global $autoreply_message_body;
    $autoreply_message_filter = 'AND NOT(a.has_quoted_msg = 1 AND a.body = "' . $autoreply_message_body . '")';

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT a.*, b.message_id as quote_message_id, b.author as quote_author, b.body as quote_body, b.has_media as quote_has_media, b.media as quote_media
        FROM $table_name as a
        LEFT JOIN $table_name as b ON a.quoted_message_id = b.message_id
        WHERE ISNULL(a.is_deleted) $autoreply_message_filter $hashtagFilter
        ORDER BY a.timestamp DESC
        LIMIT %d OFFSET %d",
        $limit,
        $offset
    ), ARRAY_A);
    return new WP_REST_Response($results, 200);
}

function periskope_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'id' => '',
    ), $atts, 'periskope');

    $hashtag_ids = !empty($atts['id']) ? array_map('intval', explode(',', $atts['id'])) : [];

    return periskope_generate_html($hashtag_ids);
}

add_shortcode('periskope', 'periskope_shortcode');

function periskope_generate_html($selected_hashtag_ids = [])
{
    $hashtags = getHashTags();
    ob_start();
    ?>
    <div id="hastagFilterDiv">
        <select id="hashtag-filter" multiple="multiple">
            <?php foreach ($hashtags as $hashtag):
                $selected = in_array($hashtag['id'], $selected_hashtag_ids) ? "selected=\"selected\"" : "";
                ?>
                <option value="<?php echo esc_attr($hashtag['id']); ?>" <?php echo $selected ?>><?php echo esc_html($hashtag['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="chat-container">
        <!-- Chat messages will be inserted here by Ajax -->
    </div>
    <?php
    return ob_get_clean();
}

function get_hashtag_hierarchy($parent_id = NULL, $type = NULL, $level = 0)
{
    global $wpdb, $hashtags_table;

    if ($parent_id === NULL) {
        // Handle the case when parent_id is NULL
        $query = $wpdb->prepare("
        SELECT id, name, type, parent_id 
        FROM $hashtags_table 
        WHERE parent_id IS NULL AND type = %d 
        ORDER BY name ASC", $type);
    } else {
        // Handle the case when parent_id is a specific value
        $query = $wpdb->prepare("
        SELECT id, name, type, parent_id 
        FROM $hashtags_table 
        WHERE parent_id = %d AND type = %d 
        ORDER BY name ASC", $parent_id, $type);
    }
    $rows = $wpdb->get_results($query);

    // Build hierarchy recursively
    $output = '';
    foreach ($rows as $row) {
        $indentation = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $level); // 4 non-breaking spaces for each level
        $output .= $indentation . esc_html($row->name) . '<br>';
        $output .= get_hashtag_hierarchy($row->id, $type, $level + 1); // Recursive call for children
    }
    return $output;
}

function display_hashtag_list()
{
    $output = '<div style="display: flex; justify-content: space-between;">';

    // Fetch and display "challenge" (type = 1) hashtags
    $output .= '<div><strong>Challenge</strong><br>';
    $output .= get_hashtag_hierarchy(NULL, 1);
    $output .= '</div>';

    // Fetch and display "region" (type = 0) hashtags
    $output .= '<div><strong>Region</strong><br>';
    $output .= get_hashtag_hierarchy(NULL, 0);
    $output .= '</div>';

    $output .= '</div>';

    return $output;
}

// Shortcode to display the hashtag list
add_shortcode('list_hashtags', 'display_hashtag_list');
