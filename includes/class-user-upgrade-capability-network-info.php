<?php

 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


class UUCNI {

	// Refers to a single instance of this class.
        private static $instance = null;

        const use_transient = true;


            /**
             * __construct function.
             *
             * @access public
             * @return void
             */
            private function __construct() {

                    add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );

            }


        public function plugins_loaded( ) {

                // On the blog list page, show the linked user base.
                add_filter( 'manage_sites-network_columns', array( $this, 'add_sites_column' ), 9, 1 );
                add_action( 'manage_sites_custom_column', array( $this, 'manage_sites_custom_column' ), 10, 2 );

                // Clear the transients on action..
                add_action( 'tabbed_settings_after_update', array( $this, 'tabbed_settings_after_update' ) );

        }


        public function manage_sites_custom_column( $column_name, $blog_id ) {

                    $plugin_file = UUC::get_instance()->plugin_file;

            if ( $column_name != 'active_uuc_blogs' ) {
                return;
            }


                    // Is this plugin Active on any blogs in this network?
                    $active_on_blogs = self::is_plugin_active_on_blogs( $plugin_file );


                    if ( is_array( $active_on_blogs ) ) {
                            $output = '<ul>';

                            // Loop through the blog list, gather details and append them to the output string
                            foreach ( $active_on_blogs as $active_on_blog_id ) {
                                    $active_on_blog_id = trim( $active_on_blog_id );
                                    if ( ! isset( $active_on_blog_id ) || $active_on_blog_id == '' ) {
                                            continue;
                                    }

                                    // Collect the reference site.
                                    switch_to_blog( $active_on_blog_id );
                                    $ref_site = get_option('uuc_reference_site');
                                    restore_current_blog();

                                    if (  ( $ref_site != 0 ) && ( $ref_site == $blog_id ) ) {

                                            $blog_details = get_blog_details( $active_on_blog_id, true );

                                            if ( isset( $blog_details->siteurl ) && isset( $blog_details->blogname ) ) {
                                                    $blog_url  = $blog_details->siteurl;
                                                    $blog_name = $blog_details->blogname;

                                                    $output .= '<li><nobr><a title="' . esc_attr( __( 'Manage User Upgrade Capability on', 'user-upgrade-capability' ) . " " . $blog_name ) .'" href="'.esc_url( $blog_url ).'/wp-admin/users.php?page=uuc-settings">' . esc_html( $blog_name ) . '</a></nobr></li>';
                                            }

                                            unset( $blog_details );
                                    }
                            }
                            $output .= '</ul>';
                    }
            echo $output;
        }


        /* Sites Page Functions *******************************************************/

        public function add_sites_column( $column_details ) {
            $column_details['active_uuc_blogs'] = __( 'UUC Linked Sites', 'user-upgrade-capability' ) . ' <span title="' . esc_attr( __( 'User Upgrade Capability - Linked Secondary Sites', 'user-upgrade-capability' ) ) . '"></span>';

            return $column_details;
        }


        /* Helper Functions ***********************************************************/

        // Get the database prefix
        static function get_blog_prefix( $blog_id=null ) {

            global $wpdb;

            if ( null === $blog_id ) {
                $blog_id = $wpdb->blogid;
            }
            $blog_id = (int) $blog_id;

            if ( defined( 'MULTISITE' ) && ( 0 == $blog_id || 1 == $blog_id ) ) {
                return $wpdb->base_prefix;
            } else {
                return $wpdb->base_prefix . $blog_id . '_';
            }
        }

        // Get the list of blogs
        static function get_network_blog_list( ) {

            global $wpdb;

                    // Fetch the list from the transient cache if available
                    $blog_list = get_site_transient( 'uuc_blog_list' );
                    if ( self::use_transient !== true || $blog_list === false ) {

                            $blog_list = $wpdb->get_results( "SELECT blog_id, domain FROM " . $wpdb->base_prefix . "blogs", ARRAY_A );

                            // Store for one hour
                            set_site_transient( 'uuc_blog_list', $blog_list, 3600 );
                    }

            //error_log( print_r( $blog_list, true ) );
            return $blog_list;
        }

        /* Plugin Helpers */

        // Determine if the given plugin is active on a list of blogs
        static function is_plugin_active_on_blogs( $plugin_file ) {

                // Is this plugin network activated
                if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
                    require_once ABSPATH . '/wp-admin/includes/plugin.php';
                }
                $active_on_network = is_plugin_active_for_network( $plugin_file );


                // Get the list of blogs
                $blog_list = self::get_network_blog_list( );


                if ( isset( $blog_list ) && $blog_list != false ) {
                        // Fetch the list from the transient cache if available
                        $uuc_active_plugins = get_site_transient( 'uuc_active_plugins' );

                        if ( ! is_array( $uuc_active_plugins ) ) {
                            $uuc_active_plugins = array();
                        }
                        $transient_name = self::get_transient_friendly_name( $plugin_file );

                        if ( self::use_transient !== true || ! array_key_exists( $transient_name, $uuc_active_plugins ) ) {
                            // We're either not using or don't have the transient index
                            $active_on = array();

                            // Gather the list of blogs this plugin is active on
                            foreach ( $blog_list as $blog ) {
                                // If the plugin is active here then add it to the list
                                if ( $active_on_network || self::is_plugin_active( $blog['blog_id'], $plugin_file ) ) {
                                    array_push( $active_on, $blog['blog_id'] );
                                }
                            }

                            // Store our list of blogs
                            $uuc_active_plugins[$transient_name] = $active_on;

                            // Store for one hour
                            set_site_transient( 'uuc_active_plugins', $uuc_active_plugins, 3600 );

                            return $active_on;

                        } else {
                            // The transient index is available, return it.
                            $active_on = $uuc_active_plugins[$transient_name];

                            return $active_on;
                        }
                }

                return false;
        }

        // Given a blog id and plugin path, determine if that plugin is active.
        static function is_plugin_active( $blog_id, $plugin_file ) {
            // Get the active plugins for this blog_id
            $plugins_active_here = self::get_active_plugins( $blog_id );

            // Is this plugin listed in the active blogs?
            if ( isset( $plugins_active_here ) && strpos( $plugins_active_here, $plugin_file ) > 0 ) {
                return true;
            } else {
                return false;
            }
        }

        // Get the list of active plugins for a single blog
        static function get_active_plugins( $blog_id ) {

            global $wpdb;

            $blog_prefix = self::get_blog_prefix( $blog_id );

            $active_plugins = $wpdb->get_var( "SELECT option_value FROM " . $blog_prefix . "options WHERE option_name = 'active_plugins'" );

            return $active_plugins;
        }

        // Given a blog id and theme object, determine if that theme is used on a this blog.
        static function is_theme_active( $blog_id, $theme_key ) {
            // Get the active theme for this blog_id
            $active_theme = self::get_active_theme( $blog_id );

            // Is this theme listed in the active blogs?
            if ( isset( $active_theme ) && ( $active_theme == $theme_key ) ) {
                return true;
            } else {
                return false;
            }
        }

        // Get the active theme for a single blog
        static function get_active_theme( $blog_id ) {

            global $wpdb;

            $blog_prefix = self::get_blog_prefix( $blog_id );

            $active_theme = $wpdb->get_var( "SELECT option_value FROM " . $blog_prefix . "options WHERE option_name = 'stylesheet'" );

            return $active_theme;
        }

        static function get_transient_friendly_name( $file_name ) {
            $transient_name = substr( $file_name, 0, strpos( $file_name, '/' ) );
            if ( $transient_name == false ) {
                $transient_name = $file_name;
            }
            if ( strlen( $transient_name ) >= 45 ) {
                $transient_name = substr( $transient_name, 0, 44 );
            }
            return esc_sql( $transient_name );
        }


        public function tabbed_settings_after_update( ) {

                    delete_site_transient( 'uuc_blog_list' );

            return;
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

UUCNI::get_instance();
