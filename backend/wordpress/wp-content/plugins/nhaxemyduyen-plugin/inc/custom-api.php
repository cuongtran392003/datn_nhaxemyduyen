<?php
/**
 * Endpoint API tùy chỉnh cho xác thực
 *
 * Tệp này định nghĩa các endpoint REST API tùy chỉnh cho đăng ký người dùng, đặt lại mật khẩu và chức năng quên mật khẩu.
 * Nó là một phần của plugin nhaxemyduyen-plugin.
 *
 * @package nhaxemyduyen-plugin
 * @subpackage inc
 * @author Tên của bạn
 */

if (!defined('ABSPATH')) {
    exit; // Thoát nếu truy cập trực tiếp
}

// Hook vào khởi tạo REST API
add_action('rest_api_init', function () {
    // Bật CORS
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function ($value) {
        header('Access-Control-Allow-Origin: http://localhost:3000'); // URL frontend React
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        header('Access-Control-Allow-Credentials: true');
        return $value;
    });

    // Endpoint đăng ký người dùng
    register_rest_route('custom/v1', '/register', array(
        'methods' => 'POST',
        'callback' => 'custom_register_user',
        'permission_callback' => '__return_true',
    ));

    // Endpoint quên mật khẩu
    register_rest_route('custom/v1', '/lostpassword', array(
        'methods' => 'POST',
        'callback' => 'custom_lost_password',
        'permission_callback' => '__return_true',
    ));

    // Endpoint đặt lại mật khẩu
    register_rest_route('custom/v1', '/resetpassword', array(
        'methods' => 'POST',
        'callback' => 'custom_reset_password',
        'permission_callback' => '__return_true',
    ));

    // Endpoint cập nhật hồ sơ
    register_rest_route('custom/v1', '/update-profile', array(
        'methods' => 'POST',
        'callback' => 'custom_update_profile',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    // Endpoint tải ảnh đại diện
    register_rest_route('custom/v1', '/upload-avatar', array(
        'methods' => 'POST',
        'callback' => 'custom_upload_avatar',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    // Endpoint lấy thông tin người dùng
    register_rest_route('custom/v1', '/user/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'custom_get_user',
        'permission_callback' => function (WP_REST_Request $request) {
            $user_id = $request['id'];
            $current_user_id = get_current_user_id();

            // Ghi log để gỡ lỗi
            error_log("Kiểm tra quyền - user_id yêu cầu: $user_id, user_id hiện tại: $current_user_id");

            // Cho phép truy cập nếu người dùng đã đăng nhập và yêu cầu dữ liệu của chính họ, hoặc là admin
            if ($current_user_id && ($user_id == $current_user_id || current_user_can('edit_users'))) {
                return true;
            }

            // Dự phòng: Xác thực token JWT thủ công
            $headers = $request->get_headers();
            $auth_header = isset($headers['authorization']) ? $headers['authorization'] : '';
            if (is_array($auth_header)) {
                $auth_header = $auth_header[0];
            }

            if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
                $token = $matches[1];
                try {
                    $jwt = new \Firebase\JWT\JWT;
                    $decoded = $jwt->decode($token, JWT_AUTH_SECRET_KEY, array('HS256'));
                    $token_user_id = $decoded->data->user->id;

                    if ($token_user_id == $user_id) {
                        wp_set_current_user($token_user_id); // Thiết lập ngữ cảnh người dùng
                        return true;
                    }
                } catch (Exception $e) {
                    error_log('Lỗi xác thực JWT trong permission_callback: ' . $e->getMessage());
                }
            }

            error_log("Từ chối quyền cho user_id: $user_id");
            return new WP_Error('unauthorized', 'Bạn không có quyền xem thông tin người dùng này.', array('status' => 403));
        },
    ));

    // Endpoint xóa người dùng
    register_rest_route('custom/v1', '/delete-user', array(
        'methods' => 'POST',
        'callback' => 'nhaxemyduyen_delete_user_callback',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ));

     
    
    
});





/**
 * Lấy thông tin người dùng
 *
 * @param WP_REST_Request $request Đối tượng yêu cầu REST
 * @return WP_REST_Response|WP_Error
 */
function custom_get_user(WP_REST_Request $request) {
    $user_id = $request['id'];
    $current_user_id = get_current_user_id();

    error_log("custom_get_user - user_id: $user_id, current_user_id: $current_user_id");

    $user = get_user_by('id', $user_id);
    if (!$user) {
        return new WP_Error('invalid_user', 'Không tìm thấy tài khoản người dùng này.', 
        array('status' => 404));
    }

    $user_data = array(
        'id' => $user->ID,
        'first_name' => get_user_meta($user->ID, 'first_name', true) ?: '',
        'last_name' => get_user_meta($user->ID, 'last_name', true) ?: '',
        'email' => $user->user_email ?: '',
        'phone_number' => get_user_meta($user->ID, 'phone_number', true) ?: '',
        'avatar_url' => get_user_meta($user->ID, 'avatar_url', true) ?: '',
        'roles' => $user->roles,
    );

    return new WP_REST_Response($user_data, 200);
}

/**
 * Xử lý đăng ký người dùng
 *
 * @param WP_REST_Request $request Đối tượng yêu cầu REST
 * @return WP_REST_Response|WP_Error
 */
function custom_register_user(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $username = sanitize_text_field($params['username'] ?? '');
    $email = sanitize_email($params['email'] ?? '');
    $password = $params['password'] ?? '';
    $first_name = sanitize_text_field($params['first_name'] ?? '');
    $last_name = sanitize_text_field($params['last_name'] ?? '');
    $phone = sanitize_text_field($params['description'] ?? '');

    if (empty($username) || empty($email) || empty($password)) {
        return new WP_Error('missing_fields', 'Vui lòng điền đầy đủ thông tin.', 
        array('status' => 400));
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

    // Tạo access token
    $jwt = new \Firebase\JWT\JWT;
    $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : 'your-secret-key';
    $issued_at = time();
    $expires_at = $issued_at + (60 * 60); // 1 giờ

    $payload = array(
        'iss' => get_bloginfo('url'),
        'iat' => $issued_at,
        'exp' => $expires_at,
        'data' => array(
            'user' => array(
                'id' => $user_id,
                'email' => $email,
            ),
        ),
    );

    $access_token = $jwt->encode($payload, $secret_key, 'HS256');

    // Tạo refresh token
    global $wpdb;
    $table_name = $wpdb->prefix . 'jwt_tokens';
    $refresh_token = wp_generate_uuid4();
    $refresh_expiry = time() + (7 * 24 * 60 * 60); // 7 ngày

    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'refresh_token' => $refresh_token,
            'expiry' => $refresh_expiry,
        ),
        array('%d', '%s', '%d')
    );

    return new WP_REST_Response(array(
        'message' => 'Đăng ký thành công! Vui lòng đăng nhập.',
        'user_id' => $user_id,
        'access_token' => $access_token,
        'refresh_token' => $refresh_token,
        'expires_in' => $expires_at,
    ), 200);
}

/**
 * Xử lý yêu cầu quên mật khẩu
 *
 * @param WP_REST_Request $request Đối tượng yêu cầu REST
 * @return WP_REST_Response|WP_Error
 */
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

    // Tạo khóa đặt lại (chỉ gọi 1 lần)
    $reset_key = get_password_reset_key($user_data);
    error_log('DEBUG RESET: $reset_key tạo ra (gốc): ' . $reset_key);
    // Lấy user_activation_key thực tế trong DB
    global $wpdb;
    $user_row = $wpdb->get_row($wpdb->prepare("SELECT ID, user_email, user_activation_key FROM {$wpdb->users} WHERE user_email = %s", $user_login));
    if ($user_row) {
        error_log('DEBUG RESET: user_activation_key trong DB SAU get_password_reset_key: ' . $user_row->user_activation_key);
        error_log('DEBUG RESET: So sánh key (reset_key == user_activation_key): ' . ($reset_key === $user_row->user_activation_key ? 'TRUE' : 'FALSE'));
    }
    // Gửi đúng key trong DB qua email (bỏ qua get_password_reset_key trả về)
    $reset_key_to_send = $user_row ? $user_row->user_activation_key : $reset_key;
    $reset_key_encoded = rawurlencode($reset_key_to_send);
    error_log('DEBUG RESET: $reset_key_to_send (dùng để gửi email): ' . $reset_key_to_send);
    $reset_link = 'http://localhost:3000/reset-password?key=' . $reset_key_encoded . '&email=' . urlencode($user_login);
    error_log('DEBUG RESET: $reset_link gửi qua email: ' . $reset_link);

    if (is_wp_error($reset_key)) {
        error_log('Không thể tạo khóa đặt lại: ' . $reset_key->get_error_message());
        return new WP_Error('reset_failed', 'Không thể tạo liên kết đặt lại mật khẩu.', array('status' => 500));
    }

    // BỎ QUA custom meta password_reset_expiry, để core WP tự kiểm soát thời gian sống của key

    // Nội dung email HTML
    $subject = 'Yêu Cầu Đặt Lại Mật Khẩu - Nhà Xe Mỹ Duyên';
    $message = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
    $message .= '<h2>Yêu Cầu Đặt Lại Mật Khẩu</h2>';
    $message .= '<p>Xin chào ' . esc_html($user_data->first_name) . ',</p>';
    $message .= '<p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản Nhà Xe Mỹ Duyên. Vui lòng nhấp vào nút dưới đây để đặt lại mật khẩu:</p>';
    $message .= '<p><a href="' . esc_url($reset_link) . '" style="display: inline-block; padding: 10px 20px; background-color: #1a73e8; color: #fff; text-decoration: none; border-radius: 5px;">Đặt Lại Mật Khẩu</a></p>';
    $message .= '<p>Nếu nút không hoạt động, hãy sao chép và dán liên kết sau vào trình duyệt của bạn:</p>';
    $message .= '<p><a href="' . esc_url($reset_link) . '">' . esc_url($reset_link) . '</a></p>';
    $message .= '<p>Liên kết này sẽ hết hạn sau <b>24 giờ</b>. Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>';
    $message .= '<p>Trân trọng,<br>Nhà Xe Mỹ Duyên</p>';
    $message .= '</body></html>';

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

/**
 * Xử lý đặt lại mật khẩu
 *
 * @param WP_REST_Request $request Đối tượng yêu cầu REST
 * @return WP_REST_Response|WP_Error
 */
function custom_reset_password(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $user_login = sanitize_email($params['user_login'] ?? '');
    $reset_key = sanitize_text_field($params['reset_key'] ?? '');
    $new_password = $params['new_password'] ?? '';

    error_log('Yêu cầu đặt lại mật khẩu: ' . print_r($params, true));
    error_log('DEBUG RESET: user_login nhận được: ' . $user_login . ', reset_key nhận được: ' . $reset_key);

    // Lấy user từ DB để log key thực tế
    global $wpdb;
    $user_row = $wpdb->get_row($wpdb->prepare("SELECT ID, user_email, user_activation_key FROM {$wpdb->users} WHERE user_email = %s", $user_login));
    if ($user_row) {
        error_log('DEBUG RESET: user_activation_key trong DB: ' . $user_row->user_activation_key);
        error_log('DEBUG RESET: So sánh key (reset_key == user_activation_key): ' . ($reset_key === $user_row->user_activation_key ? 'TRUE' : 'FALSE'));
    } else {
        error_log('DEBUG RESET: Không tìm thấy user trong DB với email: ' . $user_login);
    }

    // BỎ QUA kiểm tra custom meta password_reset_expiry, chỉ kiểm tra các trường bắt buộc
    if (empty($user_login) || empty($reset_key) || empty($new_password)) {
        error_log('Thiếu trường: user_login=' . $user_login . ', reset_key=' . $reset_key . ', new_password=' . $new_password);
        return new WP_Error('missing_fields', 'Vui lòng điền đầy đủ thông tin.', array('status' => 400));
    }

    error_log('DEBUG RESET: Gọi check_password_reset_key với reset_key: ' . $reset_key . ', user_login: ' . $user_login);
    $user = check_password_reset_key($reset_key, $user_login);

    if (is_wp_error($user)) {
        error_log('DEBUG RESET: check_password_reset_key trả về lỗi: ' . $user->get_error_message());
        $msg = $user->get_error_message();
        if (strpos($msg, 'expired') !== false || strpos($msg, 'hết hạn') !== false) {
            return new WP_Error('invalid_key', 'Liên kết đặt lại đã hết hạn. Vui lòng gửi lại yêu cầu quên mật khẩu.', array('status' => 400));
        } elseif (strpos($msg, 'invalid') !== false || strpos($msg, 'không hợp lệ') !== false || $msg === 'Invalid key.') {
            return new WP_Error('invalid_key', 'Liên kết đặt lại không hợp lệ hoặc đã được sử dụng. Vui lòng gửi lại yêu cầu quên mật khẩu.', array('status' => 400));
        } elseif (strpos($msg, 'user') !== false) {
            return new WP_Error('invalid_user', 'Tài khoản không tồn tại hoặc đã bị xóa.', array('status' => 400));
        } else {
            return new WP_Error('invalid_key', $msg, array('status' => 400));
        }
    }

    // Đặt lại mật khẩu
    wp_set_password($new_password, $user->ID);

    return new WP_REST_Response(array(
        'message' => 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập.',
    ), 200);
}

/**
 * Xử lý cập nhật hồ sơ
 *
 * @param WP_REST_Request $request Đối tượng yêu cầu REST
 * @return WP_REST_Response|WP_Error
 */
function custom_update_profile(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $user_id = intval($params['user_id'] ?? 0);
    $first_name = sanitize_text_field($params['first_name'] ?? '');
    $last_name = sanitize_text_field($params['last_name'] ?? '');
    $email = sanitize_email($params['email'] ?? '');
    $phone_number = sanitize_text_field($params['phone_number'] ?? '');
    $password = $params['password'] ?? '';

    if (empty($user_id) || empty($first_name) || empty($last_name) || empty($email) || empty($phone_number)) {
        return new WP_Error('missing_fields', 'Vui lòng điền đầy đủ thông tin bắt buộc.', array('status' => 400));
    }

    $user = get_user_by('id', $user_id);
    if (!$user) {
        return new WP_Error('invalid_user', 'Người dùng không tồn tại.', array('status' => 400));
    }

    // Kiểm tra quyền
    if (!current_user_can('edit_user', $user_id) && get_current_user_id() !== $user_id) {
        return new WP_Error('unauthorized', 'Bạn không có quyền chỉnh sửa người dùng này.', array('status' => 403));
    }

    // Kiểm tra email
    if ($email !== $user->user_email && email_exists($email)) {
        return new WP_Error('email_exists', 'Email đã được sử dụng.', array('status' => 400));
    }

    // Cập nhật thông tin
    wp_update_user([
        'ID' => $user_id,
        'user_email' => $email,
    ]);

    update_user_meta($user_id, 'first_name', $first_name);
    update_user_meta($user_id, 'last_name', $last_name);
    update_user_meta($user_id, 'phone_number', $phone_number);

    if (!empty($password)) {
        wp_set_password($password, $user_id);
    }

    return new WP_REST_Response(array(
        'message' => 'Cập nhật hồ sơ thành công!',
    ), 200);
}

/**
 * Xử lý tải ảnh đại diện
 *
 * @param WP_REST_Request $request Đối tượng yêu cầu REST
 * @return WP_REST_Response|WP_Error
 */
function custom_upload_avatar(WP_REST_Request $request) {
    $user_id = intval($request->get_param('user_id') ?? 0);
    $file = $request->get_file_params()['avatar'] ?? null;

    if (empty($user_id) || empty($file)) {
        return new WP_Error('missing_fields', 'Vui lòng cung cấp user_id và file ảnh.', array('status' => 400));
    }

    $user = get_user_by('id', $user_id);
    if (!$user) {
        return new WP_Error('invalid_user', 'Người dùng không tồn tại.', array('status' => 400));
    }

    // Kiểm tra quyền
    if (!current_user_can('edit_user', $user_id) && get_current_user_id() !== $user_id) {
        return new WP_Error('unauthorized', 'Bạn không có quyền chỉnh sửa người dùng này.', array('status' => 403));
    }

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $attachment_id = media_handle_upload('avatar', 0);
    if (is_wp_error($attachment_id)) {
        return new WP_Error('upload_failed', $attachment_id->get_error_message(), array('status' => 400));
    }

    $avatar_url = wp_get_attachment_url($attachment_id);
    update_user_meta($user_id, 'avatar_url', $avatar_url);

    return new WP_REST_Response(array(
        'message' => 'Tải ảnh đại diện thành công!',
        'avatar_url' => $avatar_url,
    ), 200);
}

/**
 * Xử lý xóa người dùng
 *
 * @param WP_REST_Request $request Đối tượng yêu cầu REST
 * @return WP_REST_Response|WP_Error
 */
function nhaxemyduyen_delete_user_callback(WP_REST_Request $request) {
    $user_id = intval($request->get_param('user_id'));
    $nonce = $request->get_param('nonce');

    // Ghi log để gỡ lỗi
    error_log('Yêu cầu xóa người dùng - user_id: ' . $user_id . ', nonce: ' . $nonce);

    // Kiểm tra user_id hợp lệ
    if ($user_id <= 0) {
        error_log('Lỗi: user_id không hợp lệ - user_id: ' . $user_id);
        return new WP_Error('invalid_user_id', 'ID người dùng không hợp lệ.', array('status' => 400));
    }

    // Kiểm tra người dùng tồn tại
    $user = get_user_by('id', $user_id);
    if (!$user) {
        error_log('Lỗi: Người dùng không tồn tại - user_id: ' . $user_id);
        return new WP_Error('invalid_user', 'Người dùng không tồn tại.', array('status' => 404));
    }

    // Kiểm tra tính hợp lệ của nonce
    if (!wp_verify_nonce($nonce, 'nhaxemyduyen_delete_user')) {
        error_log('Lỗi: Nonce không hợp lệ - nonce: ' . $nonce);
        return new WP_Error('invalid_nonce', 'Mã bảo mật không hợp lệ.', array('status' => 403));
    }

    // Kiểm tra xem người dùng có đang cố xóa chính mình
    if ($user_id === get_current_user_id()) {
        error_log('Lỗi: Cố gắng xóa tài khoản của chính mình - user_id: ' . $user_id);
        return new WP_Error('cannot_delete_self', 'Không thể xóa tài khoản của chính bạn.', array('status' => 400));
    }

    // Nạp tệp user.php để đảm bảo wp_delete_user khả dụng
    require_once(ABSPATH . 'wp-admin/includes/user.php');

    // Thực hiện xóa người dùng
    try {
        $result = wp_delete_user($user_id, 1); // Gán lại nội dung cho admin (ID=1)
        if ($result) {
            error_log('Xóa người dùng thành công - user_id: ' . $user_id);
            return new WP_REST_Response(array('message' => 'Xóa người dùng thành công.'), 200);
        } else {
            error_log('Xóa người dùng thất bại - user_id: ' . $user_id);
            return new WP_Error('delete_failed', 'Xóa người dùng thất bại.', array('status' => 500));
        }
    } catch (Exception $e) {
        error_log('Lỗi ngoại lệ khi xóa người dùng - user_id: ' . $user_id . ', lỗi: ' . $e->getMessage());
        return new WP_Error('delete_exception', 'Lỗi khi xóa người dùng: ' . $e->getMessage(), array('status' => 500));
    }
}