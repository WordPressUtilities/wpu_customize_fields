<?php
/*
Plugin Name: WPU Customize Fields
Plugin URI: https://github.com/WordPressUtilities/wpu_customize_fields
Update URI: https://github.com/WordPressUtilities/wpu_customize_fields
Description: Custom fields for WP Customizer
Version: 0.0.1
Author: kevinrocher
Author URI: https://kevinrocher.me/
Text Domain: wpu_customize_fields
Domain Path: /lang
Requires at least: 6.2
Requires PHP: 8.0
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

if (!defined('ABSPATH')) {
    exit();
}

class WPUCustomizeFields {
    private $plugin_version = '0.1.0';
    private $plugin_settings = array(
        'id' => 'wpu_customize_fields',
        'name' => 'WPU Customize Fields'
    );
    private $basetoolbox;
    private $plugin_description;

    public function __construct() {
        add_action('init', array(&$this, 'load_translation'));
        add_action('init', array(&$this, 'init'));
        add_action('customize_register', array(&$this, 'customize_register'));
        add_action('wp_head', array(&$this, 'display_variables'));
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

    public function init() {

        # Load TOOLBOX
        require_once __DIR__ . '/inc/WPUBaseToolbox/WPUBaseToolbox.php';
        $this->basetoolbox = new \wpu_customize_fields\WPUBaseToolbox(array(
            'need_form_js' => false
        ));
    }

    function customize_register($wp_customize) {
        $sections = apply_filters('wpu_customize_fields__sections', array());
        $fields = apply_filters('wpu_customize_fields__fields', array());

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

    function add_section($wp_customize, $section_id, $section) {
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

    function add_field($wp_customize, $field_id, $field = array()) {

        if (!is_array($field)) {
            return;
        }

        $field = array_merge(array(
            'label' => $field_id,
            'type' => 'text',
            'section' => 'wpu_customize_fields__default_section',
            'default' => ''
        ), $field);

        $default_setting = array(
            'default' => $field['default'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport' => 'refresh'
        );

        $default_control = array(
            'label' => $field['label'],
            'section' => $field['section'],
            'settings' => $field_id
        );

        switch ($field['type']) {
        case 'color':
            $default_setting['sanitize_callback'] = 'sanitize_hex_color';
            $wp_customize->add_setting($field_id, $default_setting);
            $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, $field_id, $default_control));
            break;

        case 'font-family':
            $default_setting['sanitize_callback'] = 'sanitize_text_field';
            $wp_customize->add_setting($field_id, $default_setting);
            $default_control['type'] = 'select';
            if (!isset($field['choices']) || !is_array($field['choices'])) {
                $field['choices'] = array(
                    'Helvetica, Arial, sans-serif' => __('Helvetica, Arial, sans-serif', 'wpu_customize_fields'),
                    'Georgia, serif' => __('Georgia, serif', 'wpu_customize_fields'),
                    'Courier New, monospace' => __('Courier New, monospace', 'wpu_customize_fields'),
                    'Times New Roman, serif' => __('Times New Roman, serif', 'wpu_customize_fields'),
                    'Verdana, sans-serif' => __('Verdana, sans-serif', 'wpu_customize_fields')
                );
            }
            $default_control['choices'] = $field['choices'];
            $wp_customize->add_control($field_id, $default_control);
            break;
        case 'size':
            $default_setting['sanitize_callback'] = function ($value) {
                return is_numeric($value) ? $value : 0;
            };
            $wp_customize->add_setting($field_id, $default_setting);
            $default_control['type'] = 'number';
            if (!isset($field['input_attrs']) || !is_array($field['input_attrs'])) {
                $field['input_attrs'] = array(
                    'min' => 0,
                    'step' => 1
                );
            }
            $default_control['input_attrs'] = $field['input_attrs'];
            $wp_customize->add_control($field_id, $default_control);
            break;
        case 'font-size':
            $default_setting['sanitize_callback'] = function ($value) {
                return is_numeric($value) ? $value : 16;
            };
            $wp_customize->add_setting($field_id, $default_setting);
            $default_control['type'] = 'number';
            if (!isset($field['input_attrs']) || !is_array($field['input_attrs'])) {
                $field['input_attrs'] = array(
                    'min' => 12,
                    'step' => 1
                );
            }
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

    function display_variables() {

        $values_html = '';
        $fields = apply_filters('wpu_customize_fields__fields', array());
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
}

$WPUCustomizeFields = new WPUCustomizeFields();
