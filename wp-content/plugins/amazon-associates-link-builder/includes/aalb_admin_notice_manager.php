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
 *  Manager class for Admin notice action
 *  Uses singleton pattern to maintain single copy of function_list all over
 *
 * @since      1.4.3
 * @package    AmazonAssociatesLinkBuilder
 * @subpackage AmazonAssociatesLinkBuilder/includes
 */
class Aalb_Admin_Notice_Manager {
    private static $instance = null;

    private function __construct() {
    }

    private function __clone() {
    }

    public static function getInstance() {
        if ( self::$instance == null ) {
            self::$instance = new Aalb_Admin_Notice_Manager();
        }

        return self::$instance;
    }

    //Array to hold all admin notice action hooks in the plugin
    private $functions_list = array();

    /**
     * Add action for admin notice and also make an entry in function_list
     *
     * @since    1.4.3
     *
     * @param string $obj                 Reference of the object
     * @param callable $callback_function The name of the function you wish to be called.
     * @param int $priority               Optional. Used to specify the order in which the functions
     *                                    associated with a particular action are executed. Default 10.
     *                                    Lower numbers correspond with earlier execution,
     *                                    and functions with the same priority are executed
     *                                    in the order in which they were added to the action.
     * @param int $accepted_args          Optional. The number of arguments the function accepts. Default 1.
     *
     */
    public function add_notice( $obj, $callback_function, $priority = 10, $accepted_args = 1 ) {
        $function_to_add = array( $obj, $callback_function );
        add_action( 'admin_notices', $function_to_add, $priority, $accepted_args );
        $key = get_class( $obj ) . "::" . $callback_function;
        //Overrides the value of key if already present to prevent multiple entries in case object is instantiated again
        $this->functions_list[$key] = array(
            "function_to_add" => $function_to_add,
            "priority"        => $priority,
            "accepted_args"   => $accepted_args
        );
    }

    /**
     * Remove all functions for admin notice action that are added by plugin and
     * also remove entry from the function_list
     *
     * @since    1.4.3
     */
    public function remove_all_notices() {
        foreach ( $this->functions_list as $key => $value ) {
            remove_action( 'admin_notices', $value["function_to_add"], $value["priority"],
                $value["accepted_args"] );
            unset( $this->functions_list[$key] );
        }
    }
}

?>