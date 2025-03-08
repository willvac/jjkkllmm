<?php
/**
 * Plugin Name: Custom Gravity Flow Workflow Details
 * Description: Displays related workflows in the Gravity Flow entry details page with status icons and action buttons
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
                'step_name' => 'Approval',
                'status' => 'complete',
                'assignee_id' => 0 // No assignee for completed steps
            ],
            [
                'form_id' => 1,
                'entry_id' => 124,
                'step_id' => 11,
                'step_name' => 'User Input',
                'status' => 'pending',
                'assignee_id' => $current_user_id // Current user is the assignee
            ],
            [
                'form_id' => 1,
                'entry_id' => 125,
                'step_id' => 12,
                'step_name' => 'Review',
                'status' => 'rejected',
                'assignee_id' => 2 // Some other user
            ]
        ],
        'Form B' => [
            [
                'form_id' => 2,
                'entry_id' => 456,
                'step_id' => 20,
                'step_name' => 'Review',
                'status' => 'in_progress',
                'assignee_id' => $current_user_id // Current user is the assignee
            ],
            [
                'form_id' => 2,
                'entry_id' => 457,
                'step_id' => 21,
                'step_name' => 'Final Approval',
                'status' => 'reverted',
                'assignee_id' => 3 // Some other user
            ],
            [
                'form_id' => 2,
                'entry_id' => 458,
                'step_id' => 22,
                'step_name' => 'Documentation',
                'status' => 'not_started',
                'assignee_id' => 0 // No assignee yet
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
                
                <?php foreach ($related_workflows as $form_name => $workflows): ?>
                    <h4><?php echo esc_html($form_name); ?></h4>
                    <ul class="related-workflows">
                        <?php foreach ($workflows as $workflow): ?>
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
                            ?>
                            <li class="workflow-item">
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
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <style>
        .related-workflows {
            margin-top: 0;
            padding-left: 10px;
            list-style: none;
        }
        .workflow-item {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
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
        .workflow-action-button .dashicons {
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
    
    // You would need to implement the actual logic based on your specific setup
    
    return $related_workflows;
}

/**
 * Determine if current user is assignee for a workflow step
 * 
 * @param int $form_id    The form ID
 * @param int $entry_id   The entry ID
 * @param int $step_id    The step ID
 * @return bool           True if current user is assignee, false otherwise
 */
function is_current_user_assignee($form_id, $entry_id, $step_id) {
    // This is a placeholder function
    // In a real implementation, you would:
    // 1. Get the current step for the entry
    // 2. Check if the current user is assigned to that step
    
    // Example using Gravity Flow API (you'll need to implement this based on your setup):
    if (class_exists('Gravity_Flow')) {
        $flow = Gravity_Flow::get_instance();
        $step = $flow->get_current_step($form_id, $entry_id);
        
        if ($step && $step->get_id() == $step_id) {
            return $step->is_assignee(wp_get_current_user());
        }
    }
    
    return false;
}
