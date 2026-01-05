<?php
/**
 * Plugin Name: Simple Todo Manager
 * Plugin URI: https://github.com/shawon25800/simple-todo-manager
 * Description: A clean and powerful personal todo list plugin with AJAX, drag & drop, inline edit, due date, search & filter, task assignment, priorities, subtasks. Built with Grok AI 🚀
 * Version: 1.0
 * Author: Shawon
 * Author URI: https://github.com/shawon25800
 * License: GPL2
 * Text Domain: simple-todo-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

// Day 11: অ্যাডমিন মেনু
function stm_admin_menu() {
    add_menu_page(
        'Todo Manager',
        'My Todos',
        'manage_options',
        'simple-todo-manager',
        'stm_todo_page',
        '',
        80
    );
}
add_action('admin_menu', 'stm_admin_menu');

// Day 18: টুডু পেজ – priorities + subtasks + assignment
function stm_todo_page() {
    ?>
    <div class="wrap" style="background:#fff; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;">
        <div style="width:100%; max-width:700px; background:rgba(255,255,255,0.95); border-radius:25px; padding:50px 40px; box-shadow:0 20px 50px rgba(0,0,0,0.1); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px);">
            <h1 style="text-align:center; color:#333; font-size:36px; margin-bottom:50px;">My Todo List</h1>

            <div id="todo-app">
                <!-- Add Task -->
                <div style="margin-bottom:40px; text-align:center;">
                    <input type="text" id="new-todo" placeholder="Add new task..." style="width:70%; padding:18px 25px; font-size:18px; border:none; border-radius:15px; background:#f1f3f4; color:#333; outline:none; box-shadow:0 5px 15px rgba(0,0,0,0.1);" />
                    <select id="task-priority" style="width:70%; margin-top:10px; padding:18px; font-size:18px; border:none; border-radius:15px; background:#f1f3f4;">
                        <option value="">Priority (optional)</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                    <textarea id="task-subtasks" placeholder="Subtasks (one per line, optional)" style="width:70%; margin-top:10px; padding:18px; font-size:18px; border:none; border-radius:15px; background:#f1f3f4; height:100px;"></textarea>
                    <select id="task-assignee" style="width:70%; margin-top:10px; padding:18px; font-size:18px; border:none; border-radius:15px; background:#f1f3f4;">
                        <option value="">Assign to (optional)</option>
                        <?php
                        $users = get_users();
                        foreach ($users as $user) {
                            echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . '</option>';
                        }
                        ?>
                    </select>
                    <button id="add-todo" style="padding:18px 30px; margin-top:10px; background:#6c5ce7; color:white; border:none; border-radius:15px; font-size:18px; cursor:pointer; box-shadow:0 5px 15px rgba(108,92,231,0.4);">Add Task</button>
                </div>

                <!-- Search -->
                <div style="margin-bottom:30px; text-align:center;">
                    <input type="text" id="search-todo" placeholder="Search tasks..." style="width:50%; padding:15px; font-size:16px; border:1px solid #ddd; border-radius:15px; outline:none;" />
                </div>

                <!-- Filter + Clear All -->
                <div style="margin-bottom:30px; text-align:center;">
                    <button id="filter-all" class="filter-btn active" style="padding:10px 20px; margin:0 5px; background:#6c5ce7; color:white; border:none; border-radius:10px;">All</button>
                    <button id="filter-active" class="filter-btn" style="padding:10px 20px; margin:0 5px; background:#95a5a6; color:white; border:none; border-radius:10px;">Active</button>
                    <button id="filter-completed" class="filter-btn" style="padding:10px 20px; margin:0 5px; background:#27ae60; color:white; border:none; border-radius:10px;">Completed</button>
                    <button id="clear-all" style="padding:10px 20px; margin-left:30px; background:#e74c3c; color:white; border:none; border-radius:10px;">Clear All</button>
                </div>

                <ul id="todo-list" style="list-style:none; padding:0;"></ul>

                <div id="progress" style="margin-top:50px; text-align:center; color:#555; font-size:20px;"></div>
            </div>
        </div>
    </div>

    <!-- Confirm Dialogs -->
    <div id="custom-confirm" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:40px; border-radius:20px; box-shadow:0 15px 40px rgba(0,0,0,0.3); text-align:center; max-width:400px; width:90%;">
            <p style="font-size:20px; margin-bottom:40px; color:#333;">Are you sure you want to delete this task?</p>
            <button id="confirm-yes" style="padding:12px 30px; background:#e74c3c; color:white; border:none; border-radius:12px; margin:0 15px; cursor:pointer; font-size:16px;">Yes, Delete</button>
            <button id="confirm-no" style="padding:12px 30px; background:#95a5a6; color:white; border:none; border-radius:12px; margin:0 15px; cursor:pointer; font-size:16px;">Cancel</button>
        </div>
    </div>

    <div id="clear-all-confirm" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:40px; border-radius:20px; box-shadow:0 15px 40px rgba(0,0,0,0.3); text-align:center; max-width:400px; width:90%;">
            <p style="font-size:20px; margin-bottom:40px; color:#333;">Delete ALL tasks?<br><strong>This cannot be undone!</strong></p>
            <button id="clear-all-yes" style="padding:12px 30px; background:#e74c3c; color:white; border:none; border-radius:12px; margin:0 15px; cursor:pointer; font-size:16px;">Yes, Delete All</button>
            <button id="clear-all-no" style="padding:12px 30px; background:#95a5a6; color:white; border:none; border-radius:12px; margin:0 15px; cursor:pointer; font-size:16px;">Cancel</button>
        </div>
    </div>

    <style>
        #todo-list li {
            cursor: move;
        }
        .sortable-ghost {
            opacity: 0.4;
        }
        .edit-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        .edit-form input, .edit-form textarea {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        .edit-form input[type="text"], .edit-form textarea {
            flex: 1;
        }
        .edit-form button {
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            color: white;
        }
        .edit-form .save-btn {
            background: #27ae60;
        }
        .edit-form .cancel-btn {
            background: #95a5a6;
        }
        .priority-high { color: #e74c3c; font-weight: bold; }
        .priority-medium { color: #f39c12; }
        .priority-low { color: #27ae60; }
        .subtasks {
            margin-top: 10px;
            font-size: 14px;
            color: #555;
        }
        .subtasks ul {
            margin: 5px 0 0 20px;
            padding: 0;
        }
    </style>
    <?php
}

// Day 12: টুডু ডাটা সেভ + লোড
function stm_get_todos() {
    $todos = get_user_meta(get_current_user_id(), 'stm_todos', true);
    return $todos ? $todos : array();
}

function stm_save_todos($todos) {
    update_user_meta(get_current_user_id(), 'stm_todos', $todos);
}

// Day 18: AJAX - নতুন টাস্ক অ্যাড + priority + subtasks + assignment + email
function stm_add_todo() {
    check_ajax_referer('stm_nonce', 'nonce');

    $text = sanitize_text_field($_POST['text']);
    $priority = sanitize_text_field($_POST['priority'] ?? '');
    $subtasks = array_filter(array_map('sanitize_text_field', explode("\n", $_POST['subtasks'] ?? '')));
    $assignee_id = intval($_POST['assignee'] ?? 0);

    if (empty($text)) {
        wp_send_json_error('Task cannot be empty');
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
        $user = get_user_by('id', $assignee_id);
        $subject = "New Task Assigned: $text";
        $message = "Hello {$user->display_name},\n\nA new task has been assigned to you:\n\n$text\n\nLogin to view your tasks.";
        wp_mail($user->user_email, $subject, $message);
    }

    wp_send_json_success($todos);
}
add_action('wp_ajax_stm_add_todo', 'stm_add_todo');

// Day 12: AJAX - টুডু লোড
function stm_load_todos() {
    wp_send_json_success(stm_get_todos());
}
add_action('wp_ajax_stm_load_todos', 'stm_load_todos');

// Day 13: AJAX - কমপ্লিট মার্ক টগল
function stm_toggle_complete() {
    check_ajax_referer('stm_nonce', 'nonce');
    $todo_id = intval($_POST['todo_id']);
    $todos = stm_get_todos();
    foreach ($todos as &$todo) {
        if ($todo['id'] == $todo_id) {
            $todo['completed'] = !$todo['completed'];
            break;
        }
    }
    stm_save_todos($todos);
    wp_send_json_success($todos);
}
add_action('wp_ajax_stm_toggle_complete', 'stm_toggle_complete');

// Day 13: AJAX - টাস্ক ডিলিট
function stm_delete_todo() {
    check_ajax_referer('stm_nonce', 'nonce');
    $todo_id = intval($_POST['todo_id']);
    $todos = stm_get_todos();
    $new_todos = array_filter($todos, function($todo) use ($todo_id) {
        return $todo['id'] != $todo_id;
    });
    stm_save_todos(array_values($new_todos));
    wp_send_json_success($new_todos);
}
add_action('wp_ajax_stm_delete_todo', 'stm_delete_todo');

// Day 14: AJAX - অর্ডার সেভ
function stm_update_todo_order() {
    check_ajax_referer('stm_nonce', 'nonce');
    $order = array_map('intval', $_POST['order']);
    $todos = stm_get_todos();
    $ordered_todos = [];
    foreach ($order as $id) {
        foreach ($todos as $todo) {
            if ($todo['id'] == $id) {
                $ordered_todos[] = $todo;
                break;
            }
        }
    }
    stm_save_todos($ordered_todos);
    wp_send_json_success();
}
add_action('wp_ajax_stm_update_todo_order', 'stm_update_todo_order');

// Day 15: AJAX - টাস্ক এডিট + due date
function stm_edit_todo() {
    check_ajax_referer('stm_nonce', 'nonce');
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

// Day 16: AJAX - Clear All
function stm_clear_all_todos() {
    check_ajax_referer('stm_nonce', 'nonce');
    stm_save_todos(array());
    wp_send_json_success();
}
add_action('wp_ajax_stm_clear_all_todos', 'stm_clear_all_todos');

// Day 18: Enqueue + Fixed Inline Edit + Priorities + Subtasks
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
            var pendingDeleteId = null;
            var currentFilter = "all";

            function loadTodos() {
                $.post(ajaxurl, {
                    action: "stm_load_todos",
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        renderTodos(response.data);
                        updateProgress(response.data);
                        initSortable();
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
                        cursor: "move"
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
                        var priorityClass = "priority-" + todo.priority;
                        textSpan.addClass(priorityClass);
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

                    // Edit form
                    var editForm = $("<div>").addClass("edit-form").hide();

                    var editInput = $("<input type=\'text\'>").val(todo.text);

                    var dateInput = $("<input type=\'text\'>").val(todo.due_date || "");

                    var saveBtn = $("<button>").addClass("save-btn").text("Save");

                    var cancelBtn = $("<button>").addClass("cancel-btn").text("Cancel");

                    editForm.append(editInput, dateInput, saveBtn, cancelBtn);

                    // Store old values for cancel
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
                if (el && typeof Sortable !== "undefined") {
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

            // Search
            $("#search-todo").on("input", function() {
                loadTodos();
            });

            // Filter
            $(document).on("click", ".filter-btn", function() {
                $(".filter-btn").removeClass("active").css("background", "#95a5a6");
                $(this).addClass("active").css("background", "#6c5ce7");
                currentFilter = this.id.replace("filter-", "");
                loadTodos();
            });

            // Clear All
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

            // Single delete
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

            // Add Task
            $("#add-todo").on("click", function() {
                var text = $("#new-todo").val().trim();
                var priority = $("#task-priority").val();
                var subtasks = $("#task-subtasks").val();
                var assignee = $("#task-assignee").val() || 0;

                if (!text) return;

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
                    }
                });
            });

            $("#new-todo").on("keypress", function(e) {
                if (e.which == 13) {
                    $("#add-todo").click();
                }
            });

            loadTodos();
        });
    ');
}
add_action('admin_enqueue_scripts', 'stm_enqueue_scripts');