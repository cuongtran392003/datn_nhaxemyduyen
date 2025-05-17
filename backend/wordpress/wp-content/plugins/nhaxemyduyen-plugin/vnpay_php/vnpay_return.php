<?php
// Ensure no output before headers
ob_start();

// Bao gồm file WordPress core để khởi tạo $wpdb
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
}

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
date_default_timezone_set('Asia/Ho_Chi_Minh');

/**
 * Xử lý phản hồi VNPAY và chuyển hướng về client
 */
require_once("./config.php");

// Ghi log để debug
function log_error($message) {
    $logFile = __DIR__ . '/vnpay_error.log';
    $message = date('[Y-m-d H:i:s e] ') . $message . PHP_EOL;
    error_log($message, 3, $logFile);
    if (!is_writable($logFile)) {
        error_log("Lỗi: Không thể ghi vào file log: $logFile", 0);
    }
}

// Truy cập cơ sở dữ liệu WordPress
global $wpdb;
if (!$wpdb) {
    log_error("Lỗi: Không thể khởi tạo \$wpdb.");
    http_response_code(500);
    ob_end_clean();
    exit('Lỗi máy chủ: Không thể kết nối cơ sở dữ liệu.');
}

$table_tickets = $wpdb->prefix . 'tickets';

// Kiểm tra vnp_SecureHash
$vnp_SecureHash = isset($_GET['vnp_SecureHash']) ? trim($_GET['vnp_SecureHash']) : '';
if (empty($vnp_SecureHash)) {
    log_error("Lỗi: Thiếu vnp_SecureHash trong phản hồi từ VNPAY: " . print_r($_GET, true));
    http_response_code(500);
    ob_end_clean();
    exit('Lỗi: Dữ liệu phản hồi không hợp lệ.');
}

// Kiểm tra biến $vnp_HashSecret từ config
if (!isset($vnp_HashSecret) || empty(trim($vnp_HashSecret))) {
    log_error("Lỗi: Thiếu hoặc không hợp lệ vnp_HashSecret trong cấu hình: " . print_r(get_defined_vars(), true));
    http_response_code(500);
    ob_end_clean();
    exit('Lỗi máy chủ: Cấu hình VNPAY không hợp lệ.');
}

$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = trim($value);
    }
}

unset($inputData['vnp_SecureHash']);
ksort($inputData);
$hashData = "";
$i = 0;
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, trim($vnp_HashSecret));
log_error("Tính toán secureHash: $secureHash, Nhận được vnp_SecureHash: $vnp_SecureHash");

// Lấy vnp_TxnRef
$vnp_TxnRef = isset($_GET['vnp_TxnRef']) ? trim($_GET['vnp_TxnRef']) : '';
if (empty($vnp_TxnRef)) {
    log_error("Lỗi: Thiếu vnp_TxnRef trong phản hồi từ VNPAY.");
    http_response_code(500);
    ob_end_clean();
    exit('Lỗi: Thiếu mã giao dịch.');
}

// Cập nhật trạng thái vé trong cơ sở dữ liệu
$vnp_ResponseCode = isset($_GET['vnp_ResponseCode']) ? trim($_GET['vnp_ResponseCode']) : '';
log_error("vnp_ResponseCode: $vnp_ResponseCode");
if ($secureHash === $vnp_SecureHash && $vnp_ResponseCode === '00') {
    $result = $wpdb->update(
        $table_tickets,
        array(
            'status' => 'Đã thanh toán',
            'updated_at' => current_time('mysql'),
        ),
        array('vnp_TxnRef' => $vnp_TxnRef),
        array('%s', '%s'),
        array('%s')
    );

    if ($result === false) {
        log_error("Lỗi khi cập nhật trạng thái vé với vnp_TxnRef $vnp_TxnRef: " . $wpdb->last_error);
    } else {
        log_error("Cập nhật thành công vé với vnp_TxnRef $vnp_TxnRef");
    }
} else {
    log_error("Không cập nhật trạng thái: secureHash match: " . ($secureHash === $vnp_SecureHash ? 'true' : 'false') . ", vnp_ResponseCode: $vnp_ResponseCode");
}

// Chuẩn bị URL chuyển hướng đến client (PaymentStatus)
$client_redirect_url = 'http://localhost:3000/payment-status';
$query_params = http_build_query([
    'vnp_TxnRef' => $vnp_TxnRef,
    'vnp_ResponseCode' => $vnp_ResponseCode,
    'vnp_Amount' => $_GET['vnp_Amount'] ?? '',
    'vnp_TransactionNo' => $_GET['vnp_TransactionNo'] ?? '',
    'vnp_BankCode' => $_GET['vnp_BankCode'] ?? '',
    'vnp_PayDate' => $_GET['vnp_PayDate'] ?? '',
]);

$redirect_url = $client_redirect_url . '?' . $query_params;
log_error("Chuyển hướng đến: $redirect_url");

// Chuyển hướng đến client
header('Location: ' . $redirect_url, true, 302);

// Fallback HTML nếu chuyển hướng thất bại
ob_end_clean();
echo '<!DOCTYPE html><html><body>';
echo '<p>Đang chuyển hướng đến trang trạng thái thanh toán... Nếu không tự động chuyển, <a href="' . htmlspecialchars($redirect_url) . '">nhấn vào đây</a>.</p>';
echo '<script>window.location.href = "' . htmlspecialchars($redirect_url) . '";</script>';
echo '</body></html>';
exit();