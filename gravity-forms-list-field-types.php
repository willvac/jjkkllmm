<?php
/**
 * Plugin Name: Gravity Forms Advanced List Fields
 * Plugin URI: https://example.com/plugins/gravity-forms-advanced-list-fields
 * Description: Extends Gravity Forms list field to support different input types for columns (checkbox, multiselect, textarea, radio, etc.)
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: gf-advanced-list-fields
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * This plugin requires Gravity Forms to be installed and activated.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class GF_Advanced_List_Fields {

    /**
     * Plugin instance.
     *
     * @var GF_Advanced_List_Fields
     */
    private static $instance = null;

    /**
     * Get plugin instance.
     *
     * @return GF_Advanced_List_Fields
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        // Check if Gravity Forms is active
        if (class_exists('GFForms')) {
            // Add hooks
            add_action('gform_field_standard_settings', array($this, 'add_list_field_type_settings'), 10, 2);
            add_action('gform_editor_js', array($this, 'editor_script'));
            add_filter('gform_field_content', array($this, 'list_field_content'), 10, 5);
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            add_action('gform_enqueue_scripts', array($this, 'gform_enqueue_scripts'), 10, 2);
            add_filter('gform_entry_field_value', array($this, 'format_entry_value'), 10, 4);
        }
    }

    /**
     * Add settings to the List Field settings in the Form Editor.
     *
     * @param int $position Position of the setting.
     * @param int $form_id  Form ID.
     */
    public function add_list_field_type_settings($position, $form_id) {
        // Add settings after List Field columns
        if ($position == 1325) {
            ?>
            <li class="list_column_types_setting field_setting">
                <label for="list_column_types" class="section_label">
                    <?php esc_html_e('Column Types', 'gf-advanced-list-fields'); ?>
                    <?php gform_tooltip('list_column_types_tooltip'); ?>
                </label>
                <div id="list_column_types_container">
                    <!-- This will be populated by JavaScript -->
                    <div id="list_column_types_message">
                        <?php esc_html_e('Add columns to the list field to configure their types.', 'gf-advanced-list-fields'); ?>
                    </div>
                </div>
            </li>
            <?php
        }
    }

    /**
     * Add editor script for List Field column types.
     */
    public function editor_script() {
        ?>
        <script type="text/javascript">
            // Add the list_column_types setting to the List Field
            fieldSettings.list += ', .list_column_types_setting';

            // Add tooltip
            gform.addToolTip('list_column_types_tooltip', '<?php echo esc_js(__('Select the input type for each column in the list field.', 'gf-advanced-list-fields')); ?>');

            // Bind to the load field settings event to initialize the column type settings
            jQuery(document).on('gform_load_field_settings', function(event, field, form) {
                if (field.type === 'list' && field.enableColumns) {
                    var $container = jQuery('#list_column_types_container');
                    var $message = jQuery('#list_column_types_message');
                    
                    // Clear container
                    $container.find('.list_column_type_row').remove();
                    
                    if (field.choices && field.choices.length > 0) {
                        $message.hide();
                        
                        // Add column type settings for each column
                        jQuery.each(field.choices, function(index, column) {
                            var columnName = column.text || '';
                            var columnType = column.inputType || 'text';
                            var columnOptions = column.options || '';
                            
                            var row = '<div class="list_column_type_row" data-index="' + index + '">' +
                                '<strong>' + columnName + '</strong>: ' +
                                '<select class="list_column_type" onchange="UpdateListColumnType(this, ' + index + ');">' +
                                    '<option value="text"' + (columnType === 'text' ? ' selected="selected"' : '') + '>Text</option>' +
                                    '<option value="textarea"' + (columnType === 'textarea' ? ' selected="selected"' : '') + '>Text Area</option>' +
                                    '<option value="select"' + (columnType === 'select' ? ' selected="selected"' : '') + '>Dropdown</option>' +
                                    '<option value="multiselect"' + (columnType === 'multiselect' ? ' selected="selected"' : '') + '>Multi-Select</option>' +
                                    '<option value="checkbox"' + (columnType === 'checkbox' ? ' selected="selected"' : '') + '>Checkbox</option>' +
                                    '<option value="radio"' + (columnType === 'radio' ? ' selected="selected"' : '') + '>Radio</option>' +
                                '</select>' +
                                '<div class="list_column_options_container"' + (columnType === 'select' || columnType === 'multiselect' || columnType === 'radio' ? '' : ' style="display:none;"') + '>' +
                                    '<label>Options:</label>' +
                                    '<textarea class="list_column_options" onblur="UpdateListColumnOptions(this, ' + index + ');">' + columnOptions + '</textarea>' +
                                    '<span class="description">Enter each option on a new line. For value/label pairs, separate with a pipe (|). Example: value|label</span>' +
                                '</div>' +
                            '</div>';
                            
                            $container.append(row);
                        });
                    } else {
                        $message.show();
                    }
                }
            });

            // Function to update the column type
            function UpdateListColumnType(select, columnIndex) {
                var field = GetSelectedField();
                var type = jQuery(select).val();
                
                if (!field.choices[columnIndex].inputType) {
                    field.choices[columnIndex].inputType = type;
                } else {
                    field.choices[columnIndex].inputType = type;
                }
                
                // Show/hide options textarea based on type
                var $optionsContainer = jQuery(select).closest('.list_column_type_row').find('.list_column_options_container');
                if (type === 'select' || type === 'multiselect' || type === 'radio') {
                    $optionsContainer.show();
                } else {
                    $optionsContainer.hide();
                }
            }
            
            // Function to update the column options
            function UpdateListColumnOptions(textarea, columnIndex) {
                var field = GetSelectedField();
                var options = jQuery(textarea).val();
                
                field.choices[columnIndex].options = options;
            }
        </script>
        <?php
    }

    /**
     * Modify the List Field content to support different input types.
     *
     * @param string $content The field content.
     * @param array  $field   The field properties.
     * @param string $value   The field value.
     * @param int    $lead_id The entry ID.
     * @param int    $form_id The form ID.
     *
     * @return string
     */
    public function list_field_content($content, $field, $value, $lead_id, $form_id) {
        if ($field['type'] == 'list' && !empty($field['enableColumns']) && !empty($field['choices'])) {
            // Check if any column has a custom input type
            $has_custom_types = false;
            foreach ($field['choices'] as $column) {
                if (!empty($column['inputType']) && $column['inputType'] !== 'text') {
                    $has_custom_types = true;
                    break;
                }
            }
            
            if ($has_custom_types) {
                // Get field data
                $id = $field['id'];
                $field_id = 'input_' . $form_id . '_' . $id;
                $max_rows = $field['maxRows'] > 0 ? $field['maxRows'] : 0;
                $add_button_label = !empty($field['addButtonLabel']) ? $field['addButtonLabel'] : __('Add Another Row', 'gf-advanced-list-fields');
                $remove_button_label = !empty($field['removeButtonLabel']) ? $field['removeButtonLabel'] : __('Remove', 'gf-advanced-list-fields');
                
                // Prepare columns
                $columns = array();
                foreach ($field['choices'] as $column) {
                    $columns[] = array(
                        'text' => $column['text'],
                        'inputType' => !empty($column['inputType']) ? $column['inputType'] : 'text',
                        'options' => !empty($column['options']) ? $column['options'] : ''
                    );
                }
                
                // Prepare values
                $list_values = maybe_unserialize($value);
                if (!is_array($list_values)) {
                    $list_values = array(array());
                } elseif (empty($list_values)) {
                    $list_values = array(array());
                }
                
                // Start building HTML
                $html = '<div class="ginput_container ginput_container_list ginput_container_advanced_list">';
                $html .= '<div class="gfield_list_container gfield_list_container_advanced">';
                
                // Table headers
                $html .= '<table class="gfield_list gfield_list_advanced" data-field-id="' . esc_attr($id) . '">';
                $html .= '<thead><tr>';
                
                foreach ($columns as $column) {
                    $html .= '<th class="gfield_list_header">' . esc_html($column['text']) . '</th>';
                }
                
                // Add delete column header
                $html .= '<th class="gfield_list_icons">&nbsp;</th>';
                $html .= '</tr></thead>';
                
                // Table body
                $html .= '<tbody class="gfield_list_tbody">';
                
                $row_count = count($list_values);
                
                foreach ($list_values as $row_index => $row_values) {
                    $html .= '<tr class="gfield_list_row_odd gfield_list_group">';
                    
                    foreach ($columns as $column_index => $column) {
                        $column_name = $column['text'];
                        $input_type = $column['inputType'];
                        $column_value = isset($row_values[$column_index]) ? $row_values[$column_index] : '';
                        
                        $html .= '<td class="gfield_list_cell gfield_list_' . esc_attr($id) . '_cell' . $column_index . '">';
                        
                        $input_id = 'input_' . $form_id . '_' . $id . '_' . $row_index . '_' . $column_index;
                        $input_name = 'input_' . $id . '[]';
                        
                        switch ($input_type) {
                            case 'textarea':
                                $html .= '<textarea id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" class="gfield_list_' . esc_attr($id) . '_textarea">' . esc_textarea($column_value) . '</textarea>';
                                break;
                                
                            case 'select':
                                $html .= '<select id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" class="gfield_list_' . esc_attr($id) . '_select">';
                                $html .= '<option value=""></option>';
                                
                                $options = $this->parse_options($column['options']);
                                foreach ($options as $option) {
                                    $selected = ($option['value'] == $column_value) ? ' selected="selected"' : '';
                                    $html .= '<option value="' . esc_attr($option['value']) . '"' . $selected . '>' . esc_html($option['label']) . '</option>';
                                }
                                
                                $html .= '</select>';
                                break;
                                
                            case 'multiselect':
                                $selected_values = !empty($column_value) ? explode(',', $column_value) : array();
                                
                                $html .= '<select id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" class="gfield_list_' . esc_attr($id) . '_multiselect" multiple="multiple">';
                                
                                $options = $this->parse_options($column['options']);
                                foreach ($options as $option) {
                                    $selected = in_array($option['value'], $selected_values) ? ' selected="selected"' : '';
                                    $html .= '<option value="' . esc_attr($option['value']) . '"' . $selected . '>' . esc_html($option['label']) . '</option>';
                                }
                                
                                $html .= '</select>';
                                break;
                                
                            case 'checkbox':
                                $checked = !empty($column_value) ? ' checked="checked"' : '';
                                $html .= '<input type="checkbox" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="1"' . $checked . ' class="gfield_list_' . esc_attr($id) . '_checkbox">';
                                break;
                                
                            case 'radio':
                                $options = $this->parse_options($column['options']);
                                
                                $radio_id_base = $input_id . '_radio';
                                $radio_name = $input_name . '_radio_' . $row_index . '_' . $column_index;
                                
                                foreach ($options as $option_index => $option) {
                                    $radio_id = $radio_id_base . '_' . $option_index;
                                    $checked = ($option['value'] == $column_value) ? ' checked="checked"' : '';
                                    
                                    $html .= '<div class="gfield_list_' . esc_attr($id) . '_radio_item">';
                                    $html .= '<input type="radio" id="' . esc_attr($radio_id) . '" name="' . esc_attr($radio_name) . '" value="' . esc_attr($option['value']) . '"' . $checked . ' class="gfield_list_' . esc_attr($id) . '_radio" data-list-input-name="' . esc_attr($input_name) . '">';
                                    $html .= '<label for="' . esc_attr($radio_id) . '">' . esc_html($option['label']) . '</label>';
                                    $html .= '</div>';
                                }
                                
                                // Add hidden field to store the selected value
                                $html .= '<input type="hidden" name="' . esc_attr($input_name) . '" value="' . esc_attr($column_value) . '" class="gfield_list_' . esc_attr($id) . '_radio_hidden">';
                                break;
                                
                            default: // text
                                $html .= '<input type="text" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($column_value) . '" class="gfield_list_' . esc_attr($id) . '_text">';
                                break;
                        }
                        
                        $html .= '</td>';
                    }
                    
                    // Add row actions (add/delete)
                    $html .= '<td class="gfield_list_icons">';
                    
                    // Delete button
                    if ($row_count > 1) {
                        $html .= '<button type="button" class="gfield_list_remove_button" aria-label="' . esc_attr($remove_button_label) . '"><svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.279337 0.279338C0.651787 -0.0931121 1.25565 -0.0931121 1.6281 0.279338L9.72066 8.3719C10.0931 8.74435 10.0931 9.34821 9.72066 9.72066C9.34821 10.0931 8.74435 10.0931 8.3719 9.72066L0.279337 1.6281C-0.0931125 1.25565 -0.0931125 0.651788 0.279337 0.279338Z" fill="currentColor"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M0.279337 9.72066C-0.0931125 9.34821 -0.0931125 8.74435 0.279337 8.3719L8.3719 0.279338C8.74435 -0.0931121 9.34821 -0.0931121 9.72066 0.279338C10.0931 0.651788 10.0931 1.25565 9.72066 1.6281L1.6281 9.72066C1.25565 10.0931 0.651787 10.0931 0.279337 9.72066Z" fill="currentColor"></path></svg></button>';
                    }
                    
                    // Add button
                    if ($max_rows === 0 || $row_count < $max_rows) {
                        $html .= '<button type="button" class="gfield_list_add_button" aria-label="' . esc_attr($add_button_label) . '"><svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5 0C5.55228 0 6 0.447715 6 1V9C6 9.55229 5.55228 10 5 10C4.44772 10 4 9.55229 4 9V1C4 0.447715 4.44772 0 5 0Z" fill="currentColor"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M10 5C10 5.55228 9.55229 6 9 6H1C0.447715 6 0 5.55228 0 5C0 4.44772 0.447715 4 1 4H9C9.55229 4 10 4.44772 10 5Z" fill="currentColor"></path></svg></button>';
                    }
                    
                    $html .= '</td>';
                    $html .= '</tr>';
                }
                
                $html .= '</tbody>';
                $html .= '</table>';
                
                // Add field metadata
                $html .= '<input type="hidden" name="input_' . esc_attr($id) . '_shim" value="' . esc_attr($field['id']) . '" style="position:absolute;left:-999em;">';
                
                $html .= '</div>'; // Close .gfield_list_container
                $html .= '</div>'; // Close .ginput_container
                
                // Add the field description if present
                if (!empty($field['description'])) {
                    $html .= '<div class="gfield_description">' . $field['description'] . '</div>';
                }
                
                // Add field data for JS
                $html .= '<script type="text/javascript">
                    if (typeof window.gf_advanced_list_data === "undefined") {
                        window.gf_advanced_list_data = {};
                    }
                    window.gf_advanced_list_data["' . esc_js($field_id) . '"] = ' . json_encode(array(
                        'columns' => $columns,
                        'max_rows' => $max_rows,
                        'add_button_label' => $add_button_label,
                        'remove_button_label' => $remove_button_label
                    )) . ';
                </script>';
                
                return $html;
            }
        }
        
        return $content;
    }

    /**
     * Parse options string into array.
     *
     * @param string $options_string Options string.
     * @return array
     */
    private function parse_options($options_string) {
        $options = array();
        
        if (!empty($options_string)) {
            $lines = explode("\n", $options_string);
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                if (!empty($line)) {
                    // Check if it's a value|label pair
                    if (strpos($line, '|') !== false) {
                        list($value, $label) = array_map('trim', explode('|', $line, 2));
                    } else {
                        $value = $label = $line;
                    }
                    
                    $options[] = array(
                        'value' => $value,
                        'label' => $label
                    );
                }
            }
        }
        
        return $options;
    }

    /**
     * Enqueue scripts and styles for the front-end.
     */
    public function enqueue_scripts() {
        if (!wp_script_is('jquery', 'enqueued')) {
            wp_enqueue_script('jquery');
        }
        
        wp_enqueue_script(
            'gf-advanced-list-fields',
            plugins_url('js/gf-advanced-list-fields.js', __FILE__),
            array('jquery', 'gform_gravityforms'),
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'gf-advanced-list-fields',
            plugins_url('css/gf-advanced-list-fields.css', __FILE__),
            array(),
            '1.0.0'
        );
    }

    /**
     * Enqueue scripts and styles for the admin panel.
     */
    public function admin_enqueue_scripts() {
        $screen = get_current_screen();
        
        // Only enqueue on Gravity Forms pages
        if ($screen && strpos($screen->id, 'gf_') === 0) {
            wp_enqueue_style(
                'gf-advanced-list-fields-admin',
                plugins_url('css/gf-advanced-list-fields-admin.css', __FILE__),
                array(),
                '1.0.0'
            );
        }
    }

    /**
     * Enqueue scripts specifically for Gravity Forms.
     *
     * @param array $form The form object.
     * @param bool  $is_ajax Whether the form is being submitted via AJAX.
     */
    public function gform_enqueue_scripts($form, $is_ajax) {
        if (!empty($form['fields'])) {
            $has_advanced_list = false;
            
            foreach ($form['fields'] as $field) {
                if ($field->type == 'list' && !empty($field->enableColumns) && !empty($field->choices)) {
                    // Check if any column has a custom input type
                    foreach ($field->choices as $column) {
                        if (!empty($column['inputType']) && $column['inputType'] !== 'text') {
                            $has_advanced_list = true;
                            break 2;
                        }
                    }
                }
            }
            
            if ($has_advanced_list) {
                // Enqueue specific scripts for forms with advanced list fields
                // This is mainly a placeholder for any additional scripts you might need
            }
        }
    }

    /**
     * Format entry value for display.
     *
     * @param string $value      The entry value.
     * @param array  $field      The field object.
     * @param array  $entry      The entry object.
     * @param array  $form       The form object.
     *
     * @return string
     */
    public function format_entry_value($value, $field, $entry, $form) {
        if (!empty($field['type']) && $field['type'] == 'list' && !empty($field['enableColumns']) && !empty($field['choices'])) {
            $list_values = maybe_unserialize($value);
            
            if (is_array($list_values) && !empty($list_values)) {
                $columns = $field['choices'];
                $output = '<table class="gf-advanced-list-entry-value">';
                
                // Table header
                $output .= '<thead><tr>';
                foreach ($columns as $column) {
                    $output .= '<th>' . esc_html($column['text']) . '</th>';
                }
                $output .= '</tr></thead>';
                
                // Table body
                $output .= '<tbody>';
                foreach ($list_values as $row) {
                    $output .= '<tr>';
                    
                    foreach ($row as $index => $cell_value) {
                        $column = isset($columns[$index]) ? $columns[$index] : null;
                        
                        if ($column && !empty($column['inputType'])) {
                            switch ($column['inputType']) {
                                case 'checkbox':
                                    $cell_output = !empty($cell_value) ? '✓' : '✗';
                                    break;
                                
                                case 'multiselect':
                                    $values = explode(',', $cell_value);
                                    $options = $this->parse_options($column['options']);
                                    $labels = array();
                                    
                                    foreach ($values as $val) {
                                        foreach ($options as $option) {
                                            if ($option['value'] == $val) {
                                                $labels[] = $option['label'];
                                                break;
                                            }
                                        }
                                    }
                                    
                                    $cell_output = implode(', ', $labels);
                                    break;
                                
                                case 'select':
                                case 'radio':
                                    $options = $this->parse_options($column['options']);
                                    $cell_output = $cell_value;
                                    
                                    foreach ($options as $option) {
                                        if ($option['value'] == $cell_value) {
                                            $cell_output = $option['label'];
                                            break;
                                        }
                                    }
                                    break;
                                
                                default:
                                    $cell_output = $cell_value;
                                    break;
                            }
                        } else {
                            $cell_output = $cell_value;
                        }
                        
                        $output .= '<td>' . esc_html($cell_output) . '</td>';
                    }
                    
                    $output .= '</tr>';
                }
                $output .= '</tbody>';
                $output .= '</table>';
                
                return $output;
            }
        }
        
        return $value;
    }
}

// Initialize the plugin
function gf_advanced_list_fields_init() {
    return GF_Advanced_List_Fields::get_instance();
}
add_action('plugins_loaded', 'gf_advanced_list_fields_init');
