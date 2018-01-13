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
 * Helper class for customizations to the xml response
 *
 * @since      1.0.0
 * @package    AmazonAssociatesLinkBuilder
 * @subpackage AmazonAssociatesLinkBuilder/lib/php
 */
class Aalb_Xml_Helper {

    protected $internationalization_helper;

    public function __construct() {
        $this->internationalization_helper = new Aalb_Internationalization_Helper();
    }

    /**
     * Add custom nodes to xml response
     *
     * @since 1.0.0
     *
     * @param SimpleXMLElement $items Well-formed XML string
     *
     * @return SimpleXMLElement $items XML String with custom nodes added
     */
    public function add_custom_nodes( $items, $marketplace ) {
        $common_marketplace_node_name = 'Marketplace' . $marketplace;
        $items->ID = "[[UNIQUE_ID]]";

        //add aalb nodes needed for each item
        foreach ( $items->Item as $item ) {
            $aalb_node = $item->addChild( 'aalb' );

            $aalb_node->ASIN = isset( $item->ASIN ) ? $item->ASIN : null;
            $aalb_node->Title = isset( $item->ItemAttributes->Title ) ? $item->ItemAttributes->Title : null;
            $aalb_node->DetailPageURL = isset( $item->DetailPageURL ) ? $item->DetailPageURL : null;
            $aalb_node->LargeImageURL = isset( $item->LargeImage->URL ) ? $item->LargeImage->URL : null;
            $aalb_node->MediumImageURL = isset( $item->MediumImage->URL ) ? $item->MediumImage->URL : null;
            $aalb_node->SmallImageURL = isset( $item->SmallImageURL->URL ) ? $item->SmallImage->URL : null;

            //Marketplace
            $marketplace_node_name = $common_marketplace_node_name;
            $aalb_node = $this->add_xml_node( $aalb_node, $marketplace_node_name, 'true' );

            //By Information
            $aalb_node = $this->add_by_information_node( $item, $aalb_node );

            //Savings
            $aalb_node = $this->add_savings_node( $item, $aalb_node );

            //MinimumPrice
            $aalb_node = $this->add_min_price_node( $item, $aalb_node );

            //Prime
            $aalb_node = $this->add_prime_node( $item, $aalb_node );

            //Merchant Name
            $aalb_node = $this->add_merchant_node( $item, $aalb_node );

            //Current and Strike Price
            $aalb_node = $this->add_price_nodes( $item, $aalb_node );


            //Node to check "out of stock" items
            $aalb_node = $this->add_out_of_stock_node( $item, $aalb_node, $marketplace );


            //If the Buying Price is empty or if is is Too Low to Display
            if ( empty( $aalb_node->CurrentPrice ) or strtolower( $aalb_node->CurrentPrice ) == 'too low to display' ) {
                $aalb_node->CurrentPrice = $this->internationalization_helper->internationalize_by_marketplace( CHECK_ON_AMAZON, $marketplace );
            }
        }

        //add common aalb nodes
        $aalb_common_node = $items->addChild( 'AalbHeader' );
        $aalb_common_node = $this->add_xml_node( $aalb_common_node, $common_marketplace_node_name, 'true' );

        return $items;
    }

    /**
     * Adds a child xml node to a given parent node if the value is not empty.
     *
     * @since 1.0.0
     *
     * @param SimpleXMLElement $parent_node PHP XML Object of parent node
     * @param string $node_name Name of the new node to be added
     * @param string $node_value Value of the new node to be added
     *
     * @return SimpleXMLElement $parent_node Parent node with added child node
     */
    public function add_xml_node( $parent_node, $node_name, $node_value ) {
        if ( ! empty( $node_value ) ) {
            $parent_node->$node_name = $node_value;
        }

        return $parent_node;
    }

    /**
     * Adds By Information Node
     * And separated list of all artists, brands and authors
     *
     * @since 1.0.0
     *
     * @param SimpleXMLElement $item Well formed XML String: The Parent item node
     * @param SimpleXMLElement $aalb_node Node to which values are to be added
     *
     * @return SimpleXMLElement Modified aalb_node
     */
    public function add_by_information_node( $item, $aalb_node ) {
        $author_array = array();
        $brand_array = array();
        $artist_array = array();
        $by_information = array();
        foreach ( $item->ItemAttributes->Author as $author ) {
            array_push( $author_array, $author );
        }
        foreach ( $item->ItemAttributes->Brand as $brand ) {
            array_push( $brand_array, $brand );
        }
        foreach ( $item->ItemAttributes->Artist as $artist ) {
            array_push( $artist_array, $artist );
        }
        if ( ! empty( $author_array ) ) {
            array_push( $by_information, implode( ', ', $author_array ) );
        }
        if ( ! empty( $brand_array ) ) {
            array_push( $by_information, implode( ', ', $brand_array ) );
        }
        if ( ! empty( $artist_array ) ) {
            array_push( $by_information, implode( ', ', $artist_array ) );
        }
        $aalb_node->By = implode( ' and ', $by_information );

        return $aalb_node;
    }

    /**
     * Adds Savings related nodes
     * Adds Amount saved in both raw and formatted way and the percentage saved.
     * Savings node are added only if we get saving nodes in the XML response from PAAPI.
     *
     * @since 1.0.0
     *
     * @param SimpleXMLElement $item Well formed XML String: The Parent item node
     * @param SimpleXMLElement $aalb_node Node to which values are to be added
     *
     * @return SimpleXMLElement Node to which values are added
     */
    public function add_savings_node( $item, $aalb_node ) {
        if ( ! empty( $item->Offers->Offer->OfferListing->AmountSaved->FormattedPrice ) ) {
            $aalb_node = $this->add_xml_node( $aalb_node, 'Saving', $item->Offers->Offer->OfferListing->AmountSaved->FormattedPrice );
        }
        if ( ! empty( $item->Offers->Offer->OfferListing->AmountSaved->Amount ) ) {
            $aalb_node = $this->add_xml_node( $aalb_node, 'SavingValue', $item->Offers->Offer->OfferListing->AmountSaved->Amount );
        }
        if ( ! empty( $item->Offers->Offer->OfferListing->PercentageSaved ) ) {
            $aalb_node = $this->add_xml_node( $aalb_node, 'SavingPercent', $item->Offers->Offer->OfferListing->PercentageSaved );
        }

        return $aalb_node;
    }

    /**
     * Adds Minimum Price related nodes
     * Adds raw and formatted values of minimum price
     *
     * @since 1.0.0
     *
     * @param SimpleXMLElement $item Well formed XML String: The Parent item node
     * @param SimpleXMLElement $aalb_node Node to which values are to be added
     *
     * @return SimpleXMLElement Node to which values are added
     */
    public function add_min_price_node( $item, $aalb_node ) {
        if ( ! empty( $item->OfferSummary->LowestNewPrice->FormattedPrice ) ) {
            $aalb_node = $this->add_xml_node( $aalb_node, 'MinimumPrice', $item->OfferSummary->LowestNewPrice->FormattedPrice );
        }
        if ( ! empty( $item->OfferSummary->LowestNewPrice->Amount ) ) {
            $aalb_node = $this->add_xml_node( $aalb_node, 'MinimumPriceValue', $item->OfferSummary->LowestNewPrice->Amount );
        }

        return $aalb_node;
    }

    /**
     * Adds Prime node
     * Adds Prime node if the item is eligible for prime
     *
     * @since 1.4.1
     *
     * @param SimpleXMLElement $item Well formed XML String: The Parent item node
     * @param SimpleXMLElement $aalb_node Node to which values are to be added
     *
     * @return SimpleXMLElement Node to which values are added
     */
    public function add_prime_node( $item, $aalb_node ) {
        if ( ! empty( $item->Offers->Offer->OfferListing->IsEligibleForPrime ) ) {
            $aalb_node = $this->add_xml_node( $aalb_node, 'Prime', $item->Offers->Offer->OfferListing->IsEligibleForPrime );
        }

        return $aalb_node;
    }

    /**
     * Adds Merchant node
     * Adds Merchant information inside the Merchant node
     *
     * @since 1.4.1
     *
     * @param SimpleXMLElement $item Well formed XML String: The Parent item node
     * @param SimpleXMLElement $aalb_node Node to which values are to be added
     *
     * @return SimpleXMLElement Node to which values are added
     */
    public function add_merchant_node( $item, $aalb_node ) {
        if ( ! empty( $item->Offers->Offer->Merchant->Name ) ) {
            $aalb_node = $this->add_xml_node( $aalb_node, 'Merchant', $item->Offers->Offer->Merchant->Name );
        }

        return $aalb_node;
    }

    /**
     * Adds Current Price and Strike Price Nodes after applying logic
     * Logic for Current Price and Strike Price
     *
     * @since 1.0.0
     *
     * @param SimpleXMLElement $item Well formed XML String: The Parent item node
     * @param SimpleXMLElement $aalb_node Node to which values are to be added
     *
     * @return SimpleXMLElement Node to which values are added
     */
    public function add_price_nodes( $item, $aalb_node ) {
        $list_price = isset( $item->ItemAttributes->ListPrice->FormattedPrice ) ? $item->ItemAttributes->ListPrice->FormattedPrice : null;
        $price = isset( $item->Offers->Offer->OfferListing->Price->FormattedPrice ) ? $item->Offers->Offer->OfferListing->Price->FormattedPrice : null;
        $sale_price = isset( $item->Offers->Offer->OfferListing->SalePrice->FormattedPrice ) ? $item->Offers->Offer->OfferListing->SalePrice->FormattedPrice : null;
        $list_price_amount = isset( $item->ItemAttributes->ListPrice->Amount ) ? $item->ItemAttributes->ListPrice->Amount : null;
        $price_amount = isset( $item->Offers->Offer->OfferListing->Price->Amount ) ? $item->Offers->Offer->OfferListing->Price->Amount : null;
        $sale_price_amount = isset( $item->Offers->Offer->OfferListing->SalePrice->Amount ) ? $item->Offers->Offer->OfferListing->SalePrice->Amount : null;
        if ( ! empty( $sale_price_amount ) ) {
            //If Sale Price is returned
            $aalb_node->CurrentPrice = $sale_price;
            $aalb_node->CurrentPriceValue = $sale_price_amount;
            if ( (int) $aalb_node->SavingPercent > 1 ) {
                $aalb_node->StrikePrice = $price;
                $aalb_node->StrikePriceValue = $price_amount;
            }
        } else {
            $aalb_node->CurrentPrice = $price;
            $aalb_node->CurrentPriceValue = $price_amount;
            if ( (int) $aalb_node->SavingPercent > 1 ) {
                $aalb_node->StrikePrice = $list_price;
                $aalb_node->StrikePriceValue = $list_price_amount;
            }
        }

        return $aalb_node;
    }

    /**
     * Adds InStock node if the item is in stock; Updates Current Price otherwise
     *
     * @since 1.0.0
     *
     * @param SimpleXMLElement $item Well formed XML String: The Parent item node
     * @param SimpleXMLElement $aalb_node Node to which values are to be added
     *
     * @oaram string $marketplace
     *
     * @return SimpleXMLElement Node to which values are added
     */
    public function add_out_of_stock_node( $item, $aalb_node, $marketplace ) {
        $total_new = isset( $item->OfferSummary->TotalNew ) ? $item->OfferSummary->TotalNew : null;
        $availability = isset( $item->Offers->Offer->OfferListing->Availability ) ? $item->Offers->Offer->OfferListing->Availability : null;
        if ( ( $total_new == '0' or $availability == "Out of Stock" ) ) {
            //If the item is out of stock, update Buying Price
            $aalb_node->CurrentPrice = $this->internationalization_helper->internationalize_by_marketplace( OUT_OF_STOCK, $marketplace );;
        } else {
            //If the item is in stock; add a xml node to identify values in stock
            $aalb_node->InStock = 'Yes True';
        }

        return $aalb_node;
    }

    /**
     * Adds Click URL Prefix to requierd hyperlinks
     * TODO: Not used post v1.4. Impression Tracking plugged out for re-vamping purposes.
     *
     * @since 1.0.0
     *
     * @param SimpleXMLElement $items Well formed XML String
     *
     * @return SimpleXMLElement $items XML String with hyperlinks prefixed with click URL
     */
    public function prefix_click_URL( $items ) {
        foreach ( $items->Item as $item ) {
            $item->DetailPageURL = '[[CLICK_URL_PREFIX]]' . $item->DetailPageURL;
        }

        return $items;
    }
}

?>
