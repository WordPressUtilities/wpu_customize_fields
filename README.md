# WPU Customize Fields

Custom fields for WP Customizer

## Add sections

```php

add_filter('wpu_customize_fields__sections', function ($sections = array()) {
    $sections['mywebsite_colors'] = array(
        'title' => '[mywebsite] Couleurs'
    );
    $sections['mywebsite_fonts'] = array(
        'title' => '[mywebsite] Polices'
    );
    $sections['mywebsite_vars'] = array(
        'title' => '[mywebsite] Vars'
    );
    return $sections;
}, 10, 1);

add_filter('wpu_customize_fields__fields', function ($fields = array()) {
    $fields['main_color'] = array(
        'label' => 'Main Color',
        'type' => 'color',
        'section' => 'mywebsite_colors',
        'default' => '#000000'
    );
    $fields['font_size'] = array(
        'label' => 'Font Size (px)',
        'type' => 'font-size',
        'section' => 'mywebsite_fonts',
        'default' => 16
    );

    $fields['font_family'] = array(
        'label' => 'Font Family',
        'type' => 'font-family',
        'section' => 'mywebsite_fonts',
        'default' => 'Arial, sans-serif',
        'choices' => array(
            'Arial, sans-serif' => 'Arial',
            'Helvetica, sans-serif' => 'Helvetica',
            'Times, serif' => 'Times',
            'Courier New, monospace' => 'Courier New'
        )
    );

    $fields['border_radius_buttons'] = array(
        'label' => 'Border Radius for Buttons (px)',
        'type' => 'size',
        'section' => 'mywebsite_vars'
    );

    return $fields;
}, 10, 1);

```
