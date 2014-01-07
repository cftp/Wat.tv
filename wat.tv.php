<?php
/*
Plugin Name: CFTP Wat.tv
Description: Wat.tv OEmbed support
Author: Tom J Nowell, Code For The People
Version: 1.0
Author URI: http://codeforthepeople.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

class CFTP_Wattv {

	protected static $_instance = null;

	public static function instance() {
		if ( !isset( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		wp_embed_register_handler( 'wattv', '#http://www.wat.tv/video/(.+)#', array( $this, 'wattv_embed_handler' ) );
	}

	function wattv_embed_handler( $matches, $attr, $url, $rawattr ) {

		$transient = get_transient( 'wattv_embed_'.$url );
		$embed = $transient;
		if ( $transient === false ) {
			/*
			request remote page
			create domdocument from remote page
			If successful
					Find twitter:player meta tag
					Use tag value as iframe option
					Return iframe with appropriate parameters
			*/
			$remote = wp_remote_get( 'http://www.wat.tv/video/'.$matches[1] );
			libxml_use_internal_errors( true );
			$dom = new DOMDocument();
			$dom->loadHTML( $remote['body'] );
			libxml_clear_errors();
			$metaChildren = $dom->getElementsByTagName( 'meta' );
			$url = '';
			for ( $i = 0; $i < $metaChildren->length; $i++ ) {
				$el = $metaChildren->item( $i );
				$name = $el->getAttribute( 'name' );
				if ( $name == 'twitter:player' ) {
					$url = $el->getAttribute( 'content' );
				}
			}

			$embed = sprintf(
				'<figure class="o-container wattv">
					<iframe src="%1$s" frameborder="0" scrolling="no" width="650" height="450" marginwidth="0" marginheight="0" allowfullscreen></iframe>
				</figure>',
				esc_attr( $url )
			);
			// we have a transient return/assign the results
			set_transient( 'wattv_embed_'.$url, $embed, DAY_IN_SECONDS );
		}

		return apply_filters( 'embed_wattv', $embed, $matches, $attr, $url, $rawattr );
	}
}

add_action( 'init', array( 'CFTP_Wattv', 'instance' ) );