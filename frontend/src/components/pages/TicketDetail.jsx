import { useLocation, useParams, useNavigate } from "react-router-dom";
import { useEffect, useState } from "react";
import axios from "axios";

const API_BASE_URL = "http://localhost:8000/wp-json";

function TicketDetail() {
  const location = useLocation();
  const { ticket_id } = useParams();
  const navigate = useNavigate();
  const [ticket, setTicket] = useState(location.state?.ticket || null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    // Nếu không có dữ liệu vé từ state, gọi API lấy chi tiết vé
    if (!ticket) {
      setLoading(true);
      axios
        .get(`${API_BASE_URL}/nhaxemyduyen/v1/ticket/${ticket_id}`)
        .then((res) => {
          setTicket(res.data);
        })
        .catch((err) => {
          setError(
            err.response?.data?.message || "Không thể tải chi tiết vé."
          );
        })
        .finally(() => setLoading(false));
    }
  }, [ticket, ticket_id]);

  // Format ngày giờ
  const formatDate = (dateString) => {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleString("vi-VN", {
      dateStyle: "short",
      timeStyle: "short",
    });
  };

  // Map trạng thái vé sang nhãn và màu sắc
  const getStatusInfo = (status) => {
    switch (status) {
      case "Đã thanh toán":
        return {
          label: "Đã thanh toán",
          color: "bg-green-100 text-green-700 border-green-400",
        };
      case "Chưa thanh toán":
        return {
          label: "Chưa thanh toán",
          color: "bg-yellow-100 text-yellow-700 border-yellow-400",
        };
      case "Đã hủy":
        return {
          label: "Đã hủy",
          color: "bg-red-100 text-red-700 border-red-400",
        };
      default:
        return {
          label: status || "Không xác định",
          color: "bg-gray-100 text-gray-700 border-gray-400",
        };
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <span>Đang tải chi tiết vé...</span>
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-center text-red-600 py-12">
        {error}
        <button
          className="block mt-4 text-blue-600 underline"
          onClick={() => navigate(-1)}
        >
          Quay lại
        </button>
      </div>
    );
  }

  if (!ticket) return null;

  const { label: statusLabel, color: statusColor } = getStatusInfo(ticket.status);

  return (
    <div className="min-h-screen flex flex-col items-center p-6 font-poppins bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100">
      <div className="w-full max-w-2xl bg-white rounded-2xl shadow-xl p-8 md:p-12 mt-8">
        <div className="flex flex-col items-center mb-8">
          <div className="text-lg font-semibold text-gray-500 mb-2">Mã vé</div>
          <div className="text-3xl font-extrabold text-indigo-700 tracking-widest mb-2">
            {ticket.ticket_code}
          </div>
          <span
            className={`px-4 py-1 rounded-full border text-base font-medium ${statusColor}`}
          >
            {statusLabel}
          </span>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-gray-700 mb-6">
          <div className="bg-indigo-50 rounded-xl p-4">
            <div className="font-medium text-gray-600 mb-1">Khách hàng</div>
            <div className="text-lg font-semibold">{ticket.customer_name}</div>
            <div className="text-sm text-gray-500">
              SĐT: {ticket.customer_phone}
            </div>
            <div className="text-sm text-gray-500">
              Email: {ticket.customer_email || "Không có"}
            </div>
          </div>
          <div className="bg-pink-50 rounded-xl p-4">
            <div className="font-medium text-gray-600 mb-1">Hành trình</div>
            <div className="text-lg font-semibold">
              {ticket.start_location} → {ticket.end_location}
            </div>
            <div className="text-sm text-gray-500">
              Điểm đón: {ticket.pickup_location}
            </div>
            <div className="text-sm text-gray-500">
              Điểm trả: {ticket.dropoff_location}
            </div>
          </div>
          <div className="bg-purple-50 rounded-xl p-4">
            <div className="font-medium text-gray-600 mb-1">
              Thời gian khởi hành
            </div>
            <div className="text-lg font-semibold">
              {formatDate(ticket.departure_time)}
            </div>
          </div>
          <div className="bg-yellow-50 rounded-xl p-4">
            <div className="font-medium text-gray-600 mb-1">Ghế</div>
            <div className="text-lg font-semibold">{ticket.seat_number}</div>
          </div>
          <div className="bg-green-50 rounded-xl p-4">
            <div className="font-medium text-gray-600 mb-1">Tài xế</div>
            <div className="text-lg font-semibold">
              {ticket.driver_name || "Chưa chọn"}
            </div>
          </div>
          <div className="bg-blue-50 rounded-xl p-4">
            <div className="font-medium text-gray-600 mb-1">Phương tiện</div>
            <div className="text-lg font-semibold">
              {ticket.vehicle_plate || "Chưa chọn"}
            </div>
          </div>
          <div className="bg-gray-50 rounded-xl p-4 sm:col-span-2">
            <div className="font-medium text-gray-600 mb-1">Ghi chú</div>
            <div className="text-base">
              {ticket.note || "Không có ghi chú"}
            </div>
          </div>
        </div>
        <button
          className="mt-4 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold transition"
          onClick={() => navigate(-1)}
        >
          Quay lại
        </button>
      </div>
    </div>
  );
}

export default TicketDetail;
