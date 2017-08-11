<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
* Display and handle the 'GDD' settings page
*/
class EbouGDDPluginSettings {

	/**
	* Constructor
	*/
	public function __construct() {
		$this->flux_type = array( 
			'asso' => 'Association', 
			'part' => 'Particulier', 
			'pro' => 'Professionel' 
		);

		$this->setup_styles();
		$this->setup_scripts();
		$this->setup_actions();
	}

	/**
	* Setup the style sheets
	*/
	private function setup_styles() {
		wp_enqueue_style(
			'ebou-gdd-settings-style',
			EBOU_GDD_PLUGIN_RESSOURCES_URL . 'css/settings.css',
			null
		);
	}

	/**
	* Setup the scripts
	*/
	private function setup_scripts() {
		wp_register_script( 
			'ebou-gdd-settings-js', 
			EBOU_GDD_PLUGIN_RESSOURCES_URL . 'js/settings.js',
			array( 'jquery' )
		);

		wp_localize_script( 'ebou-gdd-settings-js', 'api_base_url', EBOU_GDD_BO_API_RETRIEVEGDD_URL );
		wp_localize_script( 'ebou-gdd-settings-js', 'flux_type', $this->flux_type );
		wp_localize_script( 'ebou-gdd-settings-js', 'confirm_delete_msg', __( "Etes-vous sûr de vouloir supprimer cette configuration ?", 'e-bourgogne-gdd' ) );
	}

	/**
	* Hook the plugin settings to Wordpress
	*/
	private function setup_actions() {
		add_action( 'admin_menu', array( $this, 'create_option_menus' ) );
		add_action( 'admin_init', array( $this, 'register_eb_options' ) );

		add_action( 'admin_notices', array( $this, 'display_warning_external_content' ) );

		add_action( 'init', array( $this, 'register_gdd_post_type' ) );
		add_action( 'init', array( $this, 'check_for_event_submissions' ) );
		add_action( 'init', array( $this, 'check_for_messages' ) );
	}

	/**
	* Create the top option menu (e-bourgogne)
	*/
	private function create_top_option_menu() {
		add_menu_page( 
			__( "Réglages e-bourgogne - Guide des Droits et Démarches", 'e-bourgogne-gdd' ), 
			'e-bourgogne', 
			'manage_options', 
			'e-bourgogne-options', 
			array( $this, 'display_settings' ), 
			EBOU_GDD_PLUGIN_RESSOURCES_URL . 'images/logo.png',
			'menu-bottom' );
	}

	/**
	* Save a gdd configuration
	*/
	private function save_gdd( $args ) {
		$post_id = $args['post_id'];
		$gdd_title = $args['ebou-gdd-title'];

		print_r($args);

		if( is_null( get_post( $post_id ) ) ) {
			$post_id = wp_insert_post( array(
				'post_title' => $gdd_title,
				'post_content' => '',
				'post_type' => 'gdd',
				'post_status' => 'publish' 
			), true );
		} else {
			$post_id = wp_insert_post( array(
				'ID' => $post_id,
				'post_title' => $gdd_title,
				'post_content' => '',
				'post_type' => 'gdd',
				'post_status' => 'publish' 
			), true );
		}

		$gdd_args = array(
			'id' => $post_id,
			'title' => $gdd_title,
			'root' => $args['ebou-gdd-root'],
			'css' => $args['ebou-gdd-css'],
			'flux' => $args['ebou-gdd-flux'],
			'organism_id' => $args['gdd_organism_id'],
			'parent_root_list' => $args['ebou-gdd-rootlist']
		);

		$post_meta_id = update_post_meta( $post_id, 'gdd-settings', $gdd_args );
		return ( $post_id );
	}

	/**
	* Delete a gdd configuration by id
	*/ 
	private function delete_directory( $id ) {
		$success = delete_post_meta( $id, 'gdd-settings' );
		$success &= wp_delete_post( $id );

		return $success;
	}

	/**
	* Check if the API answers and if the API key is correct
	* Set both $this->is_gdd_available and $this->is_api_key_ok booleans
	*/
	private function check_service_availability_and_key_validity() {
		$headers = array(
			EBOU_GDD_BO_APIKEY_REFERER . ': ' . $this->api_key
		);

		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, EBOU_GDD_BO_API_CHECK_URL );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $curl, CURLOPT_PROXY, EBOU_GDD_PROXY );
		
		$return  = curl_exec( $curl );

		if( $return === "OK" ) {
			$this->is_gdd_available = true;
			$this->is_api_key_ok = true;
		} elseif( strpos( $return, "Invalid API" ) !== false ) {
			$this->is_gdd_available = true;
			$this->is_api_key_ok = false;
		} else {
			$this->is_gdd_available = false;
		}

		curl_close( $curl );
	}

	/**
	* Check if cURL is enabled, and return an error message if not
	*/
	private function is_curl_enabled() {
		if(function_exists('curl_version')) {
			return true;
		} else {
			return "<div class=\"error\"><p>"
				. __( "Extension cURL désactivée. Les modules e-bourgogne nécessitent cURL pour fonctionner.", 'e-bourgogne-tf' )
				. "</p></div>";
		}
	}

	/**
	* Display a message to warn the user that external data will be loaded
	*/
	public function display_warning_external_content() {
		global $pagenow;
		// Only show for 'e-bourgogne GDD' settings page
		if ( $pagenow === 'admin.php' && $_GET['page'] === $this->sub_menu_slug ) { 
			?>
			<div class="update-nag">
				<h4><?php echo sprintf( __( "Important : afin d'assurer son fonctionnement, le plugin e-bourgogne Guide des Droits et Démarches charge des données extérieures en provenance d'%se-bourgogne%s", 'e-bourgogne-gdd' ), '<a href="//www.e-bourgogne.fr">', '</a>' ); ?></h4>
			</div>
			<?php
		}
	}

	/**
	* Register the options
	*/
	public function register_eb_options() {
		register_setting( EBOU_GDD_APIKEY_OPTION_FIELD, EBOU_GDD_APIKEY_OPTION_KEY );
	}

	/**
	* Create the option menus (top if needed and sub-menu)
	*/
	public function create_option_menus() {
		/*
		* This function exists in each e-bourgogne plugins
		*/

		// Checking if the top menu for e-bourgogne already exists ;
		// if not, creates it
		if ( empty ( $GLOBALS['admin_page_hooks']['e-bourgogne-options'] ) ) {
			$this->create_top_option_menu();
			$this->sub_menu_slug = 'e-bourgogne-options';
		} else {
			$this->sub_menu_slug = 'e-bourgogne-options-gdd';
		}

		add_submenu_page( 
			'e-bourgogne-options', 
			__( "Réglages e-bourgogne - Guide des Droits Démarches", 'e-bourgogne-gdd' ), 
			__( "Guide des Droits Démarches", 'e-bourgogne-gdd' ), 
			'manage_options', 
			$this->sub_menu_slug, 
			array( $this, 'display_settings' ) );
	}

	/**
	* Register the post type 'gdd'
	*/
	public function register_gdd_post_type() {
		register_post_type( 'gdd', array(
				'query_var' => false,
				'rewrite' => false,
				'public' => true,
				'exclude_from_search' => true,
				'publicly_queryable' => false,
				'show_in_nav_menus' => false,
				'show_ui' => false,
				'labels' => array(
					'name' => 'GDD'
				)
			)
		);
	}

	/**
	* Display the settings screen
	*/
	public function display_settings() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?>
		<div class="wrap">
			<h2><?php _e( "Réglages e-bourgogne - Guide des Droits Démarches", 'e-bourgogne-gdd' ); ?></h2>

			<form method="POST" action="options.php">
				<?php 
				settings_fields( EBOU_GDD_APIKEY_OPTION_FIELD );
				do_settings_sections( EBOU_GDD_APIKEY_OPTION_FIELD );

				$is_curl_enabled = $this->is_curl_enabled();
				if($is_curl_enabled !== true) {
					echo $is_curl_enabled;
					return;
				}

				$this->api_key = get_option( EBOU_GDD_APIKEY_OPTION_KEY );
				$this->check_service_availability_and_key_validity();
				?>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="<?php echo EBOU_GDD_APIKEY_OPTION_KEY ?>"><?php _e( "Clé d'API", 'e-bourgogne-gdd' ); ?></label>
							</th>
							<td>
								<input id="<?php echo EBOU_GDD_APIKEY_OPTION_KEY ?>" class="regular-text" type="text" value="<?php echo $this->api_key; ?>" name="<?php echo EBOU_GDD_APIKEY_OPTION_KEY ?>"/>
								<?php
								if( $this->is_api_key_ok ) {
									?>
									<img class="ebou-gdd-check" src="<?php echo EBOU_GDD_PLUGIN_RESSOURCES_URL . 'images/green_check_circle.png'; ?>" title="<?php _e( "Votre clé est valide", 'e-bourgogne-gdd' ); ?>" alt="<?php _e( "Clé valide", 'e-bourgogne-gdd' ); ?>" />
									<?php
								}
								?>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( __( "Enregistrer la clé", 'e-bourgogne-gdd'), "primary", "submit-api-key", true, "id=\"submit-api-key\""); ?>
			</form>


			<?php

			
			if( $this->is_api_key_ok && $this->is_gdd_available ) {

				$gdd_ids = get_posts( array(
					'post_type'   => 'gdd',
					'posts_per_page' => -1,
					'post_status' => 'publish',
					'fields' => 'ids'
				));

				$gdd_settings = array();

				$organism_id = explode( ':', base64_decode( $this->api_key ) )[0];
				?>

				<div class="ebou-gdd-wrap">
					<p>
						<h3><?php _e( "Guides configurés", 'e-bourgogne-gdd' ); ?></h3> 
						<?php submit_button( __( "Nouveau", 'e-bourgogne-gdd' ), "secondary", "create-gdd", false, "id=\"create-gdd\""); ?> 
					</p>
					<select id="existing-gdd" class="ebou-gdd-long-select" size="20">
						<?php
						foreach( $gdd_ids as $gdd_id ) {
							$gdd_settings[$gdd_id] = get_post_meta( $gdd_id, 'gdd-settings', true );
							?><option value="<?php echo $gdd_id ?>"><?php echo get_the_title( $gdd_id ) ?></option><?php
						}

						wp_localize_script( 'ebou-gdd-settings-js', 'gdd_settings', $gdd_settings );
						wp_localize_script( 'ebou-gdd-settings-js', 'user_api_key', $this->api_key );
						wp_localize_script( 'ebou-gdd-settings-js', 'organism_id', $organism_id );
						wp_enqueue_script( 'ebou-gdd-settings-js' );
						?>
					</select>
				</div>

				<form id="delete-gdd-form" method="POST" action="admin.php?page=<?php echo $this->sub_menu_slug ?>">
					<input id="deleted_post_id" type="hidden" name="deleted_post_id" />
					<input id="action" type="hidden" name="action" value="delete-ebou-gdd" />
				</form>

				<div class="ebou-gdd-wrap ebou-gdd-container">
					<form id="save-gdd-form" method="POST" action="admin.php?page=<?php echo $this->sub_menu_slug ?>">

						<h3><?php _e( "Configuration", 'e-bourgogne-gdd' ); ?></h3>

						<span id="no-title-error" class="ebou-gdd-error" style="display: none;">
							<?php _e( "Veuillez indiquer un nom pour cette configuration", 'e-bourgogne-gdd' ); ?>
						</span>

						<input id="post_id" type="hidden" name="post_id" />
						<input id="gdd_organism_id" type="hidden" name="gdd_organism_id" value="<?php echo $organism_id; ?>" />
						<input id="action" type="hidden" name="action" value="save-ebou-gdd" />

						<input id="ebou-gdd-root" type="hidden" name="ebou-gdd-root" value="" />
						<input id="ebou-gdd-parentroot-list" type="hidden" name="ebou-gdd-rootlist" value="" />

						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row">
										<label for="ebou-gdd-title" class="mandatory">
											<?php _e( "Nom", 'e-bourgogne-gdd' ); ?>
										</label>
									</th>
									<td>
										<input id="ebou-gdd-title" class="regular-text" type="text" name="ebou-gdd-title" />
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="ebou-gdd-css">
											<?php _e( "Feuille de style personnalisée (URL)", 'e-bourgogne-gdd' ); ?>
										</label>
									</th>
									<td>
										<input id="ebou-gdd-css" class="regular-text" type="text" name="ebou-gdd-css" />
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="ebou-gdd-flux" class="mandatory">
											<?php _e( "Type de Guide", 'e-bourgogne-gdd'); ?>
										</label>
									</th>
									<td>
										<select id="ebou-gdd-flux" name="ebou-gdd-flux">
											<option value="empty"><?php _e( "Choisir un type de Guide...", 'e-bourgogne-gdd' ); ?></option>
											<?php
											foreach ( $this->flux_type as $type_id => $type ) {
												echo '<option value="' . $type_id. '">' . $type . '</option>';
											}
											?>
										</select>
									</td>
								</tr>
							</tbody>
						</table>

						<h3 class="mandatory"><?php _e( "Thématique ou fiche à afficher", 'e-bourgogne-gdd' ); ?></h3>

						<span id="no-root-error" class="ebou-gdd-error" style="display: none;">
							<?php _e( "Veuillez choisir un unique élément dans la liste ci-dessous", 'e-bourgogne-gdd' ); ?>
						</span>

						<span id="ebou-gdd-spinner" style="display: none;">
							<img src="<?php echo get_site_url(); ?>/wp-includes/images/wpspin.gif" alt="<?php _e( "Récupération des données en cours...", 'e-bourgogne-gdd' ); ?>" />
						</span>

						<div id="ebou-gdd-list">

						</div>

						<p>
							<?php 
							
							$submit_gdd_button_attr = "id=\"submit-gdd\"";
							if( !$this->is_gdd_available ) {
								$submit_gdd_button_attr .= " disabled=\"disabled\"";
							}

							submit_button( __( "Enregistrer la configuration", 'e-bourgogne-gdd' ), "primary", "submit-gdd", false, $submit_gdd_button_attr ); 
							?>

							<a id="delete-gdd" class="button delete disabled" href=""><?php _e( "Supprimer la configuration", 'e-bourgogne-gdd' ); ?></a>
						</p>
					</form>
				</div>

				<?php
			} elseif( !$this->is_api_key_ok && $this->is_gdd_available ) {
				?>
				<p class="ebou-gdd-error">
					<?php echo sprintf( __( "Votre clé d'API n'est pas valide. Veuillez vérifier si elle correspond bien à celle fournie par e-bourgogne. Si le problème persiste, contactez l'%sassistance e-bourgogne%s.", 'e-bourgogne-gdd' ), '<a href="https://www.e-bourgogne.fr/jsp/site/Portal.jsp?page_id=39">', '</a>' ); ?>
				</p>
				<?php
			} else {
				?>
				<div class="error">
					<p><?php _e( "Le service Guide des Droits et Démarches d'e-bourgogne est actuellement indisponible, veuillez réessayer ultérieurement.", 'e-bourgogne-gdd' ); ?></p>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	* Check for submissions on POST request
	*/
	public function check_for_event_submissions(){
		if( isset($_POST['action'] ) ) { 

			$action = $_POST['action'];

			if( strstr( $action, "ebou" ) ) {
				if( $action == "save-ebou-gdd" ) {
					$error = $this->save_gdd( $_POST );

					if( $error instanceof WP_Error ) {
						$message = urlencode( $error->get_error_message() );
						$message_type = 'error';
					} else {
						$message = urlencode( __( "Votre configuration de guide a été sauvegardé avec succès !", 'e-bourgogne-gdd' ) );
						$message_type = 'updated';
					}
				} else if ( $action == "delete-ebou-gdd" ) {
					$success = $this->delete_directory( $_POST['deleted_post_id'] );

					if( !$success ) {
						$message = urlencode( __( "Une erreur est survenue lors de la suppression de la configuration", 'e-bourgogne-gdd' ) );
						$message_type = 'error';
					} else {
						$message = urlencode( __( "Votre configuration a été supprimé avec succès !", 'e-bourgogne-gdd' ) );
						$message_type = 'updated';
					}
				}

				

				$url = admin_url( 'admin.php?page=' . $_GET["page"] . '&ebou-gdd-message=' . $message . '&ebou-gdd-message-type=' . $message_type );
				wp_redirect( $url );
			}
		} 
	}

	/**
	* Check for messages on GET requests
	*/
	public function check_for_messages() {
		if( isset( $_GET['ebou-gdd-message'] ) ) {
			$message = $_GET['ebou-gdd-message'];

			if( isset( $_GET['ebou-gdd-message-type'] ) ) {
				$message_type = $_GET['ebou-gdd-message-type'];
			} else {
				$message_type = 'update-nag';
			}

			?>
			<div class="<?php echo $message_type; ?>">
				<p><?php echo $message; ?><p>
			</div>
			<?php
		}
	}
}
?>