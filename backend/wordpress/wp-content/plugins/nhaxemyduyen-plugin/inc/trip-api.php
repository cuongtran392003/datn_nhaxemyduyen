<?php
class Trip_API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('nhaxemyduyen/v1', '/trips', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_trips'),
        ));

        register_rest_route('nhaxemyduyen/v1', '/trips/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_trip'),
        ));

        register_rest_route('nhaxemyduyen/v1', '/trips', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_trip'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        register_rest_route('nhaxemyduyen/v1', '/trips/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_trip'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        register_rest_route('nhaxemyduyen/v1', '/trips/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_trip'),
            'permission_callback' => array($this, 'permissions_check'),
        ));
    }

    public function get_trips($request) {
        global $wpdb;
        $table_trips = $wpdb->prefix . 'trips';
        $table_locations = $wpdb->prefix . 'locations';
        $trips = $wpdb->get_results("
            SELECT t.*, l1.name as from_location, l2.name as to_location
            FROM $table_trips t
            JOIN $table_locations l1 ON t.from_location_id = l1.location_id
            JOIN $table_locations l2 ON t.to_location_id = l2.location_id
        ", ARRAY_A);
        return new WP_REST_Response($trips, 200);
    }

    public function get_trip($request) {
        global $wpdb;
        $table_trips = $wpdb->prefix . 'trips';
        $table_locations = $wpdb->prefix . 'locations';
        $id = $request['id'];
        $trip = $wpdb->get_row($wpdb->prepare("
            SELECT t.*, l1.name as from_location, l2.name as to_location
            FROM $table_trips t
            JOIN $table_locations l1 ON t.from_location_id = l1.location_id
            JOIN $table_locations l2 ON t.to_location_id = l2.location_id
            WHERE t.trip_id = %d
        ", $id), ARRAY_A);
        if ($trip) {
            return new WP_REST_Response($trip, 200);
        }
        return new WP_Error('not_found', 'Chuyến xe không tồn tại', array('status' => 404));
    }

    public function create_trip($request) {
        global $wpdb;
        $table_trips = $wpdb->prefix . 'trips';
        $data = $request->get_json_params();

        if (empty($data['from_location_id']) || empty($data['to_location_id']) || empty($data['pickup_location']) || 
            empty($data['dropoff_location']) || empty($data['departure_time']) || empty($data['price']) || 
            empty($data['available_seats'])) {
            return new WP_Error('invalid_data', 'Thiếu thông tin bắt buộc', array('status' => 400));
        }

        $result = $wpdb->insert($table_trips, array(
            'from_location_id' => intval($data['from_location_id']),
            'to_location_id' => intval($data['to_location_id']),
            'pickup_location' => sanitize_text_field($data['pickup_location']),
            'dropoff_location' => sanitize_text_field($data['dropoff_location']),
            'departure_time' => date('Y-m-d H:i:s', strtotime($data['departure_time'])),
            'price' => floatval($data['price']),
            'available_seats' => intval($data['available_seats']),
            'bus_image' => sanitize_text_field($data['bus_image'] ?? ''),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'arrival_time' => !empty($data['arrival_time']) ? date('Y-m-d H:i:s', strtotime($data['arrival_time'])) : null,
        ));

        if ($result) {
            return new WP_REST_Response(array('message' => 'Chuyến xe đã được tạo'), 201);
        }
        return new WP_Error('create_failed', 'Không thể tạo chuyến xe', array('status' => 500));
    }

    public function update_trip($request) {
        global $wpdb;
        $table_trips = $wpdb->prefix . 'trips';
        $id = $request['id'];
        $data = $request->get_json_params();

        if (empty($data['from_location_id']) || empty($data['to_location_id']) || empty($data['pickup_location']) || 
            empty($data['dropoff_location']) || empty($data['departure_time']) || empty($data['price']) || 
            empty($data['available_seats'])) {
            return new WP_Error('invalid_data', 'Thiếu thông tin bắt buộc', array('status' => 400));
        }

        $update_data = array(
            'from_location_id' => intval($data['from_location_id']),
            'to_location_id' => intval($data['to_location_id']),
            'pickup_location' => sanitize_text_field($data['pickup_location']),
            'dropoff_location' => sanitize_text_field($data['dropoff_location']),
            'departure_time' => date('Y-m-d H:i:s', strtotime($data['departure_time'])),
            'price' => floatval($data['price']),
            'available_seats' => intval($data['available_seats']),
            'bus_image' => sanitize_text_field($data['bus_image'] ?? ''),
            'updated_at' => current_time('mysql'),
        );

        if (!empty($data['arrival_time'])) {
            $update_data['arrival_time'] = date('Y-m-d H:i:s', strtotime($data['arrival_time']));
        }

        $result = $wpdb->update($table_trips, $update_data, array('trip_id' => $id));

        if ($result !== false) {
            return new WP_REST_Response(array('message' => 'Chuyến xe đã được cập nhật'), 200);
        }
        return new WP_Error('update_failed', 'Không thể cập nhật chuyến xe', array('status' => 500));
    }

    public function delete_trip($request) {
        global $wpdb;
        $table_trips = $wpdb->prefix . 'trips';
        $table_tickets = $wpdb->prefix . 'tickets';
        $id = $request['id'];

        $ticket_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_tickets WHERE trip_id = %d", $id));
        if ($ticket_count > 0) {
            return new WP_Error('in_use', 'Không thể xóa chuyến xe vì đã có vé được đặt', array('status' => 400));
        }

        $result = $wpdb->delete($table_trips, array('trip_id' => $id));
        if ($result) {
            return new WP_REST_Response(array('message' => 'Chuyến xe đã được xóa'), 200);
        }
        return new WP_Error('delete_failed', 'Không thể xóa chuyến xe', array('status' => 500));
    }

    public function permissions_check($request) {
        return current_user_can('manage_options');
    }
}

new Trip_API();