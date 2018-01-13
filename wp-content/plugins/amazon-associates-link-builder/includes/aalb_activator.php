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
 * Fired during the plugin activation
 *
 * Gets the template names from the template directory and loads it into the database.
 *
 * @since      1.0.0
 * @package    AmazonAssociatesLinkBuilder
 * @subpackage AmazonAssociatesLinkBuilder/includes
 */
class Aalb_Activator {

    protected $helper;
    private $aalb_compatibility_helper;

    public function __construct() {
        $this->helper = new Aalb_Helper();
        $this->aalb_compatibility_helper = new Aalb_Compatibility_Helper();
    }

    /**
     * Add the template names to the database from the filesystem.
     *
     * @since 1.0.0
     */
    private function load_templates() {
        $this->helper->refresh_template_list();
    }

    /**
     * Halts activation process if the plugin is not compatible with the user environment
     *
     * @since 1.4.3
     */
    private function halt_activation() {
        /* This string runs when plugin is not activated & so translation function will not translate it.
        ** So skipping it to put under translator function.
        */
        exit(sprintf('<span style="color:red;">%s plugin requires PHP Version %s or higher. You’re still on %s.</span>', AALB_PLUGIN_NAME, AALB_PLUGIN_MINIMUM_SUPPORTED_PHP_VERSION, phpversion()));
    }

    /**
     * The code to run on activation
     *
     * @since 1.4.3
     */
    function activate() {
        if($this->aalb_compatibility_helper->is_plugin_compatible()) {
            $this->load_templates();
        } else {
            $this->halt_activation();
        }
    }
}

?>