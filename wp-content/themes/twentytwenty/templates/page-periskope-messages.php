<?php
/**
 * Template Name: Periskope Messages
 */

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <h3 class="text-center">Periskope Messages</h3>
        <div id="chat-container">
            <!-- Chat messages will be inserted here by Ajax -->
        </div>
    </main>
</div>
<style>
    .text-center {
        text-align: center;
    }

    #chat-container {
        width: 100%;
        max-width: 900px;
        margin: 0 auto;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 10px;
        background-color: #f9f9f9;
        overflow-y: auto;
        height: 80vh;
        display: flex;
        flex-direction: column;
    }

    .message {
        display: flex;
        flex-direction: column;
        padding: 10px;
        margin: 5px 0;
        border-radius: 5px;
        max-width: 80%;
        word-wrap: break-word;
        min-width: 130px;
        max-width: 450px !important;
    }

    .message.sent {
        background-color: #dcf8c6;
        align-self: flex-end;
        text-align: right;
    }

    .message.received {
        background-color: #ffffff;
        align-self: flex-start;
        text-align: left;
    }

    .message .author {
        font-weight: 600;
        margin-bottom: 5px;
        font-size: 12px;
    }

    .message .body {
        margin-bottom: 5px;
        font-size: 13.5px;
    }

    .message .timestamp {
        font-size: 0.8em;
        color: #999;
        font-size: 10px;
        line-height: 12px;
        text-align: right;
    }

    .message.sent .timestamp {
        text-align: right;
    }

    .message.received .timestamp {
        /* text-align: left; */
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/libphonenumber-js@1.9.28/bundle/libphonenumber-min.js"></script>
<?php
get_footer();
?>
