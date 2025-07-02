import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import ticketService from "../service/ticketService";
import { useAuth } from "./contexts/AuthContext";
import { useNotification } from "./contexts/NotificationContext";
import "./SeatSelection.css";

function SeatSelection({ selectedTrip, onBack }) {
  const navigate = useNavigate(); // Moved to the top
  const { user, token } = useAuth();
  const { notifyError } = useNotification();
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
  });  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [seatHover, setSeatHover] = useState(null);
  const [showSuccess, setShowSuccess] = useState(false);

  useEffect(() => {
    if (!user || !token) {
      setError("Vui lòng đăng nhập để đặt vé.");
      navigate("/signin"); // Now safe to use
    }
  }, [user, token, navigate]);

  useEffect(() => {
    if (user) {
      setUserInfo({
        name: `${user.first_name} ${user.last_name}` || "",
        phone: user.phone_number || "",
        email: user.email || "",
        note: "",
      });
    }
  }, [user]);

  const isValidEmail = (email) => {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  };

  const isValidPhone = (phone) => {
    return /^[0-9]{10,11}$/.test(phone);
  };

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

  useEffect(() => {
    setTotalPrice(selectedSeats.length * (selectedTrip?.price || 0));
  }, [selectedSeats, selectedTrip?.price]);

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
    const isSelected = status === "selected";
    const isBooked = status === "booked";
    const isEmpty = status === "empty";
      const baseClass = "w-8 h-8 flex items-center justify-center border rounded cursor-pointer relative transition-all duration-200 transform hover:scale-105 shadow-sm";
    
    let statusClass = "";
    let content = "";
    
    if (isEmpty) {
      statusClass = "bg-gray-50 border-gray-300 hover:bg-blue-50 hover:border-blue-400";
      content = (
        <div className="flex items-center justify-center">
          <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" 
            strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
        </div>
      );
    } else if (isSelected) {
      statusClass = "bg-green-500 border-green-500 text-white";
      content = (
        <div className="flex items-center justify-center">
          <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
          </svg>
        </div>
      );
    } else if (isBooked) {
      statusClass = "bg-orange-500 border-red-500 text-white cursor-not-allowed";
      content = (
        <div className="flex items-center justify-center">
          <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
          </svg>
        </div>
      );
    }

    return (
      <div
        key={seatNum}
        className="flex flex-col items-center text-sm gap-2 p-2"
        onClick={() => toggleSeat(seatNum)}
        onMouseEnter={() => setSeatHover(seatNum)}
        onMouseLeave={() => setSeatHover(null)}
      >
        <div className={`${baseClass} ${statusClass} group relative overflow-hidden`}>
          {content}
          {isEmpty && (
            <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-0 group-hover:opacity-30 transform -skew-x-12 transition-all duration-700 group-hover:translate-x-12"></div>
          )}
          {isSelected && (
            <>
              <div className="absolute inset-0 bg-white opacity-20 rounded-lg animate-ping"></div>
              <div className="absolute -top-1 -right-1 w-3 h-3 bg-yellow-400 rounded-full animate-pulse"></div>
            </>
          )}
        </div>
        <span className={`font-medium transition-colors duration-300 ${
          isSelected ? 'text-green-600 font-bold' : 
          isBooked ? 'text-red-500' : 
          'text-gray-600 group-hover:text-blue-600'
        }`}>
          {formatSeatNumber(seatNum)}
        </span>
        {seatHover === seatNum && isEmpty && (
          <div className="absolute z-10 bg-black text-white text-xs px-2 py-1 rounded mt-16 animate-fadeIn">
            Nhấn để chọn
          </div>
        )}
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
    rows.push({ left: row7.slice(0, 3), right: row7.slice(3) });    return (
      <div className="relative">
        {/* Bus Shape Container - smaller */}
        <div className="bg-white p-4 rounded-lg shadow-md border border-gray-200 relative overflow-hidden">
          {/* Animated Background Pattern - smaller */}
          <div className="absolute inset-0 opacity-10">
            <div className="absolute top-2 left-2 w-3 h-3 bg-blue-400 rounded-full animate-pulse"></div>
            <div className="absolute top-4 right-4 w-2 h-2 bg-green-400 rounded-full animate-pulse delay-1000"></div>
            <div className="absolute bottom-2 left-4 w-2 h-2 bg-yellow-400 rounded-full animate-pulse delay-2000"></div>
          </div>
          
          {/* Driver Area - smaller */}
          <div className="flex justify-center mb-3">
            <div className="bg-gray-800 text-white px-3 py-1 rounded-full shadow flex items-center gap-2 text-sm">
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
              </svg>
              <span className="font-medium">Tài xế</span>
            </div>
          </div>
          
          {/* Seats Layout - smaller spacing */}
          <div className="flex flex-col gap-2 items-center">
            {rows.map((row, idx) => (
              <div key={idx} className="flex justify-center items-center gap-4">
                <div className="flex gap-1">{row.left.map(renderSeat)}</div>
                {row.right.length > 0 && (
                  <>
                    <div className="w-6 h-px bg-gray-300"></div>
                    <div className="flex gap-1">{row.right.map(renderSeat)}</div>
                  </>
                )}
              </div>
            ))}
          </div>
          
          {/* Floor Indicator - smaller */}
          <div className="absolute -top-1 -right-1 bg-purple-500 text-white px-2 py-0.5 rounded-full text-xs font-bold shadow transform rotate-12">
            {start === 1 ? "Tầng 1" : "Tầng 2"}
          </div>
        </div>
      </div>
    );
  };
  const handleContinue = () => {
    if (selectedSeats.length === 0) {
      setError("Vui lòng chọn ít nhất 1 ghế.");
      return;
    }
    setError(null);
    setShowSuccess(true);
    setTimeout(() => {
      setShowSuccess(false);
      setStep(2);
    }, 1000);
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
      // Tạo vé trước khi thanh toán
      const ticketsData = selectedSeats.map((seatNumber) => ({
        trip_id: selectedTrip.id,
        customer_name: name,
        customer_phone: phone,
        customer_email: email,
        pickup_location: pickupDropoff.pickup,
        dropoff_location: pickupDropoff.dropoff,
        seat_number: seatNumber,
        status: "Chưa thanh toán", // Ban đầu đặt là chưa thanh toán
        note: note || "Không có ghi chú",
      }));      const response = await ticketService.createTicketsBulk(ticketsData);
      const ticketIds = response.tickets.map((ticket) => ticket.ticket_id);

      // Gọi API tạo URL thanh toán
      const paymentData = {
        ticketIds: ticketIds,
        amount: totalPrice,
        language: "vn",
        bankCode: "", // Có thể thêm lựa chọn phương thức thanh toán nếu cần
      };

      const paymentResponse = await ticketService.createPayment(paymentData);

      // Chuyển hướng người dùng đến URL thanh toán VNPAY
      window.location.href = paymentResponse.payment_url;    } catch (err) {
      console.error("Error during payment:", err);
      const errorMessage = err.message || "Có lỗi xảy ra khi xử lý thanh toán. Vui lòng thử lại.";
      
      // Thêm thông báo lỗi
      notifyError({
        message: errorMessage,
        details: { 
          tripId: selectedTrip?.id,
          seats: selectedSeats,
          timestamp: new Date().toISOString()
        }
      });
      
      setError(errorMessage);
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
  }  return (
    <div className="bg-gray-50 p-2 min-h-screen">
      <div className="max-w-4xl mx-auto">{/* Header Card - compact */}
        <div className="bg-white rounded-lg shadow-sm p-3 mb-3 relative overflow-hidden">
          <div className="relative z-10">
            <h2 className="text-lg font-bold mb-3 flex items-center gap-2 text-gray-800">
              <div className="bg-blue-500 p-1.5 rounded-md">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-4 w-4 text-white"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2v2a2 2 0 01-2 2H8zm6-10H6v8h2v-2h4v2h2V6zm-6 4v2H6v-2h2zm4 0v2h-2v-2h2z" />
                </svg>
              </div>
              {selectedTrip?.company || "Nhà Xe Mỹ Duyên"}
            </h2>
            
            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 text-gray-700">
              <div className="bg-blue-50 p-2 rounded-lg">
                <div className="flex items-center gap-2 mb-1">
                  <svg className="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                  </svg>
                  <strong className="text-blue-800 text-xs">Hành trình:</strong>
                </div>
                <p className="text-sm font-medium truncate">
                  {selectedTrip?.pickup_location || "N/A"} → {selectedTrip?.dropoff_location || "N/A"}
                </p>
              </div>
              
              <div className="bg-green-50 p-2 rounded-lg">
                <div className="flex items-center gap-2 mb-1">
                  <svg className="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clipRule="evenodd" />
                  </svg>
                  <strong className="text-green-800 text-xs">Thời gian:</strong>
                </div>
                <p className="text-sm font-medium">{selectedTrip?.departure_time || "N/A"}</p>
              </div>
              
              <div className="bg-purple-50 p-2 rounded-lg">
                <div className="flex items-center gap-2 mb-1">
                  <svg className="w-3 h-3 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                  </svg>
                  <strong className="text-purple-800 text-xs">Tài xế:</strong>
                </div>
                <p className="text-sm font-medium">{tripDetails.driver_name}</p>
              </div>
              
              <div className="bg-orange-50 p-2 rounded-lg">
                <div className="flex items-center gap-2 mb-1">
                  <svg className="w-3 h-3 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2v2a2 2 0 01-2 2H8z" />
                  </svg>
                  <strong className="text-orange-800 text-xs">Biển số:</strong>
                </div>
                <p className="text-sm font-medium">{tripDetails.vehicle_plate}</p>
              </div>
            </div>
          </div>
        </div>        {/* Progress Bar - compact */}
        <div className="bg-white rounded-lg shadow-sm p-2 mb-3">
          <div className="flex items-center justify-between mb-1">
            {[1, 2, 3].map((stepNum) => (
              <div key={stepNum} className="flex items-center">
                <div className={`w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs transition-all ${
                  step >= stepNum 
                    ? 'bg-green-500 text-white' 
                    : 'bg-gray-200 text-gray-500'
                }`}>
                  {step > stepNum ? (
                    <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                    </svg>
                  ) : stepNum}
                </div>
                {stepNum < 3 && (
                  <div className={`w-16 h-0.5 mx-3 rounded-full transition-all duration-500 ${
                    step > stepNum ? 'bg-green-500' : 'bg-gray-200'
                  }`}></div>
                )}
              </div>
            ))}
          </div>
          <div className="flex justify-between text-xs font-medium text-gray-600">
            <span>Chọn ghế</span>
            <span>Xác nhận</span>
            <span>Thanh toán</span>
          </div>
        </div>        {/* Alerts - compact */}
        {error && (
          <div className="bg-red-50 border-l-4 border-red-400 p-2 rounded mb-3">
            <div className="flex items-center">
              <svg className="w-4 h-4 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
              </svg>
              <p className="text-red-700 font-medium text-sm">{error}</p>
            </div>
          </div>
        )}        
        {loading && (
          <div className="bg-blue-50 border-l-4 border-blue-400 p-3 rounded mb-4">
            <div className="flex items-center">
              <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
              <p className="text-blue-700 font-medium text-sm">Đang tải...</p>
            </div>
          </div>
        )}        {showSuccess && (
          <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div className="bg-white p-3 rounded-lg shadow-lg">
              <div className="text-center">
                <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
                  <svg className="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                  </svg>
                </div>
                <h3 className="text-base font-bold text-gray-800 mb-1">Tuyệt vời!</h3>
                <p className="text-gray-600 text-xs">Đã chọn ghế thành công</p>
              </div>
            </div>
          </div>
        )}{step === 1 && (
          <div className="bg-white rounded-lg shadow-md p-4 relative overflow-hidden">
            <div className="relative z-10">
              <h3 className="text-xl font-bold mb-4 text-center text-gray-800">
                🚌 Chọn ghế ngồi của bạn
              </h3>
              
              {/* Legend - smaller */}
              <div className="flex justify-center gap-4 mb-4 flex-wrap">
                <div className="flex items-center gap-2 bg-gray-50 px-2 py-1 rounded-full text-sm">
                  <div className="w-4 h-4 border rounded bg-gray-50 border-gray-300" />
                  <span className="text-gray-700">Còn trống</span>
                </div>
                <div className="flex items-center gap-2 bg-gray-500 px-2 py-1 rounded-full text-sm">
                  <div className="w-4 h-4 border rounded bg-orange-500 border-red-500 text-white flex items-center justify-center">
                    <svg className="w-2 h-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                    </svg>
                  </div>
                  <span className="text-gray-700">Đã đặt</span>
                </div>
                <div className="flex items-center gap-2 bg-gray-50 px-2 py-1 rounded-full text-sm">
                  <div className="w-4 h-4 border rounded bg-green-500 border-green-500 text-white flex items-center justify-center">
                    <svg className="w-2 h-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                    </svg>
                  </div>
                  <span className="text-gray-700">Đang chọn</span>
                </div>
              </div>
              
              {/* Seat Layout - smaller */}
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 justify-center">
                <div className="flex flex-col items-center">
                  <h4 className="text-lg font-bold mb-3 text-center text-gray-700 flex items-center justify-center gap-2">
                    <span className="bg-blue-500 text-white px-2 py-0.5 rounded-full text-xs">1</span>
                    Tầng dưới
                  </h4>
                  {renderSeats(1, 22)}
                </div>
                <div className="flex flex-col items-center">
                  <h4 className="text-lg font-bold mb-3 text-center text-gray-700 flex items-center justify-center gap-2">
                    <span className="bg-purple-500 text-white px-2 py-0.5 rounded-full text-xs">2</span>
                    Tầng trên
                  </h4>
                  {renderSeats(23, 44)}
                </div>
              </div>
              
              {/* Selection Summary - smaller */}
              <div className="mt-4 bg-gray-50 p-3 rounded-lg border">
                <div className="text-center space-y-2">
                  <div className="flex items-center justify-center gap-2">
                    <svg className="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span className="text-sm font-medium text-gray-700">
                      Ghế đã chọn: 
                      <span className="ml-1 text-green-600 font-bold">
                        {selectedSeats.length > 0 ? selectedSeats.join(", ") : "Chưa chọn ghế nào"}
                      </span>
                    </span>
                  </div>
                  <div className="flex items-center justify-center gap-2">
                    <svg className="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                      <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clipRule="evenodd" />
                    </svg>
                    <span className="text-lg font-bold text-green-600">
                      {totalPrice.toLocaleString("vi-VN")}đ
                    </span>
                  </div>
                  {selectedSeats.length > 0 && (
                    <div className="text-xs text-gray-600">
                      {selectedSeats.length} ghế × {(selectedTrip?.price || 0).toLocaleString("vi-VN")}đ/ghế
                    </div>
                  )}
                </div>
              </div>              
              {/* Action Buttons - smaller */}
              <div className="flex justify-between mt-4 gap-3">
                <button
                  onClick={onBack}
                  className="flex items-center gap-2 px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors text-sm font-medium"
                >
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clipRule="evenodd" />
                  </svg>
                  Quay lại
                </button>
                <button
                  onClick={handleContinue}
                  disabled={selectedSeats.length === 0}
                  className="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Tiếp tục
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clipRule="evenodd" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        )}

        {step === 2 && (
          <div className="bg-white rounded-lg shadow-md p-4 relative overflow-hidden">
            <div className="relative z-10">
              <h3 className="text-xl font-bold mb-4 text-center text-gray-800">
                📍 Xác nhận địa điểm đón và trả
              </h3>
              <div className="max-w-xl mx-auto space-y-3">
                <div className="bg-blue-50 p-3 rounded-lg border border-blue-200">
                  <label className="flex items-center gap-2 mb-2 font-medium text-blue-800 text-sm">
                    <div className="bg-blue-500 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs">
                      📍
                    </div>
                    Địa điểm đón:
                  </label>
                  <input
                    type="text"
                    className="border border-blue-200 p-2 rounded w-full bg-white text-gray-700 text-sm focus:ring-1 focus:ring-blue-400 focus:border-blue-400"
                    value={pickupDropoff.pickup}
                    readOnly
                  />
                </div>
                
                <div className="bg-green-50 p-3 rounded-lg border border-green-200">
                  <label className="flex items-center gap-2 mb-2 font-medium text-green-800 text-sm">
                    <div className="bg-green-500 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs">
                      🎯
                    </div>
                    Địa điểm trả:
                  </label>
                  <input
                    type="text"
                    className="border border-green-200 p-2 rounded w-full bg-white text-gray-700 text-sm focus:ring-1 focus:ring-green-400 focus:border-green-400"
                    value={pickupDropoff.dropoff}
                    readOnly
                  />
                </div>
                
                {/* Route Visualization - smaller */}
                <div className="bg-purple-50 p-3 rounded-lg border border-purple-200">
                  <div className="flex items-center justify-center">
                    <div className="flex items-center gap-3">
                      <div className="flex flex-col items-center">
                        <div className="bg-blue-500 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold">
                          A
                        </div>
                        <span className="text-xs text-blue-700 mt-1">Điểm đón</span>
                      </div>
                      
                      <div className="flex items-center gap-1">
                        <div className="w-4 h-0.5 bg-blue-400 rounded-full"></div>
                        <svg className="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2v2a2 2 0 01-2 2H8z" />
                        </svg>
                        <div className="w-4 h-0.5 bg-green-400 rounded-full"></div>
                      </div>
                      
                      <div className="flex flex-col items-center">
                        <div className="bg-green-500 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold">
                          B
                        </div>
                        <span className="text-xs text-green-700 mt-1">Điểm trả</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="flex justify-between mt-4 gap-3 max-w-xl mx-auto">
                <button
                  onClick={() => setStep(1)}
                  className="flex items-center gap-2 px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors text-sm font-medium"
                >
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clipRule="evenodd" />
                  </svg>
                  Quay lại
                </button>                <button
                  onClick={handleNext}
                  className="flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors text-sm font-medium"
                >
                  Tiếp tục
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clipRule="evenodd" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        )}

        {step === 3 && (
          <div className="bg-white rounded-lg shadow-md p-4 relative overflow-hidden">
            <div className="relative z-10">
              <div className="text-center mb-4">
                <div className="inline-flex items-center justify-center w-12 h-12 bg-purple-500 rounded-full mb-2">
                  <svg className="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                  </svg>
                </div>
                <h3 className="text-xl font-bold text-gray-800">
                  👤 Thông tin khách hàng
                </h3>
                <p className="text-gray-600 text-sm mt-1">Vui lòng kiểm tra và xác nhận thông tin của bạn</p>
              </div>
              
              <div className="max-w-xl mx-auto space-y-3">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">                  <div className="space-y-1">
                    <label className="flex items-center gap-1 font-medium text-gray-700 text-sm">
                      <svg className="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                      </svg>
                      Họ tên *
                    </label>
                    <input
                      className="border border-gray-200 p-2 rounded w-full focus:ring-1 focus:ring-blue-400 focus:border-blue-400 text-sm"
                      placeholder="Nhập họ tên đầy đủ"
                      value={userInfo.name}
                      onChange={(e) =>
                        setUserInfo((prev) => ({ ...prev, name: e.target.value }))
                      }
                    />
                  </div>
                  
                  <div className="space-y-1">
                    <label className="flex items-center gap-1 font-medium text-gray-700 text-sm">
                      <svg className="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                      </svg>
                      Số điện thoại *
                    </label>
                    <input
                      className="border border-gray-200 p-2 rounded w-full focus:ring-1 focus:ring-green-400 focus:border-green-400 text-sm"
                      placeholder="Nhập số điện thoại"
                      value={userInfo.phone}
                      onChange={(e) =>
                        setUserInfo((prev) => ({ ...prev, phone: e.target.value }))
                      }
                    />
                  </div>
                </div>
                
                <div className="space-y-1">
                  <label className="flex items-center gap-1 font-medium text-gray-700 text-sm">
                    <svg className="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                      <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                    </svg>
                    Email *
                  </label>
                  <input
                    className="border border-gray-200 p-2 rounded w-full focus:ring-1 focus:ring-purple-400 focus:border-purple-400 text-sm"
                    placeholder="Nhập địa chỉ email"
                    value={userInfo.email}
                    onChange={(e) =>
                      setUserInfo((prev) => ({ ...prev, email: e.target.value }))
                    }
                  />
                </div>
                
                <div className="space-y-1">
                  <label className="flex items-center gap-1 font-medium text-gray-700 text-sm">
                    <svg className="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                    </svg>
                    Ghi chú
                  </label>
                  <textarea
                    rows="2"
                    className="border border-gray-200 p-2 rounded w-full focus:ring-1 focus:ring-orange-400 focus:border-orange-400 text-sm resize-none"
                    placeholder="Thêm ghi chú (tùy chọn)"
                    value={userInfo.note}
                    onChange={(e) =>
                      setUserInfo((prev) => ({ ...prev, note: e.target.value }))
                    }
                  />
                </div>                
                {/* Booking Summary - smaller */}
                <div className="bg-gray-50 p-3 rounded-lg border mt-4">
                  <h4 className="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
                    <svg className="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Tóm tắt đặt vé
                  </h4>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div className="space-y-1">
                      <div className="flex justify-between">
                        <span className="text-gray-600">Ghế đã chọn:</span>
                        <span className="font-medium text-green-600">{selectedSeats.join(", ")}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600">Số lượng ghế:</span>
                        <span className="font-medium">{selectedSeats.length} ghế</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600">Giá/ghế:</span>
                        <span className="font-medium">{(selectedTrip?.price || 0).toLocaleString("vi-VN")}đ</span>
                      </div>
                    </div>
                    
                    <div className="space-y-1">
                      <div className="flex justify-between">
                        <span className="text-gray-600">Điểm đón:</span>
                        <span className="font-medium text-right max-w-32 truncate">{pickupDropoff.pickup}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600">Điểm trả:</span>
                        <span className="font-medium text-right max-w-32 truncate">{pickupDropoff.dropoff}</span>
                      </div>
                      <div className="flex justify-between text-base font-bold border-t pt-1">
                        <span className="text-gray-800">Tổng tiền:</span>
                        <span className="text-green-600">{totalPrice.toLocaleString("vi-VN")}đ</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="flex justify-between mt-4 gap-3 max-w-xl mx-auto">
                <button
                  onClick={() => setStep(2)}
                  className="flex items-center gap-2 px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors text-sm font-medium"
                >
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clipRule="evenodd" />
                  </svg>                  Quay lại
                </button>
                <button
                  onClick={handlePayment}
                  disabled={loading}
                  className="flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {loading ? (
                    <>
                      <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                      Đang xử lý...
                    </>
                  ) : (
                    <>
                      <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" />
                      </svg>
                      Thanh toán ngay
                    </>
                  )}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default SeatSelection;