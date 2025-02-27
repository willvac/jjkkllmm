/**
 * Gravity Forms Advanced List Fields
 * Frontend JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize advanced list fields
        initAdvancedListFields();
        
        // Re-initialize on gform_post_render event (for AJAX forms)
        $(document).on('gform_post_render', function() {
            initAdvancedListFields();
        });
    });

    /**
     * Initialize all advanced list fields on the page
     */
    function initAdvancedListFields() {
        if (typeof window.gf_advanced_list_data === 'undefined') {
            return;
        }
        
        // Loop through each advanced list field
        $.each(window.gf_advanced_list_data, function(field_id, field_data) {
            var $field = $('#' + field_id);
            var $table = $field.find('table.gfield_list_advanced');
            
            if ($table.length === 0) {
                return;
            }
            
            // Bind events for radio buttons
            $table.on('change', 'input[type="radio"]', function() {
                var $radio = $(this);
                var value = $radio.val();
                var hidden_input_name = $radio.data('list-input-name');
                var $hidden_input = $radio.closest('td').find('input[name="' + hidden_input_name + '"]');
                
                $hidden_input.val(value);
            });
            
            // Bind events for multiselect
            $table.on('change', 'select[multiple]', function() {
                var $select = $(this);
                var selected_values = $select.val() || [];
                
                // Store as comma-separated string
                $select.val(selected_values.join(','));
            });
            
            // Initialize multiselect values
            $table.find('select[multiple]').each(function() {
                var $select = $(this);
                var current_value = $select.val();
                
                if (current_value && !Array.isArray(current_value)) {
                    var values = current_value.split(',');
                    $select.val(values);
                }
            });
            
            // Handle add button clicks
            $table.on('click', '.gfield_list_add_button', function() {
                // Wait for Gravity Forms to add the new row
                setTimeout(function() {
                    var $tbody = $table.find('tbody');
                    var $lastRow = $tbody.find('tr:last-child');
                    
                    // Process the new row's special fields
                    processRowFields($lastRow, field_data.columns);
                }, 50);
            });
        });
    }
    
    /**
     * Process special fields in a row
     * 
     * @param {jQuery} $row The row jQuery element
     * @param {Array} columns Column data
     */
    function processRowFields($row, columns) {
        if (!$row.length || !columns) {
            return;
        }
        
        // Process each column
        $row.find('td').each(function(index, cell) {
            var $cell = $(cell);
            var column = columns[index];
            
            if (!column || !column.inputType) {
                return;
            }
            
            switch (column.inputType) {
                case 'multiselect':
                    var $select = $cell.find('select[multiple]');
                    if ($select.length) {
                        // Initialize empty multiselect
                        $select.val([]);
                    }
                    break;
                    
                case 'radio':
                    var $radios = $cell.find('input[type="radio"]');
                    if ($radios.length) {
                        // Clear all radio buttons
                        $radios.prop('checked', false);
                        
                        // Clear hidden field
                        $cell.find('input[type="hidden"]').val('');
                    }
                    break;
            }
        });
    }
    
})(jQuery);
