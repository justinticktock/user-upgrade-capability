<?php

/**
 * UUC_Upgrade_caps class.
 *
 * Main Class which inits the CPTs and plugin
 */
class UUC_Upgrade_caps {
	
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

		//add_submenu_page( 'users.php', __( 'User Upgrade Capabilities', 'user-upgrade-capability' ), __( 'Upgrade Capability', 'user-upgrade-capability' ), 'manage_network_users', UUC_Upgrade_caps::get_instance()->menu . 'general', array( $this, 'plugin_options_page' ) );	
	}
	
	/**
     * Creates or returns an instance of this class.
     *
     * @return   A single instance of this class.
     */
    public static function get_instance() {

    $upgrade_user = UUC::get_instance();
    $blogId = get_current_blog_id();
    $current_site_name = get_blog_details( $blogId )->path; 

    $config = array(
                    //'default_tab_key' => 'uuc_general',		// Default settings tab, opened on first settings page open.
                    'menu_parent' => 'users.php',    		// menu options page slug name.
                    'menu_access_capability' => 'manage_options', 	// menu options page slug name.
                    'menu' => $upgrade_user->menu . '-caps',    	// menu options page slug name.
                    'menu_title' => 'Upgrade Caps',    		// menu options page slug name.
                    'page_title' => $upgrade_user->page_title,    	// menu options page title.
                    );

    $settings = apply_filters( 'UUC_Upgrade_Caps',  
                    array(                      
                        'uuc_key_caps' => array(
                                'access_capability'     => 'manage_options',
                                'title'                 => __( 'Key Capabilities :', 'user-upgrade-capability' ),
                                'description'           => __( "Select key capabilities, users who have the same capabilities on the primary reference site will have access to this site.", 'user-upgrade-capability' ),												
                                'settings'              => array(														
                                                                array(
                                                                        'name'  => 'uuc_key_caps',
                                                                        'std'   => false,
                                                                        'label' => __( 'Add Key Capability(s)', 'user-upgrade-capability' ),
                                                                        'desc'  => __( 'Enable Key Capabilities to open new tabs from which you can upgrade the user access to this local site.', 'user-upgrade-capability' ),
                                                                        'type'  => 'uuc_field_capabilties_list_checkbox',
                                                                        ),					
                                                                ),
                                    ),                                                                                                                                           
                        )
            );


        // generate a new tab for each CAP selected
        $uuc_key_caps = array_filter( ( array ) get_option( 'uuc_key_caps' ) );
        $new_settings = array();

        foreach( $uuc_key_caps as $capability )
        {
                $new_settings[$capability . '_key_cap']  = array(
                                                                //'access_capability' => 'delete_users',
                                                                'title' 		=> __( $capability , 'user-upgrade-capability' ),
                                                                'description'           => sprintf( __( 'Users that have  the "%1$s" capability on the reference site will have the additional capabilities ticked below for this site ( %2$s ).', 'user-upgrade-capability' ), $capability, $current_site_name),                    
																// 'form_action'   => admin_url( 'admin-post.php' ),
                                                                'settings' 		=> array(		
                                                                                            array(
                                                                                                    'name'      => 'uuc_key_cap_' . $capability, //$capability, 
                                                                                                    'std'       => array( $capability ),  // starting default is to give the KeyCap (not working right now)
                                                                                                    'label' 	=> __( 'Grant new Capabilities:', 'user-upgrade-capability' ),
                                                                                                    'desc'	=> __( 'Select Capabilities.', 'user-upgrade-capability' ),
                                                                                                    'type'      => 'uuc_field_capabilties_list_checkbox' 
                                                                                                    ),					
                                                                                            ),			
                                                                );
        }
        
        $new_settings = array_merge ( (array) $settings, (array) $new_settings );


        if ( null == self::$instance ) {
            self::$instance = new Tabbed_Settings( $new_settings, $config );
        }

        return self::$instance;
 
    } 
}

/**
 * UUC_Upgrade_caps_Additional_Methods class.
 */
class UUC_Upgrade_caps_Additional_Methods {

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
                                    'std'           => array(),
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

            ?>
            <form action="<?php site_url(); ?>" method="get">
                    <select id="setting-<?php echo esc_html( $option['name'] ); ?>" class="regular-text" name="<?php echo esc_html( $option['name'] ); ?>">
                                                                                    <?php foreach( $sites as $blog_id=>$site ) { ?>
                                                                                            <option value="<?php echo $blog_id; ?>" <?php selected( $blog_id, $value ); ?>><?php echo $site; ?></option>
                                                                                    <?php } ?>		
                    </select>
                            <class="description"> ( blog_id:  <?php echo get_option('uuc_reference_site')?> )
            </form>


            <?php

            if ( ! empty( $option['desc'] ))
                    echo ' <p class="description">' . esc_html( $option['desc'] ) . '</p>';		
    }

    /**
     * field_roles_checkbox 
     *
     * @param array of arguments to pass the option name to render the form field.
     * @return void
     */
    public function uuc_field_capabilties_list_checkbox( array $args  ) {

            $option   = $args['option'];
            
            $primary_ref_site = get_option( 'uuc_reference_site' );

            if ( ! empty( $primary_ref_site ) && is_multisite() && ( get_current_blog_id() != $primary_ref_site ) ) {

                    switch_to_blog( $primary_ref_site );

                    global $wp_roles;

                    if ( ! isset( $wp_roles ) ) {
                            $wp_roles = new WP_Roles( );
                    }


                    $capabilities = array();

                    /* Loop through each role object because we need to get the caps. */
                    foreach ( $wp_roles->role_objects as $key => $role ) {

                            /* Roles without capabilities will cause an error, so we need to check if $role->capabilities is an array. */
                            if ( is_array( $role->capabilities ) ) {

                                    /* Loop through the role's capabilities and add them to the $capabilities array. */
                                    foreach ( $role->capabilities as $cap => $grant )
                                            $capabilities[$cap] = $cap;
                            }
                    }

                    restore_current_blog();
                    
                    /* Return the capabilities array, making sure there are no duplicates. */
                    $capabilities = array_unique( $capabilities );
                    asort( $capabilities );


                    ?><ul><?php                                          
                    foreach( $capabilities as $capability )
                    {
                    $role = get_role( $capability );

                            $id = sanitize_key( $capability );
                            $value = ( array ) get_option( $option['name'] );

                            // Render the output  
                            ?> 
                            <li><label>
                            <input type='checkbox'  
                                    id="<?php echo esc_html( "exclude_enabled_{$id}" ) ; ?>" 
                                    name="<?php echo esc_html( $option['name'] ); ?>[]"
                                    value="<?php echo esc_attr( $capability )	; ?>"<?php checked( in_array( $capability, $value ) ) ;?>

                            >
                            <?php echo esc_html( $capability ) ; ?>	

                            <?php  if ( in_array( $capability, UUC::wp_capabilities() ) ) {
                                echo ' ( <a href="' .  esc_url( 'http://codex.wordpress.org/Roles_and_Capabilities#' . $capability )  . '">' .  esc_html__( "WP Codex", 'capabiltiy_includer' ) . ' )</a> ';
                            }?>	
                            <?php echo " <br/>" ; ?>	
                            </label></li>
                            <?php 
                    }?></ul><?php 
                    if ( ! empty( $option['desc'] ) ) {
                            echo ' <p class="description">' . $option['desc'] . '</p>';
                    }
            }
    }
}

// Include the Tabbed_Settings class.
require_once( dirname( __FILE__ ) . '/class-tabbed-settings.php' );

// Create new tabbed settings object for this plugin..
// and Include additional functions that are required.
UUC_Upgrade_caps::get_instance()->registerHandler( new UUC_Upgrade_caps_Additional_Methods() );


?>