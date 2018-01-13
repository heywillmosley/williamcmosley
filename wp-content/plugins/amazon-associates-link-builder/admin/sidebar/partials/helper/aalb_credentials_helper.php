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
 * Helper class for commonly used functions in the credentials page of plugin.
 *
 * @since      1.4.12
 * @package    AmazonAssociatesLinkBuilder
 * @subpackage AmazonAssociatesLinkBuilder/admin/sidebar/partials/helper
 */
class Aalb_Credentials_Helper {

    /**
     * Returns data to be localized in the script.
     * Makes the variable values in PHP to be used in Javascript.
     *
     * @since 1.4.12
     * @return array Data to be localized in the script
     */
    private function credentials_data() {
        return array(
            'old_store_id_db_key'        => AALB_STORE_ID_NAMES,
            'new_store_id_db_key'        => AALB_STORE_IDS,
            'new_store_ids'              => get_option( AALB_STORE_IDS ),
            'default_marketplace_db_key' => AALB_DEFAULT_MARKETPLACE,
            'default_marketplace_value'  => get_option( AALB_DEFAULT_MARKETPLACE ),
            'marketplace_list'           => $this->get_marketplace_list(),
            'default_store_id_db_key'    => AALB_DEFAULT_STORE_ID

        );
    }

    /**
     * Returns constant strings to be used in aalb_credentials.js
     * Makes the variable values in PHP to be used in Javascript.
     *
     * @since 1.4.12
     * @return array Data to be localized in the script
     */
    private function credentials_strings() {
        //ToDO: Make default marketplace and remove marketplace also as label and put all labels together
        return array(
            'tracking_id_placeholder'           => esc_html__( "Enter Tracking Id(s)", 'amazon-associates-link-builder' ),
            'remove_marketplace_label'          => esc_html__( "Remove Marketplace", 'amazon-associates-link-builder' ),
            'select_marketplace_label'          => esc_html__( "Select Marketplace", 'amazon-associates-link-builder' ),
            'default_marketplace_label'         => esc_html__( "Default Marketplace", 'amazon-associates-link-builder' ),
            'set_as_default_marketplace_label'  => esc_html__( "Set As Default Marketplace", 'amazon-associates-link-builder' ),
            "tracking_id_fieldset_label"        => esc_html__( "Tracking Id(s)", 'amazon-associates-link-builder' ),
            "add_a_marketplace_label"           => esc_html__( "Add a Marketplace", 'amazon-associates-link-builder' ),
            'remove_marketplace_confirmation'   => esc_html__( "Remove Marketplace Confirmation", 'amazon-associates-link-builder' ),
            "empty_store_id_error"              => esc_html__( "ERROR: No store id has been entered for one or more marketplaces.", 'amazon-associates-link-builder' ),
            "marketplace_exists_error"          => esc_html__( "ERROR: A marketplace already exists with this value. Please set a new marketplace.", 'amazon-associates-link-builder' ),
            "marketplace_not_set_error"         => esc_html__( "ERROR: A marketplace is present that has not been set. Please set that first.", 'amazon-associates-link-builder' ),
            "remove_last_marketplace_error"     => esc_html__( "ERROR: You need to maintain at least one marketplace entry for tracking ids ", 'amazon-associates-link-builder' ),
            "no_marketplace_row_error"          => esc_html__( "ERROR: You need to add at least one marketplace entry for tracking ids ", 'amazon-associates-link-builder' ),
            "marketplace_settings_info_message" => esc_html__( "Add a Marketplace that you want to create Amazon links to.", 'amazon-associates-link-builder' ),
            "tracking_id_settings_info_message" => esc_html__( "For each marketplace you can add multiple tracking ids, separated by commas. The first tracking id will be considered as default tracking id for that marketplace.", 'amazon-associates-link-builder' )
        );
    }

    /**
     * Enqueue CSS classes
     *
     * @since 1.4.12
     *
     */
    public function aalb_credentials_enqueue_style() {
        wp_enqueue_style( 'thickbox' );
        wp_enqueue_style( 'aalb_credentials_css', AALB_CREDENTIALS_CSS, array(), AALB_PLUGIN_CURRENT_VERSION );
    }

    /**
     * Enqueue JS files
     *
     * @since 1.4.12
     *
     */
    public function aalb_credentials_enqueue_script() {
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'thickbox' );
        wp_enqueue_script( 'handlebars_js', HANDLEBARS_JS );
        wp_enqueue_script( 'aalb_credentials_js', AALB_CREDENTIALS_JS, array( 'jquery', 'thickbox', 'handlebars_js' ), AALB_PLUGIN_CURRENT_VERSION );
        wp_localize_script( 'aalb_credentials_js', 'aalb_cred_data', $this->credentials_data() );
        wp_localize_script( 'aalb_credentials_js', 'aalb_cred_strings', $this->credentials_strings() );
    }

    /**
     * Returns list of marketplaces
     *
     *
     * @since 1.4.12
     * @return array marketplaces list
     */
    private function get_marketplace_list() {
        $config_loader = new Aalb_Config_Loader();
        $aalb_marketplace_names = $config_loader->fetch_marketplaces();

        return json_encode( array_values( $aalb_marketplace_names ) );
    }

    /**
     * Prints admin error notices specific to geolite db on settings page
     *
     * @since 1.5.0
     */
    public function handle_error_notices() {
        $maxmind_db_manager = new Aalb_Maxmind_Db_Manager();
        if ( $this->is_more_than_one_marketplaces_configured() ) {
            if ( ! is_readable( $maxmind_db_manager->db_file_path ) ) {
                aalb_error_notice( sprintf( esc_html__( "The file used to fetch country details to enable geo-targetted links doesn't have read permissions. Please give recursive read/write permissons to:%s. In case you are still facing the issue, please change download folder in Site Wide Settings section on this page.", 'amazon-associates-link-builder' ), $maxmind_db_manager->db_file_path ) );
            } else if ( ! is_writable( $maxmind_db_manager->db_file_path ) ) {
                aalb_error_notice( sprintf( esc_html__( "The file used to fetch country details to enable geo-targetted links doesn't have write permissions. Please give recursive read/write permissons to:%s. In case you are still facing the issue, please change download folder in Site Wide Settings section on this page", 'amazon-associates-link-builder' ), $maxmind_db_manager->db_file_path ) );
            } else if ( ! is_writable( $maxmind_db_manager->db_upload_dir ) ) {
                aalb_error_notice( sprintf( esc_html__( "The directory where the file used to fetch country details to enable geo-targetted links doesn't have write permissions. Please give recursive read/write permissons to:%s. In case you are still facing the issue, please change download folder in Site Wide Settings section on this page", 'amazon-associates-link-builder' ), $maxmind_db_manager->db_upload_dir ) );
            }
        }
    }

    /**
     * Checks if more than one marketplaces have been configured in settings
     *
     * @since 1.5.0
     *
     * @return bool True if more than one marketplaces configured in settings
     */
    public function is_more_than_one_marketplaces_configured() {
        return count( json_decode( get_option( AALB_STORE_IDS ), true ) ) > 1;
    }
}

?>