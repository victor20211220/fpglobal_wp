<?php
/**
 * Template Name: Periskope Messages
 */

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <h1>Periskope Messages</h1>
        <table id="periskope-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Org ID</th>
                    <th>Chat ID</th>
                    <th>Unique ID</th>
                    <th>Author</th>
                    <th>Body</th>
                    <th>From Me</th>
                    <th>Is Deleted</th>
                    <th>Timestamp</th>
                    <th>Updated At</th>
                    <th>Has Media</th>
                    <th>Media</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be inserted here by Ajax -->
            </tbody>
        </table>
    </main>
</div>

<?php
get_footer();
?>
