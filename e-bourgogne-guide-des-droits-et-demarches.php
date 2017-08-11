<?php

/**
* @package e-bourgogne-guide-des-droits-et-demarches
* @version 1.0.1
*/
/*
Plugin Name: e-bourgogne Guide des Droits et Démarches
Plugin URI: https://www.e-bourgogne.fr
Description: Guide des Droits et Démarches e-bourgogne
Requires at least: 3.3
Tested up to: 4.3.1
Version: 1.0.1
Author: e-bourgogne
Author URI: http://www.e-bourgogne.fr
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once 'settings.php';

if( !class_exists( 'EbouGDDPlugin' ) ) :

/**
* Register the plugin
*
* Display the 'GDD' pannel on post/page editing, add the 'gdd' to the page, etc...
*/
class EbouGDDPlugin {

	/**
	* Initialization
	*/
	public static function init() {
		$ebougdd = new self();
	}

	/**
	* Constructor
	*/
	public function __construct() {
		$this->define_constants();
		$this->setup_actions();
		$this->setup_filters();
		$this->setup_shortcode();

		if( is_admin() ) {
			$ebougddsettings = new EbouGDDPluginSettings();
		}
	}

	/**
	* Define the constants used by the plugin
	*/
	private function define_constants() {
		define( 'EBOU_GDD_PLUGIN_BASE_URL', plugins_url( 'e-bourgogne-guide-des-droits-et-demarches' ) . '/' );
		define( 'EBOU_GDD_PLUGIN_RESSOURCES_URL', EBOU_GDD_PLUGIN_BASE_URL . 'resources/');

		define( 'EBOU_GDD_PROXY', '');

		define( 'EBOU_GDD_BO_BASE_URL', 'https://monservicepublic.e-bourgogne.fr/' );
		define( 'EBOU_GDD_BO_API_URL', EBOU_GDD_BO_BASE_URL . 'api/gdd/' );
		define( 'EBOU_GDD_BO_API_CHECK_URL', EBOU_GDD_BO_API_URL . 'check' );
		define( 'EBOU_GDD_BO_API_RETRIEVEGDD_URL', EBOU_GDD_BO_API_URL . 'getGDD' );
		define( 'EBOU_GDD_BO_APIKEY_REFERER', 'ebou-api-key' );

		define( 'EBOU_GDD_FO_BASE_URL', 'https://monguide.e-bourgogne.fr/');
		define( 'EBOU_GDD_FO_SCRIPT_URL', EBOU_GDD_FO_BASE_URL . "api/v1/guide");

		define( 'EBOU_GDD_APIKEY_OPTION_FIELD', 'ebou_gdd_api_group' );
		define( 'EBOU_GDD_APIKEY_OPTION_KEY', 'ebou_api_key' );
	}

	/**
	* Hook Ebourgogne GDD Plugin into Wordpress
	*/
	private function setup_actions() {
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'admin_footer', array( $this,'insert_admin_footer' ) );
	}

	/**
	* Hook Ebourgogne GDD Plugin into Wordpress
	*/
	private function setup_filters() {
		add_filter( 'media_buttons_context', array( $this,'insert_ebou_gdd_button' ) );
	}

	/**
	* Register [ebou-gdd] shortcode
	*/
	private function setup_shortcode() {
		add_shortcode( 'ebou-gdd', array( $this,'register_shortcode' ) );
	}

	/**
	* Initialise translations
 	*/
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'e-bourgogne-gdd', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	* Append the 'Choisissez un GDD' thickbox content to the bottom of the admin pages
	*/
	public function insert_admin_footer() {
		global $pagenow;

		 // Only run in post/page creation and edit screens
	    if ( in_array( $pagenow, array( 'post.php', 'page.php', 'post-new.php', 'post-edit.php' ) ) ) {
			$gdds = get_posts( array(
				'post_type'   => 'gdd',
				'posts_per_page' => -1,
				'post_status' => 'publish'
			));

			?>

			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery('#add-ebou-gdd-button').on('click', function() {
						var id = jQuery('#ebou-gdd-select option:selected').val(),
							title = jQuery('#ebou-gdd-select option:selected').text();
						window.send_to_editor('[ebou-gdd id=' + id + ' title=\"' + title + '\"]');
						tb_remove();
					});
				});
			</script>

			<div id="choose-ebou-gdd" style="display: none;">
				<div class="wrap">
					<?php
					if( count( $gdds ) ) {
						?>
						<div class="update-nag">
							<p><?php _e( "Attention : il est déconseillé d'ajouter plusieurs guides sur une même page.", 'e-bourgogne-gdd' ); ?></p>
						</div>
						<h3 style="margin-bottom: 20px;"><?php _e( "Ajouter un Guide des Droits et Démarches", 'e-bourgogne-gdd' ); ?></h3>
						<select id ="ebou-gdd-select">
							<?php
							foreach ( $gdds as $gdd ) {
								echo "<option value=\"" . $gdd->ID . "\">" . $gdd->post_title . "</option>";
							}
							?>
						</select>
						<button id="add-ebou-gdd-button" class="button primary"><?php _e( "Ajouter ce Guide", 'e-bourgogne-gdd' ); ?></button>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		}
	}

	/**
	* Append the 'Ajouter un GDD' button to the admin pages
	*/
	public function insert_ebou_gdd_button($context) {
		if( !current_user_can( 'edit_others_pages' ) ) {
			return $context;
		}

		global $pagenow;
		// Only run in post/page creation and edit screens
	    if ( in_array( $pagenow, array( 'post.php', 'page.php', 'post-new.php', 'post-edit.php' ) ) ) {

			$context .= '<a href="#TB_inline?&inlineId=choose-ebou-gdd" class="thickbox button" title="Ajouter un GDD">'
				. '<span class="wp-media-buttons-icon" style="background: url(\'' . EBOU_GDD_PLUGIN_RESSOURCES_URL . 'images/logo.png\'); background-repeat: no-repeat; background-position: left bottom;"></span> ' 
				. __( "Ajouter un GDD", 'e-bourgogne-gdd' )
				. '</a>';
		}

		return $context;
	}

	/**
	* Register the [ebou-gdd] shortcode
	*/
	public function register_shortcode( $atts ) {
		$atts = shortcode_atts( array( 
			'id' => false,
			'title' => ""
		), $atts, 'ebou-gdd');

		$gdd_id = $atts['id'];

		if( !$gdd_id ) {
			return;
		}

		$gdd_settings = get_post_meta( $gdd_id, 'gdd-settings', true );

		if( !$gdd_settings ) {
			return "<!-- GDD e-bourgogne " . $gdd_id . " non trouvé -->";
		}

		return $this->generate_script( $gdd_settings );
	}

	/**
	* Return the tags that will include the 'GDD' to the page
	*/
	private function generate_script( $gdd_settings ) {
		$id = $gdd_settings['id'];
		$url = EBOU_GDD_FO_SCRIPT_URL 
			. "/" . $gdd_settings['flux']
			. "/" . $gdd_settings['root']
			. "/" . $gdd_settings['organism_id']
			. ".js";

		if( $gdd_settings['css'] != "" ) {
			$url .= "?customCss=" . $gdd_settings['css'];
		}

		return "<div id=\"iframeFlag-" . $id . "\"></div>"
			. "<script type=\"text/javascript\" src=\"" . $url . "\" charset=\"UTF-8\"></script>";
	}

}
endif;

add_action( 'plugins_loaded', array( 'EbouGDDPlugin', 'init' ) );

?>