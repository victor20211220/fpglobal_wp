<?php
/**
 * File: class-wpglobus-builder-rank_math_seo.php
 *
 * @since   2.4.3
 * @since   2.8.9 Added support REST API request.
 *
 * @package WPGlobus\Builders\RankMathSEO.
 * Author  Alex Gor(alexgff)
 */

if ( ! class_exists( 'WPGlobus_Builder_RankMathSEO' ) ) :

	/**
	 * Class WPGlobus_Builder_RankMathSEO.
	 */
	class WPGlobus_Builder_RankMathSEO {

		/**
		 * Options titles.
		 * See section Titles&Meta.
		 */
		protected static $options_titles = 'rank-math-options-titles';

		/**
		 * Get attributes.
		 */
		public static function get_attrs( $attrs ) {

			/**
			 * Global
			 *
			 * @global string $pagenow
			 */
			global $pagenow;

			if ( 'post.php' === $pagenow ) {

				$post_type = 'post';
				if ( ! empty( $attrs['post_type'] ) ) {
					$post_type = $attrs['post_type'];
				}

				$opts = get_option( self::$options_titles );

				if ( ! empty( $opts["pt_{$post_type}_add_meta_box"] ) && 'off' === $opts["pt_{$post_type}_add_meta_box"] ) {
					$attrs = false;
				} else {
					$attrs['builder_page'] = true;
				}

				return $attrs;

			} elseif ( 'term.php' === $pagenow ) {

				/**
				 * Current language will be set correctly from $_REQUEST['language']
				 *
				 * See wpglobus\includes\builders\class-wpglobus-config-builder.php
				 */

				$tax    = WPGlobus_WP::get_http_get_parameter( 'taxonomy' );
				$tag_ID = WPGlobus_WP::get_http_get_parameter( 'tag_ID' );

				if ( $tax ) {

					$opts = get_option( self::$options_titles );

					if ( ! empty( $opts["tax_{$tax}_add_meta_box"] ) && 'off' === $opts["tax_{$tax}_add_meta_box"] ) {
						$attrs = false;
					} else {
						$attrs['post_type']    = ''; // reset post type.
						$attrs['taxonomy']     = $tax;
						$attrs['tag_ID']       = $tag_ID;
						$attrs['builder_page'] = true;
					}

					return $attrs;
				}

				/**
				 * } elseif ( 'edit.php' === $pagenow ) {
				 *
				 * // return $attrs;
				 */

			} elseif ( isset( $attrs['rest_request'] ) && $attrs['rest_request'] ) {

				/**
				 * Get post ID, term, term ID, language from REST API request.
				 *
				 * @since 2.8.9
				 */
				if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {

					preg_match( '/\?post=(?<postID>[^&]+)/', WPGlobus_WP::http_referer(), $matches );

					if ( ! empty( $matches['postID'] ) && (int) $matches['postID'] > 0 ) {

						$attrs['post_id'] = $matches['postID'];

					} else {

						preg_match( '/term\.php\?taxonomy=(?<tax>[^&]+)&tag_ID=(?<id>[^&]+)/', WPGlobus_WP::http_referer(), $matches );
						preg_match( '/&language=(?<language>[a-z]{2})/', WPGlobus_WP::http_referer(), $lang_matches );

						if ( ! empty( $matches['tax'] ) && ! empty( $matches['id'] ) && (int) $matches['id'] > 0 ) {

							$attrs['taxonomy'] = $matches['tax'];
							$attrs['tag_ID']   = $matches['id'];

							/**
							 * Get language for REST API request.
							 */
							if ( ! empty( $lang_matches['language'] ) ) {
								$attrs['rest_language'] = $lang_matches['language'];
							}

						}
					}
				}

				$attrs['builder_page'] = true;

				return $attrs;
			}

			return false;
		}
	}

endif;
