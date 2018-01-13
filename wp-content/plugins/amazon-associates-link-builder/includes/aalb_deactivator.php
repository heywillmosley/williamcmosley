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
 * Fired during the plugin deactivation
 *
 * @since      1.0.0
 * @package    AmazonAssociatesLinkBuilder
 * @subpackage AmazonAssociatesLinkBuilder/includes
 */
class Aalb_Deactivator {

    protected $helper;

    public function __construct() {
        $this->helper = new Aalb_Helper();
    }

    /**
     * Remove the settings stored by the admin in the database
     *
     * @since 1.0.0
     */
    public function remove_settings() {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        $statement = 'DELETE from ' . $table_prefix . 'options
          WHERE option_name like %s';
        $settings = "aalb%";
        $prepared_statement = $wpdb->prepare( $statement, $settings );

        try {
            $wpdb->query( $prepared_statement );
        } catch ( Exception $e ) {
            error_log( 'Unable to clear settings. Query failed with the Exception ' . $e->getMessage() );
        }
    }

    /**
     * Remove the cache stored in the database.
     *
     * @since 1.0.0
     */
    public function remove_cache() {
        $this->helper->clear_cache_for_substring( '' );
    }

    /**
     * Recursively remove the template uploads dir
     *
     * @since 1.3
     */
    public function remove_uploads_dir() {
        global $wp_filesystem;
        $this->helper->aalb_initialize_wp_filesystem_api();
        $upload_dir = $this->helper->get_template_upload_directory();
        $wp_filesystem->rmdir( $upload_dir, true );
    }
}

?>
