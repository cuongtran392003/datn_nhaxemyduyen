<?php
class Location_API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        // Lấy danh sách địa điểm
        register_rest_route('nhaxemyduyen/v1', '/locations', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_locations'),
        ));

        // Lấy thông tin một địa điểm
        register_rest_route('nhaxemyduyen/v1', '/locations/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_location'),
        ));

        // Thêm mới địa điểm
        register_rest_route('nhaxemyduyen/v1', '/locations', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_location'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        // Cập nhật địa điểm
        register_rest_route('nhaxemyduyen/v1', '/locations/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_location'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        // Xóa địa điểm
        register_rest_route('nhaxemyduyen/v1', '/locations/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_location'),
            'permission_callback' => array($this, 'permissions_check'),
        ));
    }

    public function get_locations($request) {
        global $wpdb;
        $table_locations = $wpdb->prefix . 'locations';
        $locations = $wpdb->get_results("SELECT * FROM $table_locations", ARRAY_A);
        return new WP_REST_Response($locations, 200);
    }

    public function get_location($request) {
        global $wpdb;
        $table_locations = $wpdb->prefix . 'locations';
        $id = $request['id'];
        $location = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_locations WHERE location_id = %d", $id), ARRAY_A);
        if ($location) {
            return new WP_REST_Response($location, 200);
        }
        return new WP_Error('not_found', 'Địa điểm không tồn tại', array('status' => 404));
    }

    public function create_location($request) {
        global $wpdb;
        $table_locations = $wpdb->prefix . 'locations';
        $data = $request->get_json_params();

        if (empty($data['name'])) {
            return new WP_Error('invalid_data', 'Tên địa điểm là bắt buộc', array('status' => 400));
        }

        $result = $wpdb->insert($table_locations, array(
            'name' => sanitize_text_field($data['name']),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        if ($result) {
            return new WP_REST_Response(array('message' => 'Địa điểm đã được tạo'), 201);
        }
        return new WP_Error('create_failed', 'Không thể tạo địa điểm', array('status' => 500));
    }

    public function update_location($request) {
        global $wpdb;
        $table_locations = $wpdb->prefix . 'locations';
        $id = $request['id'];
        $data = $request->get_json_params();

        if (empty($data['name'])) {
            return new WP_Error('invalid_data', 'Tên địa điểm là bắt buộc', array('status' => 400));
        }

        $result = $wpdb->update($table_locations, array(
            'name' => sanitize_text_field($data['name']),
            'updated_at' => current_time('mysql'),
        ), array('location_id' => $id));

        if ($result !== false) {
            return new WP_REST_Response(array('message' => 'Địa điểm đã được cập nhật'), 200);
        }
        return new WP_Error('update_failed', 'Không thể cập nhật địa điểm', array('status' => 500));
    }

    public function delete_location($request) {
        global $wpdb;
        $table_locations = $wpdb->prefix . 'locations';
        $table_routes = $wpdb->prefix . 'routes';
        $table_trips = $wpdb->prefix . 'trips';
        $id = $request['id'];

        // Kiểm tra xem địa điểm có đang được sử dụng không
        $route_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_routes WHERE from_location_id = %d OR to_location_id = %d", $id, $id));
        $trip_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_trips WHERE from_location_id = %d OR to_location_id = %d", $id, $id));
        if ($route_count > 0 || $trip_count > 0) {
            return new WP_Error('in_use', 'Không thể xóa địa điểm vì đang được sử dụng', array('status' => 400));
        }

        $result = $wpdb->delete($table_locations, array('location_id' => $id));
        if ($result) {
            return new WP_REST_Response(array('message' => 'Địa điểm đã được xóa'), 200);
        }
        return new WP_Error('delete_failed', 'Không thể xóa địa điểm', array('status' => 500));
    }

    public function permissions_check($request) {
        return current_user_can('manage_options');
    }
}

new Location_API();