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
            $hcpp->add_action( 'new_web_domain_ready', [ $this, 'new_web_domain_ready' ] );
            $hcpp->add_action( 'priv_delete_user', [ $this, 'priv_delete_user' ] );
        }
        
        // Trigger setup when domain is created.
        public function new_web_domain_ready( $args ) {
            global $hcpp;
            $user = $args[0];
            $this->setup( $user );           
            return $args;
        }

        // On domain unsuspend, re-run setup
        public function priv_unsuspend_domain( $args ) {
            global $hcpp;
            $user = $args[0];
            $this->setup( $user );           
            return $args;
        }
        
        // Setup WebDAV services for user
        public function setup( $user ) {
            global $hcpp;
            $hostname = trim( $hcpp->delLeftMost( shell_exec( 'hostname -f' ), '.' ) );
            $conf = "/home/$user/conf/web/webdav-$user.$hostname/nginx.conf";

            // Create the configuration folder
            if ( ! is_dir( "/home/$user/conf/web/webdav-$user.$hostname" ) ) {
                mkdir( "/home/$user/conf/web/webdav-$user.$hostname" );
            }

            // Get user account first IP address.
            $ip = array_key_first(
                json_decode( shell_exec( '/usr/local/hestia/bin/v-list-user-ips ' . $user . ' json' ), true ) 
            );

            // Create the nginx.conf file.
            $content = file_get_contents( __DIR__ . '/conf-web/nginx.conf' );
            $content = str_replace( 
                ['%ip%', '%user%', '%hostname%'],
                [$ip, $user, $hostname],
                $content
            );
            file_put_contents( $conf, $content );

            // Create the NGINX configuration symbolic link.
            $link = "/etc/nginx/conf.d/domains/webdav-$user.$hostname.conf";
            if ( ! is_link( $link ) ) {
                symlink( "/home/$user/conf/web/vscode-$user.$hostname/nginx.conf", $link );
            }

            // Start the VSCode Server instance
            $cmd = "runuser -l $user -c \"cd /opt/vscode && source /opt/nvm/nvm.sh ; pm2 pid vscode-$user.$hostname\"";
            if ( trim( shell_exec( $cmd ) ) === '' ) {
                $cmd = "runuser -l $user -c \"cd /opt/vscode && source /opt/nvm/nvm.sh ; pm2 start vscode.config.js\"";
                $hcpp->log( shell_exec( $cmd ) );
            }else{
                $this->update_token( $user );
            }
        }


        // Delete the NGINX configuration reference and server when the user is deleted.
        public function priv_delete_user( $args ) {
            global $hcpp;
            $user = $args[0];
            $hostname = trim( $hcpp->delLeftMost( shell_exec( 'hostname -f' ), '.' ) );
            $link = "/etc/nginx/conf.d/domains/vscode-$user.$hostname.conf";
            if ( is_link( $link ) ) {
                unlink( $link );
            }
            return $args;
        }
        
    }
}