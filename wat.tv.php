<?php
/*
Plugin Name: CFTP Wat.tv
Description: Wat.tv OEmbed support
Author: Tom J Nowell, Code For The People
Version: 1.1
Author URI: http://codeforthepeople.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

CFTP_Wattv::instance();
WatTvOembedProvider::init();

class CFTP_Wattv {

	protected static $_instance = null;

	public static function instance() {
		if ( !isset( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Adds the oembed providers
	 */
	public function __construct() {
		$this->add_providers();
	}

	public function add_providers() {
		wp_oembed_add_provider( '#https?://www.wat.tv/video/(.+)#i', site_url('/?oembed=true&oembedtype=wattv&format={format}'), true );
	}

	public static function embed_code( $url ) {
		/*
		Steps to retrieve the superior twitter player embed:
		request remote page
		create domdocument from remote page
		If successful
				Find twitter:player meta tag
				Use tag value as iframe option
				Return iframe with appropriate parameters
		*/
		$remote = wp_remote_get( $url );
		$twitter_player_url = '';
		if ( !is_wp_error( $remote ) ) {
			// disable the printing of xml errors so we don't break the frontend
			libxml_use_internal_errors( true );
			$dom = new DOMDocument();
			$dom->loadHTML( $remote['body'] );
			libxml_clear_errors();
			$metaChildren = $dom->getElementsByTagName( 'meta' );

			// for each meta tag found
			for ( $i = 0; $i < $metaChildren->length; $i++ ) {
				$el = $metaChildren->item( $i );
				$name = $el->getAttribute( 'name' );
				if ( $name == 'twitter:player' ) {
					// we've found the twitter meta tag for the video player, stop looping
					$twitter_player_url = $el->getAttribute( 'content' );
					break;
				}
			}
		}
		$embed = $url;
		if ( !empty( $twitter_player_url ) ) {
			$embed = sprintf(
				'<figure class="o-container wattv">' .
				'<iframe src="%1$s" frameborder="0" scrolling="no" width="640" height="360" marginwidth="0" marginheight="0" allowfullscreen></iframe>'.
				'</figure>',
				esc_attr( $twitter_player_url )
			);
		}
		return $embed;
	}

	public function wattv_embed_handler( $matches, $attr, $url, $rawattr ) {

		$transient = get_transient( 'wattv_embed_'.$url );
		$embed = $transient;
		if ( $transient === false ) {
			$embed = CFTP_Wattv::embed_code( $url );
			// we have a transient return/assign the results
			set_transient( 'wattv_embed_'.$url, $embed, DAY_IN_SECONDS );
		}

		return apply_filters( 'embed_wattv', $embed, $matches, $attr, $url, $rawattr );
	}
}


/**
 * oEmbed Provider, modified code from Matthias and Craig
 *
 * @author Matthias Pfefferle
 * @author Craig Andrews
 * @author Tom J Nowell
 */
class WatTvOembedProvider {

	/**
	 * Initialises the provider by adding the necessary hooks
	 */
	public static function init() {
		add_action( 'parse_query', array( 'WatTvOembedProvider', 'parse_query' ) );
		add_filter( 'query_vars', array( 'WatTvOembedProvider', 'query_vars' ) );
		add_filter( 'cftp_oembed_provider_data_wattv_screen', array( 'WatTvOembedProvider', 'generate_default_content' ), 90, 3 );
		add_action( 'cftp_oembed_provider_render_wattv_screen_json', array( 'WatTvOembedProvider', 'render_json' ), 99, 2 );
		add_action( 'cftp_oembed_provider_render_wattv_screen_xml', array( 'WatTvOembedProvider', 'render_xml' ), 99 );
	}

	/**
	 * adds query vars
	 */
	public static function query_vars( $query_vars ) {
		foreach ( array( 'oembed', 'oembedtype', 'format', 'url', 'callback' ) as $qvar ) {
			if ( !array_key_exists( $qvar, $query_vars ) ) {
				$query_vars[] = $qvar;
			}
		}

		return $query_vars;
	}

	/**
	 * handles request
	 */
	public static function parse_query( $wp ) {
		if (!array_key_exists('oembed', $wp->query_vars) ||
			!array_key_exists('url', $wp->query_vars) ||
			!array_key_exists('oembedtype', $wp->query_vars)
		) {
			return;
		}

		// we're only handling wattv here
		if ( $wp->query_vars['oembedtype'] != 'wattv' ) {
			return;
		}

		$embed_url = $wp->query_vars['url'];

		// @TODO: perform a check on the regex if the URL matches to validate, if not, 404
		/*if(!$post) {
			header('Status: 404');
			wp_die("Not found");
		}*/

		// add support for alternate output formats
		$oembed_provider_formats = apply_filters( "oembed_provider_formats", array( 'json', 'xml' ) );

		// check output format
		$format = 'json';
		if ( array_key_exists( 'format', $wp->query_vars ) && in_array( strtolower( $wp->query_vars['format'] ), $oembed_provider_formats ) ) {
			$format = $wp->query_vars['format'];
		}

		// content filter
		$oembed_provider_data = apply_filters( 'cftp_oembed_provider_data_wattv_screen', array(), $embed_url );

		do_action( 'cftp_oembed_provider_render_wattv_screen', $format, $oembed_provider_data, $wp->query_vars);
		do_action( "cftp_oembed_provider_render_wattv_screen_{$format}", $oembed_provider_data, $wp->query_vars);
	}

	/**
	 * adds default content
	 *
	 * @param array $oembed_provider_data
	 * @param $url
	 * @internal param string $post_type
	 * @internal param Object $post
	 *
	 * @return array OEmbed data to be formatted as a response
	 */
	public static function generate_default_content( $oembed_provider_data, $url ) {
		$count = 4;
		$image_url = '';
		$title = '';
		$video_height = 270;
		$video_width = 480;
		$remote = wp_remote_get( $url );
		if ( !is_wp_error( $remote ) ) {
			// disable the printing of xml errors so we don't break the frontend
			libxml_use_internal_errors( true );
			$dom = new DOMDocument();
			$dom->loadHTML( $remote['body'] );
			libxml_clear_errors();
			$metaChildren = $dom->getElementsByTagName( 'meta' );
			// for each meta tag found
			for ( $i = 0; $i < $metaChildren->length; $i++ ) {
				$el = $metaChildren->item( $i );
				$name = $el->getAttribute( 'name' );
				if ( $name == 'og:image' ) {
					// we've found the twitter meta tag for the video player, stop looping
					$image_url = $el->getAttribute( 'content' );
					$count--;
				}
				if ( $name == 'og:title' ) {
					// we've found the twitter meta tag for the video player, stop looping
					$title = $el->getAttribute( 'content' );
					$count--;
				}
				if ( $name == 'twitter:player:height' ) {
					// we've found the twitter meta tag for the video player, stop looping
					$video_height = $el->getAttribute( 'content' );
					$count--;
				}
				if ( $name == 'twitter:player:width' ) {
					// we've found the twitter meta tag for the video player, stop looping
					$video_width = $el->getAttribute( 'content' );
					$count--;
				}
				if ( $count == 0 ) {
					break;
				}
			}
		}
		$oembed_provider_data['version'] = '1.0';
		$oembed_provider_data['provider_name'] = 'Wat.tv';
		$oembed_provider_data['provider_url'] = home_url();
		$oembed_provider_data['author_name'] = 'Wat.tv';
		$oembed_provider_data['author_url'] = 'http://wat.tv';
		$oembed_provider_data['title'] = $title;

		if ( !empty( $image_url ) ) {
			$oembed_provider_data['thumbnail_url'] = $image_url;
			$oembed_provider_data['thumbnail_width'] = $video_width;
			$oembed_provider_data['thumbnail_height'] = $video_height;
		}
		$oembed_provider_data['type'] = 'video';
		$oembed_provider_data['html'] = CFTP_Wattv::embed_code( $url );

		return $oembed_provider_data;
	}

	/**
	 * Render json output
	 *
	 * @param array $oembed_provider_data
	 * @param array $wp_query Query variables ( not a WP_Query object as you would think )
	 */
	public static function render_json($oembed_provider_data, $wp_query) {
		header( 'Content-Type: application/json; charset=' . get_bloginfo( 'charset' ), true );

		// render json output
		$json = json_encode( $oembed_provider_data );

		// add callback if available
		if (array_key_exists( 'callback', $wp_query ) ) {
			$json = $wp_query['callback'] . "($json);";
		}

		echo $json;
		exit;
	}

	/**
	 * Render xml output
	 *
	 * @param array $oembed_provider_data
	 */
	public static function render_xml( $oembed_provider_data ) {
		header('Content-Type: text/xml; charset=' . get_bloginfo('charset'), true);

		// render xml-output
		echo '<?xml version="1.0" encoding="' . get_bloginfo('charset') . '" ?>';
		echo '<oembed>';
		foreach ( array_keys($oembed_provider_data) as $element ) {
			echo '<' . $element . '>' . esc_html($oembed_provider_data[$element]) . '</' . $element . '>';
		}
		echo '</oembed>';
		exit;
	}

}
