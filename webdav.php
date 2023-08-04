<?php
/**
 * Extend the HestiaCP Pluginable object with our WebDAV object for
 * providing a WebDAV server for each HestiaCP user account; enabling
 * users to access their web folder for all their domains.
 * 
 * @version 1.0.0
 * @license GPL-3.0
 * @link https://github.com/virtuosoft-dev/hcpp-webdav
 * 
 */

if ( ! class_exists( 'WebDAV') ) {
    class WebDAV {
        
        /**
         * Constructor, listen for add, update, or remove users
         */
        public function __construct() {
            global $hcpp;
            $hcpp->webdav = $this;
            $hcpp->add_action( 'priv_unsuspend_domain', [ $this, 'priv_unsuspend_domain' ] );
            $hcpp->add_action( 'hcpp_new_domain_ready', [ $this, 'hcpp_new_domain_ready' ] );
            $hcpp->add_action( 'priv_delete_user', [ $this, 'priv_delete_user' ] );
        }
        
        // Trigger setup when domain is created.
        public function hcpp_new_domain_ready( $args ) {
            $user = $args[0];
            $this->setup( $user );           
            return $args;
        }

        // On domain unsuspend, re-run setup
        public function priv_unsuspend_domain( $args ) {
            $user = $args[0];
            $this->setup( $user );           
            return $args;
        }
        
        // Setup WebDAV services for user
        public function setup( $user ) {
            global $hcpp;
            $hcpp->log( "Setting up WebDAV for $user" );
            $domain = trim( shell_exec( 'hostname -d') );

            // Create the configuration folder
            if ( ! is_dir( "/home/$user/conf/web/webdav-$user.$domain" ) ) {
                mkdir( "/home/$user/conf/web/webdav-$user.$domain" );
            }

            // Get user account first IP address.
            $ip = array_key_first(
                json_decode( shell_exec( '/usr/local/hestia/bin/v-list-user-ips ' . $user . ' json' ), true ) 
            );

            // Create the nginx.conf file.
            $conf = "/home/$user/conf/web/webdav-$user.$domain/nginx.conf";
            $content = file_get_contents( __DIR__ . '/conf-web/nginx.conf' );
            $content = str_replace( 
                ['%ip%', '%user%', '%domain%'],
                [$ip, $user, $domain],
                $content
            );
            file_put_contents( $conf, $content );

            // Create the nginx.ssl.conf file.
            if ( property_exists( $hcpp, 'cg_pws' ) ) {
                $ssl_conf = "/home/$user/conf/web/webdav-$user.$domain/nginx.ssl.conf";
                $content = file_get_contents( __DIR__ . '/conf-web/nginx.ssl.conf' );
                $content = str_replace( 
                    ['%ip%', '%user%', '%domain%'],
                    [$ip, $user, $domain],
                    $content
                );
                file_put_contents( $ssl_conf, $content );

                // Generate website cert if it doesn't exist.
                if ( !is_dir( "/home/$user/conf/web/webdav-$user.$domain/ssl" ) ) {
                    $hcpp->cg_pws->generate_website_cert( $user, ["webdav-$user.$domain"] );
                }
            }

            // Create the apache.conf file.
            $apache_conf = "/home/$user/conf/web/webdav-$user.$domain/apache2.conf";
            $content = file_get_contents( __DIR__ . '/conf-web/apache.conf' );
            $content = str_replace(
                ['%ip%', '%user%', '%domain%'],
                [$ip, $user, $domain],
                $content
            );
            file_put_contents( $apache_conf, $content );

            // Create the nginx.conf configuration symbolic links.
            $link = "/etc/nginx/conf.d/domains/webdav-$user.$domain.conf";
            if ( ! is_link( $link ) ) {
                symlink( $conf, $link );
            }

            // Create the nginx.ssl.conf configuration symbolic links.
            if ( property_exists( $hcpp, 'cg_pws' ) ) {
                $link = "/etc/nginx/conf.d/domains/webdav-$user.$domain.ssl.conf";
                if ( ! is_link( $link ) ) {
                    symlink( $ssl_conf, $link );
                }
            }

            // Create the apache.conf configuration symbolic links.
            $link = "/etc/apache2/conf.d/domains/webdav-$user.$domain.conf";
            if ( ! is_link( $link ) ) {
                symlink( $apache_conf, $link );
            }
        }

        // Delete the NGINX configuration reference and server when the user is deleted.
        public function priv_delete_user( $args ) {
            global $hcpp;
            $user = $args[0];
            $hostname = $hcpp->delLeftMost( $hcpp->getLeftMost( $_SERVER['HTTP_HOST'], ':' ), '.' );
            $link = "/etc/nginx/conf.d/domains/webdav-$user.$hostname.conf";
            if ( is_link( $link ) ) {
                unlink( $link );
            }
            $link = "/etc/nginx/conf.d/domains/webdav-$user.$hostname.ssl.conf";
            if ( is_link( $link ) ) {
                unlink( $link );
            }
            $link = "/etc/apache2/conf.d/domains/webdav-$user.$hostname.conf";
            if ( is_link( $link ) ) {
                unlink( $link );
            }
            return $args;
        }
        
    }
    new WebDAV();
}