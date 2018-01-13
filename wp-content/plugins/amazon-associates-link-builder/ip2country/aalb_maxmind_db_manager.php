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
 *
 * Manages the operations related to maxmind GeoLite2Country database & maintains regular updates for the same
 *
 * @since      1.5.0
 * @package    AmazonAssociatesLinkBuilder
 * @subpackage AmazonAssociatesLinkBuilder/ip2country
 */
class Aalb_Maxmind_Db_Manager {

    public $db_upload_dir;
    public $db_file_path;

    public function __construct() {
        $this->db_upload_dir = $this->get_db_file_dir();
        $this->db_file_path = $this->db_upload_dir . MAXMIND_DATA_FILENAME;
        clearstatcache( true, $this->db_file_path );
    }

    /**
     * Downloads & updates the maxmind db file(GeoLite2 Country)
     *
     * @argument HTTP Response $response
     *
     * @since 1.5.0
     *
     */
    private function update_db( $response ) {
        try {
            $outFile = $this->db_file_path;
            $tmp_file = $response["tmpfname"];
            $current_file = fopen( $outFile, 'w' );
            $donwloaded_file = gzopen( $tmp_file, 'r' );
            while ( ( $string = gzread( $donwloaded_file, 4096 ) ) != false ) {
                fwrite( $current_file, $string, strlen( $string ) );
            }
            gzclose( $donwloaded_file );
            fclose( $current_file );
            unlink( $tmp_file );
            update_option( AALB_GEOLITE_DB_LAST_UPDATED_TIME, strtotime( wp_remote_retrieve_header( $response["response"], 'Last-Modified' ) ) );
        } catch ( Exception $e ) {
            error_log( "Error in maxmind_db_manager:update_db:::" . $e->getMessage() );
            throw $e;
        }
    }

    /*
     * It checks if the GeoLite Db downloaded file has expired and call for update
     *
     * @since 1.5.0
     *
     */
    public function update_db_if_required() {
        $this->reset_db_keys_if_required();
        if ( $this->is_db_expired() ) {
            $this->check_and_update_db();
        }
    }

    /*
     * It checks if the GeoLite Db has expired
     *
     * @since 1.5.0
     *
     */
    private function is_db_expired() {
        return ( get_option( AALB_GEOLITE_DB_EXPIRATION_TIME ) == "" || get_option( AALB_GEOLITE_DB_EXPIRATION_TIME ) < time() );
    }

    /*
     * It checks if the GeoLite Db update is required and calls for update
     *
     * @since 1.5.0
     *
     */
    private function check_and_update_db() {
        try {
            $response = $this->get_db();
            if ( $response ) {
                if ( $this->should_update_db( $response["response"] ) ) {
                    $this->update_db( $response );
                }
                update_option( AALB_GEOLITE_DB_EXPIRATION_TIME, strtotime( wp_remote_retrieve_header( $response["response"] , 'expires' ) ) );
            }
        }
        catch ( Exception $e ) {
            error_log( "Error in maxmind_db_manager:should_update_db:::" . $e->getMessage() );
        }
    }

    /*
     * It downloads the db file
     *
     * @since 1.5.0
     *
     * @return geolite db on success else null
     *
     */
    private function get_db() {
        try {
            $response = $this->verify_response( $this->customized_download_url( AALB_GEOLITE_COUNTRY_DB_DOWNLOAD_URL ) );
        } catch ( Exception $e ) {
            $response = null;
            error_log( "Error in maxmind_db_manager:get_db:::" . $e->getMessage() );
        }

        return $response;
    }

    /*
     * It verifies the HTTP response.
     *
     * @argument HTTP_RESPONSE $response
     *
     * @since 1.5.2
     *
     * @return HTTP_RESPONSE $response
     */
    private function verify_response( $response ) {
        if ( is_wp_error( $response ) ) {
            throw new Exception( "WP_ERROR: " . $response->get_error_message() );
        } else if ( ! is_array( $response ) || ! array_key_exists( "response", $response ) || ! array_key_exists( "tmpfname", $response ) ) {
            throw new Exception( "Either the output is not an array or the one of the keys, response or tmpfname doesn't exist" );
        } else {
            $http_response = $response['response'];
            //Below sis reponse code returned by HTTP response
            $code = $http_response['response']['code'];
            if ( $code != HTTP_SUCCESS ) {
                throw new Exception( $code );
            }
        }

        return $response;
    }

    /*
    * It reset the db keys if required
    *
    * @since 1.5.0
    *
    */
    private function reset_db_keys_if_required() {
        if ( $this->should_write_new_db_file() ) {
            update_option( AALB_GEOLITE_DB_EXPIRATION_TIME, 0 );
            update_option( AALB_GEOLITE_DB_LAST_UPDATED_TIME, 0 );
        }
    }

    /*
    * It checks if writing a new db file operation should be done
    *
    * @since 1.5.0
    *
    */
    private function should_write_new_db_file() {
        return ( ! file_exists( $this->db_file_path ) && is_writable( $this->db_upload_dir ) );
    }

    /*
     * It does basic checks regarding read/write persmissions and then check if update is required
     *
     * @param  HTTPResponse $response
     *
     * @since 1.5.0
     *
     * @bool True if geolite db should be updated
     */
    private function should_update_db( $response ) {
        return ( $this->should_write_new_db_file() || ( is_writable( $this->db_file_path ) && $this->is_version_updated( $response ) ) );
    }

    /**
     * It sets the absolute path of the directory where db file is present
     *
     * @return string database directory absolute path
     *
     * @since 1.5.0
     *
     */
    private function get_db_file_dir() {
        $file_dir_path = get_option( AALB_CUSTOM_UPLOAD_PATH );
        if ( $file_dir_path == "" ) {
            $file_dir_path = wp_upload_dir()['basedir'] . '/' . AALB_UPLOADS_FOLDER;
        }

        return $file_dir_path;
    }

    /*
    * It checks if the newer version of GeoLite Db file is present
    *
    * @ since 1.5.0
    *
    * @return bool True if geolite db's newer version is available
    */
    private function is_version_updated( $response ) {
        return ( get_option( AALB_GEOLITE_DB_LAST_UPDATED_TIME ) == '' ) || ( strtotime( wp_remote_retrieve_header( $response, 'Last-Modified' ) ) > get_option( AALB_GEOLITE_DB_LAST_UPDATED_TIME ) );
    }

    /**
     * Downloads a URL to a local temporary file using the WordPress HTTP Class.
     * Please note, That the calling function must unlink() the file.
     * Modified from download_url() that is located in wp-admin/includes/file.php. Just changed the response returned
     *
     * @since 1.5.0
     *
     * @param string $url  the URL of the file to download
     * @param int $timeout The timeout for the request to download the file default 300 seconds
     *
     * @return mixed WP_Error on failure, Array of reponse & filename on success
     */
    function customized_download_url( $url, $timeout = 300 ) {
        //WARNING: The file is not automatically deleted, The script must unlink() the file.
        if ( ! $url )
            return new WP_Error( 'http_no_url', __( 'Invalid URL Provided.' ) );

        $url_filename = basename( parse_url( $url, PHP_URL_PATH ) );

        $tmpfname = wp_tempnam( $url_filename );
        if ( ! $tmpfname )
            return new WP_Error( 'http_no_file', __( 'Could not create Temporary file.' ) );

        $response = wp_safe_remote_get( $url, array( 'timeout' => $timeout, 'stream' => true, 'filename' => $tmpfname ) );

        if ( is_wp_error( $response ) ) {
            unlink( $tmpfname );

            return $response;
        }

        if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
            unlink( $tmpfname );

            return new WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ) );
        }

        $content_md5 = wp_remote_retrieve_header( $response, 'content-md5' );
        if ( $content_md5 ) {
            $md5_check = verify_file_md5( $tmpfname, $content_md5 );
            if ( is_wp_error( $md5_check ) ) {
                unlink( $tmpfname );

                return $md5_check;
            }
        }
        return array( "tmpfname" => $tmpfname, "response" => $response );
    }
}

?>