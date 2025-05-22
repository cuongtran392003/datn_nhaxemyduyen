import { useState, useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import axios from "axios";
import { useAuth } from "../contexts/AuthContext";
import BackToTop from '../Shared/BackToTop';


const API_BASE_URL = "http://localhost:8000/wp-json";

const TicketHistory = () => {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // Chuyển hướng nếu chưa đăng nhập
  useEffect(() => {
    if (!user || !user.id) {
      navigate("/signin");
    }
  }, [user, navigate]);

  // Lấy lịch sử vé từ API
  useEffect(() => {
    const fetchTicketHistory = async () => {
      if (!user || !user.id) return;
  
      setLoading(true);
      setError("");
  
      try {
        const response = await axios.get(
          `${API_BASE_URL}/nhaxemyduyen/v1/tickets?user_id=${user.id}`,
          {
            headers: {
              Authorization: `Bearer ${user.token}`,
            },
          }
        );
  
        setTickets(response.data || []);
      } catch (error) {
        console.error("Lỗi lấy lịch sử vé:", error);
        const errorMessage =
          error.response?.data?.message ||
          error.message ||
          "Không thể tải lịch sử vé. Vui lòng thử lại sau.";
        setError(errorMessage);
        console.log("Chi tiết lỗi:", {
          status: error.response?.status,
          data: error.response?.data,
          message: error.message,
        });
      } finally {
        setLoading(false);
      }
    };
  
    fetchTicketHistory();
  }, [user]);

  // Format ngày giờ
  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString("vi-VN", {
      dateStyle: "short",
      timeStyle: "short",
    });
  };

  // Format tiền
  const formatPrice = (price) => {
    return new Intl.NumberFormat("vi-VN", {
      style: "currency",
      currency: "VND",
    }).format(price || 0);
  };

  // Map trạng thái vé sang nhãn và màu sắc
  const getStatusInfo = (status) => {
    switch (status) {
      case "confirmed":
        return { label: "Đã xác nhận", color: "bg-green-100 text-green-700" };
      case "pending":
        return { label: "Đang chờ", color: "bg-yellow-100 text-yellow-700" };
      case "cancelled":
        return { label: "Đã hủy", color: "bg-red-100 text-red-700" };
      default:
        return { label: "Không xác định", color: "bg-gray-100 text-gray-700" };
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100 flex flex-col items-center p-6 font-poppins">
      <div className="w-full max-w-5xl bg-white rounded-2xl shadow-2xl p-8 md:p-12">
        <h2 className="text-3xl font-bold text-gray-800 mb-6">
          Lịch sử đặt vé
        </h2>
        <p className="text-sm text-gray-500 mb-8">
          Xem lại các vé bạn đã đặt
        </p>

        {error && (
          <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg animate-fade-in">
            {error}
          </div>
        )}

        {loading ? (
          <div className="flex justify-center items-center h-64">
            <svg
              className="animate-spin h-8 w-8 text-indigo-600"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
              />
              <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v8z"
              />
            </svg>
          </div>
        ) : tickets.length === 0 ? (
          <div className="text-center text-gray-600 py-12">
            <p>Không có vé nào trong lịch sử đặt vé.</p>
            <Link
              to="/services"
              className="text-indigo-600 hover:underline font-semibold mt-4 inline-block"
            >
              Đặt vé ngay
            </Link>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-gray-50">
                  <th className="p-4 text-sm font-semibold text-gray-700">
                    Mã vé
                  </th>
                  <th className="p-4 text-sm font-semibold text-gray-700">
                    Điểm đi
                  </th>
                  <th className="p-4 text-sm font-semibold text-gray-700">
                    Điểm đến
                  </th>
                  <th className="p-4 text-sm font-semibold text-gray-700">
                    Ngày khởi hành
                  </th>
                  <th className="p-4 text-sm font-semibold text-gray-700">
                    Trạng thái
                  </th>
                </tr>
              </thead>
              <tbody>
                {tickets.map((ticket) => {
                  const { label, color } = getStatusInfo(ticket.status);
                  return (
                    <tr
                      key={ticket.ticket_id}
                      className="border-b border-gray-200 hover:bg-gray-50"
                    >
                      <td className="p-4 text-sm text-gray-900">{ticket.ticket_code}</td>
                      <td className="p-4 text-sm text-gray-900">
                        {ticket.from_location}
                      </td>
                      <td className="p-4 text-sm text-gray-900">
                        {ticket.to_location}
                      </td>
                      <td className="p-4 text-sm text-gray-900">
                        {formatDate(ticket.departure_time)}
                      </td>
                      <td className="p-4 text-sm text-gray-900">
                        {ticket.status}
                      </td>
                      <td className="p-4">
                        <Link
                          to={`/ticket/${ticket.ticket_id}`}
                          className="text-indigo-600 hover:underline font-medium"
                        >
                          Xem chi tiết
                        </Link>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}

        <p className="mt-8 text-sm text-center text-gray-600">
          Quay lại{" "}
          <Link
            to="/"
            className="text-indigo-600 hover:underline font-semibold"
          >
            Trang chủ
          </Link>
        </p>
      </div>
      <BackToTop></BackToTop>
    </div>
  );
};

export default TicketHistory;