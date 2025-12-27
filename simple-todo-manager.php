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

// Day 11: অ্যাডমিন মেনু + টুডু পেজ
function stm_admin_menu() {
    add_menu_page(
        'Todo Manager',
        'My Todos',
        'manage_options',
        'simple-todo-manager',
        'stm_todo_page',
        'dashicons-list-view',
        80
    );
}
add_action('admin_menu', 'stm_admin_menu');

// Day 11: টুডু পেজ
function stm_todo_page() {
    ?>
    <div class="wrap">
        <h1>📋 My Todo List</h1>
        <div id="todo-app" style="max-width:800px; margin:0 auto;">
            <div style="margin-bottom:30px;">
                <input type="text" id="new-todo" placeholder="Add new task..." style="width:70%; padding:12px; font-size:16px;" />
                <button id="add-todo" style="padding:12px 20px; background:#2271b1; color:white; border:none; font-size:16px;">Add Task</button>
            </div>
            <ul id="todo-list" style="list-style:none; padding:0;"></ul>
            <div id="progress" style="margin-top:30px; text-align:center;"></div>
        </div>
    </div>
    <?php
}