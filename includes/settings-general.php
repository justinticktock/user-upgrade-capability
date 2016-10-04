<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Append new links to the Plugin admin side
add_filter( 'plugin_action_links_' . UUC::get_instance()->plugin_file , 'uuc_plugin_action_links');

function uuc_plugin_action_links( $links ) {
	$upgrade_user = UUC::get_instance();
	
	$settings_link = '<a href="users.php?page=' . $upgrade_user->menu . '">' . __( 'Settings', 'user-upgrade-capability' ) . "</a>";
	array_push( $links, $settings_link );
	return $links;	
}


// add action after the settings save hook.
add_action( 'tabbed_settings_after_update', 'uuc_after_settings_update' );

function uuc_after_settings_update( ) {

	flush_rewrite_rules();	
	
}



/**
 * UUC_General_Settings class.
 *
 * Main Class which inits the CPTs and plugin
 */
class UUC_General_Settings {
	
	// Refers to a single instance of this class.
    private static $instance = null;
	
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	private function __construct() {
	}

	/**
	 * Called during admin_menu, adds rendered using the plugin_options_page method.
	 *
	 * @access public
	 * @return void
	 */
	public function add_admin_menus() {

		add_submenu_page( 'users.php', __( 'User Upgrade Capability', 'user-upgrade-capability' ), __( 'Upgrade Capability', 'user-upgrade-capability' ), 'manage_network_users', UUC_General_Settings::get_instance()->menu . 'general', array( $this, 'plugin_options_page' ) );	
	}
	
	/**
     * Creates or returns an instance of this class.
     *
     * @return   A single instance of this class.
     */
    public static function get_instance() {

            $upgrade_user = UUC::get_instance();

            $config = array(
                        'default_tab_key' => 'uuc_general',					// Default settings tab, opened on first settings page open.
                        'menu_parent' => 'users.php',    					// menu options page slug name.
                        'menu_access_capability' => 'manage_options', 			// menu options page slug name.
                        'menu' => $upgrade_user->menu,    					// menu options page slug name.
                        'menu_title' => $upgrade_user->menu_title,    		// menu options page slug name.
                        'page_title' => $upgrade_user->page_title,    		// menu options page title.
                        );

            $settings = apply_filters( 'UUC_General_Settings',
                    array(
                            'uuc_general' => array(
                                    'access_capability' => 'manage_network_users',
                                    'title' 		=> __( 'General', 'user-upgrade-capability' ),
                                    'description' 	=> __( 'The "User Upgrade Capability" plugin simplifies the administration of Networks for user access '
                                            . 'and only requires the user roles and capabilties to be maintained on one reference site.  '
                                            . 'All linked sites pointing back to the primary reference site (and user base) will automatically '
                                            . 'have user access granted or removed simply through changes to the user Roles&Capabilties on the reference site. '
                                            . 'It effectively allows a primary site/blog to be extended to one (or more) linked sites.', 'user-upgrade-capability' ),
                                    'settings' 		=> array(		
                                                                array(
                                                                       'name' 		=> 'uuc_reference_site',
                                                                       'std' 		=> '0',
                                                                       'label' 	=> __( 'Reference Site', 'user-upgrade-capability' ),
                                                                       'desc'		=> __( 'WARNING !! changing this setting will delete and replace this site user-list and available roles/capabilities, YOU CANNOT UNDO!! '
                                                                                               . 'Only change this option on new sites or when you wish to do so.  (If left as "-none-" this plugin will be disabled).  '
                                                                                               . 'Once you make a selection here the reference site will determine the users availble to enter this site, you will also get '
                                                                                               . 'new menus ( "Upgrade Roles" & "Upgrade Caps" ).  These settings pages allow you to configure how users of the reference site '
                                                                                               . 'can gain access to this site and also the roles or capabilities that they will be granted on this site. ', 'user-upgrade-capability' ),
                                                                       'type'          => 'field_site_list_option',
                                                                       ),	
                                                               array(
                                                                       'name' 		=> 'uuc_delay_check',
                                                                       'std' 		=> 15,
                                                                       'label' 	=> __( 'Delay Time (mins) Between Capability Overwrites', 'user-upgrade-capability' ),
                                                                       'desc'		=> __( 'Use this setting to delay overwrites of user capabilities, this will help page load times. '
                                                                                           . ' ( 15 mins is a suggested minimum and if used the local user access will reflect user capabilty changes on the reference '
                                                                                           . ' site within a 15 min time frame. If user role or capability changes are infrequent on the reference '
                                                                                           . 'site then set for a longer time delay.)', 'user-upgrade-capability' ),
                                                                       'type'          => 'field_default_option',
                                                                       'sanitize_callback' => "intval",
                                                                       ),											
                                                           ),										
                            ),      
                            'uuc_plugin_extension' => array(
                                            'access_capability' => 'install_plugins',
                                            'title' 		=> __( 'Plugin Suggestions', 'user-upgrade-capability' ),
                                            'description' 	=> __( 'Most Plugins listed here are intended for use with the "User Upgrade Capability" plugin.  Selection of a plugin will prompt you through the installation and the plugin will be forced network active while this is selected; deselecting will not remove the plugin, you will need to manually deactiate and uninstall from the network.', 'user-upgrade-capability' ),					
                                            'settings' 		=> array(
                                                                        array(
                                                                                'name' 		=> 'uuc_user_role_editor_plugin',
                                                                                'std' 		=> false,
                                                                                'label' 	=> 'User Role Editor',
                                                                                'desc'		=> __( "This plugin gives the ability to review the users capabilities granted by 'User Upgrade Capability'.  Once installed go to menu [users]..[User Role Editor].", 'user-upgrade-capability' ),
                                                                                'type'      => 'field_plugin_checkbox_option',
                                                                                // the following are for tgmpa_register activation of the plugin
                                                                                'slug'      			=> 'user-role-editor',
                                                                                'plugin_dir'			=> UUC_PLUGIN_DIR,
                                                                                'required'              => false,
                                                                                'force_deactivation' 	=> false,
                                                                                'force_activation'      => true,		
                                                                                ),							
                                                                        array(
                                                                                'name' 		=> 'uuc_blog_copier_plugin',
                                                                                'std' 		=> false,
                                                                                'label' 	=> 'Blog Copier',
                                                                                'desc'		=> __( "This is optional and is very helpful in the creation of new sites by simply copying an existing site, you can then create template sites for re-use.  Once installed go to the main Network..[sites]...[Blog Copier]", 'user-upgrade-capability' ),
                                                                                'type'      => 'field_plugin_checkbox_option',
                                                                                // the following are for tgmpa_register activation of the plugin
                                                                                'slug'      			=> 'blog-copier',
                                                                                'plugin_dir'			=> UUC_PLUGIN_DIR,
                                                                                'required'              => false,
                                                                                'force_deactivation' 	=> false,
                                                                                'force_activation'      => true,		
                                                                                ),
                                                                        ),
                                    )
                        )
                    );



							
        if ( null == self::$instance ) {
            self::$instance = new Tabbed_Settings( $settings, $config );
        }

        return self::$instance;
 
    } 
}

/**
 * UUC_General_Settings_Additional_Methods class.
 */
class UUC_General_Settings_Additional_Methods {

	/**
	 * field_site_list_option 
	 *
	 * @param array of arguments to pass the option name to render the form field.
	 * @access public
	 * @return void
	 */
	public function field_site_list_option( array $args ) {

		global $wpdb;

		$defaults = array(
					'name' 		=> '',
					);
			
		$option = wp_parse_args( $args['option'], $defaults );
		$value = get_option( $option['name'] );

		$blogs = $wpdb->get_results("
			SELECT blog_id
			FROM {$wpdb->blogs}
			WHERE site_id = '{$wpdb->siteid}'
			AND spam = '0'
			AND deleted = '0'
			AND archived = '0'
		");

		$sites = array();
		$sites[0] = _x( "- None -", 'text for no site selected', 'user-upgrade-capability' );
		foreach ($blogs as $blog) {
			$sites[$blog->blog_id] = get_blog_option($blog->blog_id, 'blogname');
		}
		natsort($sites);
     
                // remove the option to linked back to the current site to stop it being selected
                unset( $sites[ get_current_blog_id( ) ] );

		?>
		<form action="<?php site_url(); ?>" method="get">
			<select id="setting-<?php echo esc_html( $option['name'] ); ?>" class="regular-text" name="<?php echo esc_html( $option['name'] ); ?>">
											<?php foreach( $sites as $blog_id=>$site ) { ?>
												<option value="<?php echo $blog_id; ?>" <?php selected( $blog_id, $value ); ?>><?php echo $site; ?></option>
											<?php } ?>
			</select>
				<class="description"> ( blog_id:  <?php echo get_option( 'uuc_reference_site' )?> )
		</form>

		<?php
		if ( ! empty( $option['desc'] ) )
			echo ' <p class="description">' . esc_html( $option['desc'] ) . '</p>';		
	}	
}


// Include the Tabbed_Settings class.

require_once( dirname( __FILE__ ) . '/class-tabbed-settings.php' );

// Create new tabbed settings object for this plugin..
// and Include additional functions that are required.
UUC_General_Settings::get_instance()->registerHandler( new UUC_General_Settings_Additional_Methods() );


?>