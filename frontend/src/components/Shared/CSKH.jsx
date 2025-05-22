import { useState, useEffect, useRef } from "react";
import Logo from "../../assets/images/logo.png";
import User from "../../assets/icons/user.jpg";
// Giả định dịch vụ mock cho dữ liệu
import data from "../../data/cleaned_data.json"; // Import file JSON

function ChatbotAI() {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState([
    {
      sender: "bot",
      text: "Chào bạn! Mình là Chatbot AI của Nhà Xe Mỹ Duyên. Hiện tại, mình có thể hỗ trợ bạn **tra cứu vé** và **tra cứu chuyến xe**. Bạn muốn tra cứu gì? Ví dụ: \n- Tra cứu vé: 'Tra cứu vé TICKET-ABC123, 0909123456' \n- Tra cứu chuyến xe: 'Bến xe Phía Nam - Bến xe Miền Đông'",
      timestamp: new Date().toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
      }),
    },
  ]);
  const [input, setInput] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [routes, setRoutes] = useState([]);
  const [trips, setTrips] = useState([]);
  const [tickets, setTickets] = useState([]);
  const messagesEndRef = useRef(null);

  // Load data from JSON file
  useEffect(() => {
    setRoutes(data.routes);
    setTrips(data.trips);
    setTickets(data.tickets);
  }, []);

  // Toggle chatbot
  const toggleChatbot = () => setIsOpen(!isOpen);

  // Clear chat history
  const clearChat = () => {
    setMessages([
      {
        sender: "bot",
        text: "Cuộc trò chuyện đã được làm mới. Mình có thể giúp gì cho bạn hôm nay? \n- Tra cứu vé: 'Tra cứu vé TICKET-ABC123, 0909123456' \n- Tra cứu chuyến xe: 'Bến xe Phía Nam - Bến xe Miền Đông'",
        timestamp: new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }),
      },
    ]);
  };

  // Scroll to bottom
  const scrollToBottom = () => messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  useEffect(() => scrollToBottom(), [messages]);

  // Handle message submission
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!input.trim()) return;

    setMessages((prev) => [
      ...prev,
      { sender: "user", text: input, timestamp: new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }) },
    ]);
    setInput("");
    setIsLoading(true);

    const response = await generateBotResponse(input.toLowerCase());
    setMessages((prev) => [
      ...prev,
      { sender: "bot", text: response, timestamp: new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }) },
    ]);
    setIsLoading(false);
  };

  // Bot response logic
  const generateBotResponse = async (userInput) => {
    try {
      // Check schedule
      if (userInput.match(/(\w+(?:\s\w+)*)\s*-\s*(\w+(?:\s\w+)*)/)) {
        const routeMatch = userInput.match(/(\w+(?:\s\w+)*)\s*-\s*(\w+(?:\s\w+)*)/);
        const [start, end] = routeMatch.slice(1).map((s) => s.trim().toLowerCase());
        const currentDate = new Date("2025-05-18T20:40:00+07:00");
        const relevantTrips = trips.filter(
          (trip) =>
            trip.pickup_location.toLowerCase().includes(start) &&
            trip.dropoff_location.toLowerCase().includes(end) &&
            new Date(trip.departure_time) >= currentDate
        );
        if (!relevantTrips.length) {
          return `Hiện tại không có chuyến xe nào từ ${start} đến ${end} trong tương lai. Bạn có muốn kiểm tra tuyến khác không?`;
        }
        return `Mình đã kiểm tra tuyến ${start} - ${end} cho bạn! Hiện có các chuyến vào: ${relevantTrips
          .map((trip) => new Date(trip.departure_time).toLocaleString("vi-VN", { dateStyle: "short", timeStyle: "short" }))
          .join(", ")}. Bạn có muốn tra cứu thêm tuyến khác không?`;
      }

      // Check ticket by ticket code
      if (userInput.includes("tra cứu vé")) {
        const ticketMatch = userInput.match(/TICKET-\w+/);
        const phoneMatch = userInput.match(/\d{10}/);
        if (ticketMatch && phoneMatch) {
          const ticketCode = ticketMatch[0];
          const phoneNumber = phoneMatch[0];
          const ticket = tickets.find((t) => t.ticket_code === ticketCode && t.customer_phone === phoneNumber);
          if (ticket) {
            const trip = trips.find((t) => t.trip_id === ticket.trip_id);
            const departureDate = new Date(trip.departure_time);
            return `Mình đã tìm thấy vé của bạn! Mã vé: ${ticket.ticket_code}, tuyến: ${ticket.pickup_location} - ${ticket.dropoff_location}, số ghế: ${ticket.seat_number}, ngày khởi hành: ${departureDate.toLocaleString("vi-VN", { dateStyle: "short", timeStyle: "short" })}, trạng thái: ${ticket.status}. Bạn có muốn tra cứu thêm vé khác không?`;
          }
          return `Không tìm thấy vé với mã ${ticketCode} và số điện thoại ${phoneNumber}. Bạn có thể kiểm tra lại thông tin!`;
        }
        return "Hãy cung cấp mã vé và số điện thoại (ví dụ: TICKET-ABC123, 0909123456).";
      }

      // Default response for unsupported queries
      return "Mình hiện chỉ hỗ trợ **tra cứu vé** và **tra cứu chuyến xe**. Bạn có thể thử lại với ví dụ: \n- Tra cứu vé: 'Tra cứu vé TICKET-ABC123, 0909123456' \n- Tra cứu chuyến xe: 'Bến xe Phía Nam - Bến xe Miền Đông'";
    } catch (error) {
      console.error("Bot response error:", error);
      return `Xin lỗi, có lỗi xảy ra: ${error.message}. Vui lòng thử lại hoặc liên hệ hotline 1900 1234!`;
    }
  };

  return (
    <>
      {/* Toggle chatbot button */}
      <button
        onClick={toggleChatbot}
        className="fixed right-10 bottom-14 bg-gradient-to-r from-blue-500 to-blue-600 text-white p-3 rounded-full shadow-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-300 z-50 animate-bounce"
      >
        <svg
          className="w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="2"
            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
          />
        </svg>
      </button>

      {/* Chatbot window */}
      <div
        className={`fixed right-4 bottom-28 bg-white shadow-2xl rounded-xl w-[90%] max-w-xs sm:w-80 h-[400px] transform transition-all duration-500 z-40 ${
          isOpen ? "scale-100 opacity-100" : "scale-90 opacity-0 pointer-events-none"
        }`}
      >
        <div className="flex flex-col h-full">
          {/* Header */}
          <div className="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-3 rounded-t-xl flex items-center justify-between">
            <div className="flex items-center space-x-2">
              <img src={Logo} alt="Nhà Xe Mỹ Duyên" className="w-8 h-8 rounded-full border-2 border-white" />
              <div>
                <h3 className="text-base font-semibold">Chatbot Mỹ Duyên</h3>
                <p className="text-xs opacity-80">Hỗ trợ 24/7</p>
              </div>
            </div>
            <div className="flex items-center space-x-1">
              <button
                onClick={clearChat}
                className="text-white hover:bg-blue-700 p-1 rounded-full transition-colors duration-300"
                title="Xóa lịch sử chat"
              >
                <svg
                  className="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4a2 2 0 012 2v1H8V5a2 2 0 012-2z"
                  />
                </svg>
              </button>
              <button
                onClick={toggleChatbot}
                className="text-white hover:bg-blue-700 p-1 rounded-full transition-colors duration-300"
              >
                <svg
                  className="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>

          {/* Message area */}
          <div className="flex-1 overflow-y-auto p-3 bg-gray-100">
            {messages.map((msg, index) => (
              <div
                key={index}
                className={`mb-3 flex ${msg.sender === "user" ? "justify-end" : "justify-start"} animate-slideIn`}
              >
                <div className="flex items-start space-x-1">
                  {msg.sender === "bot" && <img src={Logo} alt="Bot" className="w-6 h-6 rounded-full mt-1" />}
                  <div
                    className={`max-w-[75%] p-2 rounded-xl shadow-sm transition-all duration-300 ${
                      msg.sender === "user"
                        ? "bg-blue-500 text-white rounded-br-none"
                        : "bg-white text-gray-800 rounded-bl-none"
                    } hover:shadow-md`}
                  >
                    <p className="text-xs">{msg.text}</p>
                    <span className="text-[10px] text-gray-400 mt-1 block">{msg.timestamp}</span>
                  </div>
                  {msg.sender === "user" && <img src={User} alt="User" className="w-6 h-6 rounded-full mt-1" />}
                </div>
              </div>
            ))}
            {isLoading && (
              <div className="flex justify-start mb-3">
                <div className="bg-white text-gray-800 p-2 rounded-xl shadow-sm text-xs">Đang xử lý...</div>
              </div>
            )}
            <div ref={messagesEndRef} />
          </div>

          {/* Suggestion buttons */}
          <div className="p-2 flex flex-wrap gap-1 bg-gray-100 border-t border-gray-200">
            <button
              onClick={() => setInput("Tra cứu vé")}
              className="px-3 py-1 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition-colors duration-300 text-xs font-medium"
            >
              Tra cứu vé
            </button>
            <button
              onClick={() => setInput("Tra cứu chuyến xe")}
              className="px-3 py-1 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition-colors duration-300 text-xs font-medium"
            >
              Tra cứu chuyến xe
            </button>
          </div>

          {/* Message input form */}
          <form onSubmit={handleSubmit} className="p-3 bg-white rounded-b-xl flex items-center border-t border-gray-200">
            <input
              type="text"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              className="flex-1 px-3 py-1 bg-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-400 hover:bg-gray-200 transition-all duration-300 text-xs"
              placeholder="Nhập câu hỏi..."
              disabled={isLoading}
            />
            <button
              type="submit"
              className="ml-2 bg-blue-500 text-white p-1 rounded-full hover:bg-blue-600 transition-colors duration-300"
              disabled={isLoading}
            >
              <svg
                className="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth="2"
                  d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                />
              </svg>
            </button>
          </form>
        </div>
      </div>

      {/* Inline CSS for animations */}
      <style>{`
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-slideIn { animation: slideIn 0.3s ease-out; }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .animate-bounce { animation: bounce 2s infinite; }
        @media (max-width: 640px) {
          .fixed.right-4.bottom-28 { right: 2rem; bottom: 4.5rem; width: calc(100% - 2rem); max-width: 300px; height: 350px; }
          .p-3 { padding: 0.5rem; }
          .text-base { font-size: 0.875rem; }
          .text-xs { font-size: 0.65rem; }
          .text-[10px] { font-size: 0.6rem; }
          .w-8.h-8 { width: 1.5rem; height: 1.5rem; }
          .w-6.h-6 { width: 1.25rem; height: 1.25rem; }
          .px-3.py-1 { padding: 0.25rem 0.75rem; }
          .p-1 { padding: 0.25rem; }
        }
      `}</style>
    </>
  );
}

export default ChatbotAI;