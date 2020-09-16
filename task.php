<?php
/*
Plugin Name: task
Description: test task
Version: 1.0
Author: Alekhin Maxim
Author URI: 
*/
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'PLUGIN_NAME_VERSION', '1.0' );
define( 'ADT_VERSION', '1.0' );

function taskplugin_install() {

}

function taskplugin_uninstall() {

}

register_activation_hook( __FILE__, 'taskplugin_install' );
register_deactivation_hook( __FILE__, 'taskplugin_uninstall' );


function loadMyBlock() {
	wp_enqueue_script(
		'my-new-block',
		plugin_dir_url( __FILE__ ) . '/js/script.js',
		array( 'wp-blocks', 'wp-editor' ),
		true
	);
}

add_action( 'enqueue_block_editor_assets', 'loadMyBlock' );


add_action( 'wp', 'pvc_count_post_view' );
function pvc_count_post_view() {

	// Check if we're in a single post or page
	if ( is_single() ) {

		// Get the global post object
		global $post;

		// Check if a post ID exists
		if ( isset( $post->ID ) ) {

			// Get the existing post view count (default to 0)
			$views = intval( get_post_meta( $post->ID, '_post_views', true ) );

			// Increment view count
			$views_updated = $views + 1;

			// Update stored view count
			update_post_meta( $post->ID, '_post_views', $views_updated, $views );
		}

	}
}


add_shortcode( 'post_views', 'pvc_post_views_shortcode' );
function pvc_post_views_shortcode( $atts = array() ) {

	// Parse shortcode parameters
	$atts = shortcode_atts( array(
		'post' => 0,
	), $atts, 'post_views' );

	// Set default output to empty string
	$output = '';

	// Get post ID to use in shortcode (default to 0)
	$post_id = 0;

	// First check shortcode attributes
	if ( $atts['post'] ) {
		$post_id = $atts['post'];
	} else {

		// If no post is specified in the shortcode, then use the current post ID
		global $post;
		if ( isset( $post->ID ) ) {
			$post_id = $post->ID;
		}

	}

	// Get shortcode output if post ID is present
	if ( $post_id ) {
		// Get post view count (default to 0)
		//$views = intval( get_post_meta( $post_id, '_post_views', true ) );
		// Generate output HTML for shortcode
		$output = '<span class="view-count" >Views:</span>';
	}

	// Return shortcode output
	return apply_filters( 'pvc_post_views_shortcode_output', $output );
}


define( 'DYNAMIC_OUTPUT_BUFFER_TAG', '<span class="view-count" >Views:</span>' ); // Change this to a secret placeholder tag.

if ( '' !== DYNAMIC_OUTPUT_BUFFER_TAG ) {
	function dynamic_output_buffer_test( $cachedata = 0 ) {
		if ( defined( 'DYNAMIC_OB_TEXT' ) ) {
			return str_replace( DYNAMIC_OUTPUT_BUFFER_TAG, DYNAMIC_OB_TEXT, $cachedata );
		}

		ob_start();
		// call the sidebar function, do something dynamic
		global $post;
		$re = '/id="post-(.*?)"/m';
		preg_match_all( $re, $cachedata, $matches, PREG_SET_ORDER, 0 );
		$post_id = $matches[0][1];

		$views = intval( get_post_meta( $post_id, '_post_views', true ) );

		echo '<span class="view-count" >' . sprintf( __( 'Views: %d', 'post-view-counter' ), $views ) . '</span>';
		$text = ob_get_contents();
		ob_end_clean();
		$views_updated = $views + 1;

		// Update stored view count
		update_post_meta( $post_id, '_post_views', $views_updated, $views );
		if ( 0 === $cachedata ) { // called directly from the theme so store the output.
			define( 'DYNAMIC_OB_TEXT', $text );
		} else { // called via the wpsc_cachedata filter. We only get here in cached pages in wp-cache-phase1.php.
			return str_replace( DYNAMIC_OUTPUT_BUFFER_TAG, $text, $cachedata );
		}

	}

	add_cacheaction( 'wpsc_cachedata', 'dynamic_output_buffer_test' );

	function dynamic_output_buffer_init() {
		apply_filters( 'the_content', 'dynamic_output_buffer_test' );
	}

	add_cacheaction( 'add_cacheaction', 'dynamic_output_buffer_init' );

	function dynamic_output_buffer_test_safety( $safety ) {
		if ( defined( 'DYNAMIC_OB_TEXT' ) ) {// this is set when you call dynamic_output_buffer_test() from the theme.
			return 1; // ready to replace tag with dynamic content.
		} else {
			return 0; // tag cannot be replaced.
		}
	}

	add_cacheaction( 'wpsc_cachedata_safety', 'dynamic_output_buffer_test_safety' );
}