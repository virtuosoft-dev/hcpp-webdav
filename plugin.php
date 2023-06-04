<?php
/**
 * Plugin Name: WebDAV
 * Plugin URI: https://github.com/virtuosoft-dev/hcpp-webdav
 * Description: Adds a compression-enabled WebDAV service for HestiaCP user accounts; optimized for developer files.
 * Version: 1.0.0
 * Author: Stephen J. Carnam
 *
 */

// Register the install and uninstall scripts
global $hcpp;
require_once( dirname(__FILE__) . '/webdav.php' );

$hcpp->register_install_script( dirname(__FILE__) . '/install' );
$hcpp->register_uninstall_script( dirname(__FILE__) . '/uninstall' );
