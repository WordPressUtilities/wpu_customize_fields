<?php

class WPUCustomizeFields_HeadingControl extends WP_Customize_Control {
    public function render_content() {
        echo '<hr /><h2 style="color:inherit;margin-bottom:0.5em;">' . esc_html($this->label) . '</h2>';
    }
}
