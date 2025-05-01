import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import ticketService from "../service/ticketService";

function SeatSelection({ selectedTrip, onBack }) {
  const [step, setStep] = useState(1);
  const [selectedSeats, setSelectedSeats] = useState([]);
  const [bookedSeats, setBookedSeats] = useState([]);
  const [seatsData, setSeatsData] = useState([]);
  const [totalPrice, setTotalPrice] = useState(0);
  const [pickupDropoff, setPickupDropoff] = useState({
    pickup: selectedTrip?.pickup_location || "",
    dropoff: selectedTrip?.dropoff_location || "",
  });
  const [userInfo, setUserInfo] = useState({
    name: "",
    phone: "",
    email: "",
    note: "",
  });
  const [tripDetails, setTripDetails] = useState({
    driver_name: "",
    vehicle_plate: "",
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const navigate = useNavigate();

  // Hàm kiểm tra định dạng email
  const isValidEmail = (email) => {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  };

  // Hàm kiểm tra định dạng số điện thoại
  const isValidPhone = (phone) => {
    return /^[0-9]{10,11}$/.test(phone);
  };

  // Lấy thông tin ghế từ API
  useEffect(() => {
    if (!selectedTrip?.id) {
      setError("Không tìm thấy thông tin chuyến xe.");
      return;
    }

    const fetchSeatAvailability = async () => {
      setLoading(true);
      try {
        const data = await ticketService.getSeatAvailability(selectedTrip.id);
        setBookedSeats(data.booked_seats || []);
        setSeatsData(data.seats || []);
        setTotalPrice(selectedSeats.length * (data.price || 0));
        setTripDetails({
          driver_name: data.driver_name || "Chưa chọn",
          vehicle_plate: data.vehicle_plate || "Chưa chọn",
        });
      } catch (err) {
        setError(err.message || "Không thể tải thông tin ghế");
      } finally {
        setLoading(false);
      }
    };

    fetchSeatAvailability();
  }, [selectedTrip?.id]);

  // Cập nhật tổng giá
  useEffect(() => {
    setTotalPrice(selectedSeats.length * (selectedTrip?.price || 0));
  }, [selectedSeats, selectedTrip?.price]);

  // Chuyển đổi số ghế thành định dạng A1-A44
  const formatSeatNumber = (seatNum) => `A${seatNum}`;

  const toggleSeat = (seatNumber) => {
    const formattedSeat = formatSeatNumber(seatNumber);
    if (bookedSeats.includes(formattedSeat)) return;
    setSelectedSeats((prev) =>
      prev.includes(formattedSeat)
        ? prev.filter((seat) => seat !== formattedSeat)
        : [...prev, formattedSeat]
    );
  };

  const getSeatStatus = (seatNum) => {
    const formattedSeat = formatSeatNumber(seatNum);
    if (bookedSeats.includes(formattedSeat)) return "booked";
    if (selectedSeats.includes(formattedSeat)) return "selected";
    return "empty";
  };

  const renderSeat = (seatNum) => {
    const status = getSeatStatus(seatNum);
    const baseClass =
      "w-10 h-10 flex items-center justify-center border rounded cursor-pointer";
    const statusClass = {
      empty: "bg-white border-gray-400 hover:border-gray-600",
      selected: "bg-green-500 text-white border-green-600",
      booked: "bg-blue-500 text-white cursor-not-allowed",
    };

    return (
      <div
        key={seatNum}
        className="flex flex-col items-center text-xs gap-1"
        onClick={() => toggleSeat(seatNum)}
      >
        <div className={`${baseClass} ${statusClass[status]}`}>
          {status === "selected" && "✓"}
          {status === "booked" && "✕"}
        </div>
        <span className="text-gray-600">{formatSeatNumber(seatNum)}</span>
      </div>
    );
  };

  const renderSeats = (start, end) => {
    const seats = Array.from({ length: end - start + 1 }, (_, i) => start + i);
    const rows = [];
    for (let i = 0; i < 6; i++) {
      const row = seats.slice(i * 3, i * 3 + 3);
      rows.push({ left: row, right: [] });
    }
    const row7 = seats.slice(18, 23);
    rows.push({ left: row7.slice(0, 3), right: row7.slice(3) });

    return (
      <div className="flex flex-col gap-4 bg-gray-100 p-6 rounded-xl items-center">
        {rows.map((row, idx) => (
          <div key={idx} className="flex flex-wrap justify-center gap-4">
            <div className="flex gap-4">{row.left.map(renderSeat)}</div>
            {row.right.length > 0 && (
              <div className="flex gap-4">{row.right.map(renderSeat)}</div>
            )}
          </div>
        ))}
      </div>
    );
  };

  const handleContinue = () => {
    if (selectedSeats.length === 0) {
      setError("Vui lòng chọn ít nhất 1 ghế.");
      return;
    }
    setError(null);
    setStep(2);
  };

  const handleNext = () => {
    if (!pickupDropoff.pickup || !pickupDropoff.dropoff) {
      setError("Vui lòng chọn địa điểm đón và trả.");
      return;
    }
    setError(null);
    setStep(3);
  };

  const handlePayment = async () => {
    const { name, phone, email, note } = userInfo;

    if (!name || !phone || !email) {
      setError("Vui lòng nhập đầy đủ thông tin cá nhân.");
      return;
    }

    if (!isValidEmail(email)) {
      setError("Email không hợp lệ. Vui lòng kiểm tra lại.");
      return;
    }

    if (!isValidPhone(phone)) {
      setError("Số điện thoại không hợp lệ. Vui lòng nhập 10-11 chữ số.");
      return;
    }

    if (!selectedTrip?.id) {
      setError("Không tìm thấy thông tin chuyến xe.");
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const ticketPromises = selectedSeats.map(async (seatNumber) => {
        const ticketData = {
          trip_id: selectedTrip.id,
          customer_name: name,
          customer_phone: phone,
          customer_email: email,
          pickup_location: pickupDropoff.pickup,
          dropoff_location: pickupDropoff.dropoff,
          seat_number: seatNumber,
          status: "Đã thanh toán",
          note: note || "Không có ghi chú",
        };
        return await ticketService.createTicket(ticketData);
      });

      const ticketResponses = await Promise.all(ticketPromises);

      const tickets = ticketResponses.map((res, idx) => ({
        ticket_code: res.ticket_code,
        customer_name: name,
        customer_phone: phone,
        customer_email: email,
        seat_number: selectedSeats[idx],
        status: "Đã thanh toán",
        note: note || "Không có ghi chú",
        trip_info: {
          departure_time: selectedTrip.departure_time,
          pickup_location: pickupDropoff.pickup,
          dropoff_location: pickupDropoff.dropoff,
          driver_name: tripDetails.driver_name,
          vehicle_plate: tripDetails.vehicle_plate,
        },
      }));

      const storedTickets = JSON.parse(localStorage.getItem("tickets")) || [];
      storedTickets.push(...tickets);
      localStorage.setItem("tickets", JSON.stringify(storedTickets));

      const order = {
        status: "completed",
        billing: {
          first_name: name,
          phone: phone,
          email: email,
        },
        total: totalPrice.toString(),
        meta_data: {
          ticket_codes: ticketResponses.reduce((acc, res, idx) => {
            acc[idx] = res.ticket_code;
            return acc;
          }, {}),
          seats: selectedSeats.join(", "),
          pickup: pickupDropoff.pickup,
          dropoff: pickupDropoff.dropoff,
          note: note || "Không có ghi chú",
          driver_name: tripDetails.driver_name,
          vehicle_plate: tripDetails.vehicle_plate,
        },
        trip_info: {
          departure_time: selectedTrip.departure_time,
          driver_name: tripDetails.driver_name,
          vehicle_plate: tripDetails.vehicle_plate,
        },
      };

      const orders = JSON.parse(localStorage.getItem("orders")) || [];
      orders.push({ order_id: `ORDER-${Date.now()}`, ...order });
      localStorage.setItem("orders", JSON.stringify(orders));

      navigate(
        `/payment-status?vnp_TxnRef=ORDER-${Date.now()}&vnp_ResponseCode=00`
      );
    } catch (err) {
      console.error("Error creating tickets:", err);
      setError(
        err.message ||
          "Có lỗi xảy ra khi tạo vé. Vui lòng kiểm tra lại thông tin."
      );
    } finally {
      setLoading(false);
    }
  };

  if (!selectedTrip) {
    return (
      <div className="p-6 border rounded-lg bg-white max-w-3xl mx-auto shadow-md">
        <p className="text-red-500">Không tìm thấy thông tin chuyến xe.</p>
        <button
          onClick={onBack}
          className="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition-colors"
        >
          Quay lại
        </button>
      </div>
    );
  }

  return (
    <div className="p-6 border rounded-lg bg-white max-w-3xl mx-auto shadow-md">
      <h2 className="text-2xl font-bold mb-4 flex items-center gap-2 text-gray-800">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          className="h-6 w-6 text-blue-600"
          viewBox="0 0 20 20"
          fill="currentColor"
        >
          <path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2v2a2 2 0 01-2 2H8zm6-10H6v8h2v-2h4v2h2V6zm-6 4v2H6v-2h2zm4 0v2h-2v-2h2z" />
        </svg>
        {selectedTrip?.company || "Nhà Xe Mỹ Duyên"}
      </h2>
      <div className="mb-4 text-gray-600">
        <p>
          <strong>Chuyến xe:</strong> {selectedTrip?.pickup_location || "N/A"} →{" "}
          {selectedTrip?.dropoff_location || "N/A"}
        </p>
        <p>
          <strong>Thời gian khởi hành:</strong>{" "}
          {selectedTrip?.departure_time || "N/A"}
        </p>
        <p>
          <strong>Tài xế:</strong> {tripDetails.driver_name}
        </p>
        <p>
          <strong>Biển số xe:</strong> {tripDetails.vehicle_plate}
        </p>
      </div>
      {error && (
        <p className="text-red-500 mb-4 bg-red-50 p-3 rounded-lg">{error}</p>
      )}
      {loading && (
        <p className="text-blue-500 mb-4 bg-blue-50 p-3 rounded-lg">
          Đang tải...
        </p>
      )}
      {step === 1 && (
        <>
          <h3 className="text-lg font-semibold mb-2 text-gray-700">Chọn ghế</h3>
          <div className="flex justify-center gap-6 mb-6">
            <div className="flex items-center gap-2">
              <div className="w-6 h-6 border rounded bg-white border-gray-400" />
              <span>Còn trống</span>
            </div>
            <div className="flex items-center gap-2">
              <div className="w-6 h-6 border rounded bg-blue-500 border-blue-600 text-white flex items-center justify-center">
                ✕
              </div>
              <span>Đã đặt</span>
            </div>
            <div className="flex items-center gap-2">
              <div className="w-6 h-6 border rounded bg-green-500 border-green-600 text-white flex items-center justify-center">
                ✓
              </div>
              <span>Đang chọn</span>
            </div>
          </div>
          <div className="flex flex-col md:flex-row gap-x-10 justify-center">
            <div className="mb-6 w-full">
              <h4 className="font-semibold mb-2 text-center text-gray-600">
                Tầng dưới
              </h4>
              {renderSeats(1, 22)}
            </div>
            <div className="mb-6 w-full">
              <h4 className="font-semibold mb-2 text-center text-gray-600">
                Tầng trên
              </h4>
              {renderSeats(23, 44)}
            </div>
          </div>
          <div className="text-center my-4 bg-gray-50 p-4 rounded-lg">
            <p className="text-gray-700">
              <strong>Ghế đã chọn:</strong>{" "}
              {selectedSeats.length > 0
                ? selectedSeats.join(", ")
                : "Chưa chọn"}
            </p>
            <p className="text-gray-700">
              <strong>Tổng tiền:</strong> {totalPrice.toLocaleString("vi-VN")}đ
            </p>
          </div>
          <div className="flex justify-between">
            <button
              onClick={onBack}
              className="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition-colors"
            >
              Quay lại
            </button>
            <button
              onClick={handleContinue}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Tiếp tục
            </button>
          </div>
        </>
      )}
      {step === 2 && (
        <>
          <h3 className="text-lg font-semibold mb-4 text-gray-700">
            Xác nhận địa điểm đón và trả
          </h3>
          <div className="flex flex-col gap-4 mb-4">
            <div>
              <label className="block mb-1 font-medium text-gray-600">
                Địa điểm đón:
              </label>
              <input
                type="text"
                className="border p-2 rounded-lg w-full bg-gray-50 text-gray-700"
                value={pickupDropoff.pickup}
                readOnly
              />
            </div>
            <div>
              <label className="block mb-1 font-medium text-gray-600">
                Địa điểm trả:
              </label>
              <input
                type="text"
                className="border p-2 rounded-lg w-full bg-gray-50 text-gray-700"
                value={pickupDropoff.dropoff}
                readOnly
              />
            </div>
          </div>
          <div className="flex justify-between">
            <button
              onClick={() => setStep(1)}
              className="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition-colors"
            >
              Quay lại
            </button>
            <button
              onClick={handleNext}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Tiếp tục
            </button>
          </div>
        </>
      )}
      {step === 3 && (
        <>
          <h3 className="text-lg font-semibold mb-4 text-gray-700">
            Nhập thông tin khách hàng
          </h3>
          <div className="flex flex-col gap-4 mb-4">
            <input
              className="border p-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
              placeholder="Họ tên"
              value={userInfo.name}
              onChange={(e) =>
                setUserInfo((prev) => ({ ...prev, name: e.target.value }))
              }
            />
            <input
              className="border p-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
              placeholder="Số điện thoại"
              value={userInfo.phone}
              onChange={(e) =>
                setUserInfo((prev) => ({ ...prev, phone: e.target.value }))
              }
            />
            <input
              className="border p-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
              placeholder="Email"
              value={userInfo.email}
              onChange={(e) =>
                setUserInfo((prev) => ({ ...prev, email: e.target.value }))
              }
            />
            <input
              className="border p-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
              placeholder="Ghi chú"
              value={userInfo.note}
              onChange={(e) =>
                setUserInfo((prev) => ({ ...prev, note: e.target.value }))
              }
            />
          </div>
          <div className="flex justify-between">
            <button
              onClick={() => setStep(2)}
              className="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition-colors"
            >
              Quay lại
            </button>
            <button
              onClick={handlePayment}
              disabled={loading}
              className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 flex items-center gap-2"
            >
              {loading ? (
                "Đang xử lý..."
              ) : (
                <>
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="h-5 w-5"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path
                      fillRule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-4a1 1 0 112 0v1a1 1 0 11-2 0v-1zm1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                      clipRule="evenodd"
                    />
                  </svg>
                  Thanh toán
                </>
              )}
            </button>
          </div>
        </>
      )}
    </div>
  );
}

export default SeatSelection;
