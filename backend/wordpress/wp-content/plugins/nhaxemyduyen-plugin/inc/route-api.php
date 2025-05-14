<?php
class Route_API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('nhaxemyduyen/v1', '/routes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_routes'),
            'permission_callback' => '__return_true', // Cho phép truy cập công khai
        ));

        register_rest_route('nhaxemyduyen/v1', '/routes/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_route'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('nhaxemyduyen/v1', '/routes', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_route'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        register_rest_route('nhaxemyduyen/v1', '/routes/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_route'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        register_rest_route('nhaxemyduyen/v1', '/routes/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_route'),
            'permission_callback' => array($this, 'permissions_check'),
        ));
    }

    public function get_routes($request) {
        global $wpdb;
        $table_routes = $wpdb->prefix . 'routes';
        $table_locations = $wpdb->prefix . 'locations';

        $routes = $wpdb->get_results("
            SELECT r.*, l1.name as from_location, l2.name as to_location
            FROM $table_routes r
            JOIN $table_locations l1 ON r.from_location_id = l1.location_id
            JOIN $table_locations l2 ON r.to_location_id = l2.location_id
        ", ARRAY_A);

        if ($wpdb->last_error) {
            error_log('Route_API get_routes error: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Lỗi truy vấn cơ sở dữ liệu', array('status' => 500));
        }

        return new WP_REST_Response($routes, 200);
    }

    public function get_route($request) {
        global $wpdb;
        $table_routes = $wpdb->prefix . 'routes';
        $table_locations = $wpdb->prefix . 'locations';
        $id = $request['id'];

        $route = $wpdb->get_row($wpdb->prepare("
            SELECT r.*, l1.name as from_location, l2.name as to_location
            FROM $table_routes r
            JOIN $table_locations l1 ON r.from_location_id = l1.location_id
            JOIN $table_locations l2 ON r.to_location_id = l2.location_id
            WHERE r.route_id = %d
        ", $id), ARRAY_A);

        if ($wpdb->last_error) {
            error_log('Route_API get_route error: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Lỗi truy vấn cơ sở dữ liệu', array('status' => 500));
        }

        if ($route) {
            return new WP_REST_Response($route, 200);
        }
        return new WP_Error('not_found', 'Tuyến đường không tồn tại', array('status' => 404));
    }

    public function create_route($request) {
        global $wpdb;
        $table_routes = $wpdb->prefix . 'routes';
        $table_locations = $wpdb->prefix . 'locations';
        $data = $request->get_json_params();

        // Kiểm tra các trường bắt buộc
        $required_fields = ['from_location_id', 'to_location_id', 'price', 'distance', 'duration'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('invalid_data', "Thiếu trường bắt buộc: $field", array('status' => 400));
            }
        }

        // Kiểm tra location_id
        $from_location_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_locations WHERE location_id = %d", intval($data['from_location_id'])));
        $to_location_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_locations WHERE location_id = %d", intval($data['to_location_id'])));
        if (!$from_location_exists || !$to_location_exists) {
            return new WP_Error('invalid_location', 'Địa điểm không tồn tại', array('status' => 400));
        }
        if ($data['from_location_id'] == $data['to_location_id']) {
            return new WP_Error('invalid_location', 'Địa điểm đi và đến không được trùng nhau', array('status' => 400));
        }

        // Kiểm tra giá và khoảng cách
        if (floatval($data['price']) <= 0 || intval($data['distance']) <= 0) {
            return new WP_Error('invalid_data', 'Giá vé và khoảng cách phải lớn hơn 0', array('status' => 400));
        }

        $wpdb->query('START TRANSACTION');
        $result = $wpdb->insert($table_routes, array(
            'from_location_id' => intval($data['from_location_id']),
            'to_location_id' => intval($data['to_location_id']),
            'price' => floatval($data['price']),
            'distance' => intval($data['distance']),
            'duration' => sanitize_text_field($data['duration']),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            error_log('Route_API create_route error: ' . $wpdb->last_error);
            return new WP_Error('create_failed', 'Không thể tạo tuyến đường', array('status' => 500));
        }

        $wpdb->query('COMMIT');
        return new WP_REST_Response(array('message' => 'Tuyến đường đã được tạo', 'route_id' => $wpdb->insert_id), 201);
    }

    public function update_route($request) {
        global $wpdb;
        $table_routes = $wpdb->prefix . 'routes';
        $table_locations = $wpdb->prefix . 'locations';
        $id = $request['id'];
        $data = $request->get_json_params();

        // Kiểm tra tuyến đường tồn tại
        $route_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_routes WHERE route_id = %d", $id));
        if (!$route_exists) {
            return new WP_Error('not_found', 'Tuyến đường không tồn tại', array('status' => 404));
        }

        // Kiểm tra các trường bắt buộc
        $required_fields = ['from_location_id', 'to_location_id', 'price', 'distance', 'duration'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('invalid_data', "Thiếu trường bắt buộc: $field", array('status' => 400));
            }
        }

        // Kiểm tra location_id
        $from_location_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_locations WHERE location_id = %d", intval($data['from_location_id'])));
        $to_location_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_locations WHERE location_id = %d", intval($data['to_location_id'])));
        if (!$from_location_exists || !$to_location_exists) {
            return new WP_Error('invalid_location', 'Địa điểm không tồn tại', array('status' => 400));
        }
        if ($data['from_location_id'] == $data['to_location_id']) {
            return new WP_Error('invalid_location', 'Địa điểm đi và đến không được trùng nhau', array('status' => 400));
        }

        // Kiểm tra giá và khoảng cách
        if (floatval($data['price']) <= 0 || intval($data['distance']) <= 0) {
            return new WP_Error('invalid_data', 'Giá vé và khoảng cách phải lớn hơn 0', array('status' => 400));
        }

        $wpdb->query('START TRANSACTION');
        $result = $wpdb->update($table_routes, array(
            'from_location_id' => intval($data['from_location_id']),
            'to_location_id' => intval($data['to_location_id']),
            'price' => floatval($data['price']),
            'distance' => intval($data['distance']),
            'duration' => sanitize_text_field($data['duration']),
            'updated_at' => current_time('mysql'),
        ), array('route_id' => $id));

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            error_log('Route_API update_route error: ' . $wpdb->last_error);
            return new WP_Error('update_failed', 'Không thể cập nhật tuyến đường', array('status' => 500));
        }

        $wpdb->query('COMMIT');
        return new WP_REST_Response(array('message' => 'Tuyến đường đã được cập nhật'), 200);
    }

    public function delete_route($request) {
        global $wpdb;
        $table_routes = $wpdb->prefix . 'routes';
        $table_trips = $wpdb->prefix . 'trips';
        $id = $request['id'];

        // Kiểm tra tuyến đường tồn tại
        $route_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_routes WHERE route_id = %d", $id));
        if (!$route_exists) {
            return new WP_Error('not_found', 'Tuyến đường không tồn tại', array('status' => 404));
        }

        // Kiểm tra chuyến xe liên quan
        $trip_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_trips WHERE route_id = %d", $id));
        if ($trip_count > 0) {
            return new WP_Error('in_use', 'Không thể xóa tuyến đường vì đã có chuyến xe sử dụng', array('status' => 400));
        }

        $wpdb->query('START TRANSACTION');
        $result = $wpdb->delete($table_routes, array('route_id' => $id));

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            error_log('Route_API delete_route error: ' . $wpdb->last_error);
            return new WP_Error('delete_failed', 'Không thể xóa tuyến đường', array('status' => 500));
        }

        $wpdb->query('COMMIT');
        return new WP_REST_Response(array('message' => 'Tuyến đường đã được xóa'), 200);
    }

    public function permissions_check($request) {
        return current_user_can('manage_options');
    }
}

new Route_API();