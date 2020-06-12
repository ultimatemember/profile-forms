<?php
/**
 * Plugin Name: Ultimate Member - Split profile form
 * Description: Patch the Profile page functionality. This plugin adds subtabs into the tab "About". Each subtab contains one profile form.
 * Version: 1.0.0
 * Author: Ultimate Member
 * Author URI: http://ultimatemember.com/
 * Text Domain: um-profile-forms
 * UM version: 2.1.5
 */
if ( function_exists( 'UM' ) ) {

	/**
	 * Add subnav into the profile tab 'main'
	 * @return  boolean
	 */
	function um_custom_profile_tabs() {

		$profile_page_id = UM()->config()->permalinks['user'];
		$profile_page = get_post( $profile_page_id );

		/* get all profile forms */
		$um_forms = get_posts( array(
			'meta_key'		 => '_um_mode',
			'meta_value'	 => 'profile',
			'numberposts'	 => -1,
			'post_type'		 => 'um_form',
			'post_status'	 => 'publish'
			) );


		/* add subitems into the profile tab 'main' */

		$subnav = array( 'default' => '' );

		foreach ( $um_forms as $um_form ) {
			if ( isset( $profile_page->post_content ) && strstr( $profile_page->post_content, '[ultimatemember form_id="' . $um_form->ID . '"]' ) !== false ) {
				$subnav['default'] = $um_form->post_title;
			} else {
				$subnav["profileform-{$um_form->ID}"] = $um_form->post_title;
			}
		}

		add_filter( 'um_profile_tabs', function ( $tabs ) use( $subnav ) {
			if ( isset( $tabs['main'] ) ) {
				$tabs['main']['subnav'] = $subnav;
				$tabs['main']['subnav_default'] = 'default';
			}
			return $tabs;
		}, 20 );


		/* add content in subnav */

		$subtab = filter_input( INPUT_GET, 'subnav' );

		if ( $subtab && strpos( $subtab, 'profileform-' ) === 0 ) {
			$form_id = str_replace( 'profileform-', '', $subtab );

			remove_action( 'um_profile_content_main', 'um_profile_content_main' );

			add_action( "um_profile_content_main_profileform-{$form_id}", function ( $args ) use ( $form_id ) {
				$post_data = UM()->query()->post_data( $form_id );
				$args = array_merge( $args, $post_data );
				$args = apply_filters( 'um_pre_args_setup', $args );
				$args = apply_filters( 'um_shortcode_args_filter', $args );
				$mode = isset( $args['mode'] ) ? $args['mode'] : 'profile';
				?>

				<div class="um">
					<div class="um-form">
						<form method="post">

							<?php
							do_action( 'um_before_form', $args );
							do_action( "um_before_{$mode}_fields", $args );
							do_action( "um_main_{$mode}_fields", $args );
							do_action( 'um_after_form_fields', $args );
							do_action( "um_after_{$mode}_fields", $args );
							do_action( 'um_after_form', $args );
							?>

						</form>
					</div>
				</div>

				<?php
			} );
		}
	}
	add_action( 'init', 'um_custom_profile_tabs', 20 );


	/**
	 * Patch the 'Edit Profile' link if multiple profile forms are used
	 * @param   array  $items
	 * @return  array
	 */
	function um_customprofile_edit_menu_item( $items ) {

		if ( isset( $items['editprofile'] ) ) {

			$subtab = filter_input( INPUT_GET, 'subnav' );

			if ( $subtab && strpos( $subtab, 'profileform-' ) === 0 ) {

				$url = add_query_arg( array(
					'profiletab' => 'main',
					'subnav'		 => $subtab,
					'um_action'	 => 'edit',
					), um_user_profile_url() );

				$items['editprofile'] = '<a href="' . esc_url( $url ) . '" class="real_url">' . __( 'Edit Profile', 'ultimate-member' ) . '</a>';
			}
		}

		return $items;
	}
	add_filter( 'um_profile_edit_menu_items', 'um_customprofile_edit_menu_item', 20 );
	add_filter( 'um_myprofile_edit_menu_items', 'um_customprofile_edit_menu_item', 20 );
}