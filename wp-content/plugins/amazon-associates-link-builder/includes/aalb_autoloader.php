<?php

/*
Copyright 2016-2017 Amazon.com, Inc. or its affiliates. All Rights Reserved.

Licensed under the GNU General Public License as published by the Free Software Foundation,
Version 2.0 (the "License"). You may not use this file except in compliance with the License.
A copy of the License is located in the "license" file accompanying this file.

This file is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
either express or implied. See the License for the specific language governing permissions
and limitations under the License.
*/

/**
 * The class reponsible for auto-loading files.
 *
 * Loads the class with respect to their respective directories.
 *
 * @since      1.0.0
 * @package    AmazonAssociatesLinkBuilder
 * @subpackage AmazonAssociatesLinkBuilder/includes
 */
class Aalb_Autoloader {

    private $dir;

    /**
     * Register the autoloader for a directory in the plugin.
     *
     * @since 1.0.0
     *
     * @param string $dir Path of the directory.
     */
    public function __construct( $dir = '' ) {
        if ( ! empty( $dir ) ) {
            $this->dir = $dir;
        }

        spl_autoload_register( array( $this, 'autoload' ) );
    }

    /**
     * Make instances of the autoloaders for each directory in the plugin.
     *
     * @since 1.0.0
     */
    public static function register() {
        new self( AALB_INCLUDES_DIR );
        new self( AALB_ADMIN_DIR );
        new self( AALB_SIDEBAR_DIR );
        new self( AALB_PAAPI_DIR );
        new self( AALB_SHORTCODE_DIR );
        new self( AALB_LIBRARY_DIR );
        new self( AALB_SIDEBAR_HELPER_DIR );
        new self( AALB_IP_2_COUNTRY_DIR );
    }

    /**
     * Callback function of spl_autoload_register to autoload the class.
     *
     * @since 1.0.0
     *
     * @param string $class Name of the class to autoload.
     */
    public function autoload( $class ) {
        $path = $this->dir . strtolower( $class ) . '.php';
        if ( file_exists( $path ) ) {
            require_once( $path );
        }
    }
}

?>
