import React, { useState, useRef, useEffect } from "react";
import tripService from "../../service/tripService";
import ticketService from "../../service/ticketService";
import routeService from "../../service/routeService";
import locationService from "../../service/locationService";
import logo from "../../assets/images/logo.png"; // Đường dẫn logo, đổi nếu cần
import { useAuth } from "../contexts/AuthContext"; // Thêm dòng này để lấy user

const GEMINI_API_KEY = process.env.REACT_APP_GEMINI_API_KEY;
const GEMINI_API_URL =
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" +
  GEMINI_API_KEY;

function ChatBot() {
  const [open, setOpen] = useState(false);
  const [minimized, setMinimized] = useState(false);
  const [messages, setMessages] = useState([
    { from: "bot", text: "Xin chào! Tôi có thể giúp gì cho bạn?" },
  ]);
  const [input, setInput] = useState("");
  const [loading, setLoading] = useState(false);
  const [customerInfo, setCustomerInfo] = useState({ name: "", email: "", phone: "" });
  const [infoSubmitted, setInfoSubmitted] = useState(false);
  const [infoError, setInfoError] = useState("");
  const [showBookingForm, setShowBookingForm] = useState(false);
  const [bookingForm, setBookingForm] = useState({
    name: customerInfo.name || "",
    phone: customerInfo.phone || "",
    email: customerInfo.email || "",
    from: "",
    to: "",
    date: "",
    seat: "",
    note: ""
  });
  const [bookingError, setBookingError] = useState("");
  const [tripLocations, setTripLocations] = useState({ from: [], to: [] });
  const [tripsLoading, setTripsLoading] = useState(false);
  const messagesEndRef = useRef(null);
  const { user } = useAuth(); // Lấy user từ context

  useEffect(() => {
    if (open && messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: "smooth" });
    }
  }, [messages, open]);

  // Khi user đã đăng nhập, tự động điền thông tin vào form khách hàng nhưng vẫn cho sửa
  useEffect(() => {
    if (user && user.id && !infoSubmitted) {
      setCustomerInfo((prev) => ({
        name: (user.first_name + ' ' + user.last_name).trim() || user.email || '',
        email: user.email || '',
        phone: user.phone_number || '',
      }));
    }
  }, [user, infoSubmitted]);

  // Lấy danh sách điểm đi/điểm đến khi mở form đặt vé
  useEffect(() => {
    if (showBookingForm) {
      setTripsLoading(true);
      tripService.getTrips()
        .then((data) => {
          const trips = Array.isArray(data) ? data : data.data || data.trips || [];
          // Lọc chuyến xe ngày hiện tại và tương lai
          const today = new Date();
          today.setHours(0,0,0,0);
          const validTrips = trips.filter(trip => {
            if (!trip.departure_time) return false;
            const dep = new Date(trip.departure_time);
            dep.setHours(0,0,0,0);
            return !isNaN(dep.getTime()) && dep >= today;
          });
          // Lấy điểm đi/điểm đến duy nhất
          const fromSet = new Set();
          const toSet = new Set();
          validTrips.forEach(trip => {
            if (trip.pickup_location) fromSet.add(trip.pickup_location);
            if (trip.dropoff_location) toSet.add(trip.dropoff_location);
          });
          setTripLocations({ from: Array.from(fromSet), to: Array.from(toSet) });
        })
        .catch(() => setTripLocations({ from: [], to: [] }))
        .finally(() => setTripsLoading(false));
    }
  }, [showBookingForm]);

  const handleInfoChange = (e) => {
    setCustomerInfo({ ...customerInfo, [e.target.name]: e.target.value });
  };

  const handleInfoSubmit = (e) => {
    e.preventDefault();
    if (!customerInfo.name.trim() || !customerInfo.email.trim() || !customerInfo.phone.trim()) {
      setInfoError("Vui lòng nhập đầy đủ họ tên, email và số điện thoại.");
      return;
    }
    setInfoError("");
    setInfoSubmitted(true);
    setMessages([
      { from: "bot", text: `Chào ${customerInfo.name}! Bạn cần hỗ trợ gì?` },
    ]);
  };

  // Nhận diện ý định mở rộng và gọi API phù hợp
  const handleIntent = async (text) => {
    const lower = text.toLowerCase();
    // Kịch bản FAQ
    for (const scenario of faqScenarios) {
      if (scenario.keywords.some((kw) => lower.includes(kw))) {
        return scenario.answer;
      }
    }
    // Tra cứu vé theo mã vé và số điện thoại
    if (lower.match(/(tra cứu|kiểm tra|xem) vé.*(mã|code)/) || lower.includes("trạng thái vé")) {
      return "Vui lòng nhập theo cú pháp: Tra cứu vé [Mã vé] [Số điện thoại] (VD: Tra cứu vé TICKET_ABC123, 0987654321)";
    }
    // Nhận diện cú pháp tra cứu vé: hỗ trợ dấu phẩy, chấm phẩy, khoảng trắng linh hoạt
    const traCuuMatch = lower.match(/tra cứu vé\s+([\w-]+)[,;\s]+(\d{8,})/);
    if (traCuuMatch) {
      const ticketCode = traCuuMatch[1].replace(/[^\w-]/g, "").toUpperCase();
      const phone = traCuuMatch[2];
      try {
        const result = await ticketService.checkTicket(ticketCode, phone);
        if (result && result.ticket_code) {
          // Hiển thị thông tin vé chi tiết hơn
          return (
            `Vé: ${result.ticket_code}\n` +
            `Tên: ${result.customer_name || "-"}\n` +
            `SĐT: ${result.customer_phone || "-"}\n` +
            `Chuyến: ${result.pickup_location} → ${result.dropoff_location}\n` +
            `Khởi hành: ${result.departure_time || "-"}\n` +
            `Ghế: ${result.seat_number || "-"}\n` +
            `Trạng thái: ${result.status || "-"}`
          );
        } else {
          return "Không tìm thấy vé phù hợp.";
        }
      } catch (err) {
        return "Không thể tra cứu vé: " + err.message;
      }
    }
    // Giá vé
    if (lower.includes("giá vé") || lower.includes("bao nhiêu tiền") || lower.includes("vé bao nhiêu")) {
      try {
        const trips = await tripService.getTrips();
        if (trips && trips.length > 0) {
          const prices = trips.slice(0, 3).map(t => `${t.pickup_location} → ${t.dropoff_location}: ${t.price}đ`).join(" | ");
          return `Một số giá vé hiện tại: ${prices}`;
        } else {
          return "Chưa có thông tin giá vé.";
        }
      } catch (err) {
        return "Không thể lấy giá vé: " + err.message;
      }
    }
    // Hướng dẫn đặt vé
    if (lower.includes("hướng dẫn đặt vé") || lower.includes("cách đặt vé") || lower.includes("đặt vé như thế nào")) {
      return "Bạn có thể đặt vé bằng cách:\n1. Tìm chuyến xe phù hợp trên trang chủ.\n2. Chọn chuyến và ghế ngồi.\n3. Nhập thông tin cá nhân và xác nhận.\n4. Thanh toán online hoặc tại quầy.\nNếu cần hỗ trợ, hãy gọi tổng đài 1900 6746.";
    }
    // Chính sách hoàn/hủy vé
    if (lower.includes("chính sách hoàn") || lower.includes("chính sách hủy") || lower.includes("hoàn vé") || lower.includes("hủy vé")) {
      return "Chính sách hoàn/hủy vé: Bạn có thể hoàn/hủy vé trước giờ khởi hành tối thiểu 2 tiếng. Phí hoàn/hủy sẽ áp dụng theo quy định. Liên hệ tổng đài 1900 6746 để được hỗ trợ.";
    }
    // Liên hệ tổng đài
    if (lower.includes("liên hệ") || lower.includes("tổng đài") || lower.includes("hotline") || lower.includes("số điện thoại")) {
      return "Bạn có thể liên hệ tổng đài hỗ trợ khách hàng 24/7 qua số 1900 6746.";
    }
    // Vé xe
    if (
      lower.includes("vé của tôi") ||
      lower.includes("tra cứu vé") ||
      lower.includes("xem vé")
    ) {
      try {
        const tickets = await ticketService.getTickets();
        // Lọc vé có thời gian khởi hành từ hiện tại trở về sau
        const now = new Date();
        const futureTickets = (tickets || []).filter(t => {
          if (!t.departure_time) return false;
          const dep = new Date(t.departure_time);
          return !isNaN(dep.getTime()) && dep >= now;
        });
        if (futureTickets.length > 0) {
          return (
            "Bạn có " +
            futureTickets.length +
            " vé sắp tới. Mã vé: " +
            futureTickets.map((t) => t.ticket_code || t.id).join(", ")
          );
        } else {
          return "Bạn chưa có vé nào sắp tới.";
        }
      } catch (err) {
        return "Không thể lấy thông tin vé: " + err.message;
      }
    }
    // Chuyến xe
    if (
      lower.includes("chuyến xe") ||
      lower.includes("lịch trình") ||
      lower.includes("xem chuyến")
    ) {
      try {
        const trips = await tripService.getTrips();
        if (trips && trips.length > 0) {
          // Lọc các chuyến xe có ngày khởi hành từ hôm nay trở đi
          const today = new Date();
          today.setHours(0,0,0,0);
          const validTrips = trips.filter(trip => {
            if (!trip.departure_time) return false;
            const dep = new Date(trip.departure_time);
            dep.setHours(0,0,0,0);
            return !isNaN(dep.getTime()) && dep >= today;
          });
          if (validTrips.length > 0) {
            return (
              "Có " +
              validTrips.length +
              " chuyến xe sắp tới. Ví dụ: " +
              validTrips
                .slice(0, 3)
                .map(
                  (t) =>
                    `${t.pickup_location} → ${t.dropoff_location} lúc ${t.departure_time}`
                )
                .join(" | ")
            );
          } else {
            return "Hiện chưa có chuyến xe nào sắp tới.";
          }
        } else {
          return "Hiện chưa có chuyến xe nào.";
        }
      } catch (err) {
        return "Không thể lấy thông tin chuyến xe: " + err.message;
      }
    }
    // Tuyến đường
    if (
      lower.includes("tuyến đường") ||
      lower.includes("xem tuyến") ||
      lower.includes("địa điểm đi") ||
      lower.includes("địa điểm đến")
    ) {
      try {
        const routes = await routeService.getRoutes();
        if (routes && routes.length > 0) {
          return (
            "Các tuyến đường phổ biến: " +
            routes
              .slice(0, 3)
              .map((r) => `${r.from_location} → ${r.to_location}`)
              .join(" | ")
          );
        } else {
          return "Chưa có tuyến đường nào.";
        }
      } catch (err) {
        return "Không thể lấy thông tin tuyến đường: " + err.message;
      }
    }
    // Địa điểm
    if (
      lower.includes("địa điểm") ||
      lower.includes("bến xe") ||
      lower.includes("bến đi") ||
      lower.includes("bến đến")
    ) {
      try {
        const locations = await locationService.getLocations();
        if (locations && locations.length > 0) {
          return (
            "Các địa điểm hiện có: " +
            locations.slice(0, 5).map((l) => l.name).join(", ")
          );
        } else {
          return "Chưa có địa điểm nào.";
        }
      } catch (err) {
        return "Không thể lấy thông tin địa điểm: " + err.message;
      }
    }
    return null; // Không khớp ý định
  };

  const handleSend = async (e) => {
    e.preventDefault();
    if (!input.trim() || loading) return;
    const userMsg = { from: "user", text: input };
    setMessages((msgs) => [...msgs, userMsg]);
    setInput("");
    setLoading(true);
    // Hiển thị trạng thái đang trả lời
    setMessages((msgs) => [
      ...msgs,
      { from: "bot", text: "Đang trả lời..." },
    ]);
    // Ưu tiên trả lời từ API
    let apiAnswer = await handleIntent(userMsg.text);
    if (apiAnswer) {
      setMessages((msgs) => [
        ...msgs.slice(0, -1),
        { from: "bot", text: apiAnswer },
      ]);
      setLoading(false);
      return;
    }
    // Nếu không khớp ý định, fallback sang AI Gemini
    try {
      const res = await fetch(GEMINI_API_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          contents: [{ parts: [{ text: userMsg.text }] }],
        }),
      });
      const data = await res.json();
      let aiText = "Xin lỗi, tôi chưa thể trả lời câu hỏi này.";
      if (
        data &&
        data.candidates &&
        data.candidates[0]?.content?.parts[0]?.text
      ) {
        aiText = data.candidates[0].content.parts[0].text;
      }
      setMessages((msgs) => [
        ...msgs.slice(0, -1),
        { from: "bot", text: aiText },
      ]);
    } catch (err) {
      setMessages((msgs) => [
        ...msgs.slice(0, -1),
        {
          from: "bot",
          text: "Đã xảy ra lỗi khi kết nối AI. Vui lòng thử lại sau.",
        },
      ]);
    } finally {
      setLoading(false);
    }
  };

  // Gợi ý nhanh cho khách hàng
  const quickReplies = [
    { label: "Tra cứu vé", value: "Tra cứu vé" },
    { label: "Xem lịch trình", value: "Xem chuyến xe" },
    { label: "Giá vé", value: "Giá vé" },
    { label: "Hướng dẫn đặt vé", value: "Hướng dẫn đặt vé" },
    { label: "Liên hệ tổng đài", value: "Liên hệ tổng đài" },
    { label: "Đặt vé", value: "Đặt vé" },
  ];

  // Kịch bản trả lời nhanh (FAQ)
  const faqScenarios = [
    {
      keywords: ["giờ xuất bến", "giờ chạy", "giờ khởi hành"],
      answer: "Các chuyến xe xuất bến từ 5h sáng đến 22h hàng ngày. Bạn muốn xem lịch trình cụ thể tuyến nào?"
    },
    {
      keywords: ["địa chỉ bến xe", "bến xe ở đâu", "địa chỉ văn phòng"],
      answer: "Bạn vui lòng cho biết bạn muốn hỏi địa chỉ bến xe nào? Ví dụ: bến xe miền Đông, bến xe Đà Lạt..."
    },
    {
      keywords: ["có xe giường nằm không", "loại xe", "ghế ngồi hay giường nằm"],
      answer: "Nhà xe có cả xe ghế ngồi và xe giường nằm. Bạn muốn đặt loại xe nào?"
    },
    {
      keywords: ["thanh toán", "chuyển khoản", "momo", "vnpay", "trả sau"],
      answer: "Bạn có thể thanh toán qua Momo, VNPAY, chuyển khoản hoặc trả tiền mặt tại quầy."
    },
    {
      keywords: ["hủy vé", "đổi vé", "đổi ngày", "đổi chuyến"],
      answer: "Bạn muốn hủy/đổi vé? Vui lòng cung cấp mã vé và số điện thoại để được hỗ trợ nhanh nhất."
    },
    {
      keywords: ["gặp nhân viên", "nói chuyện với nhân viên", "chuyển tiếp nhân viên", "gặp tư vấn viên", "gặp tổng đài viên"],
      answer: "Bạn vui lòng gọi tổng đài 1900 6746 hoặc để lại nội dung cần hỗ trợ, nhân viên sẽ liên hệ lại trong thời gian sớm nhất."
    },
  ];

  // Xử lý khi khách nhấn quick reply
  const handleQuickReply = async (val) => {
    if (val === "Đặt vé") {
      setShowBookingForm(true);
      setBookingForm({
        name: customerInfo.name || "",
        phone: customerInfo.phone || "",
        email: customerInfo.email || "",
        from: "",
        to: "",
        date: "",
        seat: "",
        note: ""
      });
      setBookingError("");
      setInput("");
      setMessages((msgs) => [
        ...msgs,
        { from: "user", text: "Đặt vé" },
        { from: "bot", text: "Vui lòng điền thông tin đặt vé vào form bên dưới." },
      ]);
      return;
    }
    setInput(val);
    setTimeout(() => {
      document.getElementById("cskh-input")?.focus();
    }, 100);
  };

  // Validate ngày đi (dd/mm/yyyy, không cho chọn ngày quá khứ)
  function isValidDateVN(dateStr) {
    if (!/^\d{2}\/\d{2}\/\d{4}$/.test(dateStr)) return false;
    const [d, m, y] = dateStr.split('/').map(Number);
    const date = new Date(y, m - 1, d);
    if (date.getFullYear() !== y || date.getMonth() !== m - 1 || date.getDate() !== d) return false;
    const today = new Date();
    today.setHours(0,0,0,0);
    return date >= today;
  }

  // Xử lý thay đổi form đặt vé
  const handleBookingFormChange = (e) => {
    setBookingForm({ ...bookingForm, [e.target.name]: e.target.value });
  };

  // Xử lý gửi form đặt vé
  const handleBookingFormSubmit = async (e) => {
    e.preventDefault();
    if (!bookingForm.name.trim() || !bookingForm.phone.trim() || !bookingForm.email.trim() || !bookingForm.from.trim() || !bookingForm.to.trim() || !bookingForm.date.trim() || !bookingForm.seat.trim()) {
      setBookingError("Vui lòng nhập đầy đủ các trường bắt buộc.");
      return;
    }
    if (!isValidDateVN(bookingForm.date)) {
      setBookingError("Ngày đi không hợp lệ hoặc đã qua. Định dạng: dd/mm/yyyy và không được chọn ngày quá khứ.");
      return;
    }
    setBookingError("");
    setLoading(true);
    try {
      const res = await fetch("http://localhost:8000/wp-json/custom/v1/contact", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: bookingForm.name,
          phone: bookingForm.phone,
          email: bookingForm.email,
          message: `Đặt vé:\nĐiểm đi: ${bookingForm.from}\nĐiểm đến: ${bookingForm.to}\nNgày đi: ${bookingForm.date}\nSố ghế: ${bookingForm.seat}\nGhi chú: ${bookingForm.note}`,
        }),
      });
      if (res.ok) {
        setMessages((msgs) => [
          ...msgs,
          { from: "bot", text: "Yêu cầu đặt vé của bạn đã được gửi. Chúng tôi sẽ liên hệ lại để xác nhận thông tin và giữ chỗ cho bạn!" },
        ]);
        setShowBookingForm(false);
      } else {
        setBookingError("Gửi yêu cầu thất bại. Vui lòng thử lại hoặc liên hệ tổng đài!");
      }
    } catch (err) {
      setBookingError("Đã xảy ra lỗi khi gửi yêu cầu đặt vé.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      {/* Nút mở chat */}
      {!open && (
        <button
          className="fixed bottom-20 right-8 z-[100] bg-transparent group"
          onClick={() => setOpen(true)}
          aria-label="Mở chatbot hỗ trợ khách hàng"
          style={{ boxShadow: 'none', border: 'none', background: 'none' }}
        >
          <span className="relative flex items-center justify-center w-16 h-16 pointer-events-auto">
            <span className="absolute inset-0 rounded-full bg-gradient-to-br from-bluecustom to-cyan-400 animate-pulse-slow group-hover:scale-110 transition-transform duration-300" style={{ filter: 'blur(2px)' }}></span>
            <span className="absolute inset-1 rounded-full bg-white border-4 border-white"></span>
            <img src={logo} alt="Logo" className="relative w-10 h-10 rounded-full z-10 object-contain" />
          </span>
        </button>
      )}
      {/* Cửa sổ chat */}
      {open && (
        <>
          <button
            className="fixed bottom-8 right-8 z-[100] bg-transparent group"
            onClick={() => setOpen(false)}
            aria-label="Đóng chatbot hỗ trợ khách hàng"
            style={{ boxShadow: 'none', border: 'none', background: 'none' }}
          >
            <span className="relative flex items-center justify-center w-16 h-16 pointer-events-auto">
              <span className="absolute inset-0 rounded-full bg-gradient-to-br from-bluecustom to-cyan-400 animate-pulse-slow group-hover:scale-110 transition-transform duration-300" style={{ filter: 'blur(2px)' }}></span>
              <span className="absolute inset-1 rounded-full bg-white border-4 border-white"></span>
              <img src={logo} alt="Logo" className="relative w-10 h-10 rounded-full z-10 object-contain" />
            </span>
          </button>
          <div className={`fixed bottom-[104px] right-8 z-50 w-[320px] max-w-full bg-white rounded-2xl shadow-2xl flex flex-col border border-bluecustom animate-fade-in transition-all duration-200 ${minimized ? 'h-16 overflow-hidden' : ''}`} style={{ maxHeight: minimized ? 64 : '80vh', height: minimized ? 64 : 'min(28rem,80vh)', boxShadow: '0 8px 32px 0 rgba(31, 38, 135, 0.18)' }}>
            <div className="flex items-center justify-between p-3 bg-gradient-to-r from-bluecustom to-cyan-400 rounded-t-2xl shadow-md" style={{ minHeight: 48 }}>
              <div className="flex items-center gap-2">
                <img src={logo} alt="Logo" className="w-8 h-8 rounded-full border-2 border-white shadow" />
                <span className="text-white font-bold text-base tracking-wide drop-shadow font-montserrat">Hỗ trợ khách hàng</span>
              </div>
              <div className="flex items-center gap-1">
                <button
                  className="text-white hover:text-gray-200 text-xl font-bold px-1 focus:outline-none rounded-full transition-colors duration-150 hover:bg-blue-600"
                  onClick={() => setMinimized((m) => !m)}
                  aria-label={minimized ? 'Mở rộng chat' : 'Thu nhỏ chat'}
                  style={{ width: 28, height: 28, display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                >
                  {minimized ? (
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 15l7-7 7 7" /></svg>
                  ) : (
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" /></svg>
                  )}
                </button>
                <button
                  className="text-white hover:text-gray-200 text-xl font-bold px-1 focus:outline-none rounded-full transition-colors duration-150 hover:bg-blue-600"
                  onClick={() => setOpen(false)}
                  aria-label="Đóng chatbot"
                  style={{ width: 28, height: 28, display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                >
                  <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
              </div>
            </div>
            {!minimized && (
              <>
                {/* Form xin thông tin khách hàng */}
                {!infoSubmitted && (
                  <form onSubmit={handleInfoSubmit} className="flex flex-col gap-3 p-4 bg-gradient-to-b from-blue-50 to-white rounded-b-2xl">
                    <input
                      className="px-4 py-2 rounded-lg border border-blue-200 focus:ring-2 focus:ring-bluecustom focus:outline-none text-base placeholder:text-bluecustom font-roboto"
                      type="text"
                      name="name"
                      placeholder="Họ tên của bạn"
                      value={customerInfo.name}
                      onChange={handleInfoChange}
                      autoFocus={!user || !user.id}
                    />
                    <input
                      className="px-4 py-2 rounded-lg border border-blue-200 focus:ring-2 focus:ring-bluecustom focus:outline-none text-base placeholder:text-bluecustom font-roboto"
                      type="email"
                      name="email"
                      placeholder="Email của bạn"
                      value={customerInfo.email}
                      onChange={handleInfoChange}
                    />
                    <input
                      className="px-4 py-2 rounded-lg border border-blue-200 focus:ring-2 focus:ring-bluecustom focus:outline-none text-base placeholder:text-bluecustom font-roboto"
                      type="tel"
                      name="phone"
                      placeholder="Số điện thoại của bạn"
                      value={customerInfo.phone}
                      onChange={handleInfoChange}
                    />
                    {infoError && <div className="text-red-500 text-sm font-medium">{infoError}</div>}
                    <button
                      type="submit"
                      className="bg-gradient-to-r from-bluecustom to-cyan-400 text-white px-4 py-2 rounded-lg font-bold shadow hover:from-blue-500 hover:to-cyan-300 transition-all text-base font-montserrat"
                    >
                      Bắt đầu chat
                    </button>
                  </form>
                )}
                {/* Giao diện chat chỉ hiển thị khi đã nhập thông tin */}
                {infoSubmitted && (
                  <>
                    <div className="flex-1 overflow-y-auto p-4 bg-gradient-to-b from-blue-50 to-white rounded-b-2xl scrollbar-thin scrollbar-thumb-blue-200 scrollbar-track-blue-50" style={{ maxHeight: 'calc(60vh - 120px)' }}>
                      {messages.map((msg, idx) => (
                        <div
                          key={idx}
                          className={`mb-2 flex ${msg.from === "user" ? "justify-end" : "justify-start"}`}
                        >
                          {msg.from === "bot" && (
                            <img src={logo} alt="Bot" className="w-7 h-7 rounded-full border-2 border-blue-200 shadow mr-2 animate-fade-in" />
                          )}
                          <div
                            className={`px-4 py-2 rounded-2xl text-base max-w-[75%] shadow relative leading-relaxed whitespace-pre-line font-roboto ${
                              msg.from === "user"
                                ? "bg-bluecustom text-white rounded-br-md flex flex-row-reverse items-end"
                                : "bg-white text-gray-900 border border-blue-100 rounded-bl-md"
                            }`}
                            style={{ wordBreak: 'break-word', fontSize: 15 }}
                          >
                            {msg.from === "bot" && idx === messages.length - 1 && loading ? (
                              <span className="inline-block animate-pulse">Đang trả lời...</span>
                            ) : (
                              msg.text
                            )}
                          </div>
                          {msg.from === "user" && (
                            user && user.avatar_url ? (
                              <img src={user.avatar_url} alt="User" className="ml-2 w-7 h-7 rounded-full border-2 border-bluecustom shadow animate-fade-in object-cover" onError={(e) => { e.target.onerror = null; e.target.src = `/default-avatar.png`; }} />
                            ) : (
                              <span className="ml-2 w-7 h-7 rounded-full bg-blue-200 flex items-center justify-center font-bold text-bluecustom shadow animate-fade-in text-base">
                                {customerInfo.name ? customerInfo.name[0].toUpperCase() : "U"}
                              </span>
                            )
                          )}
                        </div>
                      ))}
                      <div ref={messagesEndRef} />
                    </div>
                    {/* Quick replies */}
                    <div className="flex flex-wrap gap-2 px-3 pb-2 pt-1">
                      {quickReplies.map((q) => (
                        <button
                          key={q.value}
                          className="bg-blue-100 text-bluecustom px-3 py-1 rounded-full text-xs font-semibold shadow hover:bg-blue-200 transition-all border border-blue-200 focus:outline-none focus:ring-2 focus:ring-bluecustom font-montserrat"
                          onClick={() => handleQuickReply(q.value)}
                          type="button"
                        >
                          {q.label}
                        </button>
                      ))}
                      {/* Nút gọi tổng đài */}
                      <a
                        href="tel:19006746"
                        className="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold shadow hover:bg-green-200 transition-all flex items-center border border-green-200 focus:outline-none focus:ring-2 focus:ring-green-300 font-montserrat"
                        style={{ textDecoration: "none" }}
                      >
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" className="mr-1"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm0 12a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2zm12-12a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zm0 12a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                        Gọi tổng đài
                      </a>
                    </div>
                    <form onSubmit={handleSend} className="flex p-2 border-t bg-white rounded-b-3xl gap-2">
                      <input
                        id="cskh-input"
                        className="flex-1 px-3 py-2 rounded-l-xl border border-blue-200 focus:ring-2 focus:ring-bluecustom focus:outline-none text-base placeholder:text-bluecustom font-roboto"
                        type="text"
                        placeholder="Nhập câu hỏi..."
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        autoFocus
                        disabled={loading}
                        style={{ fontSize: 15 }}
                      />
                      <button
                        type="submit"
                        className="bg-gradient-to-r from-bluecustom to-cyan-400 text-white px-4 py-2 rounded-r-xl font-bold shadow hover:from-blue-500 hover:to-cyan-300 transition-all text-base font-montserrat focus:outline-none focus:ring-2 focus:ring-bluecustom"
                        disabled={!input.trim() || loading}
                        style={{ fontSize: 15 }}
                      >
                        {loading ? <span className="animate-pulse">...</span> : "Gửi"}
                      </button>
                    </form>
                    {/* Form đặt vé nhanh */}
                    {showBookingForm && (
                      <form onSubmit={handleBookingFormSubmit} className="flex flex-col gap-2 bg-blue-50 border border-blue-200 rounded-xl p-3 my-2 animate-fade-in overflow-y-auto" style={{maxWidth:'100%', minWidth:0, maxHeight:'320px'}}>
                        <div className="font-bold text-bluecustom mb-1 font-montserrat">Đặt vé nhanh</div>
                        <input name="name" value={bookingForm.name} onChange={handleBookingFormChange} className="px-3 py-2 rounded border border-blue-200 text-base font-roboto" placeholder="Họ tên*" required />
                        <input name="phone" value={bookingForm.phone} onChange={handleBookingFormChange} className="px-3 py-2 rounded border border-blue-200 text-base font-roboto" placeholder="Số điện thoại*" required />
                        <input name="email" value={bookingForm.email} onChange={handleBookingFormChange} className="px-3 py-2 rounded border border-blue-200 text-base font-roboto" placeholder="Email*" required />
                        <select name="from" value={bookingForm.from} onChange={handleBookingFormChange} className="px-3 py-2 rounded border border-blue-200 text-base font-roboto" required>
                          <option value="">Chọn điểm đi*</option>
                          {tripsLoading ? <option>Đang tải...</option> : tripLocations.from.map((name) => <option key={name} value={name}>{name}</option>)}
                        </select>
                        <select name="to" value={bookingForm.to} onChange={handleBookingFormChange} className="px-3 py-2 rounded border border-blue-200 text-base font-roboto" required>
                          <option value="">Chọn điểm đến*</option>
                          {tripsLoading ? <option>Đang tải...</option> : tripLocations.to.map((name) => <option key={name} value={name}>{name}</option>)}
                        </select>
                        <input name="date" value={bookingForm.date} onChange={handleBookingFormChange} className="px-3 py-2 rounded border border-blue-200 text-base font-roboto" placeholder="Ngày đi (dd/mm/yyyy)*" required />
                        <input name="seat" value={bookingForm.seat} onChange={handleBookingFormChange} className="px-3 py-2 rounded border border-blue-200 text-base font-roboto" placeholder="Số ghế*" required />
                        <input name="note" value={bookingForm.note} onChange={handleBookingFormChange} className="px-3 py-2 rounded border border-blue-200 text-base font-roboto" placeholder="Ghi chú (tuỳ chọn)" />
                        {bookingError && <div className="text-red-500 text-xs font-medium">{bookingError}</div>}
                        <div className="flex gap-2 mt-1 flex-wrap">
                          <button type="submit" className="bg-bluecustom text-white px-4 py-2 rounded font-bold hover:bg-blue-700 transition-all text-base font-montserrat" disabled={loading}>{loading ? "Đang gửi..." : "Gửi yêu cầu"}</button>
                          <button type="button" className="bg-gray-200 text-gray-700 px-4 py-2 rounded font-bold hover:bg-gray-300 transition-all text-base font-montserrat" onClick={() => setShowBookingForm(false)} disabled={loading}>Huỷ</button>
                        </div>
                      </form>
                    )}
                  </>
                )}
              </>
            )}
          </div>
        </>
      )}
    </>
  );
}

export default ChatBot;