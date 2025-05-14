<?php
class Trip_API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('nhaxemyduyen/v1', '/trips', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_trips'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('nhaxemyduyen/v1', '/trips/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_trip'),
            'permission_callback' => '__return_true',
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

        register_rest_route('nhaxemyduyen/v1', '/trips/(?P<id>\d+)/seats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_trip_seats'),
            'permission_callback' => '__return_true',
        ));
    }

    public function get_trip_seats($request) {
        global $wpdb;
        $table_trips = $wpdb->prefix . 'trips';
        $table_routes = $wpdb->prefix . 'routes';
        $table_locations = $wpdb->prefix . 'locations';
        $table_drivers = $wpdb->prefix . 'drivers';
        $table_vehicles = $wpdb->prefix . 'vehicles';
        $table_tickets = $wpdb->prefix . 'tickets';
        $trip_id = $request['id'];

        // Lấy thông tin chuyến xe
        $trip = $wpdb->get_row($wpdb->prepare("
            SELECT t.available_seats, t.price, 
                   d.name as driver_name, v.license_plate as vehicle_plate
            FROM $table_trips t
            LEFT JOIN $table_drivers d ON t.driver_id = d.driver_id
            LEFT JOIN $table_vehicles v ON t.vehicle_id = v.vehicle_id
            WHERE t.trip_id = %d
        ", $trip_id), ARRAY_A);

        if ($wpdb->last_error) {
            error_log('Trip_API get_trip_seats error: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Lỗi truy vấn cơ sở dữ liệu', array('status' => 500));
        }

        if (!$trip) {
            return new WP_Error('not_found', 'Chuyến xe không tồn tại', array('status' => 404));
        }

        // Lấy danh sách ghế đã đặt từ bảng wp_tickets
        $booked_seats = $wpdb->get_results($wpdb->prepare(
            "SELECT seat_number FROM $table_tickets WHERE trip_id = %d AND status = 'Đã thanh toán'",
            $trip_id
        ), ARRAY_A);

        if ($wpdb->last_error) {
            error_log('Trip_API get_trip_seats booked_seats error: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Lỗi truy vấn ghế đã đặt', array('status' => 500));
        }

        $booked_seat_numbers = array_column($booked_seats, 'seat_number');

        // Giả lập danh sách ghế dựa trên available_seats
        $available_seats = intval($trip['available_seats']);
        $seats = [];
        for ($i = 1; $i <= $available_seats; $i++) {
            $seat_number = 'A' . $i;
            $seats[] = [
                'seat_id' => $i,
                'seat_number' => $seat_number,
                'status' => in_array($seat_number, $booked_seat_numbers) ? 'booked' : 'available',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ];
        }

        // Tạo dữ liệu trả về
        $response = [
            'seats' => $seats,
            'booked_seats' => $booked_seat_numbers,
            'price' => floatval($trip['price']),
            'driver_name' => $trip['driver_name'] ?? 'Chưa chọn',
            'vehicle_plate' => $trip['vehicle_plate'] ?? 'Chưa chọn',
        ];

        return new WP_REST_Response($response, 200);
    }

    public function get_trips($request) {
        global $wpdb;
        $table_trips = $wpdb->prefix . 'trips';
        $table_routes = $wpdb->prefix . 'routes';
        $table_locations = $wpdb->prefix . 'locations';
        $table_drivers = $wpdb->prefix . 'drivers';
        $table_vehicles = $wpdb->prefix . 'vehicles';

        $trips = $wpdb->get_results("
            SELECT t.*, r.route_id, l1.name as from_location, l2.name as to_location, 
                   d.name as driver_name, v.license_plate as vehicle_plate
            FROM $table_trips t
            JOIN $table_routes r ON t.route_id = r.route_id
            JOIN $table_locations l1 ON r.from_location_id = l1.location_id
            JOIN $table_locations l2 ON r.to_location_id = l2.location_id
            LEFT JOIN $table_drivers d ON t.driver_id = d.driver_id
            LEFT JOIN $table_vehicles v ON t.vehicle_id = v.vehicle_id
        ", ARRAY_A);

        if ($wpdb->last_error) {
            error_log('Trip_API get_trips error: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Lỗi truy vấn cơ sở dữ liệu', array('status' => 500));
        }

        return new WP_REST_Response($trips, 200);
    }

    public function get_trip($request) {
        global $wpdb;
        $table_trips = $wpdb->prefix . 'trips';
        $table_routes = $wpdb->prefix . 'routes';
        $table_locations = $wpdb->prefix . 'locations';
        $table_drivers = $wpdb->prefix . 'drivers';
        $table_vehicles = $wpdb->prefix . 'vehicles';
        $id = $request['id'];

        $trip = $wpdb->get_row($wpdb->prepare("
            SELECT t.*, r.route_id, l1.name as from_location, l2.name as to_location, 
                   d.name as driver_name, v.license_plate as vehicle_plate
            FROM $table_trips t
            JOIN $table_routes r ON t.route_id = r.route_id
            JOIN $table_locations l1 ON r.from_location_id = l1.location_id
            JOIN $table_locations l2 ON r.to_location_id = l2.location_id
            LEFT JOIN $table_drivers d ON t.driver_id = d.driver_id
            LEFT JOIN $table_vehicles v ON t.vehicle_id = v.vehicle_id
            WHERE t.trip_id = %d
        ", $id), ARRAY_A);

        if ($wpdb->last_error) {
            error_log('Trip_API get_trip error: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Lỗi truy vấn cơ sở dữ liệu', array('status' => 500));
        }

        if ($trip) {
            return new WP_REST_Response($trip, 200);
        }
        return new WP_Error('not_found', 'Chuyến xe không tồn tại', array('status' => 404));
    }

    public function create_trip($request) {
        global $wpdb;
        $table_trips = $wpdb->prefix . 'trips';
        $table_routes = $wpdb->prefix . 'routes';
        $table_drivers = $wpdb->prefix . 'drivers';
        $table_vehicles = $wpdb->prefix . 'vehicles';
        $data = $request->get_json_params();

        $required_fields = ['route_id', 'pickup_location', 'dropoff_location', 'departure_time', 'price', 'available_seats'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('invalid_data', "Thiếu trường bắt buộc: $field", array('status' => 400));
            }
        }

        $route_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_routes WHERE route_id = %d", intval($data['route_id'])));
        if (!$route_exists) {
            return new WP_Error('invalid_route', 'Tuyến đường không tồn tại', array('status' => 400));
        }

        if (!empty($data['driver_id'])) {
            $driver_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_drivers WHERE driver_id = %d AND status = 'Active'", intval($data['driver_id'])));
            if (!$driver_exists) {
                return new WP_Error('invalid_driver', 'Tài xế không tồn tại hoặc không hoạt động', array('status' => 400));
            }
        }

        if (!empty($data['vehicle_id'])) {
            $vehicle_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_vehicles WHERE vehicle_id = %d AND status = 'Active'", intval($data['vehicle_id'])));
            if (!$vehicle_exists) {
                return new WP_Error('invalid_vehicle', 'Phương tiện không tồn tại hoặc không hoạt động', array('status' => 400));
            }
        }

        $departure_time = strtotime($data['departure_time']);
        if (!$departure_time || $departure_time < time()) {
            return new WP_Error('invalid_date', 'Thời gian khởi hành không hợp lệ hoặc đã qua', array('status' => 400));
        }
        $arrival_time = !empty($data['arrival_time']) ? strtotime($data['arrival_time']) : null;
        if ($arrival_time && $arrival_time <= $departure_time) {
            return new WP_Error('invalid_date', 'Thời gian đến phải sau thời gian khởi hành', array('status' => 400));
        }

        if (floatval($data['price']) <= 0 || intval($data['available_seats']) <= 0) {
            return new WP_Error('invalid_data', 'Giá vé và số ghế phải lớn hơn 0', array('status' => 400));
        }

        $wpdb->query('START TRANSACTION');
        $result = $wpdb->insert($table_trips, array(
            'route_id' => intval($data['route_id']),
            'driver_id' => !empty($data['driver_id']) ? intval($data['driver_id']) : null,
            'vehicle_id' => !empty($data['vehicle_id']) ? intval($data['vehicle_id']) : null,
            'pickup_location' => sanitize_text_field($data['pickup_location']),
            'dropoff_location' => sanitize_text_field($data['dropoff_location']),
            'departure_time' => date('Y-m-d H:i:s', $departure_time),
            'price' => floatval($data['price']),
            'available_seats' => intval($data['available_seats']),
            'bus_image' => sanitize_text_field($data['bus_image'] ?? ''),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'arrival_time' => $arrival_time ? date('Y-m-d H:i:s', $arrival_time) : null,
        ));

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            error_log('Trip_API create_trip error: ' . $wpdb->last_error);
            return new WP_Error('create_failed', 'Không thể tạo chuyến xe', array('status' => 500));
        }

        $wpdb->query('COMMIT');
        return new WP_REST_Response(array('message' => 'Chuyến xe đã được tạo', 'trip_id' => $wpdb->insert_id), 201);
    }

    public function update_trip($request) {
        global $wpdb;
        $table_trips = $wpdb->prefix . 'trips';
        $table_routes = $wpdb->prefix . 'routes';
        $table_drivers = $wpdb->prefix . 'drivers';
        $table_vehicles = $wpdb->prefix . 'vehicles';
        $id = $request['id'];
        $data = $request->get_json_params();

        $trip_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_trips WHERE trip_id = %d", $id));
        if (!$trip_exists) {
            return new WP_Error('not_found', 'Chuyến xe không tồn tại', array('status' => 404));
        }

        $required_fields = ['route_id', 'pickup_location', 'dropoff_location', 'departure_time', 'price', 'available_seats'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('invalid_data', "Thiếu trường bắt buộc: $field", array('status' => 400));
            }
        }

        $route_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_routes WHERE route_id = %d", intval($data['route_id'])));
        if (!$route_exists) {
            return new WP_Error('invalid_route', 'Tuyến đường không tồn tại', array('status' => 400));
        }

        if (!empty($data['driver_id'])) {
            $driver_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_drivers WHERE driver_id = %d AND status = 'Active'", intval($data['driver_id'])));
            if (!$driver_exists) {
                return new WP_Error('invalid_driver', 'Tài xế không tồn tại hoặc không hoạt động', array('status' => 400));
            }
        }

        if (!empty($data['vehicle_id'])) {
            $vehicle_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_vehicles WHERE vehicle_id = %d AND status = 'Active'", intval($data['vehicle_id'])));
            if (!$vehicle_exists) {
                return new WP_Error('invalid_vehicle', 'Phương tiện không tồn tại hoặc không hoạt động', array('status' => 400));
            }
        }

        $departure_time = strtotime($data['departure_time']);
        if (!$departure_time || $departure_time < time()) {
            return new WP_Error('invalid_date', 'Thời gian khởi hành không hợp lệ hoặc đã qua', array('status' => 400));
        }
        $arrival_time = !empty($data['arrival_time']) ? strtotime($data['arrival_time']) : null;
        if ($arrival_time && $arrival_time <= $departure_time) {
            return new WP_Error('invalid_date', 'Thời gian đến phải sau thời gian khởi hành', array('status' => 400));
        }

        if (floatval($data['price']) <= 0 || intval($data['available_seats']) <= 0) {
            return new WP_Error('invalid_data', 'Giá vé và số ghế phải lớn hơn 0', array('status' => 400));
        }

        $update_data = array(
            'route_id' => intval($data['route_id']),
            'driver_id' => !empty($data['driver_id']) ? intval($data['driver_id']) : null,
            'vehicle_id' => !empty($data['vehicle_id']) ? intval($data['vehicle_id']) : null,
            'pickup_location' => sanitize_text_field($data['pickup_location']),
            'dropoff_location' => sanitize_text_field($data['dropoff_location']),
            'departure_time' => date('Y-m-d H:i:s', $departure_time),
            'price' => floatval($data['price']),
            'available_seats' => intval($data['available_seats']),
            'bus_image' => sanitize_text_field($data['bus_image'] ?? ''),
            'updated_at' => current_time('mysql'),
            'arrival_time' => $arrival_time ? date('Y-m-d H:i:s', $arrival_time) : null,
        );

        $wpdb->query('START TRANSACTION');
        $result = $wpdb->update($table_trips, $update_data, array('trip_id' => $id));

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            error_log('Trip_API update_trip error: ' . $wpdb->last_error);
            return new WP_Error('update_failed', 'Không thể cập nhật chuyến xe', array('status' => 500));
        }

        $wpdb->query('COMMIT');
        return new WP_REST_Response(array('message' => 'Chuyến xe đã được cập nhật'), 200);
    }

    public function delete_trip($request) {
        global $wpdb;
        $table_trips = $wpdb->prefix . 'trips';
        $table_tickets = $wpdb->prefix . 'tickets';
        $id = $request['id'];

        $trip_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_trips WHERE trip_id = %d", $id));
        if (!$trip_exists) {
            return new WP_Error('not_found', 'Chuyến xe không tồn tại', array('status' => 404));
        }

        $ticket_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_tickets WHERE trip_id = %d", $id));
        if ($ticket_count > 0) {
            return new WP_Error('in_use', 'Không thể xóa chuyến xe vì đã có vé được đặt', array('status' => 400));
        }

        $wpdb->query('START TRANSACTION');
        $result = $wpdb->delete($table_trips, array('trip_id' => $id));

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            error_log('Trip_API delete_trip error: ' . $wpdb->last_error);
            return new WP_Error('delete_failed', 'Không thể xóa chuyến xe', array('status' => 500));
        }

        $wpdb->query('COMMIT');
        return new WP_REST_Response(array('message' => 'Chuyến xe đã được xóa'), 200);
    }

    public function permissions_check($request) {
        return current_user_can('manage_options');
    }
}

new Trip_API();