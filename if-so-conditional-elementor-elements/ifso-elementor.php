<?php
/*
Plugin Name: If-So Elementor Extension
Description: Powerful integration between If-So and Elementor
Version: 1.4
Author: If-So Technologies LTD
Author URI: http://www.if-so.com/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

@author: If-So Technologies LTD
*/

if(!defined('WPINC')){
    die();
}

if(!defined('IFSO_ELEMENTOR_ON')){
    define('IFSO_ELEMENTOR_ON',true);
    define('IFSO_ELEMENTOR_DIR',__DIR__);
    define('IFSO_ELEMENTOR_PLUGIN_FILE', IFSO_ELEMENTOR_DIR . '/ifso-elementor.php');
    define('IFSO_ELEMENTOR_VERSION','1.4');

    add_action( 'plugins_loaded', function(){
        if(defined('IFSO_PLUGIN_BASE_DIR') &&  defined('IFSO_WP_VERSION') && defined('ELEMENTOR_VERSION') && IFSO_ELEMENTOR_ON){
            if(version_compare(IFSO_WP_VERSION,'1.5.6','>=')){
                require_once(__DIR__ . '/ifso-elementor.class.php');
                if(class_exists('\IfSo\Addons\Elementor\ElementorExtension'))
                    $init = new IfSo\Addons\Base\Extension( \IfSo\Addons\Elementor\ElementorExtension::class );
                return;
            }
        }
        add_action( 'admin_notices', function(){
            ?>
            <div class="notice notice-error ifso-special-error">
                <p>The If-So and Elementor integration requires the core If-So plugin in order to work. </p> <p><a href="https://wordpress.org/plugins/if-so/" class="button button-primary" target="_blank">Free Download</a></p>
            </div>
            <?php
        });
    } );
}


