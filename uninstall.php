<?php

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();
	
if ( is_multisite( ) ) {

    $blogs = wp_list_pluck( wp_get_sites(), 'blog_id' );

    if ( $blogs ) {
        foreach( $blogs as $blog ) {		
            switch_to_blog( $blog );
            uuc_clean_database();
            uuc_role_upgrade_option_clean_up();
            uuc_caps_upgrade_option_clean_up();

        }
        restore_current_blog();
    }
} else {
	uuc_clean_database();
    uuc_role_upgrade_option_clean_up();
	uuc_caps_upgrade_option_clean_up();
		
}

// remove all network-wide transients
delete_site_transient( 'uuc_blog_list' );
delete_site_transient( 'uuc_active_plugins' );
	
// remove all database entries for currently active blog on uninstall.
function uuc_clean_database() {
		
		delete_option( 'uuc_plugin_version' );
		delete_option( 'uuc_reference_site' );
		delete_option( 'uuc_delay_check' );
                
		delete_option( 'uuc_key_caps' );                
		delete_option( 'uuc_key_roles' );     		
		delete_option( 'uuc_key_cap_' );

		delete_option( 'uuc_install_date' );
		
		// plugin specific database entries
		delete_option( 'uuc_user-role-editor_plugin' );
		delete_option( 'uuc_blog-copier_plugin' );
		
		delete_option( 'uuc_deactivate_user-role-editor' );
		delete_option( 'uuc_deactivate_blog-copier' );

		// user specific database entries
		delete_user_meta( get_current_user_id(), 'uuc_prompt_timeout', $meta_value );
		delete_user_meta( get_current_user_id(), 'uuc_start_date', $meta_value );
		delete_user_meta( get_current_user_id(), 'uuc_hide_notice', $meta_value );

}

// remove stored role upgrade options on uninstall.
function uuc_role_upgrade_option_clean_up( ) {

	global $wp_roles;
 
    if ( ! isset( $wp_roles ) ) {
        $wp_roles = new WP_Roles( );
    }        
    $roles = $wp_roles->get_names( );

    foreach( array_keys( $roles ) as $role_key ) {
        delete_option( 'uuc_key_role_' . $role_key );
    }

}

function uuc_caps_upgrade_option_clean_up( ) {
	
	$option_names = array_keys( wp_load_alloptions( ) );
	$option_uuc_names = preg_grep('!^uuc_key_cap_!', $option_names);
	/* Loop through each caps and remove any associated option 	*/
	foreach ( $option_uuc_names as $name ) {
		delete_option( $name );
	}
}