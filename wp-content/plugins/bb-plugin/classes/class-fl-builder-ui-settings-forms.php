<?php

/**
 * Handles logic for UI settings forms.
 *
 * @since 2.0
 */
class FLBuilderUISettingsForms {

	/**
	 * An array of JS templates for custom form tabs and
	 * sections that need to be loaded.
	 *
	 * @since 2.0
	 * @var int $form_templates
	 */
	static private $form_templates = array();

	/**
	 * @since 2.0
	 * @return void
	 */
	static public function init() {
		add_action( 'wp_footer', __CLASS__ . '::render_js_templates', 11 );
	}

	/**
	 * Returns _all_ JS config for settings forms.
	 *
	 * @since 2.0
	 * @return array
	 */
	static public function get_js_config() {
		return array(
			'forms'   		=> self::prep_forms_for_js_config( FLBuilderModel::$settings_forms ),
			'modules' 		=> self::prep_module_forms_for_js_config(),
			'nodes'  		=> self::prep_node_settings_for_js_config(),
			'attachments'   => self::prep_attachments_for_js_config(),
			'settings'		=> array(
				'global'		=> FLBuilderModel::get_global_settings(),
				'layout'		=> FLBuilderModel::get_layout_settings(),
			),
			'defaults'		=> array(
				'row'			=> FLBuilderModel::get_row_defaults(),
				'column'		=> FLBuilderModel::get_col_defaults(),
				'modules'		=> FLBuilderModel::get_module_defaults(),
				'forms'   		=> self::prep_form_defaults_for_js_config( FLBuilderModel::$settings_forms ),
			),
		);
	}

	/**
	 * Returns only the node JS config for settings forms.
	 *
	 * @since 2.0
	 * @return array
	 */
	static public function get_node_js_config() {
		return array(
			'nodes'  		=> self::prep_node_settings_for_js_config(),
			'attachments'   => self::prep_attachments_for_js_config(),
		);
	}

	/**
	 * Prepares form defaults for the JS config.
	 *
	 * @since 2.0
	 * @param array $forms
	 * @return array
	 */
	static private function prep_form_defaults_for_js_config( $forms ) {
		$defaults = array();

		foreach ( $forms as $form_key => $form ) {
			if ( isset( $form['tabs'] ) ) {
				$defaults[ $form_key ] = FLBuilderModel::get_settings_form_defaults( $form_key );
			}
		}

		return $defaults;
	}


	/**
	 * Prepares forms for the JS config.
	 *
	 * @since 2.0
	 * @param array $forms
	 * @return array
	 */
	static private function prep_forms_for_js_config( $forms ) {

		foreach ( $forms as $form_key => &$form ) {

			if ( ! isset( $form['tabs'] ) ) {
				continue;
			}

			foreach ( $form['tabs'] as $tab_key => &$tab ) {

				if ( isset( $tab['template'] ) ) {
					self::$form_templates[ $tab['template']['id'] ] = $tab['template']['file'];
				}

				if ( ! isset( $tab['sections'] ) ) {
					continue;
				}

				foreach ( $tab['sections'] as $section_key => &$section ) {

					if ( isset( $section['file'] ) && FL_BUILDER_DIR . 'includes/service-settings.php' === $section['file'] ) {
						$section['template'] = array(
							'id' 	=> 'fl-builder-service-settings',
							'file' 	=> FL_BUILDER_DIR . 'includes/ui-service-settings.php',
						);
						unset( $section['file'] );
					}

					if ( isset( $section['template'] ) ) {
						self::$form_templates[ $section['template']['id'] ] = $section['template']['file'];
					}

					if ( ! isset( $section['fields'] ) ) {
						continue;
					}

					foreach ( $section['fields'] as $field_key => &$field ) {
						self::prep_field_for_js_config( $field );
					}
				}
			}
		}// End foreach().

		return $forms;
	}

	/**
	 * Prepares a field for the JS config.
	 *
	 * @since 2.0
	 * @param array $field
	 * @return void
	 */
	static private function prep_field_for_js_config( &$field ) {

		// Convert class to className for JS compat.
		if ( isset( $field['class'] ) ) {
			$field['className'] = $field['class'];
		}

		// Select fields
		if ( 'select' === $field['type'] ) {

			if ( is_string( $field['options'] ) && is_callable( $field['options'] ) ) {
				$field['options'] = call_user_func( $field['options'] );
			} else {
				$field['options'] = (array) $field['options'];
			}
		}
	}

	/**
	 * Gathers and prepares module forms for the JS config.
	 *
	 * @since 2.0
	 * @return array
	 */
	static public function prep_module_forms_for_js_config() {
		$module_forms = array();

		foreach ( FLBuilderModel::$modules as $module ) {

			$css = '';
			$js  = '';

			if ( file_exists( $module->dir . 'css/settings.css' ) ) {
				$css .= '<link class="fl-builder-settings-css" rel="stylesheet" href="' . $module->url . 'css/settings.css" />';
			}
			if ( file_exists( $module->dir . 'js/settings.js' ) ) {
				$js .= '<script class="fl-builder-settings-js" src="' . $module->url . 'js/settings.js"></script>';
			}

			$module_forms[ $module->slug ] = array(
				'title'  => $module->name,
				'tabs'   => $module->form,
				'assets' => array(
					'css'	 => $css,
					'js'	 => $js,
				),
			);
		}

		return self::prep_forms_for_js_config( $module_forms );
	}

	/**
	 * Gathers and prepares node settings for the JS config.
	 *
	 * @since 2.0
	 * @return array
	 */
	static public function prep_node_settings_for_js_config() {
		$layout_data   = FLBuilderModel::get_layout_data();
		$node_settings = array();

		foreach ( $layout_data as $node_id => $node ) {
			if ( ! is_object( $node ) || ! isset( $node->settings ) || ! is_object( $node->settings ) ) {
				continue;
			}
			$node_settings[ $node_id ] = $node->settings;
		}

		return $node_settings;
	}

	/**
	 * Gathers and prepares attachments for the JS config.
	 *
	 * @since 2.0
	 * @return array
	 */
	static private function prep_attachments_for_js_config() {

		$layout_data = FLBuilderModel::get_layout_data();
		$attachments = array();

		foreach ( $layout_data as $node ) {

			if ( ! isset( $node->settings ) || ! is_object( $node->settings ) ) {
				continue;
			}

			if ( 'row' === $node->type ) {
				$fields = FLBuilderModel::get_settings_form_fields( FLBuilderModel::$settings_forms['row']['tabs'] );
			} elseif ( 'column' === $node->type ) {
				$fields = FLBuilderModel::get_settings_form_fields( FLBuilderModel::$settings_forms['col']['tabs'] );
			} elseif ( 'module' === $node->type && isset( FLBuilderModel::$modules[ $node->settings->type ] ) ) {
				$fields = FLBuilderModel::get_settings_form_fields( FLBuilderModel::$modules[ $node->settings->type ]->form );
			} else {
				continue;
			}

			foreach ( $node->settings as $key => $value ) {

				// Look for image attachments.
				if ( strstr( $key, '_src' ) ) {

					$base = str_replace( '_src', '', $key );

					if ( isset( $node->settings->$base ) && is_numeric( $node->settings->$base ) ) {

						$id   = $node->settings->$base;
						$data = self::prep_attachment_for_js_config( $id );

						if ( $data ) {
							$attachments[ $id ] = $data;
						}
					}
				}

				// Look for video attachments.
				if ( isset( $fields[ $key ] ) && 'video' === $fields[ $key ]['type'] ) {

					if ( is_numeric( $value ) ) {

						$id   = $value;
						$data = self::prep_attachment_for_js_config( $id );

						if ( $data ) {
							$attachments[ $id ] = $data;
						}
					}
				}
			}
		}// End foreach().

		return $attachments;
	}

	/**
	 * Prepares a single attachment for the JS config.
	 *
	 * @since 2.0
	 * @param int $id
	 * @return array|bool
	 */
	static private function prep_attachment_for_js_config( $id ) {

		$url = wp_get_attachment_url( $id );

		if ( ! $url ) {
			return false;
		}

		$filename 		= wp_basename( $url );
		$base_url 		= str_replace( $filename, '', $url );
		$meta     		= wp_get_attachment_metadata( $id );
		$sizes    		= array();
		$possible_sizes = apply_filters( 'image_size_names_choose', array(
			'thumbnail' 	=> __( 'Thumbnail' ),
			'medium'    	=> __( 'Medium' ),
			'large'     	=> __( 'Large' ),
			'full'      	=> __( 'Full Size' ),
		) );

		if ( isset( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $size_key => $size ) {
				if ( ! isset( $possible_sizes[ $size_key ] ) ) {
					continue;
				}
				$sizes[ $size_key ] = array(
					'url'      => $base_url . $size['file'],
					'filename' => $size['file'],
					'width'    => $size['width'],
					'height'   => $size['height'],
				);
			}
		}

		if ( ! isset( $sizes['full'] ) ) {
			$sizes['full'] = array(
				'url'      => $url,
				'filename' => isset( $meta['file'] ) ? $meta['file'] : $filename,
				'width'    => $meta['width'],
				'height'   => $meta['height'],
			);
		}

		return array(
			'id'		=> $id,
			'url'   	=> $url,
			'filename' 	=> $filename,
			'sizes' 	=> $sizes,
		);
	}

	/**
	 * Renders the JS templates for settings forms.
	 *
	 * @since 2.0
	 * @return void
	 */
	static public function render_js_templates() {
		if ( ! FLBuilderModel::is_builder_active() ) {
			return;
		}

		include FL_BUILDER_DIR . 'includes/ui-settings-form.php';
		include FL_BUILDER_DIR . 'includes/ui-settings-form-row.php';
		include FL_BUILDER_DIR . 'includes/ui-field.php';

		$fields = glob( FL_BUILDER_DIR . 'includes/ui-field-*.php' );
		$custom = apply_filters( 'fl_builder_custom_fields', array() );

		foreach ( $fields as $path ) {
			$slug = str_replace( array( 'ui-field-', '.php' ), '', basename( $path ) );
			echo '<script type="text/html" id="tmpl-fl-builder-field-' . $slug . '">';
			include $path;
			echo '</script>';
		}

		foreach ( $custom as $type => $path ) {
			echo '<script type="text/html" id="tmpl-fl-builder-field-' . $type . '">';
			include $path;
			echo '</script>';
		}

		foreach ( self::$form_templates as $id => $path ) {
			if ( file_exists( $path ) ) {
				echo '<script type="text/html" id="tmpl-' . $id . '">';
				include $path;
				echo '</script>';
			}
		}
	}

	/**
	 * Pre-renders legacy settings tabs, sections and fields for
	 * new modules that are currently being sent to the frontend.
	 *
	 * @since 2.0
	 * @param string $type
	 * @param object $settings
	 * @return array
	 */
	static public function pre_render_legacy_module_settings( $type, $settings ) {
		$data = array(
			'tabs'	 	=> array(),
			'sections'	=> array(),
			'fields'	=> array(),
			'settings'	=> $settings,
			'node_id'	=> null,
		);
		$custom = apply_filters( 'fl_builder_custom_fields', array() );

		foreach ( FLBuilderModel::$modules[ $type ]->form as $tab_id => $tab ) {

			if ( isset( $tab['file'] ) ) {
				$data['tabs'][] = $tab_id;
			}
			if ( ! isset( $tab['sections'] ) ) {
				continue;
			}

			foreach ( $tab['sections'] as $section_id => $section ) {

				if ( isset( $section['file'] ) ) {
					$data['sections'][] = array(
						'tab' 	  => $tab_id,
						'section' => $section_id,
					);
				}
				if ( ! isset( $section['fields'] ) ) {
					continue;
				}

				foreach ( $section['fields'] as $field_id => $field ) {

					$is_core = file_exists( FL_BUILDER_DIR . 'includes/ui-field-' . $field['type'] . '.php' );
					$is_custom = isset( $custom[ $field['type'] ] );

					if ( ! $is_core && ! $is_custom ) {
						$data['fields'][] = $field_id;
					}
				}
			}
		}

		return self::render_legacy_settings( $data, $type, 'module', null );
	}

	/**
	 * Renders legacy settings tabs, sections and fields.
	 *
	 * @since 2.0
	 * @param array $data
	 * @param string $form
	 * @param string $group
	 * @param string $lightbox
	 * @return array
	 */
	static public function render_legacy_settings( $data, $form, $group, $lightbox ) {
		$response = array(
			'lightbox'	=> $lightbox,
			'tabs'	 	=> array(),
			'sections'	=> array(),
			'fields'	=> array(),
			'extras'	=> array(),
		);

		// Get the form tabs.
		if ( 'general' === $group ) {
			$tabs = FLBuilderModel::$settings_forms[ $form ]['tabs'];
		} elseif ( 'module' === $group ) {
			$tabs = FLBuilderModel::$modules[ $form ]->form;
		}

		// Get the form fields.
		$fields = FLBuilderModel::get_settings_form_fields( $tabs );

		// Get the settings.
		if ( $data['node_id'] ) {
			$layout_data = FLBuilderModel::get_layout_data();
			$settings = $layout_data[ $data['node_id'] ]->settings;
		} else {
			$settings = isset( $data['settings'] ) ? (object) $data['settings'] : new stdClass();
		}

		// Render legacy custom fields.
		if ( isset( $data['fields'] ) ) {
			foreach ( $data['fields'] as $name ) {
				ob_start();
				self::render_settings_field( $name, (array) $fields[ $name ], $settings );
				$response['fields'][ $name ] = ob_get_clean();
			}
		}

		// Render legacy field extras with the before and after actions.
		foreach ( $fields as $name => $field ) {

			if ( in_array( $name, $response['fields'] ) ) {
				continue;
			}

			$value = isset( $settings->$name ) ? $settings->$name : '';

			ob_start();
			do_action( 'fl_builder_before_control', $name, $value, $field, $settings );
			do_action( 'fl_builder_before_control_' . $field['type'], $name, $value, $field, $settings );
			$before = ob_get_clean();

			ob_start();
			do_action( 'fl_builder_after_control_' . $field['type'], $name, $value, $field, $settings );
			do_action( 'fl_builder_after_control', $name, $value, $field, $settings );
			$after = ob_get_clean();

			if ( ! empty( $before ) || ! empty( $after ) ) {
				$response['extras'][ $name ] = array(
					'before' => $before,
					'after'  => $after,
				);
			}
		}

		// Render legacy custom sections.
		if ( isset( $data['sections'] ) ) {
			foreach ( $data['sections'] as $section_data ) {
				$tab     = $section_data['tab'];
				$name    = $section_data['section'];
				$section = $tabs[ $tab ]['sections'][ $name ];
				if ( file_exists( $section['file'] ) ) {
					if ( ! isset( $response['sections'][ $tab ] ) ) {
						$response['sections'][ $tab ] = array();
					}
					ob_start();
					include $section['file'];
					$response['sections'][ $tab ][ $name ] = ob_get_clean();
				}
			}
		}

		// Render legacy custom tabs.
		if ( isset( $data['tabs'] ) ) {
			foreach ( $data['tabs'] as $name ) {
				$tab = $tabs[ $name ];
				if ( FL_BUILDER_DIR . 'includes/loop-settings.php' === $tab['file'] ) {
					$tab['file'] = FL_BUILDER_DIR . 'includes/ui-loop-settings.php';
				}
				if ( file_exists( $tab['file'] ) ) {
					ob_start();
					include $tab['file'];
					$response['tabs'][ $name ] = ob_get_clean();
				}
			}
		}

		return $response;
	}

	/**
	 * Renders a settings via PHP. This method is only around for
	 * backwards compatibility with third party settings forms that are
	 * still being rendered via AJAX. Going forward, all settings forms
	 * should be rendered on the frontend using FLBuilderSettingsForms.render.
	 *
	 * @since 2.0
	 * @param array $form The form data.
	 * @param object $settings The settings data.
	 * @return array
	 */
	static public function render_settings( $form = array(), $settings ) {
		$defaults = array(
			'class'     => '',
			'attrs'     => '',
			'title'     => '',
			'badges'	=> array(),
			'tabs'      => array(),
			'buttons'	=> array(),
			'settings'	=> $settings,
		);

		// Legacy filter for the config.
		$form = apply_filters( 'fl_builder_settings_form_config', array_merge( $defaults, $form ) );

		// Setup the class var to be safe in JS.
		$form['className'] = $form['class'];
		unset( $form['class'] );

		// Get the form ID.
		foreach ( $form['tabs'] as $tab ) {
			$form['id'] = $tab['form_id'];
			break;
		}

		// We don't need to send tab data back.
		unset( $form['tabs'] );

		// Render and return!
		ob_start();
		include FL_BUILDER_DIR . 'includes/ui-legacy-settings.php';
		$html = ob_get_clean();

		return array(
			'html' => $html,
		);
	}

	/**
	 * Renders a settings form via PHP. This method is only around for
	 * backwards compatibility with third party settings forms that are
	 * still being rendered via AJAX. Going forward, all settings forms
	 * should be rendered on the frontend using FLBuilderSettingsForms.render.
	 *
	 * @since 2.0
	 * @param string $type The type of form to render.
	 * @param object $settings The settings data.
	 * @return array
	 */
	static public function render_settings_form( $type = null, $settings = null ) {
		$form = FLBuilderModel::get_settings_form( $type );

		if ( isset( $settings ) && ! empty( $settings ) ) {
			$defaults = FLBuilderModel::get_settings_form_defaults( $type );
			$settings = (object) array_merge( (array) $defaults, (array) $settings );
		} else {
			$settings = FLBuilderModel::get_settings_form_defaults( $type );
		}

		return self::render_settings(array(
			'title' 	=> $form['title'],
			'tabs'  	=> $form['tabs'],
		), $settings);
	}

	/**
	 * Renders a settings field via PHP. This method is only around for
	 * backwards compatibility with third party settings forms that are
	 * still being rendered via AJAX. Going forward, all settings forms
	 * should be rendered on the frontend using FLBuilderSettingsForms.render.
	 *
	 * @since 2.0
	 * @param string $name The field name.
	 * @param array $field An array of setup data for the field.
	 * @param object $settings Form settings data object.
	 * @return void
	 */
	static public function render_settings_field( $name, $field, $settings = null ) {

		$field              = apply_filters( 'fl_builder_render_settings_field', $field, $name, $settings ); // Allow field settings filtering first
		$i                  = null;
		$is_multiple        = isset( $field['multiple'] ) && true === (bool) $field['multiple'];
		$supports_multiple  = 'editor' != $field['type'] && 'photo' != $field['type'] && 'service' != $field['type'];
		$settings           = ! $settings ? new stdClass() : $settings;
		$preview            = isset( $field['preview'] ) ? json_encode( $field['preview'] ) : json_encode( array(
			'type' => 'refresh',
		) );
		$row_class          = isset( $field['row_class'] ) ? ' ' . $field['row_class'] : '';
		$responsive         = false;
		$responsive_fields  = array( 'unit' );
		$root_name          = $name;
		$global_settings    = FLBuilderModel::get_global_settings();
		$value              = isset( $settings->$name ) ? $settings->$name : '';

		// Use a default value if not set in the settings.
		if ( ! isset( $settings->$name ) && isset( $field['default'] ) ) {
			$value = $field['default'];
		}

		// Check to see if responsive is enabled for this field.
		if ( $global_settings->responsive_enabled && isset( $field['responsive'] ) && ! $is_multiple && in_array( $field['type'], $responsive_fields ) ) {
			$responsive = $field['responsive'];
		}

		if ( file_exists( FL_BUILDER_DIR . 'includes/ui-field-' . $field['type'] . '.php' ) ) {

			// Render old calls to *core* fields with JS.
			include FL_BUILDER_DIR . 'includes/ui-legacy-field.php';

		} else {

			// Render old calls to *custom* fields with PHP.
			if ( $is_multiple && $supports_multiple ) {

				$values     = $value;
				$arr_name   = $name;
				$name      .= '[]';

				echo '<tbody id="fl-field-' . $root_name . '" class="fl-field fl-builder-field-multiples" data-type="form" data-preview=\'' . $preview . '\'>';

				for ( $i = 0; $i < count( $values ); $i++ ) {
					$value = $values[ $i ];
					echo '<tr class="fl-builder-field-multiple" data-field="' . $arr_name . '">';
					include FL_BUILDER_DIR . 'includes/ui-legacy-custom-field.php';
					echo '<td class="fl-builder-field-actions">';
					echo '<i class="fl-builder-field-move fa fa-arrows"></i>';
					echo '<i class="fl-builder-field-copy fa fa-copy"></i>';
					echo '<i class="fl-builder-field-delete fa fa-times"></i>';
					echo '</td>';
					echo '</tr>';
				}

				echo '<tr>';

				if ( empty( $field['label'] ) ) {
					echo '<td colspan="2">';
				} else {
					echo '<td>&nbsp;</td><td>';
				}

				echo '<a href="javascript:void(0);" onclick="return false;" class="fl-builder-field-add fl-builder-button" data-field="' . $arr_name . '">' . sprintf( _x( 'Add %s', 'Field name to add.', 'fl-builder' ), $field['label'] ) . '</a>';
				echo '</td>';
				echo '</tr>';
				echo '</tbody>';
			} else {
				echo '<tr id="fl-field-' . $name . '" class="fl-field' . $row_class . '" data-type="' . $field['type'] . '" data-preview=\'' . $preview . '\'>';
				include FL_BUILDER_DIR . 'includes/ui-legacy-custom-field.php';
				echo '</tr>';
			}// End if().
		}// End if().
	}// End if().

	/**
	 * Renders the markup for the icon selector.
	 *
	 * @since 2.0
	 * @return array
	 */
	static public function render_icon_selector() {
		$icon_sets = FLBuilderIcons::get_sets();

		ob_start();
		include FL_BUILDER_DIR . 'includes/icon-selector.php';
		$html = ob_get_clean();

		return array(
			'html' => $html,
		);
	}
}

FLBuilderUISettingsForms::init();
