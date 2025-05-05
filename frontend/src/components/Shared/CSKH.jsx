import { useState, useEffect, useRef } from "react";
import { GoogleGenerativeAI } from "@google/generative-ai";
import Logo from "../../assets/images/logo.png";
import User from "../../assets/icons/user.jpg";
import tripService from "../../service/tripService";
import ticketService from "../../service/ticketService";

function ChatbotAI() {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState([
    {
      sender: "bot",
      text: "Chào bạn! Mình là Chatbot AI của Nhà Xe Mỹ Duyên, rất vui được hỗ trợ bạn! Bạn đang cần giúp gì hôm nay?",
      timestamp: new Date().toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
      }),
    },
  ]);
  const [input, setInput] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const messagesEndRef = useRef(null);

  // Initialize Gemini AI
  const genAI = new GoogleGenerativeAI(process.env.REACT_APP_GEMINI_API_KEY || "");

  // Toggle chatbot
  const toggleChatbot = () => {
    setIsOpen(!isOpen);
  };

  // Clear chat history
  const clearChat = () => {
    setMessages([
      {
        sender: "bot",
        text: "Cuộc trò chuyện đã được làm mới. Mình có thể giúp gì cho bạn hôm nay?",
        timestamp: new Date().toLocaleTimeString([], {
          hour: "2-digit",
          minute: "2-digit",
        }),
      },
    ]);
  };

  // Scroll to bottom
  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  // Call Gemini API
  const callGeminiAPI = async (userInput, retries = 3) => {
    try {
      if (!process.env.REACT_APP_GEMINI_API_KEY) {
        throw new Error("API key is missing. Please set REACT_APP_GEMINI_API_KEY in .env.");
      }

      const model = genAI.getGenerativeModel({ model: "gemini-2.0-flash" });
      const sanitizedInput = userInput.replace(/[<>{}]/g, "");
      const result = await model.generateContent(
        `Bạn là một trợ lý AI chuyên nghiệp, hỗ trợ người dùng đặt vé xe, kiểm tra lịch trình, giá vé, và hủy vé cho Nhà Xe Mỹ Duyên. Hãy trả lời ngắn gọn, thân thiện, và tự nhiên bằng tiếng Việt. Câu hỏi: ${sanitizedInput}`
      );
      const response = await result.response;
      const text = response.text();
      return text || "Mình chưa hiểu rõ câu hỏi của bạn. Bạn có thể hỏi lại không?";
    } catch (error) {
      if (retries > 0 && error.message.includes("429")) {
        await new Promise((resolve) => setTimeout(resolve, 1000));
        return callGeminiAPI(userInput, retries - 1);
      }
      return `Có lỗi khi kết nối với AI: ${error.message}. Bạn có thể thử lại hoặc hỏi về đặt vé, lịch trình, giá vé nhé!`;
    }
  };

  // Handle message submission
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!input.trim()) return;

    setMessages((prev) => [
      ...prev,
      {
        sender: "user",
        text: input,
        timestamp: new Date().toLocaleTimeString([], {
          hour: "2-digit",
          minute: "2-digit",
        }),
      },
    ]);
    setInput("");
    setIsLoading(true);

    const response = await generateBotResponse(input.toLowerCase());
    setMessages((prev) => [
      ...prev,
      {
        sender: "bot",
        text: response,
        timestamp: new Date().toLocaleTimeString([], {
          hour: "2-digit",
          minute: "2-digit",
        }),
      },
    ]);
    setIsLoading(false);
  };

  // Bot response logic
  const generateBotResponse = async (userInput) => {
    try {
      // Confirm ticket cancellation
      if (userInput.includes("hủy vé") && userInput.includes("có")) {
        const ticketMatch = messages[messages.length - 2]?.text.match(/TICKET-\w+/);
        if (ticketMatch) {
          const ticketCode = ticketMatch[0];
          const response = await ticketService.deleteTicket(ticketCode);
          return `Vé ${ticketCode} đã được hủy thành công! Bạn cần hỗ trợ gì thêm không?`;
        }
        return "Mình không tìm thấy mã vé để hủy. Bạn có thể cung cấp lại mã vé (ví dụ: TICKET-ABC123) không?";
      }

      // Book ticket
      if (
        userInput.includes("đặt vé") &&
        userInput.includes("hà nội") &&
        userInput.includes("sài gòn") &&
        userInput.match(/\d{2}\/\d{2}/)
      ) {
        const date = userInput.match(/\d{2}\/\d{2}/)[0];
        const trips = await tripService.getTrips();
        const relevantTrips = trips.filter(
          (trip) =>
            trip.route?.start_location?.toLowerCase().includes("hà nội") &&
            trip.route?.end_location?.toLowerCase().includes("sài gòn") &&
            new Date(trip.departure_time).toLocaleDateString("vi-VN") === date
        );
        if (!relevantTrips || relevantTrips.length === 0) {
          return `Hiện tại không có chuyến xe nào từ Hà Nội đi Sài Gòn vào ngày ${date}. Bạn có muốn thử ngày khác không?`;
        }

        const trip = relevantTrips[0]; // Chọn chuyến xe đầu tiên
        const availableSeats = await ticketService.getSeatAvailability(trip.trip_id);
        if (!availableSeats || availableSeats.length === 0) {
          return "Hiện tại không còn ghế trống cho chuyến xe này. Bạn có muốn thử chuyến khác không?";
        }

        const ticketData = {
          trip_id: trip.trip_id,
          customer_name: "Khách hàng", // Cần hỏi thêm thông tin khách hàng
          customer_phone: "0909123456", // Cần hỏi thêm
          customer_email: "khachhang@example.com", // Cần hỏi thêm
          pickup_location: "Hà Nội",
          dropoff_location: "Sài Gòn",
          seat_number: availableSeats[0], // Chọn ghế đầu tiên có sẵn
          status: "Chưa thanh toán",
        };
        const newTicket = await ticketService.createTicketsBulk([ticketData]);
        return `Vé của bạn đã được đặt thành công! Mã vé: ${newTicket.tickets[0].ticket_code}. Bạn cần in vé hay kiểm tra thêm thông tin?`;
      }

      // Request ticket booking info
      if (userInput.includes("vé xe") || userInput.includes("đặt vé")) {
        const trips = await tripService.getTrips();
        if (!trips || trips.length === 0) {
          return "Hiện tại chưa có chuyến xe nào. Vui lòng thử lại sau hoặc liên hệ hotline 1900 1234.";
        }
        const uniqueRoutes = [...new Set(trips.map((trip) => `${trip.route?.start_location} - ${trip.route?.end_location}`))];
        return `Chào bạn! Bạn muốn đặt vé xe đúng không? Hiện tại mình có các tuyến: ${uniqueRoutes.join(", ")}. Hãy cho mình biết điểm đi, điểm đến và ngày khởi hành để mình kiểm tra nhé! Ví dụ: Hà Nội - Sài Gòn, ngày 01/05.`;
      }

      // Check schedule (tra cứu chuyến đi)
      if (userInput.includes("lịch trình") || userInput.includes("chuyến xe") || userInput.includes("chuyến đi")) {
        const trips = await tripService.getTrips();
        if (!trips || trips.length === 0) {
          return "Hiện tại chưa có chuyến xe nào được thiết lập. Bạn có thể xem lại sau hoặc liên hệ hotline 1900 1234 để được hỗ trợ.";
        }
        const uniqueRoutes = [...new Set(trips.map((trip) => `${trip.route?.start_location} - ${trip.route?.end_location}`))];
        return `Bạn muốn xem lịch trình các chuyến xe phải không? Mình có các tuyến: ${uniqueRoutes.join(", ")}. Hãy cho mình biết tuyến bạn quan tâm (ví dụ: Hà Nội - Sài Gòn), mình sẽ kiểm tra ngay!`;
      }

      // Check specific schedule (Hà Nội - Sài Gòn)
      if (userInput.includes("hà nội") && userInput.includes("sài gòn")) {
        const trips = await tripService.getTrips();
        if (!trips || trips.length === 0) {
          return "Hiện tại chưa có chuyến xe nào. Bạn có muốn kiểm tra tuyến khác không?";
        }
        const relevantTrips = trips.filter(
          (trip) =>
            trip.route?.start_location?.toLowerCase().includes("hà nội") &&
            trip.route?.end_location?.toLowerCase().includes("sài gòn")
        );
        if (relevantTrips.length === 0) {
          return "Hiện tại không có chuyến xe nào từ Hà Nội đi Sài Gòn. Bạn có muốn kiểm tra tuyến khác không?";
        }
        return `Mình đã kiểm tra tuyến Hà Nội - Sài Gòn cho bạn! Hiện có các chuyến vào: ${relevantTrips
          .map((trip) => new Date(trip.departure_time).toLocaleString("vi-VN", { dateStyle: "short", timeStyle: "short" }))
          .join(", ")}. Bạn muốn đặt vé cho khung giờ nào?`;
      }

      // Check routes (tra cứu tuyến đường)
      if (userInput.includes("tuyến đường")) {
        const trips = await tripService.getTrips();
        if (!trips || trips.length === 0) {
          return "Hiện tại chưa có tuyến đường nào được thiết lập. Bạn có thể xem lại sau hoặc liên hệ hotline 1900 1234 để được hỗ trợ.";
        }
        const uniqueRoutes = [...new Set(trips.map((trip) => `${trip.route?.start_location} - ${trip.route?.end_location}`))];
        return `Hiện tại mình có các tuyến đường: ${uniqueRoutes.join(", ")}. Bạn muốn kiểm tra tuyến nào cụ thể?`;
      }

      // Check ticket price
      if (userInput.includes("giá vé")) {
        const trips = await tripService.getTrips();
        if (!trips || trips.length === 0) {
          return "Hiện tại chưa có chuyến xe nào để kiểm tra giá vé. Bạn có thể thử lại sau nhé!";
        }
        const uniqueRoutes = [...new Set(trips.map((trip) => `${trip.route?.start_location} - ${trip.route?.end_location}`))];
        return `Mình rất vui được hỗ trợ! Giá vé sẽ tùy thuộc vào tuyến đường và loại xe bạn chọn. Hiện mình có các tuyến: ${uniqueRoutes.join(", ")}. Bạn có thể cho mình biết tuyến bạn muốn đi không? Ví dụ: Hà Nội - Sài Gòn.`;
      }

      // Check ticket by ticket code
      if (userInput.includes("tra cứu vé")) {
        const ticketMatch = userInput.match(/TICKET-\w+/);
        const phoneMatch = userInput.match(/\d{10}/); // Giả sử số điện thoại có 10 chữ số
        if (ticketMatch && phoneMatch) {
          const ticketCode = ticketMatch[0];
          const phoneNumber = phoneMatch[0];
          const ticket = await ticketService.checkTicket(ticketCode, phoneNumber);
          if (!ticket) {
            return `Không tìm thấy vé với mã ${ticketCode} và số điện thoại ${phoneNumber}. Bạn có thể kiểm tra lại thông tin hoặc liên hệ hotline 1900 1234 để được hỗ trợ!`;
          }
          return `Mình đã tìm thấy vé của bạn! Mã vé: ${ticket.ticket_code}, tuyến: ${ticket.pickup_location} - ${ticket.dropoff_location}, số ghế: ${ticket.seat_number}, trạng thái: ${ticket.status}. Bạn có muốn hủy vé này không?`;
        }
        return "Bạn muốn tra cứu vé đúng không? Hãy cung cấp mã vé và số điện thoại của bạn (ví dụ: TICKET-ABC123, 0909123456). Nếu không có thông tin, bạn có thể liên hệ hotline 1900 1234 để được hỗ trợ nhanh hơn.";
      }

      // Cancel ticket
      if (userInput.includes("hủy vé")) {
        const ticketMatch = userInput.match(/TICKET-\w+/);
        if (ticketMatch) {
          const ticketCode = ticketMatch[0];
          const ticket = await ticketService.getTicket(ticketCode);
          if (!ticket) {
            return `Không tìm thấy vé với mã ${ticketCode}. Bạn có thể kiểm tra lại mã vé không?`;
          }
          return `Mình đã kiểm tra mã vé ${ticketCode}. Vé của bạn đi từ ${ticket.pickup_location} đến ${ticket.dropoff_location}, số ghế ${ticket.seat_number}, ngày khởi hành ${new Date(ticket.created_at).toLocaleDateString("vi-VN")}. Bạn có muốn hủy vé này không?`;
        }
        return "Bạn muốn hủy vé đúng không? Hãy cung cấp mã vé của bạn (ví dụ: TICKET-ABC123). Nếu không có mã vé, bạn có thể liên hệ hotline 1900 1234 để được hỗ trợ nhanh hơn.";
      }

      // Contact info
      if (userInput.includes("liên hệ")) {
        return "Bạn muốn liên hệ với Nhà Xe Mỹ Duyên? Bạn có thể gọi hotline 1900 1234 hoặc gửi email đến support@nhaxemyduyen.vn. Mình có thể giúp gì thêm cho bạn không?";
      }

      // Fallback to Gemini API
      return await callGeminiAPI(userInput);
    } catch (error) {
      console.error("Bot response error:", error);
      return `Xin lỗi, có lỗi xảy ra khi xử lý yêu cầu của bạn: ${error.message}. Bạn có thể thử lại hoặc liên hệ hotline 1900 1234 để được hỗ trợ nhé!`;
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
              <img
                src={Logo}
                alt="Nhà Xe Mỹ Duyên"
                className="w-8 h-8 rounded-full border-2 border-white"
              />
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
                className={`mb-3 flex ${
                  msg.sender === "user" ? "justify-end" : "justify-start"
                } animate-slideIn`}
              >
                <div className="flex items-start space-x-1">
                  {msg.sender === "bot" && (
                    <img
                      src={Logo}
                      alt="Bot"
                      className="w-6 h-6 rounded-full mt-1"
                    />
                  )}
                  <div
                    className={`max-w-[75%] p-2 rounded-xl shadow-sm transition-all duration-300 ${
                      msg.sender === "user"
                        ? "bg-blue-500 text-white rounded-br-none"
                        : "bg-white text-gray-800 rounded-bl-none"
                    } hover:shadow-md`}
                  >
                    <p className="text-xs">{msg.text}</p>
                    <span className="text-[10px] text-gray-400 mt-1 block">
                      {msg.timestamp}
                    </span>
                  </div>
                  {msg.sender === "user" && (
                    <img
                      src={User}
                      alt="User"
                      className="w-6 h-6 rounded-full mt-1"
                    />
                  )}
                </div>
              </div>
            ))}
            {isLoading && (
              <div className="flex justify-start mb-3">
                <div className="bg-white text-gray-800 p-2 rounded-xl shadow-sm text-xs">
                  Đang xử lý...
                </div>
              </div>
            )}
            <div ref={messagesEndRef} />
          </div>

          {/* Suggestion buttons */}
          <div className="p-2 flex flex-wrap gap-1 bg-gray-100 border-t border-gray-200">
            <button
              onClick={() => setInput("Đặt vé xe")}
              className="px-3 py-1 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition-colors duration-300 text-xs font-medium"
            >
              Đặt vé
            </button>
            <button
              onClick={() => setInput("Lịch trình chuyến xe")}
              className="px-3 py-1 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition-colors duration-300 text-xs font-medium"
            >
              Lịch trình
            </button>
            <button
              onClick={() => setInput("Giá vé")}
              className="px-3 py-1 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition-colors duration-300 text-xs font-medium"
            >
              Giá vé
            </button>
            <button
              onClick={() => setInput("Tra cứu vé")}
              className="px-3 py-1 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition-colors duration-300 text-xs font-medium"
            >
              Tra cứu vé
            </button>
            <button
              onClick={() => setInput("Tra cứu tuyến đường")}
              className="px-3 py-1 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition-colors duration-300 text-xs font-medium"
            >
              Tra cứu tuyến
            </button>
          </div>

          {/* Message input form */}
          <form
            onSubmit={handleSubmit}
            className="p-3 bg-white rounded-b-xl flex items-center border-t border-gray-200"
          >
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
      <style>
        {`
          @keyframes slideIn {
            from {
              opacity: 0;
              transform: translateY(10px);
            }
            to {
              opacity: 1;
              transform: translateY(0);
            }
          }
          .animate-slideIn {
            animation: slideIn 0.3s ease-out;
          }
          @keyframes bounce {
            0%, 100% {
              transform: translateY(0);
            }
            50% {
              transform: translateY(-10px);
            }
          }
          .animate-bounce {
            animation: bounce 2s infinite;
          }
          @media (max-width: 640px) {
            .fixed.right-4.bottom-28 {
              right: 2rem;
              bottom: 4.5rem;
              width: calc(100% - 2rem);
              max-width: 300px;
              height: 350px;
            }
            .p-3 {
              padding: 0.5rem;
            }
            .text-base {
              font-size: 0.875rem;
            }
            .text-xs {
              font-size: 0.65rem;
            }
            .text-[10px] {
              font-size: 0.6rem;
            }
            .w-8.h-8 {
              width: 1.5rem;
              height: 1.5rem;
            }
            .w-6.h-6 {
              width: 1.25rem;
              height: 1.25rem;
            }
            .px-3.py-1 {
              padding: 0.25rem 0.75rem;
            }
            .p-1 {
              padding: 0.25rem;
            }
          }
        `}
      </style>
    </>
  );
}

export default ChatbotAI;