<?php
/*
Plugin Name: User Upgrade Capability
Plugin URI: http://justinandco.com/plugins/user-upgrade-capabilities/
Description: Link multiple network sites/blogs together - Maintain only one site list of users.
Version: 2.0
Author: Justin Fletcher
Author URI: http://justinandco.com
Domain Path: /languages
License: GPLv2 or later
Network: true
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * UUC class.
 */
class UUC {

	// Refers to a single instance of this class.
        private static $instance = null;

	// Define transient prefix
        public $transient_prefix = 'uuc_';

        // file path relative to the plugin directory
        public   $plugin_file = 'user-upgrade-capability/user-upgrade-capability.php';

        // Settings page slug	
        public	 $menu = 'uuc-settings';

        // Settings Admin Menu Title
        public	 $menu_title = 'Reference Site';

        // Settings Page Title
        public	 $page_title = 'Upgrade Capability';
        
        
        
        
        

        /**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

                // drop out with warning if not a Network
                if ( ! is_multisite() ) {
                        add_action( 'admin_notices', array( $this, 'admin_not_a_network_notice' ));
                        return;
                }	
                
		// Load the textdomain.
		add_action( 'plugins_loaded', array( $this, 'i18n' ), 1 );

		// Set the constants needed by the plugin.
		add_action( 'plugins_loaded', array( $this, 'constants' ), 2 );
		
		// Load the functions files.
		add_action( 'plugins_loaded', array( $this, 'includes' ), 3 );

		// Attached to after_setup_theme. Loads the plugin installer CLASS after themes are set-up to stop duplication of the CLASS.
		// this should remain the hook until TGM-Plugin-Activation version 2.4.0 has had time to roll out to the majority of themes and plugins.
		add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ));
		
		// register admin side - upgrade routine and menu item.
		add_action( 'admin_init', array( $this, 'admin_init' ));

		// Load admin error messages	
		add_action( 'admin_init', array( $this, 'deactivation_notice' ));
		add_action( 'admin_notices', array( $this, 'action_admin_notices' ));
                
		// override local site capability and roles
		add_action( 'init', array( $this, 'override_site_caps') ); 
                
		// override local user capability and roles
		add_action( 'init', array( $this, 'override_user_caps') ); 
                

                
	}
	
	/**
	 * Defines constants used by the plugin.
	 *
	 * @return void
	 */
	public function constants() {

		// Define constants
		define( 'UUC_MYPLUGINNAME_PATH', plugin_dir_path( __FILE__ ) );
		define( 'UUC_MYPLUGINNAME_FULL_PATH', UUC_MYPLUGINNAME_PATH . 'user-upgrade-capability.php' );
		define( 'UUC_PLUGIN_DIR', trailingslashit( plugin_dir_path( UUC_MYPLUGINNAME_PATH )));
		define( 'UUC_PLUGIN_URI', plugins_url( '', __FILE__ ) );
		
		// admin prompt constants
		define( 'UUC_PROMPT_DELAY_IN_DAYS', 30);
		define( 'UUC_PROMPT_ARGUMENT', 'uuc_hide_notice');
		
	}

	/**
	 * Loads the initial files needed by the plugin.
	 *
	 * @return void
	 */
	public function includes() {

		// settings 
		require_once( UUC_MYPLUGINNAME_PATH . 'includes/settings-general.php' );
                
                if ( get_option( 'uuc_reference_site' ) ) {
                    require_once( UUC_MYPLUGINNAME_PATH . 'includes/settings-upgrade-roles.php' );  
                    require_once( UUC_MYPLUGINNAME_PATH . 'includes/settings-upgrade-caps.php' );  
                }

		// include the network wide set-up.
		require_once( UUC_MYPLUGINNAME_PATH . 'includes/class-user-upgrade-capability-network-info.php' );  
	
	}
	
	/**
	 * Initialise the plugin installs
	 *
	 * @return void
	 */
	public function after_setup_theme() {

		// install the plugins and force activation if they are selected within the plugin settings
		require_once( UUC_MYPLUGINNAME_PATH . 'includes/plugin-install.php' );
		
	}

        
        /**
	 * Initialise the plugin menu. 
	 *
	 * @return void
	 */
	public function admin_menu() {

	}
    
	/**
	 * sub_menu_page: 
	 *
	 * @return void
	 */
	public function sub_menu_page() {
		
	}	
	
	/**
	 * Initialise the plugin by handling upgrades and 
         * loading the text domain. 
	 *
	 * @return void
	 */
	public function admin_init() {

		//Registers user installation date/time on first use
		$this->action_init_store_user_meta();
		
		$plugin_current_version = get_option( 'uuc_plugin_version' );
		$plugin_new_version =  self::plugin_get_version();
		
		// Admin notice hide prompt notice catch
		$this->catch_hide_notice();

		if ( version_compare( $plugin_current_version, $plugin_new_version, '<' ) ) {
		
			$plugin_current_version = isset( $plugin_current_version ) ? $plugin_current_version : 0;

			$this->uuc_upgrade( $plugin_current_version );

			// set default options if not already set..
			$this->do_on_activation();

			// create the plugin_version store option if not 
                        // already present.
			$plugin_version = self::plugin_get_version();
			update_option( 'uuc_plugin_version' , $plugin_version ); 
			
			// Update the option again after uuc_upgrade() changes 
                        // and set the current plugin revision	
			update_option( 'uuc_plugin_version' , $plugin_new_version ); 
		}
                
                // remove the "Add New" user submenu as users are now only added or removed 
                // from the primary reference site.
                remove_submenu_page( 'users.php', 'user-new.php' ); 
	}
	

	/**
	 * Loads the text domain.
	 *
	 * @return void
	 */
	public function i18n( ) {
		$ok = load_plugin_textdomain( 'user-upgrade-capability', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
	}
		
	/**
	 * Provides an upgrade path for older versions of the plugin
	 *
	 * @param float $current_plugin_version the local plugin version prior to an update 
	 * @return void
	 */
	public function uuc_upgrade( $current_plugin_version ) {
		
		 
		// upgrade code when required.
		if ( $current_plugin_version < '1.5' ) {
                    
                        $value = array( $this->key_capability() );
                        
                        $old_key_cap = get_option( 'uuc_reference_key_capability' );
                        $old_caps_to_add = get_option( 'uuc_additional_capabilties' );
                        // convert capability option string to array delimiting 
                        // by "new line" also trim off white space
                        $uuc_caps =   array_map( 'trim',explode( "\r\n", trim( $old_caps_to_add ) ) );  

                        add_option( 'uuc_key_cap_' . $old_key_cap, $uuc_caps ); 
                        
                        // also enable this key_cap
                        add_option( 'uuc_key_caps' , array( $old_key_cap ) );
                        
                        // remove old options from database
                        delete_option( 'uuc_reference_key_capability' );
                        delete_option( 'uuc_additional_capabilties' );		
                        delete_option( 'uuc_join-my-multisite_plugin' );
                        delete_option( 'uuc_deactivate_join-my-multisite' );
                        

		}
		
	}

	/**
	 * Flush your rewrite rules for plugin activation and initial install date.
	 *
	 * @access public
	 * @return $settings
	 */	
	static function do_on_activation() {

		// Record plugin activation date initially
		add_option( 'uuc_install_date',  time() ); 
                
		// force timeout to be 15 mins initially 
		add_option( 'uuc_delay_check', 15 );

		flush_rewrite_rules();
	}

	/**
	 * remove the reference site option setting for safety when 
         * re-activating the plugin
	 *
	 * @access public
	 * @return $settings
	 */	
	static function do_on_deactivation() {

		delete_option( 'uuc_reference_site' );
	}
	
	/**
	 * Returns current plugin version.
	 *
	 * @access public
	 * @return $plugin_version
	 */	
	static function plugin_get_version() {

		$plugin_data = get_plugin_data( UUC_MYPLUGINNAME_FULL_PATH, false, false );	

		$plugin_version = $plugin_data['Version'];	
		return filter_var($plugin_version, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
	}
	
	/**
	 * Register Plugin Deactivation Hooks for all the currently 
	 * enforced active extension plugins.
	 *
	 * @access public
	 * @return null
	 */
	public function deactivation_notice() {

		// loop plugins forced active.
		$plugins = UUC_General_Settings::get_instance()->selected_plugins( 'uuc_plugin_extension' );

		foreach ( $plugins as $plugin ) {
			$plugin_file = UUC_PLUGIN_DIR . $plugin["slug"] . '\\' . $plugin['slug'] . '.php' ;
			register_deactivation_hook( $plugin_file, array( 'UUC', 'on_deactivation' ) );
		}
	}

	/**
	 * This function is hooked into plugin deactivation for 
	 * enforced active extension plugins.
	 *
	 * @access public
	 * @return null
	 */
	public static function on_deactivation()
        {
            if ( ! current_user_can( 'activate_plugins' ) )
                return;
            $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
            check_admin_referer( "deactivate-plugin_{$plugin}" );

                    $plugin_slug = explode( "/", $plugin);
                    $plugin_slug = $plugin_slug[0];
                    update_option( "uuc_deactivate_{$plugin_slug}", true );
        }


        
	/**
	 * Display the admin warnings.
	 *
	 * @access public
	 * @return null
	 */
	public function action_admin_notices( ) {                     
            
		// loop plugins forced active.
		$plugins = UUC_General_Settings::get_instance()->selected_plugins( 'uuc_plugin_extension' );

		// for each extension plugin enabled (forced active) 
                // add a error message for deactivation.
		foreach ( $plugins as $plugin ) {
			$this->action_admin_plugin_forced_active_notices( $plugin["slug"] );
		}
		
		// Prompt for rating
		$this->action_admin_rating_prompt_notices( );
	}
	
	/**
	 * Display the admin error message for plugin forced active.
	 *
	 * @access public
	 * @return null
	 */
	public function action_admin_plugin_forced_active_notices( $plugin ) {
	
		$plugin_message = get_option("uuc_deactivate_{$plugin}");
		if ( ! empty( $plugin_message ) ) {
			?>
			<div class="error">
				  <p><?php esc_html_e(sprintf( __( 'Error the %1$s plugin is forced active with ', 'user-upgrade-capability'), $plugin)); ?>
				  <a href="users.php?page=<?php echo $this->menu ; ?>&tab=uuc_plugin_extension"> <?php echo esc_html(__( 'User Upgrade Capability Settings!', 'user-upgrade-capability')); ?> </a></p>
			</div>
			<?php
			update_option("uuc_deactivate_{$plugin}", false); 
		}
	}

		
	/**
	 * Store the current users start date
	 *
	 * @access public
	 * @return null
	 */
	public function action_init_store_user_meta( ) {
		
		// store the initial starting meta for a user
		add_user_meta( get_current_user_id(), 'uuc_start_date', time(), true );
		add_user_meta( get_current_user_id(), 'uuc_prompt_timeout', time() + 60*60*24*  UUC_PROMPT_DELAY_IN_DAYS, true );

	}

	/**
	 * Display the admin message for plugin rating prompt.
	 *
	 * @access public
	 * @return null
	 */
	public function action_admin_rating_prompt_notices( ) {

		$user_responses =  array_filter( (array)get_user_meta( get_current_user_id(), UUC_PROMPT_ARGUMENT, true ));	
		if ( in_array(  "done_now", $user_responses ) ) 
			return;

		if ( current_user_can( 'install_plugins' ) ) {
			
			$next_prompt_time = get_user_meta( get_current_user_id(), 'uuc_prompt_timeout', true );
			if ( ( time() > $next_prompt_time )) {
				$plugin_user_start_date = get_user_meta( get_current_user_id(), 'uuc_start_date', true );
				?>
				<div class="update-nag">
					
					<p><?php esc_html(printf( __("You've been using <b>User Upgrade Capability</b> for more than %s.  How about giving it a review by logging in at wordpress.org ?", 'user-upgrade-capability'), human_time_diff( $plugin_user_start_date) )); ?>
				
					</p>
					<p>

						<?php echo '<a href="' .  esc_url(add_query_arg( array( UUC_PROMPT_ARGUMENT => 'doing_now' )))  . '">' .  esc_html__( 'Yes, please take me there.', 'user-upgrade-capability' ) . '</a> '; ?>
						
						| <?php echo ' <a href="' .  esc_url(add_query_arg( array( UUC_PROMPT_ARGUMENT => 'not_now' )))  . '">' .  esc_html__( 'Not right now thanks.', 'user-upgrade-capability' ) . '</a> ';?>
						
						<?php
						if ( in_array(  "not_now", $user_responses ) || in_array(  "doing_now", $user_responses )) { 
							echo '| <a href="' .  esc_url(add_query_arg( array( UUC_PROMPT_ARGUMENT => 'done_now' )))  . '">' .  esc_html__( "I've already done this !", 'user-upgrade-capability' ) . '</a> ';
						}?>

					</p>
				</div>
				<?php
			}
		}	
	}

        
	/**
	 * Send Error message to Amdin when a non-multisite installation.
	 *
	 * @access public
	 * @return null
	 */
	public function admin_not_a_network_notice( ) {
                // Prompt for multisite error
                ?>
                <div class="notice notice-error">
                                <p>
                                <?php esc_html_e( __("The User Upgrade Capability plugin only functions on WordPress Multisite.", 'user-upgrade-capability' ) ); ?>
                                </p>
                        </p>
                </div>
                <?php 


	}
	
        
	/**
	 * Store the user selection from the rate the plugin prompt.
	 *
	 * @access public
	 * @return null
	 */
	public function catch_hide_notice() {
	
		if ( isset($_GET[UUC_PROMPT_ARGUMENT]) && $_GET[UUC_PROMPT_ARGUMENT] && current_user_can( 'install_plugins' )) {
			
			$user_user_hide_message = array( sanitize_key( $_GET[UUC_PROMPT_ARGUMENT] )) ;				
			$user_responses =  array_filter( (array)get_user_meta( get_current_user_id(), UUC_PROMPT_ARGUMENT, true ));	

			if ( ! empty( $user_responses )) {
				$response = array_unique( array_merge( $user_user_hide_message, $user_responses ));
			} else {
				$response =  $user_user_hide_message;
			}
			
			check_admin_referer();	
			update_user_meta( get_current_user_id(), UUC_PROMPT_ARGUMENT, $response );

			if ( in_array( "doing_now", (array_values((array)$user_user_hide_message ))))  {
				$next_prompt_time = time() + ( 60*60*24*  UUC_PROMPT_DELAY_IN_DAYS ) ;
				update_user_meta( get_current_user_id(), 'uuc_prompt_timeout' , $next_prompt_time );
				wp_redirect( 'http://wordpress.org/support/view/plugin-reviews/user-upgrade-capability' );
				exit;					
			}

			if ( in_array( "not_now", (array_values((array)$user_user_hide_message ))))  {
				$next_prompt_time = time() + ( 60*60*24*  UUC_PROMPT_DELAY_IN_DAYS ) ;
				update_user_meta( get_current_user_id(), 'uuc_prompt_timeout' , $next_prompt_time );		
			}
				
				
			wp_redirect( remove_query_arg( UUC_PROMPT_ARGUMENT ) );
			exit;		
		}
	}
	
	public function key_capability() {
		$key_capability = get_option( 'uuc_reference_key_capability' );
		  
		if( $key_capability . '' == '' ) {
			// if no explicit capability is defined use the site 
                        // url path name ( this allows a level of flexibility 
                        // on automatically looking for a named capability 
                        // without the need to set this option manually )
			return basename( site_url() );
		} else {
			// otherwise use the capability defined
			return $key_capability; 
		}  		
	}


        public function override_site_caps( ) {
                
                $site_transient_name =  $this->get_transient_prefix( ) . 'site_block_role_cap_alignment';
                $transient_primary_site_roles = get_transient( $site_transient_name );
//$transient_primary_site_roles = false;
                // drop out is transient still present
                if( ! empty( $transient_primary_site_roles ) ) {
                        // The function will return here every time after 
                        // the first time it is run, until the transient expires.
                        return ;
                }

               // $prefix = esc_sql( $this->get_transient_prefix( ) );
                $primary_ref_site = get_option( $this->get_transient_prefix( ) . 'reference_site' );                                       
                $current_site = get_current_blog_id();
                $user = wp_get_current_user( );                
                 
                $local_roles = $this->clone_roles_caps_from_ref_site( $primary_ref_site, $current_site );                                                    

                //drop out if no roles updated locally.
                if ( ! $local_roles ) {    
                    return;
                }
                
                //if we are refreshing the site role/caps cloning again from the primary site 
                //then delete user transients to force a refresh of user caps     
                $this->purge_uuc_transients();
                
                // Set the user transient to one hour for overwrite of the site role/cap setup           
                $delay_time = 1 * HOUR_IN_SECONDS;
                set_transient( $site_transient_name, true, $delay_time ); 
        }
        
        public function override_user_caps( ) {
            
                $current_site = get_current_blog_id();
                $primary_ref_site = get_option( 'uuc_reference_site' ); 
                if ( ! $primary_ref_site
                     || $current_site == $primary_ref_site
                   ) 
                {   
                    // drop out and do nothing
                    return;
                }
                
// try this switch to primary (it follows the core method)     
                /*
                $primary_ref_site = get_option( 'uuc_reference_site' );                 
            	switch_to_blog( $primary_ref_site );
                global $current_user;
                $user = get_userdata( $current_user->ID );
                restore_current_blog( );
                 */
                
//die(var_dump($user))  ;  
            
                $user = wp_get_current_user( );
       
                if( ! $user->exists( ) ) {
                        global $wp;
                        $current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );
                        switch_to_blog( $primary_ref_site );
                        wp_redirect( wp_login_url( $current_url ) );
                        restore_current_blog( );
			exit;                
                  }
            
              //  if( $this->is_login_page( ) ) {
              //          return;
              //    }
                  
                  
                $user_transient_name = $this->get_transient_prefix( ) . 'user_' . $user->ID . '_block_override';
                $transient = get_transient( $user_transient_name );
//$transient = false;
                // drop out here until the transient expires
                if( ! empty( $transient ) ) {
                        return ;
                }                
                
                $this->override_wp_user_caps( $primary_ref_site, $user );
                $this->override_wp_user_roles( $primary_ref_site, $user );

                // if no cababilities assigned to the user after all overrides from 
                // the primary ref site clean all and dropout
                if ( ! $user->allcaps ) {
                    wp_die( "You do not have permission." , 'user-upgrade-capability'  );
                }
                
                // Set the user transient limit to 10 sec minumum overwrite interval          
                $delay_time = max( get_option( 'uuc_delay_check' ) * MINUTE_IN_SECONDS, 10 )  ;           
                set_transient( $user_transient_name, true, $delay_time );
                        
        }
        
        /*
         * override local user capabilities.
         * Returns true if a match was found.
         *
         * @param int $primary_ref_site Reference Site/blog.
         * @param object $user current user object.
         */ 
	public function override_wp_user_caps( $primary_ref_site, $user ) {
                     
                $user_caps =  array_keys( $user->caps );                 
                $new_caps = array();

                switch_to_blog( $primary_ref_site );
                global $table_prefix;
                $primary_ref_site_table_prefix = $table_prefix;
                restore_current_blog( );

                global $table_prefix;

                // Collect the site roles defined in the network primary reference site 
                // wp_ used here is not the table prefix but the option-name prefix and 
                // so doesn't change along with the $table_prefix
                $blog_wp_user_roles = get_blog_option( $primary_ref_site, $primary_ref_site_table_prefix . 'user_roles' );

                // Update the current local site defined roles to match
                update_option( $table_prefix . 'user_roles', $blog_wp_user_roles );   

                // Get rid of any bogus roles keys
                $uuc_key_caps = array_filter( ( array ) get_option( 'uuc_key_caps' ) );
 
                // collect the user roles uuc is to add to the local site
                $new_caps = array( );

                foreach( $uuc_key_caps as $key_cap ) {

                        $new_caps_from_settings_tab  = array_filter( ( array ) get_option( 'uuc_key_cap_' . $key_cap ) );

                        // check for the capability on the base site
                        if ( current_user_can_for_blog( $primary_ref_site, $key_cap ) ) {

                            $new_caps = array_merge( $new_caps_from_settings_tab, $new_caps );
                        }
                }   
                    
                if ( $new_caps ) {

                        // Make sure that we don't call $user->add_cap() any more than it's necessary
                        $_new_caps = array_diff( $new_caps, $user_caps );	
                        foreach ( $_new_caps as $_cap ) {
                                $user->add_cap( $_cap );
                        }

                }                

                //Filter out caps that are role names and assign remainder to $caps_to_remove
                $wp_roles = wp_roles();
                $caps_to_remove = array_diff( $user_caps, $new_caps );
                $roles_in_cap_array = array_filter( $caps_to_remove, array( $wp_roles, 'is_role' ) );
           
                $caps_to_remove = array_diff( $user_caps, $new_caps, $roles_in_cap_array );
     
                foreach ( $caps_to_remove as $_cap ) {
                        $user->remove_cap( $_cap );                      
                }    
	}
		
        /**
         * override local user roles.
         * Returns true if a match was found.
         *
         * @param int $primary_ref_site Reference Site/blog.
         * @param object $user current user object.
         */
	public function override_wp_user_roles( $primary_ref_site, $user ) {

                global $wp_roles;

                // Load roles if not set
                if ( ! isset( $wp_roles ) ) {
                        $wp_roles = new WP_Roles( );
                }
                $roles = $wp_roles->roles;


                // Get rid of any bogus roles
                $uuc_key_roles = array_filter( ( array ) get_option( 'uuc_key_roles' ) );
 
                // collect the user roles uuc is to add to the local site
                $new_roles = array( );

                foreach( $uuc_key_roles as $role ) {

                        $new_roles_from_settings_tab  = array_filter( ( array ) get_option( 'uuc_key_role_' . $role ) );

                        if ( $this->user_has_role_for_blog( $primary_ref_site, $role, $user->ID ) ) {
                            
                            $new_roles = array_merge( $new_roles_from_settings_tab, $new_roles );
                        }
                }   
                
                $new_roles = array_unique( $new_roles ); 
                
                $user_roles = array_intersect( array_values( $user->roles ), array_keys( $roles ) );

                $roles_to_remove = array_diff( $user_roles, $new_roles );

 
                foreach ( $roles_to_remove as $_role ) {
                       $user->remove_role( $_role );                    
                }
 
                if ( $new_roles ) {
                        // Make sure that we don't call $user->add_role() 
                        // any more than it's necessary
                        $_new_roles = array_diff( $new_roles, $user_roles );	
                        foreach ( $_new_roles as $_role ) {
                                $user->add_role( $_role );
                        }       
                }
	}

        /**
         * override local user roles.
         * Returns true if a match was found.
         *
         * @param int $primary_ref_site Reference Site/blog.
         * @param object $user current user object.
         * return $local_roles array of cloned roles   
         */
	public function clone_roles_caps_from_ref_site( $primary_ref_site, $current_site ) {
                 
                $uuc_key_roles = array_filter( ( array ) get_option( 'uuc_key_roles' ) ); 
                $user = wp_get_current_user( );           
                $local_roles = array();

                if ( $primary_ref_site == 0  // if no ref site
                     || $current_site == $primary_ref_site
                     || ! is_user_member_of_blog( $user->ID, $primary_ref_site )
                     || empty( $uuc_key_roles )
                   )
                {   
                    return $local_roles;
                }
                         
            
                switch_to_blog( $primary_ref_site );

                global $wp_roles;

                // Load roles if not set
                if ( ! isset( $wp_roles ) ) {
                        $wp_roles = new WP_Roles( );
                }

                $ref_site_roles = $wp_roles->roles;
         
                restore_current_blog();
                
                // always keep the wordpress basic roles, if available on the 
                // reference site this will stop plugin faults where they 
                // expect these roles to be present
                $role_keys = array_unique( array_merge( $uuc_key_roles, 
                                                        array( 'administrator', 
                                                            'editor', 
                                                            'author', 
                                                            'contributor', 
                                                            'subscriber',
                                                        ) 
                                                      ) 
                                        );
  
                foreach ( $ref_site_roles as $role_key => $role ) {
 
                        // remove all roles locally.
                        remove_role( $role_key );                        
                        
                        // re-add role and limit to only the uuc selected roles.
                        if ( in_array( $role_key, $role_keys ) ) {
                           // this causes issues with memmory usage.
                           $role = $this->clone_role( $role_key, $role[ 'name' ], $role[ 'capabilities' ] );
                           //die(var_dump($ref_site_roles));  
                           $local_roles[$role->name] = $role;
                        } 
		}
                return $local_roles;

	}
	
        /**
         * Re-creates the role locally taking a copy from the reference/primary site
         *
         * @param string $role
         * @param string $rolename
         * @param array $caps
         * @return void
         */
        public function clone_role( $role, $rolename, $caps ) {

            $role_instance = add_role( $role, $rolename );

            foreach( $caps as $cap_key => $cap_enabled ) {

                    if ( $cap_enabled ) {
                          $role_instance->add_cap( $cap_key );
                    }
            }
            
            return $role_instance;

        }
   

        /**
         * Checks if a particular user has a role. 
         * Returns true if a match was found.
         *
         * @param string $role Role name.
         * @param int $user_id (Optional ) The ID of a user. Defaults to the current user.
         * @return bool
         */
        public function user_has_role_for_blog( $blog_id, $role, $user_id ) {

                switch_to_blog( $blog_id );

                if ( is_numeric( $user_id ) ) {
                        $user = get_userdata( $user_id );
                } else {
                        $user = wp_get_current_user( );
                }

                if ( ! $user->exists( ) ) {
                    restore_current_blog( );
                    return false;
                }

                restore_current_blog( );
                return in_array( $role, ( array ) $user->roles );
        }




        static public function wp_capabilities() {

                /* Create list of caps that are documented on WP.ORG */
                $defaults = array(
                        'activate_plugins',
                        'add_users',
                        'create_users',
                        'delete_others_pages',
                        'delete_others_posts',
                        'delete_pages',
                        'delete_plugins',
                        'delete_posts',
                        'delete_private_pages',
                        'delete_private_posts',
                        'delete_published_pages',
                        'delete_published_posts',
                        'delete_users',
                        'edit_dashboard',
                        'edit_files',
                        'edit_others_pages',
                        'edit_others_posts',
                        'edit_pages',
                        'edit_plugins',
                        'edit_posts',
                        'edit_private_pages',
                        'edit_private_posts',
                        'edit_published_pages',
                        'edit_published_posts',
                        'edit_theme_options',
                        'edit_themes',
                        'edit_users',
                        'import',
                        'install_plugins',
                        'install_themes',
                        'list_users',
                        'manage_categories',
                        'manage_links',
                        'manage_options',
                        'moderate_comments',
                        'promote_users',
                        'publish_pages',
                        'publish_posts',
                        'read',
                        'read_private_pages',
                        'read_private_posts',
                        'remove_users',
                        'switch_themes',
                        'unfiltered_html',
                        'unfiltered_upload',
                        'update_core',
                        'update_plugins',
                        'update_themes',
                        'upload_files'
                );

                /* Return the array of default capabilities. */
                return $defaults;
        }   
        
 
        public function is_login_page() {
            return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
        }  
        
        private function get_transient_prefix( ) {
                return $this->transient_prefix;
        }
        
        // Purge all the transients associated with our plugin.
        public function purge_uuc_transients( ) {

                global $wpdb;

                $prefix = esc_sql( $this->get_transient_prefix( ) );
      

                $options = $wpdb -> options;

                $t  = esc_sql( "_transient_timeout_$prefix%" );

                $sql = $wpdb -> prepare (
                        "
                          SELECT option_name
                          FROM $options
                          WHERE option_name LIKE '%s'
                        ",
                        $t
                );

                $transients = $wpdb -> get_col( $sql );

                // For each transient...
                foreach( $transients as $transient ) {

                        // Strip away the WordPress prefix in order to arrive at the transient key.
                        $key = str_replace( '_transient_timeout_', '', $transient );

                        // Now that we have the key, use WordPress core to the delete the transient.
                        delete_transient( $key );

                }

                // But guess what?  Sometimes transients are not in the DB, so we have to do this too:
                wp_cache_flush();

        }


        /**
         * Creates or returns an instance of this class.
         *
         * @return   A single instance of this class.
         */
        public static function get_instance() {

                if ( null == self::$instance ) {
                        self::$instance = new self;
                }

                return self::$instance;

    }		
}  

/**
 * Init Upgrade User Capability class
 */
 
UUC::get_instance();

register_deactivation_hook( __FILE__, array( 'UUC', 'do_on_deactivation' ) );