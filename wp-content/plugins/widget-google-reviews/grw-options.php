<?php if (isset($title)) { ?>
<div class="form-group">
    <div class="col-sm-12">
        <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>" class="form-control" placeholder="<?php echo grw_i('Widget title'); ?>" />
    </div>
</div>
<?php } ?>

<div class="form-group">
    <div class="col-sm-12">
        <input type="text" id="<?php echo $this->get_field_id('place_name'); ?>" name="<?php echo $this->get_field_name('place_name'); ?>" value="<?php echo $place_name; ?>" class="form-control grw-google-place-name" placeholder="<?php echo grw_i('Google Place Name'); ?>" readonly />
    </div>
</div>

<div class="form-group">
    <div class="col-sm-12">
        <input type="text" id="<?php echo $this->get_field_id('place_id'); ?>" name="<?php echo $this->get_field_name('place_id'); ?>" value="<?php echo $place_id; ?>" class="form-control grw-google-place-id" placeholder="<?php echo grw_i('Google Place ID'); ?>" readonly />
    </div>
</div>

<!-- Review Options -->
<h4 class="rplg-options-toggle"><?php echo grw_i('Review Options'); ?></h4>
<div class="rplg-options" style="display:none">
    <div class="form-group rplg-disabled">
        <div class="col-sm-12">
            <label>
                <input class="form-control" type="checkbox" disabled />
                <?php echo grw_i('Try to get more than 5 Google reviews'); ?>
            </label>
        </div>
    </div>
    <div class="form-group rplg-disabled">
        <div class="col-sm-12">
            <label>
                <input class="form-control" type="checkbox" disabled />
                <?php echo grw_i('Enable Google Rich Snippet (schema.org)'); ?>
            </label>
        </div>
    </div>
    <div class="form-group rplg-disabled">
        <div class="col-sm-12">
            <?php echo grw_i('Pagination'); ?>
            <select class="form-control" disabled >
                <option value=""><?php echo grw_i('Disabled'); ?></option>
                <option value="10"><?php echo grw_i('10'); ?></option>
                <option value="5"><?php echo grw_i('5'); ?></option>
                <option value="4"><?php echo grw_i('4'); ?></option>
                <option value="3"><?php echo grw_i('3'); ?></option>
                <option value="2"><?php echo grw_i('2'); ?></option>
                <option value="1"><?php echo grw_i('1'); ?></option>
            </select>
        </div>
    </div>
    <div class="form-group rplg-disabled">
        <div class="col-sm-12">
            <?php echo grw_i('Sorting'); ?>
            <select class="form-control" disabled >
                <option value=""><?php echo grw_i('Default'); ?></option>
                <option value="1"><?php echo grw_i('Most recent'); ?></option>
                <option value="2"><?php echo grw_i('Most oldest'); ?></option>
                <option value="3"><?php echo grw_i('Highest score'); ?></option>
                <option value="4"><?php echo grw_i('Lowest score'); ?></option>
            </select>
        </div>
    </div>
    <div class="form-group rplg-disabled">
        <div class="col-sm-12">
            <?php echo grw_i('Minimum Review Rating'); ?>
            <select class="form-control" disabled >
                <option value=""><?php echo grw_i('No filter'); ?></option>
                <option value="5"><?php echo grw_i('5 Stars'); ?></option>
                <option value="4"><?php echo grw_i('4 Stars'); ?></option>
                <option value="3"><?php echo grw_i('3 Stars'); ?></option>
                <option value="2"><?php echo grw_i('2 Stars'); ?></option>
                <option value="1"><?php echo grw_i('1 Star'); ?></option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <div class="rplg-pro">
            <?php echo grw_i('These features are available in Google Reviews Business plugin: '); ?>
            <a href="https://richplugins.com/google-reviews-pro-wordpress-plugin" target="_blank">
                <?php echo grw_i('Upgrade to Business'); ?>
            </a>
        </div>
    </div>
</div>

<!-- Display Options -->
<h4 class="rplg-options-toggle"><?php echo grw_i('Display Options'); ?></h4>
<div class="rplg-options" style="display:none">
    <div class="form-group rplg-disabled">
        <div class="col-sm-12">
            <label>
                <?php echo grw_i('Custom Place photo'); ?>
            </label>
        </div>
    </div>
    <div class="form-group rplg-disabled">
        <div class="col-sm-12">
            <label>
                <input class="form-control" type="checkbox" disabled />
                <?php echo grw_i('Hide business photo'); ?>
            </label>
        </div>
    </div>
    <div class="form-group rplg-disabled">
        <div class="col-sm-12">
            <label>
                <input class="form-control" type="checkbox" disabled />
                <?php echo grw_i('Hide user avatars'); ?>
            </label>
        </div>
    </div>
    <div class="form-group rplg-disabled">
        <div class="col-sm-12">
            <label>
                <input class="form-control" type="checkbox" disabled />
                <?php echo grw_i('Disable links to G+ user profile'); ?>
            </label>
        </div>
    </div>
    <div class="form-group rplg-disabled">
        <div class="col-sm-12">
            <label>
                <input class="form-control" type="checkbox" disabled />
                <?php echo grw_i('Enable \'Write a review\' button'); ?>
            </label>
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-12">
            <label>
                <input id="<?php echo $this->get_field_id('dark_theme'); ?>" name="<?php echo $this->get_field_name('dark_theme'); ?>" type="checkbox" value="1" <?php checked('1', $dark_theme); ?> class="form-control" />
                <?php echo grw_i('Dark background'); ?>
            </label>
        </div>
    </div>
    <div class="form-group rplg-disabled">
        <div class="col-sm-12">
            <label><?php echo grw_i('Review limit before \'read more\' link'); ?></label>
            <input class="form-control" type="text" placeholder="for instance: 120" disabled />
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-12">
            <?php echo grw_i('Widget theme'); ?>
            <select id="<?php echo $this->get_field_id('view_mode'); ?>" name="<?php echo $this->get_field_name('view_mode'); ?>" class="form-control">
                <option value="list" <?php selected('list', $view_mode); ?>><?php echo grw_i('Review list'); ?></option>
                <option value="slider" <?php selected('slider', $view_mode); ?> disabled><?php echo grw_i('Reviews Slider'); ?></option>
                <option value="grid" <?php selected('grid', $view_mode); ?> disabled><?php echo grw_i('Reviews Grid'); ?></option>
                <option value="badge" <?php selected('badge', $view_mode); ?> disabled><?php echo grw_i('Google Badge: right'); ?></option>
                <option value="badge_left" <?php selected('badge_left', $view_mode); ?> disabled><?php echo grw_i('Google Badge: left'); ?></option>
                <option value="badge_inner" <?php selected('badge_inner', $view_mode); ?> disabled><?php echo grw_i('Google Badge: embed'); ?></option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <div class="rplg-pro">
            <?php echo grw_i('<b>Slider</b>, <b>Grid</b>, <b>Badge</b> themes and other features are available in Google Reviews Business plugin: '); ?>
            <a href="https://richplugins.com/google-reviews-pro-wordpress-plugin" target="_blank">
                <?php echo grw_i('Upgrade to Business'); ?>
            </a>
        </div>
    </div>
    <?php if (isset($max_width)) { ?>
    <div class="form-group">
        <div class="col-sm-12">
            <label for="<?php echo $this->get_field_id('max_width'); ?>"><?php echo grw_i('Maximum width'); ?></label>
            <input id="<?php echo $this->get_field_id('max_width'); ?>" name="<?php echo $this->get_field_name('max_width'); ?>" class="form-control" type="text" placeholder="for instance: 300px" />
        </div>
    </div>
    <?php } ?>
    <?php if (isset($max_height)) { ?>
    <div class="form-group">
        <div class="col-sm-12">
            <label for="<?php echo $this->get_field_id('max_height'); ?>"><?php echo grw_i('Maximum height'); ?></label>
            <input id="<?php echo $this->get_field_id('max_height'); ?>" name="<?php echo $this->get_field_name('max_height'); ?>" class="form-control" type="text" placeholder="for instance: 500px" />
        </div>
    </div>
    <?php } ?>
</div>

<!-- Slider Options -->
<h4 class="rplg-options-toggle"><?php echo grw_i('Slider Options'); ?></h4>
<div class="rplg-options" style="display:none">
    <div class="form-group">
        <div class="rplg-pro">
            <?php echo grw_i('<b>Slider</b> is available in Google Reviews Business plugin: '); ?>
            <a href="https://richplugins.com/google-reviews-pro-wordpress-plugin" target="_blank">
                <?php echo grw_i('Upgrade to Business'); ?>
            </a>
        </div>
    </div>
</div>

<!-- Advance Options -->
<h4 class="rplg-options-toggle"><?php echo grw_i('Advance Options'); ?></h4>
<div class="rplg-options" style="display:none">
    <div class="form-group">
        <div class="col-sm-12">
            <label>
                <input id="<?php echo $this->get_field_id('open_link'); ?>" name="<?php echo $this->get_field_name('open_link'); ?>" type="checkbox" value="1" <?php checked('1', $open_link); ?> class="form-control" />
                <?php echo grw_i('Open links in new Window'); ?>
            </label>
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-12">
            <label>
                <input id="<?php echo $this->get_field_id('nofollow_link'); ?>" name="<?php echo $this->get_field_name('nofollow_link'); ?>" type="checkbox" value="1" <?php checked('1', $nofollow_link); ?> class="form-control" />
                <?php echo grw_i('Use no follow links'); ?>
            </label>
        </div>
    </div>
</div>