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
            $hcpp->add_action( 'priv_add_user', [ $this, 'priv_add_user'] );
            $hcpp->add_action( 'priv_remove_user', [ $this, 'priv_remove_user'] );
            $hcpp->add_action( 'priv_unsuspend_domain', [ $this, 'priv_unsuspend_domain'] );
        }
    }
}