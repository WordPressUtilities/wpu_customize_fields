document.addEventListener("DOMContentLoaded", function() {
    'use strict';
    var _styletagid = 'wpu-customize-dynamic-vars';
    for (var fieldId in WPUCustomizeFields.fields) {
        watch_customize_field(fieldId, WPUCustomizeFields.fields[fieldId]);
    }

    function watch_customize_field(fieldId, _field) {
        wp.customize(fieldId, function(value) {
            value.bind(function(newValue) {
                var _newValue = newValue;
                if (_field.type =='size' || _field.type == 'font-size') {
                    _newValue += 'px';
                }
                inject_style_tag(fieldId, _newValue);
            });
        });
    }

    function inject_style_tag(fieldId, value) {
        var $styleTag = document.getElementById(_styletagid + fieldId);
        if (!$styleTag) {
            $styleTag = document.createElement('style');
            $styleTag.id = _styletagid + fieldId;
            document.head.appendChild($styleTag);
        }
        $styleTag.innerHTML = `:root { --wpucustomizefields-${fieldId.replace(/[^a-zA-Z0-9]/g, '-')}: ${value}; }`;
    }
});
