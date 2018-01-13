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
 * Helper class for APIs used for impression and clicks tracking
 *
 * @since      1.0.0
 * @package    AmazonAssociatesLinkBuilder
 * @subpackage AmazonAssociatesLinkBuilder/lib/php
 */
class Aalb_Tracking_Api_Helper {

    protected $remote_loader;
    protected $helper;
    protected $version_info;

    public function __construct() {
        $this->remote_loader = new Aalb_Remote_Loader();
        $this->helper = new Aalb_Helper();

        //Initializes the version information once.
        $plugin_version = AALB_PLUGIN_CURRENT_VERSION;
        $wordpress_version = $this->helper->get_wordpress_version();
        $this->version_info = array(
            'wordpress' => $wordpress_version,
            'plugin' => $plugin_version
        );
    }

    /**
	 * Returns the response of the link-id API.
	 * The GET request returns a unique link-id everytime a shortcode is added
	 *
	 * @since 1.0.0
	 *
	 * @param string $shortcode_name Name of the shortcode used
	 * @param string $shortcode_params Array of all shortcode parameter key-value pairs
	 *
	 * @return string Response of the get link-id API for the given link-id
	 */
    public function get_link_id( $shortcode_name, $shortcode_params ) {
        $link_info = array(
            'shortcode_name' => $shortcode_name, 'shortcode_params' => $shortcode_params, 'version' => $this->version_info
        );
        $request_body = wp_json_encode( $link_info );
        $base_url = $this->get_base_url( 'link-id' );
        $response = $this->remote_loader->post( $base_url, $request_body );
        $response_body = json_decode( $response, true );

        return $response_body["link-id"];
    }

    /**
	 * Builds the base url for each tracking api request. Adds common parameters
	 *
	 * @since 1.0.0
	 *
	 * @param string $method_path Relative path of the api method to be called.
	 *
	 * @return string The base url with common query parameters
	 */
    private function get_base_url( $method_path ) {
        $access_key_id = openssl_decrypt( base64_decode( get_option( AALB_AWS_ACCESS_KEY ) ), AALB_ENCRYPTION_ALGORITHM, AALB_ENCRYPTION_KEY, 0, AALB_ENCRYPTION_IV );

        return ( AALB_TRACKING_API_ENDPOINT . $method_path . '?' . AALB_TRACKING_API_SOURCE_TOOL_QUERY_PARAM . '&' . AALB_TRACKING_API_ACCESS_KEY_QUERY_PARAM . $access_key_id );
    }

    /**
	 * Returns the response of the impressions API for a given link-id
	 * TODO: Not used post v1.4. Impression tracking plugged out for re-vamping purposes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $link_id Link ID for which impression parameters are required
	 * @param string $shortcode_name Name of the shortcode used
	 * @param string $shortcode_params Array of all shortcode parameter key-value pairs
	 *
	 * @return string Response of the get impression API for the given link-id
	 */
    public function get_impression_params( $link_id, $shortcode_name, $shortcode_params ) {
        $link_info = array(
            'shortcode_name' => $shortcode_name, 'shortcode_params' => $shortcode_params, 'version' => $this->version_info
        );
        $request_body = wp_json_encode( $link_info );
        $base_url = $this->get_base_url( 'impression' );
        $url = $base_url . '&link-id=' . $link_id;

        return $this->remote_loader->post( $url, $request_body );
    }

    /**
	 * Returns the click URL by parsing the recieved getImpressions API Response
	 * TODO: Not used post v1.4. Impression Tracking plugged out for re-vamping purposes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $impression_params JSON Response from the get impressions API for a link-id
	 *
	 * @return string $click_url  Click URL for an impression ID
	 */
    public function get_click_url( $impression_params ) {
        $body = json_decode( $impression_params, true );

        return $body["click-url"];
    }

    /**
	 * Echoes an invisible img with src=pixel-url to fire the pixels
	 * TODO: Not used post v1.4. Impression Tracking plugged out for re-vamping purposes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $impression_params JSON Response from the get impressions API for a link-id
	 */
    public function insert_pixel( $impression_params ) {
        $body = json_decode( $impression_params, true );
        $pixel_url = $body["pixel-url"];
        if ( ! is_amp_endpoint() ) {
            echo '<img src="' . $pixel_url . '" style="display:none"></img>';
        }
    }
}

?>
