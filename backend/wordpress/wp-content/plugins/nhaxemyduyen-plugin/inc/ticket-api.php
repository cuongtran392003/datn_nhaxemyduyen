<?php
class Ticket_API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        // Lấy danh sách vé
        register_rest_route('nhaxemyduyen/v1', '/tickets', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tickets'),
            'permission_callback' => '__return_true', // Cho phép truy cập công khai
        ));

        // Lấy thông tin một vé
        register_rest_route('nhaxemyduyen/v1', '/tickets/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_ticket'),
            'permission_callback' => '__return_true', // Cho phép truy cập công khai
        ));

        // Tạo vé mới
        register_rest_route('nhaxemyduyen/v1', '/tickets', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_ticket'),
            'permission_callback' => '__return_true', // Cho phép truy cập công khai
        ));

        // Cập nhật vé
        register_rest_route('nhaxemyduyen/v1', '/tickets/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_ticket'),
            'permission_callback' => '__return_true', // Cho phép truy cập công khai
        ));

        // Xóa vé
        register_rest_route('nhaxemyduyen/v1', '/tickets/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_ticket'),
            'permission_callback' => '__return_true', // Cho phép truy cập công khai
        ));

        // Lấy trạng thái ghế của chuyến xe
        register_rest_route('nhaxemyduyen/v1', '/trips/(?P<id>\d+)/seats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_seat_availability'),
            'permission_callback' => '__return_true', // Cho phép truy cập công khai
        ));

        // Kiểm tra vé bằng mã vé và số điện thoại
        register_rest_route('nhaxemyduyen/v1', '/tickets/check', array(
            'methods' => 'POST',
            'callback' => array($this, 'check_ticket'),
            'permission_callback' => '__return_true', // Cho phép truy cập công khai
        ));
    }

    /**
     * Lấy danh sách vé
     */
    public function get_tickets($request) {
        global $wpdb;
        $table_tickets = $wpdb->prefix . 'tickets';
        $table_trips = $wpdb->prefix . 'trips';
        $table_locations = $wpdb->prefix . 'locations';
        $table_drivers = $wpdb->prefix . 'drivers';
        $table_vehicles = $wpdb->prefix . 'vehicles';

        $tickets = $wpdb->get_results("
            SELECT t.*, tr.departure_time, tr.pickup_location as trip_pickup_location, tr.dropoff_location as trip_dropoff_location,
                   l1.name as from_location, l2.name as to_location, 
                   d.name as driver_name, v.license_plate as vehicle_plate
            FROM $table_tickets t
            JOIN $table_trips tr ON t.trip_id = tr.trip_id
            JOIN $table_locations l1 ON tr.from_location_id = l1.location_id
            JOIN $table_locations l2 ON tr.to_location_id = l2.location_id
            LEFT JOIN $table_drivers d ON tr.driver_id = d.driver_id
            LEFT JOIN $table_vehicles v ON tr.vehicle_id = v.vehicle_id
        ", ARRAY_A);

        if (empty($tickets)) {
            return new WP_REST_Response(array('message' => 'Không có vé nào.'), 200);
        }

        return new WP_REST_Response($tickets, 200);
    }

    /**
     * Lấy thông tin một vé
     */
    public function get_ticket($request) {
        global $wpdb;
        $table_tickets = $wpdb->prefix . 'tickets';
        $table_trips = $wpdb->prefix . 'trips';
        $table_locations = $wpdb->prefix . 'locations';
        $table_drivers = $wpdb->prefix . 'drivers';
        $table_vehicles = $wpdb->prefix . 'vehicles';

        $id = intval($request['id']);
        $ticket = $wpdb->get_row($wpdb->prepare("
            SELECT t.*, tr.departure_time, tr.pickup_location as trip_pickup_location, tr.dropoff_location as trip_dropoff_location,
                   l1.name as from_location, l2.name as to_location, 
                   d.name as driver_name, v.license_plate as vehicle_plate
            FROM $table_tickets t
            JOIN $table_trips tr ON t.trip_id = tr.trip_id
            JOIN $table_locations l1 ON tr.from_location_id = l1.location_id
            JOIN $table_locations l2 ON tr.to_location_id = l2.location_id
            LEFT JOIN $table_drivers d ON tr.driver_id = d.driver_id
            LEFT JOIN $table_vehicles v ON tr.vehicle_id = v.vehicle_id
            WHERE t.ticket_id = %d
        ", $id), ARRAY_A);

        if ($ticket) {
            return new WP_REST_Response($ticket, 200);
        }

        return new WP_Error('not_found', 'Vé xe không tồn tại', array('status' => 404));
    }

    /**
     * Kiểm tra vé bằng mã vé và số điện thoại
     */
    public function check_ticket($request) {
        global $wpdb;
        $table_tickets = $wpdb->prefix . 'tickets';
        $table_trips = $wpdb->prefix . 'trips';
        $table_locations = $wpdb->prefix . 'locations';
        $table_drivers = $wpdb->prefix . 'drivers';
        $table_vehicles = $wpdb->prefix . 'vehicles';

        $data = $request->get_json_params();

        // Kiểm tra các trường bắt buộc
        $required_fields = ['ticket_code', 'customer_phone'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('invalid_data', "Thiếu trường bắt buộc: $field", array('status' => 400));
            }
        }

        $ticket_code = sanitize_text_field($data['ticket_code']);
        $customer_phone = sanitize_text_field($data['customer_phone']);

        // Validate phone
        if (!preg_match('/^[0-9]{10,11}$/', $customer_phone)) {
            return new WP_Error('invalid_phone', 'Số điện thoại phải có 10-11 chữ số', array('status' => 400));
        }

        // Tìm vé
        $ticket = $wpdb->get_row($wpdb->prepare("
            SELECT t.*, tr.departure_time, tr.pickup_location as trip_pickup_location, tr.dropoff_location as trip_dropoff_location,
                   l1.name as from_location, l2.name as to_location, 
                   d.name as driver_name, v.license_plate as vehicle_plate
            FROM $table_tickets t
            JOIN $table_trips tr ON t.trip_id = tr.trip_id
            JOIN $table_locations l1 ON tr.from_location_id = l1.location_id
            JOIN $table_locations l2 ON tr.to_location_id = l2.location_id
            LEFT JOIN $table_drivers d ON tr.driver_id = d.driver_id
            LEFT JOIN $table_vehicles v ON tr.vehicle_id = v.vehicle_id
            WHERE t.ticket_code = %s AND t.customer_phone = %s
        ", $ticket_code, $customer_phone), ARRAY_A);

        if ($ticket) {
            // Chuẩn hóa dữ liệu để khớp với giao diện
            $ticket['start_location'] = $ticket['from_location'];
            $ticket['end_location'] = $ticket['to_location'];
            return new WP_REST_Response($ticket, 200);
        }

        return new WP_Error('not_found', 'Không tìm thấy vé với thông tin đã nhập', array('status' => 404));
    }

    /**
     * Tạo vé mới
     */
    public function create_ticket($request) {
        global $wpdb;
        $table_tickets = $wpdb->prefix . 'tickets';
        $table_trips = $wpdb->prefix . 'trips';
        $data = $request->get_json_params();

        // Kiểm tra các trường bắt buộc
        $required_fields = ['trip_id', 'customer_name', 'customer_phone', 'seat_number', 'pickup_location', 'dropoff_location'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('invalid_data', "Thiếu trường bắt buộc: $field", array('status' => 400));
            }
        }

        // Validate email
        if (!empty($data['customer_email']) && !is_email($data['customer_email'])) {
            return new WP_Error('invalid_email', 'Email không hợp lệ', array('status' => 400));
        }

        // Validate phone
        if (!preg_match('/^[0-9]{10,11}$/', $data['customer_phone'])) {
            return new WP_Error('invalid_phone', 'Số điện thoại phải có 10-11 chữ số', array('status' => 400));
        }

        // Validate seat number
        if (!preg_match('/^A[1-9][0-9]?$/', $data['seat_number']) || intval(substr($data['seat_number'], 1)) > 44) {
            return new WP_Error('invalid_seat', 'Số ghế không hợp lệ (phải là A1-A44)', array('status' => 400));
        }

        // Kiểm tra chuyến xe và ghế trống
        $trip_id = intval($data['trip_id']);
        $seat_number = sanitize_text_field($data['seat_number']);
        $trip = $wpdb->get_row($wpdb->prepare("
            SELECT available_seats, price, pickup_location, dropoff_location 
            FROM $table_trips 
            WHERE trip_id = %d", 
            $trip_id
        ));

        if (!$trip) {
            return new WP_Error('invalid_trip', 'Chuyến xe không tồn tại', array('status' => 404));
        }
        if ($trip->available_seats <= 0) {
            return new WP_Error('no_seats', 'Chuyến xe không còn ghế trống', array('status' => 400));
        }

        // Kiểm tra điểm đón và điểm trả
        $pickup_location = sanitize_text_field($data['pickup_location']);
        $dropoff_location = sanitize_text_field($data['dropoff_location']);
        if ($pickup_location !== $trip->pickup_location || $dropoff_location !== $trip->dropoff_location) {
            return new WP_Error('invalid_locations', 'Điểm đón hoặc điểm trả không khớp với chuyến xe', array('status' => 400));
        }

        // Kiểm tra ghế đã được đặt
        $seat_exists = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM $table_tickets 
            WHERE trip_id = %d AND seat_number = %s", 
            $trip_id, $seat_number
        ));
        if ($seat_exists > 0) {
            return new WP_Error('seat_taken', 'Ghế này đã được đặt', array('status' => 400));
        }

        // Tạo mã vé
        $ticket_code = 'TICKET-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $result = $wpdb->insert($table_tickets, array(
            'ticket_code' => $ticket_code,
            'trip_id' => $trip_id,
            'customer_name' => sanitize_text_field($data['customer_name']),
            'customer_phone' => sanitize_text_field($data['customer_phone']),
            'customer_email' => sanitize_email($data['customer_email'] ?? ''),
            'pickup_location' => $pickup_location,
            'dropoff_location' => $dropoff_location,
            'seat_number' => $seat_number,
            'status' => sanitize_text_field($data['status'] ?? 'Chưa thanh toán'),
            'note' => sanitize_text_field($data['note'] ?? ''),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        if ($result === false) {
            return new WP_Error('create_failed', 'Không thể tạo vé xe: ' . $wpdb->last_error, array('status' => 500));
        }

        // Cập nhật số ghế trống
        $wpdb->update(
            $table_trips, 
            array('available_seats' => $trip->available_seats - 1), 
            array('trip_id' => $trip_id)
        );

        return new WP_REST_Response(array(
            'message' => 'Vé xe đã được tạo',
            'ticket_id' => $wpdb->insert_id,
            'ticket_code' => $ticket_code,
            'seat_number' => $seat_number,
            'price' => $trip->price,
        ), 201);
    }

    /**
     * Cập nhật vé
     */
    public function update_ticket($request) {
        global $wpdb;
        $table_tickets = $wpdb->prefix . 'tickets';
        $table_trips = $wpdb->prefix . 'trips';
        $id = intval($request['id']);
        $data = $request->get_json_params();

        // Kiểm tra vé tồn tại
        $ticket = $wpdb->get_row($wpdb->prepare("SELECT trip_id, seat_number FROM $table_tickets WHERE ticket_id = %d", $id));
        if (!$ticket) {
            return new WP_Error('not_found', 'Vé xe không tồn tại', array('status' => 404));
        }

        // Kiểm tra các trường bắt buộc
        $required_fields = ['trip_id', 'customer_name', 'customer_phone', 'seat_number', 'pickup_location', 'dropoff_location', 'status'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('invalid_data', "Thiếu trường bắt buộc: $field", array('status' => 400));
            }
        }

        // Validate email
        if (!empty($data['customer_email']) && !is_email($data['customer_email'])) {
            return new WP_Error('invalid_email', 'Email không hợp lệ', array('status' => 400));
        }

        // Validate phone
        if (!preg_match('/^[0-9]{10,11}$/', $data['customer_phone'])) {
            return new WP_Error('invalid_phone', 'Số điện thoại phải có 10-11 chữ số', array('status' => 400));
        }

        // Validate seat number
        if (!preg_match('/^A[1-9][0-9]?$/', $data['seat_number']) || intval(substr($data['seat_number'], 1)) > 44) {
            return new WP_Error('invalid_seat', 'Số ghế không hợp lệ (phải là A1-A44)', array('status' => 400));
        }

        // Validate status
        $valid_statuses = ['Đã thanh toán', 'Chưa thanh toán', 'Đã hủy'];
        if (!in_array($data['status'], $valid_statuses)) {
            return new WP_Error('invalid_status', 'Trạng thái không hợp lệ', array('status' => 400));
        }

        // Kiểm tra chuyến xe
        $trip_id = intval($data['trip_id']);
        $seat_number = sanitize_text_field($data['seat_number']);
        $trip = $wpdb->get_row($wpdb->prepare("
            SELECT available_seats, pickup_location, dropoff_location 
            FROM $table_trips 
            WHERE trip_id = %d", 
            $trip_id
        ));
        if (!$trip) {
            return new WP_Error('invalid_trip', 'Chuyến xe không tồn tại', array('status' => 404));
        }

        // Kiểm tra điểm đón và điểm trả
        $pickup_location = sanitize_text_field($data['pickup_location']);
        $dropoff_location = sanitize_text_field($data['dropoff_location']);
        if ($pickup_location !== $trip->pickup_location || $dropoff_location !== $trip->dropoff_location) {
            return new WP_Error('invalid_locations', 'Điểm đón hoặc điểm trả không khớp với chuyến xe', array('status' => 400));
        }

        // Kiểm tra ghế nếu thay đổi
        if ($seat_number !== $ticket->seat_number || $trip_id !== $ticket->trip_id) {
            $seat_exists = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM $table_tickets 
                WHERE trip_id = %d AND seat_number = %s AND ticket_id != %d", 
                $trip_id, $seat_number, $id
            ));
            if ($seat_exists > 0) {
                return new WP_Error('seat_taken', 'Ghế này đã được đặt', array('status' => 400));
            }
        }

        // Cập nhật vé
        $result = $wpdb->update(
            $table_tickets,
            array(
                'trip_id' => $trip_id,
                'customer_name' => sanitize_text_field($data['customer_name']),
                'customer_phone' => sanitize_text_field($data['customer_phone']),
                'customer_email' => sanitize_email($data['customer_email'] ?? ''),
                'pickup_location' => $pickup_location,
                'dropoff_location' => $dropoff_location,
                'seat_number' => $seat_number,
                'status' => sanitize_text_field($data['status']),
                'note' => sanitize_text_field($data['note'] ?? ''),
                'updated_at' => current_time('mysql'),
            ),
            array('ticket_id' => $id)
        );

        if ($result === false) {
            return new WP_Error('update_failed', 'Không thể cập nhật vé xe: ' . $wpdb->last_error, array('status' => 500));
        }

        return new WP_REST_Response(array('message' => 'Vé xe đã được cập nhật'), 200);
    }

    /**
     * Xóa vé
     */
    public function delete_ticket($request) {
        global $wpdb;
        $table_tickets = $wpdb->prefix . 'tickets';
        $table_trips = $wpdb->prefix . 'trips';
        $id = intval($request['id']);

        // Kiểm tra vé tồn tại
        $ticket = $wpdb->get_row($wpdb->prepare("SELECT trip_id FROM $table_tickets WHERE ticket_id = %d", $id));
        if (!$ticket) {
            return new WP_Error('not_found', 'Vé xe không tồn tại', array('status' => 404));
        }

        // Xóa vé
        $result = $wpdb->delete($table_tickets, array('ticket_id' => $id));
        if ($result === false) {
            return new WP_Error('delete_failed', 'Không thể xóa vé xe: ' . $wpdb->last_error, array('status' => 500));
        }

        // Cập nhật số ghế trống
        $trip = $wpdb->get_row($wpdb->prepare("SELECT available_seats FROM $table_trips WHERE trip_id = %d", $ticket->trip_id));
        if ($trip) {
            $wpdb->update(
                $table_trips, 
                array('available_seats' => $trip->available_seats + 1), 
                array('trip_id' => $ticket->trip_id)
            );
        }

        return new WP_REST_Response(array('message' => 'Vé xe đã được xóa'), 200);
    }

    /**
     * Lấy trạng thái ghế của chuyến xe
     */
    public function get_seat_availability($request) {
        global $wpdb;
        $table_tickets = $wpdb->prefix . 'tickets';
        $table_trips = $wpdb->prefix . 'trips';
        $table_locations = $wpdb->prefix . 'locations';
        $table_drivers = $wpdb->prefix . 'drivers';
        $table_vehicles = $wpdb->prefix . 'vehicles';
        $trip_id = intval($request['id']);

        // Lấy thông tin chuyến xe
        $trip = $wpdb->get_row($wpdb->prepare("
            SELECT t.*, l1.name as from_location, l2.name as to_location, 
                   d.name as driver_name, v.license_plate as vehicle_plate
            FROM $table_trips t
            JOIN $table_locations l1 ON t.from_location_id = l1.location_id
            JOIN $table_locations l2 ON t.to_location_id = l2.location_id
            LEFT JOIN $table_drivers d ON t.driver_id = d.driver_id
            LEFT JOIN $table_vehicles v ON t.vehicle_id = v.vehicle_id
            WHERE t.trip_id = %d
        ", $trip_id), ARRAY_A);

        if (!$trip) {
            return new WP_Error('not_found', 'Chuyến xe không tồn tại', array('status' => 404));
        }

        // Lấy danh sách ghế đã đặt
        $booked_seats = $wpdb->get_col($wpdb->prepare("
            SELECT seat_number 
            FROM $table_tickets 
            WHERE trip_id = %d", 
            $trip_id
        ));

        // Tạo danh sách ghế (A1-A44)
        $seats = array_map(function ($i) use ($booked_seats) {
            $seat_number = "A" . ($i + 1);
            return array(
                'seat_id' => $i + 1,
                'seat_number' => $seat_number,
                'status' => in_array($seat_number, $booked_seats) ? 'Booked' : 'Available',
            );
        }, range(0, 43));

        return new WP_REST_Response(array(
            'trip_id' => $trip_id,
            'from_location' => $trip['from_location'],
            'to_location' => $trip['to_location'],
            'departure_time' => $trip['departure_time'],
            'pickup_location' => $trip['pickup_location'],
            'dropoff_location' => $trip['dropoff_location'],
            'available_seats' => $trip['available_seats'],
            'price' => $trip['price'],
            'driver_name' => $trip['driver_name'] ?: 'Chưa chọn',
            'vehicle_plate' => $trip['vehicle_plate'] ?: 'Chưa chọn',
            'booked_seats' => $booked_seats,
            'seats' => $seats,
        ), 200);
    }
}

new Ticket_API();