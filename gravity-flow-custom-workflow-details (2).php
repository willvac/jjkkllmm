<?php
/**
 * Plugin Name: Custom Gravity Flow Workflow Details
 * Description: Displays related workflows in the Gravity Flow entry details page with status icons, action buttons, and step history
 * Version: 1.0.0
 * Author: Your Name
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add our custom box to the workflow details section
add_action('gravityflow_workflow_detail_display', 'add_related_workflows_box', 10, 2);

function add_related_workflows_box($form, $entry) {
    // Get current entry ID and form ID
    $current_entry_id = $entry['id'];
    $current_form_id = $form['id'];
    
    // Get current user ID
    $current_user_id = get_current_user_id();
    
    // This is where you would pass your data - for example:
    $related_workflows = [
        'Form A' => [
            [
                'form_id' => 1,
                'entry_id' => 123,
                'step_id' => 10,
                'step_name' => 'Document Approval',
                'status' => 'complete',
                'assignee_id' => 0, // No assignee for completed steps
                'step_history' => [
                    [
                        'label' => 'Initial Submission',
                        'date' => '2025-03-01 10:30:45',
                        'assignee' => 'John Doe',
                        'status' => 'complete'
                    ],
                    [
                        'label' => 'Department Review',
                        'date' => '2025-03-02 14:22:18',
                        'assignee' => 'Jane Smith',
                        'status' => 'complete'
                    ],
                    [
                        'label' => 'Final Approval',
                        'date' => '2025-03-03 09:15:33',
                        'assignee' => 'Robert Johnson',
                        'status' => 'complete'
                    ]
                ]
            ],
            [
                'form_id' => 1,
                'entry_id' => 124,
                'step_id' => 11,
                'step_name' => 'Budget Request',
                'status' => 'pending',
                'assignee_id' => $current_user_id, // Current user is the assignee
                'step_history' => [
                    [
                        'label' => 'Budget Request Created',
                        'date' => '2025-03-05 11:45:22',
                        'assignee' => 'Sarah Wilson',
                        'status' => 'complete'
                    ],
                    [
                        'label' => 'Manager Approval',
                        'date' => '2025-03-06 16:30:12',
                        'assignee' => 'Current User',
                        'status' => 'pending'
                    ],
                    [
                        'label' => 'Finance Review',
                        'date' => '',
                        'assignee' => 'Unassigned',
                        'status' => 'not_started'
                    ]
                ]
            ]
        ],
        'Form B' => [
            [
                'form_id' => 2,
                'entry_id' => 456,
                'step_id' => 20,
                'step_name' => 'Software Access Request',
                'status' => 'in_progress',
                'assignee_id' => $current_user_id, // Current user is the assignee
                'step_history' => [
                    [
                        'label' => 'Request Submission',
                        'date' => '2025-03-04 09:12:30',
                        'assignee' => 'Michael Brown',
                        'status' => 'complete'
                    ],
                    [
                        'label' => 'Manager Approval',
                        'date' => '2025-03-05 10:22:45',
                        'assignee' => 'Lisa Davis',
                        'status' => 'complete'
                    ],
                    [
                        'label' => 'IT Department Review',
                        'date' => '2025-03-07 14:05:18',
                        'assignee' => 'Current User',
                        'status' => 'in_progress'
                    ]
                ]
            ]
        ]
    ];
    
    // Status configurations with icons and colors
    $status_config = [
        'complete' => [
            'color' => '#00a654',
            'icon' => 'dashicons-yes',
            'label' => 'Completed'
        ],
        'in_progress' => [
            'color' => '#4986e7',
            'icon' => 'dashicons-arrow-right-alt',
            'label' => 'Current'
        ],
        'pending' => [
            'color' => '#ffba00',
            'icon' => 'dashicons-clock',
            'label' => 'Pending'
        ],
        'rejected' => [
            'color' => '#d54e21',
            'icon' => 'dashicons-no',
            'label' => 'Rejected'
        ],
        'reverted' => [
            'color' => '#e35b5b',
            'icon' => 'dashicons-undo',
            'label' => 'Reverted'
        ],
        'not_started' => [
            'color' => '#999999',
            'icon' => 'dashicons-minus',
            'label' => 'Not Started'
        ]
    ];
    
    // Enqueue jQuery UI for the accordion functionality
    wp_enqueue_script('jquery-ui-accordion');
    wp_enqueue_style('wp-jquery-ui-dialog');
    
    // Generate unique IDs for the accordion
    $accordion_id = 'workflow-accordion-' . rand(1000, 9999);
    
    // Output the custom box
    ?>
    <div class="postbox">
        <h3 class="hndle">
            <span><?php esc_html_e('Related Workflows', 'gravityflow'); ?></span>
        </h3>
        <div class="inside">
            <?php if (empty($related_workflows)): ?>
                <p><?php esc_html_e('No related workflows found.', 'gravityflow'); ?></p>
            <?php else: ?>
                <!-- Display status legend -->
                <div class="workflow-status-legend">
                    <?php foreach ($status_config as $status => $config): ?>
                        <div class="legend-item">
                            <span class="dashicons <?php echo esc_attr($config['icon']); ?>" style="color: <?php echo esc_attr($config['color']); ?>;"></span>
                            <span class="legend-label"><?php echo esc_html($config['label']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div id="<?php echo esc_attr($accordion_id); ?>">
                    <?php foreach ($related_workflows as $form_name => $workflows): ?>
                        <h4 class="form-header"><?php echo esc_html($form_name); ?></h4>
                        <div class="form-workflows">
                            <ul class="related-workflows">
                                <?php foreach ($workflows as $workflow_index => $workflow): ?>
                                    <?php 
                                    $status = $workflow['status'];
                                    $config = isset($status_config[$status]) ? $status_config[$status] : $status_config['not_started'];
                                    $view_url = admin_url('admin.php?page=gravityflow-inbox&view=entry&id=' . $workflow['entry_id'] . '&lid=' . $workflow['form_id']);
                                    $action_url = admin_url('admin.php?page=gravityflow-inbox&view=entry&id=' . $workflow['entry_id'] . '&lid=' . $workflow['form_id'] . '&action=take_action');
                                    
                                    // Determine if current user can take action
                                    $can_take_action = (
                                        ($workflow['status'] == 'in_progress' || $workflow['status'] == 'pending') && 
                                        $workflow['assignee_id'] == $current_user_id
                                    );
                                    
                                    // Determine if history should be collapsed by default
                                    $is_complete = ($workflow['status'] == 'complete');
                                    $collapse_class = $is_complete ? 'history-collapsed' : 'history-expanded';
                                    $history_id = 'workflow-history-' . $workflow['form_id'] . '-' . $workflow['entry_id'];
                                    ?>
                                    <li class="workflow-item <?php echo esc_attr($collapse_class); ?>">
                                        <div class="workflow-header">
                                            <span class="dashicons <?php echo esc_attr($config['icon']); ?>" style="color: <?php echo esc_attr($config['color']); ?>;"></span>
                                            <span class="workflow-info">
                                                <span class="workflow-name"><?php echo esc_html($workflow['step_name']); ?></span>
                                                <span class="workflow-entry-id">(Entry #<?php echo esc_html($workflow['entry_id']); ?>)</span>
                                                <span class="workflow-status"><?php echo esc_html($config['label']); ?></span>
                                            </span>
                                            <span class="workflow-actions">
                                                <a href="<?php echo esc_url($view_url); ?>" class="button button-secondary workflow-view-button">
                                                    <span class="dashicons dashicons-visibility"></span> View
                                                </a>
                                                <?php if ($can_take_action): ?>
                                                    <a href="<?php echo esc_url($action_url); ?>" class="button button-primary workflow-action-button">
                                                        <span class="dashicons dashicons-edit"></span> Take Action
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button" class="toggle-history button button-secondary" data-target="<?php echo esc_attr($history_id); ?>">
                                                    <span class="dashicons dashicons-arrow-down-alt2 toggle-icon"></span>
                                                </button>
                                            </span>
                                        </div>
                                        <div id="<?php echo esc_attr($history_id); ?>" class="workflow-history" style="<?php echo $is_complete ? 'display: none;' : ''; ?>">
                                            <table class="widefat fixed striped">
                                                <thead>
                                                    <tr>
                                                        <th class="column-step"><?php esc_html_e('Step', 'gravityflow'); ?></th>
                                                        <th class="column-date"><?php esc_html_e('Date', 'gravityflow'); ?></th>
                                                        <th class="column-assignee"><?php esc_html_e('Assignee', 'gravityflow'); ?></th>
                                                        <th class="column-status"><?php esc_html_e('Status', 'gravityflow'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($workflow['step_history'] as $step): ?>
                                                        <?php 
                                                        $step_status = $step['status'];
                                                        $step_config = isset($status_config[$step_status]) ? $status_config[$step_status] : $status_config['not_started'];
                                                        ?>
                                                        <tr>
                                                            <td class="column-step"><?php echo esc_html($step['label']); ?></td>
                                                            <td class="column-date"><?php echo !empty($step['date']) ? esc_html($step['date']) : '--'; ?></td>
                                                            <td class="column-assignee"><?php echo esc_html($step['assignee']); ?></td>
                                                            <td class="column-status">
                                                                <span class="dashicons <?php echo esc_attr($step_config['icon']); ?>" style="color: <?php echo esc_attr($step_config['color']); ?>;"></span>
                                                                <?php echo esc_html($step_config['label']); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Initialize the accordion
                    $("#<?php echo esc_js($accordion_id); ?>").accordion({
                        header: ".form-header",
                        collapsible: true,
                        heightStyle: "content",
                        active: false
                    });
                    
                    // Toggle history sections
                    $(".toggle-history").on("click", function(e) {
                        e.preventDefault();
                        var targetId = $(this).data("target");
                        $("#" + targetId).slideToggle(300);
                        $(this).find(".toggle-icon").toggleClass("dashicons-arrow-down-alt2 dashicons-arrow-up-alt2");
                        $(this).closest(".workflow-item").toggleClass("history-collapsed history-expanded");
                    });
                });
                </script>
            <?php endif; ?>
        </div>
    </div>
    <style>
        .related-workflows {
            margin-top: 0;
            padding-left: 0;
            list-style: none;
        }
        .workflow-item {
            margin-bottom: 12px;
            background: #f9f9f9;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
        }
        .workflow-header {
            display: flex;
            align-items: center;
            padding: 10px;
        }
        .workflow-info {
            flex-grow: 1;
            margin-left: 8px;
        }
        .workflow-name {
            font-weight: bold;
        }
        .workflow-entry-id {
            color: #666;
            margin-left: 5px;
        }
        .workflow-status {
            display: inline-block;
            margin-left: 10px;
            font-size: 12px;
            color: #777;
        }
        .workflow-actions {
            display: flex;
            gap: 5px;
        }
        .workflow-view-button .dashicons,
        .workflow-action-button .dashicons,
        .toggle-history .dashicons {
            margin-top: 3px;
            margin-right: 2px;
        }
        .workflow-status-legend {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
            margin-bottom: 5px;
        }
        .legend-label {
            font-size: 12px;
            margin-left: 3px;
        }
        .workflow-history {
            padding: 0 10px 10px 10px;
            border-top: 1px solid #e0e0e0;
        }
        .toggle-history {
            min-width: 30px;
        }
        .column-step {
            width: 30%;
        }
        .column-date {
            width: 25%;
        }
        .column-assignee {
            width: 25%;
        }
        .column-status {
            width: 20%;
        }
        .form-header {
            background: #f0f0f0;
            padding: 10px;
            margin: 10px 0 5px 0;
            cursor: pointer;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-workflows {
            padding: 0 10px;
        }
        .ui-accordion-header-active {
            background: #e9e9e9;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }
    </style>
    <?php
}

// Function to get real related workflows - replace the static data above with this
function get_related_workflows($current_form_id, $current_entry_id) {
    // This function would query your database to get real related workflows
    // For example, you might look for entries in other forms that reference this entry
    
    $related_workflows = [];
    
    // Example logic:
    // 1. Get all forms with Gravity Flow enabled
    // 2. For each form, search for entries with field values matching the current entry ID
    // 3. Get workflow step information for those entries
    // 4. Retrieve step history for each workflow
    
    // You would need to implement the actual logic based on your specific setup
    
    return $related_workflows;
}

/**
 * Get the step history for a specific workflow entry
 * 
 * @param int $form_id    The form ID
 * @param int $entry_id   The entry ID
 * @return array          Array of step history items
 */
function get_workflow_step_history($form_id, $entry_id) {
    // This is a placeholder function
    // In a real implementation, you would:
    // 1. Query the Gravity Flow tables to get the step history
    // 2. Format the data for display
    
    // Example using Gravity Flow API (you'll need to implement this based on your setup):
    $step_history = [];
    
    if (class_exists('Gravity_Flow')) {
        $api = new Gravity_Flow_API($form_id);
        $steps = $api->get_steps($entry_id);
        
        foreach ($steps as $step) {
            // Format the step data for display
            // You'll need to adjust this based on Gravity Flow's API
        }
    }
    
    return $step_history;
}
