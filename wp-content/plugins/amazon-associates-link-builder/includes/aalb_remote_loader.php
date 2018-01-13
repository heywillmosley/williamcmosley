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
 * Fired while making a GET request.
 *
 * Generic class that can be used by any class to make a GET request call.
 *
 * @since      1.0.0
 * @package    AmazonAssociatesLinkBuilder
 * @subpackage AmazonAssociatesLinkBuilder/includes
 */
class Aalb_Remote_Loader {

    /**
     * Load the information by making a GET request.
     *
     * @since 1.0.0
     *
     * @param string $url URL for making a request.
     *
     * @return string GET response body.
     */
    public function load( $url ) {
        $response = $this->fetch( $url );

        return $this->verify( $response );
    }

    /**
     * Make a GET request and return the response.
     *
     * @since 1.0.0
     *
     * @param string $url URL for making a request.
     *
     * @return string GET Response.
     */
    private function fetch( $url ) {
        return wp_remote_get( $url, array( 'timeout' => PAAPI_REQUEST_TIMEOUT ) );
    }

    /**
     * Verify the response the throw exceptions accordingly.
     * Return only the response body.
     *
     * @since 1.0.0
     *
     * @param string $response Whole response including headers,body and footers.
     *
     * @return string Response body.
     */
    private function verify( $response ) {
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            if( strpos ( $error_message, CURL_ERROR_TIMEOUT_STRING ) !== false ) {
                throw new Exception( HTTP_TIME_OUT );
            }
            /* translators: %s: Error message */
            throw new Exception( sprintf( esc_html__( 'HTTP Request failed! %s', 'amazon-associates-link-builder' ), $error_message ) );
        }
        $code = $response['response']['code'];
        if ( $code != HTTP_SUCCESS ) {
            throw new Exception( $code );
        }
        $response_body = wp_remote_retrieve_body( $response );
        if ( ! isset( $response_body ) || trim( $response_body ) === '' ) {
            throw new Exception( esc_html__( 'Response body is empty', 'amazon-associates-link-builder' ) );
        }

        return $response_body;
    }

    /**
     * Load the information by making a POST request.
     *
     * @since 1.0.0
     *
     * @param string $url URL for making a request.
     * @param string $body Body of the POST request.
     *
     * @return string POST response body.
     */
    public function post( $url, $body ) {
        $response = wp_remote_post( $url, array( 'body' => $body ) );

        return $this->verify( $response );
    }

}

?>
