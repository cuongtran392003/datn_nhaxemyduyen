<?php
/**
 * Plugin Name: API REST Tùy Chỉnh cho Xác Thực
 * Description: Các endpoint API REST tùy chỉnh cho đăng ký người dùng, quên mật khẩu và đặt lại mật khẩu.
 * Version: 1.0
 * Author: Tên của bạn
 */

add_action('rest_api_init', function () {
    // Kích hoạt CORS
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function ($value) {
        header('Access-Control-Allow-Origin: http://localhost:3000'); // URL giao diện React
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        header('Access-Control-Allow-Credentials: true');
        return $value;
    });

    // Endpoint Đăng Ký Người Dùng
    register_rest_route('custom/v1', '/register', array(
        'methods' => 'POST',
        'callback' => 'custom_register_user',
        'permission_callback' => '__return_true',
    ));

    // Endpoint Quên Mật Khẩu
    register_rest_route('custom/v1', '/lostpassword', array(
        'methods' => 'POST',
        'callback' => 'custom_lost_password',
        'permission_callback' => '__return_true',
    ));

    // Endpoint Đặt Lại Mật Khẩu
    register_rest_route('custom/v1', '/resetpassword', array(
        'methods' => 'POST',
        'callback' => 'custom_reset_password',
        'permission_callback' => '__return_true',
    ));


});

function custom_register_user(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $username = sanitize_text_field($params['username'] ?? '');
    $email = sanitize_email($params['email'] ?? '');
    $password = $params['password'] ?? '';
    $first_name = sanitize_text_field($params['first_name'] ?? '');
    $last_name = sanitize_text_field($params['last_name'] ?? '');
    $phone = sanitize_text_field($params['description'] ?? '');

    if (empty($username) || empty($email) || empty($password)) {
        return new WP_Error('missing_fields', 'Vui lòng điền đầy đủ thông tin.', array('status' => 400));
    }

    if (email_exists($email) || username_exists($username)) {
        return new WP_Error('user_exists', 'Email hoặc tên người dùng đã tồn tại.', array('status' => 400));
    }

    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        return new WP_Error('registration_failed', $user_id->get_error_message(), array('status' => 400));
    }

    update_user_meta($user_id, 'first_name', $first_name);
    update_user_meta($user_id, 'last_name', $last_name);
    update_user_meta($user_id, 'phone_number', $phone);

    return new WP_REST_Response(array(
        'message' => 'Đăng ký thành công! Vui lòng đăng nhập.',
        'user_id' => $user_id,
    ), 200);
}

function custom_lost_password(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $user_login = sanitize_email($params['user_login'] ?? '');

    if (empty($user_login)) {
        return new WP_Error('missing_email', 'Vui lòng nhập địa chỉ email.', array('status' => 400));
    }

    $user_data = get_user_by('email', $user_login);

    if (!$user_data) {
        return new WP_Error('invalid_email', 'Email không tồn tại.', array('status' => 400));
    }

    // Tạo khóa đặt lại
    $reset_key = get_password_reset_key($user_data);

    if (is_wp_error($reset_key)) {
        error_log('Không thể tạo khóa đặt lại: ' . $reset_key->get_error_message());
        return new WP_Error('reset_failed', 'Không thể tạo liên kết đặt lại mật khẩu.', array('status' => 500));
    }

    // Chỉ lưu thời gian hết hạn (WordPress đã tự động lưu reset_key dưới dạng băm)
    update_user_meta($user_data->ID, 'password_reset_expiry', time() + 3600);

    // Tạo liên kết đặt lại
    $reset_link = add_query_arg(
        array(
            'key' => $reset_key,
            'email' => urlencode($user_login),
        ),
        'http://localhost:3000/reset-password'
    );

    // Nội dung email HTML
    $subject = 'Yêu Cầu Đặt Lại Mật Khẩu - Nhà Xe Mỹ Duyên';
    $message = '<html>';
    $message .= '<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
    $message .= '<h2>Yêu Cầu Đặt Lại Mật Khẩu</h2>';
    $message .= '<p>Xin chào ' . esc_html($user_data->first_name) . ',</p>';
    $message .= '<p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản Nhà Xe Mỹ Duyên. Vui lòng nhấp vào nút dưới đây để đặt lại mật khẩu:</p>';
    $message .= '<p><a href="' . esc_url($reset_link) . '" style="display: inline-block; padding: 10px 20px; background-color: #1a73e8; color: #fff; text-decoration: none; border-radius: 5px;">Đặt Lại Mật Khẩu</a></p>';
    $message .= '<p>Nếu nút không hoạt động, hãy sao chép và dán liên kết sau vào trình duyệt của bạn:</p>';
    $message .= '<p><a href="' . esc_url($reset_link) . '">' . esc_url($reset_link) . '</a></p>';
    $message .= '<p>Liên kết này sẽ hết hạn sau 1 giờ. Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>';
    $message .= '<p>Trân trọng,<br>Nhà Xe Mỹ Duyên</p>';
    $message .= '</body>';
    $message .= '</html>';

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: Nhà Xe Mỹ Duyên <cuongtran2746@gmail.com>'
    );

    error_log('Đang cố gắng gửi email đặt lại mật khẩu tới: ' . $user_login);

    $sent = wp_mail($user_login, $subject, $message, $headers);

    if (!$sent) {
        error_log('Không thể gửi email đặt lại mật khẩu tới: ' . $user_login);
        return new WP_Error('email_failed', 'Không thể gửi email. Vui lòng thử lại sau.', array('status' => 500));
    }

    error_log('Email đặt lại mật khẩu đã được gửi thành công tới: ' . $user_login);

    return new WP_REST_Response(array(
        'message' => 'Liên kết đặt lại mật khẩu đã được gửi đến email của bạn.',
    ), 200);
}

function custom_reset_password(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $user_login = sanitize_email($params['user_login'] ?? '');
    $reset_key = sanitize_text_field($params['reset_key'] ?? '');
    $new_password = $params['new_password'] ?? '';

    error_log('Yêu cầu đặt lại mật khẩu: ' . print_r($params, true));

    if (empty($user_login) || empty($reset_key) || empty($new_password)) {
        error_log('Thiếu trường: user_login=' . $user_login . ', reset_key=' . $reset_key . ', new_password=' . $new_password);
        return new WP_Error('missing_fields', 'Vui lòng điền đầy đủ thông tin.', array('status' => 400));
    }

    $user = check_password_reset_key($reset_key, $user_login);

    if (is_wp_error($user)) {
        error_log('Khóa đặt lại không hợp lệ cho user_login: ' . $user_login . ', reset_key: ' . $reset_key);
        error_log('Chi tiết lỗi: ' . $user->get_error_message());
        return new WP_Error('invalid_key', 'Liên kết đặt lại không hợp lệ hoặc đã hết hạn.', array('status' => 400));
    }

    $expiry = get_user_meta($user->ID, 'password_reset_expiry', true);
    error_log('Thời gian hết hạn: ' . $expiry . ', Thời gian hiện tại: ' . time());
    if (!$expiry || time() > $expiry) {
        error_log('Khóa đặt lại đã hết hạn cho user_id: ' . $user->ID);
        return new WP_Error('expired_key', 'Liên kết đặt lại đã hết hạn.', array('status' => 400));
    }

    wp_set_password($new_password, $user->ID);
    delete_user_meta($user->ID, 'password_reset_expiry');

    return new WP_REST_Response(array(
        'message' => 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập.',
    ), 200);
}

