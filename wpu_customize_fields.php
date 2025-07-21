<?php
/*
Plugin Name: WPU Customize Fields
Plugin URI: https://github.com/WordPressUtilities/wpu_customize_fields
Update URI: https://github.com/WordPressUtilities/wpu_customize_fields
Description: Custom fields for WP Customizer
Version: 0.0.4
Author: kevinrocher
Author URI: https://kevinrocher.me/
Text Domain: wpu_customize_fields
Domain Path: /lang
Requires at least: 6.2
Requires PHP: 8.0
Network: Optional
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

if (!defined('ABSPATH')) {
    exit();
}

class WPUCustomizeFields {
    private $plugin_version = '0.0.4';
    private $plugin_settings = array(
        'id' => 'wpu_customize_fields',
        'name' => 'WPU Customize Fields'
    );
    private $basetoolbox;
    private $plugin_description;

    private $default_fonts = array(
        array(
            'Helvetica, Arial, sans-serif' => 'Helvetica, Arial, sans-serif',
            'Georgia, serif' => 'Georgia, serif',
            'Courier New, monospace' => 'Courier New, monospace',
            'Times New Roman, serif' => 'Times New Roman, serif',
            'Verdana, sans-serif' => 'Verdana, sans-serif'
        )
    );

    public function __construct() {
        add_action('init', array(&$this, 'load_translation'));
        add_action('init', array(&$this, 'load_dependencies'));
        add_action('customize_register', array(&$this, 'customize_register'));
        add_action('wp_head', array(&$this, 'display_variables'));
        add_action('admin_head', array(&$this, 'display_variables'));
        add_action('customize_preview_init', function () {
            wp_enqueue_script('wpu-customize-fields-preview-vars', plugins_url('assets/customize-preview.js', __FILE__), ['customize-preview', 'jquery'], null, true);
            wp_localize_script('wpu-customize-fields-preview-vars', 'WPUCustomizeFields', array(
                'fields' => $this->get_fields()
            ));
        });

    }

    public function load_translation() {
        $lang_dir = dirname(plugin_basename(__FILE__)) . '/lang/';
        if (strpos(__DIR__, 'mu-plugins') !== false) {
            load_muplugin_textdomain('wpu_customize_fields', $lang_dir);
        } else {
            load_plugin_textdomain('wpu_customize_fields', false, $lang_dir);
        }
        $this->plugin_description = __('Custom fields for WP Customizer', 'wpu_customize_fields');
    }

    public function load_dependencies() {

        # Load TOOLBOX
        require_once __DIR__ . '/inc/WPUBaseToolbox/WPUBaseToolbox.php';
        $this->basetoolbox = new \wpu_customize_fields\WPUBaseToolbox(array(
            'need_form_js' => false
        ));

    }

    public function customize_register($wp_customize) {
        $sections = apply_filters('wpu_customize_fields__sections', array());
        $fields = $this->get_fields();

        if (empty($sections) && empty($fields)) {
            return;
        }

        if (empty($sections)) {
            $sections = array(
                'wpu_customize_fields__default_section' => array(
                    'title' => __('Default Section', 'wpu_customize_fields')
                )
            );
        }
        foreach ($sections as $section_id => $section) {
            $this->add_section($wp_customize, $section_id, $section);
        }
        foreach ($fields as $field_id => $field) {
            $this->add_field($wp_customize, $field_id, $field);
        }
    }

    public function add_section($wp_customize, $section_id, $section) {
        if (!is_array($section) || !isset($section['title']) || empty($section['title'])) {
            $section = array(
                'title' => $section_id
            );
        }
        $wp_customize->add_section($section_id, array(
            'title' => $section['title'],
            'priority' => isset($section['priority']) ? $section['priority'] : 30,
            'description' => isset($section['description']) ? $section['description'] : ''
        ));
    }

    public function add_field($wp_customize, $field_id, $field = array()) {

        if (!is_array($field)) {
            return;
        }

        $field = array_merge(array(
            'label' => $field_id,
            'type' => 'text',
            'section' => 'wpu_customize_fields__default_section',
            'default' => '',
            'input_attrs' => array()
        ), $field);

        $default_setting = array(
            'default' => $field['default'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport' => 'postMessage'
        );

        $default_control = array(
            'label' => $field['label'],
            'section' => $field['section'],
            'settings' => $field_id
        );

        /* Default values */
        if (!isset($field['choices']) || !is_array($field['choices'])) {
            $field['choices'] = array();
        }

        if ($field['type'] == 'font-family' && empty($field['choices'])) {
            $field['choices'] = $this->default_fonts;
        }

        /* BUILD FIELDS */
        switch ($field['type']) {
        case 'color':
            $default_setting['sanitize_callback'] = 'sanitize_hex_color';
            $wp_customize->add_setting($field_id, $default_setting);
            $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, $field_id, $default_control));
            break;

        case 'select':
        case 'font-family':
            if (empty($field['choices'])) {
                $field['choices'] = $this->default_fonts;
            }
            $default_setting['sanitize_callback'] = function ($value) use ($field) {
                if (array_key_exists($value, $field['choices'])) {
                    return $value;
                }
                return array_key_first($field['choices']);
            };
            $wp_customize->add_setting($field_id, $default_setting);
            $default_control['type'] = 'select';
            $default_control['choices'] = $field['choices'];
            $wp_customize->add_control($field_id, $default_control);
            break;

        case 'size':
        case 'font-size':

            /* Set default value */
            $_default = ($field['type'] === 'size') ? 0 : 16;
            if (!isset($field['default']) || !is_numeric($field['default'])) {
                $field['default'] = $_default;
            }

            /* Set input attributes */
            $input_attrs = array(
                'min' => 0,
                'max' => 1000,
                'step' => 1
            );
            if ($field['type'] === 'font-size') {
                $input_attrs['min'] = 12;
                $input_attrs['max'] = 200;
                $input_attrs['default'] = 16;
            }
            if (!is_array($field['input_attrs'])) {
                $field['input_attrs'] = $input_attrs;
            }
            foreach ($input_attrs as $key => $value) {
                if (!isset($field['input_attrs'][$key]) || !is_numeric($field['input_attrs'][$key])) {
                    $field['input_attrs'][$key] = $value;
                }
            }
            $default_setting['sanitize_callback'] = function ($value) use ($field) {
                if (!is_numeric($value) || $value < $field['input_attrs']['min'] || $value > $field['input_attrs']['max']) {
                    $value = $field['default'];
                }
                return $value;
            };
            $wp_customize->add_setting($field_id, $default_setting);
            $default_control['type'] = 'number';
            $default_control['input_attrs'] = $field['input_attrs'];
            $wp_customize->add_control($field_id, $default_control);
            break;
        default:
            $wp_customize->add_setting($field_id, $default_setting);
            $default_control['type'] = 'text';
            $wp_customize->add_control($field_id, $default_control);
            break;
        }

    }

    public function display_variables() {

        $values_html = '';
        $fields = $this->get_fields();
        foreach ($fields as $field_id => $field) {
            if (isset($field['default'])) {
                $value = get_theme_mod($field_id, $field['default']);
            } else {
                $value = get_theme_mod($field_id);
            }

            $field_id = str_replace('_', '-', $field_id);

            if ($field['type'] === 'font-size' || $field['type'] === 'size') {
                $value .= 'px';
            }

            if ($value) {
                $values_html .= '--wpucustomizefields-' . esc_html($field_id) . ': ' . esc_html($value) . ';' . "\n";
            }
        }

        if ($values_html) {
            echo '<style>:root { ' . $values_html . '}</style>';
        }

    }

    public function get_fields() {
        $fields = apply_filters('wpu_customize_fields__fields', array());
        if (empty($fields) || !is_array($fields)) {
            return array();
        }

        foreach ($fields as $field_id => $field) {
            if (!isset($field['id'])) {
                $fields[$field_id]['id'] = $field_id;
            }
            if (!isset($field['label'])) {
                $fields[$field_id]['label'] = $field_id;
            }
            if (!isset($field['type'])) {
                $fields[$field_id]['type'] = 'text';
            }
        }

        return $fields;
    }

}

$WPUCustomizeFields = new WPUCustomizeFields();
