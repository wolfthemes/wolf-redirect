<?php
/**
 * Plugin Name: Redirect
 * Plugin URI: https://github.com/wolfthemes/wolf-redirect
 * Description: Super simple page redirection plugin
 * Version: 1.0.4
 * Author: WolfThemes
 * Author URI: https://wolfthemes.com
 * Requires at least: 5.0
 * Tested up to: 5.5
 *
 * Text Domain: wolf-redirect
 * Domain Path: /languages/
 *
 * @package WolfRedirect
 * @category Core
 * @author WolfThemes
 *
 * Verified customers who have purchased a premium theme at https://wlfthm.es/tf/
 * will have access to support for this plugin in the forums
 * https://wlfthm.es/help/
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Wolf_Redirect' ) ) {
	/**
	 * Main Wolf_Redirect Class
	 *
	 * Contains the main functions for Wolf_Redirect
	 *
	 * @class Wolf_Redirect
	 * @version 1.0.4
	 * @since 1.0.0
	 */
	class Wolf_Redirect {


		/**
		 * Hook into the appropriate actions when the class is constructed.
		 */
		public function __construct() {

			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
			add_action( 'save_post', array( $this, 'save' ) );
			add_action( 'template_redirect', array( $this, 'do_redirect' ) );
			add_action( 'admin_init', array( $this, 'plugin_update' ) );
			add_action( 'wp_head', array( $this, 'redirect_js' ), 100 );
		}

		/**
		 * Redirect the page to the URL set in the post meta
		 */
		public function do_redirect() {

			if ( is_single() || is_page() ) {
				$post_id = get_the_ID();
				$redirect_url = get_post_meta( $post_id, '_wolf_redirect_url', true );
				$redirect_permanent = get_post_meta( $post_id, '_wolf_redirect_permanent', true );
				$redirect_js = get_post_meta( $post_id, '_wolf_redirect_js', true );
				$visitor_only = get_post_meta( $post_id, '_wolf_redirect_visitor_only', true );
				$redirect_type = ( $redirect_permanent ) ? 301 : 302;
				$is_admin = current_user_can( 'manage_options' );
				$condition = ( $visitor_only && $is_admin ) ? false : true;

				if ( ! $redirect_js && $redirect_url && $condition ) {
					wp_redirect( $redirect_url, $redirect_type );
					exit;
				}
			}
		}

		/**
		 * Redirect the page to the URL set in the post meta
		 */
		public function redirect_js() {
			if ( is_single() || is_page() ) {

				$post_id = get_the_ID();

				$redirect_url = get_post_meta( $post_id, '_wolf_redirect_url', true );
				$redirect_permanent = get_post_meta( $post_id, '_wolf_redirect_permanent', true );
				$redirect_js = get_post_meta( $post_id, '_wolf_redirect_js', true );
				$visitor_only = get_post_meta( $post_id, '_wolf_redirect_visitor_only', true );
				$redirect_type = ( $redirect_permanent ) ? 301 : 302;
				$is_admin = current_user_can( 'manage_options' );
				$condition = ( $visitor_only && $is_admin ) ? false : true;

				if ( $redirect_js && $redirect_url && $condition ) {
					?>
					<script>
						setTimeout(function(){
							window.location.href='<?php echo esc_js( $redirect_url ); ?>';
						},200);
					</script>
					<?php
				}
			}
		}

		/**
		 * Adds the meta box container
		 *
		 * @param string $post_type
		 */
		public function add_meta_box( $post_type ) {
			$post_types = array( 'post', 'page', 'work', 'release', 'show', 'gallery', 'video', 'plugin', 'product', 'review' ); // limit meta box to certain post types
			if ( in_array( $post_type, $post_types ) ) {
				add_meta_box(
					'redirect_url'
					,esc_html__( 'Redirect', 'wolf' )
					,array( $this, 'render_meta_box_content' )
					,$post_type
					,'side'
					,'high'
				);
			}
		}

		/**
		 * Save the meta when the post is saved.
		 *
		 * @param int $post_id The ID of the post being saved.
		 */
		public function save( $post_id ) {

			// Check if our nonce is set.
			if ( ! isset( $_POST['wolf_redirect_inner_custom_box_nonce'] ) )
				return $post_id;

			$nonce = $_POST['wolf_redirect_inner_custom_box_nonce'];

			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $nonce, 'wolf_redirect_inner_custom_box' ) )
				return $post_id;

			// If this is an autosave, our form has not been submitted,
			// so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_id;

			// Check the user's permissions.
			if ( 'page' == $_POST['post_type'] ) {

				if ( ! current_user_can( 'edit_page', $post_id ) )
					return $post_id;

			} else {

				if ( ! current_user_can( 'edit_post', $post_id ) )
					return $post_id;
			}

			/* OK, its safe for us to save the data now. */

			// Sanitize the user input.
			$url = esc_url( $_POST['wolf_redirect_field'] );

			$visitor_only = ( isset( $_POST['wolf_redirect_visitor_only'] ) );
			$permanent = ( isset( $_POST['wolf_redirect_permanent'] ) );
			$js = ( isset( $_POST['wolf_redirect_js'] ) );

			if ( $permanent ) {
				update_post_meta( $post_id, '_wolf_redirect_permanent', true );
			} else {
				delete_post_meta( $post_id, '_wolf_redirect_permanent' );
			}

			if ( $visitor_only ) {
				update_post_meta( $post_id, '_wolf_redirect_visitor_only', true );
			} else {
				delete_post_meta( $post_id, '_wolf_redirect_visitor_only' );
			}

			if ( $js ) {
				update_post_meta( $post_id, '_wolf_redirect_js', true );
			} else {
				delete_post_meta( $post_id, '_wolf_redirect_js' );
			}

			// Update the meta field.
			update_post_meta( $post_id, '_wolf_redirect_url', $url );
		}


		/**
		 * Render Meta Box content.
		 *
		 * @param int $post The post object.
		 */
		public function render_meta_box_content( $post ) {

			// Add an nonce field so we can check for it later.
			wp_nonce_field( 'wolf_redirect_inner_custom_box', 'wolf_redirect_inner_custom_box_nonce' );

			// Use get_post_meta to retrieve an existing value from the database.
			$value = get_post_meta( $post->ID, '_wolf_redirect_url', true );
			$permanent = get_post_meta( $post->ID, '_wolf_redirect_permanent', true );
			$visitor_only = get_post_meta( $post->ID, '_wolf_redirect_visitor_only', true );
			$js = get_post_meta( $post->ID, '_wolf_redirect_js', true );

			// Display the form, using the current value.
			echo '<label for="wolf_redirect_field">';
			echo '</label> ';
			echo '<input placeholder="http://" type="text" id="wolf_redirect_field" name="wolf_redirect_field"';
			echo ' value="' . esc_url( $value ) . '" size="25" />';

			echo '<label for="wolf_redirect_visitor_only"><br><br>';
			echo '<input';
			echo ( $visitor_only ) ? ' checked="checked"' : '';
			echo ' value="1" type="checkbox" id="wolf_redirect_visitor_only" name="wolf_redirect_visitor_only">';
			esc_html_e( 'Redirect non-admin users only', 'wolf-redirect' );
			echo '</label> ';

			echo '<label for="wolf_redirect_permanent"><br><br>';
			echo '<input';
			echo ( $permanent ) ? ' checked="checked"' : '';
			echo ' value="1" type="checkbox" id="wolf_redirect_permanent" name="wolf_redirect_permanent">';
			esc_html_e( 'Redirect permanently', 'wolf-redirect' );
			echo '</label> ';

			echo '<label for="wolf_redirect_js"><br><br>';
			echo '<input';
			echo ( $js ) ? ' checked="checked"' : '';
			echo ' value="1" type="checkbox" id="wolf_redirect_js" name="wolf_redirect_js">';
			esc_html_e( 'Redirect with JS', 'wolf-redirect' );
			echo '</label> ';
		}

		/**
		 * Plugin update
		 */
		public function plugin_update() {

			if ( ! class_exists( 'WP_GitHub_Updater' ) ) {
				include_once 'class/updater.php';
			}

			$repo = 'wolfthemes/wolf-redirect';

			$config = array(
				'slug' => plugin_basename( __FILE__ ),
				'proper_folder_name' => 'wolf-redirect',
				'api_url' => 'https://api.github.com/repos/' . $repo . '',
				'raw_url' => 'https://raw.github.com/' . $repo . '/master/',
				'github_url' => 'https://github.com/' . $repo . '',
				'zip_url' => 'https://github.com/' . $repo . '/archive/master.zip',
				'sslverify' => true,
				'requires' => '5.0',
				'tested' => '5.5',
				'readme' => 'README.md',
				'access_token' => '',
			);

			new WP_GitHub_Updater( $config );
		}

	} // end class

	$wolf_redirect = new Wolf_Redirect();

} // class_exists check
