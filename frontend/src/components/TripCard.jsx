import { Bus } from "../assets/images";
import React from "react";
import { useNavigate } from "react-router-dom";

function TripCard({ trip, onSelect }) {
  const navigate = useNavigate();

  // Xử lý departure_time để lấy ngày và giờ
  let formattedDate = "Chưa xác định";
  let formattedDepartureTime = "Không xác định";
  let formattedTimeCategory = "Không xác định";

  // Ghi log để kiểm tra dữ liệu trip
  console.log("TripCard Data:", {
    departure_time: trip.departure_time,
    availableSeats: trip.availableSeats,
  });
  console.log("Full Trip Data:", trip);

  if (trip.departure_time) {
    const departureDate = new Date(trip.departure_time);
    if (!isNaN(departureDate.getTime())) {
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

  return (
    <div className="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
      <div className="flex flex-col sm:flex-row sm:items-center gap-4">
        {/* Hình ảnh xe */}
        <img
          src={trip.bus_image || Bus}
          alt="Xe khách"
          className="w-16 h-16 object-cover rounded-lg"
        />
        {/* Thông tin chuyến xe */}
        <div className="flex-1">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <p className="text-gray-700">
              <span className="font-medium">Đón:</span>{" "}
              {trip.pickup_location || "Chưa xác định"}
            </p>
            <p className="text-gray-700">
              <span className="font-medium">Trả:</span>{" "}
              {trip.dropoff_location || "Chưa xác định"}
            </p>
            <p className="text-gray-700">
              <span className="font-medium">Thời gian:</span>{" "}
              {formattedDepartureTime} ({formattedTimeCategory})
            </p>
            <p className="text-gray-700">
              <span className="font-medium">Ngày:</span> {formattedDate}
            </p>
            <p className="text-gray-700">
              <span className="font-medium">Giá vé:</span>{" "}
              {(trip.price || 0).toLocaleString("vi-VN")} VND
            </p>
            <p className="text-gray-700">
              <span className="font-medium">Còn lại:</span>{" "}
              {typeof trip.availableSeats === "number" ||
              (typeof trip.availableSeats === "string" &&
                !isNaN(parseInt(trip.availableSeats)))
                ? parseInt(trip.availableSeats)
                : "Không xác định"}{" "}
              chỗ
              {isFullyBooked && (
                <span className="text-red-500 font-semibold ml-2">Hết chỗ</span>
              )}
            </p>
          </div>
        </div>
        {/* Nút đặt vé hoặc nút liên hệ */}
        {isFullyBooked ? (
          <button
            onClick={handleContact}
            title="Chuyến xe đã hết chỗ, vui lòng liên hệ để được hỗ trợ"
            className="relative inline-flex items-center justify-center p-0.5 mb-2 me-2
              overflow-hidden text-sm font-medium text-gray-900 rounded-full
              group bg-gradient-to-br from-purple-600 to-blue-500
              group-hover:from-purple-600 group-hover:to-blue-500 hover:text-white
              dark:text-black 
              focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800"
          >
            <span
              className="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900
                rounded-full group-hover:bg-transparent group-hover:dark:bg-transparent"
            >
              Liên hệ
            </span>
          </button>
        ) : (
          <button
            onClick={() => onSelect(trip)}
            className="mt-2 sm:mt-0 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-medium rounded-full px-5 py-2.5 hover:from-blue-600 hover:to-indigo-700 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300 flex items-center gap-2"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-5 w-5"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fillRule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000-16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"
                clipRule="evenodd"
              />
            </svg>
            Đặt vé
          </button>
        )}
      </div>
    </div>
  );
}

export default TripCard;
