import { useState, useEffect } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import { useAuth } from "./contexts/AuthContext"; // Import useAuth
import { useNotification } from "./contexts/NotificationContext";
import axios from "axios"; // Import axios

function PaymentStatus() {
  const [order, setOrder] = useState(null);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(true);  const [hasNotified, setHasNotified] = useState(false); // Thêm flag để tránh thông báo lặp
  const navigate = useNavigate();
  const location = useLocation();
  const { token, isLoading: authLoading } = useAuth(); // Get token and auth loading state
  const { notifyPaymentSuccess, notifyBookingSuccess, notifyError } = useNotification();

  useEffect(() => {
    if (authLoading) return; // Wait for auth to initialize

    if (!token) {
      setError("Vui lòng đăng nhập để xem trạng thái đơn hàng.");
      setLoading(false);
      navigate("/login");
      return;
    }

    const query = new URLSearchParams(location.search);
    const orderId = query.get("vnp_TxnRef");
    const responseCode = query.get("vnp_ResponseCode");

    if (!orderId || !responseCode) {
      setError("Không tìm thấy thông tin thanh toán.");
      setLoading(false);
      return;
    }    axios
      .get(`http://localhost:8000/wp-json/nhaxemyduyen/v1/order/${orderId}`)
      .then((response) => {
        if (!response.data || typeof response.data !== "object") {
          throw new Error("Dữ liệu đơn hàng không hợp lệ.");
        }        setOrder(response.data);
        
        // Thêm thông báo thanh toán thành công nếu responseCode là 00 và chưa thông báo
        const notificationKey = `payment_notified_${orderId}`;
        const hasAlreadyNotified = localStorage.getItem(notificationKey);
          if (responseCode === "00" && !hasAlreadyNotified && !hasNotified) {
          // Thông báo thanh toán thành công
          notifyPaymentSuccess({
            amount: response.data.total,
            orderId: orderId,
            transactionRef: orderId,
            method: "VNPAY"
          });
          
          // Thông báo đặt vé thành công
          notifyBookingSuccess({
            seatCount: 1, // Có thể lấy từ response.data nếu có
            route: "Chuyến xe đã thanh toán",
            totalAmount: response.data.total,
            departureTime: response.data.trip_info?.departure_time,
            ticketCode: orderId
          });
          
          // Đánh dấu đã thông báo
          localStorage.setItem(notificationKey, 'true');
          setHasNotified(true);
        }
        
        setLoading(false);
      })
      .catch((err) => {
        const message = err.response?.data?.message || err.message;
        setError("Lỗi khi kiểm tra trạng thái đơn hàng: " + message);
        
        // Thêm thông báo lỗi
        notifyError({
          message: message,
          details: { orderId, responseCode }
        });
        
        setLoading(false);
      });
  }, [location, navigate, token, authLoading, notifyPaymentSuccess, notifyBookingSuccess, notifyError, hasNotified]);

  // Hàm xử lý tìm chuyến xe khác: reset bộ lọc và chuyển về trang tìm kiếm
  const handleFindAnotherTrip = () => {
    navigate("/search", { state: { reset: true } });
  };

  if (loading || authLoading) {
    return (
      <div className="flex justify-center items-center h-screen">
        <div className="text-xl font-semibold text-gray-600">Đang tải...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-3xl mx-auto p-6">
        <div className="bg-red-50 p-6 rounded-lg text-center">
          <h2 className="text-2xl font-bold text-red-600 mb-4">Lỗi</h2>
          <p className="text-red-700 mb-6">{error}</p>
          <button
            onClick={() => navigate("/search")}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Quay lại tìm kiếm
          </button>
        </div>
      </div>
    );
  }

  const isSuccess = order?.status === "Đã thanh toán";
  const ticketCodes = Array.isArray(order?.meta_data?.ticket_codes)
    ? order.meta_data.ticket_codes
    : [];
  const seats = order?.meta_data?.seats || "Không xác định";
  const pickup = order?.meta_data?.pickup || "Không xác định";
  const dropoff = order?.meta_data?.dropoff || "Không xác định";
  const note = order?.meta_data?.note || "Không có ghi chú";
  const driver_name = order?.meta_data?.driver_name || "Chưa chọn";
  const vehicle_plate = order?.meta_data?.vehicle_plate || "Chưa chọn";
  const departure_time = order?.trip_info?.departure_time || "Không xác định";
  const customer_name = order?.billing?.first_name || "Không xác định";
  const phone = order?.billing?.phone || "Không xác định";
  const email = order?.billing?.email || "Không xác định";
  const total = order?.total
    ? parseFloat(order.total).toLocaleString("vi-VN")
    : "0";

  return (
    <div className="max-w-3xl mx-auto p-6 font-roboto">
      {isSuccess ? (
        <div className="bg-gradient-to-br from-green-50 via-white to-blue-50 p-10 rounded-3xl shadow-2xl border-2 border-green-200">
          <h2 className="text-3xl font-extrabold text-green-700 mb-6 flex items-center gap-3 justify-center font-montserrat">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-10 w-10"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fillRule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clipRule="evenodd"
              />
            </svg>
            Thanh toán thành công!
          </h2>
          <p className="text-gray-700 mb-6 text-lg text-center font-roboto">
            Cảm ơn bạn đã đặt vé tại{" "}
            <span className="font-bold text-blue-700 font-montserrat">
              Nhà Xe Mỹ Duyên
            </span>
            .
            <br />
            Dưới đây là thông tin vé của bạn:
          </p>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 text-gray-700 mb-6 text-base">
            <p>
              <span className="font-semibold">Khách hàng:</span> {customer_name}
            </p>
            <p>
              <span className="font-semibold">Số điện thoại:</span> {phone}
            </p>
            <p>
              <span className="font-semibold">Email:</span> {email}
            </p>
            <p>
              <span className="font-semibold">Chuyến xe:</span> {pickup} →{" "}
              {dropoff}
            </p>
            <p>
              <span className="font-semibold">Thời gian khởi hành:</span>{" "}
              {departure_time}
            </p>
            <p>
              <span className="font-semibold">Ghế:</span> {seats}
            </p>
            <p>
              <span className="font-semibold">Tài xế:</span> {driver_name}
            </p>
            <p>
              <span className="font-semibold">Biển số xe:</span> {vehicle_plate}
            </p>
            <p className="sm:col-span-2">
              <span className="font-semibold">Ghi chú:</span> {note}
            </p>
            <p className="sm:col-span-2">
              <span className="font-semibold">Tổng tiền:</span>{" "}
              <span className="text-lg text-green-700 font-bold">
                {total}đ
              </span>
            </p>
          </div>
          <div className="mb-6">
            <p className="text-gray-700 font-semibold mb-2">Mã vé của bạn:</p>
            {ticketCodes.length > 0 ? (
              <div className="flex flex-wrap gap-3 justify-center">
                {ticketCodes.map((code, idx) => (
                  <span
                    key={idx}
                    className="bg-green-100 text-green-700 px-5 py-2 rounded-xl font-bold text-lg font-montserrat border-2 border-green-300 shadow"
                  >
                    {code}
                  </span>
                ))}
              </div>
            ) : (
              <p className="text-gray-600">Không có mã vé.</p>
            )}
          </div>
          <p className="text-gray-600 mb-8 text-center">
            Thông tin vé đã được gửi qua email và SMS. Vui lòng kiểm tra!
          </p>
          <div className="flex flex-col sm:flex-row justify-center gap-4">
            <button
              onClick={() => navigate("/tickets")}
              className="px-8 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-semibold font-montserrat text-lg shadow"
            >
              Tra cứu vé
            </button>
            <button
              onClick={handleFindAnotherTrip}
              className="px-8 py-3 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition-colors font-semibold font-montserrat text-lg shadow"
            >
              Tìm chuyến xe khác
            </button>
          </div>
        </div>
      ) : (
        <div className="bg-gradient-to-br from-red-50 via-white to-blue-50 p-10 rounded-3xl shadow-2xl border-2 border-red-200 text-center">
          <h2 className="text-3xl font-extrabold text-red-600 mb-6 font-montserrat flex items-center gap-2 justify-center">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-10 w-10"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
            Thanh toán thất bại
          </h2>
          <p className="text-red-700 mb-8 text-lg font-roboto">
            Thanh toán của bạn không thành công. Vui lòng thử lại.
          </p>
          <div className="flex flex-col sm:flex-row justify-center gap-4">
            <button
              onClick={handleFindAnotherTrip}
              className="px-8 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-semibold font-montserrat text-lg shadow"
            >
              Thử lại
            </button>
            <button
              onClick={() => navigate("/tickets")}
              className="px-8 py-3 bg-gray-400 text-white rounded-xl hover:bg-gray-500 transition-colors font-semibold font-montserrat text-lg shadow"
            >
              Tra cứu vé
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

export default PaymentStatus;