<?php
/**
 * Plugin Name: Simple Todo Manager
 * Plugin URI: https://github.com/shawon25800/simple-todo-manager
 * Description: A clean and powerful personal todo list plugin with AJAX and drag & drop. Built with Grok AI 🚀
 * Version: 1.0
 * Author: Shawon
 * Author URI: https://github.com/shawon25800
 * License: GPL2
 * Text Domain: simple-todo-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

// Day 11: অ্যাডমিন মেনু – আইকন ছাড়া
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

// Day 13: টুডু পেজ – glassmorphism style
function stm_todo_page() {
    ?>
    <div class="wrap" style="background:#fff; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;">
        <div style="width:100%; max-width:700px; background:rgba(255,255,255,0.95); border-radius:25px; padding:50px 40px; box-shadow:0 20px 50px rgba(0,0,0,0.1); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px);">
            <h1 style="text-align:center; color:#333; font-size:36px; margin-bottom:50px;">My Todo List</h1>

            <div id="todo-app">
                <div style="margin-bottom:40px; text-align:center;">
                    <input type="text" id="new-todo" placeholder="Add new task..." style="width:70%; padding:18px 25px; font-size:18px; border:none; border-radius:15px; background:#f1f3f4; color:#333; outline:none; box-shadow:0 5px 15px rgba(0,0,0,0.1);" />
                    <button id="add-todo" style="padding:18px 30px; margin-left:10px; background:#6c5ce7; color:white; border:none; border-radius:15px; font-size:18px; cursor:pointer; box-shadow:0 5px 15px rgba(108,92,231,0.4);">Add Task</button>
                </div>

                <ul id="todo-list" style="list-style:none; padding:0;"></ul>

                <div id="progress" style="margin-top:50px; text-align:center; color:#555; font-size:20px;"></div>
            </div>
        </div>
    </div>

    <!-- Custom Confirm Dialog -->
    <div id="custom-confirm" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:40px; border-radius:20px; box-shadow:0 15px 40px rgba(0,0,0,0.3); text-align:center; max-width:400px; width:90%;">
            <p style="font-size:20px; margin-bottom:40px; color:#333;">Are you sure you want to delete this task?</p>
            <button id="confirm-yes" style="padding:12px 30px; background:#e74c3c; color:white; border:none; border-radius:12px; margin:0 15px; cursor:pointer; font-size:16px;">Yes, Delete</button>
            <button id="confirm-no" style="padding:12px 30px; background:#95a5a6; color:white; border:none; border-radius:12px; margin:0 15px; cursor:pointer; font-size:16px;">Cancel</button>
        </div>
    </div>

    <style>
        #todo-list li {
            cursor: move;
        }
        .sortable-ghost {
            opacity: 0.4;
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

// Day 12: AJAX - নতুন টাস্ক অ্যাড
function stm_add_todo() {
    check_ajax_referer('stm_nonce', 'nonce');

    $text = sanitize_text_field($_POST['text']);
    if (empty($text)) {
        wp_send_json_error('Task cannot be empty');
    }

    $todos = stm_get_todos();
    $todos[] = array(
        'id' => time(),
        'text' => $text,
        'completed' => false
    );

    stm_save_todos($todos);
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
    $new_todos = array();

    foreach ($todos as $todo) {
        if ($todo['id'] != $todo_id) {
            $new_todos[] = $todo;
        }
    }

    stm_save_todos($new_todos);
    wp_send_json_success($new_todos);
}
add_action('wp_ajax_stm_delete_todo', 'stm_delete_todo');

// Day 14: AJAX - অর্ডার সেভ
function stm_update_todo_order() {
    check_ajax_referer('stm_nonce', 'nonce');

    $order = array_map('intval', $_POST['order']);

    $todos = stm_get_todos();
    $ordered_todos = array();

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

// Day 14: Enqueue + Full JS with Drag & Drop + Fixed Confirm
function stm_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_simple-todo-manager') {
        return;
    }

    wp_enqueue_script('jquery');
    wp_enqueue_script('sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js', array('jquery'), '1.15.2', true);

    wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            var nonce = "' . wp_create_nonce('stm_nonce') . '";
            var pendingDeleteId = null;

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
                var list = $("#todo-list");
                list.empty();

                todos.forEach(function(todo) {
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
                        }, function(response) {
                            if (response.success) {
                                renderTodos(response.data);
                                updateProgress(response.data);
                            }
                        });
                    });

                    var text = $("<span>").text(todo.text).css({
                        "flex": "1",
                        "margin-left": "20px",
                        "font-size": "18px",
                        "font-weight": "500"
                    });

                    if (todo.completed) {
                        text.css("text-decoration", "line-through").css("opacity", "0.6");
                    }

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

                    li.append(checkbox, text, deleteBtn);
                    list.append(li);
                });
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

            // Confirm dialog buttons – fixed
            $(document).on("click", "#confirm-yes", function() {
                if (pendingDeleteId !== null) {
                    $.post(ajaxurl, {
                        action: "stm_delete_todo",
                        todo_id: pendingDeleteId,
                        nonce: nonce
                    }, function(response) {
                        if (response.success) {
                            renderTodos(response.data);
                            updateProgress(response.data);
                        }
                    });
                }
                $("#custom-confirm").fadeOut(200);
                pendingDeleteId = null;
            });

            $(document).on("click", "#confirm-no", function() {
                $("#custom-confirm").fadeOut(200);
                pendingDeleteId = null;
            });

            loadTodos();

            $("#add-todo").on("click", function() {
                var text = $("#new-todo").val().trim();
                if (!text) return;

                $.post(ajaxurl, {
                    action: "stm_add_todo",
                    text: text,
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        $("#new-todo").val("");
                        renderTodos(response.data);
                        updateProgress(response.data);
                    }
                });
            });

            $("#new-todo").on("keypress", function(e) {
                if (e.which == 13) {
                    $("#add-todo").click();
                }
            });
        });
    ');
}
add_action('admin_enqueue_scripts', 'stm_enqueue_scripts');