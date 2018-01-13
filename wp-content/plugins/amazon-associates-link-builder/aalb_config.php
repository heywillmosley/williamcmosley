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

//version
define( 'AALB_PLUGIN_CURRENT_VERSION', '1.5.2' );

//Version no. with multi locale settings page
define( 'AALB_MULTI_LOCALE_SETTINGS_PLUGIN_VERSION', '1.4.12' );

//PHP version compatible for AALB plugin
define( 'AALB_PLUGIN_MINIMUM_SUPPORTED_PHP_VERSION', '5.4.0' );

//Plugin Name
define( 'AALB_PLUGIN_NAME', 'Amazon Associates Link Builder' );
//paths
define( 'AALB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AALB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

//Project Title
define( 'AALB_PROJECT_TITLE', 'Associates Link Builder' );

/**
 * File paths
 */

//Library
define( 'MUSTACHE_AUTOLOADER_PHP', AALB_PLUGIN_DIR . 'lib/php/Mustache/Autoloader.php' );
define( 'AALB_PAAPI_HELPER_PHP', AALB_PLUGIN_DIR . 'lib/php/Paapi/aalb_paapi_helper.php' );
define( 'AALB_VALIDATION_HELPER_PHP', AALB_PLUGIN_DIR . 'lib/php/aalb_validation_helper.php' );
define( 'AALB_XML_HELPER_PHP', AALB_PLUGIN_DIR . 'lib/php/aalb_xml_helper.php' );
define( 'AALB_TRACKING_API_HELPER_PHP', AALB_PLUGIN_DIR . 'lib/php/aalb_tracking_api_helper.php' );
define( 'MAXMIND_DATA_FILENAME', 'GeoLite2-Country.mmdb' );

//Shortcodes supported
define( 'AALB_SHORTCODE_AMAZON_LINK', 'amazon_link' );
define( 'AALB_SHORTCODE_AMAZON_TEXT', 'amazon_textlink' );

//Admin
define( 'AALB_SIDEBAR_PHP', AALB_PLUGIN_DIR . 'admin/sidebar/aalb_sidebar.php' );
define( 'AALB_ADMIN_PHP', AALB_PLUGIN_DIR . 'admin/aalb_admin.php' );
define( 'AALB_ABOUT_PHP', AALB_PLUGIN_DIR . 'admin/sidebar/partials/aalb_about.php' );
define( 'AALB_CREDENTIALS_PHP', AALB_PLUGIN_DIR . 'admin/sidebar/partials/aalb_credentials.php' );
define( 'AALB_TEMPLATE_PHP', AALB_PLUGIN_DIR . 'admin/sidebar/partials/aalb_templates.php' );

//Directories
define( 'AALB_TEMPLATE_DIR', AALB_PLUGIN_DIR . 'template/' );
define( 'AALB_ADMIN_DIR', AALB_PLUGIN_DIR . 'admin/' );
define( 'AALB_SIDEBAR_DIR', AALB_PLUGIN_DIR . 'admin/sidebar/' );
define( 'AALB_INCLUDES_DIR', AALB_PLUGIN_DIR . 'includes/' );
define( 'AALB_PAAPI_DIR', AALB_PLUGIN_DIR . 'lib/php/Paapi/' );
define( 'AALB_SHORTCODE_DIR', AALB_PLUGIN_DIR . 'shortcode/' );
define( 'AALB_LIBRARY_DIR', AALB_PLUGIN_DIR . 'lib/php/' );
define( 'AALB_SIDEBAR_HELPER_DIR', AALB_PLUGIN_DIR . 'admin/sidebar/partials/helper/' );
define( 'AALB_IP_2_COUNTRY_DIR', AALB_PLUGIN_DIR . 'ip2country/' );

//Includes
define( 'AALB_ACTIVATOR_PHP', AALB_PLUGIN_DIR . 'includes/aalb_activator.php' );
define( 'AALB_DEACTIVATOR_PHP', AALB_PLUGIN_DIR . 'includes/aalb_deactivator.php' );
define( 'AALB_MANAGER', AALB_PLUGIN_DIR . 'includes/aalb_manager.php' );
define( 'AALB_HOOK_LOADER', AALB_PLUGIN_DIR . 'includes/aalb_hook_loader.php' );
define( 'AALB_CACHE_LOADER', AALB_PLUGIN_DIR . 'includes/aalb_cache_loader.php' );
define( 'AALB_REMOTE_LOADER', AALB_PLUGIN_DIR . 'includes/aalb_remote_loader.php' );
define( 'AALB_AUTOLOADER', AALB_PLUGIN_DIR . 'includes/aalb_autoloader.php' );

//Templates Directory
define( 'AALB_TEMPLATE_URL', AALB_PLUGIN_URL . 'template/' );
define( 'AALB_TEMPLATE_UPLOADS_FOLDER', 'amazon-associates-link-builder/template/' );
define( 'AALB_UPLOADS_FOLDER', 'amazon-associates-link-builder/' );

//Partials
define( 'AALB_META_BOX_PARTIAL', AALB_PLUGIN_DIR . 'admin/partials/aalb_meta_box.php' );
define( 'AALB_EDITOR_SEARCH_BOX', AALB_PLUGIN_DIR . 'admin/partials/aalb_editor_search_box.php' );

//Proxy
define( 'AALB_PROXY_URL', AALB_PLUGIN_URL . 'lib/php/Paapi/aalb_paapi_proxy.php' );

//Tracking API Endpoint
define( 'AALB_TRACKING_API_ENDPOINT', 'https://rx5hfxbp45.execute-api.us-east-1.amazonaws.com/aalb/' );
define( 'AALB_TRACKING_API_SOURCE_TOOL_QUERY_PARAM', 'source-tool=aalb' );
define( 'AALB_TRACKING_API_ACCESS_KEY_QUERY_PARAM', 'aws-access-key-id=' );

//Wordpress Pages
define( 'WP_POST', 'post.php' );
define( 'WP_POST_NEW', 'post-new.php' );

/**
 * Styles and scripts
 */

//Local Styles
define( 'AALB_ADMIN_CSS', AALB_PLUGIN_URL . 'admin/css/aalb_admin.css' );
define( 'AALB_CREDENTIALS_CSS', AALB_PLUGIN_URL . 'admin/css/aalb_credentials.css' );
define( 'AALB_BASICS_CSS', AALB_PLUGIN_URL . 'css/aalb_basics.css' );

//Local Scripts
define( 'AALB_SHA2_JS', AALB_PLUGIN_URL . 'lib/js/jssha2/sha2.js' );
define( 'AALB_ADMIN_JS', AALB_PLUGIN_URL . 'admin/js/aalb_admin.js' );
define( 'AALB_TEMPLATE_JS', AALB_PLUGIN_URL . 'admin/sidebar/js/aalb_template.js' );
define( 'AALB_CREDENTIALS_JS', AALB_PLUGIN_URL . 'admin/sidebar/js/aalb_credentials.js' );

//Templates
define( 'AALB_ADMIN_ITEM_SEARCH_ITEMS_PATH', AALB_PLUGIN_DIR . 'aalb_admin_item_search_items.php' );

//External Scripts
define( 'HANDLEBARS_JS', 'https://d8fd03967nrad.cloudfront.net/libs/handlebars.js/4.0.5/handlebars.min.js' );
define( 'CODEMIRROR_JS', 'https://d8fd03967nrad.cloudfront.net/libs/codemirror/5.13.2/codemirror.min.js' );
define( 'CODEMIRROR_MODE_XML_JS', 'https://d8fd03967nrad.cloudfront.net/libs/codemirror/5.13.2/mode/xml/xml.min.js' );
define( 'CODEMIRROR_MODE_CSS_JS', 'https://d8fd03967nrad.cloudfront.net/libs/codemirror/5.13.2/mode/css/css.min.js' );
define( 'AALB_JQUERY_UI_JS', 'https://d8fd03967nrad.cloudfront.net/libs/jQueryUI/1.12.1/jquery-ui.js' );
define( 'AALB_JQUERY_UI_CSS', 'https://d8fd03967nrad.cloudfront.net/libs/jQueryUI/1.12.1/themes/ui-lightness/jquery-ui.css' );

//External Styles
define( 'FONT_AWESOME_CSS', 'https://d8fd03967nrad.cloudfront.net/libs/font-awesome/4.5.0/css/font-awesome.min.css' );
define( 'CODEMIRROR_CSS', 'https://d8fd03967nrad.cloudfront.net/libs/codemirror/5.13.2/codemirror.min.css' );

/**
 * Icons
 */
define( 'AALB_SECURE_HOSTNAME', 'https://images-na.ssl-images-amazon.com/' );
define( 'AALB_NORMAL_HOSTNAME', 'http://g-ecx.images-amazon.com/' );
define( 'AALB_ICON_LOCATION', 'images/G/01/PAAPI/AmazonAssociatesLinkBuilder/icon-2._V276841048_.png' );
define( 'AALB_ADMIN_ICON_LOCATION', 'images/G/01/PAAPI/AmazonAssociatesLinkBuilder/amazon_icon._V506839993_.png' );
//AALB_ICON URL is generated by wordpress at run-time by checking the remotehost's encryption. Image source has different URLs depending upon the encryption used.
if ( is_ssl() ) {
    define( 'AALB_ICON', AALB_SECURE_HOSTNAME . AALB_ICON_LOCATION );
} else {
    define( 'AALB_ICON', AALB_NORMAL_HOSTNAME . AALB_ICON_LOCATION );
}
define( 'AALB_ADMIN_ICON', AALB_SECURE_HOSTNAME . AALB_ADMIN_ICON_LOCATION );


/**
 * Constants
 */

//Search result items. Paapi returns 10 items by default.
define( 'AALB_MAX_SEARCH_RESULT_ITEMS', 9 );
//List of Default Amazon Template names
define( 'AALB_AMAZON_TEMPLATE_NAMES', 'ProductCarousel,ProductGrid,ProductAd,PriceLink,ProductLink' );

//Database keys
define( 'AALB_TEMPLATE_NAMES', 'aalb_template_names' );
define( 'AALB_MARKETPLACE_NAMES', 'aalb_marketplace_names' );
define( 'AALB_DEFAULT_TEMPLATE', 'aalb_default_template' );
define( 'AALB_DEFAULT_STORE_ID', 'aalb_default_store_id' );
define( 'AALB_DEFAULT_MARKETPLACE', 'aalb_default_marketplace' );
define( 'AALB_AWS_ACCESS_KEY', 'aalb_aws_access_key' );
define( 'AALB_AWS_SECRET_KEY', 'aalb_aws_secret_key' );
define( 'AALB_CRED_CONFIG_GROUP', 'aalb_cred_config_group' );
define( 'AALB_STORE_ID_NAMES', 'aalb_store_id_names' );
define( 'AALB_STORE_IDS', 'aalb_store_ids' );
define( 'AALB_CUSTOM_UPLOAD_PATH', 'aalb_custom_upload_path' );
define( 'AALB_SHOW_HTTP_WARNING_ONCE', 'aalb_show_http_warning_once' );
define( 'AALB_PLUGIN_VERSION', 'aalb_plugin_version' );
define( 'AALB_NO_REFERRER_DISABLED', 'aalb_no_referrer_disabled' );
define( 'AALB_GEOLITE_DB_EXPIRATION_TIME', 'aalb_geolite_db_expiration_time' );
define( 'AALB_GEOLITE_DB_LAST_UPDATED_TIME', 'aalb_geolite_db_last_updated_time' );

//Masking constant
define( 'AALB_AWS_SECRET_KEY_MASK', '••••••••••••••••••••••••••••••••••••••••' );


//Defaults in case DB doesn't contain them.
define( 'AALB_DEFAULT_TEMPLATE_NAME', 'ProductCarousel' );
define( 'AALB_DEFAULT_MARKETPLACE_NAME', 'US' );
define( 'AALB_DEFAULT_STORE_ID_NAME', 'not-specified' );

//Marketplaces
define( 'MARKETPLACES_URL', 'https://webservices.amazon.com/scratchpad/assets/config/config.json' );

//PAAPI
define( 'PAAPI_URI', '/onca/xml' );
define( 'PAAPI_TRANSFER_PROTOCOL', 'https://' );
define( 'PAAPI_URL_QUERY_SEPARATOR', '?' );
define( 'PAAPI_SERVICE', 'AWSECommerceService' );
define( 'PAAPI_VERSION', '2013-08-01' );
define( 'PAAPI_URL_REGEX', '^https:\\/\\/(.*)\\/onca\\/xml\\?(.*)$' );

define( 'PAAPI_INVALID_PARAMETER_VALUE_ERROR', 'AWS.InvalidParameterValue' );

// PAAPI Request Timeout in seconds
define( "PAAPI_REQUEST_TIMEOUT", 35 );

//HTTP Status Codes
define( 'HTTP_SUCCESS', '200' );
define( 'HTTP_BAD_REQUEST', '400' );
define( 'HTTP_REQUEST_URI_TOO_LONG', '414' );
define( 'HTTP_FORBIDDEN', '403' );
define( 'HTTP_INTERNAL_SERVER_ERROR', '500' );
define( 'HTTP_THROTTLE', '503' );
define( 'HTTP_TIME_OUT', '504' );

/**
 * Cipher
 */
//Make a key of length 32 byte.
//Specify your unique encryption key here.
define( 'AALB_ENCRYPTION_KEY', 'put your unique phrase here' );
//Default Encryption Key. Do NOT change this.
define( 'AALB_ENCRYPTION_KEY_DEFAULT', 'put your unique phrase here' );

//Make IV of 16 bytes
define( 'AALB_ENCRYPTION_IV', '0123456789ABCDEF' );

//Algorithm to use
define( 'AALB_ENCRYPTION_ALGORITHM', 'aes-256-cbc' );

//Caching Requirements
//====================
//As defined by the Product Advertising API License Agreement at https://affiliate-program.amazon.com/gp/advertising/api/detail/agreement.html,
//Dated Jul 22, 2016, Section 4(n) and 4(o), caching of product information is permitted upto a maximum of 24-hours.
//Further, if the product price is not refreshed every one hour, the displayed price should be accompanied with a timestamp when the price was read.
//Note that the plugin uses a two tier cache. It caches the ASINs as well as the rendered templates.
//At any given time the sum of ASIN cache TTL and Rendered AdUnit cache TTL should be less than or equal to one hour.
//The below configuration is compliant with the License Agreement. Any modification may result in the violation of the license agreement.
define( 'AALB_CACHE_FOR_ASIN_RAWINFO_TTL', 30 * MINUTE_IN_SECONDS );
define( 'AALB_CACHE_FOR_ASIN_ADUNIT_TTL', 30 * MINUTE_IN_SECONDS );

//Translation keys
define( 'CHECK_ON_AMAZON', 'Check on Amazon' );
define( 'OUT_OF_STOCK', 'Out of stock' );

//Marketplaces supported for translations
define( 'US', 'US' );
define( 'FR', 'FR' );
define( 'IT', 'IT' );
define( 'DE', 'DE' );
define( 'ES', 'ES' );
define( 'BR', 'BR' );
define( 'CA', 'CA' );
define( 'CN', 'CN' );
define( 'IN', 'IN' );
define( 'JP', 'JP' );
define( 'MX', 'MX' );
define( 'UK', 'UK' );

//Amazon URLs To be used in Aalb_Content_Filter.
$AALB_AMAZON_DOMAINS = array(
    'amazon\.com',
    'amazon\.fr',
    'amazon\.it',
    'amazon\.de',
    'amazon\.es',
    'amazon\.com\.br',
    'amazon\.ca',
    'amazon\.cn',
    'amazon\.in',
    'amazon\.co\.jp',
    'amazon\.com\.mx',
    'amazon\.co\.uk',
    'amzn\.to'
);

//Wordpress Server Timeout in milliseconds
define( "AALB_WORDPRESS_REQUEST_TIMEOUT", 40000 );

//Curl Timeout Error String
define( 'CURL_ERROR_TIMEOUT_STRING', 'cURL error 28' );

//Support Email-Id
define( 'AALB_SUPPORT_EMAIL_ID', 'link-builder@amazon.com' );

//Plugin Specific URLs
define( 'AALB_CONDITIONS_OF_USE_URL', 'https://s3.amazonaws.com/aalb-public-resources/documents/AssociatesLinkBuilder-ConditionsOfUse-2017-01-17.pdf' );
define( 'AALB_USER_GUIDE_URL', 'https://s3.amazonaws.com/aalb-public-resources/documents/AssociatesLinkBuilder-UserGuide.pdf' );
define( 'AALB_SUPPORT_FORUM_URL', 'https://wordpress.org/support/plugin/amazon-associates-link-builder' );

//Associate URLs
define( 'AALB_GETTING_STARTED_URL', 'http://docs.aws.amazon.com/AWSECommerceService/latest/DG/CHAP_GettingStarted.html' );
define( 'AALB_AFFILIATE_WEBSITE_URL', 'https://affiliate-program.amazon.com' );
define( 'AALB_ADDING_SECONDARY_USER_AC_URL', 'https://affiliate-program.amazon.com/help/node/topic/202049770' );
define( 'AALB_ASSOCIATE_SIGN_UP_URL', 'http://docs.aws.amazon.com/AWSECommerceService/latest/DG/becomingAssociate.html' );

//PAAPI URLs
define( 'AALB_PAAPI_SIGN_UP_URL', 'http://docs.aws.amazon.com/AWSECommerceService/latest/DG/becomingDev.html' );
define( 'AALB_MANAGE_PAAPI_US_ACCOUNT_URL', 'https://affiliate-program.amazon.com/gp/advertising/api/detail/your-account.html' );
define( 'AALB_PAAPI_EFFICIENCY_GUIDELINES_URL', 'http://docs.aws.amazon.com/AWSECommerceService/latest/DG/TroubleshootingApplications.html#efficiency-guidelines' );

//Plugin specific URLS
define( 'AALB_SETTINGS_PAGE_URL', admin_url( 'admin.php?page=associates-link-builder-settings' ) );

//Maxmind GeoLite2Country DB Download URL
define('AALB_GEOLITE_COUNTRY_DB_DOWNLOAD_URL', 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz');
define('AALB_GEOLITE_COUNTRY_DB_DETAILS_URL','https://dev.maxmind.com/geoip/geoip2/geolite2/');
define( 'AALB_NEW_PAGE_TARGET', "_blank" );

define( 'AALB_NEWLINE_SEPARATOR', "\r\n" );
define('GEO_TARGETED_LINKS_DELIMITER', "|");

//Pugin Link Codes
define('AALB_DEFAULT_LINK_CODE', "alb");
define('AALB_GEO_TARGETED_LINKS_DEFAULT_COUNTRY_LINK_CODE', "al0");
define('AALB_GEO_TARGETED_LINKS_REDIRECTED_COUNTRY_LINK_CODE', "al1");

?>
