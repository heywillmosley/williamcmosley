<?php

class FrmSigAppController{
    function __construct(){
        add_action( 'init', 'FrmSigAppController::load_hooks', 9 );
        add_action( 'admin_init', 'FrmSigAppController::include_updater', 1 );
    }

    public static function load_hooks(){
        add_filter( 'frm_pre_display_form', 'FrmSigAppController::register_scripts' );
        add_action( 'wp_footer', 'FrmSigAppController::footer_js', 20 );
        add_action( 'admin_footer', 'FrmSigAppController::footer_js', 20 );
        add_filter( 'frm_available_fields', 'FrmSigAppController::maybe_add_field' );
        add_filter( 'frm_before_field_created', 'FrmSigAppController::set_defaults' );
        add_action( 'frm_display_added_fields', 'FrmSigAppController::admin_field' );
        add_action( 'frm_field_options_form', 'FrmSigAppController::options_form', 10, 1 );
        add_filter( 'frm_update_field_options', 'FrmSigAppController::update', 10, 3 );

        add_filter( 'frm_setup_new_fields_vars', 'FrmSigAppController::check_signature_fields', 20, 2 );
        add_filter( 'frm_setup_edit_fields_vars', 'FrmSigAppController::check_signature_fields', 20, 3 );
        add_action( 'frm_form_fields', 'FrmSigAppController::front_field', 10, 2 );
        add_filter( 'frm_validate_field_entry', 'FrmSigAppController::validate', 9, 3 );

		add_action( 'frm_after_create_entry', 'FrmSigAppController::maybe_create_signature_files', 10, 2 );

        add_filter( 'frm_email_value', 'FrmSigAppController::email_value', 8, 2 );
        add_filter( 'frmpro_fields_replace_shortcodes', 'FrmSigAppController::custom_display_signature', 10, 4 );
        add_filter( 'frm_display_value', 'FrmSigAppController::display_signature', 10, 3 );
        add_filter( 'frm_csv_value', 'FrmSigAppController::csv_value', 10, 2 );
        add_filter( 'frm_graph_value', 'FrmSigAppController::graph_value', 10, 2 );
        add_action( 'frm_before_destroy_entry', 'FrmSigAppController::delete_images' );
    }

    public static function path(){
        return WP_PLUGIN_DIR .'/formidable-signature';
    }

	public static function register_scripts( $form ){
		$url = plugins_url( '', basename( dirname( dirname( __FILE__ ) ) ) .'/signature.php' );

		add_filter( 'frm_ajax_load_scripts', 'FrmSigAppController::ajax_load_scripts' );
		add_filter( 'frm_ajax_load_styles', 'FrmSigAppController::ajax_load_styles' );

		wp_register_script( 'flashcanvas', $url .'/js/flashcanvas.js', array('jquery'), '1.5', true );
		wp_register_script( 'jquery-signaturepad', $url .'/js/jquery.signaturepad.min.js', array('jquery', 'flashcanvas', 'json2'), '2.5.0', true );
		wp_register_script( 'frm-signature', $url .'/js/frm.signature.js', array('jquery-signaturepad') );
		wp_register_style( 'jquery-signaturepad', $url .'/css/jquery.signaturepad.css' );

		return $form;
	}

	/**
	 * Make sure these scripts are loaded on ajax page change if enqueued
	 */
	public static function ajax_load_scripts( $scripts ) {
		$scripts = array_merge( $scripts, array( 'flashcanvas', 'jquery-signaturepad', 'frm-signature' ) );
		return $scripts;
	}

	/**
	 * Make sure these styles are loaded on ajax page change if enqueued
	 */
	public static function ajax_load_styles( $styles ) {
		$styles[] = 'jquery-signaturepad';
		return $styles;
	}

	public static function maybe_add_field( $fields ) {
		if ( FrmAppHelper::pro_is_installed() ) {
			add_filter( 'frm_pro_available_fields', 'FrmSigAppController::add_field' );
		} else {
			$fields = self::add_field( $fields );
		}
		return $fields;
	}

    public static function add_field( $fields ) {
        $fields['signature'] = __( 'Signature', 'frmsig' );
        return $fields;
    }

    public static function set_defaults( $field_data ) {
        if ( $field_data['type'] == 'signature' ) {
            $field_data['name'] = __( 'Signature' , 'frmsig' );
            foreach ( self::get_defaults() as $k => $v ) {
                $field_data['field_options'][ $k ] = $v;
            }
        }

        return $field_data;
    }

    public static function get_defaults(){
        return array(
            'size' => 400, 'max' => 150, 'restrict' => false,
            'label1' => __( 'Draw It', 'frmsig' ),
            'label2' => __( 'Type It', 'frmsig' ),
            'label3' => __( 'Clear', 'frmsig' ),
        );
    }

    public static function admin_field( $field ) {
        if ( $field['type'] != 'signature' ) {
            return;
        }

        include( self::path() .'/views/admin_field.php' );
    }

    public static function options_form( $field ) {
        if ( $field['type'] != 'signature' ) {
            return;
        }

        foreach ( self::get_defaults() as $k => $v ) {
            if ( ! isset( $field[ $k ] ) ) {
                $field[ $k ] = $v;
            }
        }

        include( self::path() .'/views/options_form.php' );
    }

    /**
     * If this form uses ajax, we need all the signature field info in advance
     */
    public static function check_signature_fields ( $values, $field, $entry_id = false ) {
        if ( $field->type != 'signature' && ! isset( $field->field_options['label1'] ) ) {
            return $values;
        }

		// Make sure signature value is formatted correctly
		self::check_signature_value_format( $values['value'] );

		// If the signature has already been saved, don't load scripts for this field
        if ( $entry_id && is_array( $values['value'] ) && self::isset_and_not_empty_in_array( $values['value'], 'output' ) ) {
            return $values;
        }

        global $frm_vars;
        if ( ! is_array( $frm_vars ) ) {
            $frm_vars = array();
        }

        if ( ! isset( $frm_vars['sig_fields'] ) || empty( $frm_vars['sig_fields'] ) ) {
            $frm_vars['sig_fields'] = array();
        }

        $style_settings = self::get_style_settings( $field->form_id  );

        $values['size'] = ( ! empty( $values['size'] ) && $values['size'] > 70 ) ? $values['size'] : 400;

        $frm_vars['sig_fields'][] = array(
            'id'            => $field->id,
            'width'         => $values['size'],
            'bg_color'      => '#'. $style_settings['bg_color'],
            'text_color'    => '#'. $style_settings['text_color'],
            'border_color'  => '#'. $style_settings['border_color'],
        );

        return $values;
    }

	/**
	 * Check if an item isset and is not empty in an array
	 *
	 * @since 1.08
	 * @param array $array
	 * @param string $key
	 * @return bool
	 */
	private static function isset_and_not_empty_in_array( $array, $key ) {
		return isset( $array[ $key ] ) && ! empty( $array[ $key ] );
	}

	/**
	 * Check the value for a signature field. Make sure it is an array if the value is not empty.
	 *
	 * @since 1.08
	 * @param array|string $value
	 */
	private static function check_signature_value_format( &$value ) {
		if ( $value ) {
			$value = maybe_unserialize( $value );

			if ( is_array( $value ) ) {
				// Make sure the 'output' signature isn't saved as the 'typed' value
				if ( isset( $value['typed'] ) && strpos( $value['typed'], '[{"lx":' ) !== false ) {
					if ( ! isset( $value['output'] ) || empty( $value['output'] ) ) {
						$value['output'] = $value['typed'];
					}
					$value['typed'] = '';
				}
			} else {
				// Fix values that were incorrectly saved as strings
				if ( strpos( $value, '[{"lx":' ) !== false ) {
					$value = array( 'output' => $value );
				} else {
					$value = array( 'typed' => $value );
				}
			}
		}
	}

    public static function front_field( $field, $field_name ) {
        if ( $field['type'] != 'signature' ) {
            return;
        }

        if ( ! isset( $field['label1'] ) ) {
            $field_obj = FrmField::getOne( $field['id'] );

            foreach ( self::get_defaults() as $k => $v ) {
                if ( ! isset( $field[ $k ] ) ) {
                    $field[ $k ] = isset( $field_obj->field_options[ $k ] ) ? $field_obj->field_options[ $k ] : $v;
                }
            }

            unset($field_obj);
        }

        global $frm_editing_entry, $frm_vars;

        $entry_id = isset($frm_vars['editing_entry']) ? $frm_vars['editing_entry'] : $frm_editing_entry;
        if ( $entry_id ) {
            //make sure entry is for this form
            $entry = FrmEntry::getOne( (int) $entry_id );

            if ( ! $entry || $entry->form_id != $field['form_id'] ) {
                $entry_id = false;
            }
            unset($entry);
        }

        wp_enqueue_script( 'flashcanvas' );
        wp_enqueue_script( 'jquery-signaturepad' );
        wp_enqueue_style( 'jquery-signaturepad' );
        wp_enqueue_script( 'frm-signature' );

        $style_settings = self::get_style_settings( $field['form_id']  );

        $field['value'] = stripslashes_deep($field['value']);

		$width = self::get_signature_image_width( $field['size'] );
		$height = self::get_signature_image_height( $field['max'] );

        require(self::path() .'/views/front_field.php');
    }

    public static function footer_js(){
        global $frm_vars;

        if ( ! is_array( $frm_vars ) || ! isset( $frm_vars['sig_fields'] ) || empty( $frm_vars['sig_fields'] ) ) {
            return;
        }

        include_once( self::path() .'/views/footer_js.php' );
    }

    public static function update( $field_options, $field, $values ) {
        if ( $field->type != 'signature' ) {
            return $field_options;
        }

        $defaults = self::get_defaults();
        unset( $defaults['size'], $defaults['max'], $defaults['restrict'] );

        foreach ( $defaults as $opt => $default ) {
            $field_options[ $opt ] = isset( $values['field_options'][ $opt .'_'. $field->id ] ) ? $values['field_options'][ $opt .'_'. $field->id ] : $default;
        }

        return $field_options;
    }

    public static function validate( $errors, $field, $value ) {
        if ( $field->type != 'signature' || $field->required != '1' || isset( $errors['field'. $field->id] ) ) {
            return $errors;
        }

        if ( empty( $value ) || ( empty( $value['output'] ) && empty( $value['typed'] ) ) ) {
            if ( method_exists( 'FrmProFieldsHelper', 'is_field_hidden' ) ) {
                $hidden = FrmProFieldsHelper::is_field_hidden( $field, $_POST );
            } else {
                $hidden = false;
            }

            if ( ! $hidden ) {
                global $frm_settings;
                $errors['field'. $field->id] = ( ! isset( $field->field_options['blank'] ) || empty( $field->field_options['blank'] ) ) ? $frm_settings->blank_msg : $field->field_options['blank'];
            }
        }

        return $errors;
    }

	/**
	 * Check if there are any signatures that need to be created when the entry is created
	 *
	 * @since 1.07.03
	 * @param $entry_id
	 * @param $form_id
	 */
	public static function maybe_create_signature_files( $entry_id, $form_id ){
		$sig_fields = FrmField::get_all_types_in_form( $form_id, 'signature' );

		foreach ( $sig_fields as $sig_field ) {
			global $wpdb;
			$where = array( 'field_id' => $sig_field->id, 'item_id' => $entry_id );
			$sig_value = FrmDb::get_var( $wpdb->prefix . 'frm_item_metas', $where, 'meta_value' );

			if ( $sig_value ) {
				self::create_signature_file( $sig_value, $sig_field, $entry_id );
			}
		}
	}

	/**
	 * Create the signature file
	 *
	 * @since 1.07.03
	 * @param $value
	 * @param $field
	 * @param $entry_id
	 */
	public static function create_signature_file( $value, $field, $entry_id ) {
		self::check_signature_value_format( $value );

		if ( ! isset( $value['output'] ) || empty( $value['output'] ) ) {
			return;
		}

		$width = self::get_signature_image_width( $field->field_options['size'] );
		$height = self::get_signature_image_height( $field->field_options['max'] );

		$file_path = self::get_full_path_to_signature( $field->id, $entry_id );

		if ( file_exists( $file_path ) ) {
			// File was already created, possibly when a draft was saved
			return;
		}

		require_once( self::path() .'/signature-to-image.php' );

		$options = array();
		$options['bgColour'] = 'transparent';

		if ( is_numeric( $height ) ) {
			$options['imageSize'] = array( (int) $width, (int) $height );
			$options['drawMultiplier'] = apply_filters( 'frm_sig_multiplier', 5, $field, $value );
		}

		$options = apply_filters( 'frm_sig_output_options', $options, compact( 'field' ) );
		$img = sigJsonToImage( $value['output'], $options );
		if ( $img ) {
			imagepng( $img, $file_path );
			imagedestroy( $img );
		}
	}

	/**
	 * Get the width for the signature field
	 *
	 * @since 1.07.03
	 * @param int $size
	 * @return int
	 */
	private static function get_signature_image_width( $size ) {
		return ( ! empty( $size ) ) ? $size : 400;
	}

	/**
	 * Get the height for a signature field
	 *
	 * @since 1.07.03
	 * @param int $max
	 * @return int
	 */
	private static function get_signature_image_height( $max ) {
		return ( ! empty( $max ) ) ? $max : 150;
	}

	/**
	 * Get the signature value for the email message and frm-show-entry shortcode
	 *
	 * @param mixed $value
	 * @param object $meta
	 * @return mixed $value
	 */
	public static function email_value( $value, $meta ) {
		if ( $meta->field_type == 'signature' ) {
			$args = array(
				'field_id' => $meta->field_id,
				'entry_id' => $meta->item_id,
				'use_html' => true,
			);

			$value = self::get_final_signature_value( $value, $args );
		}

		return $value;
	}

    public static function custom_display_signature( $value, $tag, $atts, $field ) {
        if ( $field->type != 'signature' ) {
            return $value;
        }

        if ( ! isset( $atts['entry_id'] ) ) {
            return '';
        }

		return self::display_signature( $value, $field, $atts );
    }

    public static function display_signature( $value, $field, $atts ) {
        if ( $field->type != 'signature' || empty( $value ) ) {
            return $value;
        }

		$sig_args = array(
			'field_id' => $field->id,
			'entry_id' => $atts['entry_id'],
			'use_html' => true,
		);

		return self::get_final_signature_value( $value, $sig_args );
    }

	/**
	 * Get the typed or written signature value with or without HTML added
	 *
	 * @since 1.07.03
	 * @param $value
	 * @param $args
	 * @return string
	 */
	public static function get_final_signature_value( $value, $args ) {
		self::check_signature_value_format( $value );

		if ( ! isset( $value['output'] ) || empty( $value['output'] ) ) {
			// Return typed signature
			$sig_value = isset( $value['typed'] ) ? $value['typed'] : reset( $value );
		} else {
			// Get signature file
			$file_path = self::get_full_path_to_signature( $args['field_id'], $args['entry_id'] );

			if ( ! file_exists( $file_path ) ) {
				// Back-up signature file creation
				$sig_field = FrmField::getOne( $args['field_id'] );
				self::create_signature_file( $value, $sig_field, $args['entry_id'] );
			}

			$sig_value = self::get_signature_url( $file_path );

			if ( $args['use_html'] ) {
				$sig_value = '<img src="' . esc_attr( $sig_value ) . '" />';
			}

		}

		return $sig_value;
	}

    public static function csv_value( $value, $atts ) {
        if ( $atts['field']->type != 'signature' ) {
            return $value;
        }

		$args = array(
			'field_id' => $atts['field']->id,
			'entry_id' => $atts['entry']->id,
			'use_html' => false,
		);

		return self::get_final_signature_value( $value, $args );
    }

    public static function graph_value( $values, $field ) {
        if ( ! is_object( $field ) || $field->type != 'signature' ) {
            return $values;
        }

        foreach ( $values as $k => $v ) {
            if ( is_array($v) ) {
                $values[ $k ] = isset( $v['typed'] ) ? $v['typed'] : reset($v);
            }
            unset($k, $v);
        }

        return $values;
    }

    public static function delete_images( $entry_id ) {
        global $wpdb;

        $fields = $wpdb->get_col( $wpdb->prepare("SELECT fi.id FROM {$wpdb->prefix}frm_fields fi LEFT JOIN {$wpdb->prefix}frm_items it ON (it.form_id=fi.form_id) WHERE fi.type=%s AND it.id=%d", 'signature', $entry_id) );

        if ( ! $fields ) {
            return;
        }

        $uploads = wp_upload_dir();
        $target_path = $uploads['basedir'] .'/'; 
        $target_path .= apply_filters( 'frm_sig_upload_folder', 'formidable/signatures' );
        $target_path = untrailingslashit( $target_path );

        foreach ( $fields as $field ) {
            $file = $target_path . '/signature-'. $field .'-'. $entry_id .'.png';
            if ( file_exists( $file ) ) {
                //delete it
                unlink( $file );
            }
            unset( $field );
        }
    }

    public static function include_updater(){
		if ( class_exists( 'FrmAddon' ) ) {
			include_once( self::path() .'/models/FrmSigUpdate.php' );
			FrmSigUpdate::load_hooks();
		}
    }

    private static function get_style_settings( $form_id ) {
        if ( is_callable( 'FrmStylesController::get_form_style' ) ) {
            $style_settings = FrmStylesController::get_form_style( $form_id );
            $style_settings = $style_settings->post_content;
        } else {
            global $frmpro_settings;
            if ( ! $frmpro_settings && class_exists('FrmProSettings') ) {
                $frmpro_settings = new FrmProSettings();
            }
            $style_settings = (array) $frmpro_settings;
        }
        return $style_settings;
    }

	/**
	 * Get the URL for a signature
	 *
	 * @since 1.07.03
	 * @param string $file_path
	 * @return string $url
	 */
    private static function get_signature_url( $file_path ){
		if ( ! file_exists( $file_path ) ) {
			$url = '';
		} else {
			$uploads = wp_upload_dir();
			$url = str_replace( $uploads['basedir'], $uploads['baseurl'], $file_path );
		}

		return $url;
    }

	/**
	 * Get the full path a signature
	 *
	 * @since 1.07.03
	 * @param int $field_id
	 * @param int $entry_id
	 * @return string
	 */
	private static function get_full_path_to_signature( $field_id, $entry_id ) {
		$uploads = wp_upload_dir();
		$target_path = $uploads['basedir'];

		self::maybe_make_directory( $target_path );

		$relative_path = apply_filters( 'frm_sig_upload_folder', 'formidable/signatures' );
		$relative_path = untrailingslashit( $relative_path );
		$folders = explode( '/', $relative_path );

		foreach ( $folders as $folder ) {
			$target_path .= '/'. $folder;
			self::maybe_make_directory( $target_path );
		}

		$file_name = 'signature-'. $field_id .'-'. $entry_id .'.png';

		return $target_path . '/'. $file_name;
	}

	/**
	 * Create a directory if it doesn't exist
	 *
	 * @since 1.07.03
	 * @param string $target_path
	 */
	private static function maybe_make_directory( $target_path ){
		if ( ! file_exists( $target_path ) ) {
			@mkdir( $target_path . '/' );
		}
	}
}