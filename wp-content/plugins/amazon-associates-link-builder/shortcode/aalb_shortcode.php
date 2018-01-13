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
 * Fired when a amazon_link shortcode is there in the post page.
 *
 * Gets the product information by making a Paapi request and renders the HTML
 *
 * @since      1.0.0
 * @package    AmazonAssociatesLinkBuilder
 * @subpackage AmazonAssociatesLinkBuilder/shortcode
 */
class Aalb_Shortcode {
    protected $paapi_helper;
    protected $template_engine;
    protected $helper;
    protected $tracking_api_helper;
    protected $shortcode_helper;

    public function __construct() {
        $this->template_engine = new Aalb_Template_Engine();
        $this->paapi_helper = new Aalb_Paapi_Helper();
        $this->helper = new Aalb_Helper();
        $this->tracking_api_helper = new Aalb_Tracking_Api_Helper();
        $this->shortcode_helper = new Aalb_Shortcode_Helper();
    }

    /**
     * Add basic styles
     *
     * @since 1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( 'aalb_basics_css', AALB_BASICS_CSS, array(), AALB_PLUGIN_CURRENT_VERSION );
    }

    /**
     * The function responsible for rendering the shortcode.
     * Makes a GET request and calls the render_xml to render the response.
     *
     * @since 1.0.0
     *
     * @param array $atts Shortcode attribute and values.
     *
     * @return HTML Rendered html to display.
     */
    public function render( $atts ) {
        try {
            $shortcode_attributes = $this->get_shortcode_attributes( $atts );

            $validated_link_id = $this->shortcode_helper->get_validated_link_id( $shortcode_attributes['link_id'] );
            $validated_marketplace = $this->shortcode_helper->get_validated_marketplace( $shortcode_attributes['marketplace'] );
            $validated_asins = $this->shortcode_helper->get_validated_asins( $shortcode_attributes['asins'] );
            $validated_template = $this->shortcode_helper->get_validated_template( $shortcode_attributes['template'] );
            $validated_store_id = $this->shortcode_helper->get_validated_store_id( $shortcode_attributes['store'] );

            $marketplace = $this->shortcode_helper->get_marketplace_endpoint( $validated_marketplace );
            $url = $this->paapi_helper->get_item_lookup_url( $validated_asins, $marketplace, $validated_store_id );
            $asins = $this->shortcode_helper->format_asins( $validated_asins );
            $products_key = $this->helper->build_products_cache_key( $asins, $marketplace, $validated_store_id );
            $products_template_key = $this->helper->build_template_cache_key( $asins, $marketplace, $validated_store_id, $validated_template );

            $this->shortcode_helper->enqueue_template_styles( $validated_template );

            return str_replace( array( '[[UNIQUE_ID]]' ), array( str_replace( '.', '-', $products_template_key ) ), $this->template_engine->render( $products_template_key, $products_key, $validated_template, $url, $validated_marketplace ) );
        } catch ( Exception $e ) {
            error_log( $this->paapi_helper->get_error_message( $e->getMessage() ) );
        }
    }

    /**
     * Returns default shortcode attributes if not mentioned
     *
     * @since 1.0.0
     *
     * @param array $atts Shortcode attributes.
     *
     * @return array  Default shortcode attributes if not mentioned.
     */
    private function get_shortcode_attributes( $atts ) {
        $shortcode_attributes = shortcode_atts( array(
            'asins' => null,
            'marketplace' => get_option( AALB_DEFAULT_MARKETPLACE ),
            'store' => get_option( AALB_DEFAULT_STORE_ID ),
            'template' => get_option( AALB_DEFAULT_TEMPLATE ),
            'link_id' => null
        ), $atts );

        return $shortcode_attributes;
    }
}

?>
