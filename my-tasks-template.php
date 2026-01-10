<?php
/*
Template Name: My Tasks Dashboard
*/

get_header();

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$current_user_id = get_current_user_id();
$all_tasks = stm_get_todos();

// Strict filter – non-admin only assigned tasks
$my_tasks = array_filter($all_tasks, function($task) use ($current_user_id) {
    return !empty($task['assigned_to']) && $task['assigned_to'] == $current_user_id;
});

usort($my_tasks, function($a, $b) {
    return ($b['id'] ?? 0) - ($a['id'] ?? 0);
});
?>
<div class="wrap" style="background:#fff; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;">
    <div style="width:100%; max-width:700px; background:rgba(255,255,255,0.95); border-radius:25px; padding:50px 40px; box-shadow:0 20px 50px rgba(0,0,0,0.1); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px);">
        <h1 style="text-align:center; color:#333; font-size:36px; margin-bottom:50px;">My Assigned Tasks</h1>

        <div style="margin-bottom:30px; text-align:center;">
            <input type="text" id="search-todo" placeholder="Search my tasks..." style="width:60%; padding:15px; font-size:16px; border:1px solid #ddd; border-radius:15px; outline:none;" />
        </div>

        <div style="margin-bottom:40px; text-align:center;">
            <button class="tab-btn active" data-tab="active" style="padding:12px 30px; background:#6c5ce7; color:white; border:none; border-radius:15px; margin:0 10px; cursor:pointer; font-size:16px;">Active Tasks</button>
            <button class="tab-btn" data-tab="completed" style="padding:12px 30px; background:#95a5a6; color:white; border:none; border-radius:15px; margin:0 10px; cursor:pointer; font-size:16px;">Completed Tasks</button>
        </div>

        <div id="active-tasks">
            <?php $active = array_filter($my_tasks, fn($t) => !$t['completed']); ?>
            <?php if (empty($active)): ?>
                <p style="text-align:center; color:#888; font-size:18px; padding:60px;">No active tasks assigned to you.</p>
            <?php else: ?>
                <ul style="list-style:none; padding:0;">
                    <?php foreach ($active as $task): ?>
                        <li style="padding:30px; background:rgba(255,255,255,0.9); margin:20px 0; border-radius:25px; box-shadow:0 10px 30px rgba(0,0,0,0.08); display:flex; align-items:flex-start;">
                            <button class="complete-btn" data-id="<?php echo esc_attr($task['id']); ?>" style="padding:12px 24px; background:#27ae60; color:white; border:none; border-radius:15px; cursor:pointer; font-size:16px; box-shadow:0 5px 15px rgba(39,174,96,0.4);">Mark Complete</button>
                            <div style="flex:1; margin-left:25px;">
                                <div style="font-size:22px; font-weight:600; color:#2c3e50;">
                                    <?php echo esc_html($task['text']); ?>
                                    <?php if (!empty($task['priority'])): ?>
                                        <span style="margin-left:12px; padding:6px 14px; border-radius:12px; font-size:14px; font-weight:bold; color:white; background:<?php echo $task['priority'] === 'high' ? '#e74c3c' : ($task['priority'] === 'medium' ? '#f39c12' : '#27ae60'); ?>;">
                                            <?php echo strtoupper($task['priority']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($task['due_date'])): ?>
                                    <div style="margin-top:10px; color:#e74c3c; font-weight:bold; font-size:16px;">
                                        Due: <?php echo esc_html($task['due_date']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($task['subtasks'])): ?>
                                    <div style="margin-top:15px;">
                                        <strong style="color:#34495e; font-size:16px;">Subtasks:</strong>
                                        <ul style="margin:8px 0 0 25px; padding:0; color:#555;">
                                            <?php foreach ($task['subtasks'] as $sub): ?>
                                                <li style="margin:6px 0; font-size:15px;"><?php echo esc_html($sub); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div id="completed-tasks" style="display:none;">
            <?php $completed = array_filter($my_tasks, fn($t) => $t['completed']); ?>
            <?php if (empty($completed)): ?>
                <p style="text-align:center; color:#888; font-size:18px; padding:60px;">No completed tasks yet.</p>
            <?php else: ?>
                <ul style="list-style:none; padding:0;">
                    <?php foreach ($completed as $task): ?>
                        <li style="padding:30px; background:rgba(240,240,240,0.8); margin:20px 0; border-radius:25px; box-shadow:0 10px 30px rgba(0,0,0,0.08);">
                            <div style="font-size:22px; color:#95a5a6; text-decoration:line-through;">
                                <?php echo esc_html($task['text']); ?>
                            </div>
                            <?php if (!empty($task['due_date'])): ?>
                                <div style="margin-top:10px; color:#7f8c8d;">Completed (Due was: <?php echo esc_html($task['due_date']); ?>)</div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $("#search-todo").on("input", function() {
        var val = $(this).val().toLowerCase();
        $("li").each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(val));
        });
    });

    $(".tab-btn").on("click", function() {
        $(".tab-btn").removeClass("active").css("background", "#95a5a6");
        $(this).addClass("active").css("background", "#6c5ce7");
        if ($(this).data("tab") === "active") {
            $("#active-tasks").show();
            $("#completed-tasks").hide();
        } else {
            $("#active-tasks").hide();
            $("#completed-tasks").show();
        }
    });

    $(".complete-btn").on("click", function() {
        var task_id = $(this).data("id");
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'stm_toggle_complete',
            todo_id: task_id,
            nonce: '<?php echo wp_create_nonce('stm_nonce'); ?>'
        }, function() {
            location.reload();
        });
    });
});
</script>

<?php get_footer(); ?>