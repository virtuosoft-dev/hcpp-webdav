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
         * Constructor, listen for add, update, or remove users.
         */
        public function __construct() {
            global $hcpp;
            $hcpp->webdav = $this;
            $hcpp->add_action( 'cg_pws_generate_website_cert', [ $this, 'cg_pws_generate_website_cert' ] );
            $hcpp->add_action( 'post_change_user_shell', [ $this, 'post_change_user_shell' ] );
            $hcpp->add_action( 'hcpp_invoke_plugin', [ $this, 'hcpp_invoke_plugin' ] );
            $hcpp->add_action( 'post_delete_user', [ $this, 'post_delete_user' ] );
            $hcpp->add_action( 'priv_delete_user', [ $this, 'priv_delete_user' ] );
            $hcpp->add_action( 'post_add_user', [ $this, 'post_add_user' ] );
            $hcpp->add_action( 'hcpp_rebooted', [ $this, 'hcpp_rebooted' ] );
            $hcpp->add_action( 'hcpp_plugin_disabled', [ $this, 'hcpp_plugin_disabled' ] );
            $hcpp->add_action( 'hcpp_plugin_enabled', [ $this, 'hcpp_plugin_enabled' ] );
        }

        // Stop services on plugin disabled.
        public function hcpp_plugin_disabled() {
            $this->stop();
        }

        // Start services on plugin enabled.
        public function hcpp_plugin_enabled() {
            $this->start();
        }

        // Intercept the certificate generation and copy over ssl certs for the webdav domain.
        public function cg_pws_generate_website_cert( $cmd ) {
            if ( strpos( $cmd, '/webdav-' ) !== false && strpos( $cmd, '/cg_pws_ssl && ') !== false ) {
                
                // Omit the v-delete-web-domain-ssl, v-add-web-domain-ssl, and v-add-web-domain-ssl-force cmds.
                global $hcpp;
                $path = $hcpp->delLeftMost( $cmd, '/usr/local/hestia/bin/v-add-web-domain-ssl' );
                $path = '/home' . $hcpp->delLeftMost( $path, '/home' );
                $path = $hcpp->delRightMost( $path, '/cg_pws_ssl &&' );
                $cmd = $hcpp->delRightMost( $cmd, '/usr/local/hestia/bin/v-delete-web-domain-ssl ' );
                $cmd .= " mkdir -p $path/ssl ; cp -r $path/cg_pws_ssl/* $path/ssl ";
                $cmd = $hcpp->do_action( 'webdav_generate_website_cert', $cmd );
            }
            return $cmd;
        }

        // Setup WebDAV for all users on reboot.
        public function hcpp_rebooted() {
            $this->start();
        }

        // Respond to invoke-plugin webdav_restart.
        public function hcpp_invoke_plugin( $args ) {
            if ( $args[0] === 'webdav_restart' ) {
                $this->restart();
            }
            return $args;
        }

        // Get the base domain; cache it for future use.
        public function get_base_domain() {
            global $hcpp;

            // Get the domain.
            if ( ! property_exists( $hcpp, 'domain' ) ) {
                $hcpp->domain = trim( shell_exec( 'hostname -d' ) );
            }
            return $hcpp->domain;
        }

        // Restart WebDAV services when user added.
        public function post_add_user( $args ) {
            global $hcpp;
            $hcpp->log( $hcpp->run( 'invoke-plugin webdav_restart' ) );
            return $args;
        }

        // Restart WebDAV services when shell changes.
        public function post_change_user_shell( $args ) {
            global $hcpp;
            $hcpp->log( $hcpp->run( 'invoke-plugin webdav_restart' ) );
            return $args;
        }

        // Restart WebDAV services.
        public function restart() {
            $this->stop();
            $this->start();
        }

        // Start all WebDAV services.
        public function start() {
            
            // Gather list of all users
            $cmd = "/usr/local/hestia/bin/v-list-users json";
            $result = shell_exec( $cmd );
            try {
                $result = json_decode( $result, true, 512, JSON_THROW_ON_ERROR );
            } catch (Exception $e) {
                var_dump( $e );
                return;
            }
            
            // Setup WebDAV for each valid user
            foreach( $result as $key=> $value ) {
                if ( $key === 'admin') continue;
                if ( $value['SHELL'] !== 'bash' ) continue;
                $this->setup( $key );
            }

            // Reload nginx
            global $hcpp;
            $cmd = '(service nginx reload) > /dev/null 2>&1 &';
            $cmd = $hcpp->do_action( 'webdav_nginx_reload', $cmd );
            shell_exec( $cmd );
        }
        
        // Stop all WebDAV services.
        public function stop() {

            // Find all rclone webdav processes for the /home folder
            $cmd = 'ps ax | grep "rclone serve webdav" | grep "/home" | grep -v grep';
            exec($cmd, $processes);

            // Loop through each process and extract the process ID (PID)
            foreach ($processes as $process) {
                $pid = preg_replace('/^\s*(\d+).*$/', '$1', $process);

                // Kill the process
                $kill = "kill $pid";
                exec($kill, $output, $returnValue);

                global $hcpp;
                $hcpp->log( "Killed rclone webdav process $pid" );
            }
        }

        // Setup WebDAV services for user.
        public function setup( $user ) {
            global $hcpp;
            $hcpp->log( "Setting up WebDAV for $user" );
            $domain = $this->get_base_domain();
            
            // Get user account first IP address.
            $ip = array_key_first(
                json_decode( shell_exec( '/usr/local/hestia/bin/v-list-user-ips ' . $user . ' json' ), true ) 
            );

            // Get a port for the WebDAV service.
            $port = $hcpp->allocate_port( 'webdav', $user );

            // Create the configuration folder.
            if ( ! is_dir( "/home/$user/conf/web/webdav-$user.$domain" ) ) {
                mkdir( "/home/$user/conf/web/webdav-$user.$domain" );
            }

            // Create the password file.
            $pw_hash = trim( shell_exec( "grep '^$user:' /etc/shadow" ) );
            file_put_contents( "/home/$user/conf/web/webdav-$user.$domain/.htpasswd", $pw_hash );

            // Create the nginx.conf file.
            $conf = "/home/$user/conf/web/webdav-$user.$domain/nginx.conf";
            $content = file_get_contents( __DIR__ . '/conf-web/nginx.conf' );
            $content = str_replace( 
                ['%ip%', '%user%', '%domain%', '%port%'],
                [$ip, $user, $domain, $port],
                $content
            );
            file_put_contents( $conf, $content );

            // Create the nginx.ssl.conf file.
            $ssl_conf = "/home/$user/conf/web/webdav-$user.$domain/nginx.ssl.conf";
            $content = file_get_contents( __DIR__ . '/conf-web/nginx.ssl.conf' );
            $content = str_replace( 
                ['%ip%', '%user%', '%domain%', '%port%'],
                [$ip, $user, $domain, $port],
                $content
            );
            file_put_contents( $ssl_conf, $content );

            // Generate website cert if it doesn't exist for Personal Web Server edition.
            if ( property_exists( $hcpp, 'cg_pws' ) ) {
                if ( !is_dir( "/home/$user/conf/web/webdav-$user.$domain/ssl" ) ) {
                    $hcpp->cg_pws->generate_website_cert( $user, ["webdav-$user.$domain"] );
                }
            }else{

                // Force SSL on non-Personal Web Server edition.
                $force_ssl_conf = "/home/$user/conf/web/webdav-$user.$domain/nginx.forcessl.conf";
                $content = "return 301 https://\$host\$request_uri;";
                file_put_contents( $force_ssl_conf, $content );

                // TODO: support for LE
            }

            // Create the nginx.conf configuration symbolic links.
            $link = "/etc/nginx/conf.d/domains/webdav-$user.$domain.conf";
            if ( ! is_link( $link ) ) {
                symlink( $conf, $link );
            }

            // Create the nginx.ssl.conf configuration symbolic links.
            $link = "/etc/nginx/conf.d/domains/webdav-$user.$domain.ssl.conf";
            if ( ! is_link( $link ) ) {
                symlink( $ssl_conf, $link );
            }

            // Start the WebDAV service on the given port.
            $cmd = 'runuser -l ' . $user . ' -c "';
            $cmd .= "(rclone serve webdav --addr $ip:$port /home/$user/web) > /dev/null 2>&1 &";
            $cmd .= '"';
            $cmd = $hcpp->do_action( 'webdav_rclone_cmd', $cmd );
            shell_exec( $cmd );
        }

        // Delete the NGINX configuration reference and server when the user is deleted.
        public function priv_delete_user( $args ) {
            global $hcpp;
            $user = $args[0];
            $domain = $this->get_base_domain();
            $link = "/etc/nginx/conf.d/domains/webdav-$user.$domain.conf";
            if ( is_link( $link ) ) {
                unlink( $link );
            }
            $link = "/etc/nginx/conf.d/domains/webdav-$user.$domain.ssl.conf";
            if ( is_link( $link ) ) {
                unlink( $link );
            }
            $link = "/etc/apache2/conf.d/domains/webdav-$user.$domain.conf";
            if ( is_link( $link ) ) {
                unlink( $link );
            }

            // Delete user port
            $hcpp->delete_port( 'webdav', $user );
            return $args;
        }

        // Restart the WebDAV service when a user is deleted.
        public function post_delete_user( $args ) {
            global $hcpp;
            $hcpp->log( $hcpp->run( 'invoke-plugin webdav_restart' ) );
            return $args;
        }
    }
    new WebDAV();
}