<?php

/**
 * Plugin Name: Forced Preview
 * Description: Requires authors to view a preview of their post before it is actually published.
 * Author: Micah Ernst
 * Author URI: http://www.micahernst.com
 * Version: 0.1
 */

define( 'FORCED_PREVIEW_VERSION', '0.1' );

class Forced_Preview {

	/**
	 * Post types that will require a forced preview
	 */
	var $post_types = array(
		'post'
	);

	/**
	 * Setup the hooks for our plugin
	 *
	 * @return void
	 */
	public function __construct() {

		add_action( 'save_post', array( $this, 'save_post' ), 33, 2 );
		add_action( 'wp_ajax_forced_preview', array( $this, 'preview' ) );
		add_action( 'wp_ajax_nopriv_forced_preview', array( $this, 'preview' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );

		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) ); 

		// filter the possible post types that will have forced preview
		$this->post_types = apply_filters( 'forced_preview_post_types', $this->post_types );
	}

	/**
	 * Set the post meta value indicating the preview has happened
	 *
	 * @return void
	 */
	public function preview() {

		check_ajax_referer( 'forced-preview' );

		if( isset( $_POST['id'] ) ) {

			update_post_meta( 
				intval( $_POST['id'] ),
				'forced_preview',
				1
			);
		}
	}

	/**
	 * Setup our script that will make sure the preview happens
	 *
	 * @return void
	 */
	public function scripts() {

		global $post;

		// only add the script under certain condtions
		if( is_singular() && 
			is_preview() && 
			!get_post_meta( $post->ID, 'forced_preview', true ) && 
			in_array( $post->post_type, $this->post_types ) ) {

			wp_enqueue_script( 
				'forced-preview',
				plugins_url( 'forced-preview' ) . '/js/forced-preview.js',
				array( 'jquery' ),
				FORCED_PREVIEW_VERSION,
				true
			);

			wp_localize_script(
				'forced-preview',
				'FORCED_PREVIEW_CONFIG',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'forced-preview' ),
					'post_id' => $post->ID
				)
			);
		}
	}

	/**
	 * Post messaging
	 *
	 * @return void
	 */
	public function post_updated_messages( $messages ) {

		global $post;

		if( !get_post_meta( $post->ID, 'forced_preview', true ) ) {

			$messages['post'][6] = sprintf( 
				'<strong>The post was not published</strong>. Please <a href="%s">preview</a> the post before publishing.',
				esc_url( home_url() . '/?p=' . $post->ID . '&preview=true' )
			);
		}

		return $messages;
	}

	/**
	 * Check to see if the post has been previewed, set to draft if it hasn't
	 *
	 * @return void
	 */
	public function save_post( $post_id, $post ) {

		if( !current_user_can( 'edit_post', $post_id ) )
			return;

		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// do some checking to see if the current post type is one that we force previews for
		if( !in_array( $post->post_type, $this->post_types ) )
			return;
		
		// if the post type is one that should have forced preview and they dont have the meta key present
		// set the post status back to draft
		if( !get_post_meta( $post_id, 'forced_preview', true ) && $post->post_status === 'publish' ) {

			// disable our save action hook so we don't loop
			remove_action( 'save_post', array( $this, 'save_post') );

			wp_update_post( array(
				'ID' => $post_id,
				'post_status' => 'draft'
			));

			add_action( 'save_post', array( $this, 'save_post' ) );
		}
	}

}
new Forced_Preview();