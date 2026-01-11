<?php
/**
 * Plugin Name: Simple Todo Manager Pro
 * Plugin URI: https://github.com/shawon25800/simple-todo-manager
 * Description: Team task manager with role-based access, assignment, notifications. Built with Grok AI 🚀
 * Version: 3.1
 * Author: Shawon
 * Author URI: https://github.com/shawon25800
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Global tasks
function stm_get_todos() {
    return get_option('stm_global_todos', array());
}

function stm_save_todos($todos) {
    update_option('stm_global_todos', $todos);
}

// Notifications
function stm_add_notification($user_id, $message) {
    $notifs = get_user_meta($user_id, 'stm_notifications', true) ?: array();
    $notifs[] = array(
        'message' => $message,
        'time' => current_time('mysql'),
        'read' => false
    );
    update_user_meta($user_id, 'stm_notifications', array_slice($notifs, -50));
}

function stm_get_notifications($user_id) {
    return get_user_meta($user_id, 'stm_notifications', true) ?: array();
}

function stm_mark_notifications_read($user_id) {
    $notifs = stm_get_notifications($user_id);
    foreach ($notifs as &$n) {
        $n['read'] = true;
    }
    update_user_meta($user_id, 'stm_notifications', $notifs);
}

// AJAX - Mark notifications as read
function stm_mark_notifications_read_ajax() {
    check_ajax_referer('stm_nonce', 'nonce');
    stm_mark_notifications_read(get_current_user_id());
    wp_send_json_success();
}
add_action('wp_ajax_stm_mark_notifications_read', 'stm_mark_notifications_read_ajax');

// Auto create page
function stm_create_my_tasks_page() {
    if (get_page_by_path('my-tasks')) return;

    $page_id = wp_insert_post([
        'post_title'   => 'My Tasks',
        'post_name'    => 'my-tasks',
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'page'
    ]);

    if ($page_id && !is_wp_error($page_id)) {
        update_post_meta($page_id, '_wp_page_template', 'my-tasks-template.php');
    }
}
register_activation_hook(__FILE__, 'stm_create_my_tasks_page');

// Admin menu
function stm_admin_menu() {
    add_menu_page(
        'Task Manager',
        'Tasks',
        'read',  // এটা সব logged-in user দেখবে
        'simple-todo-manager',
        'stm_todo_page',
        'dashicons-list-view',
        80
    );
}
add_action('admin_menu', 'stm_admin_menu');

// Admin Dashboard - full control
function stm_todo_page() {
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url());
        exit;
    }

    $is_admin = current_user_can('manage_options');
    $current_user_id = get_current_user_id();
    $all_tasks = stm_get_todos();

    // CRITICAL FIX: Non-admin sees ONLY assigned tasks
    $tasks = $all_tasks;
    if (!$is_admin) {
        $tasks = array_filter($all_tasks, function($task) use ($current_user_id) {
            return isset($task['assigned_to']) && $task['assigned_to'] == $current_user_id;
        });
    }

    // Pass filtered tasks to JS (this is what fixes the issue)
    ?>
    <script>
        window.initialTasks = <?php echo json_encode(array_values($tasks)); ?>;
    </script>
    <?php

    // Pass tasks to JS for initial render (optional, if you want to skip AJAX load)
    ?>
    <div class="wrap" style="background:#fff; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;">
        <div style="width:100%; max-width:900px; background:rgba(255,255,255,0.95); border-radius:30px; padding:50px 40px; box-shadow:0 25px 60px rgba(0,0,0,0.12); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px);">
            <h1 style="text-align:center; color:#2c3e50; font-size:42px; margin-bottom:50px; font-weight:600;">
                <!-- Bell Icon + Badge -->
<div id="notification-bell" style="position:absolute; top:40px; right:40px; cursor:pointer; font-size:34px; z-index:100;">
    <!-- Sundor 3D Animated Bell -->
<svg id="bell-icon" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3)); transition: transform 0.3s ease;">
  <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8V14L4 16V17H20V16L18 14V8Z" fill="url(#bellGradient)"/>
  <path d="M9.5 21C9.5 21.8284 10.1716 22.5 11 22.5C11.8284 22.5 12.5 21.8284 12.5 21H9.5Z" fill="#6c5ce7"/>
  <defs>
    <linearGradient id="bellGradient" x1="6" y1="2" x2="18" y2="22" gradientUnits="userSpaceOnUse">
      <stop stop-color="#6c5ce7"/>
      <stop offset="1" stop-color="#a29bfe"/>
    </linearGradient>
  </defs>
</svg>

<style>
  #bell-icon:hover {
    transform: scale(1.15) rotate(15deg);
    animation: ring 0.8s ease-in-out;
  }
  @keyframes ring {
    0% { transform: rotate(0deg); }
    10% { transform: rotate(-15deg); }
    20% { transform: rotate(15deg); }
    30% { transform: rotate(-10deg); }
    40% { transform: rotate(10deg); }
    50% { transform: rotate(0deg); }
    100% { transform: rotate(0deg); }
  }
</style>
    <?php
    $notifs = stm_get_notifications(get_current_user_id());
    $unread = count(array_filter($notifs, function($n) { return !$n['read']; }));
    if ($unread > 0):
    ?>
        <span id="unread-count" style="position:absolute; top:-12px; right:-12px; background:#e74c3c; color:white; border-radius:50%; width:26px; height:26px; font-size:12px; font-weight:bold; display:flex; align-items:center; justify-content:center;">
            <?php echo $unread; ?>
        </span>
    <?php endif; ?>
</div>

<!-- Dropdown Box -->
<div id="notification-dropdown" style="display:none; position:absolute; top:90px; right:20px; width:400px; background:rgba(255,255,255,0.98); border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.25); z-index:1000; max-height:480px; overflow-y:auto; backdrop-filter:blur(12px);">
    <div style="padding:20px; border-bottom:1px solid #eee; text-align:center;">
        <strong style="font-size:22px;">Notifications</strong>
    </div>
    <div style="padding:15px;">
        <?php
        if (empty($notifs)) {
            echo '<p style="text-align:center; color:#888; padding:40px 0;">No notifications yet.</p>';
        } else {
            foreach ($notifs as $n) {
                $bg = !$n['read'] ? 'background:rgba(108,92,231,0.08);' : 'background:#f8f9fa;';
                echo '<div style="padding:15px; margin-bottom:10px; border-radius:12px; ' . $bg . '">';
                echo '<p style="margin:0 0 6px 0; font-size:15px;">' . esc_html($n['message']) . '</p>';
                echo '<small style="color:#888; font-size:13px;">' . human_time_diff(strtotime($n['time']), current_time('timestamp')) . ' ago</small>';
                echo '</div>';
            }
        }
        ?>
    </div>
</div>
                <?php echo $is_admin ? 'Full Task Manager (Admin)' : 'My Assigned Tasks'; ?>
            </h1>

            <?php if ($is_admin): ?>
                <!-- Admin only: Add task section -->
                <div style="margin-bottom:50px; text-align:center;">
                    <input type="text" id="new-todo" placeholder="Add new task..." style="width:70%; padding:18px 25px; font-size:18px; border:none; border-radius:15px; background:#f1f3f4; color:#333; outline:none; box-shadow:0 5px 15px rgba(0,0,0,0.1);" />
                    <select id="task-priority" style="width:70%; margin-top:15px; padding:18px; font-size:18px; border:none; border-radius:15px; background:#f1f3f4;">
                        <option value="">Priority (optional)</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                    <textarea id="task-subtasks" placeholder="Subtasks (one per line, optional)" style="width:70%; margin-top:15px; padding:18px; font-size:18px; border:none; border-radius:15px; background:#f1f3f4; height:120px;"></textarea>
                    <select id="task-assignee" style="width:70%; margin-top:15px; padding:18px; font-size:18px; border:none; border-radius:15px; background:#f1f3f4;">
                        <option value="">Assign to (optional)</option>
                        <?php
                        $users = get_users();
                        foreach ($users as $user) {
                            if ($user->ID == $current_user_id) continue;
                            echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
                        }
                        ?>
                    </select>
                    <button id="add-todo" style="padding:18px 40px; margin-top:20px; background:#6c5ce7; color:white; border:none; border-radius:15px; font-size:18px; cursor:pointer; box-shadow:0 5px 15px rgba(108,92,231,0.4);">Add Task</button>
                </div>
            <?php endif; ?>

            <div style="margin-bottom:40px; text-align:center;">
                <input type="text" id="search-todo" placeholder="Search tasks..." style="width:60%; padding:15px; font-size:16px; border:1px solid #ddd; border-radius:15px; outline:none;" />
            </div>

            <?php if ($is_admin): ?>
                <div style="margin-bottom:40px; text-align:center;">
                    <button id="filter-all" class="filter-btn active" style="padding:12px 30px; margin:0 8px; background:#6c5ce7; color:white; border:none; border-radius:12px; font-size:16px; cursor:pointer;">All</button>
                    <button id="filter-active" class="filter-btn" style="padding:12px 30px; margin:0 8px; background:#95a5a6; color:white; border:none; border-radius:12px; font-size:16px; cursor:pointer;">Active</button>
                    <button id="filter-completed" class="filter-btn" style="padding:12px 30px; margin:0 8px; background:#27ae60; color:white; border:none; border-radius:12px; font-size:16px; cursor:pointer;">Completed</button>
                    <button id="clear-all" style="padding:12px 30px; margin-left:40px; background:#e74c3c; color:white; border:none; border-radius:12px; font-size:16px; cursor:pointer;">Clear All</button>
                </div>
            <?php endif; ?>

            <ul id="todo-list" style="list-style:none; padding:0;"></ul>
            <div id="progress" style="margin-top:60px; text-align:center; color:#555; font-size:20px; font-weight:500;"></div>
        </div>
    </div>

    <!-- Admin only confirm dialogs -->
     
    <?php if ($is_admin): ?>
        <div id="custom-confirm" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; backdrop-filter:blur(5px);">
            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:40px; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center; max-width:420px; width:90%;">
                <p style="font-size:20px; margin-bottom:40px; color:#333;">Are you sure you want to delete this task?</p>
                <button id="confirm-yes" style="padding:14px 40px; background:#e74c3c; color:white; border:none; border-radius:12px; margin:0 15px; cursor:pointer; font-size:16px;">Yes, Delete</button>
                <button id="confirm-no" style="padding:14px 40px; background:#95a5a6; color:white; border:none; border-radius:12px; margin:0 15px; cursor:pointer; font-size:16px;">Cancel</button>
            </div>
        </div>

        <div id="clear-all-confirm" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; backdrop-filter:blur(5px);">
            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:40px; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center; max-width:420px; width:90%;">
                <p style="font-size:20px; margin-bottom:40px; color:#333;">Delete ALL tasks?<br><strong>This cannot be undone!</strong></p>
                <button id="clear-all-yes" style="padding:14px 40px; background:#e74c3c; color:white; border:none; border-radius:12px; margin:0 15px; cursor:pointer; font-size:16px;">Yes, Delete All</button>
                <button id="clear-all-no" style="padding:14px 40px; background:#95a5a6; color:white; border:none; border-radius:12px; margin:0 15px; cursor:pointer; font-size:16px;">Cancel</button>
            </div>
        </div>
    <?php endif; ?>
<!-- Custom Success Popup -->
<div id="success-popup" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; backdrop-filter:blur(5px);">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:rgba(255,255,255,0.95); border-radius:25px; padding:40px; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center; max-width:400px; width:90%; backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px);">
        <h2 style="color:#2c3e50; font-size:28px; margin-bottom:20px;">Success!</h2>
        <p style="font-size:18px; color:#555; margin-bottom:30px;">Task added successfully.</p>
        <button id="close-success" style="padding:12px 40px; background:#6c5ce7; color:white; border:none; border-radius:15px; font-size:16px; cursor:pointer; box-shadow:0 5px 15px rgba(108,92,231,0.4);">OK</button>
    </div>
</div>
    <script>
        window.initialTasks = <?php echo json_encode(array_values($tasks)); ?>;
    </script>

    <style>
        .filter-btn.active {
            background: #6c5ce7 !important;
        }
        .priority-high { color: #e74c3c; font-weight: bold; }
        .priority-medium { color: #f39c12; }
        .priority-low { color: #27ae60; }
        .subtasks ul { margin: 10px 0 0 20px; padding: 0; font-size: 14px; color: #555; }
    </style>
    <?php
}
// AJAX handlers with role checks
function stm_add_todo() {
    check_ajax_referer('stm_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Only admin can add tasks']);
    }

    $text = sanitize_text_field($_POST['text'] ?? '');
    $priority = sanitize_text_field($_POST['priority'] ?? '');
    $subtasks = array_filter(array_map('sanitize_text_field', explode("\n", $_POST['subtasks'] ?? '')));
    $assignee_id = intval($_POST['assignee'] ?? 0);

    if (empty($text)) {
        wp_send_json_error(['message' => 'Task text is required']);
    }

    $todos = stm_get_todos();
    $new_task = array(
        'id' => time(),
        'text' => $text,
        'completed' => false,
        'due_date' => null,
        'priority' => $priority,
        'subtasks' => $subtasks,
        'assigned_to' => $assignee_id,
        'assigned_name' => $assignee_id ? get_user_by('id', $assignee_id)->display_name : ''
    );

    $todos[] = $new_task;
    stm_save_todos($todos);

    if ($assignee_id) {
        stm_add_notification($assignee_id, "New task assigned: " . $text);
    }

    wp_send_json_success($todos);
}
add_action('wp_ajax_stm_add_todo', 'stm_add_todo');
function stm_load_todos() {
    $todos = stm_get_todos();
    if (!current_user_can('manage_options')) {
        $current_user_id = get_current_user_id();
        $todos = array_filter($todos, function($task) use ($current_user_id) {
            return isset($task['assigned_to']) && $task['assigned_to'] == $current_user_id;
        });
    }
    wp_send_json_success(array_values($todos));
}
add_action('wp_ajax_stm_load_todos', 'stm_load_todos');

function stm_toggle_complete() {
    check_ajax_referer('stm_nonce', 'nonce');

    $todo_id = intval($_POST['todo_id']);
    $todos = stm_get_todos();
    $user_id = get_current_user_id();

    foreach ($todos as &$todo) {
        if ($todo['id'] == $todo_id) {
            if ($todo['assigned_to'] != $user_id && !current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized');
            }

            $old_status = $todo['completed'];
            $todo['completed'] = !$todo['completed'];

            if (!$old_status && $todo['completed'] && $todo['assigned_to']) {
                $admins = get_users(array('role' => 'administrator'));
                foreach ($admins as $admin) {
                    stm_add_notification($admin->ID, get_user_by('id', $todo['assigned_to'])->display_name . " completed task: " . $todo['text']);
                }
            }
            break;
        }
    }

    stm_save_todos($todos);
    wp_send_json_success($todos);
}
add_action('wp_ajax_stm_toggle_complete', 'stm_toggle_complete');

// Delete, edit, clear – admin only
function stm_delete_todo() {
    check_ajax_referer('stm_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    $todo_id = intval($_POST['todo_id']);
    $todos = stm_get_todos();
    $new_todos = array_filter($todos, fn($t) => $t['id'] != $todo_id);
    stm_save_todos(array_values($new_todos));
    wp_send_json_success($new_todos);
}
add_action('wp_ajax_stm_delete_todo', 'stm_delete_todo');

function stm_edit_todo() {
    check_ajax_referer('stm_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    $todo_id = intval($_POST['todo_id']);
    $text = sanitize_text_field($_POST['text']);
    $due_date = sanitize_text_field($_POST['due_date']);
    $todos = stm_get_todos();
    foreach ($todos as &$todo) {
        if ($todo['id'] == $todo_id) {
            $todo['text'] = $text;
            $todo['due_date'] = $due_date ?: null;
            break;
        }
    }
    stm_save_todos($todos);
    wp_send_json_success($todos);
}
add_action('wp_ajax_stm_edit_todo', 'stm_edit_todo');

function stm_clear_all_todos() {
    check_ajax_referer('stm_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    stm_save_todos(array());
    wp_send_json_success();
}
add_action('wp_ajax_stm_clear_all_todos', 'stm_clear_all_todos');

function stm_update_todo_order() {
    check_ajax_referer('stm_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    wp_send_json_success();
}
add_action('wp_ajax_stm_update_todo_order', 'stm_update_todo_order');

// Force redirect non-admin to /my-tasks on login
function stm_force_redirect_non_admin($redirect_to, $request, $user) {
    // If user is object and not admin
    if (is_object($user) && isset($user->ID) && !user_can($user, 'manage_options')) {
        return home_url('/my-tasks');
    }
    return $redirect_to;
}
add_filter('login_redirect', 'stm_force_redirect_non_admin', 9999, 3); // very high priority

// Enqueue admin scripts
function stm_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_simple-todo-manager') {
        return;
    }

    wp_enqueue_script('jquery');
    wp_enqueue_script('sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js', array('jquery'), '1.15.2', true);
    wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), null, true);

    wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            var nonce = "' . wp_create_nonce('stm_nonce') . '";
            var isAdmin = ' . (current_user_can('manage_options') ? 'true' : 'false') . ';
            var currentFilter = "all";
            var pendingDeleteId = null;

            function loadTodos()
             {
                $.post(ajaxurl, {
                    action: "stm_load_todos",
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        renderTodos(response.data);
                        updateProgress(response.data);
                        if (isAdmin) initSortable();
                    }
                });
            }

            function renderTodos(todos) {
                var searchVal = $("#search-todo").val().toLowerCase();
                var filtered = todos.filter(function(todo) {
                    var matchesSearch = todo.text.toLowerCase().includes(searchVal);
                    if (currentFilter === "active") return matchesSearch && !todo.completed;
                    if (currentFilter === "completed") return matchesSearch && todo.completed;
                    return matchesSearch;
                });

                var list = $("#todo-list");
                list.empty();

                filtered.forEach(function(todo) {
                    var li = $("<li>").attr("data-id", todo.id).css({
                        padding: "25px",
                        background: "rgba(255,255,255,0.9)",
                        margin: "20px 0",
                        borderRadius: "20px",
                        boxShadow: "0 10px 30px rgba(0,0,0,0.1)",
                        display: "flex",
                        alignItems: "center",
                        color: "#333",
                        cursor: isAdmin ? "move" : "default"
                    });

                    var checkbox = $("<input type=\'checkbox\'>").prop("checked", todo.completed).css({
                        width: "22px",
                        height: "22px",
                        cursor: "pointer"
                    });

                    checkbox.on("change", function() {
                        $.post(ajaxurl, {
                            action: "stm_toggle_complete",
                            todo_id: todo.id,
                            nonce: nonce
                        }, function() {
                            loadTodos();
                            
                        });
                    });
// Notification bell & dropdown - 100% fixed version
$("#notification-bell").on("click", function(e) {
    e.preventDefault();          // no default action
    e.stopImmediatePropagation(); // stop all bubbling instantly
    $("#notification-dropdown").toggle(200); // smooth toggle

    if ($("#notification-dropdown").is(":visible") && $("#unread-count").length > 0) {
        $.post(ajaxurl, {
            action: "stm_mark_notifications_read",
            nonce: nonce
        }, function() {
            $("#unread-count").fadeOut(300, function() { $(this).remove(); });
        });
    }
});

// Outside click close - super safe version
$(document).on("click", function(e) {
    // যদি ক্লিক dropdown বা bell-এর ভিতরে না হয় তবেই close
    if (!$(e.target).closest("#notification-dropdown, #notification-bell").length) {
        $("#notification-dropdown").hide(200);
    }
});
                    var viewWrapper = $("<div>").css({
                        flex: "1",
                        display: "flex",
                        flexDirection: "column"
                    });

                    var headerDiv = $("<div>").css({
                        display: "flex",
                        alignItems: "center"
                    });

                    var textSpan = $("<span>").text(todo.text).css({
                        "font-size": "18px",
                        "font-weight": "500",
                        flex: "1"
                    });

                    if (todo.priority) {
                        textSpan.addClass("priority-" + todo.priority);
                    }

                    var dueSpan = $("<span>").text(todo.due_date ? "Due: " + todo.due_date : "").css({
                        "font-size": "14px",
                        "color": "#888",
                        "margin-left": "20px"
                    });

                    var assignedSpan = $("<span>").text(todo.assigned_name ? "Assigned to: " + todo.assigned_name : "").css({
                        "font-size": "14px",
                        "color": "#555",
                        "margin-left": "20px"
                    });

                    headerDiv.append(textSpan, dueSpan, assignedSpan);

                    var subtasksDiv = $("<div>").addClass("subtasks");
                    if (todo.subtasks && todo.subtasks.length > 0) {
                        var ul = $("<ul>");
                        todo.subtasks.forEach(function(sub) {
                            ul.append("<li>" + sub + "</li>");
                        });
                        subtasksDiv.append(ul);
                    }

                    viewWrapper.append(headerDiv, subtasksDiv);

                    if (isAdmin) {
                        var editForm = $("<div>").addClass("edit-form").hide();

                        var editInput = $("<input type=\'text\'>").val(todo.text);

                        var dateInput = $("<input type=\'text\'>").val(todo.due_date || "");

                        var saveBtn = $("<button>").addClass("save-btn").text("Save");

                        var cancelBtn = $("<button>").addClass("cancel-btn").text("Cancel");

                        editForm.append(editInput, dateInput, saveBtn, cancelBtn);

                        var oldText = todo.text;
                        var oldDue = todo.due_date || "";

                        viewWrapper.on("dblclick", function(e) {
                            e.stopPropagation();
                            viewWrapper.hide();
                            editForm.show();
                            editInput.focus();
                            if (!dateInput.hasClass("flatpickr-input")) {
                                dateInput.flatpickr({
                                    dateFormat: "Y-m-d"
                                });
                            }
                        });

                        cancelBtn.on("click", function() {
                            editInput.val(oldText);
                            dateInput.val(oldDue);
                            editForm.hide();
                            viewWrapper.show();
                        });

                        saveBtn.on("click", function() {
                            var newText = editInput.val().trim();
                            var newDue = dateInput.val().trim();
                            if (!newText) return;

                            $.post(ajaxurl, {
                                action: "stm_edit_todo",
                                todo_id: todo.id,
                                text: newText,
                                due_date: newDue,
                                nonce: nonce
                            }, function() {
                                loadTodos();
                            });
                        });

                        var deleteBtn = $("<button>").text("Delete").css({
                            marginLeft: "20px",
                            background: "#e74c3c",
                            color: "white",
                            border: "none",
                            padding: "10px 20px",
                            borderRadius: "12px",
                            cursor: "pointer"
                        });

                        deleteBtn.on("click", function() {
                            pendingDeleteId = todo.id;
                            $("#custom-confirm").fadeIn(200);
                        });

                        li.append(checkbox, viewWrapper, editForm, deleteBtn);
                    } else {
                        li.append(checkbox, viewWrapper);
                    }

                    list.append(li);
                });

                updateProgress(filtered);
            }

            function updateProgress(todos) {
                var completed = todos.filter(t => t.completed).length;
                var total = todos.length;
                var percent = total > 0 ? Math.round((completed / total) * 100) : 0;
                $("#progress").html("<strong>Progress: " + completed + "/" + total + " (" + percent + "%)</strong>");
            }

            function initSortable() {
                var el = document.getElementById("todo-list");
                if (el && typeof Sortable !== "undefined" && isAdmin) {
                    Sortable.create(el, {
                        animation: 150,
                        ghostClass: "sortable-ghost",
                        onEnd: function () {
                            var order = [];
                            $("#todo-list li").each(function() {
                                order.push($(this).data("id"));
                            });

                            $.post(ajaxurl, {
                                action: "stm_update_todo_order",
                                order: order,
                                nonce: nonce
                            });
                        }
                    });
                }
            }

            $("#search-todo").on("input", function() {
                loadTodos();
            });

            $(document).on("click", ".filter-btn", function() {
                $(".filter-btn").removeClass("active").css("background", "#95a5a6");
                $(this).addClass("active").css("background", "#6c5ce7");
                currentFilter = this.id.replace("filter-", "");
                loadTodos();
            });

            $("#clear-all").on("click", function() {
                $("#clear-all-confirm").fadeIn(200);
            });

            $("#clear-all-yes").on("click", function() {
                $.post(ajaxurl, {
                    action: "stm_clear_all_todos",
                    nonce: nonce
                }, function() {
                    loadTodos();
                    $("#clear-all-confirm").fadeOut(200);
                });
            });

            $("#clear-all-no").on("click", function() {
                $("#clear-all-confirm").fadeOut(200);
            });

            $(document).on("click", "#confirm-yes", function() {
                if (pendingDeleteId !== null) {
                    $.post(ajaxurl, {
                        action: "stm_delete_todo",
                        todo_id: pendingDeleteId,
                        nonce: nonce
                    }, function() {
                        loadTodos();
                    });
                }
                $("#custom-confirm").fadeOut(200);
                pendingDeleteId = null;
            });

            $(document).on("click", "#confirm-no", function() {
                $("#custom-confirm").fadeOut(200);
                pendingDeleteId = null;
            });

            $("#add-todo").on("click", function() {
    var text = $("#new-todo").val().trim();
    var priority = $("#task-priority").val();
    var subtasks = $("#task-subtasks").val();
    var assignee = $("#task-assignee").val() || 0;

    if (!text) {
        alert("Task text is required!");
        return;
    }

    $.post(ajaxurl, {
        action: "stm_add_todo",
        text: text,
        priority: priority,
        subtasks: subtasks,
        assignee: assignee,
        nonce: nonce
    }, function(response) {
        if (response.success) {
            $("#new-todo").val("");
            $("#task-priority").val("");
            $("#task-subtasks").val("");
            $("#task-assignee").val("");
            loadTodos();

            // Show custom glassmorphism popup
            $("#success-popup").fadeIn(300);
        } else {
            alert("Error: " + (response.data?.message || "Something went wrong"));
            console.log("Server response:", response);
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        alert("AJAX failed! Check console.");
        console.error("AJAX error:", textStatus, errorThrown);
    });
});

// Close popup
$("#close-success").on("click", function() {
    $("#success-popup").fadeOut(300);
});

// Close on outside click
$("#success-popup").on("click", function(e) {
    if (e.target === this) {
        $(this).fadeOut(300);
    }
});

            loadTodos();
        });
    ');
}
add_action('admin_enqueue_scripts', 'stm_enqueue_scripts');