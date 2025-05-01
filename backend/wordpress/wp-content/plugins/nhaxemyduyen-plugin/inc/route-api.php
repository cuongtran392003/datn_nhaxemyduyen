<?php
class Route_API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('nhaxemyduyen/v1', '/routes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_routes'),
        ));

        register_rest_route('nhaxemyduyen/v1', '/routes/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_route'),
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
        if ($route) {
            return new WP_REST_Response($route, 200);
        }
        return new WP_Error('not_found', 'Tuyến đường không tồn tại', array('status' => 404));
    }

    public function create_route($request) {
        global $wpdb;
        $table_routes = $wpdb->prefix . 'routes';
        $data = $request->get_json_params();

        if (empty($data['from_location_id']) || empty($data['to_location_id']) || empty($data['price']) || empty($data['distance']) || empty($data['duration'])) {
            return new WP_Error('invalid_data', 'Thiếu thông tin bắt buộc', array('status' => 400));
        }

        $result = $wpdb->insert($table_routes, array(
            'from_location_id' => intval($data['from_location_id']),
            'to_location_id' => intval($data['to_location_id']),
            'price' => floatval($data['price']),
            'distance' => intval($data['distance']),
            'duration' => sanitize_text_field($data['duration']),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        if ($result) {
            return new WP_REST_Response(array('message' => 'Tuyến đường đã được tạo'), 201);
        }
        return new WP_Error('create_failed', 'Không thể tạo tuyến đường', array('status' => 500));
    }

    public function update_route($request) {
        global $wpdb;
        $table_routes = $wpdb->prefix . 'routes';
        $id = $request['id'];
        $data = $request->get_json_params();

        if (empty($data['from_location_id']) || empty($data['to_location_id']) || empty($data['price']) || empty($data['distance']) || empty($data['duration'])) {
            return new WP_Error('invalid_data', 'Thiếu thông tin bắt buộc', array('status' => 400));
        }

        $result = $wpdb->update($table_routes, array(
            'from_location_id' => intval($data['from_location_id']),
            'to_location_id' => intval($data['to_location_id']),
            'price' => floatval($data['price']),
            'distance' => intval($data['distance']),
            'duration' => sanitize_text_field($data['duration']),
            'updated_at' => current_time('mysql'),
        ), array('route_id' => $id));

        if ($result !== false) {
            return new WP_REST_Response(array('message' => 'Tuyến đường đã được cập nhật'), 200);
        }
        return new WP_Error('update_failed', 'Không thể cập nhật tuyến đường', array('status' => 500));
    }

    public function delete_route($request) {
        global $wpdb;
        $table_routes = $wpdb->prefix . 'routes';
        $id = $request['id'];

        $result = $wpdb->delete($table_routes, array('route_id' => $id));
        if ($result) {
            return new WP_REST_Response(array('message' => 'Tuyến đường đã được xóa'), 200);
        }
        return new WP_Error('delete_failed', 'Không thể xóa tuyến đường', array('status' => 500));
    }

    public function permissions_check($request) {
        return current_user_can('manage_options');
    }
}

new Route_API();