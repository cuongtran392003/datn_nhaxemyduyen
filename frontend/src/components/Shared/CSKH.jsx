import React, { useState, useEffect, useRef } from "react";
import Logo from "../../assets/images/logo.png"; // Adjust the path as necessary
import "./ChatBot.css";
import { useNavigate } from "react-router-dom"; // Import useNavigate for navigation
import MessageFormatter from "./MessageFormatter"; // Import MessageFormatter component
import "./MessageFormatter.css"; // Import MessageFormatter styles

const CSKH = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [isMinimized, setIsMinimized] = useState(false);
  const [messages, setMessages] = useState([]);
  const [inputMessage, setInputMessage] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [isConnected, setIsConnected] = useState(false);
  const [hasNewMessage, setHasNewMessage] = useState(false);
  const messagesEndRef = useRef(null);
  const navigate=useNavigate();

  // API Configuration
  const API_BASE_URL = "http://localhost:8082";

  // Check API connection on component mount
  useEffect(() => {
    checkConnection();
  }, []);

  // Auto scroll to bottom when new messages arrive
  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  // Show notification when chat is closed and new message arrives
  useEffect(() => {
    if (!isOpen && messages.length > 0) {
      const lastMessage = messages[messages.length - 1];
      if (lastMessage.type === "bot") {
        setHasNewMessage(true);
      }
    }
  }, [messages, isOpen]);

  // Clear notification when chat is opened
  useEffect(() => {
    if (isOpen) {
      setHasNewMessage(false);
    }
  }, [isOpen]);

  const checkConnection = async () => {
    try {
      const response = await fetch(`${API_BASE_URL}/health`);
      if (response.ok) {
        setIsConnected(true);
        // Don't add welcome message immediately, wait for user to open chat
      }
    } catch (error) {
      setIsConnected(false);
    }
  };

  const initializeChat = () => {
    if (messages.length === 0) {
      addMessage(
        "Chatbot",
        "Xin chào! Tôi là chatbot của *Nhà xe Mỹ Duyên*.\n\nTôi có thể giúp bạn:\n- Tìm hiểu về các tuyến xe\n- Đặt vé xe\n- Xem thông tin giá vé\n- Cung cấp thông tin dịch vụ\n\nBạn cần hỗ trợ gì? 🚌",
        "bot"
      );
    }
  };

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  const addMessage = (sender, text, type = "user", metadata = null) => {
    const newMessage = {
      id: Date.now(),
      sender,
      text,
      type,
      metadata,
      timestamp: new Date(),
    };
    setMessages((prev) => [...prev, newMessage]);
  };

  const sendMessage = async () => {
    if (!inputMessage.trim() || isLoading) return;

    const userMessage = inputMessage.trim();
    setInputMessage("");

    // Add user message
    addMessage("Bạn", userMessage, "user");
    setIsLoading(true);

    try {
      const response = await fetch(`${API_BASE_URL}/api/chat`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ message: userMessage }),
      });

      const data = await response.json();

      if (data.success) {
        addMessage("Chatbot", data.message, "bot", data.metadata);
      } else {
        addMessage(
          "Chatbot",
          data.message || "Xin lỗi, đã xảy ra lỗi.",
          "error"
        );
      }
    } catch (error) {
      console.error("Chat API Error:", error);
      addMessage(
        "System",
        "*Lỗi kết nối với chatbot*\n\nKhông thể xử lý yêu cầu của bạn lúc này.\n\nVui lòng thử lại sau hoặc liên hệ bộ phận hỗ trợ.",
        "error"
      );
    } finally {
      setIsLoading(false);
    }
  };

  const handleKeyPress = (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  };

  const formatTimestamp = (timestamp) => {
    return timestamp.toLocaleTimeString("vi-VN", {
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  const toggleChat = () => {
    if (!isOpen) {
      setIsOpen(true);
      initializeChat();
    } else {
      setIsOpen(false);
    }
    setIsMinimized(false);
  };

  const minimizeChat = () => {
    setIsMinimized(!isMinimized);
  };

  const closeChat = () => {
    setIsOpen(false);
    setIsMinimized(false);
  };

  return (
    <div className="chatbot-widget">
      {/* Floating Chat Toggle Button */}      
      {!isOpen && (
        <button
          className="chat-toggle-btn"
          onClick={toggleChat}
          title="Mở chat hỗ trợ"
        >
          <svg viewBox="0 0 24 24" fill="currentColor" className="chat-icon">
            <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/>
          </svg>
          {hasNewMessage && (
            <div className="notification-badge">
              <svg viewBox="0 0 24 24" fill="currentColor" className="notification-icon">
                <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
              </svg>
            </div>
          )}
        </button>
      )}

      {/* Chat Container */}
      {isOpen && (
        <div
          className={`chatbot-container ${isOpen ? "open" : "closed"} ${
            isMinimized ? "minimized" : ""
          }`}
        >
          {/* Chat Header */}          <div className="chatbot-header">
            <div className="header-left">
              {isConnected && <div className="online-pulse"></div>}
              <div className="brand-icon">
                <img src={Logo} alt="" />
              </div>
              <div className="header-info">
                <h3>Nhà xe Mỹ Duyên</h3>
                <div className="subtitle">Hỗ trợ khách hàng</div>
              </div>
            </div>
            <div className="header-controls">
              <div
                className={`connection-status ${
                  isConnected ? "connected" : "disconnected"
                }`}
              >
                <div className="status-dot"></div>
                <span>{isConnected ? "Online" : "Offline"}</span>
              </div>
              <button
                className="minimize-btn"
                onClick={minimizeChat}
                title={isMinimized ? "Mở rộng" : "Thu nhỏ"}
              >
                <svg viewBox="0 0 24 24" fill="currentColor">
                  {isMinimized ? (
                    <path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z"/>
                  ) : (
                    <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
                  )}
                </svg>
              </button>
              <button
                className="close-btn"
                onClick={closeChat}
                title="Đóng chat"
              >
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
              </button>
            </div>
          </div>

          {/* Messages Area */}
          {!isMinimized && (
            <>
              <div className="chatbot-messages">                
                {messages.length === 0 ? (
                  <div className="welcome-message welcome-fade-in">
                    <div className="welcome-icon">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                      </svg>
                    </div>
                    <h4>Chào mừng bạn đến với Nhà xe Mỹ Duyên!</h4>
                    <p>Tôi là trợ lý ảo, sẵn sàng hỗ trợ bạn:</p>
                    <ul className="welcome-features">
                      <li><span>🎫</span> Tìm kiếm và đặt vé xe</li>
                      <li><span>💰</span> Tra cứu giá vé</li>
                      <li><span>📅</span> Xem lịch trình các tuyến</li>
                      <li><span>📞</span> Thông tin liên hệ</li>
                    </ul>
                  </div>
                ) : (
                  messages.map((message) => (
                    <div key={message.id} className={`message ${message.type}`}>
                      <div className="message-header">
                        <span className="sender">{message.sender}</span>
                        <span className="timestamp">
                          {formatTimestamp(message.timestamp)}
                        </span>
                      </div>
                      <div className="message-content">
                        {message.type === "bot" || message.type === "error" ? (
                          <MessageFormatter text={message.text} />
                        ) : (
                          <p>{message.text}</p>
                        )}
                        {message.type === "user" && (
                          <div className="message-status">
                            <svg viewBox="0 0 24 24" fill="currentColor" className="read-icon">
                              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                          </div>
                        )}
                      </div>
                    </div>
                  ))
                )}

                {/* Loading Indicator */}
                {isLoading && (
                  <div className="message bot loading">
                    <div className="message-header">
                      <span className="sender">Chatbot</span>
                    </div>
                    <div className="message-content">
                      <div className="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                      </div>
                    </div>
                  </div>
                )}

                <div ref={messagesEndRef} />
              </div>

              {/* Input Area */}
              <div className="chatbot-input">
                <div className="input-group">
                  <textarea
                    value={inputMessage}
                    onChange={(e) => setInputMessage(e.target.value)}
                    onKeyPress={handleKeyPress}
                    placeholder="Nhập tin nhắn của bạn..."
                    disabled={isLoading || !isConnected}
                    rows={1}                    style={{
                      height: "auto",
                      minHeight: "36px",
                      maxHeight: "80px",
                    }}                    onInput={(e) => {
                      e.target.style.height = "auto";
                      e.target.style.height =
                        Math.min(e.target.scrollHeight, 80) + "px";
                    }}
                  />                    <button
                      onClick={sendMessage}
                      disabled={isLoading || !inputMessage.trim() || !isConnected}
                      className="send-button"
                      title="Gửi tin nhắn"
                    >
                      {isLoading ? (
                        <svg viewBox="0 0 24 24" fill="currentColor" className="loading-icon">
                          <path d="M12,4V2A10,10 0 0,0 2,12H4A8,8 0 0,1 12,4Z" />
                        </svg>
                      ) : (
                        <svg viewBox="0 0 24 24" fill="currentColor" className="send-icon">
                          <path d="M2,21L23,12L2,3V10L17,12L2,14V21Z"/>
                        </svg>
                      )}
                    </button>
                </div>

                {/* Quick Actions */}                
                <div className="quick-actions">
                  <button
                    onClick={() => {
                      // Thêm thông báo trước khi chuyển trang
                      addMessage(
                        "Chatbot", 
                        "*Đang kiểm tra các chuyến xe hiện có*\n\nChuẩn bị chuyển bạn đến trang tìm kiếm...\nVui lòng đợi trong giây lát!",
                        "bot"
                      );
                      
                      // Kiểm tra kết nối API trước khi chuyển trang
                      setIsLoading(true);
                      
                      // Tạo một ngày mặc định (hôm nay)
                      const today = new Date();
                      const formattedDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                      
                      fetch("http://localhost:8000/wp-json/nhaxemyduyen/v1/trips")
                        .then(response => {
                          if (!response.ok) {
                            throw new Error("Không thể kết nối đến máy chủ");
                          }
                          return response.json();
                        })
                        .then(data => {
                          setIsLoading(false);
                          if (data && data.length > 0) {
                            // Có dữ liệu chuyến xe, chuyển đến trang tìm kiếm
                            navigate("/search", {
                              state: {
                                reset: true,
                                fromChat: true,
                                date: formattedDate, // Truyền ngày hôm nay làm mặc định
                                initialTrips: data, // Truyền luôn dữ liệu chuyến xe đã lấy được
                                skipPolling: true // Cờ để ngăn polling làm mất dữ liệu
                              }
                            });
                          } else {
                            // Không có chuyến xe nào, thông báo cho người dùng
                            addMessage(
                              "Chatbot", 
                              "*Thông báo:* Hiện không có chuyến xe nào khả dụng.\n\nVui lòng thử lại sau hoặc liên hệ hotline *1900 1111 2222* để được hỗ trợ trực tiếp.",
                              "bot"
                            );
                          }
                        })
                        .catch(error => {
                          setIsLoading(false);
                          addMessage(
                            "Chatbot", 
                            "*Lỗi kết nối!*\n\nKhông thể kết nối đến máy chủ để lấy thông tin chuyến xe.\n\nVui lòng:\n- Kiểm tra kết nối mạng\n- Thử lại sau ít phút\n- Liên hệ hỗ trợ nếu lỗi vẫn tiếp tục",
                            "error"
                          );
                          console.error("API Error:", error);
                        });
                    }}
                    disabled={isLoading}
                    className="quick-action"
                  >
                    <svg viewBox="0 0 24 24" fill="currentColor" className="action-icon">
                      <path d="M20,8H4V6H20M20,18H4V12H20M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C2,4.89 21.1,4 20,4Z"/>
                    </svg>
                    Đặt vé
                  </button>
                  <button
                    onClick={() => setInputMessage("Giá vé bao nhiêu?")}
                    disabled={isLoading}
                    className="quick-action"
                  >
                    <svg viewBox="0 0 24 24" fill="currentColor" className="action-icon">
                      <path d="M7,15H9C9,16.08 10.37,17 12,17C13.63,17 15,16.08 15,15C15,13.9 13.96,13.5 11.76,12.97C9.64,12.44 7,11.78 7,9C7,7.21 8.47,5.69 10.5,5.18V3H13.5V5.18C15.53,5.69 17,7.21 17,9H15C15,7.92 13.63,7 12,7C10.37,7 9,7.92 9,9C9,10.1 10.04,10.5 12.24,11.03C14.36,11.56 17,12.22 17,15C17,16.79 15.53,18.31 13.5,18.82V21H10.5V18.82C8.47,18.31 7,16.79 7,15Z"/>
                    </svg>
                    Giá vé
                  </button>
                  <button
                    onClick={() => setInputMessage("Lịch trình các tuyến xe")}
                    disabled={isLoading}
                    className="quick-action"
                  >
                    <svg viewBox="0 0 24 24" fill="currentColor" className="action-icon">
                      <path d="M19,3H18V1H16V3H8V1H6V3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3M19,19H5V8H19V19Z"/>
                    </svg>
                    Lịch trình
                  </button>
                  <button
                    onClick={() => setInputMessage("Thông tin liên hệ")}
                    disabled={isLoading}
                    className="quick-action"
                  >
                    <svg viewBox="0 0 24 24" fill="currentColor" className="action-icon">
                      <path d="M6.62,10.79C8.06,13.62 10.38,15.94 13.21,17.38L15.41,15.18C15.69,14.9 16.08,14.82 16.43,14.93C17.55,15.3 18.75,15.5 20,15.5A1,1 0 0,1 21,16.5V20A1,1 0 0,1 20,21A17,17 0 0,1 3,4A1,1 0 0,1 4,3H7.5A1,1 0 0,1 8.5,4C8.5,5.25 8.7,6.45 9.07,7.57C9.18,7.92 9.1,8.31 8.82,8.59L6.62,10.79Z"/>
                    </svg>
                    Liên hệ
                  </button>
                </div>
              </div>
            </>
          )}
        </div>
      )}
    </div>
  );
};

export default CSKH;
