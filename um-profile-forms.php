<?php
/**
 * Plugin Name: Ultimate Member - Split profile form
 * Description: Patch the Profile page functionality. This plugin adds subtabs into the tab "About". Each subtab contains one profile form.
 * Version: 1.0.1
 * Author: Ultimate Member
 * Author URI: http://ultimatemember.com/
 * Text Domain: um-profile-forms
 * UM version: 2.1.5
 */
if ( function_exists( 'UM' ) && !class_exists( 'UM_Profile_Forms' ) ) {

	/**
	 * Class User
	 * @package um\core
	 */
	class UM_Profile_Forms {

		/**
		 * The profile form ID
		 * @var integer
		 */
		private $form_id = null;

		/**
		 * The profile menu subitems in the tab 'main'
		 * @var array
		 */
		private $main_subnav = array( 'default' => '' );

		/**
		 * The profile menu subitem slug
		 * @var string
		 */
		private $subtab = '';

		/**
		 * Get the class instance
		 * @return UM_Profile_Forms
		 */
		static function instance() {
			if ( empty( UM()->classes['UM_Profile_Forms'] ) ) {
				UM()->classes['UM_Profile_Forms'] = new UM_Profile_Forms();
			}
			return UM()->classes['UM_Profile_Forms'];
		}

		/**
		 * UM_Profile_Forms constructor.
		 */
		public function __construct() {
			add_filter( 'template_include', array( $this, 'customize_profile' ), 20 );
			add_filter( 'um_profile_edit_menu_items', array( $this, 'profile_edit_menu_items' ), 20 );
			add_filter( 'um_myprofile_edit_menu_items', array( $this, 'profile_edit_menu_items' ), 20 );
		}

		/**
		 * Add subnav into the profile tab 'main'
		 *
		 * @since 1.0.1
		 *
		 * @param   string  $template
		 * @return  string
		 */
		public function customize_profile( $template ) {

			if ( um_is_core_page( 'user' ) ) {

				$priority_user_role = UM()->roles()->get_priority_user_role( um_profile_id() );
				$profile_page_id = UM()->config()->permalinks['user'];
				$profile_page = get_post( $profile_page_id );
				$this->subtab = filter_input( INPUT_GET, 'subnav' );

				/* get profile forms */
				$um_profile_forms = get_posts( array(
						'meta_key' => '_um_mode',
						'meta_value' => 'profile',
						'numberposts' => -1,
						'post_type' => 'um_form',
						'post_status' => 'publish'
						) );

				foreach ( $um_profile_forms as $um_form ) {
					if ( $um_form->_um_profile_use_custom_settings && $um_form->_um_profile_role && !in_array( $priority_user_role, (array) $um_form->_um_profile_role ) ) {
						continue;
					}
					if ( isset( $profile_page->post_content ) && strstr( $profile_page->post_content, '[ultimatemember form_id="' . $um_form->ID . '"]' ) !== false ) {
						$this->main_subnav['default'] = $um_form->post_title;
					} else {
						$this->main_subnav["profileform-{$um_form->ID}"] = $um_form->post_title;
					}
				}

				/* add subitems into the profile tab 'main' */
				if ( count( $this->main_subnav ) > 1 ) {
					add_filter( 'um_profile_tabs', array( $this, 'profile_tabs' ), 20 );
				}

				/* add the tab's content */
				if ( $this->subtab && strpos( $this->subtab, 'profileform-' ) === 0 ) {
					$this->form_id = str_replace( 'profileform-', '', $this->subtab );
					remove_action( 'um_profile_content_main', 'um_profile_content_main' );
					add_action( "um_profile_content_main_profileform-{$this->form_id}", array( $this, 'profile_content' ) );
				}
			}

			return $template;
		}

		/**
		 * Patch the 'Edit Profile' link if multiple profile forms are used
		 *
		 * @since 1.0.1
		 *
		 * @param   array  $items
		 * @return  array
		 */
		public function profile_edit_menu_items( $items ) {

			if ( isset( $items['editprofile'] ) && $this->subtab && strpos( $this->subtab, 'profileform-' ) === 0 ) {

				$url = add_query_arg( array(
						'profiletab' => 'main',
						'subnav' => $this->subtab,
						'um_action' => 'edit',
						), um_user_profile_url() );

				$items['editprofile'] = '<a href="' . esc_url( $url ) . '" class="real_url">' . __( 'Edit Profile', 'ultimate-member' ) . '</a>';
			}

			return $items;
		}

		/**
		 * Print the profile content
		 *
		 * @since 1.0.1
		 *
		 * @param array $args
		 */
		public function profile_content( $args ) {
			$post_data = UM()->query()->post_data( $this->form_id );
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
		}

		/**
		 * Add subitems into the profile tab 'main'
		 *
		 * @since 1.0.1
		 *
		 * @param  array  $tabs
		 * @return array
		 */
		public function profile_tabs( $tabs ) {
			if ( isset( $tabs['main'] ) ) {
				$tabs['main']['subnav'] = $this->main_subnav;
				$tabs['main']['subnav_default'] = 'default';
			}
			return $tabs;
		}

	}

	UM_Profile_Forms::instance();
}