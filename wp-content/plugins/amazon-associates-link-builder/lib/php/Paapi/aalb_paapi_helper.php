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
 * Helper class for Paapi
 *
 * @since      1.0.0
 * @package    AmazonAssociatesLinkBuilder
 * @subpackage AmazonAssociatesLinkBuilder/lib/php/Paapi
 */
class Aalb_Paapi_Helper {

    /**
     * Returns the item lookup URL for asins
     *
     * @param string $asin Asin value.
     * @param string $marketplaces Marketplace to search the products.
     * @param string $tracking_id Associate tag.
     *
     * @return string Signed URL for item lookup.
     */
    function get_item_lookup_url( $asin, $marketplace, $tracking_id ) {
        $params = array(
            "Operation" => "ItemLookup", "ItemId" => "$asin", "IdType" => "ASIN", "ResponseGroup" => "Images,ItemAttributes,OfferFull", "AssociateTag" => "$tracking_id",
        );
        $url = $this->aws_signed_url( $params, $marketplace );

        return $url;
    }

    /**
     * Returns signed URL for Paapi request
     *
     * @since 1.0.0
     *
     * @param array $params Paapi parameters.
     * @param string $marketplace Marketplace to search the product.
     *
     * @return string Signed URL.
     */
    function aws_signed_url( $params, $marketplace ) {
        $access_key_id = openssl_decrypt( base64_decode( get_option( AALB_AWS_ACCESS_KEY ) ), AALB_ENCRYPTION_ALGORITHM, AALB_ENCRYPTION_KEY, 0, AALB_ENCRYPTION_IV );
        $secret_key = openssl_decrypt( base64_decode( get_option( AALB_AWS_SECRET_KEY ) ), AALB_ENCRYPTION_ALGORITHM, AALB_ENCRYPTION_KEY, 0, AALB_ENCRYPTION_IV );
        $host = $marketplace;

        $method = 'GET';
        $uri = PAAPI_URI;

        $params["Service"] = PAAPI_SERVICE;
        $params["AWSAccessKeyId"] = $access_key_id;
        $params["Timestamp"] = gmdate( 'Y-m-d\TH:i:s\Z' );
        $params["Version"] = PAAPI_VERSION;

        ksort( $params );

        $canonicalized_query = array();
        foreach ( $params as $param => $value ) {
            $param = str_replace( "%7E", "~", rawurlencode( $param ) );
            $value = str_replace( "%7E", "~", rawurlencode( $value ) );
            $canonicalized_query[] = $param . "=" . $value;
        }

        $canonicalized_query = implode( "&", $canonicalized_query );

        $string_to_sign = $method . "\n" . $host . "\n" . $uri . "\n" . $canonicalized_query;
        $signature = base64_encode( hash_hmac( "sha256", $string_to_sign, $secret_key, true ) );
        $signature = str_replace( "%7E", "~", rawurlencode( $signature ) );

        $signed_url = PAAPI_TRANSFER_PROTOCOL . $host . $uri . PAAPI_URL_QUERY_SEPARATOR . $canonicalized_query . "&Signature=" . $signature;

        return $signed_url;
    }

    /**
     * Returns the item search URL for search keywords
     *
     * @param string $search_keywords Search keywords of the products.
     * @param string $marketplaces Marketplace to search the products.
     * @param string $tracking_id Associate tag.
     *
     * @return string Signed URL for item search.
     */
    function get_item_search_url( $search_keywords, $marketplace, $tracking_id ) {
        $params = array(
            "Operation" => "ItemSearch", "SearchIndex" => "All", "Keywords" => "$search_keywords", "ResponseGroup" => "Images,ItemAttributes,Offers", "AssociateTag" => "$tracking_id",
        );
        $url = $this->aws_signed_url( $params, $marketplace );

        return $url;
    }

    /**
     * PA-API error messages to display in case of request errors
     *
     * @param string $error code Error code of the request.
     *
     * @return string PA-API error message.
     */
    function get_error_message( $error ) {
        switch ( $error ) {
            case HTTP_BAD_REQUEST:
                /* translators: 1: URL of Associate sign-up page  2: _blank  3:URL of adding secondary user page  4: _blank */
                return '<h4>' . sprintf( __( "Your AWS Access Key Id is not registered as an Amazon Associate. Please verify that you are <a href=%1s target=%2s>registered as an Amazon Associate</a> in respective locale and you added the email address registered for the Product Advertising API as a <a href=%3s target=%4s>secondary email address in your Amazon Associates account</a>.", 'amazon-associates-link-builder' ), AALB_ASSOCIATE_SIGN_UP_URL, AALB_NEW_PAGE_TARGET, AALB_ADDING_SECONDARY_USER_AC_URL, AALB_NEW_PAGE_TARGET ) . '</h4>';
            case HTTP_FORBIDDEN:
                /* translators: 1: URL of PA-API sign-up page  2: _blank */
                return '<h4>' . sprintf( __( "Your AccessKey Id is not registered for Product Advertising API. Please sign up for Product Advertising API by <a href=%1s target=%2s>following these guidelines</a>.", 'amazon-associates-link-builder' ), AALB_PAAPI_SIGN_UP_URL, AALB_NEW_PAGE_TARGET ) . '</h4>';
            case HTTP_REQUEST_URI_TOO_LONG:
                return '<h4>' . sprintf( __( "Your AccessKey Id is not registered for Product Advertising API. Please sign up for Product Advertising API by <a href=%1s target=%2s>following these guidelines</a>.", 'amazon-associates-link-builder' ), AALB_PAAPI_SIGN_UP_URL, AALB_NEW_PAGE_TARGET ) . '</h4>';
            case HTTP_INTERNAL_SERVER_ERROR:
                return '<h4>' . esc_html__( "Internal server error", 'amazon-associates-link-builder' ) . '</h4>';
            case HTTP_THROTTLE:
                /* translators: 1: URL of PA-API efficiency guidelines page  2: _blank */
                return '<h4>' . sprintf( __( "You are submitting requests too quickly. Please retry your requests at a slower rate. For more information, see <a href=%1s target=%2s>Efficiency Guidelines</a>.", 'amazon-associates-link-builder' ), AALB_PAAPI_EFFICIENCY_GUIDELINES_URL, AALB_NEW_PAGE_TARGET ) . '</h4>';
            case HTTP_TIME_OUT:
                /* translators: %s: Email-id of the support */
                return '<h4>' . sprintf( __( "Request timed out. Try again after some time. Please check you network and firewall settings. If the error still persists, write to us at %s.", 'amazon-associates-link-builder' ), AALB_SUPPORT_EMAIL_ID ) . '</h4>';
            default:
                /**
                 * <h4> tag ensures that the message is treated as HTML element in jQuery.find in aalb_admin.js.
                 * Otherwise due to the error message string's characters like "!,:,etc", string is parsed as
                 * if it contains partial css classes and later given syntax error
                */
                return '<h4>'.$error.'</h4>';
        }
    }

}

?>
