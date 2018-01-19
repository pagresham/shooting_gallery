<?php if(!defined('ABSPATH')) { die(); } // Include in all php files, to prevent direct execution
/**
 * Plugin Name: Shooting Gallery
 * Description: A sweet little gallery plugin
 * Author:
 * Author URI:
 * Version: 0.1.1
 * Text Domain: shooting-gallery
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 **/


if( !class_exists('ShootingGallery') ) {
	class ShootingGallery {
		private static $version = '0.1.0';
		private static $_this;
		private $settings;

		public static function Instance() {
			static $instance = null;
			if ($instance === null) {
				$instance = new self();
			}
			return $instance;
		}

		private function __construct() {
			register_activation_hook( __FILE__, array( $this, 'register_activation' ) );
			register_deactivation_hook( __FILE__, array( $this, 'register_deactivation' ) );
			// Stuff that happens on every page load, once the plugin is active
			
			// Include add ons css/js // 
			add_action( 'wp_enqueue_scripts', array($this, 'add_scripts') );

			$this->initialize_settings();
			if( is_admin() && !( defined('DOING_AJAX') && DOING_AJAX ) ) {
				add_action( 'admin_init', array( $this, 'admin_init' ) );
				add_action( 'admin_menu', array( $this, 'admin_menu' ) );
				add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
				
				add_action( 'save_post', array( $this, 'save_post' ) );
				// // Include the image loader script // 
				// This could be in the register activation funtion, but wasn't working correctly there // 
				include plugin_dir_path( __FILE__ ).'resources/bytes_image_uploader/bbytes_image_uploader.php';

				
			} else {
				add_filter( 'the_content', array( $this, 'the_content') );
				add_shortcode( 'shooting-gallery', array( $this, 'sg_shortcode' ) );
			}
		}
		// PUBLIC STATIC FUNCTIONS
		public static function get_version() {
			return ShootingGallery::$version;
		}
		
		
		// PUBLIC FUNCTIONS
		
		/**
		 * Register and enque resource styles and scripts
		 * Owl Carousel and Featherlight libraries
		 */
		public function add_scripts() {
			
			// used resource: http://www.wpbeginner.com/wp-tutorials/how-to-properly-add-javascripts-and-styles-in-wordpress/
			wp_register_style('owl_styles', plugins_url('/resources/owl-carousel-1.3.2/owl.carousel.css', __FILE__) ,array(), '20120208', 'all' );
			wp_register_style('owl_theme_styles', plugins_url('/resources/owl-carousel-1.3.2/owl.theme.css', __FILE__) ,array(), '20120208', 'all' );
			wp_register_style('feather', plugins_url('/resources/featherlight-1.5.0/featherlight.min.css', __FILE__) ,array(), '20120208', 'all' );
			wp_register_style('feather_gallery', plugins_url('/resources/featherlight-1.5.0/featherlight.gallery.min.css', __FILE__) ,array(), '20120208', 'all' );

			wp_register_script('feather_js', plugins_url('/resources/featherlight-1.5.0/featherlight.min.js', __FILE__), array('jquery'), true);
			wp_register_script('feather_gallery_js', plugins_url('/resources/featherlight-1.5.0/featherlight.gallery.min.js', __FILE__), array('jquery'), true);
			wp_register_script('owl_js', plugins_url('/resources/owl-carousel-1.3.2/owl.carousel.js', __FILE__), array('jquery'), true);
			wp_register_script('custom_js', plugins_url('/custom.js', __FILE__),  array('jquery'), true);


			wp_enqueue_style('feather');
			wp_enqueue_style('feather_gallery');
			wp_enqueue_style('owl_styles');
			wp_enqueue_style('owl_theme_styles');
			wp_enqueue_script('feather_js'); 
			wp_enqueue_script('feather_gallery_js'); 
			wp_enqueue_script('owl_js'); 
			wp_enqueue_script('custom_js'); 
		}
		
		/**
		* Display the Owl Carousel
		* @param array - list of the image ids saved on the post meta
		*/
		public function show_gallery($imageIds) {
			?>
			<div>
				<div style="text-align: center; font-weight: lighter;">
					<p>Enjoy the Shooting Gallery!</hp>
				</div>
				<div class="owl-carousel owl-theme">

				<?php		
				// TODO adjust image size and lightbox size to show a larger / better res image on lightbox
				foreach ($imageIds as $id) {
					$url = wp_get_attachment_image_src($id)[0];
					print wp_get_attachment_image($id, array('700', '600'), "", array("data-featherlight" => $url));
				}	
				?>
				</div>
				<div style="text-align: center; font-weight: lighter;">
					<small>Change gallery position in Settings</small>
				</div>
			</div>
			<?php
		}

		/**
		* Display function to show content in the post and page body
		*/
		public function the_content() {

			$pos = get_option("position");
			$pos = ($pos) ? $pos : "t";
			$post = get_post(get_the_ID());

			// get image IDs //
			$key = get_the_ID() . "";
			$imageIds = [];
			$imageMeta = get_post_meta(get_the_ID(), $key);
			
			if($imageMeta && count($imageMeta) > 0) {
				$imageIds = $imageMeta[0];
			}

			// Title

			// Top position
			if($pos == "t") 
				$this->show_gallery($imageIds);

			// print post body //
			echo "<p>". $post->post_content ."</p>";
			
			// Middle position
			if($pos == "m") 
				$this->show_gallery($imageIds);

			// Bottom position
			if($pos == "b") 	
				$this->show_gallery($imageIds);	
		}


		public function register_activation() {
			// Stuff that only has to run once, on plugin activation
		}

		public function register_deactivation() {
		// TODO Complete this method!
		}

		public function admin_init() {
			// Register Settings Here
			register_setting( 'myoptions', 'position' );
		}

		public function admin_menu() {
			add_options_page(
				__( 'Shooting Gallery Settings', 'shooting-gallery' ),
				__( 'Shooting Gallery', 'shooting-gallery' ),
				'manage_options',
				'shooting-gallery-admin',
				array( $this, 'options_page_callback' )
			);
		}

		/**
		* Render the content of the Shooting Gallery options page
		* Used WP Codex as reference
		*/
		public function options_page_callback() {
			// TODO: Implement options page
			$pos = (get_option('position')) ? get_option('position') : "";
			?>
			<div class="wrap" style="border: 1px solid #999; border-radius: 5px; max-width: 50%; padding: .5em; margin-top:1em;">
				<h1>Shooting Gallery Options</h1>
				<h2>Select the position of your image viewer relative to your post body.</h2>
				<form method="post" action="options.php">
					<?php settings_fields( 'myoptions' ); ?>
					<?php do_settings_sections( 'myoptions' ); ?>
					<label>Position</label>
					<select name="position">
						<option value="t">Choose a Position</option>
						<option value="t" <?php echo ($pos == 't') ? "selected" : "" ?> >Top</option>
						<option value="m" <?php echo ($pos == 'm') ? "selected" : "" ?> >Middle</option>
						<option value="b" <?php echo ($pos == 'b') ? "selected" : "" ?> >Bottom</option>
					</select>
					<?php submit_button(); ?>
				</form>
			</div>
			<?php
		}

		/**
		* Register the meta box and meta box call back
		*/
		public function add_meta_boxes() {
			// confirmed settings = array("post", "page")
			$post_types = $this->get_setting('post_types');
			foreach( $post_types as $type ) {
				// id, title, callback, screen
				// adds the metabox for the given post types with the given title
				add_meta_box(
					'shooting_gallery_metabox',
					__( 'Slideshow Gallery Images', 'shooting-gallery' ),
					array($this,'shooting_gallery_metabox'),
					$type,
					"normal",
					"high",
					null
				);
			}
		}

		
		/**
		 * Builds display for metabox
		 * @param  $post - the global $post object
		 */
		function shooting_gallery_metabox( $post ) {
			global $post; 
			// get key as a string for arg to get_post_meta
			$key = get_the_ID()."";

			// Pass the connected image ids into the $existing images array
			$imagesMeta = get_post_meta(get_the_ID(), $key); // get the image meta data
			$existingImages = array(); // create empty array if no imaage data is present
			
			if(is_array($imagesMeta) && count($imagesMeta) > 0) { // check if is array, and has at least one elementt
				$existingImages = $imagesMeta[0];
			} else {
				$existingImages = array();
			}
			
			// (actionName, nonceName)
			wp_nonce_field( 'shooting_gallery_metabox', 'shooting_gallery_metabox_nonce' );
			
			echo "<h1>Select Images to add to your post's Shooting Gallery</h1>";
			echo "<small>Change the position of your gallery in the Settings menu</small>";		
		    bbytes_render_image_uploader( $post, get_the_ID(), $existingImages, 10 );

		}

		/**
		* Process to run on post save
		*/
		function save_post( $post_id ) {   
			// TODO add in security measures //
			// nonce, permission, etc

			$key = $post_id . ""; // get the key as a string //
			$data = get_post_meta($post_id, $key, false);
			
			// if ( !isset( $_POST['shooting_gallery_metabox_nonce'] ) || !wp_verify_nonce( $_POST['shooting_gallery_metabox_nonce'], 'shooting_gallery_metabox' )) {
			//    		print 'Sorry, your nonce did not verify.';
			//    		exit;
			// 	} else { // nonce passes // do processing
			// }

			// TODO Correct this

			$old = $data;
			$new = isset($_POST[$key]) ? $_POST[$key] : "";
			
			if ( $new && $new !== $old ) {
				update_post_meta( $post_id, $key, $new );
			} elseif ( '' === $new && $old ) {
				delete_post_meta( $post_id, $key, $old );
			}
		}
		

		public function sg_shortcode( $atts, $content ) {
			// TODO: implement shortcode
		}

		// PRIVATE FUNCTIONS
		
		/**
		 * Set up Settings dialog, under Settings in the Dashboard
		 */
		private function initialize_settings() {
			$default_settings = array(
				'post_types' => array( 'post', 'page' ),
			);
			$this->settings = get_option( 'ShootingGallery_options', $default_settings );
		}
		/**
		 * Get Setting Description - used here to get post/page type 
		 * @param $key - string value to search the settings array for.
		 * @return - String value for $settings->key
		 */
		private function get_setting( $key ) {
			if( $key && isset( $this->settings[$key] ) ) {
				// pg fixed typo ['key']
				return $this->settings[$key];
			}
			return null;
		}
	}
	// create instance of Class
	ShootingGallery::Instance();
}
/**
 * Overall goal -  
 * Create a metabox on the post/page editing screen, called "Slideshow Gallery Images", that will allow users to select images to add to a slideshow. The slideshow would then be output above the post automatically, if there are any images attached to the post via this metabox.
 */
?>

