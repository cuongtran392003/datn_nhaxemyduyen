<?php

class Ticket_API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        // Hàm kiểm tra xác thực
        $require_auth = function () {
            if (!is_user_logged_in()) {
                return new WP_Error('unauthorized', 'Bạn cần đăng nhập để thực hiện hành động này', array('status' => 401));
            }
            return true;
        };
    
        // Lấy danh sách vé
        register_rest_route('nhaxemyduyen/v1', '/tickets', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tickets'),
            'permission_callback' => $require_auth, // Yêu cầu đăng nhập
        ));
    
        // Lấy thông tin một vé
        register_rest_route('nhaxemyduyen/v1', '/tickets/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_ticket'),
            'permission_callback' => $require_auth, // Yêu cầu đăng nhập
        ));
    
        // Tạo vé mới
        register_rest_route('nhaxemyduyen/v1', '/tickets', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_ticket'),
            'permission_callback' => $require_auth, // Yêu cầu đăng nhập
        ));
    
        // Cập nhật vé
        register_rest_route('nhaxemyduyen/v1', '/tickets/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_ticket'),
            'permission_callback' => $require_auth, // Yêu cầu đăng nhập
        ));
    
        // Xóa vé
        register_rest_route('nhaxemyduyen/v1', '/tickets/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_ticket'),
            'permission_callback' => $require_auth, // Yêu cầu đăng nhập
        ));
    
        // Lấy trạng thái ghế của chuyến xe (công khai)
        register_rest_route('nhaxemyduyen/v1', '/trips/(?P<id>\d+)/seats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_seat_availability'),
            'permission_callback' => '__return_true',
        ));
    
        // Kiểm tra vé bằng mã vé và số điện thoại (công khai)
        register_rest_route('nhaxemyduyen/v1', '/tickets/check', array(
            'methods' => 'POST',
            'callback' => array($this, 'check_ticket'),
            'permission_callback' => '__return_true',
        ));
    
        // Tạo nhiều vé
        register_rest_route('nhaxemyduyen/v1', '/tickets/bulk', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_tickets_bulk'),
            'permission_callback' => $require_auth, // Yêu cầu đăng nhập
        ));
    
        // Tạo URL thanh toán VNPAY
        register_rest_route('nhaxemyduyen/v1', '/create-payment', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_vnpay_payment'),
            'permission_callback' => $require_auth, // Yêu cầu đăng nhập
        ));
    
        // Lấy thông tin đơn hàng dựa trên vnp_TxnRef
        register_rest_route('nhaxemyduyen/v1', '/order/(?P<orderId>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_order'),
            'permission_callback' => $require_auth, // Yêu cầu đăng nhập
        ));
    }


    /**
 * Lấy danh sách vé
 */
public function get_tickets($request) {
    global $wpdb;
    $table_tickets = $wpdb->prefix . 'tickets';
    $table_trips = $wpdb->prefix . 'trips';
    $table_routes = $wpdb->prefix . 'routes';
    $table_locations = $wpdb->prefix . 'locations';
    $table_drivers = $wpdb->prefix . 'drivers';
    $table_vehicles = $wpdb->prefix . 'vehicles';

    $user_id = get_current_user_id();
    $tickets = $wpdb->get_results($wpdb->prepare("
        SELECT t.*, tr.departure_time, tr.pickup_location as trip_pickup_location, tr.dropoff_location as trip_dropoff_location,
               l1.name as from_location, l2.name as to_location, 
               d.name as driver_name, v.license_plate as vehicle_plate
        FROM $table_tickets t
        JOIN $table_trips tr ON t.trip_id = tr.trip_id
        JOIN $table_routes r ON tr.route_id = r.route_id
        LEFT JOIN $table_locations l1 ON r.from_location_id = l1.location_id
        LEFT JOIN $table_locations l2 ON r.to_location_id = l2.location_id
        LEFT JOIN $table_drivers d ON tr.driver_id = d.driver_id
        LEFT JOIN $table_vehicles v ON tr.vehicle_id = v.vehicle_id
        WHERE t.user_id = %d
    ", $user_id), ARRAY_A);

    // if (empty($tickets)) {
    //     return new WP_REST_Response(array('message' => 'Không có vé nào thuộc về bạn.'), 200);
    // }

    return new WP_REST_Response($tickets, 200);
}

/**
 * Lấy thông tin một vé
 */
public function get_ticket($request) {
    global $wpdb;
    $table_tickets = $wpdb->prefix . 'tickets';
    $table_trips = $wpdb->prefix . 'trips';
    $table_routes = $wpdb->prefix . 'routes';
    $table_locations = $wpdb->prefix . 'locations';
    $table_drivers = $wpdb->prefix . 'drivers';
    $table_vehicles = $wpdb->prefix . 'vehicles';

    $id = intval($request['id']);
    $user_id = get_current_user_id();
    $ticket = $wpdb->get_row($wpdb->prepare("
        SELECT t.*, tr.departure_time, tr.pickup_location as trip_pickup_location, tr.dropoff_location as trip_dropoff_location,
               l1.name as from_location, l2.name as to_location, 
               d.name as driver_name, v.license_plate as vehicle_plate
        FROM $table_tickets t
        JOIN $table_trips tr ON t.trip_id = tr.trip_id
        JOIN $table_routes r ON tr.route_id = r.route_id
        LEFT JOIN $table_locations l1 ON r.from_location_id = l1.location_id
        LEFT JOIN $table_locations l2 ON r.to_location_id = l2.location_id
        LEFT JOIN $table_drivers d ON tr.driver_id = d.driver_id
        LEFT JOIN $table_vehicles v ON tr.vehicle_id = v.vehicle_id
        WHERE t.ticket_id = %d AND t.user_id = %d
    ", $id, $user_id), ARRAY_A);

    if ($ticket) {
        return new WP_REST_Response($ticket, 200);
    }

    return new WP_Error('not_found', 'Vé xe không tồn tại hoặc không thuộc về bạn', array('status' => 404));
}

    /**
     * Kiểm tra vé bằng mã vé và số điện thoại
     */
    public function check_ticket($request) {
        global $wpdb;
        $table_tickets = $wpdb->prefix . 'tickets';
        $table_trips = $wpdb->prefix . 'trips';
        $table_routes = $wpdb->prefix . 'routes';
        $table_locations = $wpdb->prefix . 'locations';
        $table_drivers = $wpdb->prefix . 'drivers';
        $table_vehicles = $wpdb->prefix . 'vehicles';

        $data = $request->get_json_params();
        $required_fields = ['ticket_code', 'customer_phone'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('invalid_data', "Thiếu trường bắt buộc: $field", array('status' => 400));
            }
        }

        $ticket_code = sanitize_text_field($data['ticket_code']);
        $customer_phone = sanitize_text_field($data['customer_phone']);

        if (!preg_match('/^[0-9]{10,11}$/', $customer_phone)) {
            return new WP_Error('invalid_phone', 'Số điện thoại phải có 10-11 chữ số', array('status' => 400));
        }

        $query = $wpdb->prepare("
            SELECT t.*, tr.departure_time, tr.pickup_location as trip_pickup_location, tr.dropoff_location as trip_dropoff_location,
                   l1.name as from_location, l2.name as to_location, 
                   d.name as driver_name, v.license_plate as vehicle_plate
            FROM $table_tickets t
            JOIN $table_trips tr ON t.trip_id = tr.trip_id
            JOIN $table_routes r ON tr.route_id = r.route_id
            LEFT JOIN $table_locations l1 ON r.from_location_id = l1.location_id
            LEFT JOIN $table_locations l2 ON r.to_location_id = l2.location_id
            LEFT JOIN $table_drivers d ON tr.driver_id = d.driver_id
            LEFT JOIN $table_vehicles v ON tr.vehicle_id = v.vehicle_id
            WHERE t.ticket_code = %s AND t.customer_phone = %s
        ", $ticket_code, $customer_phone);

        error_log('check_ticket SQL query: ' . $query); // Ghi lại truy vấn chính xác

        $ticket = $wpdb->get_row($query, ARRAY_A);

        if ($wpdb->last_error) {
            error_log('check_ticket SQL error: ' . $wpdb->last_error);
            error_log('check_ticket Last query: ' . $wpdb->last_query);
            return new WP_Error('db_error', 'Lỗi truy vấn cơ sở dữ liệu: ' . $wpdb->last_error, array('status' => 500));
        }

        if ($ticket) {
            $ticket['start_location'] = $ticket['from_location'];
            $ticket['end_location'] = $ticket['to_location'];
            return new WP_REST_Response($ticket, 200);
        }

        return new WP_Error('not_found', 'Không tìm thấy vé với thông tin đã nhập', array('status' => 404));
    }

    /**
     * Tạo vé mới
     */
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

        // Bắt đầu giao dịch
        $wpdb->query('START TRANSACTION');

        try {
            // Kiểm tra chuyến xe và ghế trống
            $trip_id = intval($data['trip_id']);
            $seat_number = sanitize_text_field($data['seat_number']);
            $trip = $wpdb->get_row($wpdb->prepare("
                SELECT available_seats, price, pickup_location, dropoff_location 
                FROM $table_trips 
                WHERE trip_id = %d
                FOR UPDATE", 
                $trip_id
            ));

            if (!$trip) {
                throw new Exception('Chuyến xe không tồn tại', 404);
            }
            if ($trip->available_seats <= 0) {
                throw new Exception('Chuyến xe không còn ghế trống', 400);
            }

            // Kiểm tra điểm đón và điểm trả
            $pickup_location = sanitize_text_field($data['pickup_location']);
            $dropoff_location = sanitize_text_field($data['dropoff_location']);
            if ($pickup_location !== $trip->pickup_location || $dropoff_location !== $trip->dropoff_location) {
                throw new Exception('Điểm đón hoặc điểm trả không khớp với chuyến xe', 400);
            }

            // Kiểm tra ghế đã được đặt
            $seat_exists = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM $table_tickets 
                WHERE trip_id = %d AND seat_number = %s", 
                $trip_id, $seat_number
            ));
            if ($seat_exists > 0) {
                throw new Exception('Ghế này đã được đặt', 400);
            }

            // Lấy user_id của người dùng đã đăng nhập
            $user_id = get_current_user_id(); // 0 nếu không đăng nhập

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
                'user_id' => $user_id ?: null, // Lưu user_id
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ));

            if ($result === false) {
                throw new Exception('Không thể tạo vé xe: ' . $wpdb->last_error, 500);
            }

            // Cập nhật số ghế trống
            $wpdb->update(
                $table_trips, 
                array('available_seats' => $trip->available_seats - 1), 
                array('trip_id' => $trip_id)
            );

            // Commit giao dịch
            $wpdb->query('COMMIT');

            return new WP_REST_Response(array(
                'message' => 'Vé xe đã được tạo',
                'ticket_id' => $wpdb->insert_id,
                'ticket_code' => $ticket_code,
                'seat_number' => $seat_number,
                'price' => $trip->price,
                'user_id' => $user_id ?: null,
            ), 201);
        } catch (Exception $e) {
            // Rollback giao dịch nếu có lỗi
            $wpdb->query('ROLLBACK');
            return new WP_Error('error', $e->getMessage(), array('status' => $e->getCode() ?: 500));
        }
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
        $table_routes = $wpdb->prefix . 'routes';
        $table_locations = $wpdb->prefix . 'locations';
        $table_drivers = $wpdb->prefix . 'drivers';
        $table_vehicles = $wpdb->prefix . 'vehicles';
        $trip_id = intval($request['id']);

        // Lấy thông tin chuyến xe
        $trip = $wpdb->get_row($wpdb->prepare("
            SELECT t.*, l1.name as from_location, l2.name as to_location, 
                   d.name as driver_name, v.license_plate as vehicle_plate
            FROM $table_trips t
            JOIN $table_routes r ON t.route_id = r.route_id
            LEFT JOIN $table_locations l1 ON r.from_location_id = l1.location_id
            LEFT JOIN $table_locations l2 ON r.to_location_id = l2.location_id
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

    /**
 * Tạo nhiều vé cùng lúc
 */
public function create_tickets_bulk($request) {
    global $wpdb;
    $table_tickets = $wpdb->prefix . 'tickets';
    $table_trips = $wpdb->prefix . 'trips';
    $data = $request->get_json_params();

    if (empty($data['tickets']) || !is_array($data['tickets'])) {
        return new WP_Error('invalid_data', 'Danh sách vé không hợp lệ', array('status' => 400));
    }

    // Kiểm tra số lượng vé tối đa
    if (count($data['tickets']) > 10) {
        return new WP_Error('too_many_tickets', 'Số lượng vé vượt quá giới hạn (tối đa 10 vé)', array('status' => 400));
    }

    $wpdb->query('START TRANSACTION');

    try {
        $trip_id = intval($data['tickets'][0]['trip_id']);
        $trip = $wpdb->get_row($wpdb->prepare("
            SELECT available_seats, price, pickup_location, dropoff_location 
            FROM $table_trips 
            WHERE trip_id = %d
            FOR UPDATE", 
            $trip_id
        ));

        if (!$trip) {
            throw new Exception('Chuyến xe không tồn tại', 404);
        }

        if ($trip->available_seats < count($data['tickets'])) {
            throw new Exception('Không đủ ghế trống cho số lượng vé yêu cầu', 400);
        }

        // Lấy user_id của người dùng đã đăng nhập
        $user_id = get_current_user_id();

        $ticket_results = [];
        foreach ($data['tickets'] as $ticket_data) {
            // Kiểm tra các trường bắt buộc
            $required_fields = ['customer_name', 'customer_phone', 'seat_number', 'pickup_location', 'dropoff_location'];
            foreach ($required_fields as $field) {
                if (empty($ticket_data[$field])) {
                    throw new Exception("Thiếu trường bắt buộc: $field", 400);
                }
            }

            // Validate phone
            if (!preg_match('/^[0-9]{10,11}$/', $ticket_data['customer_phone'])) {
                throw new Exception('Số điện thoại phải có 10-11 chữ số', 400);
            }

            // Validate seat number
            if (!preg_match('/^A[1-9][0-9]?$/', $ticket_data['seat_number']) || intval(substr($ticket_data['seat_number'], 1)) > 44) {
                throw new Exception('Số ghế không hợp lệ (phải là A1-A44)', 400);
            }

            // Kiểm tra ghế đã được đặt
            $seat_number = sanitize_text_field($ticket_data['seat_number']);
            $seat_exists = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM $table_tickets 
                WHERE trip_id = %d AND seat_number = %s", 
                $trip_id, $seat_number
            ));
            if ($seat_exists > 0) {
                throw new Exception("Ghế $seat_number đã được đặt", 400);
            }

            // Tạo mã vé
            $ticket_code = 'TICKET-' . strtoupper(substr(md5(uniqid()), 0, 8));
            $result = $wpdb->insert($table_tickets, array(
                'ticket_code' => $ticket_code,
                'trip_id' => $trip_id,
                'customer_name' => sanitize_text_field($ticket_data['customer_name']),
                'customer_phone' => sanitize_text_field($ticket_data['customer_phone']),
                'customer_email' => sanitize_email($ticket_data['customer_email'] ?? ''),
                'pickup_location' => sanitize_text_field($ticket_data['pickup_location']),
                'dropoff_location' => sanitize_text_field($ticket_data['dropoff_location']),
                'seat_number' => $seat_number,
                'status' => sanitize_text_field($ticket_data['status'] ?? 'Chưa thanh toán'),
                'note' => sanitize_text_field($ticket_data['note'] ?? ''),
                'user_id' => $user_id ?: null, // Lưu user_id
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ));

            if ($result === false) {
                throw new Exception('Không thể tạo vé xe: ' . $wpdb->last_error, 500);
            }

            $ticket_results[] = array(
                'ticket_id' => $wpdb->insert_id,
                'ticket_code' => $ticket_code,
                'seat_number' => $seat_number,
                'user_id' => $user_id ?: null,
            );
        }

        $wpdb->update(
            $table_trips, 
            array('available_seats' => $trip->available_seats - count($data['tickets'])), 
            array('trip_id' => $trip_id)
        );

        $wpdb->query('COMMIT');

        return new WP_REST_Response(array(
            'message' => 'Các vé xe đã được tạo',
            'tickets' => $ticket_results,
            'price' => $trip->price,
        ), 201);
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('error', $e->getMessage(), array('status' => $e->getCode() ?: 500));
    }
}

    /**
 * Tạo URL thanh toán VNPAY
 */
public function create_vnpay_payment(WP_REST_Request $request) {
    global $wpdb;
    $table_tickets = $wpdb->prefix . 'tickets';
    $table_trips = $wpdb->prefix . 'trips';

    date_default_timezone_set('Asia/Ho_Chi_Minh');

    function log_error($message) {
        error_log(date('[Y-m-d H:i:s e] ') . $message . PHP_EOL, 3, plugin_dir_path(__FILE__) . '../vnpay_php/vnpay_error.log');
    }

    require_once plugin_dir_path(__FILE__) . '../vnpay_php/config.php';

    $params = $request->get_json_params();
    $ticketIds = isset($params['ticketIds']) ? $params['ticketIds'] : [];
    $amount = isset($params['amount']) ? $params['amount'] : 0;
    $language = isset($params['language']) ? $params['language'] : 'vn';
    $bankCode = isset($params['bankCode']) ? $params['bankCode'] : '';

    if (empty($ticketIds) || $amount <= 0) {
        log_error("Lỗi: Dữ liệu không hợp lệ - ticketIds: " . print_r($ticketIds, true) . ", amount: $amount");
        return new WP_Error('invalid_data', 'Dữ liệu không hợp lệ', array('status' => 400));
    }

    // Kiểm tra vé thuộc về người dùng
    $user_id = get_current_user_id();
    $total_price = 0;
    foreach ($ticketIds as $ticketId) {
        $ticket = $wpdb->get_row($wpdb->prepare("
            SELECT t.ticket_id, t.user_id, tr.price
            FROM $table_tickets t
            JOIN $table_trips tr ON t.trip_id = tr.trip_id
            WHERE t.ticket_id = %d", $ticketId));
        if (!$ticket) {
            log_error("Vé $ticketId không tồn tại");
            return new WP_Error('invalid_ticket', "Vé $ticketId không tồn tại", array('status' => 404));
        }
        if ($ticket->user_id != $user_id) {
            log_error("Người dùng $user_id không có quyền truy cập vé $ticketId");
            return new WP_Error('unauthorized', "Bạn không có quyền truy cập vé $ticketId", array('status' => 403));
        }
        $total_price += $ticket->price;
    }

    // Kiểm tra số tiền
    if ($total_price != $amount) {
        log_error("Số tiền không khớp: yêu cầu $amount, thực tế $total_price");
        return new WP_Error('invalid_amount', 'Số tiền không khớp với giá vé', array('status' => 400));
    }

    $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
    $txnRef = rand(1, 10000);
    $ticketIdsStr = json_encode($ticketIds);
    $orderInfo = "Thanh toan GD: $txnRef - Tickets: $ticketIdsStr";

    if (strlen($orderInfo) > 255) {
        log_error("Lỗi: vnp_OrderInfo vượt quá 255 ký tự: " . $orderInfo);
        return new WP_Error('order_info_too_long', 'Thông tin giao dịch quá dài', array('status' => 400));
    }

    // Lưu vnp_TxnRef vào bảng wp_tickets
    foreach ($ticketIds as $ticketId) {
        $result = $wpdb->update(
            $table_tickets,
            array(
                'vnp_TxnRef' => $txnRef,
                'updated_at' => current_time('mysql'),
            ),
            array('ticket_id' => $ticketId),
            array('%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            log_error("Lỗi khi lưu vnp_TxnRef cho ticket $ticketId: " . $wpdb->last_error);
            return new WP_Error('db_error', 'Lỗi khi lưu thông tin giao dịch', array('status' => 500));
        }
    }

    $inputData = array(
        "vnp_Version" => "2.1.0",
        "vnp_TmnCode" => $vnp_TmnCode,
        "vnp_Amount" => $amount * 100,
        "vnp_Command" => "pay",
        "vnp_CreateDate" => date('YmdHis'),
        "vnp_CurrCode" => "VND",
        "vnp_IpAddr" => $vnp_IpAddr,
        "vnp_Locale" => $language,
        "vnp_OrderInfo" => $orderInfo,
        "vnp_OrderType" => "other",
        "vnp_ReturnUrl" => $vnp_Returnurl,
        "vnp_TxnRef" => $txnRef,
        "vnp_ExpireDate" => $expire,
    );

    if (!empty($bankCode)) {
        $inputData['vnp_BankCode'] = $bankCode;
    }

    ksort($inputData);
    $query = "";
    $i = 0;
    $hashdata = "";
    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
        $query .= urlencode($key) . "=" . urlencode($value) . '&';
    }

    $vnp_Url = $vnp_Url . "?" . $query;
    if (!isset($vnp_HashSecret)) {
        log_error("Lỗi: Thiếu vnp_HashSecret trong cấu hình.");
        return new WP_Error('config_error', 'Cấu hình VNPAY không hợp lệ', array('status' => 500));
    }

    $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
    $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;

    return rest_ensure_response(array(
        'payment_url' => $vnp_Url,
        'txnRef' => $txnRef,
    ));
}

    /**
 * Lấy thông tin đơn hàng dựa trên vnp_TxnRef
 */
public function get_order($request) {
    global $wpdb;
    $table_tickets = $wpdb->prefix . 'tickets';
    $table_trips = $wpdb->prefix . 'trips';
    $table_routes = $wpdb->prefix . 'routes';
    $table_locations = $wpdb->prefix . 'locations';
    $table_drivers = $wpdb->prefix . 'drivers';
    $table_vehicles = $wpdb->prefix . 'vehicles';

    $orderId = intval($request['orderId']);
    $user_id = get_current_user_id();
    $tickets = $wpdb->get_results($wpdb->prepare("
        SELECT t.*, tr.departure_time, tr.pickup_location as trip_pickup_location, tr.dropoff_location as trip_dropoff_location,
               l1.name as from_location, l2.name as to_location, 
               d.name as driver_name, v.license_plate as vehicle_plate,
               tr.price
        FROM $table_tickets t
        JOIN $table_trips tr ON t.trip_id = tr.trip_id
        JOIN $table_routes r ON tr.route_id = r.route_id
        LEFT JOIN $table_locations l1 ON r.from_location_id = l1.location_id
        LEFT JOIN $table_locations l2 ON r.to_location_id = l2.location_id
        LEFT JOIN $table_drivers d ON tr.driver_id = d.driver_id
        LEFT JOIN $table_vehicles v ON tr.vehicle_id = v.vehicle_id
        WHERE t.vnp_TxnRef = %d AND t.user_id = %d
    ", $orderId, $user_id), ARRAY_A);

    if (empty($tickets)) {
        return new WP_Error('not_found', 'Không tìm thấy đơn hàng hoặc không thuộc về bạn', array('status' => 404));
    }

    $order = [
        'status' => $tickets[0]['status'],
        'billing' => [
            'first_name' => $tickets[0]['customer_name'],
            'phone' => $tickets[0]['customer_phone'],
            'email' => $tickets[0]['customer_email'],
        ],
        'meta_data' => [
            'ticket_codes' => array_column($tickets, 'ticket_code'),
            'seats' => implode(', ', array_column($tickets, 'seat_number')),
            'pickup' => $tickets[0]['pickup_location'],
            'dropoff' => $tickets[0]['dropoff_location'],
            'note' => $tickets[0]['note'],
            'driver_name' => $tickets[0]['driver_name'] ?: 'Chưa chọn',
            'vehicle_plate' => $tickets[0]['vehicle_plate'] ?: 'Chưa chọn',
        ],
        'trip_info' => [
            'departure_time' => $tickets[0]['departure_time'],
        ],
        'total' => array_sum(array_column($tickets, 'price')) ?: 0,
    ];

    return new WP_REST_Response($order, 200);
}
}



new Ticket_API();