import { useState, useEffect } from "react";
import { useNavigate, useLocation } from "react-router-dom";

function PaymentStatus() {
  const [order, setOrder] = useState(null);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    const query = new URLSearchParams(location.search);
    const orderId = query.get("vnp_TxnRef");
    const responseCode = query.get("vnp_ResponseCode");

    if (!orderId || !responseCode) {
      setError("Không tìm thấy thông tin thanh toán.");
      setLoading(false);
      return;
    }

    fetch(`http://localhost:8000/wp-json/nhaxemyduyen/v1/order/${orderId}`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Không tìm thấy đơn hàng (Mã lỗi: ${response.status})`);
        }
        return response.json();
      })
      .then((data) => {
        if (!data || typeof data !== "object") {
          throw new Error("Dữ liệu đơn hàng không hợp lệ.");
        }
        setOrder(data);
        setLoading(false);
      })
      .catch((err) => {
        setError("Lỗi khi kiểm tra trạng thái đơn hàng: " + err.message);
        setLoading(false);
      });
  }, [location]);

  if (loading) {
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
    <div className="max-w-3xl mx-auto p-6">
      {isSuccess ? (
        <div className="bg-green-50 p-6 rounded-lg">
          <h2 className="text-2xl font-bold text-green-600 mb-4 flex items-center gap-2">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-6 w-6"
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
          <p className="text-gray-700 mb-4">
            Cảm ơn bạn đã đặt vé tại Nhà Xe Mỹ Duyên. Dưới đây là thông tin vé của bạn:
          </p>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-gray-700 mb-4">
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
              <span className="font-semibold">Chuyến xe:</span> {pickup} → {dropoff}
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
            <p>
              <span className="font-semibold">Ghi chú:</span> {note}
            </p>
            <p>
              <span className="font-semibold">Tổng tiền:</span> {total}đ
            </p>
          </div>
          <div className="mb-4">
            <p className="text-gray-700 font-semibold">Mã vé của bạn:</p>
            {ticketCodes.length > 0 ? (
              ticketCodes.map((code, idx) => (
                <p key={idx} className="text-green-600 font-bold text-lg">
                  {code}
                </p>
              ))
            ) : (
              <p className="text-gray-600">Không có mã vé.</p>
            )}
          </div>
          <p className="text-gray-600 mb-6">
            Thông tin vé đã được gửi qua email và SMS. Vui lòng kiểm tra!
          </p>
          <div className="flex justify-center gap-4">
            <button
              onClick={() => navigate("/tickets")}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Tra cứu vé
            </button>
            <button
              onClick={() => navigate("/search")}
              className="px-4 py-2 bg-blue-500 text-white rounded-lg
               hover:bg-blue-600 transition-colors"
            >
              Tìm chuyến xe khác
            </button>
          </div>
        </div>
      ) : (
        <div className="bg-red-50 p-6 rounded-lg text-center">
          <h2 className="text-2xl font-bold text-red-600 mb-4">Thanh toán thất bại</h2>
          <p className="text-red-700 mb-6">
            Thanh toán của bạn không thành công. Vui lòng thử lại.
          </p>
          <div className="flex justify-center gap-4">
            <button
              onClick={() => navigate("/search")}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg
               hover:bg-blue-700 transition-colors"
            >
              Thử lại
            </button>
            <button
              onClick={() => navigate("/tickets")}
              className="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition-colors"
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