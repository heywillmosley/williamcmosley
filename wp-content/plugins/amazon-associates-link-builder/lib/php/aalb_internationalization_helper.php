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
 * Helper class for internationalizing the strings
 *
 * @since      1.0.0
 * @package    AmazonAssociatesLinkBuilder
 * @subpackage AmazonAssociatesLinkBuilder/lib/php
 */
class Aalb_Internationalization_Helper {

    /**
     * Array that defines the translations for various marketplaces
     */
    protected $translation_array;

    public function __construct() {
        $this->translation_array = array(
            CHECK_ON_AMAZON => array(
                US => "Check on Amazon",
                FR => "Consulter sur Amazon.fr",
                IT => "Vedi su Amazon.it",
                DE => "Hier auf Amazon.de",
                ES => "Consultar en Amazon.es",
                BR => "Check on Amazon",
                CA => "Check on Amazon",
                CN => "Check on Amazon",
                IN => "Check on Amazon",
                JP => "Check on Amazon",
                MX => "Check on Amazon",
                UK => "Check on Amazon",
            ),
            OUT_OF_STOCK => array(
                US => "Out of stock",
                FR => "Rupture de stock",
                IT => "Non disponibile",
                DE => "Derzeit nicht verfügbar",
                ES => "Producto no disponible",
                BR => "Out of stock",
                CA => "Out of stock",
                CN => "Out of stock",
                IN => "Out of stock",
                JP => "Out of stock",
                MX => "Out of stock",
                UK => "Out of stock",
            ),
        );
    }

    /**
     * Internationalize stings by marketplace
     *
     * @since 1.0.0
     *
     * @param string $key Identifier of string to be translated
     * @param string $marketplace The target marketplace name
     *
     * @return string
     */
    public function internationalize_by_marketplace( $key, $marketplace ) {
        $translated_string = $this->translation_array[ $key ][ $marketplace ];

        if ( $translated_string == null ) {
            //use english if transation is not available
            $translated_string = $this->translation_array[ $key ][ AALB_DEFAULT_MARKETPLACE_NAME ];
            if ( $translated_string == null ) {
                //use key name if english is also not available
                $translated_string = $key;
            }
        }

        return $translated_string;
    }
}

?>
