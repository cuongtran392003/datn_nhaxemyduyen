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

/**
 * Đăng ký endpoint AJAX cho hành động địa điểm
 */
add_action('wp_ajax_nhaxemyduyen_location_action', 'nhaxemyduyen_handle_location_action');
function nhaxemyduyen_handle_location_action() {
    global $wpdb;
    $table_locations = $wpdb->prefix . 'locations';
    $table_trips = $wpdb->prefix . 'trips';

    // Kiểm tra quyền
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
    }

    // Kiểm tra nonce
    if (!check_ajax_referer('nhaxemyduyen_location_action', 'nonce', false)) {
        error_log("nhaxemyduyen_handle_location_action: Nonce verification failed");
        wp_send_json_error(['message' => 'Xác thực không hợp lệ.'], 403);
    }

    $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';

    if (!in_array($action, ['add', 'edit', 'delete'])) {
        wp_send_json_error(['message' => 'Hành động không hợp lệ.'], 400);
    }

    if ($action === 'delete') {
        $location_id = intval($_POST['location_id']);
        if ($location_id <= 0) {
            wp_send_json_error(['message' => 'ID địa điểm không hợp lệ.'], 400);
        }

        $trip_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_trips WHERE from_location_id = %d OR to_location_id = %d",
            $location_id, $location_id
        ));

        error_log("nhaxemyduyen_handle_location_action: Attempting to delete location_id=$location_id, trip_count=$trip_count");

        if ($trip_count > 0) {
            wp_send_json_error(['message' => 'Không thể xóa địa điểm vì đang được sử dụng trong các chuyến xe!'], 400);
        }

        $wpdb->query('SET FOREIGN_KEY_CHECKS = 0');
        $result = $wpdb->delete($table_locations, ['location_id' => $location_id]);
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');

        if ($result === false) {
            error_log("nhaxemyduyen_handle_location_action: Delete failed, error: " . $wpdb->last_error);
            wp_send_json_error(['message' => 'Lỗi: Không thể xóa địa điểm. ' . $wpdb->last_error], 500);
        }

        wp_send_json_success(['message' => 'Xóa địa điểm thành công!']);
    } else {
        // Xử lý thêm/sửa
        if (!isset($_POST['name']) || empty(trim($_POST['name']))) {
            wp_send_json_error(['message' => 'Tên địa điểm không được để trống.'], 400);
        }

        $location_data = [
            'name' => sanitize_text_field($_POST['name']),
            'updated_at' => current_time('mysql'),
        ];

        if ($action === 'add') {
            $location_data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($table_locations, $location_data);
            if ($result === false) {
                error_log("nhaxemyduyen_handle_location_action: Insert failed, error: " . $wpdb->last_error);
                wp_send_json_error(['message' => 'Lỗi: Không thể thêm địa điểm. ' . $wpdb->last_error], 500);
            }
            wp_send_json_success([
                'message' => 'Thêm địa điểm thành công!',
                'location' => [
                    'location_id' => $wpdb->insert_id,
                    'name' => $location_data['name'],
                    'created_at' => $location_data['created_at'],
                    'updated_at' => $location_data['updated_at'],
                ]
            ]);
        } elseif ($action === 'edit') {
            $location_id = intval($_POST['location_id']);
            if ($location_id <= 0) {
                wp_send_json_error(['message' => 'ID địa điểm không hợp lệ.'], 400);
            }
            $result = $wpdb->update($table_locations, $location_data, ['location_id' => $location_id]);
            if ($result === false) {
                error_log("nhaxemyduyen_handle_location_action: Update failed, error: " . $wpdb->last_error);
                wp_send_json_error(['message' => 'Lỗi: Không thể cập nhật địa điểm. ' . $wpdb->last_error], 500);
            }
            $created_at = $wpdb->get_var($wpdb->prepare(
                "SELECT created_at FROM $table_locations WHERE location_id = %d",
                $location_id
            ));
            wp_send_json_success([
                'message' => 'Cập nhật địa điểm thành công!',
                'location' => [
                    'location_id' => $location_id,
                    'name' => $location_data['name'],
                    'created_at' => $created_at,
                    'updated_at' => $location_data['updated_at'],
                ]
            ]);
        }
    }
}

/**
 * Đăng ký endpoint AJAX để lấy danh sách địa điểm
 */
add_action('wp_ajax_nhaxemyduyen_get_locations', 'nhaxemyduyen_get_locations');
function nhaxemyduyen_get_locations() {
    global $wpdb;
    $table_locations = $wpdb->prefix . 'locations';

    // Kiểm tra quyền
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Bạn không có quyền truy cập danh sách địa điểm.'], 403);
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

    // Lấy danh sách địa điểm
    $locations = $wpdb->get_results("SELECT * FROM $table_locations $where_clause ORDER BY created_at DESC", ARRAY_A);
    if ($locations === null) {
        error_log("nhaxemyduyen_get_locations: Query failed, error: " . $wpdb->last_error);
        wp_send_json_error(['message' => 'Lỗi: Không thể lấy danh sách địa điểm. ' . $wpdb->last_error], 500);
    }

    wp_send_json_success(['locations' => $locations]);
}

/**
 * Trang quản lý địa điểm
 */
function nhaxemyduyen_manage_locations() {
    global $wpdb;
    $table_locations = $wpdb->prefix . 'locations';
    $table_trips = $wpdb->prefix . 'trips';

    // Kiểm tra quyền truy cập
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền truy cập trang này.');
    }

    // Xử lý bộ lọc tìm kiếm
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

    // Lấy danh sách địa điểm
    $locations = $wpdb->get_results("SELECT * FROM $table_locations $where_clause ORDER BY created_at DESC", ARRAY_A);

    // Xử lý chỉnh sửa địa điểm
    $location_to_edit = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['location_id'])) {
        $location_id = intval($_GET['location_id']);
        $location_to_edit = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_locations WHERE location_id = %d",
            $location_id
        ), ARRAY_A);
    }

    // Hiển thị thông báo từ query string (cho lần tải đầu tiên)
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

    // Đăng ký jQuery và Tailwind CSS
    wp_enqueue_script('jquery');
    wp_enqueue_style('tailwind-css', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    // Lấy URL AJAX và nonce
    $ajax_url = admin_url('admin-ajax.php');
    error_log("nhaxemyduyen_manage_locations: ajax_url = $ajax_url");
    $ajax_nonce = wp_create_nonce('nhaxemyduyen_location_action');

    ?>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Quản lý Địa Điểm</h1>
        <div id="nhaxe-message" class="mb-6"><?php echo $message; ?></div>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Danh sách Địa Điểm</h2>

            <!-- Filter Form and Add Button -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <!-- Filter Form -->
                <form id="nhaxe-filter-form" method="post" action="" class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <input type="text" name="filter_name" id="filter_name" value="<?php echo esc_attr($filter_name); ?>" placeholder="Tên địa điểm" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <input type="date" name="filter_date" id="filter_date" value="<?php echo esc_attr($filter_date); ?>" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Tìm kiếm</button>
                </form>

                <!-- Add Location Button -->
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition nhaxe-toggle-form mt-4 sm:mt-0" data-action="add">Thêm Địa Điểm</button>
            </div>

            <!-- Add/Edit Location Form -->
            <div id="nhaxe-add-form" class="nhaxe-add-form hidden bg-gray-50 p-6 rounded-lg mb-6">
                <form id="nhaxe-location-form">
                    <input type="hidden" name="action" value="nhaxemyduyen_location_action">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr($ajax_nonce); ?>">
                    <input type="hidden" name="action_type" id="action-type" value="<?php echo $location_to_edit ? 'edit' : 'add'; ?>">
                    <input type="hidden" name="location_id" id="location-id" value="<?php echo $location_to_edit ? esc_attr($location_to_edit['location_id']) : ''; ?>">
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
                <table id="nhaxe-locations-table" class="min-w-full bg-white border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên địa điểm</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian tạo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian cập nhật</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="nhaxe-locations-body">
                        <?php if (empty($locations)) : ?>
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-sm text-gray-500 text-center">Không có địa điểm nào phù hợp.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($locations as $location) : ?>
                                <tr class="hover:bg-gray-50" data-location-id="<?php echo esc_attr($location['location_id']); ?>">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($location['location_id']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($location['name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($location['created_at']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($location['updated_at']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <button class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition mr-2 nhaxe-edit-location" data-location-id="<?php echo esc_attr($location['location_id']); ?>" data-name="<?php echo esc_attr($location['name']); ?>">Sửa</button>
                                        <button class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition nhaxe-delete-location" data-location-id="<?php echo esc_attr($location['location_id']); ?>">Xóa</button>
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
            const ajaxUrl = '<?php echo esc_js($ajax_url); ?>';
            const ajaxNonce = '<?php echo esc_js($ajax_nonce); ?>';
            console.log('AJAX URL:', ajaxUrl);
            console.log('Nonce:', ajaxNonce);

            // Toggle form
            $('.nhaxe-toggle-form').click(function() {
                $('#nhaxe-add-form').toggleClass('hidden');
                if (!$('#nhaxe-add-form').hasClass('hidden')) {
                    $('#action-type').val('add');
                    $('#location-id').val('');
                    $('#name').val('');
                    $('#nhaxe-location-form button[type="submit"]').text('Thêm Địa Điểm');
                }
            });

            // Submit form (add/edit)
            $('#nhaxe-location-form').submit(function(e) {
                e.preventDefault();
                const formData = $(this).serializeArray();
                const data = {
                    action: 'nhaxemyduyen_location_action',
                    nonce: ajaxNonce,
                };
                formData.forEach(item => data[item.name] = item.value);

                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data.message, 'success');
                            $('#nhaxe-add-form').addClass('hidden');
                            $('#nhaxe-location-form')[0].reset();
                            $('#action-type').val('add');
                            refreshLocations();
                        } else {
                            showMessage(response.data.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Lỗi AJAX:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            error: error
                        });
                        let errorMessage = 'Đã xảy ra lỗi. Vui lòng thử lại.';
                        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                            errorMessage = xhr.responseJSON.data.message;
                        } else if (xhr.status === 404) {
                            errorMessage = 'Không tìm thấy endpoint AJAX. Vui lòng kiểm tra cấu hình server.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Lỗi server. Vui lòng kiểm tra nhật ký lỗi.';
                        }
                        showMessage(errorMessage, 'error');
                    }
                });
            });

            // Edit location
            $(document).on('click', '.nhaxe-edit-location', function() {
                const locationId = $(this).data('location-id');
                const name = $(this).data('name');
                $('#action-type').val('edit');
                $('#location-id').val(locationId);
                $('#name').val(name);
                $('#nhaxe-location-form button[type="submit"]').text('Cập nhật Địa Điểm');
                $('#nhaxe-add-form').removeClass('hidden');
            });

            // Delete location
            $(document).on('click', '.nhaxe-delete-location', function() {
                if (!confirm('Bạn có chắc chắn muốn xóa địa điểm này?')) return;
                const locationId = $(this).data('location-id');
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'nhaxemyduyen_location_action',
                        nonce: ajaxNonce,
                        action_type: 'delete',
                        location_id: locationId
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data.message, 'success');
                            refreshLocations();
                        } else {
                            showMessage(response.data.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Lỗi AJAX:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            error: error
                        });
                        let errorMessage = 'Đã xảy ra lỗi. Vui lòng thử lại.';
                        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                            errorMessage = xhr.responseJSON.data.message;
                        } else if (xhr.status === 404) {
                            errorMessage = 'Không tìm thấy endpoint AJAX. Vui lòng kiểm tra cấu hình server.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Lỗi server. Vui lòng kiểm tra nhật ký lỗi.';
                        }
                        showMessage(errorMessage, 'error');
                    }
                });
            });

            // Refresh locations
            function refreshLocations() {
                const filterName = $('#filter_name').val();
                const filterDate = $('#filter_date').val();
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'nhaxemyduyen_get_locations',
                        nonce: ajaxNonce,
                        filter_name: filterName,
                        filter_date: filterDate
                    },
                    success: function(response) {
                        if (response.success) {
                            const tbody = $('#nhaxe-locations-body');
                            tbody.empty();
                            if (response.data.locations.length === 0) {
                                tbody.html('<tr><td colspan="5" class="px-4 py-3 text-sm text-gray-500 text-center">Không có địa điểm nào phù hợp.</td></tr>');
                            } else {
                                response.data.locations.forEach(location => {
                                    const row = `
                                        <tr class="hover:bg-gray-50" data-location-id="${location.location_id}">
                                            <td class="px-4 py-3 text-sm text-gray-900">${location.location_id}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">${location.name}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">${location.created_at}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">${location.updated_at}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                <button class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition mr-2 nhaxe-edit-location" data-location-id="${location.location_id}" data-name="${location.name}">Sửa</button>
                                                <button class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition nhaxe-delete-location" data-location-id="${location.location_id}">Xóa</button>
                                            </td>
                                        </tr>`;
                                    tbody.append(row);
                                });
                            }
                        } else {
                            showMessage(response.data.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Lỗi AJAX:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            error: error
                        });
                        let errorMessage = 'Không thể tải danh sách địa điểm. Vui lòng thử lại.';
                        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                            errorMessage = xhr.responseJSON.data.message;
                        } else if (xhr.status === 404) {
                            errorMessage = 'Không tìm thấy endpoint AJAX. Vui lòng kiểm tra cấu hình server.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Lỗi server. Vui lòng kiểm tra nhật ký lỗi.';
                        }
                        showMessage(errorMessage, 'error');
                    }
                });
            }

            // Show message
            function showMessage(message, type) {
                const messageDiv = $('#nhaxe-message');
                const className = type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700';
                messageDiv.html(`<div class="${className} p-4 rounded-lg"><p>${message}</p></div>`);
                setTimeout(() => messageDiv.empty(), 5000);
            }
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

    // Xử lý bộ Tìm kiếm
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

    // Xử lý xóa tuyến đường
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && 
        isset($_GET['route_id']) && isset($_GET['nonce']) && 
        wp_verify_nonce($_GET['nonce'], 'nhaxemyduyen_delete_route')) {
        $route_id = intval($_GET['route_id']);
        
        // Xóa tất cả chuyến xe liên quan đến tuyến đường
        $wpdb->delete($table_trips, array('route_id' => $route_id));
        
        // Xóa ảnh của tuyến đường
        $bus_image = $wpdb->get_var($wpdb->prepare("SELECT bus_image FROM $table_routes WHERE route_id = %d", $route_id));
        if (!empty($bus_image)) {
            $upload_dir = wp_upload_dir();
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $bus_image);
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Xóa tuyến đường
        $result = $wpdb->delete($table_routes, array('route_id' => $route_id));
        if ($result === false) {
            echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: Không thể xóa tuyến đường. ' . esc_html($wpdb->last_error) . '</p></div>';
        } else {
            // Giữ lại các tham số tìm kiếm
            $redirect_params = array(
                'page' => 'nhaxemyduyen-routes',
                'message' => 'delete_success',
            );
            if ($filter_from_location > 0) {
                $redirect_params['filter_from_location'] = $filter_from_location;
            }
            if ($filter_to_location > 0) {
                $redirect_params['filter_to_location'] = $filter_to_location;
            }
            if (!empty($filter_price_min)) {
                $redirect_params['filter_price_min'] = $filter_price_min;
            }
            if (!empty($filter_price_max)) {
                $redirect_params['filter_price_max'] = $filter_price_max;
            }
            $redirect_url = add_query_arg($redirect_params, admin_url('admin.php'));
            error_log('Redirecting to: ' . $redirect_url); // Debug
            wp_redirect($redirect_url);
            exit;
        }
    }

    // Lấy danh sách tuyến đường với bộ Tìm kiếm
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
    wp_enqueue_script('jquery', false, [], false, true);
    wp_enqueue_style('tailwind-css', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    ?>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Quản lý Tuyến Đường</h1>
        <div id="message" class="mb-6"><?php echo $message; ?></div>

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
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Tìm kiếm</button>
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
                                        <button class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition mr-2 nhaxe-toggle-form" 
                                                data-action="edit" 
                                                data-route-id="<?php echo esc_attr($route['route_id']); ?>" 
                                                data-from-location-id="<?php echo esc_attr($route['from_location_id']); ?>" 
                                                data-to-location-id="<?php echo esc_attr($route['to_location_id']); ?>" 
                                                data-price="<?php echo esc_attr($route['price']); ?>" 
                                                data-distance="<?php echo esc_attr($route['distance']); ?>" 
                                                data-duration="<?php echo esc_attr(format_duration_to_hhmm($route['duration'])); ?>" 
                                                data-bus-image="<?php echo esc_attr($route['bus_image']); ?>">Sửa</button>
                                        <button class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition nhaxe-delete-route" 
                                                data-route-id="<?php echo esc_attr($route['route_id']); ?>" 
                                                data-nonce="<?php echo wp_create_nonce('nhaxemyduyen_delete_route'); ?>">Xóa</button>
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
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded');
            document.getElementById('message').innerHTML = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: jQuery không được tải.</p></div>';
        }

        jQuery(document).ready(function($) {
            // Xử lý sự kiện click cho nút Thêm/Sửa
            $(document).on('click', '.nhaxe-toggle-form', function(e) {
                e.preventDefault();
                try {
                    console.log('Toggle form clicked, action:', $(this).data('action')); // Debug
                    const action = $(this).data('action');
                    const form = $('.nhaxe-add-form');

                    // Ensure form exists
                    if (!form.length) {
                        console.error('Form .nhaxe-add-form not found');
                        $('#message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: Không tìm thấy form chỉnh sửa.</p></div>');
                        return;
                    }

                    // Toggle form visibility
                    form.toggleClass('hidden');

                    // Reset form when adding new
                    $('#route_action').val('add');
                    $('#route_id').val('');
                    $('#from_location_id').val('');
                    $('#to_location_id').val('');
                    $('#price').val('');
                    $('#distance').val('');
                    $('#duration').val('');
                    $('#bus_image').val('');
                    $('#bus_image_preview').addClass('hidden').attr('src', '');
                    $('#submit_button').text('Thêm Tuyến Đường');

                    // Populate form for edit
                    if (action === 'edit') {
                        const routeId = $(this).data('route-id');
                        const fromLocationId = $(this).data('from-location-id');
                        const toLocationId = $(this).data('to-location-id');
                        const price = $(this).data('price');
                        const distance = $(this).data('distance');
                        const duration = $(this).data('duration');
                        const busImage = $(this).data('bus-image');

                        // Debug data
                        console.log('Edit route data:', {
                            routeId, fromLocationId, toLocationId, price, distance, duration, busImage
                        });

                        // Validate data
                        if (!routeId) {
                            console.error('Invalid route ID');
                            $('#message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: ID tuyến đường không hợp lệ.</p></div>');
                            form.addClass('hidden');
                            return;
                        }

                        $('#route_action').val('edit');
                        $('#route_id').val(routeId);
                        $('#from_location_id').val(fromLocationId);
                        $('#to_location_id').val(toLocationId);
                        $('#price').val(price);
                        $('#distance').val(distance);
                        $('#duration').val(duration);
                        if (busImage) {
                            $('#bus_image_preview').removeClass('hidden').attr('src', busImage);
                        } else {
                            $('#bus_image_preview').addClass('hidden').attr('src', '');
                        }
                        $('#submit_button').text('Cập nhật Tuyến Đường');
                    }
                } catch (err) {
                    console.error('Error in toggle form:', err);
                    $('#message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi: ' + err.message + '</p></div>');
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

            // Xử lý submit form qua AJAX
            $('.nhaxe-add-form form').on('submit', function(e) {
                e.preventDefault();
                console.log('Form submitted'); // Debug
                var formData = new FormData(this);
                formData.append('action', 'nhaxemyduyen_manage_route');
                console.log('Form data:', Object.fromEntries(formData)); // Debug
                $('#message').html('<div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded-lg"><p>Đang xử lý...</p></div>');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('AJAX response:', response); // Debug
                        if (response.success) {
                            $('#message').html('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>' + response.data.message + '</p></div>');
                            $('.nhaxe-add-form').addClass('hidden');
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'nhaxemyduyen_filter_routes',
                                    filter_from_location: $('#filter_from_location').val(),
                                    filter_to_location: $('#filter_to_location').val(),
                                    filter_price_min: $('#filter_price_min').val(),
                                    filter_price_max: $('#filter_price_max').val()
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $('table tbody').html(response.data.html);
                                    } else {
                                        $('#message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi khi làm mới danh sách tuyến đường.</p></div>');
                                    }
                                },
                                error: function() {
                                    $('#message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra khi làm mới danh sách tuyến đường.</p></div>');
                                }
                            });
                        } else {
                            $('#message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error, xhr.responseText); // Debug
                        $('#message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra khi xử lý yêu cầu: ' + error + '</p></div>');
                    }
                });
            });

            // Xử lý xóa tuyến đường bằng AJAX
            $(document).on('click', '.nhaxe-delete-route', function() {
                if (!confirm('Bạn có chắc chắn muốn xóa?')) return;
                var routeId = $(this).data('route-id');
                var nonce = $(this).data('nonce');
                $('#message').html('<div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded-lg"><p>Đang xử lý...</p></div>');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'nhaxemyduyen_delete_route',
                        route_id: routeId,
                        nhaxemyduyen_delete_nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#message').html('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>' + response.data.message + '</p></div>');
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'nhaxemyduyen_filter_routes',
                                    filter_from_location: $('#filter_from_location').val(),
                                    filter_to_location: $('#filter_to_location').val(),
                                    filter_price_min: $('#filter_price_min').val(),
                                    filter_price_max: $('#filter_price_max').val()
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $('table tbody').html(response.data.html);
                                    } else {
                                        $('#message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi khi làm mới danh sách tuyến đường.</p></div>');
                                    }
                                },
                                error: function() {
                                    $('#message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra khi làm mới danh sách tuyến đường.</p></div>');
                                }
                            });
                        } else {
                            $('#message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra khi xóa tuyến đường.</p></div>');
                    }
                });
            });
        });
    </script>
    <?php
}

// AJAX xóa tuyến đường
add_action('wp_ajax_nhaxemyduyen_delete_route', 'nhaxemyduyen_delete_route_ajax');

function nhaxemyduyen_delete_route_ajax() {
    global $wpdb;
    $table_routes = $wpdb->prefix . 'routes';
    $table_trips = $wpdb->prefix . 'trips';

    // Kiểm tra nonce
    if (!isset($_POST['nhaxemyduyen_delete_nonce']) || !wp_verify_nonce($_POST['nhaxemyduyen_delete_nonce'], 'nhaxemyduyen_delete_route')) {
        wp_send_json_error(array('message' => 'Lỗi xác thực nonce.'));
        wp_die();
    }

    $route_id = isset($_POST['route_id']) ? intval($_POST['route_id']) : 0;
    if ($route_id <= 0) {
        wp_send_json_error(array('message' => 'ID tuyến đường không hợp lệ.'));
        wp_die();
    }

    // Xóa tất cả chuyến xe liên quan đến tuyến đường
    $wpdb->delete($table_trips, array('route_id' => $route_id));

    // Xóa ảnh của tuyến đường
    $bus_image = $wpdb->get_var($wpdb->prepare("SELECT bus_image FROM $table_routes WHERE route_id = %d", $route_id));
    if (!empty($bus_image)) {
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $bus_image);
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Xóa tuyến đường
    $result = $wpdb->delete($table_routes, array('route_id' => $route_id));
    if ($result === false) {
        wp_send_json_error(array('message' => 'Lỗi: Không thể xóa tuyến đường. ' . esc_html($wpdb->last_error)));
    } else {
        wp_send_json_success(array('message' => 'Xóa tuyến đường thành công!'));
    }

    wp_die();
}

// AJAX lọc tuyến đường
add_action('wp_ajax_nhaxemyduyen_filter_routes', 'nhaxemyduyen_filter_routes_ajax');

function nhaxemyduyen_filter_routes_ajax() {
    global $wpdb;
    $table_routes = $wpdb->prefix . 'routes';
    $table_locations = $wpdb->prefix . 'locations';

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

    // Lấy danh sách tuyến đường
    $routes = $wpdb->get_results("
        SELECT r.*, l1.name as from_location, l2.name as to_location
        FROM $table_routes r
        JOIN $table_locations l1 ON r.from_location_id = l1.location_id
        JOIN $table_locations l2 ON r.to_location_id = l2.location_id
        $where_clause
        ORDER BY r.created_at DESC
    ", ARRAY_A);

    ob_start();
    if (empty($routes)) {
        echo '<tr><td colspan="10" class="px-4 py-3 text-sm text-gray-500 text-center">Không tìm thấy tuyến đường nào.</td></tr>';
    } else {
        foreach ($routes as $route) {
            ?>
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
                    <button class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition mr-2 nhaxe-toggle-form" 
                            data-action="edit" 
                            data-route-id="<?php echo esc_attr($route['route_id']); ?>" 
                            data-from-location-id="<?php echo esc_attr($route['from_location_id']); ?>" 
                            data-to-location-id="<?php echo esc_attr($route['to_location_id']); ?>" 
                            data-price="<?php echo esc_attr($route['price']); ?>" 
                            data-distance="<?php echo esc_attr($route['distance']); ?>" 
                            data-duration="<?php echo esc_attr(format_duration_to_hhmm($route['duration'])); ?>" 
                            data-bus-image="<?php echo esc_attr($route['bus_image']); ?>">Sửa</button>
                    <button class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition nhaxe-delete-route" 
                            data-route-id="<?php echo esc_attr($route['route_id']); ?>" 
                            data-nonce="<?php echo wp_create_nonce('nhaxemyduyen_delete_route'); ?>">Xóa</button>
                </td>
            </tr>
            <?php
        }
    }
    $html = ob_get_clean();
    wp_send_json_success(array('html' => $html));
    wp_die();
}

// AJAX thêm/sửa tuyến đường
add_action('wp_ajax_nhaxemyduyen_manage_route', 'nhaxemyduyen_manage_route_ajax');

function nhaxemyduyen_manage_route_ajax() {
    global $wpdb;
    $table_routes = $wpdb->prefix . 'routes';

    // Kiểm tra nonce
    if (!isset($_POST['nhaxemyduyen_route_nonce']) || !wp_verify_nonce($_POST['nhaxemyduyen_route_nonce'], 'nhaxemyduyen_route_action')) {
        wp_send_json_error(array('message' => 'Lỗi xác thực nonce.'));
        wp_die();
    }

    $action = isset($_POST['nhaxemyduyen_route_action']) ? sanitize_text_field($_POST['nhaxemyduyen_route_action']) : '';

    // Chuyển định dạng "giờ:phút" thành số phút
    $duration_input = sanitize_text_field($_POST['duration']);
    if (!preg_match('/^\d+:[0-5][0-9]$/', $duration_input)) {
        wp_send_json_error(array('message' => 'Lỗi: Thời gian di chuyển phải có định dạng giờ:phút (VD: 2:30).'));
        wp_die();
    }
    $duration_minutes = parse_hhmm_to_minutes($duration_input);
    if ($duration_minutes <= 0) {
        wp_send_json_error(array('message' => 'Lỗi: Thời gian di chuyển phải lớn hơn 0.'));
        wp_die();
    }

    // Xử lý upload ảnh
    $bus_image_url = '';
    if (!empty($_FILES['bus_image']['name'])) {
        $uploaded_file = wp_handle_upload($_FILES['bus_image'], array('test_form' => false));
        if (isset($uploaded_file['error'])) {
            wp_send_json_error(array('message' => 'Lỗi: Không thể upload ảnh - ' . esc_html($uploaded_file['error'])));
            wp_die();
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
            wp_send_json_error(array('message' => 'Lỗi: Không thể thêm tuyến đường. ' . esc_html($wpdb->last_error)));
        } else {
            wp_send_json_success(array('message' => 'Tuyến đường đã được thêm thành công!'));
        }
    } elseif ($action === 'edit') {
        $route_id = intval($_POST['route_id']);
        unset($route_data['created_at']);
        $result = $wpdb->update($table_routes, $route_data, array('route_id' => $route_id));
        if ($result === false) {
            wp_send_json_error(array('message' => 'Lỗi: Không thể cập nhật tuyến đường. ' . esc_html($wpdb->last_error)));
        } else {
            wp_send_json_success(array('message' => 'Tuyến đường đã được cập nhật thành công!'));
        }
    } else {
        wp_send_json_error(array('message' => 'Hành động không hợp lệ.'));
    }

    wp_die();
}


// Trang quản lý chuyến xe
function nhaxemyduyen_manage_trips() {
    global $wpdb;
    $table_trips = $wpdb->prefix . 'trips';
    $table_locations = $wpdb->prefix . 'locations';
    $table_tickets = $wpdb->prefix . 'tickets';
    $table_drivers = $wpdb->prefix . 'drivers';
    $table_vehicles = $wpdb->prefix . 'vehicles';
    $table_routes = $wpdb->prefix . 'routes';

    // Kiểm tra quyền truy cập
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền truy cập trang này.');
    }

    // Đăng ký script và style
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    wp_enqueue_style('tailwind-css', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    // Xử lý ngày từ form (mặc định là ngày hiện tại nếu không có giá trị)
    $selected_date = isset($_POST['departure_time']) ? sanitize_text_field($_POST['departure_time']) : date('Y-m-d\TH:i');
    $selected_date = DateTime::createFromFormat('Y-m-d\TH:i', $selected_date);
    if ($selected_date) {
        $filter_date = $selected_date->format('Y-m-d');
    } else {
        $filter_date = date('Y-m-d'); // Mặc định là ngày hiện tại
    }

    // Lấy danh sách tài xế và phương tiện đã được sử dụng trong ngày được chọn
    $used_drivers = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT driver_id FROM $table_trips WHERE driver_id IS NOT NULL AND DATE(departure_time) = %s",
        $filter_date
    ));

    $used_vehicles = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT vehicle_id FROM $table_trips WHERE vehicle_id IS NOT NULL AND DATE(departure_time) = %s",
        $filter_date
    ));

    // Lấy danh sách tài xế và phương tiện, loại bỏ những cái đã sử dụng trong ngày đó
    $drivers = $wpdb->get_results("
        SELECT driver_id, name 
        FROM $table_drivers 
        WHERE status = 'Active' 
        AND driver_id NOT IN (" . (!empty($used_drivers) ? implode(',', array_map('intval', $used_drivers)) : '0') . ")
        ORDER BY name", ARRAY_A);

    $vehicles = $wpdb->get_results("
        SELECT vehicle_id, license_plate, capacity, image 
        FROM $table_vehicles 
        WHERE status = 'Active' 
        AND vehicle_id NOT IN (" . (!empty($used_vehicles) ? implode(',', array_map('intval', $used_vehicles)) : '0') . ")
        ORDER BY license_plate", ARRAY_A);

    $routes = $wpdb->get_results("
        SELECT route_id, r.from_location_id, r.to_location_id, l1.name as from_location, l2.name as to_location, r.price, r.distance, r.duration 
        FROM $table_routes r 
        JOIN $table_locations l1 ON r.from_location_id = l1.location_id 
        JOIN $table_locations l2 ON r.to_location_id = l2.location_id 
        ORDER BY r.created_at DESC", ARRAY_A);

    // Xử lý bộ Tìm kiếm
    $filter_route_id = isset($_POST['filter_route_id']) ? intval($_POST['filter_route_id']) : 0;
    $filter_departure_date = isset($_POST['filter_departure_date']) ? sanitize_text_field($_POST['filter_departure_date']) : current_time('m/d/Y'); // Mặc định là ngày hiện tại
    $filter_seats_min = isset($_POST['filter_seats_min']) ? intval($_POST['filter_seats_min']) : '';
    $filter_driver = isset($_POST['filter_driver']) ? intval($_POST['filter_driver']) : 0;
    $filter_vehicle = isset($_POST['filter_vehicle']) ? intval($_POST['filter_vehicle']) : 0;

    if (!empty($filter_departure_date)) {
        $date = DateTime::createFromFormat('m/d/Y', $filter_departure_date);
        if ($date) {
            $filter_departure_date = $date->format('Y-m-d');
        } else {
            $filter_departure_date = current_time('Y-m-d');
        }
    } else {
        $filter_departure_date = current_time('Y-m-d');
    }

    $where_conditions = [];
    if ($filter_route_id > 0) {
        $where_conditions[] = $wpdb->prepare("t.route_id = %d", $filter_route_id);
    }
    $where_conditions[] = $wpdb->prepare("DATE(t.departure_time) = %s", $filter_departure_date);
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

    // Lấy danh sách chuyến xe với bộ Tìm kiếm
    $trips = $wpdb->get_results("
        SELECT t.*, l1.name as from_location, l2.name as to_location, d.name as driver_name, v.license_plate as vehicle_plate, v.image as bus_image, r.price as route_price
        FROM $table_trips t
        JOIN $table_routes r ON t.route_id = r.route_id
        JOIN $table_locations l1 ON r.from_location_id = l1.location_id
        JOIN $table_locations l2 ON r.to_location_id = l2.location_id
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
        } elseif ($_GET['message'] === 'bulk_delete_success') {
            $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>Xóa các chuyến xe được chọn thành công!</p></div>';
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
                    <select name="filter_route_id" id="filter_route_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="0">-- Chọn tuyến đường --</option>
                        <?php foreach ($routes as $route) : ?>
                            <option value="<?php echo esc_attr($route['route_id']); ?>" <?php selected($filter_route_id, $route['route_id']); ?>>
                                <?php echo esc_html($route['from_location'] . ' -> ' . $route['to_location'] . ' (Giá: ' . number_format($route['price'], 0, ',', '.') . ' VNĐ)'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="filter_departure_date" id="filter_departure_date" 
                           value="<?php echo esc_attr(current_time('m/d/Y')); ?>" 
                           placeholder="mm/dd/yyyy" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Tìm kiếm</button>
                </form>

                <!-- Add Trip and Delete Selected Buttons -->
                <div class="flex space-x-4 mt-4 sm:mt-0">
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition nhaxe-toggle-form" data-action="add">Thêm Chuyến Xe</button>
                    <button id="nhaxe-delete-selected" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition hidden" data-nonce="<?php echo wp_create_nonce('nhaxemyduyen_bulk_delete_trip'); ?>">Xóa Các Chuyến Được Chọn</button>
                </div>
            </div>

            <!-- Add/Edit Trip Form -->
            <div class="nhaxe-add-form hidden bg-gray-50 p-6 rounded-lg mb-6">
                <form id="trip-form" action="">
                    <?php wp_nonce_field('nhaxemyduyen_trip_action', 'nhaxemyduyen_trip_nonce'); ?>
                    <input type="hidden" name="nhaxemyduyen_trip_action" id="trip_action" value="add">
                    <input type="hidden" name="trip_id" id="trip_id" value="">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="route_id" class="block text-sm font-medium text-gray-700">Tuyến đường</label>
                            <select name="route_id" id="route_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" onchange="updateRouteInfo(this)">
                                <option value="">-- Chọn tuyến đường --</option>
                                <?php foreach ($routes as $route) : ?>
                                    <option value="<?php echo esc_attr($route['route_id']); ?>" 
                                            data-price="<?php echo esc_attr($route['price']); ?>" 
                                            data-duration="<?php echo esc_attr($route['duration']); ?>">
                                        <?php echo esc_html($route['from_location'] . ' -> ' . $route['to_location']); ?>
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
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="select-all-trips" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tuyến đường</th>
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
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <input type="checkbox" class="trip-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" value="<?php echo esc_attr($trip['trip_id']); ?>">
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['trip_id']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['from_location'] . ' -> ' . $trip['to_location']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['pickup_location']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['dropoff_location']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo $trip['driver_name'] ? esc_html($trip['driver_name']) : 'Chưa chọn'; ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo $trip['vehicle_plate'] ? esc_html($trip['vehicle_plate']) : 'Chưa chọn'; ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html(date('m/d/Y H:i', strtotime($trip['departure_time']))); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo $trip['arrival_time'] ? esc_html(date('m/d/Y H:i', strtotime($trip['arrival_time']))) : 'Chưa có'; ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html(number_format($trip['route_price'], 0, ',', '.')); ?></td>
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
            // Khởi tạo Datepicker với ngày hiện tại
            $('#filter_departure_date').datepicker({
                dateFormat: 'mm/dd/yy',
                defaultDate: new Date(),
                onSelect: function(dateText) {
                    $(this).val(dateText);
                    refreshTripsTable(); // Làm mới bảng ngay khi chọn ngày
                }
            });

            // Tự động làm mới bảng khi tải trang
            $(document).ready(function() {
                refreshTripsTable();
            });

            // Xử lý chọn tất cả checkbox
            $('#select-all-trips').on('change', function() {
                $('.trip-checkbox').prop('checked', $(this).prop('checked'));
                toggleDeleteSelectedButton();
            });

            // Xử lý khi checkbox riêng lẻ thay đổi
            $(document).on('change', '.trip-checkbox', function() {
                toggleDeleteSelectedButton();
                // Nếu bỏ chọn một checkbox, bỏ chọn "Select All"
                if (!$(this).prop('checked')) {
                    $('#select-all-trips').prop('checked', false);
                }
                // Nếu tất cả checkbox được chọn, chọn "Select All"
                if ($('.trip-checkbox:checked').length === $('.trip-checkbox').length && $('.trip-checkbox').length > 0) {
                    $('#select-all-trips').prop('checked', true);
                }
            });

            // Hiển thị/ẩn nút "Xóa Các Chuyến Được Chọn"
            function toggleDeleteSelectedButton() {
                if ($('.trip-checkbox:checked').length > 0) {
                    $('#nhaxe-delete-selected').removeClass('hidden');
                } else {
                    $('#nhaxe-delete-selected').addClass('hidden');
                }
            }

            // Xử lý xóa nhiều chuyến xe
            $('#nhaxe-delete-selected').on('click', function() {
                if (!confirm('Bạn có chắc chắn muốn xóa các chuyến xe được chọn?')) return;

                var tripIds = $('.trip-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                if (tripIds.length === 0) {
                    $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Vui lòng chọn ít nhất một chuyến xe để xóa.</p></div>');
                    return;
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'nhaxemyduyen_bulk_delete_trips',
                        trip_ids: tripIds,
                        nhaxemyduyen_bulk_delete_nonce: $(this).data('nonce')
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#nhaxe-message').html('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>' + response.data.message + '</p></div>');
                            refreshTripsTable();
                            $('#select-all-trips').prop('checked', false);
                            toggleDeleteSelectedButton();
                        } else {
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra khi xóa các chuyến xe.</p></div>');
                    }
                });
            });

            // Cập nhật thông tin giá và thời gian khi chọn tuyến đường
            window.updateRouteInfo = function(element) {
                var selectedOption = $(element).find('option:selected');
                var price = selectedOption.data('price') || '';
                var duration = selectedOption.data('duration') || 0;

                $('#price').val(price);

                var departureTime = $('#departure_time').val();
                if (departureTime && duration > 0) {
                    var departureDate = new Date(departureTime);
                    var timezoneOffset = 7 * 60; // UTC+7
                    var adjustedDepartureDate = new Date(departureDate.getTime() + (timezoneOffset * 60000));
                    var arrivalDate = new Date(adjustedDepartureDate.getTime() + duration * 60000);
                    var arrivalTimeFormatted = arrivalDate.toISOString().slice(0, 16);
                    $('#arrival_time').val(arrivalTimeFormatted);
                } else {
                    $('#arrival_time').val('');
                }
            };

            // Trigger cập nhật giá và thời gian khi thay đổi giờ đi
            $('#departure_time').change(function() {
                $('#route_id').trigger('change');

                // Gửi AJAX để cập nhật danh sách tài xế và phương tiện theo ngày
                var departureTime = $(this).val();
                if (departureTime) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'nhaxemyduyen_get_available_drivers_vehicles',
                            departure_time: departureTime,
                            nonce: '<?php echo wp_create_nonce('nhaxemyduyen_get_available'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Cập nhật danh sách tài xế
                                var drivers = response.data.drivers;
                                var driverSelect = $('#driver_id');
                                driverSelect.html('<option value="">-- Chọn tài xế --</option>');
                                $.each(drivers, function(index, driver) {
                                    driverSelect.append('<option value="' + driver.driver_id + '">' + driver.name + '</option>');
                                });

                                // Cập nhật danh sách phương tiện
                                var vehicles = response.data.vehicles;
                                var vehicleSelect = $('#vehicle_id');
                                vehicleSelect.html('<option value="">-- Chọn xe --</option>');
                                $.each(vehicles, function(index, vehicle) {
                                    vehicleSelect.append('<option value="' + vehicle.vehicle_id + '" data-capacity="' + vehicle.capacity + '" data-image="' + vehicle.image + '">' + vehicle.license_plate + '</option>');
                                });

                                // Reset hình ảnh xe và số ghế trống
                                $('.nhaxe-image-preview').html('');
                                $('#available_seats').val('');
                            } else {
                                $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi khi cập nhật danh sách tài xế và phương tiện.</p></div>');
                            }
                        },
                        error: function() {
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra khi cập nhật danh sách tài xế và phương tiện.</p></div>');
                        }
                    });
                }
            });

              // Toggle form thêm/sửa chuyến xe
            $(document).on('click', '.nhaxe-toggle-form', function() {
                var action = $(this).data('action');
                var tripId = $(this).data('trip-id');

                if (action === 'add') {
                    // Reset form về trạng thái thêm mới
                    $('#trip_action').val('add');
                    $('#trip_id').val('');
                    $('#trip-form')[0].reset();
                    $('#price').val('');
                    $('#arrival_time').val('');
                    $('.nhaxe-image-preview').html('');
                    $('#submit-trip').text('Thêm Chuyến Xe');
                    $('.nhaxe-add-form').removeClass('hidden');
                } else if (action === 'edit' && tripId) {
                    // Lấy dữ liệu chuyến xe và nạp vào form
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
                                $('#route_id').val(trip.route_id).trigger('change');
                                $('#driver_id').val(trip.driver_id);
                                $('#vehicle_id').val(trip.vehicle_id).trigger('change');
                                $('#pickup_location').val(trip.pickup_location);
                                $('#dropoff_location').val(trip.dropoff_location);
                                $('#departure_time').val(trip.departure_time.replace(' ', 'T'));
                                $('#arrival_time').val(trip.arrival_time ? trip.arrival_time.replace(' ', 'T') : '');
                                $('#price').val(trip.price);
                                $('#available_seats').val(trip.available_seats);
                                // Hiển thị hình ảnh xe nếu có
                                var selectedVehicle = $('#vehicle_id').find('option:selected');
                                var imageUrl = selectedVehicle.data('image') || '';
                                if (imageUrl) {
                                    $('.nhaxe-image-preview').html('<img src="' + imageUrl + '" alt="Hình ảnh xe" class="max-w-[200px] rounded-lg">');
                                } else {
                                    $('.nhaxe-image-preview').html('');
                                }
                                $('#submit-trip').text('Cập nhật Chuyến Xe');
                                $('.nhaxe-add-form').removeClass('hidden');
                            } else {
                                $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg animate-slide-in"><p>Lỗi: Không lấy được dữ liệu chuyến xe.</p></div>');
                            }
                        },
                        error: function(xhr) {
                            console.error('Lỗi AJAX (get_trip):', xhr);
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg animate-slide-in"><p>Đã có lỗi xảy ra: ' + xhr.statusText + '</p></div>');
                        }
                    });
                } else {
                    // Ẩn form khi bấm Hủy hoặc không hợp lệ
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

                            // Làm mới danh sách tài xế và phương tiện sau khi thêm thành công
                            var departureTime = $('#departure_time').val();
                            if (departureTime) {
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'nhaxemyduyen_get_available_drivers_vehicles',
                                        departure_time: departureTime,
                                        nonce: '<?php echo wp_create_nonce('nhaxemyduyen_get_available'); ?>'
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            var drivers = response.data.drivers;
                                            var driverSelect = $('#driver_id');
                                            driverSelect.html('<option value="">-- Chọn tài xế --</option>');
                                            $.each(drivers, function(index, driver) {
                                                driverSelect.append('<option value="' + driver.driver_id + '">' + driver.name + '</option>');
                                            });

                                            var vehicles = response.data.vehicles;
                                            var vehicleSelect = $('#vehicle_id');
                                            vehicleSelect.html('<option value="">-- Chọn xe --</option>');
                                            $.each(vehicles, function(index, vehicle) {
                                                vehicleSelect.append('<option value="' + vehicle.vehicle_id + '" data-capacity="' + vehicle.capacity + '" data-image="' + vehicle.image + '">' + vehicle.license_plate + '</option>');
                                            });

                                            $('.nhaxe-image-preview').html('');
                                            $('#available_seats').val('');
                                        }
                                    },
                                    error: function() {
                                        $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra khi làm mới danh sách tài xế và phương tiện.</p></div>');
                                    }
                                });
                            }
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

            // Xử lý Tìm kiếm danh sách chuyến xe qua AJAX
            $('#filter-form').submit(function(e) {
                e.preventDefault();
                refreshTripsTable();
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
                            $('#select-all-trips').prop('checked', false);
                            toggleDeleteSelectedButton();
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

// Xử lý AJAX lấy danh sách tài xế và phương tiện theo ngày
add_action('wp_ajax_nhaxemyduyen_get_available_drivers_vehicles', 'nhaxemyduyen_get_available_drivers_vehicles_callback');
function nhaxemyduyen_get_available_drivers_vehicles_callback() {
    check_ajax_referer('nhaxemyduyen_get_available', 'nonce');

    global $wpdb;
    $table_trips = $wpdb->prefix . 'trips';
    $table_drivers = $wpdb->prefix . 'drivers';
    $table_vehicles = $wpdb->prefix . 'vehicles';

    $departure_time = isset($_POST['departure_time']) ? sanitize_text_field($_POST['departure_time']) : '';
    if (!$departure_time) {
        wp_send_json_error(['message' => 'Ngày giờ không hợp lệ']);
    }

    $departure_date = DateTime::createFromFormat('Y-m-d\TH:i', $departure_time);
    if (!$departure_date) {
        wp_send_json_error(['message' => 'Định dạng ngày giờ không hợp lệ']);
    }
    $filter_date = $departure_date->format('Y-m-d');

    // Lấy danh sách tài xế và phương tiện đã được sử dụng trong ngày
    $used_drivers = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT driver_id FROM $table_trips WHERE driver_id IS NOT NULL AND DATE(departure_time) = %s",
        $filter_date
    ));

    $used_vehicles = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT vehicle_id FROM $table_trips WHERE vehicle_id IS NOT NULL AND DATE(departure_time) = %s",
        $filter_date
    ));

    // Lấy danh sách tài xế và phương tiện chưa được sử dụng
    $drivers = $wpdb->get_results("
        SELECT driver_id, name 
        FROM $table_drivers 
        WHERE status = 'Active' 
        AND driver_id NOT IN (" . (!empty($used_drivers) ? implode(',', array_map('intval', $used_drivers)) : '0') . ")
        ORDER BY name", ARRAY_A);

    $vehicles = $wpdb->get_results("
        SELECT vehicle_id, license_plate, capacity, image 
        FROM $table_vehicles 
        WHERE status = 'Active' 
        AND vehicle_id NOT IN (" . (!empty($used_vehicles) ? implode(',', array_map('intval', $used_vehicles)) : '0') . ")
        ORDER BY license_plate", ARRAY_A);

    wp_send_json_success([
        'drivers' => $drivers,
        'vehicles' => $vehicles
    ]);
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
        empty($_POST['route_id']) ||
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
        'route_id' => intval($_POST['route_id']),
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

// Xử lý AJAX xóa nhiều chuyến xe
add_action('wp_ajax_nhaxemyduyen_bulk_delete_trips', 'nhaxemyduyen_bulk_delete_trips_callback');
function nhaxemyduyen_bulk_delete_trips_callback() {
    global $wpdb;
    $table_trips = $wpdb->prefix . 'trips';
    $table_tickets = $wpdb->prefix . 'tickets';

    check_ajax_referer('nhaxemyduyen_bulk_delete_trip', 'nhaxemyduyen_bulk_delete_nonce');

    if (!isset($_POST['trip_ids']) || !is_array($_POST['trip_ids']) || empty($_POST['trip_ids'])) {
        wp_send_json_error(array('message' => 'Lỗi: Không có chuyến xe nào được chọn để xóa.'));
    }

    $trip_ids = array_map('intval', $_POST['trip_ids']);
    $deleted_count = 0;
    $skipped_count = 0;
    $errors = [];

    foreach ($trip_ids as $trip_id) {
        // Kiểm tra vé đã đặt
        $ticket_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_tickets WHERE trip_id = %d", $trip_id));
        if ($ticket_count > 0) {
            $skipped_count++;
            continue;
        }

        // Xóa chuyến xe
        $result = $wpdb->delete($table_trips, array('trip_id' => $trip_id));
        if ($result === false) {
            $errors[] = "Không thể xóa chuyến xe ID $trip_id: " . esc_html($wpdb->last_error);
        } else {
            $deleted_count++;
        }
    }

    if ($deleted_count > 0) {
        $message = "Đã xóa $deleted_count chuyến xe thành công.";
        if ($skipped_count > 0) {
            $message .= " $skipped_count chuyến xe không thể xóa vì đã có vé được đặt.";
        }
        if (!empty($errors)) {
            $message .= " Có lỗi xảy ra: " . implode(', ', $errors);
        }
        wp_send_json_success(array('message' => $message));
    } else {
        $message = 'Không có chuyến xe nào được xóa.';
        if ($skipped_count > 0) {
            $message .= " $skipped_count chuyến xe không thể xóa vì đã có vé được đặt.";
        }
        if (!empty($errors)) {
            $message .= " Có lỗi xảy ra: " . implode(', ', $errors);
        }
        wp_send_json_error(array('message' => $message));
    }
}

// Xử lý AJAX Tìm kiếm chuyến xe
add_action('wp_ajax_nhaxemyduyen_filter_trips', 'nhaxemyduyen_filter_trips_callback');
function nhaxemyduyen_filter_trips_callback() {
    global $wpdb;
    $table_trips = $wpdb->prefix . 'trips';
    $table_locations = $wpdb->prefix . 'locations';
    $table_drivers = $wpdb->prefix . 'drivers';
    $table_vehicles = $wpdb->prefix . 'vehicles';
    $table_routes = $wpdb->prefix . 'routes';

    $filter_route_id = isset($_POST['filter_route_id']) ? intval($_POST['filter_route_id']) : 0;
    $filter_departure_date = isset($_POST['filter_departure_date']) ? sanitize_text_field($_POST['filter_departure_date']) : current_time('m/d/Y');

    if (!empty($filter_departure_date)) {
        $date = DateTime::createFromFormat('m/d/Y', $filter_departure_date);
        if ($date) {
            $filter_departure_date = $date->format('Y-m-d');
        } else {
            $filter_departure_date = current_time('Y-m-d');
        }
    } else {
        $filter_departure_date = current_time('Y-m-d');
    }

    $where_conditions = [];
    if ($filter_route_id > 0) {
        $where_conditions[] = $wpdb->prepare("t.route_id = %d", $filter_route_id);
    }
    $where_conditions[] = $wpdb->prepare("DATE(t.departure_time) = %s", $filter_departure_date);

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = " WHERE " . implode(' AND ', $where_conditions);
    }

    $trips = $wpdb->get_results("
        SELECT t.*, l1.name as from_location, l2.name as to_location, d.name as driver_name, v.license_plate as vehicle_plate, v.image as bus_image, r.price as route_price
        FROM $table_trips t
        JOIN $table_routes r ON t.route_id = r.route_id
        JOIN $table_locations l1 ON r.from_location_id = l1.location_id
        JOIN $table_locations l2 ON r.to_location_id = l2.location_id
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
                <td class="px-4 py-3 text-sm text-gray-900">
                    <input type="checkbox" class="trip-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" value="<?php echo esc_attr($trip['trip_id']); ?>">
                </td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['trip_id']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['from_location'] . ' -> ' . $trip['to_location']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['pickup_location']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($trip['dropoff_location']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo $trip['driver_name'] ? esc_html($trip['driver_name']) : 'Chưa chọn'; ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo $trip['vehicle_plate'] ? esc_html($trip['vehicle_plate']) : 'Chưa chọn'; ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html(date('m/d/Y H:i', strtotime($trip['departure_time']))); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo $trip['arrival_time'] ? esc_html(date('m/d/Y H:i', strtotime($trip['arrival_time']))) : 'Chưa có'; ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html(number_format($trip['route_price'], 0, ',', '.')); ?></td>
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



// Đảm bảo PHPSpreadsheet đã được tải (nếu đã cài đặt qua Composer)
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Đăng ký stylesheet (nếu cần)
if (file_exists(plugin_dir_path(__FILE__) . 'admin-style.css')) {
    wp_enqueue_style('nhaxemyduyen-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
}

// Quản lý vé xe
function nhaxemyduyen_manage_tickets() {
    global $wpdb;
    $table_tickets = $wpdb->prefix . 'tickets';
    $table_trips = $wpdb->prefix . 'trips';
    $table_locations = $wpdb->prefix . 'locations';
    $table_drivers = $wpdb->prefix . 'drivers';
    $table_vehicles = $wpdb->prefix . 'vehicles';
    $table_routes = $wpdb->prefix . 'routes';

    // Đăng ký script và style
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    wp_enqueue_style('tailwind-css', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    // Xử lý AJAX
    if (isset($_POST['nhaxemyduyen_action']) && $_POST['nhaxemyduyen_action'] !== 'export_tickets_to_excel') {
        header('Content-Type: application/json');

        // Xử lý thêm vé
        if ($_POST['nhaxemyduyen_action'] === 'add_ticket') {
            // Kiểm tra nonce
            check_ajax_referer('nhaxemyduyen_ticket_nonce', 'nonce');
        
            // Lấy dữ liệu từ POST
            $customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
            $customer_phone = isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '';
            $customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
            $trip_id = isset($_POST['trip_id']) ? intval($_POST['trip_id']) : 0;
            $seat_number = isset($_POST['seat_number']) ? sanitize_text_field($_POST['seat_number']) : '';
            $pickup_location = isset($_POST['pickup_location']) ? sanitize_text_field($_POST['pickup_location']) : '';
            $dropoff_location = isset($_POST['dropoff_location']) ? sanitize_text_field($_POST['dropoff_location']) : '';
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'Chưa thanh toán';
            $note = isset($_POST['note']) ? sanitize_text_field($_POST['note']) : '';
        
            // Xác thực dữ liệu
            if (empty($customer_name) || empty($customer_phone) || empty($trip_id) || empty($seat_number)) {
                wp_send_json_error(['message' => 'Vui lòng điền đầy đủ thông tin bắt buộc (Tên khách hàng, Số điện thoại, Chuyến xe, Số ghế).']);
                return;
            }
        
            if (!preg_match('/^[0-9]{10}$/', $customer_phone)) {
                wp_send_json_error(['message' => 'Số điện thoại không hợp lệ (phải là 10 chữ số).']);
                return;
            }
        
            // Kiểm tra chuyến xe có tồn tại và còn ghế trống
            $trip = $wpdb->get_row($wpdb->prepare("SELECT available_seats FROM $table_trips WHERE trip_id = %d", $trip_id), ARRAY_A);
            if (!$trip || $trip['available_seats'] < 1) {
                wp_send_json_error(['message' => 'Chuyến xe không tồn tại hoặc đã hết ghế.']);
                return;
            }
        
            // Tạo mã vé ngẫu nhiên
            $ticket_code = 'TICKET_' . strtoupper(uniqid());
        
            // Thêm vé vào cơ sở dữ liệu
            $data = [
                'ticket_code' => $ticket_code,
                'customer_name' => $customer_name,
                'customer_phone' => $customer_phone,
                'customer_email' => $customer_email,
                'trip_id' => $trip_id,
                'seat_number' => $seat_number,
                'pickup_location' => $pickup_location,
                'dropoff_location' => $dropoff_location,
                'status' => $status,
                'note' => $note,
                'created_at' => current_time('mysql'),
            ];
        
            $result = $wpdb->insert($table_tickets, $data);
            if ($result === false) {
                error_log('Insert ticket error: ' . $wpdb->last_error);
                wp_send_json_error(['message' => 'Lỗi khi thêm vé vào cơ sở dữ liệu: ' . $wpdb->last_error]);
                return;
            }
        
            // Cập nhật số ghế trống của chuyến xe
            $update_result = $wpdb->query($wpdb->prepare("UPDATE $table_trips SET available_seats = available_seats - 1 WHERE trip_id = %d", $trip_id));
            if ($update_result === false) {
                error_log('Update available seats error: ' . $wpdb->last_error);
                wp_send_json_error(['message' => 'Lỗi khi cập nhật số ghế trống: ' . $wpdb->last_error]);
                return;
            }
        
            // Trả về phản hồi thành công
            wp_send_json_success(['message' => 'Thêm vé thành công. Mã vé: ' . $ticket_code]);
        }

        // Xử lý cập nhật trạng thái
        if ($_POST['nhaxemyduyen_action'] === 'update_status') {
            if (!check_admin_referer('nhaxemyduyen_ticket_nonce', 'nhaxemyduyen_ticket_nonce')) {
                error_log('Update status: Invalid nonce');
                wp_send_json_error(['message' => 'Lỗi bảo mật: Nonce không hợp lệ. Vui lòng tải lại trang và thử lại.']);
            }

            $ticket_id = intval($_POST['ticket_id']);
            $status = sanitize_text_field($_POST['status']);
            $valid_statuses = ['Đã thanh toán', 'Chưa thanh toán'];

            if (!in_array($status, $valid_statuses)) {
                wp_send_json_error(['message' => 'Trạng thái không hợp lệ.']);
            }

            $current_ticket = $wpdb->get_row($wpdb->prepare("SELECT status FROM $table_tickets WHERE ticket_id = %d", $ticket_id));
            if (!$current_ticket) {
                wp_send_json_error(['message' => 'Vé không tồn tại.']);
            }

            if ($current_ticket->status === $status) {
                wp_send_json_error(['message' => 'Trạng thái không thay đổi.']);
            }

            $result = $wpdb->update(
                $table_tickets,
                array('status' => $status, 'updated_at' => current_time('mysql')),
                array('ticket_id' => $ticket_id)
            );

            if ($result === false) {
                error_log('Status update error: ' . $wpdb->last_error);
                wp_send_json_error(['message' => 'Lỗi khi cập nhật trạng thái vé: ' . $wpdb->last_error]);
            }

            wp_send_json_success(['message' => 'Trạng thái vé đã được cập nhật thành công.', 'ticket_id' => $ticket_id, 'status' => $status]);
        }

        // Xử lý hủy vé
        if ($_POST['nhaxemyduyen_action'] === 'cancel_ticket') {
            if (!check_admin_referer('nhaxemyduyen_cancel_ticket', 'nhaxemyduyen_cancel_nonce')) {
                error_log('Cancel ticket: Invalid nonce');
                wp_send_json_error(['message' => 'Lỗi bảo mật: Nonce không hợp lệ. Vui lòng tải lại trang và thử lại.']);
            }

            error_log('Cancel ticket: Processing ticket_id - ' . $_POST['ticket_id']);

            $ticket_id = intval($_POST['ticket_id']);
            $ticket = $wpdb->get_row($wpdb->prepare("SELECT trip_id, status FROM $table_tickets WHERE ticket_id = %d", $ticket_id));
            if (!$ticket) {
                wp_send_json_error(['message' => 'Vé không tồn tại.']);
            }

            if ($ticket->status === 'Đã hủy') {
                wp_send_json_error(['message' => 'Vé đã được hủy trước đó.']);
            }

            // Start transaction
            $wpdb->query('START TRANSACTION');

            // Update ticket status
            $result = $wpdb->update(
                $table_tickets,
                array('status' => 'Đã hủy', 'updated_at' => current_time('mysql')),
                array('ticket_id' => $ticket_id)
            );
            if ($result === false) {
                $wpdb->query('ROLLBACK');
                error_log('Cancel ticket error: ' . $wpdb->last_error);
                wp_send_json_error(['message' => 'Lỗi khi hủy vé: ' . $wpdb->last_error]);
            }

            // Update available seats
            $trip = $wpdb->get_row($wpdb->prepare("SELECT available_seats FROM $table_trips WHERE trip_id = %d", $ticket->trip_id));
            if ($trip) {
                $update_result = $wpdb->update(
                    $table_trips,
                    array('available_seats' => $trip->available_seats + 1),
                    array('trip_id' => $ticket->trip_id)
                );
                if ($update_result === false) {
                    $wpdb->query('ROLLBACK');
                    error_log('Update available seats on cancel error: ' . $wpdb->last_error);
                    wp_send_json_error(['message' => 'Lỗi khi cập nhật số ghế trống: ' . $wpdb->last_error]);
                }
            } else {
                $wpdb->query('ROLLBACK');
                error_log('Trip not found for ticket_id: ' . $ticket_id);
                wp_send_json_error(['message' => 'Chuyến xe không tồn tại.']);
            }

            // Commit transaction
            $wpdb->query('COMMIT');

            wp_send_json_success(['message' => 'Vé đã được hủy thành công.', 'ticket_id' => $ticket_id, 'status' => 'Đã hủy']);
        }

        // Xử lý lọc chuyến xe
        if ($_POST['nhaxemyduyen_action'] === 'filter_trips') {
            $filter_route_id = isset($_POST['filter_route_id']) ? intval($_POST['filter_route_id']) : 0;
            $filter_departure_date = isset($_POST['filter_departure_date']) ? sanitize_text_field($_POST['filter_departure_date']) : '';

            // Chuyển đổi định dạng ngày
            if (!empty($filter_departure_date)) {
                $date = DateTime::createFromFormat('m/d/Y', $filter_departure_date);
                if ($date) {
                    $filter_departure_date = $date->format('Y-m-d');
                } else {
                    wp_send_json_error(['message' => 'Định dạng ngày không hợp lệ.']);
                }
            } else {
                $filter_departure_date = current_time('Y-m-d');
            }

            $where_conditions = ['t.available_seats > 0', 'DATE(t.departure_time) = %s'];
            $params = [$filter_departure_date];

            if ($filter_route_id > 0) {
                $where_conditions[] = 't.route_id = %d';
                $params[] = $filter_route_id;
            }

            $where_clause = ' WHERE ' . implode(' AND ', $where_conditions);
            $query = $wpdb->prepare("
                SELECT t.*, l1.name as from_location, l2.name as to_location, t.pickup_location, t.dropoff_location, 
                    d.name as driver_name, v.license_plate as vehicle_plate, v.image as bus_image
                FROM $table_trips t
                JOIN $table_routes r ON t.route_id = r.route_id
                JOIN $table_locations l1 ON r.from_location_id = l1.location_id
                JOIN $table_locations l2 ON r.to_location_id = l2.location_id
                LEFT JOIN $table_drivers d ON t.driver_id = d.driver_id
                LEFT JOIN $table_vehicles v ON t.vehicle_id = v.vehicle_id
                $where_clause
                ORDER BY t.departure_time ASC
            ", $params);

            $trips = $wpdb->get_results($query, ARRAY_A);

            if ($wpdb->last_error) {
                error_log('Filter trips query error: ' . $wpdb->last_error);
                wp_send_json_error(['message' => 'Lỗi khi lấy danh sách chuyến xe: ' . $wpdb->last_error]);
            }

            $options = '<option value="">-- Chọn chuyến xe --</option>';
            if (!empty($trips)) {
                foreach ($trips as $trip) {
                    $options .= sprintf(
                        '<option value="%s" data-pickup="%s" data-dropoff="%s" data-driver="%s" data-vehicle="%s" data-image="%s">%s</option>',
                        esc_attr($trip['trip_id']),
                        esc_attr($trip['pickup_location']),
                        esc_attr($trip['dropoff_location']),
                        esc_attr($trip['driver_name'] ?: 'Chưa chọn'),
                        esc_attr($trip['vehicle_plate'] ?: 'Chưa chọn'),
                        esc_attr($trip['bus_image'] ?: ''),
                        esc_html($trip['from_location'] . ' - ' . $trip['to_location'] . ' (' . date('m/d/Y H:i', strtotime($trip['departure_time'])) . ')')
                    );
                }
            } else {
                $options .= '<option value="">Không có chuyến xe phù hợp</option>';
            }

            wp_send_json_success(['options' => $options]);
        }


        // Xử lý lọc vé
        if ($_POST['nhaxemyduyen_action'] === 'filter_tickets') {
            // [Logic lọc vé giữ nguyên như trước]
            $filter_customer_phone = isset($_POST['filter_customer_phone']) ? sanitize_text_field($_POST['filter_customer_phone']) : '';
            $filter_status = isset($_POST['filter_status']) ? sanitize_text_field($_POST['filter_status']) : '';
            $filter_departure_date = isset($_POST['filter_departure_date']) ? sanitize_text_field($_POST['filter_departure_date']) : '';
            $filter_from_location = isset($_POST['filter_from_location']) ? intval($_POST['filter_from_location']) : 0;
            $filter_to_location = isset($_POST['filter_to_location']) ? intval($_POST['filter_to_location']) : 0;
            $filter_pickup_location = isset($_POST['filter_pickup_location']) ? sanitize_text_field($_POST['filter_pickup_location']) : '';
            $filter_dropoff_location = isset($_POST['filter_dropoff_location']) ? sanitize_text_field($_POST['filter_dropoff_location']) : '';
            $filter_driver = isset($_POST['filter_driver']) ? intval($_POST['filter_driver']) : 0;
            $filter_vehicle = isset($_POST['filter_vehicle']) ? intval($_POST['filter_vehicle']) : 0;
            $filter_trip_id = isset($_POST['filter_trip_id']) ? intval($_POST['filter_trip_id']) : 0;

            if (!empty($filter_departure_date)) {
                $date = DateTime::createFromFormat('m/d/Y', $filter_departure_date);
                if ($date) {
                    $filter_departure_date = $date->format('Y-m-d');
                } else {
                    $filter_departure_date = '';
                }
            }

            $where_conditions = [];
            $params = [];
            if (!empty($filter_customer_phone)) {
                $where_conditions[] = 't.customer_phone LIKE %s';
                $params[] = '%' . $filter_customer_phone . '%';
            }
            if (!empty($filter_status)) {
                $where_conditions[] = 't.status = %s';
                $params[] = $filter_status;
            }
            if (!empty($filter_departure_date)) {
                $where_conditions[] = 'DATE(tr.departure_time) = %s';
                $params[] = $filter_departure_date;
            } else {
                $where_conditions[] = 'tr.departure_time >= CURDATE()';
            }
            if ($filter_from_location > 0) {
                $where_conditions[] = 'r.from_location_id = %d';
                $params[] = $filter_from_location;
            }
            if ($filter_to_location > 0) {
                $where_conditions[] = 'r.to_location_id = %d';
                $params[] = $filter_to_location;
            }
            if (!empty($filter_pickup_location)) {
                $where_conditions[] = 't.pickup_location = %s';
                $params[] = $filter_pickup_location;
            }
            if (!empty($filter_dropoff_location)) {
                $where_conditions[] = 't.dropoff_location = %s';
                $params[] = $filter_dropoff_location;
            }
            if ($filter_driver > 0) {
                $where_conditions[] = 'tr.driver_id = %d';
                $params[] = $filter_driver;
            }
            if ($filter_vehicle > 0) {
                $where_conditions[] = 'tr.vehicle_id = %d';
                $params[] = $filter_vehicle;
            }
            if ($filter_trip_id > 0) {
                $where_conditions[] = 't.trip_id = %d';
                $params[] = $filter_trip_id;
            }

            $where_clause = !empty($where_conditions) ? ' WHERE ' . implode(' AND ', $where_conditions) : '';
            $query = $wpdb->prepare("
                SELECT t.*, tr.departure_time, tr.pickup_location as trip_pickup_location, tr.dropoff_location as trip_dropoff_location, 
                       l1.name as from_location, l2.name as to_location, d.name as driver_name, v.license_plate as vehicle_plate
                FROM $table_tickets t
                JOIN $table_trips tr ON t.trip_id = tr.trip_id
                JOIN $table_routes r ON tr.route_id = r.route_id
                JOIN $table_locations l1 ON r.from_location_id = l1.location_id
                JOIN $table_locations l2 ON r.to_location_id = l2.location_id
                LEFT JOIN $table_drivers d ON tr.driver_id = d.driver_id
                LEFT JOIN $table_vehicles v ON tr.vehicle_id = v.vehicle_id
                $where_clause
                ORDER BY tr.departure_time DESC
            ", $params);

            $tickets = $wpdb->get_results($query, ARRAY_A);

            ob_start();
            if (!empty($tickets)) {
                foreach ($tickets as $ticket) {
                    ?>
                    <tr class="hover:bg-gray-50" data-ticket-id="<?php echo esc_attr($ticket['ticket_id']); ?>">
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
                            <select class="nhaxe-status-select border border-gray-300 rounded-lg px-2 py-1 focus:ring-2 focus:ring-blue-500" data-ticket-id="<?php echo esc_attr($ticket['ticket_id']); ?>">
                                <option value="Đã thanh toán" <?php selected($ticket['status'], 'Đã thanh toán'); ?>>Đã thanh toán</option>
                                <option value="Chưa thanh toán" <?php selected($ticket['status'], 'Chưa thanh toán'); ?>>Chưa thanh toán</option>
                                <option value="Đã hủy" <?php selected($ticket['status'], 'Đã hủy'); ?>>Đã hủy</option>
                            </select>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($ticket['note']); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <button class="nhaxe-cancel-ticket bg-yellow-600 text-white px-3 py-1 rounded-lg hover:bg-yellow-700 transition <?php echo $ticket['status'] === 'Đã hủy' ? 'bg-gray-400 cursor-not-allowed' : ''; ?>" 
                                    data-ticket-id="<?php echo esc_attr($ticket['ticket_id']); ?>" 
                                    data-nonce="<?php echo wp_create_nonce('nhaxemyduyen_cancel_ticket'); ?>" 
                                    <?php echo $ticket['status'] === 'Đã hủy' ? 'disabled' : ''; ?>>
                                Hủy vé
                            </button>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="15" class="px-4 py-3 text-sm text-gray-500 text-center">Không có vé nào phù hợp với tiêu chí.</td>
                </tr>
                <?php
            }
            $table_html = ob_get_clean();

            wp_send_json_success(['table_html' => $table_html]);
        }

        wp_send_json_error(['message' => 'Hành động không hợp lệ.']);
    }

    // Lấy danh sách tài xế, phương tiện, tuyến đường, chuyến xe, điểm đón, điểm trả
    $drivers = $wpdb->get_results("SELECT driver_id, name FROM $table_drivers WHERE status = 'Active' ORDER BY name", ARRAY_A);
    $vehicles = $wpdb->get_results("SELECT vehicle_id, license_plate FROM $table_vehicles WHERE status = 'Active' ORDER BY license_plate", ARRAY_A);
    $routes = $wpdb->get_results("
        SELECT r.route_id, l1.name as from_location, l2.name as to_location 
        FROM $table_routes r 
        JOIN $table_locations l1 ON r.from_location_id = l1.location_id 
        JOIN $table_locations l2 ON r.to_location_id = l2.location_id 
        ORDER BY r.created_at DESC
    ", ARRAY_A);
    $trips = $wpdb->get_results($wpdb->prepare("
        SELECT t.*, l1.name as from_location, l2.name as to_location, t.pickup_location, t.dropoff_location, 
            d.name as driver_name, v.license_plate as vehicle_plate, v.image as bus_image
        FROM $table_trips t
        JOIN $table_routes r ON t.route_id = r.route_id
        JOIN $table_locations l1 ON r.from_location_id = l1.location_id
        JOIN $table_locations l2 ON r.to_location_id = l2.location_id
        LEFT JOIN $table_drivers d ON t.driver_id = d.driver_id
        LEFT JOIN $table_vehicles v ON t.vehicle_id = v.vehicle_id
        WHERE t.available_seats > 0 AND DATE(t.departure_time) = %s
        ORDER BY t.departure_time ASC
    ", current_time('Y-m-d')), ARRAY_A);
    $pickup_locations = $wpdb->get_col("SELECT DISTINCT pickup_location FROM $table_trips WHERE pickup_location != '' ORDER BY pickup_location");
    $dropoff_locations = $wpdb->get_col("SELECT DISTINCT dropoff_location FROM $table_trips WHERE dropoff_location != '' ORDER BY dropoff_location");

    // Lấy tất cả vé ban đầu
    $filter_customer_phone = isset($_GET['filter_customer_phone']) ? sanitize_text_field($_GET['filter_customer_phone']) : '';
    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
    $filter_departure_date = isset($_GET['filter_departure_date']) ? sanitize_text_field($_GET['filter_departure_date']) : '';
    $filter_from_location = isset($_GET['filter_from_location']) ? intval($_GET['filter_from_location']) : 0;
    $filter_to_location = isset($_GET['filter_to_location']) ? intval($_GET['filter_to_location']) : 0;
    $filter_pickup_location = isset($_GET['filter_pickup_location']) ? sanitize_text_field($_GET['filter_pickup_location']) : '';
    $filter_dropoff_location = isset($_GET['filter_dropoff_location']) ? sanitize_text_field($_GET['filter_dropoff_location']) : '';
    $filter_driver = isset($_GET['filter_driver']) ? intval($_GET['filter_driver']) : 0;
    $filter_vehicle = isset($_GET['filter_vehicle']) ? intval($_GET['filter_vehicle']) : 0;
    $filter_trip_id = isset($_GET['filter_trip_id']) ? intval($_GET['filter_trip_id']) : 0;

    if (!empty($filter_departure_date)) {
        $date = DateTime::createFromFormat('m/d/Y', $filter_departure_date);
        if ($date) {
            $filter_departure_date = $date->format('Y-m-d');
        } else {
            $filter_departure_date = '';
        }
    }

    $where_conditions = [];
    $params = [];
    if (!empty($filter_customer_phone)) {
        $where_conditions[] = 't.customer_phone LIKE %s';
        $params[] = '%' . $filter_customer_phone . '%';
    }
    if (!empty($filter_status)) {
        $where_conditions[] = 't.status = %s';
        $params[] = $filter_status;
    }
    if (!empty($filter_departure_date)) {
        $where_conditions[] = 'DATE(tr.departure_time) = %s';
        $params[] = $filter_departure_date;
    } else {
        $where_conditions[] = 'tr.departure_time >= CURDATE()';
    }
    if ($filter_from_location > 0) {
        $where_conditions[] = 'r.from_location_id = %d';
        $params[] = $filter_from_location;
    }
    if ($filter_to_location > 0) {
        $where_conditions[] = 'r.to_location_id = %d';
        $params[] = $filter_to_location;
    }
    if (!empty($filter_pickup_location)) {
        $where_conditions[] = 't.pickup_location = %s';
        $params[] = $filter_pickup_location;
    }
    if (!empty($filter_dropoff_location)) {
        $where_conditions[] = 't.dropoff_location = %s';
        $params[] = $filter_dropoff_location;
    }
    if ($filter_driver > 0) {
        $where_conditions[] = 'tr.driver_id = %d';
        $params[] = $filter_driver;
    }
    if ($filter_vehicle > 0) {
        $where_conditions[] = 'tr.vehicle_id = %d';
        $params[] = $filter_vehicle;
    }
    if ($filter_trip_id > 0) {
        $where_conditions[] = 't.trip_id = %d';
        $params[] = $filter_trip_id;
    }

    $where_clause = !empty($where_conditions) ? ' WHERE ' . implode(' AND ', $where_conditions) : '';
    $tickets = $wpdb->get_results($wpdb->prepare("
        SELECT t.*, tr.departure_time, tr.pickup_location as trip_pickup_location, tr.dropoff_location as trip_dropoff_location, 
            l1.name as from_location, l2.name as to_location, d.name as driver_name, v.license_plate as vehicle_plate
        FROM $table_tickets t
        JOIN $table_trips tr ON t.trip_id = tr.trip_id
        JOIN $table_routes r ON tr.route_id = r.route_id
        JOIN $table_locations l1 ON r.from_location_id = l1.location_id
        JOIN $table_locations l2 ON r.to_location_id = l2.location_id
        LEFT JOIN $table_drivers d ON tr.driver_id = d.driver_id
        LEFT JOIN $table_vehicles v ON tr.vehicle_id = v.vehicle_id
        $where_clause
        ORDER BY tr.departure_time DESC
    ", $params), ARRAY_A);

    ?>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Quản lý Vé Xe</h1>

        <div id="nhaxe-messages" class="mb-6"></div>

        <?php if (empty($trips)) : ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                <p>Cảnh báo: Không có chuyến xe nào có sẵn cho hôm nay.</p>
            </div>
        <?php endif; ?>
        <?php if (empty($routes)) : ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                <p>Cảnh báo: Không có tuyến đường nào trong wp_routes!</p>
            </div>
        <?php endif; ?>
        <?php if (empty($pickup_locations)) : ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                <p>Cảnh báo: Không có điểm đón nào trong wp_trips!</p>
            </div>
        <?php endif; ?>
        <?php if (empty($dropoff_locations)) : ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                <p>Cảnh báo: Không có điểm trả nào trong wp_trips!</p>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Danh sách Vé Xe</h2>

            <!-- Filter Form -->
            <form id="nhaxe-filter-form" method="get" action="" class="mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <input type="text" name="filter_customer_phone" 
                        id="filter_customer_phone" value="<?php echo esc_attr($filter_customer_phone); ?>" 
                        placeholder="Số điện thoại khách hàng" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <input type="text" name="filter_departure_date" id="filter_departure_date" 
                        value="<?php echo esc_attr(!empty($filter_departure_date) ? date('m/d/Y', strtotime($filter_departure_date)) : ''); ?>" 
                        placeholder="mm/dd/yyyy" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <select name="filter_trip_id" id="filter_trip_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Chọn chuyến xe --</option>
                        <?php foreach ($trips as $trip) : ?>
                            <option value="<?php echo esc_attr($trip['trip_id']); ?>" 
                                    <?php selected($filter_trip_id, $trip['trip_id']); ?>>
                                <?php echo esc_html($trip['from_location'] . ' - ' . $trip['to_location'] . ' (' . date('m/d/Y H:i', strtotime($trip['departure_time'])) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Tìm kiếm</button>
                    <a href="#" id="nhaxe-export-excel" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition inline-block" 
                    data-base-url="<?php echo admin_url('admin-post.php?action=nhaxemyduyen_export_tickets&nhaxemyduyen_export_nonce=' . wp_create_nonce('nhaxemyduyen_export_nonce')); ?>">
                        Xuất Excel
                    </a>
                </div>
            </form>

             <!-- Add Ticket Button -->
             <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition mb-6 nhaxe-toggle-form">Thêm vé</button>

              <!-- Add Ticket Form -->
              <div class="nhaxe-add-form hidden bg-gray-50 p-6 rounded-lg mb-6">
                    <form id="nhaxe-add-ticket-form" method="post" action="">
                        <input type="hidden" name="nhaxemyduyen_action" value="add_ticket">
                        <?php wp_nonce_field('nhaxemyduyen_ticket_nonce', 'nonce'); ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Trip Filter -->
                            <div>
                                <label for="filter_route_id" class="block text-sm font-medium text-gray-700">Tuyến đường</label>
                                <select name="filter_route_id" id="filter_route_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Chọn tuyến đường --</option>
                                    <?php foreach ($routes as $route) : ?>
                                        <option value="<?php echo esc_attr($route['route_id']); ?>">
                                            <?php echo esc_html($route['from_location'] . ' - ' . $route['to_location']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="filter_departure_date_trip" class="block text-sm font-medium text-gray-700">Ngày khởi hành</label>
                                <input type="text" name="filter_departure_date_trip" id="filter_departure_date_trip" placeholder="mm/dd/yyyy" value="<?php echo esc_attr(date('m/d/Y')); ?>" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                            </div>
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
                <table id="nhaxe-ticket-table" class="min-w-full bg-white border border-gray-200">
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
                    <tbody id="nhaxe-ticket-table-body" class="divide-y divide-gray-200">
                        <?php if (!empty($tickets)) : ?>
                            <?php foreach ($tickets as $ticket) : ?>
                                <tr class="hover:bg-gray-50" data-ticket-id="<?php echo esc_attr($ticket['ticket_id']); ?>">
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
                                        <select class="nhaxe-status-select border border-gray-300 rounded-lg px-2 py-1 focus:ring-2 focus:ring-blue-500" data-ticket-id="<?php echo esc_attr($ticket['ticket_id']); ?>">
                                            <option value="Đã thanh toán" <?php selected($ticket['status'], 'Đã thanh toán'); ?>>Đã thanh toán</option>
                                            <option value="Chưa thanh toán" <?php selected($ticket['status'], 'Chưa thanh toán'); ?>>Chưa thanh toán</option>
                                            <option value="Đã hủy" <?php selected($ticket['status'], 'Đã hủy'); ?>>Đã hủy</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($ticket['note']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <button class="nhaxe-cancel-ticket bg-yellow-600 text-white px-3 py-1 rounded-lg hover:bg-yellow-700 transition <?php echo $ticket['status'] === 'Đã hủy' ? 'bg-gray-400 cursor-not-allowed' : ''; ?>" 
                                                data-ticket-id="<?php echo esc_attr($ticket['ticket_id']); ?>" 
                                                data-nonce="<?php echo wp_create_nonce('nhaxemyduyen_cancel_ticket'); ?>" 
                                                <?php echo $ticket['status'] === 'Đã hủy' ? 'disabled' : ''; ?>>
                                            Hủy vé
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
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
    #nhaxe-messages {
        transition: opacity 0.5s ease-in-out;
    }
    #nhaxe-messages .bg-green-100 {
        background-color: #d4edda;
    }
    #nhaxe-messages .border-green-500 {
        border-color: #28a745;
    }
    #nhaxe-messages .text-green-700 {
        color: #155724;
    }
    #nhaxe-messages .bg-red-100 {
        background-color: #f8d7da;
    }
    #nhaxe-messages .border-red-500 {
        border-color: #dc3545;
    }
    #nhaxe-messages .text-red-700 {
        color: #721c24;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Khởi tạo datepicker
        $('#filter_departure_date, #filter_departure_date_trip').datepicker({
            dateFormat: 'mm/dd/yy',
            minDate: 0,
            onSelect: function(dateText) {
                $(this).val(dateText);
                if ($(this).attr('id') === 'filter_departure_date_trip') {
                    filterTrips();
                }
            }
        });

        // Toggle form thêm vé
        $('.nhaxe-toggle-form').click(function() {
            $('.nhaxe-add-form').toggleClass('hidden');
            if ($('.nhaxe-add-form').hasClass('hidden')) {
                $('#nhaxe-add-ticket-form')[0].reset();
                $('#filter_departure_date_trip').val('<?php echo esc_attr(date('m/d/Y')); ?>');
                $('#trip_driver').text('Chưa chọn');
                $('#trip_vehicle').text('Chưa chọn');
                $('#trip_image').html('');
                filterTrips();
            }
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

        // Hàm hiển thị thông báo
        function showMessage(message, type) {
            const messageHtml = `<div class="bg-${type}-100 border-l-4 border-${type}-500 text-${type}-700 p-4 mb-6 rounded-lg"><p>${message}</p></div>`;
            $('#nhaxe-messages').html(messageHtml).show();
            setTimeout(() => $('#nhaxe-messages').fadeOut('slow', function() {
                $(this).html('');
            }), 5000);
        }

        // Hàm lọc chuyến xe
        function filterTrips() {
            const routeId = $('#filter_route_id').val();
            const departureDate = $('#filter_departure_date_trip').val();

            if (!departureDate) {
                $('#trip_id').html('<option value="">-- Chọn chuyến xe --</option>');
                $('#trip_driver').text('Chưa chọn');
                $('#trip_vehicle').text('Chưa chọn');
                $('#trip_image').html('');
                $('#pickup_location').val('');
                $('#dropoff_location').val('');
                return;
            }

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'nhaxemyduyen_manage_tickets',
                    nhaxemyduyen_action: 'filter_trips',
                    filter_route_id: routeId,
                    filter_departure_date: departureDate
                },
                success: function(response) {
                    console.log('Filter trips response:', response);
                    if (response.success) {
                        $('#trip_id').html(response.data.options);
                        $('#trip_driver').text('Chưa chọn');
                        $('#trip_vehicle').text('Chưa chọn');
                        $('#trip_image').html('');
                        $('#pickup_location').val('');
                        $('#dropoff_location').val('');
                        showMessage('Danh sách chuyến xe đã được cập nhật.', 'green');
                    } else {
                        showMessage(response.data.message || 'Không thể tải danh sách chuyến xe.', 'red');
                        $('#trip_id').html('<option value="">Không có chuyến xe phù hợp</option>');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Filter trips AJAX error:', textStatus, errorThrown);
                    showMessage('Lỗi kết nối máy chủ khi lọc chuyến xe.', 'red');
                    $('#trip_id').html('<option value="">Không có chuyến xe phù hợp</option>');
                }
            });
        }

        // Lọc chuyến xe khi thay đổi tuyến đường hoặc ngày khởi hành
        $('#filter_route_id, #filter_departure_date_trip').change(function() {
            filterTrips();
        });

        // Gọi filterTrips khi mở form
        $('.nhaxe-toggle-form').click(function() {
            if (!$('.nhaxe-add-form').hasClass('hidden')) {
                filterTrips();
            }
        });

        // Tải lại bảng vé
        function reloadTicketTable() {
            const formData = $('#nhaxe-filter-form').serialize();
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: formData + '&action=nhaxemyduyen_manage_tickets&nhaxemyduyen_action=filter_tickets',
                success: function(response) {
                    console.log('Reload ticket table response:', response);
                    if (response.success) {
                        $('#nhaxe-ticket-table-body').html(response.data.table_html);
                        showMessage('Danh sách vé đã được cập nhật.', 'green');
                    } else {
                        showMessage(response.data.message || 'Không thể tải danh sách vé.', 'red');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Reload ticket table AJAX error:', textStatus, errorThrown);
                    showMessage('Lỗi kết nối máy chủ khi tải danh sách vé.', 'red');
                }
            });
        }

        // Kiểm tra trạng thái thanh toán khi quay lại từ VNPAY
        function checkPaymentStatus() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const message = urlParams.get('message') || 'Cập nhật trạng thái vé...';

            if (status) {
                showMessage(message, status === 'success' ? 'green' : 'red');
                if (status === 'success') {
                    reloadTicketTable();
                }
                history.replaceState({}, document.title, window.location.pathname);
            }
        }

        // Gọi khi trang được tải
        checkPaymentStatus();

        // Xử lý thêm vé qua AJAX
        $('#nhaxe-add-ticket-form').on('submit', function(e) {
            e.preventDefault();
            console.log('Submitting add ticket form');
            const formData = new FormData(this);
            formData.append('action', 'nhaxemyduyen_manage_tickets');
            console.log('Form data:', Object.fromEntries(formData)); // Ghi log dữ liệu gửi đi
            if (!confirm('Bạn có chắc chắn muốn thêm vé này?')) {
                return;
            }
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 10000, // 10 giây
                success: function(response) {
                    console.log('Add ticket response:', response);
                    if (response.success) {
                        showMessage(response.data.message, 'green');
                        $('#nhaxe-add-ticket-form')[0].reset();
                        $('#filter_departure_date_trip').val('<?php echo esc_attr(date('m/d/Y')); ?>');
                        $('.nhaxe-add-form').addClass('hidden');
                        $('#trip_driver').text('Chưa chọn');
                        $('#trip_vehicle').text('Chưa chọn');
                        $('#trip_image').html('');
                        filterTrips();
                        reloadTicketTable();
                    } else {
                        showMessage(response.data.message, 'red');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Add ticket AJAX error:', textStatus, errorThrown);
                    console.log('Response:', jqXHR.responseText);
                    let errorMessage = 'Lỗi kết nối máy chủ khi thêm vé.';
                    if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                        errorMessage = jqXHR.responseJSON.data.message;
                    } else {
                        errorMessage += ' ' + textStatus + ': ' + errorThrown;
                    }
                    showMessage(errorMessage, 'red');
                }
            });
        });

        // Xử lý cập nhật trạng thái qua AJAX
        $(document).on('change', '.nhaxe-status-select', function() {
            const ticketId = $(this).data('ticket-id');
            const newStatus = $(this).val();
            const currentStatus = $(this).find('option:not(:selected)').filter(function() { return this.value !== newStatus; }).text();
            const nonce = '<?php echo wp_create_nonce('nhaxemyduyen_ticket_nonce'); ?>';

            if (confirm(`Bạn có chắc chắn muốn thay đổi trạng thái vé ${ticketId} từ "${currentStatus}" thành "${newStatus}"?`)) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'nhaxemyduyen_manage_tickets',
                        nhaxemyduyen_action: 'update_status',
                        ticket_id: ticketId,
                        status: newStatus,
                        nhaxemyduyen_ticket_nonce: nonce
                    },
                    success: function(response) {
                        console.log('Update status response:', response);
                        if (response.success) {
                            showMessage(response.data.message, 'green');
                            $(`select[data-ticket-id="${ticketId}"]`).val(newStatus);
                            reloadTicketTable();
                        } else {
                            showMessage(response.data.message, 'red');
                            $(`select[data-ticket-id="${ticketId}"]`).val(currentStatus);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Update status AJAX error:', textStatus, errorThrown);
                        showMessage('Lỗi kết nối máy chủ khi cập nhật trạng thái.', 'red');
                        $(`select[data-ticket-id="${ticketId}"]`).val(currentStatus);
                    }
                });
            } else {
                $(this).val(currentStatus);
            }
        });

        // Xử lý hủy vé qua AJAX
        $(document).on('click', '.nhaxe-cancel-ticket', function() {
            if ($(this).hasClass('cursor-not-allowed')) return;

            if (!confirm('Bạn có chắc chắn muốn hủy vé này?')) return;

            const ticketId = $(this).data('ticket-id');
            const nonce = $(this).data('nonce');

            console.log('Cancel ticket:', ticketId);

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'nhaxemyduyen_manage_tickets',
                    nhaxemyduyen_action: 'cancel_ticket',
                    ticket_id: ticketId,
                    nhaxemyduyen_cancel_nonce: nonce
                },
                success: function(response) {
                    console.log('Cancel ticket response:', response);
                    if (response.success) {
                        showMessage(response.data.message, 'green');
                        reloadTicketTable();
                    } else {
                        showMessage(response.data.message, 'red');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Cancel ticket AJAX error:', textStatus, errorThrown);
                    showMessage('Lỗi kết nối máy chủ khi hủy vé.', 'red');
                }
            });
        });

        // Xử lý URL cho nút Xuất Excel
        function updateExportExcelUrl() {
            const formData = $('#nhaxe-filter-form').serialize();
            const baseUrl = $('#nhaxe-export-excel').data('base-url');
            const exportUrl = baseUrl + '&' + formData;
            $('#nhaxe-export-excel').attr('href', exportUrl);
        }

        // Cập nhật URL khi form thay đổi
        $('#nhaxe-filter-form input, #nhaxe-filter-form select').on('change', function() {
            updateExportExcelUrl();
        });

        // Cập nhật URL khi gửi form
        $('#nhaxe-filter-form').on('submit', function(e) {
            e.preventDefault();
            console.log('Submitting filter form');
            updateExportExcelUrl(); // Cập nhật URL trước khi reload bảng
            reloadTicketTable();
        });

        // Cập nhật URL khi trang tải
        updateExportExcelUrl();

        // Xử lý bộ lọc vé qua AJAX
        $('#nhaxe-filter-form').on('submit', function(e) {
            e.preventDefault();
            console.log('Submitting filter form');
            reloadTicketTable();
        });

        // Hiển thị tất cả vé khi tải trang nếu không có bộ lọc
        if (!$('#filter_customer_phone').val() && !$('#filter_status').val() && !$('#filter_departure_date').val() && 
            !$('#filter_from_location').val() && !$('#filter_to_location').val() && 
            !$('#filter_pickup_location').val() && !$('#filter_dropoff_location').val() && 
            !$('#filter_driver').val() && !$('#filter_vehicle').val()) {
            reloadTicketTable();
        }
    });
    </script>
    <?php
}

// Action để xử lý xuất file Excel
add_action('admin_post_nhaxemyduyen_export_tickets', 'nhaxemyduyen_export_tickets');
function nhaxemyduyen_export_tickets() {
    // Kiểm tra quyền truy cập
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền truy cập.');
    }

    // Kiểm tra nonce
    if (!isset($_GET['nhaxemyduyen_export_nonce']) || !wp_verify_nonce($_GET['nhaxemyduyen_export_nonce'], 'nhaxemyduyen_export_nonce')) {
        wp_die('Lỗi bảo mật: Nonce không hợp lệ.');
    }

    // Phần còn lại của hàm giữ nguyên
    global $wpdb;
    $table_tickets = $wpdb->prefix . 'tickets';
    $table_trips = $wpdb->prefix . 'trips';
    $table_locations = $wpdb->prefix . 'locations';
    $table_drivers = $wpdb->prefix . 'drivers';
    $table_vehicles = $wpdb->prefix . 'vehicles';
    $table_routes = $wpdb->prefix . 'routes';

    // Lấy dữ liệu lọc từ GET
    $filter_customer_phone = isset($_GET['filter_customer_phone']) ? sanitize_text_field($_GET['filter_customer_phone']) : '';
    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
    $filter_departure_date = isset($_GET['filter_departure_date']) ? sanitize_text_field($_GET['filter_departure_date']) : '';
    $filter_from_location = isset($_GET['filter_from_location']) ? intval($_GET['filter_from_location']) : 0;
    $filter_to_location = isset($_GET['filter_to_location']) ? intval($_GET['filter_to_location']) : 0;
    $filter_pickup_location = isset($_GET['filter_pickup_location']) ? sanitize_text_field($_GET['filter_pickup_location']) : '';
    $filter_dropoff_location = isset($_GET['filter_dropoff_location']) ? sanitize_text_field($_GET['filter_dropoff_location']) : '';
    $filter_driver = isset($_GET['filter_driver']) ? intval($_GET['filter_driver']) : 0;
    $filter_vehicle = isset($_GET['filter_vehicle']) ? intval($_GET['filter_vehicle']) : 0;
    $filter_trip_id = isset($_GET['filter_trip_id']) ? intval($_GET['filter_trip_id']) : 0;

    if (!empty($filter_departure_date)) {
        $date = DateTime::createFromFormat('m/d/Y', $filter_departure_date);
        if ($date) {
            $filter_departure_date = $date->format('Y-m-d');
        } else {
            $filter_departure_date = '';
        }
    }

    $where_conditions = [];
    $params = [];
    if (!empty($filter_customer_phone)) {
        $where_conditions[] = 't.customer_phone LIKE %s';
        $params[] = '%' . $filter_customer_phone . '%';
    }
    if (!empty($filter_status)) {
        $where_conditions[] = 't.status = %s';
        $params[] = $filter_status;
    }
    if (!empty($filter_departure_date)) {
        $where_conditions[] = 'DATE(tr.departure_time) = %s';
        $params[] = $filter_departure_date;
    } else {
        $where_conditions[] = 'tr.departure_time >= CURDATE()';
    }
    if ($filter_from_location > 0) {
        $where_conditions[] = 'r.from_location_id = %d';
        $params[] = $filter_from_location;
    }
    if ($filter_to_location > 0) {
        $where_conditions[] = 'r.to_location_id = %d';
        $params[] = $filter_to_location;
    }
    if (!empty($filter_pickup_location)) {
        $where_conditions[] = 't.pickup_location = %s';
        $params[] = $filter_pickup_location;
    }
    if (!empty($filter_dropoff_location)) {
        $where_conditions[] = 't.dropoff_location = %s';
        $params[] = $filter_dropoff_location;
    }
    if ($filter_driver > 0) {
        $where_conditions[] = 'tr.driver_id = %d';
        $params[] = $filter_driver;
    }
    if ($filter_vehicle > 0) {
        $where_conditions[] = 'tr.vehicle_id = %d';
        $params[] = $filter_vehicle;
    }
    if ($filter_trip_id > 0) {
        $where_conditions[] = 't.trip_id = %d';
        $params[] = $filter_trip_id;
    }

    $where_clause = !empty($where_conditions) ? ' WHERE ' . implode(' AND ', $where_conditions) : '';
    $query = $wpdb->prepare("
        SELECT t.*, tr.departure_time, tr.pickup_location as trip_pickup_location, tr.dropoff_location as trip_dropoff_location, 
               l1.name as from_location, l2.name as to_location, d.name as driver_name, v.license_plate as vehicle_plate
        FROM $table_tickets t
        JOIN $table_trips tr ON t.trip_id = tr.trip_id
        JOIN $table_routes r ON tr.route_id = r.route_id
        JOIN $table_locations l1 ON r.from_location_id = l1.location_id
        JOIN $table_locations l2 ON r.to_location_id = l2.location_id
        LEFT JOIN $table_drivers d ON tr.driver_id = d.driver_id
        LEFT JOIN $table_vehicles v ON tr.vehicle_id = v.vehicle_id
        $where_clause
        ORDER BY tr.departure_time DESC
    ", $params);

    $tickets = $wpdb->get_results($query, ARRAY_A);

    if (empty($tickets)) {
        wp_die('Không có vé nào để xuất.');
    }

    // Tạo file Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Danh Sach Ve Xe');

    // Định nghĩa tiêu đề
    $headers = ['Mã vé', 'Khách hàng', 'Số điện thoại', 'Email', 'Điểm đi', 'Điểm đến', 'Điểm đón', 'Điểm trả', 'Tài xế', 'Phương tiện', 'Giờ đi', 'Số ghế', 'Trạng thái', 'Ghi chú'];
    $column = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($column . '1', $header);
        $sheet->getColumnDimension($column)->setAutoSize(true);
        $column++;
    }

    // Điền dữ liệu
    $row = 2;
    foreach ($tickets as $ticket) {
        $sheet->setCellValue('A' . $row, $ticket['ticket_code']);
        $sheet->setCellValue('B' . $row, $ticket['customer_name']);
        $sheet->setCellValue('C' . $row, $ticket['customer_phone']);
        $sheet->setCellValue('D' . $row, $ticket['customer_email']);
        $sheet->setCellValue('E' . $row, $ticket['from_location']);
        $sheet->setCellValue('F' . $row, $ticket['to_location']);
        $sheet->setCellValue('G' . $row, $ticket['pickup_location'] ?: $ticket['trip_pickup_location']);
        $sheet->setCellValue('H' . $row, $ticket['dropoff_location'] ?: $ticket['trip_dropoff_location']);
        $sheet->setCellValue('I' . $row, $ticket['driver_name'] ?: 'Chưa chọn');
        $sheet->setCellValue('J' . $row, $ticket['vehicle_plate'] ?: 'Chưa chọn');
        $sheet->setCellValue('K' . $row, date('m/d/Y H:i', strtotime($ticket['departure_time'])));
        $sheet->setCellValue('L' . $row, $ticket['seat_number']);
        $sheet->setCellValue('M' . $row, $ticket['status']);
        $sheet->setCellValue('N' . $row, $ticket['note']);
        $row++;
    }

    // Xuất file
    $filename = 'danh_sach_ve_' . date('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Xử lý AJAX
add_action('wp_ajax_nhaxemyduyen_manage_tickets', 'nhaxemyduyen_manage_tickets');



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

    // Đăng ký jQuery (nếu chưa có)
    wp_enqueue_script('jquery');

    // Xử lý bộ lọc tìm kiếm
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

    // Lấy danh sách người dùng từ cơ sở dữ liệu
    $users = $wpdb->get_results("SELECT * FROM $table_users $where_clause", ARRAY_A);

    // Lọc theo vai trò
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

    // Xử lý thông báo
    $message = '';
    if (isset($_GET['message'])) {
        if ($_GET['message'] === 'delete_success') {
            $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">Xóa người dùng thành công!</div>';
        } elseif ($_GET['message'] === 'delete_error') {
            $message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">Lỗi: Không thể xóa tài khoản của chính bạn!</div>';
        }
    }

    // Lấy tất cả vai trò có thể chỉnh sửa
    $roles = get_editable_roles();

    ?>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Quản lý Người Dùng</h1>
        <div id="message-area"><?php echo $message; ?></div>

        <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Tìm kiếm Người Dùng</h2>
            <form method="post" action="" class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-4 mb-6" id="search-form">
                <input type="text" name="filter_username" id="filter_username" value="<?php echo esc_attr($filter_username); ?>" placeholder="Tên đăng nhập" autocomplete="username" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                <input type="text" name="filter_email" id="filter_email" value="<?php echo esc_attr($filter_email); ?>" placeholder="Email" autocomplete="email" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                <select name="filter_role" id="filter_role" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Chọn vai trò --</option>
                    <?php foreach ($roles as $role_key => $role_data) : ?>
                        <option value="<?php echo esc_attr($role_key); ?>" <?php selected($filter_role, $role_key); ?>>
                            <?php echo esc_html($role_data['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">Tìm kiếm</button>
            </form>
        </div>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Danh sách Người Dùng</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200" id="user-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ảnh Đại Diện</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Họ Tên</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số Điện Thoại</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vai Trò</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($users)) : ?>
                            <tr>
                                <td colspan="7" class="px-4 py-3 text-sm text-gray-500 text-center">Không tìm thấy người dùng nào.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($users as $user) : ?>
                                <?php 
                                $user_info = get_userdata($user['ID']);
                                $first_name = get_user_meta($user['ID'], 'first_name', true);
                                $last_name = get_user_meta($user['ID'], 'last_name', true);
                                $phone_number = get_user_meta($user['ID'], 'phone_number', true);
                                $avatar_url = get_user_meta($user['ID'], 'avatar_url', true);
                                $full_name = trim($first_name . ' ' . $last_name) ?: $user['user_login'];
                                ?>
                                <tr class="hover:bg-gray-50" data-user-id="<?php echo $user['ID']; ?>">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?php if ($avatar_url) : ?>
                                            <img src="<?php echo esc_url($avatar_url); ?>" alt="Ảnh đại diện" class="w-10 h-10 rounded-full object-cover">
                                        <?php else : ?>
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-indigo-400 to-purple-400 flex items-center justify-center text-white text-sm font-bold">
                                                <?php echo esc_html(substr($full_name, 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($user['ID']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($full_name); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($user['user_email']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($phone_number ?: 'Chưa có'); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html(implode(', ', $user_info->roles)); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <button onclick="document.getElementById('edit-user-<?php echo $user['ID']; ?>').classList.remove('hidden')" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 transition mr-2">Sửa</button>
                                        <button class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition delete-user" 
                                                data-user-id="<?php echo $user['ID']; ?>" 
                                                data-nonce="<?php echo wp_create_nonce('nhaxemyduyen_delete_user'); ?>">Xóa</button>
                                    </td>
                                </tr>
                                <!-- Form chỉnh sửa -->
                                <tr id="edit-user-<?php echo $user['ID']; ?>" class="hidden">
                                    <td colspan="7" class="px-4 py-4 bg-gray-50">
                                        <form method="post" enctype="multipart/form-data" class="space-y-4 user-edit-form" data-user-id="<?php echo $user['ID']; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $user['ID']; ?>">
                                            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('nhaxemyduyen_update_profile'); ?>">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Họ</label>
                                                    <input type="text" name="first_name" value="<?php echo esc_attr($first_name); ?>" class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-400 bg-gray-50 hover:bg-gray-100" required autocomplete="given-name">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên</label>
                                                    <input type="text" name="last_name" value="<?php echo esc_attr($last_name); ?>" class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-400 bg-gray-50 hover:bg-gray-100" required autocomplete="family-name">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                                <input type="email" name="email" value="<?php echo esc_attr($user['user_email']); ?>" class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-400 bg-gray-50 hover:bg-gray-100" required autocomplete="email">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                                                <input type="tel" name="phone_number" value="<?php echo esc_attr($phone_number); ?>" class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-400 bg-gray-50 hover:bg-gray-100" required autocomplete="tel">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Ảnh đại diện</label>
                                                <input type="file" name="avatar" accept="image/*" class="w-full p-3 border border-gray-200 rounded-lg bg-gray-50">
                                                <input type="hidden" name="avatar_user_id" value="<?php echo $user['ID']; ?>">
                                                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('nhaxemyduyen_upload_avatar'); ?>">
                                            </div>
                                            <div class="flex space-x-4">
                                                <button type="submit" name="update_profile" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">Cập nhật</button>
                                                <button type="button" onclick="document.getElementById('edit-user-<?php echo $user['ID']; ?>').classList.add('hidden')" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">Hủy</button>
                                            </div>
                                            <div class="form-message"></div>
                                        </form>
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
            // Xử lý gửi biểu mẫu để cập nhật hồ sơ và ảnh đại diện
            $('.user-edit-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var userId = form.data('user-id');
                var formData = new FormData(this);
                var messageArea = form.find('.form-message');
                var updateButton = form.find('button[name="update_profile"]');

                // Kiểm tra dữ liệu trước khi gửi
                var dataToSend = {
                    user_id: formData.get('user_id'),
                    first_name: formData.get('first_name'),
                    last_name: formData.get('last_name'),
                    email: formData.get('email'),
                    phone_number: formData.get('phone_number'),
                    nonce: formData.get('nonce')
                };

                if (!dataToSend.user_id || !dataToSend.first_name || !dataToSend.last_name || !dataToSend.email || !dataToSend.phone_number) {
                    messageArea.html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">Lỗi: Vui lòng điền đầy đủ thông tin bắt buộc.</div>');
                    return;
                }

                // Vô hiệu hóa nút để tránh gửi nhiều lần
                updateButton.prop('disabled', true).text('Đang cập nhật...');

                // Gửi yêu cầu AJAX để cập nhật hồ sơ
                $.ajax({
                    url: '<?php echo rest_url('custom/v1/update-profile'); ?>',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(dataToSend),
                    headers: {
                        'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                    },
                    success: function(response) {
                        messageArea.html('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">Cập nhật hồ sơ thành công!</div>');
                        // Cập nhật dữ liệu trong bảng
                        var row = form.closest('tr').prev();
                        row.find('td:nth-child(3)').text(formData.get('first_name') + ' ' + formData.get('last_name'));
                        row.find('td:nth-child(4)').text(formData.get('email'));
                        row.find('td:nth-child(5)').text(formData.get('phone_number') || 'Chưa có');
                        setTimeout(function() {
                            form.closest('tr').addClass('hidden');
                            messageArea.empty();
                        }, 2000);
                    },
                    error: function(xhr) {
                        console.log('Lỗi cập nhật: ', xhr);
                        var errorMessage = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Cập nhật thất bại (Mã lỗi: ' + xhr.status + ').';
                        messageArea.html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">Lỗi: ' + errorMessage + '</div>');
                    },
                    complete: function() {
                        updateButton.prop('disabled', false).text('Cập nhật');
                    }
                });

                // Xử lý tải ảnh đại diện nếu có tệp được chọn
                if (formData.get('avatar') && formData.get('avatar').size > 0) {
                    var avatarData = new FormData();
                    avatarData.append('user_id', formData.get('user_id'));
                    avatarData.append('avatar', formData.get('avatar'));
                    avatarData.append('nonce', formData.get('nonce'));

                    $.ajax({
                        url: '<?php echo rest_url('custom/v1/upload-avatar'); ?>',
                        method: 'POST',
                        data: avatarData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                        },
                        success: function(response) {
                            messageArea.append('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">Tải ảnh đại diện thành công!</div>');
                            // Cập nhật ảnh đại diện trong bảng
                            var row = form.closest('tr').prev();
                            row.find('td:nth-child(1)').html('<img src="' + response.avatar_url + '" alt="Ảnh đại diện" class="w-10 h-10 rounded-full object-cover">');
                            setTimeout(function() {
                                form.closest('tr').addClass('hidden');
                                messageArea.empty();
                            }, 2000);
                        },
                        error: function(xhr) {
                            console.log('Lỗi tải ảnh: ', xhr);
                            var errorMessage = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Tải ảnh thất bại (Mã lỗi: ' + xhr.status + ').';
                            messageArea.append('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">Lỗi: ' + errorMessage + '</div>');
                        }
                    });
                }
            });

            // Xử lý xóa người dùng bằng AJAX
            $('.delete-user').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var userId = button.data('user-id');
                var nonce = button.data('nonce');
                var row = button.closest('tr');
                var messageArea = $('#message-area');

                if (!confirm('Bạn có chắc chắn muốn xóa người dùng này?')) {
                    return;
                }

                // Ghi log để gỡ lỗi
                console.log('Yêu cầu xóa - user_id: ' + userId + ', nonce: ' + nonce);

                // Vô hiệu hóa nút để tránh nhấp nhiều lần
                button.prop('disabled', true).text('Đang xóa...');

                $.ajax({
                    url: '<?php echo rest_url('custom/v1/delete-user'); ?>',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        nonce: nonce
                    },
                    headers: {
                        'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                    },
                    success: function(response) {
                        console.log('Xóa thành công: ', response);
                        messageArea.html('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">Xóa người dùng thành công!</div>');
                        row.remove();
                        setTimeout(function() {
                            messageArea.empty();
                        }, 2000);
                    },
                    error: function(xhr) {
                        console.log('Lỗi xóa: ', xhr);
                        var errorMessage = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Xóa thất bại (Mã lỗi: ' + xhr.status + ').';
                        messageArea.html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">Lỗi: ' + errorMessage + '</div>');
                        button.prop('disabled', false).text('Xóa');
                    }
                });
            });
        });
    </script>
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
                <input type="tel" name="phone_number" id="phone_number" value="<?php echo esc_attr(get_user_meta($user->ID, 'phone_number', true)); ?>" class="regular-text" autocomplete="tel">
            </td>
        </tr>
    </table>
    <?php
}

// Lưu trường số điện thoại
add_action('personal_options_update', 'nhaxemyduyen_save_phone_field');
add_action('edit_user_profile_update', 'nhaxemyduyen_save_phone_field');

function nhaxemyduyen_save_phone_field($user_id) {
    // Kiểm tra quyền chỉnh sửa người dùng
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    if (isset($_POST['phone_number'])) {
        update_user_meta($user_id, 'phone_number', sanitize_text_field($_POST['phone_number']));
    }
}


// trang thống kê
function nhaxemyduyen_stats() {
    global $wpdb;
    $table_tickets = $wpdb->prefix . 'tickets';
    $table_trips = $wpdb->prefix . 'trips';

    // Đăng ký Chart.js
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);

    // Xử lý Tìm kiếm theo ngày/tháng
    $filter_type = isset($_POST['filter_type']) ? $_POST['filter_type'] : 'day';
    $filter_value = isset($_POST['filter_value']) ? $_POST['filter_value'] : ($filter_type === 'day' ? date('Y-m-d') : date('Y-m'));

    // Xác định điều kiện lọc dựa trên loại
    if ($filter_type === 'day') {
        $date_condition = "DATE(tr.departure_time) = %s";
        $filter_value = date('Y-m-d', strtotime($filter_value));
        $prev_filter_value = date('Y-m-d', strtotime($filter_value . ' -1 day'));
    } else {
        $date_condition = "DATE_FORMAT(tr.departure_time, '%Y-%m') = %s";
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
        JOIN $table_trips tr ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND $date_condition
    ", $filter_value)) ?: 0;

    // Thống kê số chuyến xe
    $trip_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT tr.trip_id)
        FROM $table_trips tr
        JOIN $table_tickets t ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND $date_condition
    ", $filter_value)) ?: 0;

    // Thống kê tổng số ghế khả dụng
    $total_seats = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(tr.available_seats)
        FROM $table_trips tr
        JOIN $table_tickets t ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND $date_condition
    ", $filter_value)) ?: ($trip_count * 44);

    // Tính phần trăm vé bán ra
    $ticket_percentage = $total_seats > 0 ? round(($ticket_count / $total_seats) * 100, 2) : 0;

    // Thống kê so sánh với kỳ trước
    $prev_revenue = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(tr.price)
        FROM $table_tickets t
        JOIN $table_trips tr ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND " . str_replace($filter_value, $prev_filter_value, $date_condition),
        $prev_filter_value)) ?: 0;

    $prev_ticket_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM $table_tickets t
        JOIN $table_trips tr ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND " . str_replace($filter_value, $prev_filter_value, $date_condition),
        $prev_filter_value)) ?: 0;

    $prev_trip_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT tr.trip_id)
        FROM $table_trips tr
        JOIN $table_tickets t ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND " . str_replace($filter_value, $prev_filter_value, $date_condition),
        $prev_filter_value)) ?: 0;

    $prev_total_seats = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(tr.available_seats)
        FROM $table_trips tr
        JOIN $table_tickets t ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND " . str_replace($filter_value, $prev_filter_value, $date_condition),
        $prev_filter_value)) ?: ($prev_trip_count * 44);

    $prev_ticket_percentage = $prev_total_seats > 0 ? round(($prev_ticket_count / $prev_total_seats) * 100, 2) : 0;

    // Tính phần trăm thay đổi
    $revenue_change = $prev_revenue > 0 ? round((($revenue - $prev_revenue) / $prev_revenue) * 100, 2) : ($revenue > 0 ? 100 : 0);
    $ticket_percentage_change = $prev_ticket_percentage > 0 ? round((($ticket_percentage - $prev_ticket_percentage) / $prev_ticket_percentage) * 100, 2) : ($ticket_percentage > 0 ? 100 : 0);

    ?>
    <div class="wrap nhaxe-wrap">
        <h1 class="nhaxe-title">Thống kê</h1>

        <!-- Card chứa form Tìm kiếm và thống kê -->
        <div class="nhaxe-card">
            <h2>Kết quả Thống kê</h2>

            <!-- Form Tìm kiếm -->
            <div class="nhaxe-filter-add-container">
                <form method="post" action="" class="nhaxe-filter-form" id="nhaxe-stats-filter-form">
                    <div class="nhaxe-filter-group">
                        <select name="filter_type" id="filter_type" onchange="this.form.submit()">
                            <option value="day" <?php selected($filter_type, 'day'); ?>>Theo ngày</option>
                            <option value="month" <?php selected($filter_type, 'month'); ?>>Theo tháng</option>
                        </select>
                        <?php if ($filter_type === 'day') : ?>
                            <input type="date" name="filter_value" id="filter_value" value="<?php echo esc_attr($filter_value); ?>" max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                        <?php else : ?>
                            <input type="month" name="filter_value" id="filter_value" value="<?php echo esc_attr($filter_value); ?>" max="<?php echo date('Y-m', strtotime('+1 year')); ?>">
                        <?php endif; ?>
                        <input type="submit" class="button nhaxe-button-primary" value="Tìm kiếm">
                        <a href="#" id="nhaxe-export-excel" class="button nhaxe-button-secondary" 
                           data-base-url="<?php echo admin_url('admin-post.php?action=nhaxemyduyen_export_stats&nhaxemyduyen_export_nonce=' . wp_create_nonce('nhaxemyduyen_export_nonce')); ?>">
                            Xuất Excel
                        </a>
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
        .nhaxe-button-secondary {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            margin-left: 10px;
            cursor: pointer;
        }
        .nhaxe-button-secondary:hover {
            background-color: #45a049;
        }
    </style>

    <!-- Script để vẽ biểu đồ và xử lý URL xuất Excel -->
    <script>
        jQuery(document).ready(function($) {
            // Vẽ biểu đồ (giữ nguyên logic cũ)
            var ctx = document.getElementById('nhaxeRevenueChart').getContext('2d');
            var revenueChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php
                        if ($filter_type === 'day') {
                            $labels = [];
                            for ($i = 0; $i < 24; $i++) {
                                $labels[] = sprintf("%02d:00", $i);
                            }
                            echo "'" . implode("','", $labels) . "'";
                        } else {
                            $days_in_month = date('t', strtotime($filter_value . '-01'));
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
                                            AND $date_condition
                                        ", $hour_start, $hour_end, $filter_value)) ?: 0;
                                        $data[] = $revenue;
                                    }
                                    echo implode(',', $data);
                                } else {
                                    $days_in_month = date('t', strtotime($filter_value . '-01'));
                                    $data = [];
                                    for ($i = 1; $i <= $days_in_month; $i++) {
                                        $day = sprintf("%s-%02d", $filter_value, $i);
                                        $revenue = $wpdb->get_var($wpdb->prepare("
                                            SELECT SUM(tr.price)
                                            FROM $table_tickets t
                                            JOIN $table_trips tr ON t.trip_id = tr.trip_id
                                            WHERE t.status = 'Đã thanh toán'
                                            AND DATE(tr.departure_time) = %s
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
                                if ($filter_type === 'day') {
                                    $data = [];
                                    for ($i = 0; $i < 24; $i++) {
                                        $hour_start = sprintf("%s %02d:00:00", $filter_value, $i);
                                        $hour_end = sprintf("%s %02d:59:59", $filter_value, $i);
                                        $ticket_count = $wpdb->get_var($wpdb->prepare("
                                            SELECT COUNT(*)
                                            FROM $table_tickets t
                                            JOIN $table_trips tr ON t.trip_id = tr.trip_id
                                            WHERE t.status = 'Đã thanh toán'
                                            AND t.created_at BETWEEN %s AND %s
                                            AND $date_condition
                                        ", $hour_start, $hour_end, $filter_value)) ?: 0;
                                        $total_seats = $wpdb->get_var($wpdb->prepare("
                                            SELECT SUM(tr.available_seats)
                                            FROM $table_trips tr
                                            JOIN $table_tickets t ON t.trip_id = tr.trip_id
                                            WHERE t.status = 'Đã thanh toán'
                                            AND t.created_at BETWEEN %s AND %s
                                            AND $date_condition
                                        ", $hour_start, $hour_end, $filter_value)) ?: 44;
                                        $percentage = $total_seats > 0 ? round(($ticket_count / $total_seats) * 100, 2) : 0;
                                        $data[] = $percentage;
                                    }
                                    echo implode(',', $data);
                                } else {
                                    $days_in_month = date('t', strtotime($filter_value . '-01'));
                                    $data = [];
                                    for ($i = 1; $i <= $days_in_month; $i++) {
                                        $day = sprintf("%s-%02d", $filter_value, $i);
                                        $ticket_count = $wpdb->get_var($wpdb->prepare("
                                            SELECT COUNT(*)
                                            FROM $table_tickets t
                                            JOIN $table_trips tr ON t.trip_id = tr.trip_id
                                            WHERE t.status = 'Đã thanh toán'
                                            AND DATE(tr.departure_time) = %s
                                        ", $day)) ?: 0;
                                        $total_seats = $wpdb->get_var($wpdb->prepare("
                                            SELECT SUM(tr.available_seats)
                                            FROM $table_trips tr
                                            JOIN $table_tickets t ON t.trip_id = tr.trip_id
                                            WHERE t.status = 'Đã thanh toán'
                                            AND DATE(tr.departure_time) = %s
                                        ", $day)) ?: 44;
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

            // Cập nhật URL cho nút Xuất Excel
            function updateExportExcelUrl() {
                const formData = $('#nhaxe-stats-filter-form').serialize();
                const baseUrl = $('#nhaxe-export-excel').data('base-url');
                const exportUrl = baseUrl + '&' + formData;
                $('#nhaxe-export-excel').attr('href', exportUrl);
            }

            // Cập nhật URL khi form thay đổi
            $('#nhaxe-stats-filter-form input, #nhaxe-stats-filter-form select').on('change', function() {
                updateExportExcelUrl();
            });

            // Cập nhật URL khi gửi form
            $('#nhaxe-stats-filter-form').on('submit', function(e) {
                e.preventDefault();
                updateExportExcelUrl();
                this.submit(); // Gửi form bình thường để cập nhật trang
            });

            // Cập nhật URL khi trang tải
            updateExportExcelUrl();
        });
    </script>
    <?php
}
// Action để xử lý xuất file Excel cho thống kê
add_action('admin_post_nhaxemyduyen_export_stats', 'nhaxemyduyen_export_stats');
function nhaxemyduyen_export_stats() {
    // Kiểm tra quyền truy cập
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền truy cập.');
    }

    // Kiểm tra nonce
    if (!isset($_GET['nhaxemyduyen_export_nonce']) || !wp_verify_nonce($_GET['nhaxemyduyen_export_nonce'], 'nhaxemyduyen_export_nonce')) {
        wp_die('Lỗi bảo mật: Nonce không hợp lệ.');
    }

    

    global $wpdb;
    $table_tickets = $wpdb->prefix . 'tickets';
    $table_trips = $wpdb->prefix . 'trips';

    // Lấy dữ liệu lọc từ GET
    $filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : 'day';
    $filter_value = isset($_GET['filter_value']) ? sanitize_text_field($_GET['filter_value']) : ($filter_type === 'day' ? date('Y-m-d') : date('Y-m'));

    // Xác định điều kiện lọc dựa trên loại
    if ($filter_type === 'day') {
        $date_condition = "DATE(tr.departure_time) = %s";
        $filter_value = date('Y-m-d', strtotime($filter_value));
        $prev_filter_value = date('Y-m-d', strtotime($filter_value . ' -1 day'));
    } else {
        $date_condition = "DATE_FORMAT(tr.departure_time, '%Y-%m') = %s";
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
        JOIN $table_trips tr ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND $date_condition
    ", $filter_value)) ?: 0;

    // Thống kê số chuyến xe
    $trip_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT tr.trip_id)
        FROM $table_trips tr
        JOIN $table_tickets t ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND $date_condition
    ", $filter_value)) ?: 0;

    // Thống kê tổng số ghế khả dụng
    $total_seats = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(tr.available_seats)
        FROM $table_trips tr
        JOIN $table_tickets t ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND $date_condition
    ", $filter_value)) ?: ($trip_count * 44);

    // Tính phần trăm vé bán ra
    $ticket_percentage = $total_seats > 0 ? round(($ticket_count / $total_seats) * 100, 2) : 0;

    // Thống kê so sánh với kỳ trước
    $prev_revenue = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(tr.price)
        FROM $table_tickets t
        JOIN $table_trips tr ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND " . str_replace($filter_value, $prev_filter_value, $date_condition),
        $prev_filter_value)) ?: 0;

    $prev_ticket_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM $table_tickets t
        JOIN $table_trips tr ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND " . str_replace($filter_value, $prev_filter_value, $date_condition),
        $prev_filter_value)) ?: 0;

    $prev_trip_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT tr.trip_id)
        FROM $table_trips tr
        JOIN $table_tickets t ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND " . str_replace($filter_value, $prev_filter_value, $date_condition),
        $prev_filter_value)) ?: 0;

    $prev_total_seats = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(tr.available_seats)
        FROM $table_trips tr
        JOIN $table_tickets t ON t.trip_id = tr.trip_id
        WHERE t.status = 'Đã thanh toán' AND " . str_replace($filter_value, $prev_filter_value, $date_condition),
        $prev_filter_value)) ?: ($prev_trip_count * 44);

    $prev_ticket_percentage = $prev_total_seats > 0 ? round(($prev_ticket_count / $prev_total_seats) * 100, 2) : 0;

    // Tạo file Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Thong Ke');

    // Tiêu đề cột
    $sheet->setCellValue('A1', 'Thống kê');
    $sheet->setCellValue('A2', 'Loại lọc');
    $sheet->setCellValue('B2', $filter_type === 'day' ? 'Theo ngày' : 'Theo tháng');
    $sheet->setCellValue('A3', 'Giá trị lọc');
    $sheet->setCellValue('B3', $filter_value);

    // Dữ liệu thống kê
    $sheet->setCellValue('A5', 'Doanh thu (VNĐ)');
    $sheet->setCellValue('B5', number_format($revenue, 0, ',', '.'));
    $sheet->setCellValue('A6', 'Số vé đã thanh toán');
    $sheet->setCellValue('B6', $ticket_count);
    $sheet->setCellValue('A7', 'Tỷ lệ vé bán ra (%)');
    $sheet->setCellValue('B7', $ticket_percentage . '%');
    $sheet->setCellValue('A8', 'Số chuyến xe');
    $sheet->setCellValue('B8', $trip_count);

    // So sánh với kỳ trước
    $sheet->setCellValue('A10', 'So sánh với kỳ trước');
    $sheet->setCellValue('A11', 'Doanh thu (VNĐ)');
    $sheet->setCellValue('B11', number_format($prev_revenue, 0, ',', '.'));
    $sheet->setCellValue('A12', 'Số vé đã thanh toán');
    $sheet->setCellValue('B12', $prev_ticket_count);
    $sheet->setCellValue('A13', 'Tỷ lệ vé bán ra (%)');
    $sheet->setCellValue('B13', $prev_ticket_percentage . '%');
    $sheet->setCellValue('A14', 'Số chuyến xe');
    $sheet->setCellValue('B14', $prev_trip_count);

    // Thiết lập tiêu đề file
    $filename = 'Thong_ke_' . $filter_type . '_' . str_replace('-', '', $filter_value) . '.xlsx';

    // Xuất file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}




// trang quan lý tài xế
function nhaxemyduyen_manage_drivers() {
    global $wpdb;
    $table_drivers = $wpdb->prefix . 'drivers';
    $table_locations = $wpdb->prefix . 'locations';
    $table_trips = $wpdb->prefix . 'trips';

    // Kiểm tra quyền truy cập
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền truy cập trang này.');
    }

    // Đăng ký script và style
    wp_enqueue_script('jquery');
    wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    wp_enqueue_style('tailwind-css', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    // Lấy danh sách địa điểm
    $locations = $wpdb->get_results("SELECT * FROM $table_locations ORDER BY name", ARRAY_A);

    // Bộ Tìm kiếm
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

    // Lấy danh sách tài xế và số chuyến xe
    $drivers = $wpdb->get_results("
        SELECT d.*, l.name as location_name, 
               COUNT(t.trip_id) as trip_count
        FROM $table_drivers d
        LEFT JOIN $table_locations l ON d.location_id = l.location_id
        LEFT JOIN $table_trips t ON d.driver_id = t.driver_id
        $where_clause
        GROUP BY d.driver_id
        ORDER BY d.created_at DESC
    ", ARRAY_A);

    ?>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Quản lý Tài xế</h1>
        <div id="nhaxe-message"></div>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Danh sách Tài xế</h2>

            <!-- Filter Form and Add Button -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <form method="post" action="" class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-4" id="filter-form">
                    <input type="text" name="filter_name" id="filter_name" value="<?php echo esc_attr($filter_name); ?>" placeholder="Tên tài xế" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <input type="text" name="filter_phone" id="filter_phone" value="<?php echo esc_attr($filter_phone); ?>" placeholder="Số điện thoại" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <select name="filter_location" id="filter_location" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="0">-- Tất cả địa điểm --</option>
                        <?php foreach ($locations as $location) : ?>
                            <option value="<?php echo esc_attr($location['location_id']); ?>" <?php selected($filter_location, $location['location_id']); ?>>
                                <?php echo esc_html($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Tìm kiếm</button>
                </form>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition nhaxe-toggle-form mt-4 sm:mt-0" data-action="add">Thêm Tài xế</button>
            </div>

            <!-- Add/Edit Driver Form -->
            <div class="nhaxe-add-form hidden bg-gray-50 p-6 rounded-lg mb-6">
                <form id="driver-form" action="">
                    <?php wp_nonce_field('nhaxemyduyen_driver_action', 'nhaxemyduyen_driver_nonce'); ?>
                    <input type="hidden" name="nhaxemyduyen_driver_action" id="driver_action" value="add">
                    <input type="hidden" name="driver_id" id="driver_id" value="">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Tên tài xế</label>
                            <input type="text" name="name" id="name" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                            <input type="text" name="phone" id="phone" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="license_number" class="block text-sm font-medium text-gray-700">Số GPLX</label>
                            <input type="text" name="license_number" id="license_number" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="location_id" class="block text-sm font-medium text-gray-700">Địa điểm</label>
                            <select name="location_id" id="location_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Chọn địa điểm --</option>
                                <?php foreach ($locations as $location) : ?>
                                    <option value="<?php echo esc_attr($location['location_id']); ?>">
                                        <?php echo esc_html($location['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Trạng thái</label>
                            <select name="status" id="status" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="Active">Hoạt động</option>
                                <option value="Inactive">Không hoạt động</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="note" class="block text-sm font-medium text-gray-700">Ghi chú</label>
                            <textarea name="note" id="note" rows="4" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex space-x-4">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition" id="submit-driver">Thêm Tài xế</button>
                        <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition nhaxe-toggle-form">Hủy</button>
                    </div>
                </form>
            </div>

            <!-- Drivers Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200" id="drivers-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số điện thoại</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số GPLX</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Địa điểm</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số chuyến xe</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ghi chú</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($drivers)) : ?>
                            <tr>
                                <td colspan="8" class="px-4 py-3 text-sm text-gray-500 text-center">Không có tài xế nào phù hợp với tiêu chí.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($drivers as $driver) : ?>
                                <tr class="hover:bg-gray-50" data-driver-id="<?php echo esc_attr($driver['driver_id']); ?>">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($driver['name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($driver['phone']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($driver['license_number']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($driver['location_name'] ?: 'Chưa có'); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <button class="nhaxe-toggle-trips text-blue-600 hover:underline" data-driver-id="<?php echo esc_attr($driver['driver_id']); ?>">
                                            <?php echo esc_html($driver['trip_count']); ?> chuyến
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <select class="nhaxe-status-select border border-gray-300 rounded-lg px-2 py-1" data-driver-id="<?php echo esc_attr($driver['driver_id']); ?>">
                                            <option value="Active" <?php selected($driver['status'], 'Active'); ?>>Hoạt động</option>
                                            <option value="Inactive" <?php selected($driver['status'], 'Inactive'); ?>>Không hoạt động</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($driver['note']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <button class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition mr-2 nhaxe-toggle-form" data-action="edit" data-driver-id="<?php echo esc_attr($driver['driver_id']); ?>">Sửa</button>
                                        <button class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition nhaxe-delete-driver" data-driver-id="<?php echo esc_attr($driver['driver_id']); ?>" data-nonce="<?php echo wp_create_nonce('nhaxemyduyen_delete_driver'); ?>">Xóa</button>
                                    </td>
                                </tr>
                                <!-- Bảng con chi tiết chuyến xe (ẩn mặc định) -->
                                <tr class="nhaxe-trips-row hidden" data-driver-id="<?php echo esc_attr($driver['driver_id']); ?>">
                                    <td colspan="8" class="px-4 py-3">
                                        <div class="nhaxe-trips-container">
                                            <table class="min-w-full bg-gray-50 border border-gray-200">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã chuyến</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm đi</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm đến</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian khởi hành</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giá vé</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200" id="trips-<?php echo esc_attr($driver['driver_id']); ?>">
                                                    <!-- Dữ liệu sẽ được tải qua AJAX -->
                                                </tbody>
                                            </table>
                                        </div>
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
        .nhaxe-add-form.hidden { display: none; }
        .nhaxe-trips-row.hidden { display: none; }
        table th, table td { white-space: nowrap; }
        .nhaxe-trips-container { margin-top: 10px; }
        .ui-datepicker {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .ui-datepicker-header { background: #f3f4f6; border-radius: 0.25rem; }
        .ui-datepicker-title { font-weight: 600; color: #1f2937; }
        .ui-datepicker-prev, .ui-datepicker-next { cursor: pointer; color: #2563eb; }
        .ui-datepicker-prev:hover, .ui-datepicker-next:hover { background: #e5e7eb; }
        .ui-datepicker-calendar td a { text-align: center; padding: 0.25rem; border-radius: 0.25rem; color: #1f2937; }
        .ui-datepicker-calendar td a:hover { background: #e5e7eb; }
        .ui-state-highlight, .ui-widget-content .ui-state-highlight { background: #2563eb; color: #fff; border-radius: 0.25rem; }
        @media (max-width: 640px) {
            .sm\:flex-row { flex-direction: column; }
            .sm\:space-x-4 { space-x: 0; space-y: 4px; }
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            // Toggle form thêm/sửa
            $(document).on('click', '.nhaxe-toggle-form', function() {
                var action = $(this).data('action');
                var driverId = $(this).data('driver-id');

                if (action === 'add') {
                    $('#driver_action').val('add');
                    $('#driver_id').val('');
                    $('#name').val('');
                    $('#phone').val('');
                    $('#license_number').val('');
                    $('#location_id').val('');
                    $('#status').val('Active');
                    $('#note').val('');
                    $('#submit-driver').text('Thêm Tài xế');
                    $('.nhaxe-add-form').removeClass('hidden');
                } else if (action === 'edit' && driverId) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'nhaxemyduyen_get_driver',
                            driver_id: driverId,
                            nonce: '<?php echo wp_create_nonce('nhaxemyduyen_get_driver'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                var driver = response.data;
                                $('#driver_action').val('edit');
                                $('#driver_id').val(driver.driver_id);
                                $('#name').val(driver.name);
                                $('#phone').val(driver.phone);
                                $('#license_number').val(driver.license_number);
                                $('#location_id').val(driver.location_id);
                                $('#status').val(driver.status);
                                $('#note').val(driver.note);
                                $('#submit-driver').text('Cập nhật Tài xế');
                                $('.nhaxe-add-form').removeClass('hidden');
                            } else {
                                $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Không thể lấy dữ liệu tài xế.</p></div>');
                            }
                        },
                        error: function() {
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra.</p></div>');
                        }
                    });
                } else {
                    $('.nhaxe-add-form').addClass('hidden');
                }
            });

            // Toggle danh sách chuyến xe
            $(document).on('click', '.nhaxe-toggle-trips', function() {
                var driverId = $(this).data('driver-id');
                var $row = $(`.nhaxe-trips-row[data-driver-id="${driverId}"]`);
                var $tbody = $(`#trips-${driverId}`);

                if ($row.hasClass('hidden')) {
                    // Tải dữ liệu chuyến xe qua AJAX
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'nhaxemyduyen_get_driver_trips',
                            driver_id: driverId,
                            nonce: '<?php echo wp_create_nonce('nhaxemyduyen_get_driver_trips'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $tbody.html(response.data.html);
                                $row.removeClass('hidden');
                            } else {
                                $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Không thể tải danh sách chuyến xe.</p></div>');
                            }
                        },
                        error: function() {
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra.</p></div>');
                        }
                    });
                } else {
                    $row.addClass('hidden');
                }
            });

            // Submit form thêm/sửa tài xế qua AJAX
            $('#driver-form').submit(function(e) {
                e.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData + '&action=nhaxemyduyen_manage_driver',
                    success: function(response) {
                        if (response.success) {
                            $('#nhaxe-message').html('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>' + response.data.message + '</p></div>');
                            $('.nhaxe-add-form').addClass('hidden');
                            refreshDriversTable();
                        } else {
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra.</p></div>');
                    }
                });
            });

            // Xóa tài xế qua AJAX
            $(document).on('click', '.nhaxe-delete-driver', function() {
                if (!confirm('Bạn có chắc chắn muốn xóa?')) return;

                var driverId = $(this).data('driver-id');
                var nonce = $(this).data('nonce');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'nhaxemyduyen_delete_driver',
                        driver_id: driverId,
                        nhaxemyduyen_delete_nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#nhaxe-message').html('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>Xóa tài xế thành công!</p></div>');
                            refreshDriversTable();
                        } else {
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra.</p></div>');
                    }
                });
            });

            // Cập nhật trạng thái qua AJAX
            $(document).on('change', '.nhaxe-status-select', function() {
                var driverId = $(this).data('driver-id');
                var status = $(this).val();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'nhaxemyduyen_update_driver_status',
                        driver_id: driverId,
                        status: status,
                        nonce: '<?php echo wp_create_nonce('nhaxemyduyen_update_driver_status'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#nhaxe-message').html('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg"><p>Cập nhật trạng thái thành công!</p></div>');
                            refreshDriversTable();
                        } else {
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra.</p></div>');
                    }
                });
            });

            // Tìm kiếm danh sách tài xế qua AJAX
            $('#filter-form').submit(function(e) {
                e.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData + '&action=nhaxemyduyen_filter_drivers',
                    success: function(response) {
                        if (response.success) {
                            $('#drivers-table tbody').html(response.data.html);
                        } else {
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi khi Tìm kiếm tài xế.</p></div>');
                        }
                    },
                    error: function() {
                        $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra.</p></div>');
                    }
                });
            });

            // Hàm làm mới bảng tài xế
            function refreshDriversTable() {
                var formData = $('#filter-form').serialize();
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData + '&action=nhaxemyduyen_filter_drivers',
                    success: function(response) {
                        if (response.success) {
                            $('#drivers-table tbody').html(response.data.html);
                        } else {
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi khi làm mới danh sách tài xế.</p></div>');
                        }
                    },
                    error: function() {
                        $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Đã có lỗi xảy ra.</p></div>');
                    }
                });
            }
        });
    </script>
    <?php
}

// AJAX lấy dữ liệu tài xế
add_action('wp_ajax_nhaxemyduyen_get_driver', 'nhaxemyduyen_get_driver_callback');
function nhaxemyduyen_get_driver_callback() {
    check_ajax_referer('nhaxemyduyen_get_driver', 'nonce');

    global $wpdb;
    $table_drivers = $wpdb->prefix . 'drivers';
    $driver_id = intval($_POST['driver_id']);

    $driver = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_drivers WHERE driver_id = %d", $driver_id), ARRAY_A);

    if ($driver) {
        wp_send_json_success($driver);
    } else {
        wp_send_json_error(['message' => 'Không tìm thấy tài xế.']);
    }
}

// AJAX thêm/sửa tài xế
add_action('wp_ajax_nhaxemyduyen_manage_driver', 'nhaxemyduyen_manage_driver_callback');
function nhaxemyduyen_manage_driver_callback() {
    check_ajax_referer('nhaxemyduyen_driver_action', 'nhaxemyduyen_driver_nonce');

    global $wpdb;
    $table_drivers = $wpdb->prefix . 'drivers';

    $action = sanitize_text_field($_POST['nhaxemyduyen_driver_action']);
    $driver_id = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : 0;
    $name = sanitize_text_field($_POST['name']);
    $phone = sanitize_text_field($_POST['phone']);
    $license_number = sanitize_text_field($_POST['license_number']);
    $location_id = intval($_POST['location_id']);
    $status = sanitize_text_field($_POST['status']);
    $note = sanitize_textarea_field($_POST['note']);

    if (empty($name) || empty($phone) || empty($license_number) || empty($location_id) || empty($status)) {
        wp_send_json_error(['message' => 'Vui lòng điền đầy đủ các trường bắt buộc.']);
    }

    $driver_data = [
        'name' => $name,
        'phone' => $phone,
        'license_number' => $license_number,
        'location_id' => $location_id,
        'status' => $status,
        'note' => $note,
        'updated_at' => current_time('mysql'),
    ];

    if ($action === 'add') {
        $driver_data['created_at'] = current_time('mysql');
        $result = $wpdb->insert($table_drivers, $driver_data);
        if ($result) {
            wp_send_json_success(['message' => 'Thêm tài xế thành công!']);
        } else {
            wp_send_json_error(['message' => 'Lỗi khi thêm tài xế.']);
        }
    } elseif ($action === 'edit' && $driver_id) {
        $result = $wpdb->update($table_drivers, $driver_data, ['driver_id' => $driver_id]);
        if ($result !== false) {
            wp_send_json_success(['message' => 'Cập nhật tài xế thành công!']);
        } else {
            wp_send_json_error(['message' => 'Lỗi khi cập nhật tài xế.']);
        }
    } else {
        wp_send_json_error(['message' => 'Hành động không hợp lệ.']);
    }
}

// AJAX xóa tài xế
add_action('wp_ajax_nhaxemyduyen_delete_driver', 'nhaxemyduyen_delete_driver_callback');
function nhaxemyduyen_delete_driver_callback() {
    check_ajax_referer('nhaxemyduyen_delete_driver', 'nhaxemyduyen_delete_nonce');

    global $wpdb;
    $table_drivers = $wpdb->prefix . 'drivers';
    $driver_id = intval($_POST['driver_id']);

    $result = $wpdb->delete($table_drivers, ['driver_id' => $driver_id]);
    if ($result) {
        wp_send_json_success(['message' => 'Xóa tài xế thành công!']);
    } else {
        wp_send_json_error(['message' => 'Lỗi khi xóa tài xế.']);
    }
}

// AJAX cập nhật trạng thái tài xế
add_action('wp_ajax_nhaxemyduyen_update_driver_status', 'nhaxemyduyen_update_driver_status_callback');
function nhaxemyduyen_update_driver_status_callback() {
    check_ajax_referer('nhaxemyduyen_update_driver_status', 'nonce');

    global $wpdb;
    $table_drivers = $wpdb->prefix . 'drivers';
    $driver_id = intval($_POST['driver_id']);
    $status = sanitize_text_field($_POST['status']);

    $result = $wpdb->update($table_drivers, ['status' => $status, 'updated_at' => current_time('mysql')], ['driver_id' => $driver_id]);
    if ($result !== false) {
        wp_send_json_success(['message' => 'Cập nhật trạng thái thành công!']);
    } else {
        wp_send_json_error(['message' => 'Lỗi khi cập nhật trạng thái.']);
    }
}

// AJAX Tìm kiếm tài xế
add_action('wp_ajax_nhaxemyduyen_filter_drivers', 'nhaxemyduyen_filter_drivers_callback');
function nhaxemyduyen_filter_drivers_callback() {
    global $wpdb;
    $table_drivers = $wpdb->prefix . 'drivers';
    $table_locations = $wpdb->prefix . 'locations';
    $table_trips = $wpdb->prefix . 'trips';

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

    $drivers = $wpdb->get_results("
        SELECT d.*, l.name as location_name, 
               COUNT(t.trip_id) as trip_count
        FROM $table_drivers d
        LEFT JOIN $table_locations l ON d.location_id = l.location_id
        LEFT JOIN $table_trips t ON d.driver_id = t.driver_id
        $where_clause
        GROUP BY d.driver_id
        ORDER BY d.created_at DESC
    ", ARRAY_A);

    ob_start();
    if (empty($drivers)) {
        echo '<tr><td colspan="8" class="px-4 py-3 text-sm text-gray-500 text-center">Không có tài xế nào phù hợp với tiêu chí.</td></tr>';
    } else {
        foreach ($drivers as $driver) {
            ?>
            <tr class="hover:bg-gray-50" data-driver-id="<?php echo esc_attr($driver['driver_id']); ?>">
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($driver['name']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($driver['phone']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($driver['license_number']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($driver['location_name'] ?: 'Chưa có'); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900">
                    <button class="nhaxe-toggle-trips text-blue-600 hover:underline" data-driver-id="<?php echo esc_attr($driver['driver_id']); ?>">
                        <?php echo esc_html($driver['trip_count']); ?> chuyến
                    </button>
                </td>
                <td class="px-4 py-3 text-sm text-gray-900">
                    <select class="nhaxe-status-select border border-gray-300 rounded-lg px-2 py-1" data-driver-id="<?php echo esc_attr($driver['driver_id']); ?>">
                        <option value="Active" <?php selected($driver['status'], 'Active'); ?>>Hoạt động</option>
                        <option value="Inactive" <?php selected($driver['status'], 'Inactive'); ?>>Không hoạt động</option>
                    </select>
                </td>
                <td class="px-4 py-3 text-sm text-gray-900"><?php echo esc_html($driver['note']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-900">
                    <button class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition mr-2 nhaxe-toggle-form" data-action="edit" data-driver-id="<?php echo esc_attr($driver['driver_id']); ?>">Sửa</button>
                    <button class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition nhaxe-delete-driver" data-driver-id="<?php echo esc_attr($driver['driver_id']); ?>" data-nonce="<?php echo wp_create_nonce('nhaxemyduyen_delete_driver'); ?>">Xóa</button>
                </td>
            </tr>
            <tr class="nhaxe-trips-row hidden" data-driver-id="<?php echo esc_attr($driver['driver_id']); ?>">
                <td colspan="8" class="px-4 py-3">
                    <div class="nhaxe-trips-container">
                        <table class="min-w-full bg-gray-50 border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã chuyến</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm đi</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm đến</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian khởi hành</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giá vé</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200" id="trips-<?php echo esc_attr($driver['driver_id']); ?>">
                                <!-- Dữ liệu sẽ được tải qua AJAX -->
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
            <?php
        }
    }
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
}

add_action('wp_ajax_nhaxemyduyen_get_driver_trips', 'nhaxemyduyen_get_driver_trips_callback');
function nhaxemyduyen_get_driver_trips_callback() {
    check_ajax_referer('nhaxemyduyen_get_driver_trips', 'nonce');

    global $wpdb;
    $table_trips = $wpdb->prefix . 'trips';
    $table_locations = $wpdb->prefix . 'locations';
    $driver_id = intval($_POST['driver_id']);

    // Lấy danh sách chuyến xe của tài xế
    $trips = $wpdb->get_results($wpdb->prepare("
        SELECT t.*, 
               l1.name as departure_name, 
               l2.name as destination_name
        FROM $table_trips t
        LEFT JOIN $table_locations l1 ON t.departure_location_id = l1.location_id
        LEFT JOIN $table_locations l2 ON t.destination_location_id = l2.location_id
        WHERE t.driver_id = %d
        ORDER BY t.departure_time DESC
    ", $driver_id), ARRAY_A);

    ob_start();
    if (empty($trips)) {
        echo '<tr><td colspan="5" class="px-4 py-2 text-sm text-gray-500 text-center">Không có chuyến xe nào.</td></tr>';
    } else {
        foreach ($trips as $trip) {
            ?>
            <tr>
                <td class="px-4 py-2 text-sm text-gray-900"><?php echo esc_html($trip['trip_id']); ?></td>
                <td class="px-4 py-2 text-sm text-gray-900"><?php echo esc_html($trip['departure_name'] ?: 'N/A'); ?></td>
                <td class="px-4 py-2 text-sm text-gray-900"><?php echo esc_html($trip['destination_name'] ?: 'N/A'); ?></td>
                <td class="px-4 py-2 text-sm text-gray-900"><?php echo esc_html(date('d/m/Y H:i', strtotime($trip['departure_time']))); ?></td>
                <td class="px-4 py-2 text-sm text-gray-900"><?php echo esc_html(number_format($trip['price'], 0, ',', '.')) . ' VNĐ'; ?></td>
            </tr>
            <?php
        }
    }
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
}




// Trang quản lý xe
function nhaxemyduyen_manage_vehicles() {
    global $wpdb;
    $table_vehicles = $wpdb->prefix . 'vehicles';
    $table_trips = $wpdb->prefix . 'trips';

    // Kiểm tra quyền truy cập
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền truy cập trang này.');
    }

    // Đăng ký script và style
    wp_enqueue_media();
    wp_enqueue_script('jquery');
    wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    wp_enqueue_style('tailwind-css', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    // Bộ Tìm kiếm
    $filter_license_plate = isset($_POST['filter_license_plate']) ? sanitize_text_field($_POST['filter_license_plate']) : '';
    $filter_type = isset($_POST['filter_type']) ? sanitize_text_field($_POST['filter_type']) : '';

    $where_conditions = [];
    if (!empty($filter_license_plate)) {
        $where_conditions[] = $wpdb->prepare("v.license_plate LIKE %s", '%' . $filter_license_plate . '%');
    }
    if (!empty($filter_type)) {
        $where_conditions[] = $wpdb->prepare("v.type LIKE %s", '%' . $filter_type . '%');
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = " WHERE " . implode(' AND ', $where_conditions);
    }

    // Lấy danh sách xe với số chuyến xe
    $vehicles = $wpdb->get_results("
        SELECT v.*, COUNT(t.trip_id) as trip_count
        FROM $table_vehicles v
        LEFT JOIN $table_trips t ON v.vehicle_id = t.vehicle_id
        $where_clause
        GROUP BY v.vehicle_id
        ORDER BY v.created_at DESC
    ", ARRAY_A);
    if ($wpdb->last_error) {
        echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg"><p>Lỗi truy vấn danh sách xe: ' . esc_html($wpdb->last_error) . '</p></div>';
        return;
    }

    ?>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Quản lý Xe</h1>
        <div id="nhaxe-message"></div>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Danh sách Xe</h2>

            <!-- Filter Form and Add Button -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <form method="post" action="" class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-4" id="filter-form">
                    <input type="text" name="filter_license_plate" id="filter_license_plate" value="<?php echo esc_attr($filter_license_plate); ?>" placeholder="Biển số xe" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <input type="text" name="filter_type" id="filter_type" value="<?php echo esc_attr($filter_type); ?>" placeholder="Loại xe" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Tìm kiếm</button>
                </form>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition nhaxe-toggle-form mt-4 sm:mt-0" data-action="add">Thêm Xe</button>
            </div>

            <!-- Add/Edit Vehicle Form -->
            <div class="nhaxe-add-form hidden bg-gray-50 p-6 rounded-lg mb-6">
                <form id="vehicle-form" action="">
                    <?php wp_nonce_field('nhaxemyduyen_vehicle_action', 'nhaxemyduyen_vehicle_nonce'); ?>
                    <input type="hidden" name="nhaxemyduyen_vehicle_action" id="vehicle_action" value="add">
                    <input type="hidden" name="vehicle_id" id="vehicle_id" value="">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="license_plate" class="block text-sm font-medium text-gray-700">Biển số xe</label>
                            <input type="text" name="license_plate" id="license_plate" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Loại xe</label>
                            <input type="text" name="type" id="type" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="capacity" class="block text-sm font-medium text-gray-700">Số chỗ</label>
                            <input type="number" name="capacity" id="capacity" min="1" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="image" class="block text-sm font-medium text-gray-700">Hình ảnh xe (tùy chọn)</label>
                            <div class="flex items-center space-x-2">
                                <input type="text" name="image" id="image" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <button type="button" class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition nhaxe-upload-button">Chọn</button>
                            </div>
                            <div class="nhaxe-image-preview mt-2"></div>
                        </div>
                    </div>
                    <div class="mt-6 flex space-x-4">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition" id="submit-vehicle">Thêm Xe</button>
                        <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition nhaxe-toggle-form">Hủy</button>
                    </div>
                </form>
            </div>

                <!-- Vehicles Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-md" id="vehicles-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 120px;">Biển số xe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 150px;">Loại xe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 100px;">Số chỗ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 120px;">Số chuyến xe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 150px;">Hình ảnh xe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 180px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($vehicles)) : ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-sm text-gray-500 text-center">Không có xe nào phù hợp với tiêu chí.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($vehicles as $vehicle) : ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap"><?php echo esc_html($vehicle['license_plate']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap"><?php echo esc_html($vehicle['type']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap text-center"><?php echo esc_html($vehicle['capacity']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <a href="#" class="nhaxe-show-trips text-blue-600 hover:underline" data-vehicle-id="<?php echo esc_attr($vehicle['vehicle_id']); ?>">
                                            <?php echo esc_html($vehicle['trip_count']); ?> chuyến
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-center">
                                        <?php if ($vehicle['image']) : ?>
                                            <img src="<?php echo esc_url($vehicle['image']); ?>" alt="Hình ảnh xe" class="max-w-[120px] h-auto rounded-md mx-auto" />
                                        <?php else : ?>
                                            <span class="text-gray-400">Chưa có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 flex space-x-2">
                                        <button class="bg-blue-600 text-white px-3 py-1 rounded-md hover:bg-blue-700 transition mr-2 nhaxe-toggle-form" data-action="edit" data-vehicle-id="<?php echo esc_attr($vehicle['vehicle_id']); ?>">Sửa</button>
                                        <button class="bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 transition nhaxe-delete-vehicle" data-vehicle-id="<?php echo esc_attr($vehicle['vehicle_id']); ?>" data-nonce="<?php echo wp_create_nonce('nhaxemyduyen_delete_vehicle'); ?>">Xóa</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Modal hiển thị danh sách chuyến xe -->
        <div id="trips-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50 transition-opacity duration-300">
            <div class="bg-white rounded-lg p-8 max-w-5xl w-full max-h-[85vh] overflow-y-auto relative shadow-2xl" style="max-height: 85vh !important;">
                <!-- Nút X đóng modal -->
                <button class="nhaxe-close-modal absolute top-4 right-4 text-gray-500 hover:text-gray-700 transition" aria-label="Đóng">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Danh sách chuyến xe</h2>
                <div id="trips-content" class="mb-6"></div>
                <div class="flex justify-end">
                    <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition nhaxe-close-modal">Đóng</button>
                </div>
            </div>
        </div>

        <style>
            .nhaxe-add-form.hidden { display: none; }
            #trips-modal.hidden { display: none; opacity: 0; }
            #trips-modal { opacity: 1; }
            #trips-modal table { width: 100%; border-collapse: collapse; table-layout: fixed; }
            #trips-modal th, #trips-modal td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb; word-wrap: break-word; overflow-wrap: break-word; }
            #trips-modal th { background-color: #f9fafb; font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; }
            #trips-modal td { font-size: 0.875rem; color: #1f2937; }
            #trips-modal tr:hover { background-color: #f3f4f6; }
            #trips-modal .overflow-x-auto { max-width: 100%; overflow-x: hidden; }
            /* Style thanh cuộn */
            #trips-modal::-webkit-scrollbar { width: 10px; height: 10px; }
            #trips-modal::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 5px; }
            #trips-modal::-webkit-scrollbar-thumb { background: #6b7280; border-radius: 5px; }
            #trips-modal::-webkit-scrollbar-thumb:hover { background: #4b5563; }
            #trips-modal { scrollbar-width: thin; scrollbar-color: #6b7280 #f1f1f1; }
            .nhaxe-image-preview img { max-width: 200px; border-radius: 0.5rem; }
            .ui-datepicker {
                background: #fff;
                border: 1px solid #d1d5db;
                border-radius: 0.5rem;
                padding: 0.5rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .ui-datepicker-header { background: #f3f4f6; border-radius: 0.25rem; }
            .ui-datepicker-title { font-weight: 600; color: #1f2937; }
            .ui-datepicker-prev, .ui-datepicker-next { cursor: pointer; color: #2563eb; }
            .ui-datepicker-prev:hover, .ui-datepicker-next:hover { background: #e5e7eb; }
            .ui-datepicker-calendar td a { text-align: center; padding: 0.25rem; border-radius: 0.25rem; color: #1f2937; }
            .ui-datepicker-calendar td a:hover { background: #e5e7eb; }
            .ui-state-highlight, .ui-widget-content .ui-state-highlight { background: #2563eb; color: #fff; border-radius: 0.25rem; }
            @media (max-width: 640px) {
                .sm\:flex-row { flex-direction: column; }
                .sm\:space-x-4 { space-x: 0; space-y: 4px; }
                #trips-modal th, #trips-modal td { padding: 8px; font-size: 0.75rem; }
            }
            @keyframes slideIn {
                from { transform: translateY(-20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            .animate-slide-in { animation: slideIn 0.3s ease-in-out; }
        </style>

        <script>
            jQuery(document).ready(function($) {
                // Toggle form thêm/sửa
                $(document).on('click', '.nhaxe-toggle-form', function() {
                    var action = $(this).data('action');
                    var vehicleId = $(this).data('vehicle-id');

                    if (action === 'add') {
                        $('#vehicle_action').val('add');
                        $('#vehicle_id').val('');
                        $('#license_plate').val('');
                        $('#type').val('');
                        $('#capacity').val('');
                        $('#image').val('');
                        $('.nhaxe-image-preview').html('');
                        $('#submit-vehicle').text('Thêm Xe');
                        $('.nhaxe-add-form').removeClass('hidden');
                    } else if (action === 'edit' && vehicleId) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'nhaxemyduyen_get_vehicle',
                                vehicle_id: vehicleId,
                                nhaxemyduyen_vehicle_nonce: '<?php echo wp_create_nonce('nhaxemyduyen_vehicle_action'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    var vehicle = response.data;
                                    $('#vehicle_action').val('edit');
                                    $('#vehicle_id').val(vehicle.vehicle_id);
                                    $('#license_plate').val(vehicle.license_plate);
                                    $('#type').val(vehicle.type);
                                    $('#capacity').val(vehicle.capacity);
                                    $('#image').val(vehicle.image);
                                    $('.nhaxe-image-preview').html(vehicle.image ? '<img src="' + vehicle.image + '" alt="Hình ảnh xe" class="max-w-[200px] rounded-lg">' : '');
                                    $('#submit-vehicle').text('Cập nhật Xe');
                                    $('.nhaxe-add-form').removeClass('hidden');
                                } else {
                                    $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg animate-slide-in"><p>' + response.data.message + '</p></div>');
                                }
                            },
                            error: function(xhr) {
                                console.error('Lỗi AJAX (get_vehicle):', xhr);
                                $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg animate-slide-in"><p>Đã có lỗi xảy ra: ' + xhr.statusText + '</p></div>');
                            }
                        });
                    } else {
                        $('.nhaxe-add-form').addClass('hidden');
                    }
                });

                // Media Uploader
                $('.nhaxe-upload-button').click(function(e) {
                    e.preventDefault();
                    var image = wp.media({
                        title: 'Chọn hình ảnh xe',
                        multiple: false
                    }).open().on('select', function() {
                        var uploaded_image = image.state().get('selection').first();
                        var image_url = uploaded_image.toJSON().url;
                        $('#image').val(image_url);
                        $('.nhaxe-image-preview').html('<img src="' + image_url + '" alt="Hình ảnh xe" class="max-w-[200px] rounded-lg">');
                    });
                });

                // Submit form thêm/sửa xe qua AJAX
                $('#vehicle-form').submit(function(e) {
                    e.preventDefault();
                    var formData = $(this).serialize();

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: formData + '&action=nhaxemyduyen_manage_vehicle',
                        success: function(response) {
                            if (response.success) {
                                $('#nhaxe-message').html('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg animate-slide-in"><p>' + response.data.message + '</p></div>');
                                $('.nhaxe-add-form').addClass('hidden');
                                refreshVehiclesTable();
                            } else {
                                $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg animate-slide-in"><p>' + response.data.message + '</p></div>');
                            }
                        },
                        error: function(xhr) {
                            console.error('Lỗi AJAX (manage_vehicle):', xhr);
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg animate-slide-in"><p>Đã có lỗi xảy ra: ' + xhr.statusText + '</p></div>');
                        }
                    });
                });

                // Xóa xe qua AJAX
                $(document).on('click', '.nhaxe-delete-vehicle', function() {
                    if (!confirm('Bạn có chắc chắn muốn xóa?')) return;

                    var vehicleId = $(this).data('vehicle-id');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'nhaxemyduyen_delete_vehicle',
                            vehicle_id: vehicleId,
                            nhaxemyduyen_vehicle_nonce: '<?php echo wp_create_nonce('nhaxemyduyen_vehicle_action'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#nhaxe-message').html('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg animate-slide-in"><p>' + response.data.message + '</p></div>');
                                refreshVehiclesTable();
                            } else {
                                $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg animate-slide-in"><p>' + response.data.message + '</p></div>');
                            }
                        },
                        error: function(xhr) {
                            console.error('Lỗi AJAX (delete_vehicle):', xhr);
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg animate-slide-in"><p>Đã có lỗi xảy ra: ' + xhr.statusText + '</p></div>');
                        }
                    });
                });

                // Hiển thị danh sách chuyến xe qua AJAX
                $(document).on('click', '.nhaxe-show-trips', function(e) {
                    e.preventDefault();
                    var vehicleId = $(this).data('vehicle-id');
                    console.log('Opening modal for vehicle ID:', vehicleId); // Debug

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'nhaxemyduyen_get_trips',
                            vehicle_id: vehicleId,
                            nhaxemyduyen_vehicle_nonce: '<?php echo wp_create_nonce('nhaxemyduyen_vehicle_action'); ?>'
                        },
                        beforeSend: function() {
                            $('#trips-content').html('<p class="text-gray-500">Đang tải...</p>');
                            $('#trips-modal').removeClass('hidden');
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#trips-content').html(response.data.html);
                            } else {
                                console.error('Lỗi AJAX (get_trips):', response);
                                $('#trips-content').html('<p class="text-red-700">Lỗi: ' + response.data.message + '</p>');
                            }
                        },
                        error: function(xhr) {
                            console.error('Lỗi AJAX (get_trips):', xhr);
                            $('#trips-content').html('<p class="text-red-700">Đã có lỗi xảy ra: ' + xhr.statusText + '</p>');
                        }
                    });
                });

                // Đóng modal bằng nút Đóng hoặc X
                $(document).on('click', '.nhaxe-close-modal', function(e) {
                    e.preventDefault();
                    console.log('Closing modal'); // Debug
                    $('#trips-modal').addClass('hidden');
                    $('#trips-content').html('');
                });

                // Đóng modal khi nhấp ra ngoài nội dung
                $(document).on('click', '#trips-modal', function(e) {
                    if ($(e.target).is('#trips-modal')) {
                        console.log('Closing modal via background click'); // Debug
                        $('#trips-modal').addClass('hidden');
                        $('#trips-content').html('');
                    }
                });

                // Đóng modal bằng phím Esc
                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape' && !$('#trips-modal').hasClass('hidden')) {
                        console.log('Closing modal via Esc key'); // Debug
                        $('#trips-modal').addClass('hidden');
                        $('#trips-content').html('');
                    }
                });

                // Tìm kiếm danh sách xe qua AJAX
                $('#filter-form').submit(function(e) {
                    e.preventDefault();
                    var formData = $(this).serialize();

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: formData + '&action=nhaxemyduyen_filter_vehicles',
                        success: function(response) {
                            if (response.success) {
                                $('#vehicles-table tbody').html(response.data.html);
                            } else {
                                console.error('Lỗi AJAX (filter_vehicles):', response);
                                $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg animate-slide-in"><p>' + response.data.message + '</p></div>');
                            }
                        },
                        error: function(xhr) {
                            console.error('Lỗi AJAX (filter_vehicles):', xhr);
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg animate-slide-in"><p>Đã có lỗi xảy ra: ' + xhr.statusText + '</p></div>');
                        }
                    });
                });

                // Hàm làm mới bảng xe
                function refreshVehiclesTable() {
                    var formData = $('#filter-form').serialize();
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: formData + '&action=nhaxemyduyen_filter_vehicles',
                        success: function(response) {
                            if (response.success) {
                                $('#vehicles-table tbody').html(response.data.html);
                            } else {
                                console.error('Lỗi AJAX (refresh_vehicles):', response);
                                $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg animate-slide-in"><p>' + response.data.message + '</p></div>');
                            }
                        },
                        error: function(xhr) {
                            console.error('Lỗi AJAX (refresh_vehicles):', xhr);
                            $('#nhaxe-message').html('<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg animate-slide-in"><p>Đã có lỗi xảy ra: ' + xhr.statusText + '</p></div>');
                        }
                    });
                }
            });
        </script>
    <?php
}

// AJAX lấy dữ liệu xe
add_action('wp_ajax_nhaxemyduyen_get_vehicle', 'nhaxemyduyen_get_vehicle_callback');
function nhaxemyduyen_get_vehicle_callback() {
    check_ajax_referer('nhaxemyduyen_vehicle_action', 'nhaxemyduyen_vehicle_nonce');

    global $wpdb;
    $table_vehicles = $wpdb->prefix . 'vehicles';
    $vehicle_id = intval($_POST['vehicle_id']);

    $vehicle = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_vehicles WHERE vehicle_id = %d", $vehicle_id), ARRAY_A);

    if ($vehicle) {
        wp_send_json_success($vehicle);
    } else {
        wp_send_json_error(['message' => 'Không tìm thấy xe.']);
    }
}

// AJAX thêm/sửa xe
add_action('wp_ajax_nhaxemyduyen_manage_vehicle', 'nhaxemyduyen_manage_vehicle_callback');
function nhaxemyduyen_manage_vehicle_callback() {
    check_ajax_referer('nhaxemyduyen_vehicle_action', 'nhaxemyduyen_vehicle_nonce');

    global $wpdb;
    $table_vehicles = $wpdb->prefix . 'vehicles';

    $action = sanitize_text_field($_POST['nhaxemyduyen_vehicle_action']);
    $vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;
    $license_plate = sanitize_text_field($_POST['license_plate']);
    $type = sanitize_text_field($_POST['type']);
    $capacity = intval($_POST['capacity']);
    $image = isset($_POST['image']) ? sanitize_text_field($_POST['image']) : '';

    // Validate required fields
    if (empty($license_plate) || empty($type) || empty($capacity)) {
        wp_send_json_error(['message' => 'Vui lòng điền đầy đủ các trường bắt buộc.']);
    }

    if ($capacity <= 0) {
        wp_send_json_error(['message' => 'Số chỗ phải lớn hơn 0.']);
    }

    // Validate image URL if provided
    if (!empty($image) && !filter_var($image, FILTER_VALIDATE_URL)) {
        wp_send_json_error(['message' => 'URL hình ảnh không hợp lệ.']);
    }

    $vehicle_data = [
        'license_plate' => $license_plate,
        'type' => $type,
        'capacity' => $capacity,
        'image' => $image,
        'updated_at' => current_time('mysql'),
    ];

    if ($action === 'add') {
        $vehicle_data['created_at'] = current_time('mysql');
        $result = $wpdb->insert($table_vehicles, $vehicle_data);
        if ($result === false) {
            wp_send_json_error(['message' => 'Lỗi khi thêm xe: ' . esc_html($wpdb->last_error)]);
        } else {
            wp_send_json_success(['message' => 'Thêm xe thành công!']);
        }
    } elseif ($action === 'edit' && $vehicle_id) {
        $result = $wpdb->update($table_vehicles, $vehicle_data, ['vehicle_id' => $vehicle_id]);
        if ($result === false) {
            wp_send_json_error(['message' => 'Lỗi khi cập nhật xe: ' . esc_html($wpdb->last_error)]);
        } else {
            wp_send_json_success(['message' => 'Cập nhật xe thành công!']);
        }
    } else {
        wp_send_json_error(['message' => 'Hành động không hợp lệ.']);
    }
}

// AJAX xóa xe
add_action('wp_ajax_nhaxemyduyen_delete_vehicle', 'nhaxemyduyen_delete_vehicle_callback');
function nhaxemyduyen_delete_vehicle_callback() {
    check_ajax_referer('nhaxemyduyen_vehicle_action', 'nhaxemyduyen_vehicle_nonce');

    global $wpdb;
    $table_vehicles = $wpdb->prefix . 'vehicles';
    $vehicle_id = intval($_POST['vehicle_id']);

    $result = $wpdb->delete($table_vehicles, ['vehicle_id' => $vehicle_id]);
    if ($result === false) {
        wp_send_json_error(['message' => 'Lỗi khi xóa xe: ' . esc_html($wpdb->last_error)]);
    } else {
        wp_send_json_success(['message' => 'Xóa xe thành công!']);
    }
}

// AJAX cập nhật trạng thái xe
add_action('wp_ajax_nhaxemyduyen_update_vehicle_status', 'nhaxemyduyen_update_vehicle_status_callback');
function nhaxemyduyen_update_vehicle_status_callback() {
    check_ajax_referer('nhaxemyduyen_vehicle_action', 'nhaxemyduyen_vehicle_nonce');

    global $wpdb;
    $table_vehicles = $wpdb->prefix . 'vehicles';
    $vehicle_id = intval($_POST['vehicle_id']);
    $status = sanitize_text_field($_POST['status']);

    $result = $wpdb->update($table_vehicles, ['status' => $status, 'updated_at' => current_time('mysql')], ['vehicle_id' => $vehicle_id]);
    if ($result === false) {
        wp_send_json_error(['message' => 'Lỗi khi cập nhật trạng thái: ' . esc_html($wpdb->last_error)]);
    } else {
        wp_send_json_success(['message' => 'Cập nhật trạng thái thành công!']);
    }
}

// AJAX Tìm kiếm xe
add_action('wp_ajax_nhaxemyduyen_filter_vehicles', 'nhaxemyduyen_filter_vehicles_callback');
function nhaxemyduyen_filter_vehicles_callback() {
    check_ajax_referer('nhaxemyduyen_vehicle_action', 'nhaxemyduyen_vehicle_nonce');

    global $wpdb;
    $table_vehicles = $wpdb->prefix . 'vehicles';
    $table_trips = $wpdb->prefix . 'trips';

    $filter_license_plate = isset($_POST['filter_license_plate']) ? sanitize_text_field($_POST['filter_license_plate']) : '';
    $filter_type = isset($_POST['filter_type']) ? sanitize_text_field($_POST['filter_type']) : '';

    $where_conditions = [];
    if (!empty($filter_license_plate)) {
        $where_conditions[] = $wpdb->prepare("v.license_plate LIKE %s", '%' . $filter_license_plate . '%');
    }
    if (!empty($filter_type)) {
        $where_conditions[] = $wpdb->prepare("v.type LIKE %s", '%' . $filter_type . '%');
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = " WHERE " . implode(' AND ', $where_conditions);
    }

    $vehicles = $wpdb->get_results("
        SELECT v.*, COUNT(t.trip_id) as trip_count
        FROM $table_vehicles v
        LEFT JOIN $table_trips t ON v.vehicle_id = t.vehicle_id
        $where_clause
        GROUP BY v.vehicle_id
        ORDER BY v.created_at DESC
    ", ARRAY_A);

    if ($wpdb->last_error) {
        wp_send_json_error(['message' => 'Lỗi truy vấn: ' . esc_html($wpdb->last_error)]);
    } else {
        ob_start();
        if (empty($vehicles)) : ?>
            <tr>
                <td colspan="6" class="px-6 py-4 text-sm text-gray-500 text-center">Không có xe nào phù hợp với tiêu chí.</td>
            </tr>
        <?php else : ?>
            <?php foreach ($vehicles as $vehicle) : ?>
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap"><?php echo esc_html($vehicle['license_plate']); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap"><?php echo esc_html($vehicle['type']); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap text-center"><?php echo esc_html($vehicle['capacity']); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <a href="#" class="nhaxe-show-trips text-blue-600 hover:underline" data-vehicle-id="<?php echo esc_attr($vehicle['vehicle_id']); ?>">
                            <?php echo esc_html($vehicle['trip_count']); ?> chuyến
                        </a>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 text-center">
                        <?php if ($vehicle['image']) : ?>
                            <img src="<?php echo esc_url($vehicle['image']); ?>" alt="Hình ảnh xe" class="max-w-[120px] h-auto rounded-md mx-auto" />
                        <?php else : ?>
                            <span class="text-gray-400">Chưa có</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 flex space-x-2">
                        <button class="bg-blue-600 text-white px-3 py-1 rounded-md hover:bg-blue-700 transition mr-2 nhaxe-toggle-form" data-action="edit" data-vehicle-id="<?php echo esc_attr($vehicle['vehicle_id']); ?>">Sửa</button>
                        <button class="bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 transition nhaxe-delete-vehicle" data-vehicle-id="<?php echo esc_attr($vehicle['vehicle_id']); ?>" data-nonce="<?php echo wp_create_nonce('nhaxemyduyen_delete_vehicle'); ?>">Xóa</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif;
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }
}

// Xử lý AJAX để lấy danh sách chuyến xe
add_action('wp_ajax_nhaxemyduyen_get_trips', 'nhaxemyduyen_get_trips_callback');
function nhaxemyduyen_get_trips_callback() {
    check_ajax_referer('nhaxemyduyen_vehicle_action', 'nhaxemyduyen_vehicle_nonce');

    global $wpdb;
    $table_trips = $wpdb->prefix . 'trips';
    $table_routes = $wpdb->prefix . 'routes';
    $table_locations = $wpdb->prefix . 'locations';
    $vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;

    if ($vehicle_id <= 0) {
        wp_send_json_error(['message' => 'ID xe không hợp lệ']);
    }

    $trips = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT t.trip_id, t.route_id, t.driver_id, t.pickup_location, t.dropoff_location, t.departure_time, 
                    l1.name AS from_location, l2.name AS to_location
             FROM $table_trips t
             LEFT JOIN $table_routes r ON t.route_id = r.route_id
             LEFT JOIN $table_locations l1 ON r.from_location_id = l1.location_id
             LEFT JOIN $table_locations l2 ON r.to_location_id = l2.location_id
             WHERE t.vehicle_id = %d
             ORDER BY t.departure_time DESC",
            $vehicle_id
        ),
        ARRAY_A
    );

    if ($wpdb->last_error) {
        wp_send_json_error(['message' => 'Lỗi truy vấn: ' . $wpdb->last_error]);
    }

    if (empty($trips)) {
        $html = '<p class="text-gray-500">Không có chuyến xe nào cho xe này.</p>';
    } else {
        $html = '<div class="overflow-x-auto"><table class="min-w-full bg-white border border-gray-200">';
        $html .= '<thead class="bg-gray-50">';
        $html .= '<tr>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tuyến đường</th>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm đón</th>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm trả</th>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian khởi hành</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="divide-y divide-gray-200">';
        foreach ($trips as $trip) {
            $html .= '<tr class="hover:bg-gray-50">';
            $html .= '<td class="px-4 py-3 text-sm text-gray-900">' . esc_html($trip['from_location'] . ' -> ' . $trip['to_location']) . '</td>';
            $html .= '<td class="px-4 py-3 text-sm text-gray-900">' . esc_html($trip['pickup_location'] ?: 'Chưa có') . '</td>';
            $html .= '<td class="px-4 py-3 text-sm text-gray-900">' . esc_html($trip['dropoff_location'] ?: 'Chưa có') . '</td>';
            $html .= '<td class="px-4 py-3 text-sm text-gray-900">' . esc_html(date('d/m/Y H:i', strtotime($trip['departure_time']))) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table></div>';
    }

    wp_send_json_success(['html' => $html]);
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



    require_once plugin_dir_path(__FILE__) . 'inc/custom-api.php';
    require_once plugin_dir_path(__FILE__) . 'inc/location-api.php';
    require_once plugin_dir_path(__FILE__) . 'inc/route-api.php';
    require_once plugin_dir_path(__FILE__) . 'inc/trip-api.php';
    require_once plugin_dir_path(__FILE__) . 'inc/ticket-api.php';
    require_once plugin_dir_path(__FILE__) . 'inc/contact-api.php';
?>