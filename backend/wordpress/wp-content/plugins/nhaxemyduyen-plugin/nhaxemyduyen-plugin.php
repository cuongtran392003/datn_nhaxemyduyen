<?php
/*
Plugin Name: Nhà Xe Mỹ Duyên
Description: Plugin quản lý địa điểm, tuyến đường, chuyến xe, vé xe, người dùng và thống kê.
Version: 2.0
Author: Your Name
*/

// Đăng ký menu Admin
add_action('admin_menu', 'nhaxemyduyen_admin_menu');

function nhaxemyduyen_admin_menu() {
    add_menu_page(
        'Quản lý Nhà Xe Mỹ Duyên',
        'Nhà Xe Mỹ Duyên',
        'manage_options',
        'nhaxemyduyen',
        'nhaxemyduyen_dashboard',
        'dashicons-admin-generic',
        6
    );

    add_submenu_page(
        'nhaxemyduyen',
        'Quản lý Địa Điểm',
        'Quản lý Địa Điểm',
        'manage_options',
        'nhaxemyduyen-locations',
        'nhaxemyduyen_manage_locations'
    );

    add_submenu_page(
        'nhaxemyduyen',
        'Quản lý Tuyến Đường',
        'Quản lý Tuyến Đường',
        'manage_options',
        'nhaxemyduyen-routes',
        'nhaxemyduyen_manage_routes'
    );

    add_submenu_page(
        'nhaxemyduyen',
        'Quản lý Chuyến Xe',
        'Quản lý Chuyến Xe',
        'manage_options',
        'nhaxemyduyen-trips',
        'nhaxemyduyen_manage_trips'
    );

    add_submenu_page(
        'nhaxemyduyen',
        'Quản lý Vé Xe',
        'Quản lý Vé Xe',
        'manage_options',
        'nhaxemyduyen-tickets',
        'nhaxemyduyen_manage_tickets'
    );

    add_submenu_page(
        'nhaxemyduyen',
        'Quản lý Người Dùng',
        'Quản lý Người Dùng',
        'manage_options',
        'nhaxemyduyen-users',
        'nhaxemyduyen_manage_users'
    );

    add_submenu_page(
        'nhaxemyduyen',
        'Thống kê',
        'Thống kê',
        'manage_options',
        'nhaxemyduyen-stats',
        'nhaxemyduyen_stats'
    );

    add_submenu_page(
        'nhaxemyduyen',
        'Quản Lý Tài Xế',
        'Quản Lý Tài Xế',
        'manage_options',
        'nhaxemyduyen-drivers',
        'nhaxemyduyen_manage_drivers'
    );

    add_submenu_page(
        'nhaxemyduyen',
        'Quản Lý Xe',
        'Quản Lý Xe',
        'manage_options',
        'nhaxemyduyen-buses',
        'nhaxemyduyen_manage_vehicles'
    );
}

// Đảm bảo charset UTF-8
add_action('admin_head', 'nhaxemyduyen_force_utf8');
function nhaxemyduyen_force_utf8() {
    echo '<meta charset="UTF-8">';
}

// Trang Dashboard chính
function nhaxemyduyen_dashboard() {
    ?>
    <div class="wrap nhaxe-wrap">
        <h1 class="nhaxe-title">Quản lý Nhà Xe Mỹ Duyên</h1>
        <div class="nhaxe-card">
            <p>Chào mừng đến với hệ thống quản lý Nhà Xe Mỹ Duyên. Vui lòng chọn một mục từ menu bên trái để bắt đầu.</p>
        </div>
    </div>
    <?php
}

// Hàm chuyển đổi số phút thành định dạng "giờ:phút"
function format_duration_to_hhmm($minutes) {
    $hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    return sprintf("%d:%02d", $hours, $remaining_minutes);
}

// Hàm chuyển định dạng "giờ:phút" thành số phút
function parse_hhmm_to_minutes($hhmm) {
    list($hours, $minutes) = explode(':', $hhmm);
    return (int)$hours * 60 + (int)$minutes;
}

// Trang quản lý địa điểm
function nhaxemyduyen_manage_locations() {
    global $wpdb;
    $table_locations = $wpdb->prefix . 'locations';
    $table_trips = $wpdb->prefix . 'trips';

    // Kiểm tra quyền truy cập
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền truy cập trang này.');
    }

    // Xử lý bộ lọc
    $filter_name = isset($_POST['filter_name']) ? sanitize_text_field($_POST['filter_name']) : '';
    $filter_date = isset($_POST['filter_date']) ? sanitize_text_field($_POST['filter_date']) : '';

    $where_conditions = [];
    if (!empty($filter_name)) {
        $where_conditions[] = $wpdb->prepare("name LIKE %s", '%' . $filter_name . '%');
    }
    if (!empty($filter_date)) {
        $where_conditions[] = $wpdb->prepare("DATE(created_at) = %s", $filter_date);
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = " WHERE " . implode(' AND ', $where_conditions);
    }

    // Xử lý thêm/sửa địa điểm
    if (isset($_POST['nhaxemyduyen_location_action']) && isset($_POST['nhaxemyduyen_location_nonce']) && wp_verify_nonce($_POST['nhaxemyduyen_location_nonce'], 'nhaxemyduyen_location_action')) {
        $action = $_POST['nhaxemyduyen_location_action'];
        $location_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        if ($action === 'add') {
            $result = $wpdb->insert($table_locations, $location_data);
            if ($result === false) {
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: Không thể thêm địa điểm. ' . esc_html($wpdb->last_error) . '</p></div>';
            } else {
                wp_redirect(admin_url('admin.php?page=nhaxemyduyen-locations&message=add_success'));
                exit;
            }
        } elseif ($action === 'edit') {
            $location_id = intval($_POST['location_id']);
            unset($location_data['created_at']);
            $result = $wpdb->update($table_locations, $location_data, array('location_id' => $location_id));
            if ($result === false) {
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: Không thể cập nhật địa điểm. ' . esc_html($wpdb->last_error) . '</p></div>';
            } else {
                wp_redirect(admin_url('admin.php?page=nhaxemyduyen-locations&message=edit_success'));
                exit;
            }
        }
    }

    // Xử lý xóa địa điểm
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['location_id']) && isset($_GET['nonce']) && wp_verify_nonce($_GET['nonce'], 'nhaxemyduyen_delete_location')) {
        $location_id = intval($_GET['location_id']);
        $trip_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_trips WHERE from_location_id = %d OR to_location_id = %d", $location_id, $location_id));
        if ($trip_count > 0) {
            echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Không thể xóa địa điểm vì đang được sử dụng trong các chuyến xe!</p></div>';
        } else {
            $result = $wpdb->delete($table_locations, array('location_id' => $location_id));
            if ($result === false) {
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: Không thể xóa địa điểm. ' . esc_html($wpdb->last_error) . '</p></div>';
            } else {
                wp_redirect(admin_url('admin.php?page=nhaxemyduyen-locations&message=delete_success'));
                exit;
            }
        }
    }

    // Lấy danh sách địa điểm với bộ lọc
    $locations = $wpdb->get_results("SELECT * FROM $table_locations $where_clause ORDER BY created_at DESC", ARRAY_A);

    // Xử lý chỉnh sửa địa điểm
    $location_to_edit = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['location_id'])) {
        $location_id = intval($_GET['location_id']);
        $location_to_edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_locations WHERE location_id = %d", $location_id), ARRAY_A);
    }

    // Hiển thị thông báo
    $message = '';
    if (isset($_GET['message'])) {
        if ($_GET['message'] === 'add_success') {
            $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>Thêm địa điểm thành công!</p></div>';
        } elseif ($_GET['message'] === 'edit_success') {
            $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>Cập nhật địa điểm thành công!</p></div>';
        } elseif ($_GET['message'] === 'delete_success') {
            $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>Xóa địa điểm thành công!</p></div>';
        }
    }

    // Đăng ký jQuery
    wp_enqueue_script('jquery');
    wp_enqueue_style('tailwind-css', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    ?>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Quản lý Địa Điểm</h1>
        <?php echo $message; ?>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Danh sách Địa Điểm</h2>

            <!-- Filter Form and Add Button -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <!-- Filter Form -->
                <form method="post" action="" class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <input type="text" name="filter_name" id="filter_name" value="<?php echo esc_attr($filter_name); ?>" placeholder="Tên địa điểm" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <input type="date" name="filter_date" id="filter_date" value="<?php echo esc_attr($filter_date); ?>" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Lọc</button>
                </form>

                <!-- Add Location Button -->
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition nhaxe-toggle-form mt-4 sm:mt-0">Thêm Địa Điểm</button>
            </div>

            <!-- Add/Edit Location Form -->
            <div class="nhaxe-add-form hidden bg-gray-50 p-6 rounded-lg mb-6">
                <form method="post" action="">
                    <?php wp_nonce_field('nhaxemyduyen_location_action', 'nhaxemyduyen_location_nonce'); ?>
                    <input type="hidden" name="nhaxemyduyen_location_action" value="<?php echo $location_to_edit ? 'edit' : 'add'; ?>">
                    <input type="hidden" name="location_id" value="<?php echo $location_to_edit ? esc_attr($location_to_edit['location_id']) : ''; ?>">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Tên địa điểm</label>
                            <input type="text" name="name" id="name" value="<?php echo $location_to_edit ? esc_attr($location_to_edit['name']) : ''; ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="mt-6 flex space-x-4">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"><?php echo $location_to_edit ? 'Cập nhật Địa Điểm' : 'Thêm Địa Điểm'; ?></button>
                        <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition nhaxe-toggle-form">Hủy</button>
                    </div>
                </form>
            </div>

            <!-- Locations Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên địa điểm</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian tạo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian cập nhật</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($locations)) : ?>
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-sm text-gray-500 text-center">Không có địa điểm nào phù hợp.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($locations as $location) : ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($location['location_id']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($location['name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($location['created_at']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($location['updated_at']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <a href="<?php echo admin_url('admin.php?page=nhaxemyduyen-locations&action=edit&location_id=' . $location['location_id']); ?>" class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition mr-2">Sửa</a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=nhaxemyduyen-locations&action=delete&location_id=' . $location['location_id']), 'nhaxemyduyen_delete_location', 'nonce'); ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa?')" class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition">Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        /* Custom styles for specific tweaks */
        .nhaxe-add-form.hidden {
            display: none;
        }
        table th, table td {
            white-space: nowrap;
        }
        @media (max-width: 640px) {
            .sm\:flex-row {
                flex-direction: column;
            }
            .sm\:space-x-4 {
                space-x: 0;
                space-y: 4px;
            }
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            $('.nhaxe-toggle-form').click(function() {
                $('.nhaxe-add-form').toggleClass('hidden');
            });
        });
    </script>
    <?php
}




// Trang quản lý tuyến đường
function nhaxemyduyen_manage_routes() {
    global $wpdb;
    $table_routes = $wpdb->prefix . 'routes';
    $table_locations = $wpdb->prefix . 'locations';
    $table_trips = $wpdb->prefix . 'trips';

    // Kiểm tra quyền truy cập
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền truy cập trang này.');
    }

    // Xử lý thông báo
    $message = '';
    if (isset($_GET['message'])) {
        if ($_GET['message'] === 'edit_success') {
            $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>Tuyến đường đã được cập nhật thành công!</p></div>';
        } elseif ($_GET['message'] === 'delete_success') {
            $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>Tuyến đường đã được xóa thành công!</p></div>';
        } elseif ($_GET['message'] === 'add_success') {
            $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>Tuyến đường đã được thêm thành công!</p></div>';
        }
    }

    // Xử lý bộ lọc
    $filter_from_location = isset($_POST['filter_from_location']) ? intval($_POST['filter_from_location']) : 0;
    $filter_to_location = isset($_POST['filter_to_location']) ? intval($_POST['filter_to_location']) : 0;
    $filter_price_min = isset($_POST['filter_price_min']) ? floatval($_POST['filter_price_min']) : '';
    $filter_price_max = isset($_POST['filter_price_max']) ? floatval($_POST['filter_price_max']) : '';

    $where_conditions = [];
    if ($filter_from_location > 0) {
        $where_conditions[] = $wpdb->prepare("r.from_location_id = %d", $filter_from_location);
    }
    if ($filter_to_location > 0) {
        $where_conditions[] = $wpdb->prepare("r.to_location_id = %d", $filter_to_location);
    }
    if (!empty($filter_price_min)) {
        $where_conditions[] = $wpdb->prepare("r.price >= %f", $filter_price_min);
    }
    if (!empty($filter_price_max)) {
        $where_conditions[] = $wpdb->prepare("r.price <= %f", $filter_price_max);
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = " WHERE " . implode(' AND ', $where_conditions);
    }

    // Xử lý thêm/sửa tuyến đường
    if (isset($_POST['nhaxemyduyen_route_action']) && isset($_POST['nhaxemyduyen_route_nonce']) && wp_verify_nonce($_POST['nhaxemyduyen_route_nonce'], 'nhaxemyduyen_route_action')) {
        $action = $_POST['nhaxemyduyen_route_action'];

        // Chuyển định dạng "giờ:phút" thành số phút
        $duration_input = sanitize_text_field($_POST['duration']);
        if (!preg_match('/^\d+:[0-5][0-9]$/', $duration_input)) {
            echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: Thời gian di chuyển phải có định dạng giờ:phút (VD: 2:30).</p></div>';
            return;
        }
        $duration_minutes = parse_hhmm_to_minutes($duration_input);
        if ($duration_minutes <= 0) {
            echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: Thời gian di chuyển phải lớn hơn 0.</p></div>';
            return;
        }

        // Xử lý upload ảnh
        $bus_image_url = '';
        if (!empty($_FILES['bus_image']['name'])) {
            $uploaded_file = wp_handle_upload($_FILES['bus_image'], array('test_form' => false));
            if (isset($uploaded_file['error'])) {
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: Không thể upload ảnh - ' . esc_html($uploaded_file['error']) . '</p></div>';
                return;
            }
            $bus_image_url = $uploaded_file['url'];
        } elseif ($action === 'edit' && empty($_FILES['bus_image']['name'])) {
            $route_id = intval($_POST['route_id']);
            $existing_route = $wpdb->get_row($wpdb->prepare("SELECT bus_image FROM $table_routes WHERE route_id = %d", $route_id), ARRAY_A);
            $bus_image_url = $existing_route['bus_image'];
        }

        $route_data = array(
            'from_location_id' => intval($_POST['from_location_id']),
            'to_location_id' => intval($_POST['to_location_id']),
            'price' => floatval($_POST['price']),
            'distance' => floatval($_POST['distance']),
            'duration' => $duration_minutes,
            'bus_image' => $bus_image_url,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        if ($action === 'add') {
            $result = $wpdb->insert($table_routes, $route_data);
            if ($result === false) {
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: Không thể thêm tuyến đường. ' . esc_html($wpdb->last_error) . '</p></div>';
            } else {
                wp_redirect(admin_url('admin.php?page=nhaxemyduyen-routes&message=add_success'));
                exit;
            }
        } elseif ($action === 'edit') {
            $route_id = intval($_POST['route_id']);
            unset($route_data['created_at']);
            $result = $wpdb->update($table_routes, $route_data, array('route_id' => $route_id));
            if ($result === false) {
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: Không thể cập nhật tuyến đường. ' . esc_html($wpdb->last_error) . '</p></div>';
            } else {
                wp_redirect(admin_url('admin.php?page=nhaxemyduyen-routes&message=edit_success'));
                exit;
            }
        }
    }

    // Xử lý xóa tuyến đường
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['route_id']) && isset($_GET['nonce']) && wp_verify_nonce($_GET['nonce'], 'nhaxemyduyen_delete_route')) {
        $route_id = intval($_GET['route_id']);
        $trip_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_trips WHERE route_id = %d", $route_id));
        if ($trip_count > 0) {
            echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Không thể xóa tuyến đường vì đang được sử dụng trong các chuyến xe!</p></div>';
        } else {
            $bus_image = $wpdb->get_var($wpdb->prepare("SELECT bus_image FROM $table_routes WHERE route_id = %d", $route_id));
            $result = $wpdb->delete($table_routes, array('route_id' => $route_id));
            if ($result === false) {
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: Không thể xóa tuyến đường. ' . esc_html($wpdb->last_error) . '</p></div>';
            } else {
                if (!empty($bus_image)) {
                    $upload_dir = wp_upload_dir();
                    $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $bus_image);
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                wp_redirect(admin_url('admin.php?page=nhaxemyduyen-routes&message=delete_success'));
                exit;
            }
        }
    }

    // Lấy danh sách tuyến đường với bộ lọc
    $routes = $wpdb->get_results("
        SELECT r.*, l1.name as from_location, l2.name as to_location
        FROM $table_routes r
        JOIN $table_locations l1 ON r.from_location_id = l1.location_id
        JOIN $table_locations l2 ON r.to_location_id = l2.location_id
        $where_clause
        ORDER BY r.created_at DESC
    ", ARRAY_A);

    // Lấy danh sách địa điểm
    $locations = $wpdb->get_results("SELECT * FROM $table_locations ORDER BY name", ARRAY_A);

    // Xử lý chỉnh sửa tuyến đường
    $route_to_edit = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['route_id'])) {
        $route_id = intval($_GET['route_id']);
        $route_to_edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_routes WHERE route_id = %d", $route_id), ARRAY_A);
        if (!$route_to_edit) {
            echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: Tuyến đường không tồn tại!</p></div>';
        }
    }

    // Đăng ký jQuery
    wp_enqueue_script('jquery');
    wp_enqueue_style('tailwind-css', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    ?>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Quản lý Tuyến Đường</h1>
        <?php echo $message; ?>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Danh sách Tuyến Đường</h2>

            <!-- Filter Form and Add Button -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <!-- Filter Form -->
                <form method="post" action="" class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <select name="filter_from_location" id="filter_from_location" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="0">-- Chọn điểm đi --</option>
                        <?php foreach ($locations as $location) : ?>
                            <option value="<?php echo esc_attr($location['location_id']); ?>" <?php selected($filter_from_location, $location['location_id']); ?>>
                                <?php echo esc_html($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="filter_to_location" id="filter_to_location" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="0">-- Chọn điểm đến --</option>
                        <?php foreach ($locations as $location) : ?>
                            <option value="<?php echo esc_attr($location['location_id']); ?>" <?php selected($filter_to_location, $location['location_id']); ?>>
                                <?php echo esc_html($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="filter_price_min" id="filter_price_min" value="<?php echo esc_attr($filter_price_min); ?>" placeholder="Giá tối thiểu" step="0.01" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <input type="number" name="filter_price_max" id="filter_price_max" value="<?php echo esc_attr($filter_price_max); ?>" placeholder="Giá tối đa" step="0.01" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Lọc</button>
                </form>

                <!-- Add Route Button -->
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition nhaxe-toggle-form mt-4 sm:mt-0" data-action="add">Thêm Tuyến Đường</button>
            </div>

            <!-- Add/Edit Route Form -->
            <div class="nhaxe-add-form hidden bg-gray-50 p-6 rounded-lg mb-6">
                <form method="post" action="" enctype="multipart/form-data">
                    <?php wp_nonce_field('nhaxemyduyen_route_action', 'nhaxemyduyen_route_nonce'); ?>
                    <input type="hidden" name="nhaxemyduyen_route_action" id="route_action" value="<?php echo $route_to_edit ? 'edit' : 'add'; ?>">
                    <input type="hidden" name="route_id" id="route_id" value="<?php echo $route_to_edit ? esc_attr($route_to_edit['route_id']) : ''; ?>">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="from_location_id" class="block text-sm font-medium text-gray-700">Điểm đi</label>
                            <select name="from_location_id" id="from_location_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Chọn điểm đi --</option>
                                <?php foreach ($locations as $location) : ?>
                                    <option value="<?php echo esc_attr($location['location_id']); ?>" <?php selected($route_to_edit && $route_to_edit['from_location_id'] == $location['location_id']); ?>>
                                        <?php echo esc_html($location['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="to_location_id" class="block text-sm font-medium text-gray-700">Điểm đến</label>
                            <select name="to_location_id" id="to_location_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Chọn điểm đến --</option>
                                <?php foreach ($locations as $location) : ?>
                                    <option value="<?php echo esc_attr($location['location_id']); ?>" <?php selected($route_to_edit && $route_to_edit['to_location_id'] == $location['location_id']); ?>>
                                        <?php echo esc_html($location['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700">Giá vé (VNĐ)</label>
                            <input type="number" name="price" id="price" step="0.01" value="<?php echo $route_to_edit ? esc_attr($route_to_edit['price']) : ''; ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="distance" class="block text-sm font-medium text-gray-700">Khoảng cách (km)</label>
                            <input type="number" name="distance" id="distance" step="0.01" value="<?php echo $route_to_edit ? esc_attr($route_to_edit['distance']) : ''; ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="duration" class="block text-sm font-medium text-gray-700">Thời gian di chuyển (giờ:phút)</label>
                            <input type="text" name="duration" id="duration" placeholder="Nhập thời gian, ví dụ: 2:30" pattern="\d+:[0-5][0-9]" value="<?php echo $route_to_edit ? esc_attr(format_duration_to_hhmm($route_to_edit['duration'])) : ''; ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="bus_image" class="block text-sm font-medium text-gray-700">Ảnh xe</label>
                            <img id="bus_image_preview" src="<?php echo $route_to_edit && $route_to_edit['bus_image'] ? esc_url($route_to_edit['bus_image']) : ''; ?>" alt="Bus Image" class="mt-1 max-w-[150px] rounded-lg <?php echo $route_to_edit && $route_to_edit['bus_image'] ? '' : 'hidden'; ?>" />
                            <input type="file" name="bus_image" id="bus_image" accept="image/*" class="mt-2 block w-full">
                            <p class="mt-1 text-sm text-gray-500">Chọn ảnh đại diện cho tuyến đường (để trống nếu không thay đổi).</p>
                        </div>
                    </div>
                    <div class="mt-6 flex space-x-4">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition" id="submit_button"><?php echo $route_to_edit ? 'Cập nhật Tuyến Đường' : 'Thêm Tuyến Đường'; ?></button>
                        <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition nhaxe-toggle-form">Hủy</button>
                    </div>
                </form>
            </div>

            <!-- Routes Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm đi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm đến</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giá vé (VNĐ)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khoảng cách (km)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian di chuyển</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ảnh xe</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian tạo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian cập nhật</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($routes)) : ?>
                            <tr>
                                <td colspan="10" class="px-4 py-3 text-sm text-gray-500 text-center">Không tìm thấy tuyến đường nào.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($routes as $route) : ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($route['route_id']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($route['from_location']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($route['to_location']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html(number_format($route['price'], 0, ',', '.')); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($route['distance']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html(format_duration_to_hhmm($route['duration'])); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?php if (!empty($route['bus_image'])) : ?>
                                            <img src="<?php echo esc_url($route['bus_image']); ?>" alt="Bus Image" class="max-w-[50px] rounded-lg" />
                                        <?php else : ?>
                                            Không có ảnh
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($route['created_at']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($route['updated_at']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <a href="<?php echo admin_url('admin.php?page=nhaxemyduyen-routes&action=edit&route_id=' . $route['route_id']); ?>" 
                                           class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition mr-2 nhaxe-toggle-form" 
                                           data-action="edit" 
                                           data-route-id="<?php echo esc_attr($route['route_id']); ?>" 
                                           data-from-location-id="<?php echo esc_attr($route['from_location_id']); ?>" 
                                           data-to-location-id="<?php echo esc_attr($route['to_location_id']); ?>" 
                                           data-price="<?php echo esc_attr($route['price']); ?>" 
                                           data-distance="<?php echo esc_attr($route['distance']); ?>" 
                                           data-duration="<?php echo esc_attr(format_duration_to_hhmm($route['duration'])); ?>" 
                                           data-bus-image="<?php echo esc_attr($route['bus_image']); ?>">Sửa</a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=nhaxemyduyen-routes&action=delete&route_id=' . $route['route_id']), 'nhaxemyduyen_delete_route', 'nonce'); ?>" 
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa tuyến đường này?')" 
                                           class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition">Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        /* Custom styles for specific tweaks */
        .nhaxe-add-form.hidden {
            display: none;
        }
        table th, table td {
            white-space: nowrap;
        }
        #bus_image_preview.hidden {
            display: none;
        }
        @media (max-width: 640px) {
            .sm\:flex-row {
                flex-direction: column;
            }
            .sm\:space-x-4 {
                space-x: 0;
                space-y: 4px;
            }
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            $('.nhaxe-toggle-form').click(function(e) {
                e.preventDefault();
                const action = $(this).data('action');
                const form = $('.nhaxe-add-form');

                form.toggleClass('hidden');

                // Reset form for add action
                $('#route_action').val('add');
                $('#route_id').val('');
                $('#from_location_id').val('');
                $('#to_location_id').val('');
                $('#price').val('');
                $('#distance').val('');
                $('#duration').val('');
                $('#bus_image').val('');
                $('#bus_image_preview').addClass('hidden').attr('src', '');
                $('#submit_button').val('Thêm Tuyến Đường');

                // Populate form for edit action
                if (action === 'edit') {
                    const routeId = $(this).data('route-id');
                    const fromLocationId = $(this).data('from-location-id');
                    const toLocationId = $(this).data('to-location-id');
                    const price = $(this).data('price');
                    const distance = $(this).data('distance');
                    const duration = $(this).data('duration');
                    const busImage = $(this).data('bus-image');

                    $('#route_action').val('edit');
                    $('#route_id').val(routeId);
                    $('#from_location_id').val(fromLocationId);
                    $('#to_location_id').val(toLocationId);
                    $('#price').val(price);
                    $('#distance').val(distance);
                    $('#duration').val(duration);
                    if (busImage) {
                        $('#bus_image_preview').removeClass('hidden').attr('src', busImage);
                    }
                    $('#submit_button').val('Cập nhật Tuyến Đường');
                }
            });

            // Preview image before upload
            $('#bus_image').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#bus_image_preview').removeClass('hidden').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#bus_image_preview').addClass('hidden').attr('src', '');
                }
            });
        });
    </script>
    <?php
}


// Trang quản lý chuyến xe
// Trang quản lý chuyến xe
function nhaxemyduyen_manage_trips() {
    global $wpdb;
    $table_trips = $wpdb->prefix . 'trips';
    $table_locations = $wpdb->prefix . 'locations';
    $table_tickets = $wpdb->prefix . 'tickets';
    $table_drivers = $wpdb->prefix . 'drivers';
    $table_vehicles = $wpdb->prefix . 'vehicles';

    // Kiểm tra quyền truy cập
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền truy cập trang này.');
    }

    // Đăng ký script và style
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    wp_enqueue_style('tailwind-css', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    // Lấy danh sách địa điểm, tài xế và phương tiện
    $locations = $wpdb->get_results("SELECT * FROM $table_locations ORDER BY name", ARRAY_A);
    $drivers = $wpdb->get_results("SELECT driver_id, name FROM $table_drivers WHERE status = 'Active' ORDER BY name", ARRAY_A);
    $vehicles = $wpdb->get_results("SELECT vehicle_id, license_plate, capacity, image FROM $table_vehicles WHERE status = 'Active' ORDER BY license_plate", ARRAY_A);

    // Xử lý bộ lọc
    $filter_from_location = isset($_POST['filter_from_location']) ? intval($_POST['filter_from_location']) : 0;
    $filter_to_location = isset($_POST['filter_to_location']) ? intval($_POST['filter_to_location']) : 0;
    $filter_departure_date = isset($_POST['filter_departure_date']) ? sanitize_text_field($_POST['filter_departure_date']) : '';
    $filter_seats_min = isset($_POST['filter_seats_min']) ? intval($_POST['filter_seats_min']) : '';
    $filter_driver = isset($_POST['filter_driver']) ? intval($_POST['filter_driver']) : 0;
    $filter_vehicle = isset($_POST['filter_vehicle']) ? intval($_POST['filter_vehicle']) : 0;

    if (!empty($filter_departure_date)) {
        $date = DateTime::createFromFormat('m/d/Y', $filter_departure_date);
        if ($date) {
            $filter_departure_date = $date->format('Y-m-d');
        } else {
            $filter_departure_date = '';
        }
    }

    $where_conditions = [];
    if ($filter_from_location > 0) {
        $where_conditions[] = $wpdb->prepare("t.from_location_id = %d", $filter_from_location);
    }
    if ($filter_to_location > 0) {
        $where_conditions[] = $wpdb->prepare("t.to_location_id = %d", $filter_to_location);
    }
    if (!empty($filter_departure_date)) {
        $where_conditions[] = $wpdb->prepare("DATE(t.departure_time) = %s", $filter_departure_date);
    } else {
        $where_conditions[] = "DATE(t.departure_time) = CURDATE()";
    }
    if (!empty($filter_seats_min)) {
        $where_conditions[] = $wpdb->prepare("t.available_seats >= %d", $filter_seats_min);
    }
    if ($filter_driver > 0) {
        $where_conditions[] = $wpdb->prepare("t.driver_id = %d", $filter_driver);
    }
    if ($filter_vehicle > 0) {
        $where_conditions[] = $wpdb->prepare("t.vehicle_id = %d", $filter_vehicle);
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = " WHERE " . implode(' AND ', $where_conditions);
    }

    // Lấy danh sách chuyến xe với bộ lọc
    $trips = $wpdb->get_results("
        SELECT t.*, l1.name as from_location, l2.name as to_location, d.name as driver_name, v.license_plate as vehicle_plate, v.image as bus_image
        FROM $table_trips t
        JOIN $table_locations l1 ON t.from_location_id = l1.location_id
        JOIN $table_locations l2 ON t.to_location_id = l2.location_id
        LEFT JOIN $table_drivers d ON t.driver_id = d.driver_id
        LEFT JOIN $table_vehicles v ON t.vehicle_id = v.vehicle_id
        $where_clause
        ORDER BY t.departure_time DESC
    ", ARRAY_A);

    // Xử lý thông báo
    $message = '';
    if (isset($_GET['message'])) {
        if ($_GET['message'] === 'add_success') {
            $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>Thêm chuyến xe thành công!</p></div>';
        } elseif ($_GET['message'] === 'edit_success') {
            $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>Cập nhật chuyến xe thành công!</p></div>';
        } elseif ($_GET['message'] === 'delete_success') {
            $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>Xóa chuyến xe thành công!</p></div>';
        }
    }

    ?>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Quản lý Chuyến Xe</h1>
        <div id="nhaxe-message"><?php echo $message; ?></div>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Danh sách Chuyến Xe</h2>

            <!-- Filter Form and Add Button -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <!-- Filter Form -->
                <form method="post" action="" class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-4" id="filter-form">
                    <select name="filter_from_location" id="filter_from_location" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="0">-- Chọn điểm đi --</option>
                        <?php foreach ($locations as $location) : ?>
                            <option value="<?php echo esc_attr($location['location_id']); ?>" <?php selected($filter_from_location, $location['location_id']); ?>>
                                <?php echo esc_html($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="filter_to_location" id="filter_to_location" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="0">-- Chọn điểm đến --</option>
                        <?php foreach ($locations as $location) : ?>
                            <option value="<?php echo esc_attr($location['location_id']); ?>" <?php selected($filter_to_location, $location['location_id']); ?>>
                                <?php echo esc_html($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="filter_departure_date" id="filter_departure_date" 
                           value="<?php echo esc_attr(!empty($filter_departure_date) ? date('m/d/Y', strtotime($filter_departure_date)) : date('m/d/Y')); ?>" 
                           placeholder="mm/dd/yyyy" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Lọc</button>
                </form>

                <!-- Add Trip Button -->
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition nhaxe-toggle-form mt-4 sm:mt-0" data-action="add">Thêm Chuyến Xe</button>
            </div>

            <!-- Add/Edit Trip Form -->
            <div class="nhaxe-add-form hidden bg-gray-50 p-6 rounded-lg mb-6">
                <form id="trip-form" action="">
                    <?php wp_nonce_field('nhaxemyduyen_trip_action', 'nhaxemyduyen_trip_nonce'); ?>
                    <input type="hidden" name="nhaxemyduyen_trip_action" id="trip_action" value="add">
                    <input type="hidden" name="trip_id" id="trip_id" value="">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="from_location_id" class="block text-sm font-medium text-gray-700">Điểm đi</label>
                            <select name="from_location_id" id="from_location_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Chọn điểm đi --</option>
                                <?php foreach ($locations as $location) : ?>
                                    <option value="<?php echo esc_attr($location['location_id']); ?>">
                                        <?php echo esc_html($location['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="to_location_id" class="block text-sm font-medium text-gray-700">Điểm đến</label>
                            <select name="to_location_id" id="to_location_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Chọn điểm đến --</option>
                                <?php foreach ($locations as $location) : ?>
                                    <option value="<?php echo esc_attr($location['location_id']); ?>">
                                        <?php echo esc_html($location['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="driver_id" class="block text-sm font-medium text-gray-700">Tài xế</label>
                            <select name="driver_id" id="driver_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Chọn tài xế --</option>
                                <?php foreach ($drivers as $driver) : ?>
                                    <option value="<?php echo esc_attr($driver['driver_id']); ?>">
                                        <?php echo esc_html($driver['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="vehicle_id" class="block text-sm font-medium text-gray-700">Phương tiện</label>
                            <select name="vehicle_id" id="vehicle_id" onchange="updateAvailableSeats(this)" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Chọn xe --</option>
                                <?php foreach ($vehicles as $vehicle) : ?>
                                    <option value="<?php echo esc_attr($vehicle['vehicle_id']); ?>" 
                                            data-capacity="<?php echo esc_attr($vehicle['capacity']); ?>" 
                                            data-image="<?php echo esc_attr($vehicle['image']); ?>">
                                        <?php echo esc_html($vehicle['license_plate']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hình ảnh xe</label>
                            <div class="nhaxe-image-preview mt-1"></div>
                        </div>
                        <div>
                            <label for="pickup_location" class="block text-sm font-medium text-gray-700">Điểm đón</label>
                            <input type="text" name="pickup_location" id="pickup_location" placeholder="Nhập điểm đón cụ thể" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="dropoff_location" class="block text-sm font-medium text-gray-700">Điểm trả</label>
                            <input type="text" name="dropoff_location" id="dropoff_location" placeholder="Nhập điểm trả cụ thể" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="departure_time" class="block text-sm font-medium text-gray-700">Giờ đi</label>
                            <input type="datetime-local" name="departure_time" id="departure_time" value="<?php echo date('Y-m-d\TH:i'); ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="arrival_time" class="block text-sm font-medium text-gray-700">Giờ đến (dự kiến)</label>
                            <input type="datetime-local" name="arrival_time" id="arrival_time" readonly required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100">
                        </div>
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700">Giá vé (VNĐ)</label>
                            <input type="number" name="price" id="price" step="0.01" readonly required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100">
                        </div>
                        <div>
                            <label for="available_seats" class="block text-sm font-medium text-gray-700">Số ghế trống</label>
                            <input type="number" name="available_seats" id="available_seats" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="mt-6 flex space-x-4">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition" id="submit-trip">Thêm Chuyến Xe</button>
                        <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition nhaxe-toggle-form">Hủy</button>
                    </div>
                </form>
            </div>

            <!-- Trips Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200" id="trips-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm đi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm đến</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm đón</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm trả</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tài xế</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phương tiện</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giờ đi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giờ đến</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giá vé (VNĐ)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số ghế trống</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hình ảnh xe</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($trips)) : ?>
                            <tr>
                                <td colspan="13" class="px-4 py-3 text-sm text-gray-500 text-center">Không có chuyến xe nào phù hợp với tiêu chí.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($trips as $trip) : ?>
                                <tr class="hover:bg-gray-50" data-trip-id="<?php echo esc_attr($trip['trip_id']); ?>">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['trip_id']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['from_location']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['to_location']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['pickup_location']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['dropoff_location']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo $trip['driver_name'] ? esc_html($trip['driver_name']) : 'Chưa chọn'; ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo $trip['vehicle_plate'] ? esc_html($trip['vehicle_plate']) : 'Chưa chọn'; ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html(date('m/d/Y H:i', strtotime($trip['departure_time']))); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo $trip['arrival_time'] ? esc_html(date('m/d/Y H:i', strtotime($trip['arrival_time']))) : 'Chưa có'; ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html(number_format($trip['price'], 0, ',', '.')); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['available_seats']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?php if ($trip['bus_image']) : ?>
                                            <img src="<?php echo esc_url($trip['bus_image']); ?>" alt="Hình ảnh xe" class="max-w-[100px] rounded-lg" />
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <button type="button" class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition mr-2 nhaxe-toggle-form" data-action="edit" data-trip-id="<?php echo esc_attr($trip['trip_id']); ?>">Sửa</button>
                                        <button type="button" class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition nhaxe-delete-trip" data-trip-id="<?php echo esc_attr($trip['trip_id']); ?>" data-nonce="<?php echo wp_create_nonce('nhaxemyduyen_delete_trip'); ?>">Xóa</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        /* Custom styles for specific tweaks */
        .nhaxe-add-form.hidden {
            display: none;
        }
        table th, table td {
            white-space: nowrap;
        }
        .nhaxe-image-preview img {
            display: block;
        }
        /* Style jQuery UI Datepicker to match Tailwind */
        .ui-datepicker {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .ui-datepicker-header {
            background: #f3f4f6;
            border-radius: 0.25rem;
        }
        .ui-datepicker-title {
            font-weight: 600;
            color: #1f2937;
        }
        .ui-datepicker-prev, .ui-datepicker-next {
            cursor: pointer;
            color: #2563eb;
        }
        .ui-datepicker-prev:hover, .ui-datepicker-next:hover {
            background: #e5e7eb;
        }
        .ui-datepicker-calendar td a {
            text-align: center;
            padding: 0.25rem;
            border-radius: 0.25rem;
            color: #1f2937;
        }
        .ui-datepicker-calendar td a:hover {
            background: #e5e7eb;
        }
        .ui-state-highlight, .ui-widget-content .ui-state-highlight {
            background: #2563eb;
            color: #fff;
            border-radius: 0.25rem;
        }
        @media (max-width: 640px) {
            .sm\:flex-row {
                flex-direction: column;
            }
            .sm\:space-x-4 {
                space-x: 0;
                space-y: 4px;
            }
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            // Khởi tạo Datepicker
            $('#filter_departure_date').datepicker({
                dateFormat: 'mm/dd/yy',
                onSelect: function(dateText) {
                    $(this).val(dateText);
                }
            });

            // Xử lý thay đổi điểm đi/đến để lấy giá và thời gian
            $('#from_location_id, #to_location_id').change(function() {
                var from_location_id = $('#from_location_id').val();
                var to_location_id = $('#to_location_id').val();

                if (from_location_id && to_location_id) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'nhaxemyduyen_get_route_info',
                            from_location_id: from_location_id,
                            to_location_id: to_location_id
                        },
                        success: function(response) {
                            if (response.success) {
                                var data = response.data;
                                $('#price').val(data.price);

                                var departureTime = $('#departure_time').val();
                                if (departureTime && data.duration) {
                                    var totalMinutes = parseInt(data.duration);

                                    if (totalMinutes > 0) {
                                        var departureDate = new Date(departureTime);
                                        if (isNaN(departureDate.getTime())) {
                                            alert('Giờ đi không hợp lệ. Vui lòng kiểm tra lại.');
                                            $('#arrival_time').val('');
                                            return;
                                        }

                                        var timezoneOffset = 7 * 60;
                                        var adjustedDepartureDate = new Date(departureDate.getTime() + (timezoneOffset * 60000));
                                        var arrivalDate = new Date(adjustedDepartureDate.getTime() + totalMinutes * 60000);
                                        var arrivalTimeFormatted = arrivalDate.toISOString().slice(0, 16);
                                        $('#arrival_time').val(arrivalTimeFormatted);
                                    } else {
                                        $('#arrival_time').val('');
                                        alert('Thời gian di chuyển không hợp lệ. Vui lòng kiểm tra tuyến đường.');
                                    }
                                }
                            } else {
                                $('#price').val('');
                                $('#arrival_time').val('');
                                alert('Không tìm thấy tuyến đường phù hợp.');
                            }
                        },
                        error: function() {
                            alert('Đã có lỗi xảy ra khi lấy thông tin tuyến đường.');
                        }
                    });
                }
            });

            // Trigger cập nhật giá và thời gian khi thay đổi giờ đi
            $('#departure_time').change(function() {
                $('#from_location_id, #to_location_id').trigger('change');
            });

            // Xử lý toggle form thêm/sửa
            $(document).on('click', '.nhaxe-toggle-form', function() {
                var action = $(this).data('action');
                var tripId = $(this).data('trip-id');

                if (action === 'add') {
                    // Reset form cho thêm mới
                    $('#trip_action').val('add');
                    $('#trip_id').val('');
                    $('#from_location_id').val('');
                    $('#to_location_id').val('');
                    $('#pickup_location').val('');
                    $('#dropoff_location').val('');
                    $('#departure_time').val('<?php echo date('Y-m-d\TH:i'); ?>');
                    $('#arrival_time').val('');
                    $('#price').val('');
                    $('#available_seats').val('');
                    $('#driver_id').val('');
                    $('#vehicle_id').val('');
                    $('.nhaxe-image-preview').html('');
                    $('#submit-trip').text('Thêm Chuyến Xe');
                    $('.nhaxe-add-form').removeClass('hidden');
                } else if (action === 'edit' && tripId) {
                    // Lấy dữ liệu chuyến xe qua AJAX
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'nhaxemyduyen_get_trip',
                            trip_id: tripId,
                            nonce: '<?php echo wp_create_nonce('nhaxemyduyen_edit_trip'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                var trip = response.data;
                                $('#trip_action').val('edit');
                                $('#trip_id').val(trip.trip_id);
                                $('#from_location_id').val(trip.from_location_id);
                                $('#to_location_id').val(trip.to_location_id);
                                $('#pickup_location').val(trip.pickup_location);
                                $('#dropoff_location').val(trip.dropoff_location);
                                $('#departure_time').val(new Date(trip.departure_time).toISOString().slice(0, 16));
                                $('#arrival_time').val(trip.arrival_time ? new Date(trip.arrival_time).toISOString().slice(0, 16) : '');
                                $('#price').val(trip.price);
                                $('#available_seats').val(trip.available_seats);
                                $('#driver_id').val(trip.driver_id || '');
                                $('#vehicle_id').val(trip.vehicle_id || '');

                                // Cập nhật hình ảnh xe
                                var vehicleImage = $('#vehicle_id option:selected').data('image') || '';
                                if (vehicleImage) {
                                    $('.nhaxe-image-preview').html('<img src="' + vehicleImage + '" alt="Hình ảnh xe" class="max-w-[200px] rounded-lg">');
                                } else {
                                    $('.nhaxe-image-preview').html('');
                                }

                                $('#submit-trip').text('Cập nhật Chuyến Xe');
                                $('.nhaxe-add-form').removeClass('hidden');
                            } else {
                                $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Không thể lấy dữ liệu chuyến xe.</p></div>');
                            }
                        },
                        error: function() {
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra khi lấy dữ liệu chuyến xe.</p></div>');
                        }
                    });
                } else {
                    $('.nhaxe-add-form').addClass('hidden');
                }
            });

            // Xử lý submit form thêm/sửa chuyến xe qua AJAX
            $('#trip-form').submit(function(e) {
                e.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData + '&action=nhaxemyduyen_manage_trip',
                    success: function(response) {
                        if (response.success) {
                            $('#nhaxe-message').html('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>' + response.data.message + '</p></div>');
                            $('.nhaxe-add-form').addClass('hidden');
                            refreshTripsTable();
                        } else {
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra. Vui lòng thử lại.</p></div>');
                    }
                });
            });

            // Xử lý xóa chuyến xe qua AJAX
            $(document).on('click', '.nhaxe-delete-trip', function() {
                if (!confirm('Bạn có chắc chắn muốn xóa?')) return;

                var tripId = $(this).data('trip-id');
                var nonce = $(this).data('nonce');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'nhaxemyduyen_delete_trip',
                        trip_id: tripId,
                        nhaxemyduyen_delete_nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#nhaxe-message').html('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>Xóa chuyến xe thành công!</p></div>');
                            refreshTripsTable();
                        } else {
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra khi xóa chuyến xe.</p></div>');
                    }
                });
            });

            // Xử lý lọc danh sách chuyến xe qua AJAX
            $('#filter-form').submit(function(e) {
                e.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData + '&action=nhaxemyduyen_filter_trips',
                    success: function(response) {
                        if (response.success) {
                            $('#trips-table tbody').html(response.data.html);
                        } else {
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi khi lọc chuyến xe.</p></div>');
                        }
                    },
                    error: function() {
                        $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra khi lọc chuyến xe.</p></div>');
                    }
                });
            });

            // Hàm làm mới bảng chuyến xe
            function refreshTripsTable() {
                var formData = $('#filter-form').serialize();
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData + '&action=nhaxemyduyen_filter_trips',
                    success: function(response) {
                        if (response.success) {
                            $('#trips-table tbody').html(response.data.html);
                        } else {
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi khi làm mới danh sách chuyến xe.</p></div>');
                        }
                    },
                    error: function() {
                        $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra khi làm mới danh sách chuyến xe.</p></div>');
                    }
                });
            }

            // Cập nhật số ghế trống và hình ảnh xe khi chọn phương tiện
            window.updateAvailableSeats = function(element) {
                var selectedOption = $(element).find('option:selected');
                var capacity = selectedOption.data('capacity') || 0;
                var imageUrl = selectedOption.data('image') || '';
                $('#available_seats').val(capacity);
                if (imageUrl) {
                    $('.nhaxe-image-preview').html('<img src="' + imageUrl + '" alt="Hình ảnh xe" class="max-w-[200px] rounded-lg">');
                } else {
                    $('.nhaxe-image-preview').html('');
                }
            };
        });
    </script>
    <?php
}

// Xử lý AJAX lấy dữ liệu chuyến xe
add_action('wp_ajax_nhaxemyduyen_get_trip', 'nhaxemyduyen_get_trip_callback');
function nhaxemyduyen_get_trip_callback() {
    check_ajax_referer('nhaxemyduyen_edit_trip', 'nonce');

    global $wpdb;
    $table_trips = $wpdb->prefix . 'trips';
    $trip_id = intval($_POST['trip_id']);

    $trip = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_trips WHERE trip_id = %d", $trip_id), ARRAY_A);

    if ($trip) {
        wp_send_json_success($trip);
    } else {
        wp_send_json_error();
    }
}

// Xử lý AJAX thêm/sửa chuyến xe
add_action('wp_ajax_nhaxemyduyen_manage_trip', 'nhaxemyduyen_manage_trip_callback');
function nhaxemyduyen_manage_trip_callback() {
    global $wpdb;
    $table_trips = $wpdb->prefix . 'trips';

    check_ajax_referer('nhaxemyduyen_trip_action', 'nhaxemyduyen_trip_nonce');

    $action = isset($_POST['nhaxemyduyen_trip_action']) ? sanitize_text_field($_POST['nhaxemyduyen_trip_action']) : '';

    if (
        empty($_POST['from_location_id']) ||
        empty($_POST['to_location_id']) ||
        empty($_POST['pickup_location']) ||
        empty($_POST['dropoff_location']) ||
        empty($_POST['departure_time']) ||
        empty($_POST['arrival_time']) ||
        empty($_POST['price']) ||
        empty($_POST['available_seats'])
    ) {
        wp_send_json_error(array('message' => 'Lỗi: Vui lòng điền đầy đủ các trường bắt buộc!'));
    }

    $trip_data = array(
        'from_location_id' => intval($_POST['from_location_id']),
        'to_location_id' => intval($_POST['to_location_id']),
        'pickup_location' => sanitize_text_field($_POST['pickup_location']),
        'dropoff_location' => sanitize_text_field($_POST['dropoff_location']),
        'departure_time' => date('Y-m-d H:i:s', strtotime($_POST['departure_time'])),
        'arrival_time' => date('Y-m-d H:i:s', strtotime($_POST['arrival_time'])),
        'price' => floatval($_POST['price']),
        'available_seats' => intval($_POST['available_seats']),
        'driver_id' => !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null,
        'vehicle_id' => !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null,
    );

    $current_time = current_time('mysql');

    if ($action === 'add') {
        $trip_data['created_at'] = $current_time;
        $trip_data['updated_at'] = $current_time;

        $result = $wpdb->insert($table_trips, $trip_data);
        if ($result === false) {
            wp_send_json_error(array('message' => 'Lỗi: Không thể thêm chuyến xe. ' . esc_html($wpdb->last_error)));
        } else {
            wp_send_json_success(array('message' => 'Thêm chuyến xe thành công!'));
        }
    } elseif ($action === 'edit') {
        $trip_id = intval($_POST['trip_id']);
        $trip_data['updated_at'] = $current_time;

        $result = $wpdb->update($table_trips, $trip_data, array('trip_id' => $trip_id));
        if ($result === false) {
            wp_send_json_error(array('message' => 'Lỗi: Không thể cập nhật chuyến xe. ' . esc_html($wpdb->last_error)));
        } else {
            wp_send_json_success(array('message' => 'Cập nhật chuyến xe thành công!'));
        }
    } else {
        wp_send_json_error(array('message' => 'Hành động không hợp lệ.'));
    }
}

// Xử lý AJAX xóa chuyến xe
add_action('wp_ajax_nhaxemyduyen_delete_trip', 'nhaxemyduyen_delete_trip_callback');
function nhaxemyduyen_delete_trip_callback() {
    global $wpdb;
    $table_trips = $wpdb->prefix . 'trips';
    $table_tickets = $wpdb->prefix . 'tickets';

    check_ajax_referer('nhaxemyduyen_delete_trip', 'nhaxemyduyen_delete_nonce');

    $trip_id = intval($_POST['trip_id']);
    $ticket_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_tickets WHERE trip_id = %d", $trip_id));

    if ($ticket_count > 0) {
        wp_send_json_error(array('message' => 'Không thể xóa chuyến xe vì đã có vé được đặt!'));
    }

    $result = $wpdb->delete($table_trips, array('trip_id' => $trip_id));
    if ($result === false) {
        wp_send_json_error(array('message' => 'Lỗi: Không thể xóa chuyến xe. ' . esc_html($wpdb->last_error)));
    }

    wp_send_json_success();
}

// Xử lý AJAX lọc chuyến xe
add_action('wp_ajax_nhaxemyduyen_filter_trips', 'nhaxemyduyen_filter_trips_callback');
function nhaxemyduyen_filter_trips_callback() {
    global $wpdb;
    $table_trips = $wpdb->prefix . 'trips';
    $table_locations = $wpdb->prefix . 'locations';
    $table_drivers = $wpdb->prefix . 'drivers';
    $table_vehicles = $wpdb->prefix . 'vehicles';

    $filter_from_location = isset($_POST['filter_from_location']) ? intval($_POST['filter_from_location']) : 0;
    $filter_to_location = isset($_POST['filter_to_location']) ? intval($_POST['filter_to_location']) : 0;
    $filter_departure_date = isset($_POST['filter_departure_date']) ? sanitize_text_field($_POST['filter_departure_date']) : '';

    if (!empty($filter_departure_date)) {
        $date = DateTime::createFromFormat('m/d/Y', $filter_departure_date);
        if ($date) {
            $filter_departure_date = $date->format('Y-m-d');
        } else {
            $filter_departure_date = '';
        }
    }

    $where_conditions = [];
    if ($filter_from_location > 0) {
        $where_conditions[] = $wpdb->prepare("t.from_location_id = %d", $filter_from_location);
    }
    if ($filter_to_location > 0) {
        $where_conditions[] = $wpdb->prepare("t.to_location_id = %d", $filter_to_location);
    }
    if (!empty($filter_departure_date)) {
        $where_conditions[] = $wpdb->prepare("DATE(t.departure_time) = %s", $filter_departure_date);
    } else {
        $where_conditions[] = "DATE(t.departure_time) = CURDATE()";
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = " WHERE " . implode(' AND ', $where_conditions);
    }

    $trips = $wpdb->get_results("
        SELECT t.*, l1.name as from_location, l2.name as to_location, d.name as driver_name, v.license_plate as vehicle_plate, v.image as bus_image
        FROM $table_trips t
        JOIN $table_locations l1 ON t.from_location_id = l1.location_id
        JOIN $table_locations l2 ON t.to_location_id = l2.location_id
        LEFT JOIN $table_drivers d ON t.driver_id = d.driver_id
        LEFT JOIN $table_vehicles v ON t.vehicle_id = v.vehicle_id
        $where_clause
        ORDER BY t.departure_time DESC
    ", ARRAY_A);

    ob_start();
    if (empty($trips)) {
        echo '<tr><td colspan="13" class="px-4 py-3 text-sm text-gray-500 text-center">Không có chuyến xe nào phù hợp với tiêu chí.</td></tr>';
    } else {
        foreach ($trips as $trip) {
            ?>
            <tr class="hover:bg-gray-50" data-trip-id="<?php echo esc_attr($trip['trip_id']); ?>">
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['trip_id']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['from_location']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['to_location']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['pickup_location']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['dropoff_location']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo $trip['driver_name'] ? esc_html($trip['driver_name']) : 'Chưa chọn'; ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo $trip['vehicle_plate'] ? esc_html($trip['vehicle_plate']) : 'Chưa chọn'; ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html(date('m/d/Y H:i', strtotime($trip['departure_time']))); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo $trip['arrival_time'] ? esc_html(date('m/d/Y H:i', strtotime($trip['arrival_time']))) : 'Chưa có'; ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html(number_format($trip['price'], 0, ',', '.')); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['available_seats']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900">
                    <?php if ($trip['bus_image']) : ?>
                        <img src="<?php echo esc_url($trip['bus_image']); ?>" alt="Hình ảnh xe" class="max-w-[100px] rounded-lg" />
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-sm text-gray-900">
                    <button type="button" class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition mr-2 nhaxe-toggle-form" data-action="edit" data-trip-id="<?php echo esc_attr($trip['trip_id']); ?>">Sửa</button>
                    <button type="button" class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition nhaxe-delete-trip" data-trip-id="<?php echo esc_attr($trip['trip_id']); ?>" data-nonce="<?php echo wp_create_nonce('nhaxemyduyen_delete_trip'); ?>">Xóa</button>
                </td>
            </tr>
            <?php
        }
    }
    $html = ob_get_clean();
    wp_send_json_success(array('html' => $html));
}

// Xử lý AJAX để lấy thông tin tuyến đường
add_action('wp_ajax_nhaxemyduyen_get_route_info', 'nhaxemyduyen_get_route_info');
function nhaxemyduyen_get_route_info() {
    global $wpdb;
    $table_routes = $wpdb->prefix . 'routes';
    $table_locations = $wpdb->prefix . 'locations';

    $from_location_id = isset($_POST['from_location_id']) ? intval($_POST['from_location_id']) : 0;
    $to_location_id = isset($_POST['to_location_id']) ? intval($_POST['to_location_id']) : 0;

    if (!$from_location_id || !$to_location_id) {
        wp_send_json_error();
        return;
    }

    // Lấy thông tin tuyến đường
    $route = $wpdb->get_row($wpdb->prepare("
        SELECT r.*
        FROM $table_routes r
        WHERE r.from_location_id = %d AND r.to_location_id = %d
    ", $from_location_id, $to_location_id), ARRAY_A);

    if ($route) {
        wp_send_json_success($route);
    } else {
        wp_send_json_error();
    }
}

// quản lý vé xe
function nhaxemyduyen_manage_tickets() {
    global $wpdb;
    $table_tickets = $wpdb->prefix . 'tickets';
    $table_trips = $wpdb->prefix . 'trips';
    $table_locations = $wpdb->prefix . 'locations';
    $table_drivers = $wpdb->prefix . 'drivers';
    $table_vehicles = $wpdb->prefix . 'vehicles';

    // Đăng ký script và style
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    wp_enqueue_style('tailwind-css', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    // Xử lý thêm vé
    $add_error = '';
    $add_success = '';
    if (isset($_POST['nhaxemyduyen_add_ticket']) && check_admin_referer('nhaxemyduyen_ticket_action', 'nhaxemyduyen_ticket_nonce')) {
        $data = array(
            'trip_id' => intval($_POST['trip_id']),
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone']),
            'customer_email' => sanitize_email($_POST['customer_email'] ?? ''),
            'pickup_location' => sanitize_text_field($_POST['pickup_location']),
            'dropoff_location' => sanitize_text_field($_POST['dropoff_location']),
            'seat_number' => sanitize_text_field($_POST['seat_number']),
            'status' => sanitize_text_field($_POST['status']),
            'note' => sanitize_text_field($_POST['note'] ?? ''),
        );

        // Validate required fields
        $required_fields = ['trip_id', 'customer_name', 'customer_phone', 'pickup_location', 'dropoff_location', 'seat_number', 'status'];
        $missing_fields = array_filter($required_fields, fn($field) => empty($data[$field]));
        if (!empty($missing_fields)) {
            $add_error = 'Vui lòng nhập đầy đủ các trường bắt buộc: ' . implode(', ', $missing_fields);
        } elseif (!preg_match('/^[0-9]{10,11}$/', $data['customer_phone'])) {
            $add_error = 'Số điện thoại phải có 10-11 chữ số.';
        } elseif (!preg_match('/^A[1-9][0-9]?$/', $data['seat_number']) || intval(substr($data['seat_number'], 1)) > 44) {
            $add_error = 'Số ghế không hợp lệ (phải là A1-A44).';
        } else {
            // Gửi yêu cầu tới API
            $response = wp_remote_post(rest_url('nhaxemyduyen/v1/tickets'), array(
                'method' => 'POST',
                'body' => json_encode($data),
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
            ));

            if (is_wp_error($response)) {
                $add_error = 'Lỗi khi thêm vé: ' . $response->get_error_message();
            } else {
                $response_body = json_decode(wp_remote_retrieve_body($response), true);
                if (wp_remote_retrieve_response_code($response) === 201) {
                    $add_success = 'Vé xe đã được thêm thành công. Mã vé: ' . $response_body['ticket_code'];
                } else {
                    $add_error = $response_body['message'] ?? 'Lỗi không xác định khi thêm vé.';
                }
            }
        }
    }

    // Xử lý cập nhật trạng thái
    if (isset($_POST['nhaxemyduyen_ticket_action']) && $_POST['nhaxemyduyen_ticket_action'] === 'update' && check_admin_referer('nhaxemyduyen_ticket_nonce')) {
        $ticket_id = intval($_POST['ticket_id']);
        $status = sanitize_text_field($_POST['status']);
        $valid_statuses = ['Đã thanh toán', 'Chưa thanh toán', 'Đã hủy'];
        
        if (in_array($status, $valid_statuses)) {
            $result = $wpdb->update(
                $table_tickets,
                array('status' => $status, 'updated_at' => current_time('mysql')),
                array('ticket_id' => $ticket_id)
            );
            if ($result === false) {
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4"><p>Lỗi khi cập nhật trạng thái vé: ' . $wpdb->last_error . '</p></div>';
            } else {
                echo '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4"><p>Trạng thái vé đã được cập nhật.</p></div>';
            }
        }
    }

    // Xử lý xóa vé
    if (isset($_POST['nhaxemyduyen_ticket_action']) && $_POST['nhaxemyduyen_ticket_action'] === 'delete' && check_admin_referer('nhaxemyduyen_delete_ticket', 'nhaxemyduyen_delete_nonce')) {
        $ticket_id = intval($_POST['ticket_id']);
        $ticket = $wpdb->get_row($wpdb->prepare("SELECT trip_id FROM $table_tickets WHERE ticket_id = %d", $ticket_id));
        if ($ticket) {
            $result = $wpdb->delete($table_tickets, array('ticket_id' => $ticket_id));
            if ($result !== false) {
                $trip = $wpdb->get_row($wpdb->prepare("SELECT available_seats FROM $table_trips WHERE trip_id = %d", $ticket->trip_id));
                if ($trip) {
                    $wpdb->update(
                        $table_trips,
                        array('available_seats' => $trip->available_seats + 1),
                        array('trip_id' => $ticket->trip_id)
                    );
                }
                echo '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4"><p>Vé xe đã được xóa.</p></div>';
            } else {
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4"><p>Lỗi khi xóa vé: ' . $wpdb->last_error . '</p></div>';
            }
        }
    }

    // Lấy danh sách địa điểm, tài xế và phương tiện
    $locations = $wpdb->get_results("SELECT * FROM $table_locations", ARRAY_A);
    $drivers = $wpdb->get_results("SELECT driver_id, name FROM $table_drivers WHERE status = 'Active' ORDER BY name", ARRAY_A);
    $vehicles = $wpdb->get_results("SELECT vehicle_id, license_plate FROM $table_vehicles WHERE status = 'Active' ORDER BY license_plate", ARRAY_A);

    // Lấy danh sách điểm đón và trả từ bảng trips
    $pickup_locations = $wpdb->get_col("SELECT DISTINCT pickup_location FROM $table_trips WHERE pickup_location != '' ORDER BY pickup_location");
    $dropoff_locations = $wpdb->get_col("SELECT DISTINCT dropoff_location FROM $table_trips WHERE dropoff_location != '' ORDER BY dropoff_location");

    // Cảnh báo nếu không có điểm đón hoặc trả
    if (empty($pickup_locations)) {
        echo '<div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4"><p>Cảnh báo: Không có điểm đón nào trong wp_trips!</p></div>';
    }
    if (empty($dropoff_locations)) {
        echo '<div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4"><p>Cảnh báo: Không có điểm trả nào trong wp_trips!</p></div>';
    }

    // Bộ lọc
    $filter_customer_phone = isset($_POST['filter_customer_phone']) ? sanitize_text_field($_POST['filter_customer_phone']) : '';
    $filter_status = isset($_POST['filter_status']) ? sanitize_text_field($_POST['filter_status']) : '';
    $filter_departure_date = isset($_POST['filter_departure_date']) ? sanitize_text_field($_POST['filter_departure_date']) : '';
    $filter_from_location = isset($_POST['filter_from_location']) ? intval($_POST['filter_from_location']) : 0;
    $filter_to_location = isset($_POST['filter_to_location']) ? intval($_POST['filter_to_location']) : 0;
    $filter_pickup_location = isset($_POST['filter_pickup_location']) ? sanitize_text_field($_POST['filter_pickup_location']) : '';
    $filter_dropoff_location = isset($_POST['filter_dropoff_location']) ? sanitize_text_field($_POST['filter_dropoff_location']) : '';
    $filter_driver = isset($_POST['filter_driver']) ? intval($_POST['filter_driver']) : 0;
    $filter_vehicle = isset($_POST['filter_vehicle']) ? intval($_POST['filter_vehicle']) : 0;

    if (!empty($filter_departure_date)) {
        $date = DateTime::createFromFormat('m/d/Y', $filter_departure_date);
        if ($date) {
            $filter_departure_date = $date->format('Y-m-d');
        } else {
            $filter_departure_date = '';
        }
    }

    $where_conditions = [];
    if (!empty($filter_customer_phone)) {
        $where_conditions[] = $wpdb->prepare("t.customer_phone LIKE %s", '%' . $filter_customer_phone . '%');
    }
    if (!empty($filter_status)) {
        $where_conditions[] = $wpdb->prepare("t.status = %s", $filter_status);
    }
    if (!empty($filter_departure_date)) {
        $where_conditions[] = $wpdb->prepare("DATE(tr.departure_time) = %s", $filter_departure_date);
    } else {
        $where_conditions[] = "tr.departure_time >= CURDATE()";
    }
    if ($filter_from_location > 0) {
        $where_conditions[] = $wpdb->prepare("tr.from_location_id = %d", $filter_from_location);
    }
    if ($filter_to_location > 0) {
        $where_conditions[] = $wpdb->prepare("tr.to_location_id = %d", $filter_to_location);
    }
    if (!empty($filter_pickup_location)) {
        $where_conditions[] = $wpdb->prepare("t.pickup_location = %s", $filter_pickup_location);
    }
    if (!empty($filter_dropoff_location)) {
        $where_conditions[] = $wpdb->prepare("t.dropoff_location = %s", $filter_dropoff_location);
    }
    if ($filter_driver > 0) {
        $where_conditions[] = $wpdb->prepare("tr.driver_id = %d", $filter_driver);
    }
    if ($filter_vehicle > 0) {
        $where_conditions[] = $wpdb->prepare("tr.vehicle_id = %d", $filter_vehicle);
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = " WHERE " . implode(' AND ', $where_conditions);
    }

    // Lấy danh sách vé
    $tickets = $wpdb->get_results("
        SELECT t.*, tr.departure_time, tr.pickup_location as trip_pickup_location, tr.dropoff_location as trip_dropoff_location, 
               l1.name as from_location, l2.name as to_location, d.name as driver_name, v.license_plate as vehicle_plate
        FROM $table_tickets t
        JOIN $table_trips tr ON t.trip_id = tr.trip_id
        JOIN $table_locations l1 ON tr.from_location_id = l1.location_id
        JOIN $table_locations l2 ON tr.to_location_id = l2.location_id
        LEFT JOIN $table_drivers d ON tr.driver_id = d.driver_id
        LEFT JOIN $table_vehicles v ON tr.vehicle_id = v.vehicle_id
        $where_clause
    ", ARRAY_A);

    // Lấy danh sách chuyến xe có ghế trống
    $trips = $wpdb->get_results("
        SELECT t.*, l1.name as from_location, l2.name as to_location, t.pickup_location, t.dropoff_location, 
               d.name as driver_name, v.license_plate as vehicle_plate, v.image as bus_image
        FROM $table_trips t
        JOIN $table_locations l1 ON t.from_location_id = l1.location_id
        JOIN $table_locations l2 ON t.to_location_id = l2.location_id
        LEFT JOIN $table_drivers d ON t.driver_id = d.driver_id
        LEFT JOIN $table_vehicles v ON t.vehicle_id = v.vehicle_id
        WHERE t.available_seats > 0 AND t.departure_time >= CURDATE()
    ", ARRAY_A);

    ?>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Quản lý Vé Xe</h1>

        <?php if ($add_success) : ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
                <p><?php echo esc_html($add_success); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($add_error) : ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
                <p><?php echo esc_html($add_error); ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Danh sách Vé Xe</h2>

            <!-- Filter Form -->
            <form method="post" action="" class="mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <input type="text" name="filter_customer_phone" id="filter_customer_phone" value="<?php echo esc_attr($filter_customer_phone); ?>" placeholder="Số điện thoại khách hàng" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <select name="filter_status" id="filter_status" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Chọn trạng thái --</option>
                        <option value="Đã thanh toán" <?php selected($filter_status, 'Đã thanh toán'); ?>>Đã thanh toán</option>
                        <option value="Chưa thanh toán" <?php selected($filter_status, 'Chưa thanh toán'); ?>>Chưa thanh toán</option>
                        <option value="Đã hủy" <?php selected($filter_status, 'Đã hủy'); ?>>Đã hủy</option>
                    </select>
                    <input type="text" name="filter_departure_date" id="filter_departure_date" value="<?php echo esc_attr(!empty($filter_departure_date) ? date('m/d/Y', strtotime($filter_departure_date)) : date('m/d/Y')); ?>" placeholder="mm/dd/yyyy" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <select name="filter_from_location" id="filter_from_location" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="0">-- Chọn điểm đi --</option>
                        <?php foreach ($locations as $location) : ?>
                            <option value="<?php echo esc_attr($location['location_id']); ?>" <?php selected($filter_from_location, $location['location_id']); ?>>
                                <?php echo esc_html($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="filter_to_location" id="filter_to_location" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="0">-- Chọn điểm đến --</option>
                        <?php foreach ($locations as $location) : ?>
                            <option value="<?php echo esc_attr($location['location_id']); ?>" <?php selected($filter_to_location, $location['location_id']); ?>>
                                <?php echo esc_html($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="filter_pickup_location" id="filter_pickup_location" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Tất cả điểm đón --</option>
                        <?php foreach ($pickup_locations as $location) : ?>
                            <option value="<?php echo esc_attr($location); ?>" <?php selected($filter_pickup_location, $location); ?>>
                                <?php echo esc_html($location); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="filter_dropoff_location" id="filter_dropoff_location" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Tất cả điểm trả --</option>
                        <?php foreach ($dropoff_locations as $location) : ?>
                            <option value="<?php echo esc_attr($location); ?>" <?php selected($filter_dropoff_location, $location); ?>>
                                <?php echo esc_html($location); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="filter_driver" id="filter_driver" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="0">-- Chọn tài xế --</option>
                        <?php foreach ($drivers as $driver) : ?>
                            <option value="<?php echo esc_attr($driver['driver_id']); ?>" <?php selected($filter_driver, $driver['driver_id']); ?>>
                                <?php echo esc_html($driver['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="filter_vehicle" id="filter_vehicle" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="0">-- Chọn xe --</option>
                        <?php foreach ($vehicles as $vehicle) : ?>
                            <option value="<?php echo esc_attr($vehicle['vehicle_id']); ?>" <?php selected($filter_vehicle, $vehicle['vehicle_id']); ?>>
                                <?php echo esc_html($vehicle['license_plate']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Lọc</button>
                </div>
            </form>

            <!-- Add Ticket Button -->
            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition mb-6 nhaxe-toggle-form">Thêm Vé</button>

            <!-- Add Ticket Form -->
            <div class="nhaxe-add-form hidden bg-gray-50 p-6 rounded-lg mb-6">
                <form method="post" action="">
                    <input type="hidden" name="nhaxemyduyen_add_ticket" value="1">
                    <?php wp_nonce_field('nhaxemyduyen_ticket_action', 'nhaxemyduyen_ticket_nonce'); ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="trip_id" class="block text-sm font-medium text-gray-700">Chuyến xe</label>
                            <select name="trip_id" id="trip_id" required onchange="updateTripDetails(this)" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Chọn chuyến xe --</option>
                                <?php foreach ($trips as $trip) : ?>
                                    <option value="<?php echo esc_attr($trip['trip_id']); ?>" 
                                            data-pickup="<?php echo esc_attr($trip['pickup_location']); ?>" 
                                            data-dropoff="<?php echo esc_attr($trip['dropoff_location']); ?>"
                                            data-driver="<?php echo esc_attr($trip['driver_name'] ?: 'Chưa chọn'); ?>"
                                            data-vehicle="<?php echo esc_attr($trip['vehicle_plate'] ?: 'Chưa chọn'); ?>"
                                            data-image="<?php echo esc_attr($trip['bus_image'] ?: ''); ?>">
                                        <?php echo esc_html($trip['from_location'] . ' - ' . $trip['to_location'] . ' (' . date('m/d/Y H:i', strtotime($trip['departure_time'])) . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tài xế</label>
                            <span id="trip_driver" class="mt-1 block text-gray-600">Chưa chọn</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phương tiện</label>
                            <span id="trip_vehicle" class="mt-1 block text-gray-600">Chưa chọn</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hình ảnh xe</label>
                            <div class="nhaxe-image-preview mt-1" id="trip_image"></div>
                        </div>
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-gray-700">Tên khách hàng</label>
                            <input type="text" name="customer_name" id="customer_name" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="customer_phone" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                            <input type="text" name="customer_phone" id="customer_phone" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="customer_email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="customer_email" id="customer_email" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="pickup_location" class="block text-sm font-medium text-gray-700">Điểm đón</label>
                            <select name="pickup_location" id="pickup_location" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Chọn điểm đón --</option>
                                <?php foreach ($pickup_locations as $location) : ?>
                                    <option value="<?php echo esc_attr($location); ?>">
                                        <?php echo esc_html($location); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="dropoff_location" class="block text-sm font-medium text-gray-700">Điểm trả</label>
                            <select name="dropoff_location" id="dropoff_location" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Chọn điểm trả --</option>
                                <?php foreach ($dropoff_locations as $location) : ?>
                                    <option value="<?php echo esc_attr($location); ?>">
                                        <?php echo esc_html($location); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="seat_number" class="block text-sm font-medium text-gray-700">Số ghế</label>
                            <input type="text" name="seat_number" id="seat_number" placeholder="Ví dụ: A1" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Trạng thái</label>
                            <select name="status" id="status" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="Đã thanh toán">Đã thanh toán</option>
                                <option value="Chưa thanh toán" selected>Chưa thanh toán</option>
                                <option value="Đã hủy">Đã hủy</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="note" class="block text-sm font-medium text-gray-700">Ghi chú</label>
                            <textarea name="note" id="note" rows="4" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex space-x-4">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Thêm Vé</button>
                        <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition nhaxe-toggle-form">Hủy</button>
                    </div>
                </form>
            </div>

            <!-- Ticket Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã vé</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khách hàng</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số điện thoại</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm đi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm đến</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm đón</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm trả</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tài xế</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phương tiện</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giờ đi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số ghế</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ghi chú</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($tickets as $ticket) : ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($ticket['ticket_code']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($ticket['customer_name']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($ticket['customer_phone']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($ticket['customer_email']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($ticket['from_location']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($ticket['to_location']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($ticket['pickup_location'] ?: $ticket['trip_pickup_location']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($ticket['dropoff_location'] ?: $ticket['trip_dropoff_location']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($ticket['driver_name'] ?: 'Chưa chọn'); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($ticket['vehicle_plate'] ?: 'Chưa chọn'); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html(date('m/d/Y H:i', strtotime($ticket['departure_time']))); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($ticket['seat_number']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <form method="post" action="">
                                        <input type="hidden" name="nhaxemyduyen_ticket_action" value="update">
                                        <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket['ticket_id']); ?>">
                                        <input type="hidden" name="nhaxemyduyen_ticket_nonce" value="<?php echo wp_create_nonce('nhaxemyduyen_ticket_nonce'); ?>">
                                        <select name="status" class="border border-gray-300 rounded-lg px-2 py-1 focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                                            <option value="Đã thanh toán" <?php selected($ticket['status'], 'Đã thanh toán'); ?>>Đã thanh toán</option>
                                            <option value="Chưa thanh toán" <?php selected($ticket['status'], 'Chưa thanh toán'); ?>>Chưa thanh toán</option>
                                            <option value="Đã hủy" <?php selected($ticket['status'], 'Đã hủy'); ?>>Đã hủy</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($ticket['note']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <form method="post" action="" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vé này?');">
                                        <input type="hidden" name="nhaxemyduyen_ticket_action" value="delete">
                                        <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket['ticket_id']); ?>">
                                        <input type="hidden" name="nhaxemyduyen_delete_nonce" value="<?php echo wp_create_nonce('nhaxemyduyen_delete_ticket'); ?>">
                                        <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tickets)) : ?>
                            <tr>
                                <td colspan="15" class="px-4 py-3 text-sm text-gray-500 text-center">Không có vé nào phù hợp với tiêu chí.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        /* Custom styles for specific tweaks */
        .nhaxe-add-form.hidden {
            display: none;
        }
        .nhaxe-image-preview img {
            max-width: 200px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .ui-datepicker {
            z-index: 1000 !important;
        }
        table th, table td {
            white-space: nowrap;
        }
        @media (max-width: 640px) {
            .grid-cols-3 {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            $('#filter_departure_date').datepicker({
                dateFormat: 'mm/dd/yy',
                minDate: 0,
                onSelect: function(dateText) {
                    $(this).val(dateText);
                }
            });

            $('.nhaxe-toggle-form').click(function() {
                $('.nhaxe-add-form').toggleClass('hidden');
            });

            // Cập nhật chi tiết chuyến xe
            window.updateTripDetails = function(element) {
                const selectedOption = $(element).find('option:selected');
                const pickup = selectedOption.data('pickup') || '';
                const dropoff = selectedOption.data('dropoff') || '';
                const driver = selectedOption.data('driver') || 'Chưa chọn';
                const vehicle = selectedOption.data('vehicle') || 'Chưa chọn';
                const image = selectedOption.data('image') || '';

                $('#pickup_location').val(pickup);
                $('#dropoff_location').val(dropoff);
                $('#trip_driver').text(driver);
                $('#trip_vehicle').text(vehicle);
                if (image) {
                    $('#trip_image').html('<img src="' + image + '" alt="Hình ảnh xe" style="max-width: 200px; border-radius: 8px; margin-top: 10px;">');
                } else {
                    $('#trip_image').html('');
                }
            };
        });
    </script>
    <?php
}

// Trang quản lý người dùng
function nhaxemyduyen_manage_users() {
    global $wpdb;
    $table_users = $wpdb->prefix . 'users';

    // Kiểm tra quyền truy cập
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền truy cập trang này.');
    }

    // Đăng ký Tailwind CSS
    wp_enqueue_style('tailwind-css', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    // Xử lý bộ lọc
    $filter_username = isset($_POST['filter_username']) ? sanitize_text_field($_POST['filter_username']) : '';
    $filter_email = isset($_POST['filter_email']) ? sanitize_text_field($_POST['filter_email']) : '';
    $filter_role = isset($_POST['filter_role']) ? sanitize_text_field($_POST['filter_role']) : '';

    $where_conditions = [];
    if (!empty($filter_username)) {
        $where_conditions[] = $wpdb->prepare("user_login LIKE %s", '%' . $filter_username . '%');
    }
    if (!empty($filter_email)) {
        $where_conditions[] = $wpdb->prepare("user_email LIKE %s", '%' . $filter_email . '%');
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = " WHERE " . implode(' AND ', $where_conditions);
    }

    // Lấy danh sách người dùng với bộ lọc SQL
    $users = $wpdb->get_results("SELECT * FROM $table_users $where_clause", ARRAY_A);

    // Lọc theo vai trò (dùng PHP vì vai trò không lưu trực tiếp trong bảng users)
    if (!empty($filter_role)) {
        $filtered_users = [];
        foreach ($users as $user) {
            $user_info = get_userdata($user['ID']);
            if (in_array($filter_role, $user_info->roles)) {
                $filtered_users[] = $user;
            }
        }
        $users = $filtered_users;
    }

    // Xử lý xóa người dùng
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['user_id']) && isset($_GET['nonce']) && wp_verify_nonce($_GET['nonce'], 'nhaxemyduyen_delete_user')) {
        $user_id = intval($_GET['user_id']);
        if ($user_id !== get_current_user_id()) { // Prevent self-deletion
            wp_delete_user($user_id);
            wp_redirect(admin_url('admin.php?page=nhaxemyduyen-users&message=delete_success'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=nhaxemyduyen-users&message=delete_error'));
            exit;
        }
    }

    // Xử lý thông báo
    $message = '';
    if (isset($_GET['message'])) {
        if ($_GET['message'] === 'delete_success') {
            $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>Xóa người dùng thành công!</p></div>';
        } elseif ($_GET['message'] === 'delete_error') {
            $message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: Không thể xóa tài khoản của chính bạn!</p></div>';
        }
    }

    // Lấy tất cả vai trò để hiển thị trong bộ lọc
    $roles = get_editable_roles();

    ?>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Quản lý Người Dùng</h1>
        <?php echo $message; ?>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Danh sách Người Dùng</h2>

            <!-- Filter Form -->
            <form method="post" action="" class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-4 mb-6">
                <input type="text" name="filter_username" id="filter_username" value="<?php echo esc_attr($filter_username); ?>" placeholder="Tên đăng nhập" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <input type="text" name="filter_email" id="filter_email" value="<?php echo esc_attr($filter_email); ?>" placeholder="Email" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <select name="filter_role" id="filter_role" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Chọn vai trò --</option>
                    <?php foreach ($roles as $role_key => $role_data) : ?>
                        <option value="<?php echo esc_attr($role_key); ?>" <?php selected($filter_role, $role_key); ?>>
                            <?php echo esc_html($role_data['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Lọc</button>
            </form>

            <!-- Users Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên đăng nhập</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số điện thoại</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vai trò</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($users)) : ?>
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-sm text-gray-500 text-center">Không tìm thấy người dùng nào.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($users as $user) : ?>
                                <?php 
                                $user_info = get_userdata($user['ID']);
                                $phone_number = get_user_meta($user['ID'], 'phone_number', true);
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($user['ID']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($user['user_login']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($user['user_email']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($phone_number ?: 'Chưa có'); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html(implode(', ', $user_info->roles)); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $user['ID']); ?>" class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition mr-2">Sửa</a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=nhaxemyduyen-users&action=delete&user_id=' . $user['ID']), 'nhaxemyduyen_delete_user', 'nonce'); ?>" 
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa?')" 
                                           class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition">Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        /* Custom styles for specific tweaks */
        table th, table td {
            white-space: nowrap;
        }
        @media (max-width: 640px) {
            .sm\:flex-row {
                flex-direction: column;
            }
            .sm\:space-x-4 {
                space-x: 0;
                space-y: 4px;
            }
        }
    </style>
    <?php
}

// Thêm trường số điện thoại vào trang chỉnh sửa hồ sơ người dùng
add_action('show_user_profile', 'nhaxemyduyen_add_phone_field');
add_action('edit_user_profile', 'nhaxemyduyen_add_phone_field');

function nhaxemyduyen_add_phone_field($user) {
    ?>
    <h3>Thông tin bổ sung</h3>
    <table class="form-table">
        <tr>
            <th><label for="phone_number">Số điện thoại</label></th>
            <td>
                <input type="text" name="phone_number" id="phone_number" value="<?php echo esc_attr(get_user_meta($user->ID, 'phone_number', true)); ?>" class="regular-text">
            </td>
        </tr>
    </table>
    <?php
}

// Lưu số điện thoại khi cập nhật hồ sơ người dùng
add_action('personal_options_update', 'nhaxemyduyen_save_phone_field');
add_action('edit_user_profile_update', 'nhaxemyduyen_save_phone_field');

function nhaxemyduyen_save_phone_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    if (isset($_POST['phone_number'])) {
        update_user_meta($user_id, 'phone_number', sanitize_text_field($_POST['phone_number']));
    }
}

// Trang thống kê
function nhaxemyduyen_stats() {
    global $wpdb;
    $table_tickets = $wpdb->prefix . 'tickets';
    $table_trips = $wpdb->prefix . 'trips';

    // Xử lý lọc theo ngày/tháng
    $filter_type = isset($_POST['filter_type']) ? $_POST['filter_type'] : 'day';
    $filter_value = isset($_POST['filter_value']) ? $_POST['filter_value'] : date('Y-m-d');

    if ($filter_type === 'day') {
        $date_condition = "DATE(t.created_at) = %s";
        $filter_value = date('Y-m-d', strtotime($filter_value));
        $prev_filter_value = date('Y-m-d', strtotime($filter_value . ' -1 day'));
    } else {
        $date_condition = "DATE_FORMAT(t.created_at, '%Y-%m') = %s";
        $filter_value = date('Y-m', strtotime($filter_value));
        $prev_filter_value = date('Y-m', strtotime($filter_value . ' -1 month'));
    }

    // Thống kê doanh thu
    $revenue = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(tr.price)
        FROM $table_tickets t
        JOIN $table_trips tr ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND $date_condition
    ", $filter_value)) ?: 0;

    // Thống kê số vé đã thanh toán
    $ticket_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM $table_tickets t
        WHERE t.status = 'Đã thanh toán' AND $date_condition
    ", $filter_value)) ?: 0;

    // Thống kê số chuyến xe
    $trip_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT tr.trip_id)
        FROM $table_trips tr
        JOIN $table_tickets t ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND $date_condition
    ", $filter_value)) ?: 0;

    // Thống kê tổng số ghế khả dụng (giả sử mỗi chuyến xe có 44 ghế nếu không có cột available_seats)
    $total_seats = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(tr.available_seats)
        FROM $table_trips tr
        JOIN $table_tickets t ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND $date_condition
    ", $filter_value)) ?: ($trip_count * 44); // Fallback to 44 seats per trip if available_seats is unavailable

    // Tính phần trăm vé bán ra
    $ticket_percentage = $total_seats > 0 ? round(($ticket_count / $total_seats) * 100, 2) : 0;

    // Thống kê so sánh với kỳ trước
    $prev_revenue = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(tr.price)
        FROM $table_tickets t
        JOIN $table_trips tr ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND $date_condition
    ", $prev_filter_value)) ?: 0;

    $prev_ticket_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM $table_tickets t
        WHERE t.status = 'Đã thanh toán' AND $date_condition
    ", $prev_filter_value)) ?: 0;

    $prev_trip_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT tr.trip_id)
        FROM $table_trips tr
        JOIN $table_tickets t ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND $date_condition
    ", $prev_filter_value)) ?: 0;

    $prev_total_seats = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(tr.available_seats)
        FROM $table_trips tr
        JOIN $table_tickets t ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND $date_condition
    ", $prev_filter_value)) ?: ($prev_trip_count * 44);

    $prev_ticket_percentage = $prev_total_seats > 0 ? round(($prev_ticket_count / $prev_total_seats) * 100, 2) : 0;

    // Tính phần trăm thay đổi
    $revenue_change = $prev_revenue > 0 ? round((($revenue - $prev_revenue) / $prev_revenue) * 100, 2) : ($revenue > 0 ? 100 : 0);
    $ticket_percentage_change = $prev_ticket_percentage > 0 ? round((($ticket_percentage - $prev_ticket_percentage) / $prev_ticket_percentage) * 100, 2) : ($ticket_percentage > 0 ? 100 : 0);

    // Đăng ký Chart.js
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);

    ?>
    <div class="wrap nhaxe-wrap">
        <h1 class="nhaxe-title">Thống kê</h1>

        <!-- Card chứa form lọc và thống kê -->
        <div class="nhaxe-card">
            <h2>Kết quả Thống kê</h2>

            <!-- Form lọc -->
            <div class="nhaxe-filter-add-container">
                <form method="post" action="" class="nhaxe-filter-form">
                    <div class="nhaxe-filter-group">
                        <select name="filter_type" id="filter_type" onchange="this.form.submit()">
                            <option value="day" <?php selected($filter_type, 'day'); ?>>Theo ngày</option>
                            <option value="month" <?php selected($filter_type, 'month'); ?>>Theo tháng</option>
                        </select>
                        <?php if ($filter_type === 'day') : ?>
                            <input type="date" name="filter_value" id="filter_value" value="<?php echo esc_attr($filter_value); ?>">
                        <?php else : ?>
                            <input type="month" name="filter_value" id="filter_value" value="<?php echo esc_attr($filter_value); ?>">
                        <?php endif; ?>
                        <input type="submit" class="button nhaxe-button-primary" value="Lọc">
                    </div>
                </form>
            </div>

            <!-- Hiển thị thống kê -->
            <div class="nhaxe-stats-container">
                <div class="nhaxe-stats-table">
                    <table class="widefat nhaxe-table">
                        <tr>
                            <th>Doanh thu</th>
                            <td><?php echo esc_html(number_format($revenue, 0, ',', '.')) . ' VNĐ'; ?>
                                <span class="nhaxe-change <?php echo $revenue_change >= 0 ? 'positive' : 'negative'; ?>">
                                    (<?php echo $revenue_change >= 0 ? '+' : ''; ?><?php echo esc_html($revenue_change); ?>% so với <?php echo $filter_type === 'day' ? 'ngày trước' : 'tháng trước'; ?>)
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Số vé đã thanh toán</th>
                            <td><?php echo esc_html($ticket_count); ?></td>
                        </tr>
                        <tr>
                            <th>Tỷ lệ vé bán ra</th>
                            <td><?php echo esc_html($ticket_percentage); ?>% (<?php echo esc_html($ticket_count); ?>/<?php echo esc_html($total_seats); ?> ghế)
                                <span class="nhaxe-change <?php echo $ticket_percentage_change >= 0 ? 'positive' : 'negative'; ?>">
                                    (<?php echo $ticket_percentage_change >= 0 ? '+' : ''; ?><?php echo esc_html($ticket_percentage_change); ?>% so với <?php echo $filter_type === 'day' ? 'ngày trước' : 'tháng trước'; ?>)
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Số chuyến xe</th>
                            <td><?php echo esc_html($trip_count); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Biểu đồ thống kê -->
                <div class="nhaxe-stats-chart">
                    <canvas id="nhaxeRevenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- CSS cho phần trăm thay đổi -->
    <style>
        .nhaxe-change.positive {
            color: green;
            font-size: 0.9em;
            margin-left: 10px;
        }
        .nhaxe-change.negative {
            color: red;
            font-size: 0.9em;
            margin-left: 10px;
        }
    </style>

    <!-- Script để vẽ biểu đồ -->
    <script>
        jQuery(document).ready(function($) {
            var ctx = document.getElementById('nhaxeRevenueChart').getContext('2d');
            var revenueChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php
                        // Tạo nhãn cho biểu đồ dựa trên loại lọc
                        if ($filter_type === 'day') {
                            // Hiển thị dữ liệu theo giờ trong ngày
                            $labels = [];
                            for ($i = 0; $i < 24; $i++) {
                                $labels[] = sprintf("%02d:00", $i);
                            }
                            echo "'" . implode("','", $labels) . "'";
                        } else {
                            // Hiển thị dữ liệu theo ngày trong tháng
                            $days_in_month = date('t', strtotime($filter_value));
                            $labels = [];
                            for ($i = 1; $i <= $days_in_month; $i++) {
                                $labels[] = sprintf("Ngày %d", $i);
                            }
                            echo "'" . implode("','", $labels) . "'";
                        }
                        ?>
                    ],
                    datasets: [
                        {
                            label: 'Doanh thu (VNĐ)',
                            data: [
                                <?php
                                // Tạo dữ liệu doanh thu
                                if ($filter_type === 'day') {
                                    $data = [];
                                    for ($i = 0; $i < 24; $i++) {
                                        $hour_start = sprintf("%s %02d:00:00", $filter_value, $i);
                                        $hour_end = sprintf("%s %02d:59:59", $filter_value, $i);
                                        $revenue = $wpdb->get_var($wpdb->prepare("
                                            SELECT SUM(tr.price)
                                            FROM $table_tickets t
                                            JOIN $table_trips tr ON t.trip_id = tr.trip_id
                                            WHERE t.status = 'Đã thanh toán'
                                            AND t.created_at BETWEEN %s AND %s
                                        ", $hour_start, $hour_end)) ?: 0;
                                        $data[] = $revenue;
                                    }
                                    echo implode(',', $data);
                                } else {
                                    $days_in_month = date('t', strtotime($filter_value));
                                    $data = [];
                                    for ($i = 1; $i <= $days_in_month; $i++) {
                                        $day = sprintf("%s-%02d", $filter_value, $i);
                                        $revenue = $wpdb->get_var($wpdb->prepare("
                                            SELECT SUM(tr.price)
                                            FROM $table_tickets t
                                            JOIN $table_trips tr ON t.trip_id = tr.trip_id
                                            WHERE t.status = 'Đã thanh toán'
                                            AND DATE(t.created_at) = %s
                                        ", $day)) ?: 0;
                                        $data[] = $revenue;
                                    }
                                    echo implode(',', $data);
                                }
                                ?>
                            ],
                            backgroundColor: 'rgba(26, 115, 232, 0.7)',
                            borderColor: 'rgba(26, 115, 232, 1)',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Tỷ lệ vé bán ra (%)',
                            data: [
                                <?php
                                // Tạo dữ liệu tỷ lệ vé bán ra
                                if ($filter_type === 'day') {
                                    $data = [];
                                    for ($i = 0; $i < 24; $i++) {
                                        $hour_start = sprintf("%s %02d:00:00", $filter_value, $i);
                                        $hour_end = sprintf("%s %02d:59:59", $filter_value, $i);
                                        $ticket_count = $wpdb->get_var($wpdb->prepare("
                                            SELECT COUNT(*)
                                            FROM $table_tickets t
                                            WHERE t.status = 'Đã thanh toán'
                                            AND t.created_at BETWEEN %s AND %s
                                        ", $hour_start, $hour_end)) ?: 0;
                                        $total_seats = $wpdb->get_var($wpdb->prepare("
                                            SELECT SUM(tr.available_seats)
                                            FROM $table_trips tr
                                            JOIN $table_tickets t ON t.trip_id = tr.trip_id
                                            WHERE t.status = 'Đã thanh toán'
                                            AND t.created_at BETWEEN %s AND %s
                                        ", $hour_start, $hour_end)) ?: 44; // Fallback to 44 seats
                                        $percentage = $total_seats > 0 ? round(($ticket_count / $total_seats) * 100, 2) : 0;
                                        $data[] = $percentage;
                                    }
                                    echo implode(',', $data);
                                } else {
                                    $days_in_month = date('t', strtotime($filter_value));
                                    $data = [];
                                    for ($i = 1; $i <= $days_in_month; $i++) {
                                        $day = sprintf("%s-%02d", $filter_value, $i);
                                        $ticket_count = $wpdb->get_var($wpdb->prepare("
                                            SELECT COUNT(*)
                                            FROM $table_tickets t
                                            WHERE t.status = 'Đã thanh toán'
                                            AND DATE(t.created_at) = %s
                                        ", $day)) ?: 0;
                                        $total_seats = $wpdb->get_var($wpdb->prepare("
                                            SELECT SUM(tr.available_seats)
                                            FROM $table_trips tr
                                            JOIN $table_tickets t ON t.trip_id = tr.trip_id
                                            WHERE t.status = 'Đã thanh toán'
                                            AND DATE(t.created_at) = %s
                                        ", $day)) ?: 44; // Fallback to 44 seats
                                        $percentage = $total_seats > 0 ? round(($ticket_count / $total_seats) * 100, 2) : 0;
                                        $data[] = $percentage;
                                    }
                                    echo implode(',', $data);
                                }
                                ?>
                            ],
                            type: 'line',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            fill: false,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Doanh thu (VNĐ)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('vi-VN');
                                }
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Tỷ lệ vé bán ra (%)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: '<?php echo $filter_type === 'day' ? 'Giờ trong ngày' : 'Ngày trong tháng'; ?>'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    if (context.dataset.label === 'Doanh thu (VNĐ)') {
                                        return context.dataset.label + ': ' + context.parsed.y.toLocaleString('vi-VN') + ' VNĐ';
                                    } else {
                                        return context.dataset.label + ': ' + context.parsed.y + '%';
                                    }
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
    <?php
}

// trang quan lý tài xế
function nhaxemyduyen_manage_drivers() {
    global $wpdb;
    $table_drivers = $wpdb->prefix . 'drivers';
    $table_locations = $wpdb->prefix . 'locations';

    wp_enqueue_script('jquery');
    wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

    $locations = $wpdb->get_results("SELECT * FROM $table_locations", ARRAY_A);

    // Filters
    $filter_name = isset($_POST['filter_name']) ? sanitize_text_field($_POST['filter_name']) : '';
    $filter_phone = isset($_POST['filter_phone']) ? sanitize_text_field($_POST['filter_phone']) : '';
    $filter_location = isset($_POST['filter_location']) ? intval($_POST['filter_location']) : 0;

    $where_conditions = [];
    if (!empty($filter_name)) {
        $where_conditions[] = $wpdb->prepare("d.name LIKE %s", '%' . $filter_name . '%');
    }
    if (!empty($filter_phone)) {
        $where_conditions[] = $wpdb->prepare("d.phone LIKE %s", '%' . $filter_phone . '%');
    }
    if ($filter_location > 0) {
        $where_conditions[] = $wpdb->prepare("d.location_id = %d", $filter_location);
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = " WHERE " . implode(' AND ', $where_conditions);
    }

    // Handle add driver
    if (isset($_POST['nhaxemyduyen_add_driver'])) {
        if (
            empty($_POST['name']) ||
            empty($_POST['phone']) ||
            empty($_POST['license_number']) ||
            empty($_POST['location_id']) ||
            empty($_POST['status'])
        ) {
            echo '<div class="error"><p>Lỗi: Vui lòng điền đầy đủ các trường bắt buộc!</p></div>';
        } else {
            $driver_data = array(
                'name' => sanitize_text_field($_POST['name']),
                'phone' => sanitize_text_field($_POST['phone']),
                'license_number' => sanitize_text_field($_POST['license_number']),
                'location_id' => intval($_POST['location_id']),
                'status' => sanitize_text_field($_POST['status']),
                'note' => sanitize_textarea_field($_POST['note']),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            );

            $result = $wpdb->insert($table_drivers, $driver_data);
            if ($result === false) {
                echo '<div class="error"><p>Lỗi: Không thể thêm tài xế. ' . esc_html($wpdb->last_error) . '</p></div>';
            } else {
                wp_redirect(admin_url('admin.php?page=nhaxemyduyen-drivers'));
                exit;
            }
        }
    }

    // Handle update status
    if (isset($_POST['nhaxemyduyen_driver_action'])) {
        $driver_id = intval($_POST['driver_id']);
        $status = sanitize_text_field($_POST['status']);
        $result = $wpdb->update($table_drivers, array('status' => $status, 'updated_at' => current_time('mysql')), array('driver_id' => $driver_id));
        if ($result === false) {
            echo '<div class="error"><p>Lỗi: Không thể cập nhật trạng thái. ' . esc_html($wpdb->last_error) . '</p></div>';
        } else {
            wp_redirect(admin_url('admin.php?page=nhaxemyduyen-drivers'));
            exit;
        }
    }

    // Handle delete driver
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['driver_id'])) {
        $driver_id = intval($_GET['driver_id']);
        $result = $wpdb->delete($table_drivers, array('driver_id' => $driver_id));
        if ($result === false) {
            echo '<div class="error"><p>Lỗi: Không thể xóa tài xế. ' . esc_html($wpdb->last_error) . '</p></div>';
        } else {
            wp_redirect(admin_url('admin.php?page=nhaxemyduyen-drivers'));
            exit;
        }
    }

    $drivers = $wpdb->get_results("
        SELECT d.*, l.name as location_name
        FROM $table_drivers d
        LEFT JOIN $table_locations l ON d.location_id = l.location_id
        $where_clause
        ORDER BY d.created_at DESC
    ", ARRAY_A);

    ?>
    <div class="wrap nhaxe-wrap">
        <h1 class="nhaxe-title">Quản lý Tài xế</h1>

        <div class="nhaxe-card">
            <h2>Danh sách Tài xế</h2>

            <div class="nhaxe-filter-add-container">
                <form method="post" action="" class="nhaxe-filter-form">
                    <div class="nhaxe-filter-group">
                        <input type="text" name="filter_name" id="filter_name" value="<?php echo esc_attr($filter_name); ?>" placeholder="Tên tài xế">
                        <input type="text" name="filter_phone" id="filter_phone" value="<?php echo esc_attr($filter_phone); ?>" placeholder="Số điện thoại">
                        <select name="filter_location" id="filter_location">
                            <option value="0">-- Chọn địa điểm --</option>
                            <?php foreach ($locations as $location) : ?>
                                <option value="<?php echo esc_attr($location['location_id']); ?>" <?php selected($filter_location, $location['location_id']); ?>>
                                    <?php echo esc_html($location['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="submit" class="button nhaxe-button-primary" value="Lọc">
                    </div>
                </form>

                <button class="button nhaxe-button-primary nhaxe-toggle-form">Thêm Tài xế</button>
            </div>

            <div class="nhaxe-add-form" style="display: none;">
                <form method="post" action="">
                    <input type="hidden" name="nhaxemyduyen_add_driver" value="1">
                    <table class="form-table nhaxe-form-table">
                        <tr>
                            <th><label for="name">Tên tài xế</label></th>
                            <td><input type="text" name="name" id="name" required></td>
                        </tr>
                        <tr>
                            <th><label for="phone">Số điện thoại</label></th>
                            <td><input type="text" name="phone" id="phone" required></td>
                        </tr>
                        <tr>
                            <th><label for="license_number">Số GPLX</label></th>
                            <td><input type="text" name="license_number" id="license_number" required></td>
                        </tr>
                        <tr>
                            <th><label for="location_id">Địa điểm</label></th>
                            <td>
                                <select name="location_id" id="location_id" required>
                                    <option value="">-- Chọn địa điểm --</option>
                                    <?php foreach ($locations as $location) : ?>
                                        <option value="<?php echo esc_attr($location['location_id']); ?>">
                                            <?php echo esc_html($location['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="status">Trạng thái</label></th>
                            <td>
                                <select name="status" id="status" required>
                                    <option value="Active" selected>Hoạt động</option>
                                    <option value="Inactive">Không hoạt động</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="note">Ghi chú</label></th>
                            <td><textarea name="note" id="note" rows="4"></textarea></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button nhaxe-button-primary" value="Thêm Tài xế">
                        <button type="button" class="button nhaxe-button-cancel nhaxe-toggle-form">Hủy</button>
                    </p>
                </form>
            </div>

            <table class="widefat nhaxe-table">
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Số điện thoại</th>
                        <th>Số GPLX</th>
                        <th>Địa điểm</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($drivers as $driver) : ?>
                        <tr>
                            <td><?php echo esc_html($driver['name']); ?></td>
                            <td><?php echo esc_html($driver['phone']); ?></td>
                            <td><?php echo esc_html($driver['license_number']); ?></td>
                            <td><?php echo esc_html($driver['location_name']); ?></td>
                            <td>
                                <form method="post" action="">
                                    <input type="hidden" name="nhaxemyduyen_driver_action" value="update">
                                    <input type="hidden" name="driver_id" value="<?php echo esc_attr($driver['driver_id']); ?>">
                                    <select name="status" onchange="this.form.submit()" class="nhaxe-select">
                                        <option value="Active" <?php selected($driver['status'], 'Active'); ?>>Hoạt động</option>
                                        <option value="Inactive" <?php selected($driver['status'], 'Inactive'); ?>>Không hoạt động</option>
                                    </select>
                                </form>
                            </td>
                            <td><?php echo esc_html($driver['note']); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=nhaxemyduyen-drivers&action=delete&driver_id=' . $driver['driver_id']); ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa?')" class="nhaxe-button nhaxe-button-delete">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($drivers)) : ?>
                        <tr>
                            <td colspan="7">Không có tài xế nào phù hợp với tiêu chí.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('.nhaxe-toggle-form').click(function() {
                $('.nhaxe-add-form').slideToggle(300);
            });
        });
    </script>
    <?php
}

// Trang quản lý xe
function nhaxemyduyen_manage_vehicles() {
    global $wpdb;
    $table_vehicles = $wpdb->prefix . 'vehicles';
    $table_locations = $wpdb->prefix . 'locations';

    // Đăng ký script WordPress Media Uploader và jQuery
    wp_enqueue_media();
    wp_enqueue_script('jquery');
    wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

    // Kiểm tra quyền truy cập
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền truy cập trang này.');
    }

    // Lấy danh sách địa điểm
    $locations = $wpdb->get_results("SELECT * FROM $table_locations", ARRAY_A);
    if ($wpdb->last_error) {
        echo '<div class="error"><p>Lỗi truy vấn địa điểm: ' . esc_html($wpdb->last_error) . '</p></div>';
        return;
    }

    // Bộ lọc
    $filter_license_plate = isset($_POST['filter_license_plate']) ? sanitize_text_field($_POST['filter_license_plate']) : '';
    $filter_type = isset($_POST['filter_type']) ? sanitize_text_field($_POST['filter_type']) : '';
    $filter_location = isset($_POST['filter_location']) ? intval($_POST['filter_location']) : 0;

    $where_conditions = [];
    if (!empty($filter_license_plate)) {
        $where_conditions[] = $wpdb->prepare("v.license_plate LIKE %s", '%' . $filter_license_plate . '%');
    }
    if (!empty($filter_type)) {
        $where_conditions[] = $wpdb->prepare("v.type LIKE %s", '%' . $filter_type . '%');
    }
    if ($filter_location > 0) {
        $where_conditions[] = $wpdb->prepare("v.location_id = %d", $filter_location);
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = " WHERE " . implode(' AND ', $where_conditions);
    }

    // Xử lý thêm hoặc chỉnh sửa xe
    if (isset($_POST['nhaxemyduyen_vehicle_action']) && isset($_POST['nhaxemyduyen_vehicle_nonce']) && wp_verify_nonce($_POST['nhaxemyduyen_vehicle_nonce'], 'nhaxemyduyen_vehicle_action')) {
        $action = $_POST['nhaxemyduyen_vehicle_action'];

        if (
            empty($_POST['license_plate']) ||
            empty($_POST['type']) ||
            empty($_POST['capacity']) ||
            empty($_POST['location_id']) ||
            empty($_POST['status'])
        ) {
            echo '<div class="error"><p>Lỗi: Vui lòng điền đầy đủ các trường bắt buộc!</p></div>';
        } else {
            $capacity = intval($_POST['capacity']);
            if ($capacity <= 0) {
                echo '<div class="error"><p>Lỗi: Số chỗ phải lớn hơn 0!</p></div>';
            } else {
                $vehicle_data = array(
                    'license_plate' => sanitize_text_field($_POST['license_plate']),
                    'type' => sanitize_text_field($_POST['type']),
                    'capacity' => $capacity,
                    'location_id' => intval($_POST['location_id']),
                    'status' => sanitize_text_field($_POST['status']),
                    'note' => sanitize_textarea_field($_POST['note']),
                    'image' => sanitize_text_field($_POST['image']),
                    'updated_at' => current_time('mysql'),
                );

                if ($action === 'add') {
                    $vehicle_data['created_at'] = current_time('mysql');
                    $result = $wpdb->insert($table_vehicles, $vehicle_data);
                    if ($result === false) {
                        echo '<div class="error"><p>Lỗi: Không thể thêm xe. ' . esc_html($wpdb->last_error) . '</p></div>';
                    } else {
                        echo '<div class="updated"><p>Thêm xe thành công!</p></div>';
                        wp_redirect(admin_url('admin.php?page=nhaxemyduyen-vehicles'));
                        exit;
                    }
                } elseif ($action === 'edit') {
                    $vehicle_id = intval($_POST['vehicle_id']);
                    $result = $wpdb->update($table_vehicles, $vehicle_data, array('vehicle_id' => $vehicle_id));
                    if ($result === false) {
                        echo '<div class="error"><p>Lỗi: Không thể cập nhật xe. ' . esc_html($wpdb->last_error) . '</p></div>';
                    } else {
                        echo '<div class="updated"><p>Cập nhật xe thành công!</p></div>';
                        wp_redirect(admin_url('admin.php?page=nhaxemyduyen-vehicles'));
                        exit;
                    }
                }
            }
        }
    }

    // Xử lý cập nhật trạng thái
    if (isset($_POST['nhaxemyduyen_vehicle_status']) && isset($_POST['nhaxemyduyen_status_nonce']) && wp_verify_nonce($_POST['nhaxemyduyen_status_nonce'], 'nhaxemyduyen_vehicle_status')) {
        $vehicle_id = intval($_POST['vehicle_id']);
        $status = sanitize_text_field($_POST['status']);
        $result = $wpdb->update($table_vehicles, array('status' => $status, 'updated_at' => current_time('mysql')), array('vehicle_id' => $vehicle_id));
        if ($result === false) {
            echo '<div class="error"><p>Lỗi: Không thể cập nhật trạng thái. ' . esc_html($wpdb->last_error) . '</p></div>';
        } else {
            wp_redirect(admin_url('admin.php?page=nhaxemyduyen-vehicles'));
            exit;
        }
    }

    // Xử lý hành động "Sửa" (hiển thị form)
    $vehicle_to_edit = null;
    if (isset($_POST['nhaxemyduyen_edit_vehicle']) && isset($_POST['vehicle_id']) && isset($_POST['nhaxemyduyen_edit_nonce']) && wp_verify_nonce($_POST['nhaxemyduyen_edit_nonce'], 'nhaxemyduyen_edit_vehicle')) {
        $vehicle_id = intval($_POST['vehicle_id']);
        if ($vehicle_id > 0) {
            $vehicle_to_edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_vehicles WHERE vehicle_id = %d", $vehicle_id), ARRAY_A);
            if (!$vehicle_to_edit) {
                echo '<div class="error"><p>Lỗi: Không tìm thấy xe với ID ' . esc_html($vehicle_id) . '!</p></div>';
            }
        } else {
            echo '<div class="error"><p>Lỗi: ID xe không hợp lệ!</p></div>';
        }
    }

    // Xử lý xóa xe
    if (isset($_POST['nhaxemyduyen_delete_vehicle']) && isset($_POST['vehicle_id']) && isset($_POST['nhaxemyduyen_delete_nonce']) && wp_verify_nonce($_POST['nhaxemyduyen_delete_nonce'], 'nhaxemyduyen_delete_vehicle')) {
        $vehicle_id = intval($_POST['vehicle_id']);
        $result = $wpdb->delete($table_vehicles, array('vehicle_id' => $vehicle_id));
        if ($result === false) {
            echo '<div class="error"><p>Lỗi: Không thể xóa xe. ' . esc_html($wpdb->last_error) . '</p></div>';
        } else {
            echo '<div class="updated"><p>Xóa xe thành công!</p></div>';
            wp_redirect(admin_url('admin.php?page=nhaxemyduyen-vehicles'));
            exit;
        }
    }

    // Lấy danh sách xe
    $vehicles = $wpdb->get_results("
        SELECT v.*, l.name as location_name
        FROM $table_vehicles v
        LEFT JOIN $table_locations l ON v.location_id = l.location_id
        $where_clause
        ORDER BY v.created_at DESC
    ", ARRAY_A);
    if ($wpdb->last_error) {
        echo '<div class="error"><p>Lỗi truy vấn danh sách xe: ' . esc_html($wpdb->last_error) . '</p></div>';
        return;
    }

    ?>
    <div class="wrap nhaxe-wrap">
        <h1 class="nhaxe-title">Quản lý Xe</h1>

        <div class="nhaxe-card">
            <h2>Danh sách Xe</h2>

            <div class="nhaxe-filter-add-container">
                <form method="post" action="" class="nhaxe-filter-form">
                    <div class="nhaxe-filter-group">
                        <input type="text" name="filter_license_plate" id="filter_license_plate" value="<?php echo esc_attr($filter_license_plate); ?>" placeholder="Biển số xe">
                        <input type="text" name="filter_type" id="filter_type" value="<?php echo esc_attr($filter_type); ?>" placeholder="Loại xe">
                        <select name="filter_location" id="filter_location">
                            <option value="0">-- Chọn địa điểm --</option>
                            <?php foreach ($locations as $location) : ?>
                                <option value="<?php echo esc_attr($location['location_id']); ?>" <?php selected($filter_location, $location['location_id']); ?>>
                                    <?php echo esc_html($location['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="submit" class="button nhaxe-button-primary" value="Lọc">
                    </div>
                </form>

                <button class="button nhaxe-button-primary nhaxe-toggle-form">Thêm Xe</button>
            </div>

            <!-- Form thêm hoặc chỉnh sửa xe -->
            <div class="nhaxe-add-form" style="display: <?php echo $vehicle_to_edit ? 'block' : 'none'; ?>;">
                <form method="post" action="">
                    <?php wp_nonce_field('nhaxemyduyen_vehicle_action', 'nhaxemyduyen_vehicle_nonce'); ?>
                    <input type="hidden" name="nhaxemyduyen_vehicle_action" value="<?php echo $vehicle_to_edit ? 'edit' : 'add'; ?>">
                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicle_to_edit ? esc_attr($vehicle_to_edit['vehicle_id']) : ''; ?>">
                    <table class="form-table nhaxe-form-table">
                        <tr>
                            <th><label for="license_plate">Biển số xe</label></th>
                            <td><input type="text" name="license_plate" id="license_plate" value="<?php echo $vehicle_to_edit ? esc_attr($vehicle_to_edit['license_plate']) : ''; ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="type">Loại xe</label></th>
                            <td><input type="text" name="type" id="type" value="<?php echo $vehicle_to_edit ? esc_attr($vehicle_to_edit['type']) : ''; ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="capacity">Số chỗ</label></th>
                            <td><input type="number" name="capacity" id="capacity" min="1" value="<?php echo $vehicle_to_edit ? esc_attr($vehicle_to_edit['capacity']) : ''; ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="location_id">Địa điểm</label></th>
                            <td>
                                <select name="location_id" id="location_id" required>
                                    <option value="">-- Chọn địa điểm --</option>
                                    <?php foreach ($locations as $location) : ?>
                                        <option value="<?php echo esc_attr($location['location_id']); ?>" <?php selected($vehicle_to_edit && $vehicle_to_edit['location_id'] == $location['location_id']); ?>>
                                            <?php echo esc_html($location['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="status">Trạng thái</label></th>
                            <td>
                                <select name="status" id="status" required>
                                    <option value="Active" <?php selected($vehicle_to_edit && $vehicle_to_edit['status'] == 'Active'); ?>>Hoạt động</option>
                                    <option value="Inactive" <?php selected($vehicle_to_edit && $vehicle_to_edit['status'] == 'Inactive'); ?>>Không hoạt động</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="image">Hình ảnh xe</label></th>
                            <td>
                                <input type="text" name="image" id="image" value="<?php echo $vehicle_to_edit ? esc_attr($vehicle_to_edit['image']) : ''; ?>">
                                <input type="button" class="button nhaxe-upload-button" value="Chọn hình ảnh">
                                <div class="nhaxe-image-preview">
                                    <?php if ($vehicle_to_edit && $vehicle_to_edit['image']) : ?>
                                        <img src="<?php echo esc_url($vehicle_to_edit['image']); ?>" alt="Hình ảnh xe" style="max-width: 200px; margin-top: 10px;">
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="note">Ghi chú</label></th>
                            <td><textarea name="note" id="note" rows="4"><?php echo $vehicle_to_edit ? esc_html($vehicle_to_edit['note']) : ''; ?></textarea></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button nhaxe-button-primary" value="<?php echo $vehicle_to_edit ? 'Cập nhật Xe' : 'Thêm Xe'; ?>">
                        <button type="button" class="button nhaxe-button-cancel nhaxe-toggle-form">Hủy</button>
                    </p>
                </form>
            </div>

            <!-- Danh sách xe -->
            <table class="widefat nhaxe-table">
                <thead>
                    <tr>
                        <th>Biển số xe</th>
                        <th>Loại xe</th>
                        <th>Số chỗ</th>
                        <th>Địa điểm</th>
                        <th>Hình ảnh xe</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $vehicle) : ?>
                        <tr>
                            <td><?php echo esc_html($vehicle['license_plate']); ?></td>
                            <td><?php echo esc_html($vehicle['type']); ?></td>
                            <td><?php echo esc_html($vehicle['capacity']); ?></td>
                            <td><?php echo esc_html($vehicle['location_name'] ?: 'Chưa có'); ?></td>
                            <td>
                                <?php if ($vehicle['image']) : ?>
                                    <img src="<?php echo esc_url($vehicle['image']); ?>" alt="Hình ảnh xe" style="max-width: 100px;">
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="">
                                    <?php wp_nonce_field('nhaxemyduyen_vehicle_status', 'nhaxemyduyen_status_nonce'); ?>
                                    <input type="hidden" name="nhaxemyduyen_vehicle_status" value="update">
                                    <input type="hidden" name="vehicle_id" value="<?php echo esc_attr($vehicle['vehicle_id']); ?>">
                                    <select name="status" onchange="this.form.submit()" class="nhaxe-select">
                                        <option value="Active" <?php selected($vehicle['status'], 'Active'); ?>>Hoạt động</option>
                                        <option value="Inactive" <?php selected($vehicle['status'], 'Inactive'); ?>>Không hoạt động</option>
                                    </select>
                                </form>
                            </td>
                            <td><?php echo esc_html($vehicle['note']); ?></td>
                            <td>
                                <!-- Form cho hành động Sửa -->
                                <form method="post" action="" style="display:inline;">
                                    <?php wp_nonce_field('nhaxemyduyen_edit_vehicle', 'nhaxemyduyen_edit_nonce'); ?>
                                    <input type="hidden" name="nhaxemyduyen_edit_vehicle" value="edit">
                                    <input type="hidden" name="vehicle_id" value="<?php echo esc_attr($vehicle['vehicle_id']); ?>">
                                    <button type="submit" class="nhaxe-button nhaxe-button-edit">Sửa</button>
                                </form>
                                <!-- Form cho hành động Xóa -->
                                <form method="post" action="" style="display:inline;">
                                    <?php wp_nonce_field('nhaxemyduyen_delete_vehicle', 'nhaxemyduyen_delete_nonce'); ?>
                                    <input type="hidden" name="nhaxemyduyen_delete_vehicle" value="delete">
                                    <input type="hidden" name="vehicle_id" value="<?php echo esc_attr($vehicle['vehicle_id']); ?>">
                                    <button type="submit" class="nhaxe-button nhaxe-button-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($vehicles)) : ?>
                        <tr>
                            <td colspan="8">Không có xe nào phù hợp với tiêu chí.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            // Script để hiển thị/ẩn form thêm
            $('.nhaxe-toggle-form').click(function() {
                $('.nhaxe-add-form').slideToggle(300);
            });

            // Script cho Media Uploader
            $('.nhaxe-upload-button').click(function(e) {
                e.preventDefault();
                var image = wp.media({
                    title: 'Chọn hình ảnh xe',
                    multiple: false
                }).open().on('select', function() {
                    var uploaded_image = image.state().get('selection').first();
                    var image_url = uploaded_image.toJSON().url;
                    $('#image').val(image_url);
                    $('.nhaxe-image-preview').html('<img src="' + image_url + '" alt="Hình ảnh xe" style="max-width: 200px; margin-top: 10px;">');
                });
            });
        });
    </script>
    <?php
}

// Đăng ký style cho giao diện admin
add_action('admin_enqueue_scripts', 'nhaxemyduyen_admin_styles');

function nhaxemyduyen_admin_styles($hook) {
    // Chỉ tải style trên các trang của plugin
    if (strpos($hook, 'nhaxemyduyen') === false) {
        return;
    }

    wp_enqueue_style('nhaxemyduyen-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css', array(), '2.0');

    ?>
    <style>
        .nhaxe-wrap {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .nhaxe-title {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .nhaxe-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .nhaxe-card h2 {
            font-size: 20px;
            font-weight: 600;
            color: #34495e;
            margin: 0 0 20px;
        }

        .nhaxe-filter-add-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }

        .nhaxe-filter-form {
            flex: 1;
        }

        .nhaxe-filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nhaxe-filter-group input,
        .nhaxe-filter-group select {
            padding: 8px;
            border: 1px solid #dfe6e9;
            border-radius: 4px;
            font-size: 14px;
            max-width: 200px;
        }

        .nhaxe-add-form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #dfe6e9;
        }

        .nhaxe-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .nhaxe-table th,
        .nhaxe-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dfe6e9;
        }

        .nhaxe-table th {
            background-color: #34495e;
            color: #fff;
            font-weight: 600;
        }

        .nhaxe-table tr:hover {
            background-color: #f5f5f5;
        }

        .nhaxe-form-table th {
            width: 200px;
            font-weight: 500;
            color: #2c3e50;
        }

        .nhaxe-form-table input,
        .nhaxe-form-table select {
            width: 100%;
            max-width: 400px;
            padding: 8px;
            border: 1px solid #dfe6e9;
            border-radius: 4px;
        }

        .nhaxe-select {
            padding: 6px;
            border: 1px solid #dfe6e9;
            border-radius: 4px;
        }

        .nhaxe-button {
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: all 0.2s;
        }

        .nhaxe-button-primary {
            background-color: #1a73e8;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        .nhaxe-button-primary:hover {
            background-color: #1565c0;
            transform: translateY(-1px);
        }

        .nhaxe-button-edit {
            background-color: #2ecc71;
            color: #fff;
        }

        .nhaxe-button-edit:hover {
            background-color: #27ae60;
        }

        .nhaxe-button-delete {
            background-color: #e74c3c;
            color: #fff;
        }

        .nhaxe-button-delete:hover {
            background-color: #c0392b;
        }

        .nhaxe-button-cancel {
            background-color: #7f8c8d;
            color: #fff;
        }

        .nhaxe-button-cancel:hover {
            background-color: #6c757d;
        }

        .nhaxe-toggle-form {
            padding: 10px 20px;
            font-size: 14px;
        }

        .nhaxe-image-preview img {
            border-radius: 6px;
            border: 1px solid #dfe6e9;
        }

        .nhaxe-stats-container {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .nhaxe-stats-table {
            flex: 1;
            min-width: 300px;
        }

        .nhaxe-stats-table .nhaxe-table th {
            width: 150px;
            background-color: #34495e;
        }

        .nhaxe-stats-table .nhaxe-table td {
            font-weight: 500;
            color: #2c3e50;
        }

        .nhaxe-stats-chart {
            flex: 1;
            min-width: 300px;
            max-width: 500px;
        }

        @media (max-width: 768px) {
            .nhaxe-wrap {
                padding: 15px;
            }

            .nhaxe-title {
                font-size: 24px;
            }

            .nhaxe-filter-add-container {
                flex-direction: column;
                align-items: stretch;
            }

            .nhaxe-filter-group {
                flex-direction: column;
                gap: 8px;
            }

            .nhaxe-filter-group input,
            .nhaxe-filter-group select {
                width: 100%;
            }

            .nhaxe-form-table th {
                width: 100%;
                display: block;
                padding: 5px 0;
            }

            .nhaxe-form-table td {
                display: block;
                width: 100%;
            }

            .nhaxe-form-table input,
            .nhaxe-form-table select {
                max-width: 100%;
            }

            .nhaxe-stats-container {
                flex-direction: column;
            }
        }

        .error p {
            background-color: #fef0f0;
            color: #d32f2f;
            padding: 10px 15px;
            border-radius: 6px;
            border: 1px solid #ef9a9a;
            font-size: 14px;
        }
    </style>
    <?php
}
    require_once plugin_dir_path(__FILE__) . 'inc/location-api.php';
    require_once plugin_dir_path(__FILE__) . 'inc/route-api.php';
    require_once plugin_dir_path(__FILE__) . 'inc/trip-api.php';
    require_once plugin_dir_path(__FILE__) . 'inc/ticket-api.php';
    
?>