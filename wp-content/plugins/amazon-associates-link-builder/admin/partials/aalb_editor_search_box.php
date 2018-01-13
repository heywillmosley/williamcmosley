<!--
Copyright 2016-2017 Amazon.com, Inc. or its affiliates. All Rights Reserved.

Licensed under the GNU General Public License as published by the Free Software Foundation,
Version 2.0 (the "License"). You may not use this file except in compliance with the License.
A copy of the License is located in the "license" file accompanying this file.

This file is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
either express or implied. See the License for the specific language governing permissions
and limitations under the License.
*/
-->

<?php
$aalb_settings_page_url = admin_url( 'admin.php?page=associates-link-builder-settings' );
$aalb_admin = new Aalb_Admin();
$aalb_admin->aalb_enqueue_styles();
$aalb_admin->aalb_enqueue_scripts();
?>

<!--

  UI for Search box shown in WordPress editors. User can type in keyword and trigger add short code box.

-->

<div class="aalb-admin-inline aalb-admin-searchbox">
    <span class="aalb-admin-editor-tooltip aalb-admin-hide-display"></span>
    <img src=<?= AALB_ADMIN_ICON ?> class="aalb-admin-searchbox-amzlogo">
    <input type="text" class="aalb-admin-input-search" name="aalb-admin-input-search" placeholder="<?php esc_attr_e( "Enter keyword(s)", 'amazon-associates-link-builder' ) ?>" />
    <a class="button aalb-admin-button-create-amazon-shortcode" title="<?php esc_attr_e( "Add Amazon Associates Link Builder Shortcode", 'amazon-associates-link-builder' ) ?>">
        <?php esc_html_e( "Search", 'amazon-associates-link-builder' ) ?>
    </a>
</div>