<?php
/**
 * Template Name: Periskope Messages
 */

get_header();

// Fetch hashtags from the database
global $wpdb;
$hashtags_table = $wpdb->prefix . 'periskope_hashtags';
$hashtags = $wpdb->get_results( "SELECT name FROM $hashtags_table", ARRAY_A );
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <header class="entry-header has-text-align-center header-footer-group">

	<div class="entry-header-inner section-inner medium">

		        <?php
        the_title( '<h1 class="entry-title">', '</h1>' );
        ?>
	</div><!-- .entry-header-inner -->

</header>

		<div class="entry-content">
			
			<?php
			if ( is_search() || ! is_singular() && 'summary' === get_theme_mod( 'blog_content', 'full' ) ) {
				the_excerpt();
			} else {
				the_content( __( 'Continue reading', 'twentytwenty' ) );
			}
			?>
		</div><!-- .entry-content -->
        <div id="hastagFilterDiv">
            <select id="hashtag-filter" multiple="multiple">
                <?php foreach($hashtags as $hashtag): ?>
                    <option value="<?php echo esc_attr($hashtag['name']); ?>"><?php echo esc_html($hashtag['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="chat-container">
            <!-- Chat messages will be inserted here by Ajax -->
        </div>
    </main>
</div>

<!-- Include Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/libphonenumber-js@1.9.28/bundle/libphonenumber-min.js"></script>
<?php
get_footer();
?>
