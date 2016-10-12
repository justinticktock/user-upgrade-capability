<?php

/**
 * UUC_Upgrade_Roles class.
 *
 * Main Class which inits the CPTs and plugin
 */
class UUC_Upgrade_Roles {
	
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

		//add_submenu_page( 'users.php', __( 'Upgrade User Roles', 'user-upgrade-capability' ), __( 'Upgrade Capability', 'user-upgrade-capability' ), 'manage_network_users', UUC_Upgrade_Roles::get_instance()->menu . 'role', array( $this, 'plugin_options_page' ) );	
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
				'menu' => $upgrade_user->menu . '-roles',    	// menu options page slug name.
				'menu_title' => 'Upgrade Roles',    		// menu options page slug name.
				'page_title' => $upgrade_user->page_title,    	// menu options page title.
				);

		$settings = 	apply_filters( 'UUC_Upgrade_Roles',
			array(
                                'uuc_key_roles' => array(
                                        'access_capability' => 'promote_users',
                                        'title' 		=> __( 'Key Roles :','user-upgrade-capability' ),
                                        'description' 	=> __( 'Select key roles, users who have the same role(s) on the primary reference site will have access to this site.','user-upgrade-capability' ),
                                        'settings' 		=> array(														
                                                                array(
                                                                        'name' 		=> 'uuc_key_roles',
                                                                        'std' 		=> false,
                                                                        'label' 	=> __( 'Key Role(s)','user-upgrade-capability' ),
                                                                        'desc'		=> __( 'Enable Key Roles to open new tabs from which you can upgrade the user access to this local site.', 'user-upgrade-capability' ),
                                                                        'type'          => 'ref_site_field_roles_checkbox',
                                                                        ),					
                                                                ),			
                                ), 
                            )
			);
		

                // generate a new tab for each role selected
                $uuc_key_roles = array_filter( ( array ) get_option( 'uuc_key_roles' ) );
                $new_settings = array();

                foreach( $uuc_key_roles as $role )
                {

                        $new_settings[$role . '_key_role']  = array(
                                                                    //'access_capability' => 'delete_users',
                                                                    'title' 		=> __( $role , 'user-upgrade-capability' ),
                                                                    'description' 	=> sprintf( __( 'Users that have  the "%1$s" role on the reference site will have the additional roles ticked below for this site ( %2$s ).', 'user-upgrade-capability' ), $role, $current_site_name),
                                                                   // 'form_action'   => admin_url( 'admin-post.php' ),
                                                                    'settings' 		=> array(														

                                                                                                array(
                                                                                                        'name' 		=> 'uuc_key_role_' . $role, 
                                                                                                        'std' 		=> false,
                                                                                                        'label' 	=> __(  'Grant new Roles:', 'user-upgrade-capability' ),
                                                                                                        'desc'		=> __( 'Select Roles.' , 'user-upgrade-capability' ),
                                                                                                        'type'      => 'ref_site_field_roles_checkbox',
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
 * UUC_Upgrade_Roles_Additional_Methods class.
 */
class UUC_Upgrade_Roles_Additional_Methods {
    
        /**
         * ref_site_field_roles_checkbox 
         *
         * @param array of arguments to pass the option name to render the form field.
         * @return void
         */
        public function ref_site_field_roles_checkbox( array $args  ) {

                $option   = $args[ 'option' ];

		$primary_ref_site = get_option( 'uuc_reference_site' );

		if ( ! empty( $primary_ref_site ) && is_multisite() && ( get_current_blog_id() != $primary_ref_site ) ) {
		
			switch_to_blog( $primary_ref_site );

                        //  loop through the site roles and create a custom post for each
                        global $wp_roles;

                        if ( ! isset( $wp_roles ) ) {
                                $wp_roles = new WP_Roles( );
                        }

                        $roles = $wp_roles->get_names( );                   
                        unset( $wp_roles );
                        
                        restore_current_blog();
                        
                        ?><ul><?php 
                        asort( $roles );


                        foreach( $roles as $role_key=>$role_name )
                        {
                                $id = sanitize_key( $role_key );
                                $value = ( array ) get_option( $option['name'] );

                                // Render the output  
                                ?> 
                                <li><label>
                                <input type='checkbox'  
                                        id="<?php echo esc_html( "exclude_enabled_{$id}" ) ; ?>" 
                                        name="<?php echo esc_html( $option['name'] ); ?>[]"
                                        value="<?php echo esc_attr( $role_key )	; ?>"<?php checked( in_array( $role_key, $value ) ); ?>
                                >
                                <?php echo esc_html( $role_name ) . " <br/>"; ?>	
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
UUC_Upgrade_Roles::get_instance()->registerHandler( new UUC_Upgrade_Roles_Additional_Methods() );


?>