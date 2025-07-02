import { Bus } from "../assets/images";
import React from "react";
import { useNavigate } from "react-router-dom";
import "./TripCard.css";

function TripCard({ trip, onSelect, hideIfDeparted = false }) {
  const navigate = useNavigate();

  // Xử lý departure_time để lấy ngày và giờ
  let formattedDate = "Chưa xác định";
  let formattedDepartureTime = "Không xác định";
  let formattedTimeCategory = "Không xác định";
  let hasDeparted = false; // Biến kiểm tra chuyến xe đã khởi hành chưa
  let departureDate = null;

  // Ghi log để kiểm tra dữ liệu trip
  console.log("TripCard Data:", {
    departure_time: trip.departure_time,
    availableSeats: trip.availableSeats,
  });
  console.log("Full Trip Data:", trip);

  if (trip.departure_time) {
    departureDate = new Date(trip.departure_time);
    if (!isNaN(departureDate.getTime())) {
      // Kiểm tra xem chuyến xe đã khởi hành chưa
      const currentDate = new Date();
      hasDeparted = departureDate < currentDate;
      
      // Trích xuất ngày: định dạng DD/MM/YYYY
      formattedDate = departureDate.toLocaleDateString("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      });

      // Trích xuất giờ: định dạng HH:mm
      formattedDepartureTime = departureDate.toLocaleTimeString("vi-VN", {
        hour: "2-digit",
        minute: "2-digit",
      });

      // Xác định khung giờ (time category): sáng, chiều, tối
      const hours = departureDate.getHours();
      if (hours >= 5 && hours < 12) {
        formattedTimeCategory = "Sáng";
      } else if (hours >= 12 && hours < 18) {
        formattedTimeCategory = "Chiều";
      } else {
        formattedTimeCategory = "Tối";
      }
    } else {
      console.error(
        `Invalid departure time format for trip ${trip.id}: ${trip.departure_time}`
      );
      formattedDate = "Ngày không hợp lệ";
      formattedDepartureTime = "Giờ không hợp lệ";
      formattedTimeCategory = "Không xác định";
    }
  } else {
    console.warn(`Departure time is missing for trip ${trip.id}`);
    formattedDate = "Ngày không hợp lệ";
    formattedDepartureTime = "Giờ không hợp lệ";
    formattedTimeCategory = "Không xác định";
  }

  // Hàm xử lý nút liên hệ
  const handleContact = () => {
    navigate("/contact");
  };
  // Kiểm tra xem chuyến xe có hết chỗ hay không
  const isFullyBooked =
    (typeof trip.availableSeats === "number" && trip.availableSeats === 0) ||
    (typeof trip.availableSeats === "string" && trip.availableSeats === "0");

  // Hàm xác định class cho time category
  const getTimeCategoryClass = (category) => {
    switch (category) {
      case "Sáng":
        return "time-category-morning";
      case "Chiều":
        return "time-category-afternoon";
      case "Tối":
        return "time-category-evening";
      default:
        return "text-gray-600";
    }
  };
  
  // Lý do không thể đặt vé: đã khởi hành hoặc hết chỗ
  // Kiểm tra xem có nên ẩn chuyến xe đã khởi hành không
  if (hideIfDeparted && hasDeparted) {
    return null; // Không hiển thị chuyến xe này nếu đã khởi hành và cần ẩn
  }
  return (
    <div
      className={`trip-card relative group 
        ${isFullyBooked ? "fully-booked" : ""} 
        ${hasDeparted ? "departed" : ""}
        bg-white p-4 rounded-lg shadow-sm hover:shadow-md 
        transition-all duration-300 ease-out transform hover:-translate-y-1
        border border-gray-100 hover:border-gray-200
        ${isFullyBooked || hasDeparted ? "glass-effect" : ""}
        ${hasDeparted ? "border-gray-300" : ""}
        backdrop-blur-sm`}
    >
      <div className="flex flex-col sm:flex-row sm:items-center gap-4">
        {/* Hình ảnh xe với hiệu ứng */}
        <div className="relative">
          <img
            src={trip.bus_image || Bus}
            alt="Xe khách"
            className="trip-image w-12 h-12 object-cover rounded-lg shadow-sm"
          />
        </div>

        {/* Thông tin chuyến xe với animation */}
        <div className="flex-1 space-y-2">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm">
            <div className="info-item flex items-center gap-2">
              <svg
                className="icon-subtle w-3 h-3 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                />
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
              <div>
                <span className="text-gray-500 text-xs">Đón:</span>
                <p className="font-medium text-gray-800 truncate">
                  {trip.pickup_location || "Chưa xác định"}
                </p>
              </div>
            </div>

            <div className="info-item flex items-center gap-2">
              <svg
                className="icon-subtle w-3 h-3 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                />
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
              <div>
                <span className="text-gray-500 text-xs">Trả:</span>
                <p className="font-medium text-gray-800 truncate">
                  {trip.dropoff_location || "Chưa xác định"}
                </p>
              </div>
            </div>

            <div className="info-item flex items-center gap-2">
              <svg
                className="icon-subtle w-3 h-3 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <div>
                <span className="text-gray-500 text-xs">Thời gian:</span>
                <p
                  className={`font-semibold text-sm ${getTimeCategoryClass(
                    formattedTimeCategory
                  )}`}
                >
                  {formattedDepartureTime}
                </p>
              </div>
            </div>

            <div className="info-item flex items-center gap-2">
              <svg
                className="icon-subtle w-3 h-3 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
              </svg>
              <div>
                <span className="text-gray-500 text-xs">Ngày:</span>
                <p className="font-medium text-gray-800 text-sm">
                  {formattedDate}
                </p>
              </div>
            </div>

            <div className="info-item flex items-center gap-2">
              <svg
                className="icon-subtle w-3 h-3 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"
                />
              </svg>
              <div>
                <span className="text-gray-500 text-xs">Giá:</span>
                <p className="price-highlight font-bold text-sm">
                  {(trip.price || 0).toLocaleString("vi-VN")} VND
                </p>
              </div>
            </div>

            <div className="info-item flex items-center gap-2">
              <svg
                className="icon-subtle w-3 h-3 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                />
              </svg>
              <div>
                <span className="text-gray-500 text-xs">Còn lại:</span>
                <div className="flex items-center gap-2">
                  <span
                    className={`font-bold text-sm ${
                      isFullyBooked
                        ? "text-red-600"
                        : parseInt(trip.availableSeats) <= 5
                        ? "text-orange-600"
                        : "text-green-600"
                    }`}
                  >
                    {typeof trip.availableSeats === "number" ||
                    (typeof trip.availableSeats === "string" &&
                      !isNaN(parseInt(trip.availableSeats)))
                      ? parseInt(trip.availableSeats)
                      : "?"}{" "}
                    chỗ
                  </span>
                  {isFullyBooked && (
                    <span className="status-badge px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded">
                      HẾT
                    </span>
                  )}
                  {!isFullyBooked &&
                    parseInt(trip.availableSeats) <= 5 &&
                    parseInt(trip.availableSeats) > 0 && (
                      <span className="status-badge px-2 py-0.5 bg-orange-500 text-white text-xs font-bold rounded animate-pulse">
                        SẮP HẾT
                      </span>
                    )}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Nút đặt vé hoặc nút liên hệ với hiệu ứng nhẹ */}
        <div className="flex flex-col items-center gap-2">
          {isFullyBooked || hasDeparted ? (
            <button
              onClick={handleContact}
              title={hasDeparted 
                ? "Chuyến xe đã khởi hành, vui lòng liên hệ để được hỗ trợ" 
                : "Chuyến xe đã hết chỗ, vui lòng liên hệ để được hỗ trợ"
              }
              className="contact-button group relative overflow-hidden px-4 py-2 
                text-white font-medium rounded-lg shadow-sm hover:shadow-md 
                transform hover:scale-105 transition-all duration-200
                focus:outline-none focus:ring-2 focus:ring-gray-300"
            >
              <span className="relative flex items-center gap-2 z-10 text-sm">
                <svg
                  className="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                  />
                </svg>
                Liên hệ
              </span>
            </button>
          ) : (
            <button
              onClick={() => onSelect(trip)}
              className="booking-button group bg-blue-600 hover:bg-blue-700 
                text-white font-medium rounded-lg px-4 py-2 shadow-sm hover:shadow-md 
                transform hover:scale-105 transition-all duration-200 
                focus:outline-none focus:ring-2 focus:ring-blue-300 flex items-center gap-2"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                className="h-4 w-4 group-hover:animate-bounce"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"
                  clipRule="evenodd"
                />
              </svg>
              <span className="relative z-10 text-sm">Đặt vé</span>
            </button>
          )}

          {/* Chỉ báo trạng thái nhỏ gọn */}
          <div className="flex items-center gap-1 text-xs text-gray-500">
            <div
              className={`w-1.5 h-1.5 rounded-full ${
                hasDeparted 
                  ? "bg-gray-500"
                  : isFullyBooked
                  ? "bg-red-500 animate-pulse"
                  : parseInt(trip.availableSeats) <= 5
                  ? "bg-orange-500 animate-pulse"
                  : "bg-green-500"
              }`}
            ></div>
            <span className="text-xs">
              {hasDeparted
                ? "Đã khởi hành"
                : isFullyBooked
                ? "Hết chỗ"
                : parseInt(trip.availableSeats) <= 5
                ? "Sắp hết"
                : "Còn chỗ"}
            </span>
          </div>
        </div>
      </div>
    </div>
  );
}

export default TripCard;
