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
 * Template engine to render the product in the particular display unit.
 *
 * @since      1.0.0
 * @package    AmazonAssociatesLinkBuilder
 * @subpackage AmazonAssociatesLinkBuilder/includes
 */
class Aalb_Template_Engine {

    protected $xml_loader;
    protected $cache_template_loader;
    protected $mustache;
    protected $xml_helper;
    protected $helper;

    public function __construct() {
        $this->xml_loader = new Aalb_Cache_Loader( new Aalb_Remote_Loader() );
        $this->helper = new Aalb_Helper();
        $this->cache_template_loader = new Aalb_Cache_Template_Loader();
        $this->mustache = new Mustache_Engine( array( 'loader' => new Mustache_Loader_FilesystemLoader( AALB_TEMPLATE_DIR ) ) );
        $this->mustache_custom = new Mustache_Engine( array( 'loader' => new Mustache_Loader_FilesystemLoader( $this->helper->get_template_upload_directory() ) ) );
        $this->xml_helper = new Aalb_Xml_Helper();
    }

    /**
     * Render the products into the display unit.
     * If the display unit exists in the cache return the display unit.
     * Else get the xml and render the product.
     *
     * @since 1.0.0
     *
     * @param string $display_key Key of the display unit.
     * @param string $products_key Key of the combined products.
     * @param string $template Template to render the display unit.
     * @param string $url Url to get the product from if not present in cache.
     * @param string $marketplace Marketplace to which the product belongs.
     * @param string $link_code Link Code to be entered in URLS for attribution purposes.
     *
     * @return string  HTML of the disply unit.
     */
    public function render( $display_key, $products_key, $template, $url, $marketplace, $link_code ) {
        if ( false === ( $display_unit = $this->cache_template_loader->get_display_unit( $display_key ) ) ) {
            $products = $this->get_products( $products_key, $url, $link_code );
            $xml = $this->parse( $products );
            $items = $this->get_items( $xml );

            //Add custom nodes to the XML
            $custom_items = $this->xml_helper->add_custom_nodes( $items, $marketplace );

            $display_unit = $this->render_xml( $custom_items, $template );
            $this->cache_template_loader->save_display_unit( $display_key, $display_unit );
        }

        return $display_unit;
    }

    /**
     * Get the products information.
     *
     * @since 1.0.0
     *
     * @param string $key Unique identification of the product.
     * @param string $url Signed URL for the PAAPI request.
     * @param string $link_code Link Code to be entered in URLS for attribution purposes.
     *
     * @return string Xml response from PAAPI.
     */
    private function get_products( $key, $url, $link_code ) {
        return $this->xml_loader->load( $key, $url, $link_code );
    }

    /**
     * Convert the well-formed xml string into a SimpleXMLElement object.
     *
     * @since 1.0.0
     *
     * @param string $xml_string Well-formed XML string
     *
     * @return SimpleXMLElement Php xml object.
     */
    private function parse( $xml_string ) {
        libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $xml_string );

        if ( $xml === false ) {
            //Don't translate as this is also dumped in error logs and will facilitate AALB team to debug
            throw new Exception( 'Failed Loading XML' );
        }

        if ( $this->should_not_render_xml( $xml ) ) {
            throw new Exception( $xml->Items->Request->Errors->Error->Code );
        }

        return $xml;
    }

    /**
     * Whether to allow xml to be rendered
     *
     * @since 1.4.7
     *
     * @param SimpleXMLElement $xml Well-formed XML string
     *
     * @return boolean
     */
    private function should_not_render_xml( $xml ) {
        return isset( $xml->Items->Request->Errors->Error )  && !( $xml->Items->Request->Errors->Error->Code == PAAPI_INVALID_PARAMETER_VALUE_ERROR && isset( $xml->Items->Item ) );
    }

    /**
     * Return the Item attribute contained in the xml.
     *
     * @since 1.0.0
     *
     * @param SimpleXMLElement $xml Well-formed XML string
     *
     * @return SimpleXMLElement Php xml object of the Items attribute.
     */
    private function get_items( $xml ) {
        return $xml->Items;
    }

    /**
     * Render the xml with a specific template.
     *
     * @since 1.0.0
     *
     * @param array $items Each key consists of an item information object.
     * @param string $template Template in which the content has to be rendered.
     *
     * @return string HTML of the display unit.
     */
    private function render_xml( $items, $template ) {
        $aalb_default_templates = explode( ",", AALB_AMAZON_TEMPLATE_NAMES );
        try {
            if ( in_array( $template, $aalb_default_templates ) ) {
                $template = $this->mustache->loadTemplate( $template );
            } else {
                $template = $this->mustache_custom->loadTemplate( $template );
            }
        } catch ( Mustache_Exception_UnknownTemplateException $e ) {
            $template = $this->mustache->loadTemplate( get_option( AALB_DEFAULT_TEMPLATE, AALB_DEFAULT_TEMPLATE_NAME ) );
        }

        return $template->render( array( 'StoreId' => get_option( AALB_DEFAULT_STORE_ID ), 'Items' => $items ) );
    }
}

?>
