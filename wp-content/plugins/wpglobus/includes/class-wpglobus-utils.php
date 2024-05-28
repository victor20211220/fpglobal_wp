<?php
/**
 * File: class-wpglobus-utils.php
 *
 * @package WPGlobus
 */

/**
 * Class WPGlobus_Utils
 */
class WPGlobus_Utils {

	/**
	 * Localize URL by inserting language prefix
	 *
	 * @param string          $url      URL to localize
	 * @param string          $language Language code
	 * @param WPGlobus_Config $config   Alternative configuration (i.e. Unit Test mock object)
	 *
	 * @return string
	 */
	public static function localize_url( $url = '', $language = '', WPGlobus_Config $config = null ) {
		/**
		 * Use the global configuration if alternative not passed
		 */
		if ( null === $config ) {
			// @codeCoverageIgnoreStart
			$config = WPGlobus::Config();
		}
		// @codeCoverageIgnoreEnd

		/**
		 * Use the current language if not passed
		 */
		$language = empty( $language ) ? $config->language : $language;

		/**
		 * Local cache to speed-up processing on pages with multiple links.
		 */
		static $cache = array();
		if ( isset( $cache[ $language ][ $url ] ) ) {
			return $cache[ $language ][ $url ];
		}
		if ( ! isset( $cache[ $language ] ) ) {
			$cache[ $language ] = array();
		}

		/**
		 * In Admin-Settings-General:
		 * WordPress Address (URL) is site_url()
		 * Site Address (URL) is home_url
		 * We need home_url, and we cannot use the @home_url function,
		 * because it will filter back here causing endless loop.
		 *
		 * @todo Multisite?
		 */
		$home_url = get_option( 'home' );

		/**
		 * `hide_default_language` means "Do not use language code in the default URL"
		 * So, no /en/page/, just /page/
		 */
		if ( $language === $config->default_language && $config->hide_default_language ) {
			$language_url_prefix = '';
		} else {
			/**
			 * Language prefix looks like '/ru'
			 */
			$language_url_prefix = '/' . $language;
		}

		/**
		 * For the following regex, we need home_url without prefix
		 * http://www.example.com becomes example.com
		 */
		$home_domain_tld = self::domain_tld( $home_url );

		/**
		 * Regex to replace current language prefix with the requested one.
		 *
		 * @example ^(https?:\/\/(?:.+\.)?example\.com)(?:\/?(?:en|ru|pt))?($|\/$|[\/#\?].*$)
		 */

		/**
		 * The "host+path" part of the URL (captured)
		 * We ignore http(s) and domain prefix, but we must match the domain-tld, so any external URLs
		 * are not localized.
		 */
		// Because `parse_url` may return `null`, do `str_replace` separately (PHP81 deprecated).
		$path_home = wp_parse_url( $home_url, PHP_URL_PATH );
		$path_home = is_string( $path_home ) ? str_replace( '/', '\/', $path_home ) : '';

		$re_host_part = '(https?:\/\/(?:.+\.)?' .
						str_replace( '.', '\.', $home_domain_tld ) .
						$path_home
						. ')';

		/**
		 * The "language" part (optional, not captured, will be thrown away)
		 */
		$re_language_part = '(?:\/?(?:' . implode( '|', $config->enabled_languages ) . '))?';

		/**
		 * The rest of the URL. Can be:
		 * - Nothing or trailing slash, or
		 * - Slash, hash or question and optionally anything after
		 * *
		 * Using 'or' regex to capture things like '/rush' or '/designer/' correctly,
		 * and not extract '/ru' or '/de' from them,
		 */
		$re_trailer = '(\/?|[\/#\?].*)';

		$re = '!^' . $re_host_part . $re_language_part . $re_trailer . '$!';

		/**
		 * Replace the existing (or empty) language prefix with the requested one
		 */
		$localized_url = preg_replace( $re, '\1' . $language_url_prefix . '\2', $url );

		/**
		 * Cache it.
		 */
		$cache[ $language ][ $url ] = $localized_url;

		return $localized_url;
	}

	/**
	 * Extract language from URL
	 * http://example.com/ru/page/ returns 'ru'
	 *
	 * @param string          $url
	 * @param WPGlobus_Config $config Alternative configuration (i.e. Unit Test mock object)
	 *
	 * @return string
	 */
	public static function extract_language_from_url( $url = '', WPGlobus_Config $config = null ) {

		$language = '';

		if ( ! is_string( $url ) ) {
			return $language;
		}

		/**
		 * Use the global configuration if alternative not passed
		 */
		if ( null === $config ) {
			// @codeCoverageIgnoreStart
			$config = WPGlobus::Config();
		}
		// @codeCoverageIgnoreEnd

		$path = wp_parse_url( $url, PHP_URL_PATH );

		// Because `parse_url` may return `null`, do `untrailingslashit` separately.
		$path_home = wp_parse_url( get_option( 'home' ), PHP_URL_PATH );
		$path_home = is_string( $path_home ) ? untrailingslashit( $path_home ) : '';

		/**
		 * Regex to find the language prefix.
		 *
		 * @example !^/(en|ru|pt)/!
		 */
		$re = '!^' . $path_home .
			  '/(' . implode( '|', $config->enabled_languages ) . ')(?:/|$)!';

		if ( preg_match( $re, $path, $match ) ) {
			// Found language information
			$language = $match[1];
		}

		return $language;

	}

	/**
	 * Check if was called by a specific function (could be any levels deep).
	 *
	 * @see        WPGlobus_WP::is_function_in_backtrace()
	 *
	 * @param string|callable $function_name Function name or array(class,function).
	 *
	 * @return bool True if Function is in backtrace.
	 * @deprecated 1.7.7 Use WPGlobus_WP::is_function_in_backtrace()
	 */
	public static function is_function_in_backtrace( $function_name ) {
		_deprecated_function( __METHOD__, 'WPGlobus 1.7.7', 'WPGlobus_WP::is_function_in_backtrace()' );

		return WPGlobus_WP::is_function_in_backtrace( $function_name );
	}

	/**
	 * Strip the prefix from the host name
	 * http://www.example.com becomes example.com
	 *
	 * @since 1.0.12
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function domain_tld( $url ) {

		$pre = '';
		/**
		 * Short-circuit processing to provide own return for the cases not covered by the algorithm.
		 * Ex. www.example.carrara-massa.it (carrara-massa.it is a TLD)
		 *
		 * @since 1.2.9
		 *
		 * @param string $pre Empty string. Return your domain_tld instead.
		 * @param string $url The URL to extract domain_tld from.
		 */
		$pre = apply_filters( 'wpglobus_pre_domain_tld', $pre, $url );
		if ( $pre ) {
			return $pre;
		}

		// URL passed with no scheme. parse_url will think it's a path only. Let's add a scheme.
		if ( ! preg_match( '!^(?:https?:)?//!', $url ) ) {
			$url = '//' . $url;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! $host ) {
			// parse_url failed. We cannot do much. Let's return the original url.
			return $url;
		}

		/**
		 * Extract domain-tld from the host.
		 * Note: this does not cover all possible public suffixes.
		 * Using the proper algorithm based PublicList might be resource-consuming.
		 * We'll provide a filter for special cases instead.
		 *
		 * @link https://publicsuffix.org/list/
		 */
		$re         = '/([a-z0-9][a-z0-9\-]+\.[a-z.]{2,6})$/';
		$domain_tld = $host;
		if ( preg_match( $re, $host, $matches ) ) {
			$domain_tld = $matches[1];
		}

		return $domain_tld;
	}

	/**
	 * Convert array of local texts to multilingual string (with WPGlobus delimiters)
	 *
	 * @param string[] $translations
	 *
	 * @return string
	 */
	public static function build_multilingual_string( $translations ) {
		$sz     = '';
		$single = ( 1 === count( $translations ) );
		foreach ( $translations as $language => $text ) {
			if ( $single && WPGlobus::Config()->default_language === $language ) {
				$sz = $text;
			} else {
				$sz .= WPGlobus::add_locale_marks( $text, $language );
			}
		}

		return $sz;
	}

	/**
	 * Returns the current URL.
	 * There is no method of getting the current URL in WordPress.
	 * Various snippets published on the Web use a combination of home_url and add_query_arg.
	 * However, none of them work when WordPress is installed in a subfolder.
	 * The method below looks valid. There is a theoretical chance of HTTP_HOST tampered, etc.
	 * However, the same line of code is used by the WordPress core,
	 * for example in @see wp_admin_canonical_url, so we are going to use it, too
	 * *
	 * Note that #hash is always lost because it's a client-side parameter.
	 * We might add it using a JavaScript call.
	 *
	 * @since 1.1.1
	 */
	public static function current_url() {
		return set_url_scheme( 'http://' . WPGlobus_WP::http_host() . WPGlobus_WP::request_uri( '/' ) );
	}

	/**
	 * Build hreflang metas.
	 *
	 * @since 1.1.1
	 * @since 2.3.4 Revised code.
	 *
	 * @param WPGlobus_Config $config Alternative configuration (i.e. Unit Test mock object)
	 *
	 * @return string[] Array of rel-alternate link tags
	 */
	public static function hreflangs( WPGlobus_Config $config = null ) {

		/**
		 * Use the global configuration is alternative not passed
		 */
		if ( null === $config ) {
			// @codeCoverageIgnoreStart
			$config = WPGlobus::Config();
		}
		// @codeCoverageIgnoreEnd

		$hreflangs = array();

		if ( is_404() ) {
			return $hreflangs;
		}

		foreach ( $config->enabled_languages as $language ) {

			switch ( $config->seo_hreflang_type ) {
				case 'zz':
					$_hreflang_type = $language;
					break;
				case 'zz-zz':
					$_hreflang_type = str_replace( '_', '-', strtolower( $config->locale[ $language ] ) );
					break;
				default:
					// 'zz-ZZ'
					$_hreflang_type = str_replace( '_', '-', $config->locale[ $language ] );
					break;
			}

			if ( $language === $config->default_language ) {
				if ( ! empty( $config->seo_hreflang_default_language_type ) && $config->seo_hreflang_default_language_type ) {
					$_hreflang_type = $config->seo_hreflang_default_language_type;

				}
			}

			$hreflangs[ $language ] = sprintf( '<link rel="alternate" hreflang="%s" href="%s"/>',
				$_hreflang_type,
				esc_url( self::localize_current_url( $language, $config ) )
			);

		}

		return $hreflangs;
	}

	/**
	 * Localize the current URL.
	 *
	 * @since 1.2.3
	 *
	 * @param string          $language Language to localize the URL to.
	 * @param WPGlobus_Config $config   Alternative configuration (i.e. Unit Test mock object)
	 *
	 * @return string
	 */
	public static function localize_current_url( $language = '', WPGlobus_Config $config = null ) {
		/**
		 * Filter the current URL before it is localized (a short-circuit filter).
		 * If a non-empty string is returned by the filter, then the `localize_url()`
		 * won't be called.
		 *
		 * @since 1.2.3
		 *
		 * @param string $url      Empty string is passed.
		 * @param string $language The language that the URL is going to be localized to.
		 *
		 * @return string
		 */
		$url = apply_filters( 'wpglobus_pre_localize_current_url', '', $language );

		if ( ! $url ) {
			/**
			 * Use the global configuration if alternative not passed
			 */
			if ( null === $config ) {
				// @codeCoverageIgnoreStart
				$config = WPGlobus::Config();
			}
			// @codeCoverageIgnoreEnd
			$url = self::localize_url( self::current_url(), $language, $config );
		}

		/**
		 * Filter the current URL after it was localized.
		 *
		 * @since 1.8.1
		 *
		 * @param string $url      The localized URL.
		 * @param string $language The language it was localized to.
		 *
		 * @return string
		 */
		return apply_filters( 'wpglobus_after_localize_current_url', $url, $language );
	}

	/**
	 * Localize wpglobus.com for use in outgoing links
	 *
	 * @since 1.2.6
	 *
	 * @param WPGlobus_Config $config
	 *
	 * @return string
	 */
	public static function url_wpglobus_site( WPGlobus_Config $config = null ) {
		if ( null === $config ) {
			// @codeCoverageIgnoreStart
			$config = WPGlobus::Config();
		}
		// @codeCoverageIgnoreEnd

		$url = WPGlobus::URL_WPGLOBUS_SITE;
		if ( 'ru' === $config->language ) {
			$url .= 'ru/';
		}

		return $url;
	}

	/**
	 * Return true if language is in array of enabled languages, otherwise false
	 *
	 * @param string $language
	 *
	 * @return bool
	 * @codeCoverageIgnore
	 */
	public static function is_enabled( $language ) {
		return in_array( $language, WPGlobus::Config()->enabled_languages, true );
	}

	/**
	 * Secure access to scalars in $_GET.
	 *
	 * @param string $key Index ($_GET[ $key ]).
	 *
	 * @return string The value or empty string if not exists or not scalar.
	 * @deprecated 2.12.1
	 */
	public static function safe_get( $key ) {
		return WPGlobus_WP::get_http_get_parameter( $key );
	}

	/**
	 * Need to check if they are used by any add-on.
	 * Marking them as deprecated so they will pop-up on code inspection.
	 *
	 * @todo The methods below are not used by the WPGlobus plugin.
	 */

	/**
	 * Return true if language is in array of opened languages, otherwise false
	 *
	 * @param string $language
	 *
	 * @return bool
	 * @deprecated
	 * @codeCoverageIgnore
	 *
	 * @noinspection PhpUnused
	 */
	public static function is_open( $language ) {
		return in_array( $language, WPGlobus::Config()->open_languages, true );
	}

	/**
	 * Method starts_with
	 *
	 * @param string $s
	 * @param string $n
	 *
	 * @return bool
	 * @deprecated
	 * @codeCoverageIgnore
	 */
	public static function starts_with( $s, $n ) {
		if ( strlen( $n ) > strlen( $s ) ) {
			return false;
		}

		/* @noinspection SubStrUsedAsStrPosInspection */
		return ( substr( $s, 0, strlen( $n ) ) === $n );
	}

	/**
	 * Returns sanitized $_SERVER['REQUEST_URI'].
	 *
	 * @param string $default Default to return when unset.
	 *
	 * @return string
	 * @deprecated 2.12.1
	 */
	public static function request_uri( $default = '' ) {
		return WPGlobus_WP::request_uri( $default );
	}

	/**
	 * Returns sanitized $_SERVER['HTTP_HOST'].
	 *
	 * @param string $default Default to return when unset.
	 *
	 * @return string
	 * @deprecated 2.12.1
	 */
	public static function http_host( $default = 'localhost' ) {
		return WPGlobus_WP::http_host( $default );
	}
}
