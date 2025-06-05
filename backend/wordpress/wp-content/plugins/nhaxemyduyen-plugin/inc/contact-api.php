<?php 
function create_contact_post_type() {
    register_post_type('contact_message', array(
        'labels' => array(
            'name' => __('Contact Messages'),
            'singular_name' => __('Contact Message')
        ),
        'public' => false,
        'show_ui' => true,
        'supports' => array('title', 'editor'),
        'show_in_rest' => true,
    ));
}
add_action('init', 'create_contact_post_type');

// Tạo endpoint REST API tùy chỉnh
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/contact', array(
        'methods' => 'POST',
        'callback' => 'handle_contact_submission',
        'permission_callback' => '__return_true', // Cho phép tất cả người dùng gửi
    ));
});

// Xử lý gửi tin nhắn và lưu vào custom post type
function handle_contact_submission($request) {
    $params = $request->get_json_params();
    $name = sanitize_text_field($params['name']);
    $phone = sanitize_text_field($params['phone']);
    $email = sanitize_email($params['email']);
    $message = sanitize_textarea_field($params['message']);

    if (empty($name) || empty($phone) || empty($email) || empty($message)) {
        return new WP_Error('missing_data', 'Vui lòng điền đầy đủ thông tin.', array('status' => 400));
    }

    // Tạo bài viết mới trong custom post type
    $post_data = array(
        'post_title' => 'Tin nhắn từ ' . $name,
        'post_content' => "Họ tên: $name\nSố điện thoại: $phone\nEmail: $email\nNội dung: $message",
        'post_status' => 'publish',
        'post_type' => 'contact_message',
    );

    $post_id = wp_insert_post($post_data);

    if ($post_id && !is_wp_error($post_id)) {
        // Gửi email thông báo cho admin (tùy chọn)
        $to = get_option('admin_email');
        $subject = 'Tin nhắn mới từ ' . $name;
        $body = "Họ tên: $name\nSố điện thoại: $phone\nEmail: $email\nNội dung: $message";
        wp_mail($to, $subject, $body);

        return new WP_Rest_Response('Tin nhắn đã được gửi thành công!', 200);
    } else {
        return new WP_Error('save_error', 'Đã xảy ra lỗi khi lưu tin nhắn.', array('status' => 500));
    }
}

?>