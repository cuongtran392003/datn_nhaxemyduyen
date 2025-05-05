import { useLocation } from "react-router-dom";
import { useEffect, useState, useMemo } from "react";
import TripCard from "../TripCard";
import SeatSelection from "../SeatSelection";
import route_no_schedule_2 from "../../assets/images/route-no-schedule-2.png";
function SearchResults() {
  const location = useLocation();
  const [trips, setTrips] = useState([]);
  const [filters, setFilters] = useState({
    time: "",
    price: "",
    seats: "",
    pickup: "",
    dropoff: "",
  });
  const [sortOption, setSortOption] = useState("");
  const [expandedTripId, setExpandedTripId] = useState(null);

  const {
    trips: initialTrips,
    departure,
    destination,
    date,
  } = location.state || {};

  // Hàm định dạng ngày thành dạng ngày/tháng/năm
  const formatDate = (dateString) => {
    if (!dateString) return "Không xác định";
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return "Không xác định";
    return date.toLocaleDateString("vi-VN", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });
  };

  useEffect(() => {
    if (initialTrips && Array.isArray(initialTrips)) {
      const formattedTrips = initialTrips.map((trip) => {
        const departureTime = trip.departure_time
          ? new Date(trip.departure_time)
          : null;
        let timeCategory = "Không xác định";
        let formattedDepartureTime = "Không xác định";

        if (departureTime && !isNaN(departureTime.getTime())) {
          const hours = departureTime.getHours();
          if (hours >= 5 && hours < 12) timeCategory = "Morning";
          else if (hours >= 12 && hours < 17) timeCategory = "Afternoon";
          else timeCategory = "Evening";

          formattedDepartureTime = departureTime.toLocaleTimeString("vi-VN", {
            hour: "2-digit",
            minute: "2-digit",
          });
        } else {
          console.warn(
            `Invalid departure time for trip ${trip.trip_id}: ${trip.departure_time}`
          );
        }

        return {
          id: trip.trip_id || `trip-${Math.random()}`, // Fallback ID
          pickup_location: trip.pickup_location || "Không xác định",
          dropoff_location: trip.dropoff_location || "Không xác định",
          date: formatDate(date), // Định dạng lại date thành ngày/tháng/năm
          time: timeCategory,
          price: parseFloat(trip.price) || 0,
          availableSeats: parseInt(trip.available_seats) || 0,
          bus_image: trip.bus_image || "",
          company: "Nhà Xe Mỹ Duyên",
          departure_time: trip.departure_time || "",
        };
      });
      setTrips(formattedTrips);
    } else {
      setTrips([]);
      console.warn("No valid initialTrips provided in location.state");
    }
  }, [initialTrips, date]);

  const filteredTrips = useMemo(() => {
    let updatedTrips = [...trips];

    // Apply filters
    if (filters.time) {
      updatedTrips = updatedTrips.filter((trip) => trip.time === filters.time);
    }
    if (
      filters.price &&
      !isNaN(parseInt(filters.price)) &&
      parseInt(filters.price) >= 0
    ) {
      updatedTrips = updatedTrips.filter(
        (trip) => trip.price <= parseInt(filters.price)
      );
    }
    if (
      filters.seats &&
      !isNaN(parseInt(filters.seats)) &&
      parseInt(filters.seats) >= 0
    ) {
      updatedTrips = updatedTrips.filter(
        (trip) => trip.availableSeats >= parseInt(filters.seats)
      );
    }
    if (filters.pickup) {
      updatedTrips = updatedTrips.filter((trip) =>
        trip.pickup_location
          .toLowerCase()
          .includes(filters.pickup.toLowerCase())
      );
    }
    if (filters.dropoff) {
      updatedTrips = updatedTrips.filter((trip) =>
        trip.dropoff_location
          .toLowerCase()
          .includes(filters.dropoff.toLowerCase())
      );
    }

    // Apply sorting
    if (sortOption) {
      updatedTrips.sort((a, b) => {
        if (sortOption === "earliest" || sortOption === "latest") {
          const timeA = a.departure_time
            ? new Date(a.departure_time).getTime()
            : Infinity;
          const timeB = b.departure_time
            ? new Date(b.departure_time).getTime()
            : Infinity;
          if (isNaN(timeA) && isNaN(timeB)) return 0;
          if (isNaN(timeA)) return 1;
          if (isNaN(timeB)) return -1;
          return sortOption === "earliest" ? timeA - timeB : timeB - timeA;
        } else if (sortOption === "price_asc") {
          return a.price - b.price;
        } else if (sortOption === "price_desc") {
          return b.price - a.price;
        }
        return 0;
      });
    }

    return updatedTrips;
  }, [filters, sortOption, trips]);

  // Extract unique pickup and dropoff locations
  const uniquePickups = [...new Set(trips.map((trip) => trip.pickup_location))];
  const uniqueDropoffs = [
    ...new Set(trips.map((trip) => trip.dropoff_location)),
  ];

  // Fallback rendering if location.state is missing
  if (!initialTrips || !departure || !destination || !date) {
    return (
      <div className="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
        <div className="max-w-7xl mx-auto">
          <div className="bg-white p-8 rounded-xl shadow-md flex items-center justify-center gap-3 text-gray-600 text-lg">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-8 w-8"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fillRule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1zm-1 4a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1z"
                clipRule="evenodd"
              />
            </svg>
            Không có dữ liệu tìm kiếm. Vui lòng thử lại.
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-7xl mx-auto">
        <div className="flex flex-col lg:flex-row gap-8">
          {/* Sidebar: Filters */}
          <div className="lg:w-1/4 bg-white rounded-xl shadow-lg p-6 sticky top-8">
            <h2 className="text-xl font-semibold text-gray-800 mb-6 flex items-center gap-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                className="h-5 w-5 text-blue-600"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fillRule="evenodd"
                  d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                  clipRule="evenodd"
                />
              </svg>
              Lọc Chuyến Xe
            </h2>
            <div className="space-y-6">
              {/* Thời gian */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Giờ Đi
                </label>
                <select
                  value={filters.time}
                  onChange={(e) =>
                    setFilters((prev) => ({ ...prev, time: e.target.value }))
                  }
                  className="block w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                >
                  <option value="">Tất cả</option>
                  <option value="Morning">Sáng (5:00 - 11:59)</option>
                  <option value="Afternoon">Chiều (12:00 - 16:59)</option>
                  <option value="Evening">Tối (17:00 - 23:59)</option>
                </select>
              </div>
              {/* Giá vé */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Giá Tối Đa (VND)
                </label>
                <input
                  type="number"
                  value={filters.price}
                  onChange={(e) =>
                    setFilters((prev) => ({ ...prev, price: e.target.value }))
                  }
                  min="0"
                  className="block w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                  placeholder="Nhập giá tối đa"
                />
              </div>
              {/* Số chỗ trống */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Số Chỗ Trống Tối Thiểu
                </label>
                <input
                  type="number"
                  value={filters.seats}
                  onChange={(e) =>
                    setFilters((prev) => ({ ...prev, seats: e.target.value }))
                  }
                  min="0"
                  className="block w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                  placeholder="Nhập số chỗ"
                />
              </div>
              {/* Điểm đón */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Điểm Đón
                </label>
                <select
                  value={filters.pickup}
                  onChange={(e) =>
                    setFilters((prev) => ({ ...prev, pickup: e.target.value }))
                  }
                  className="block w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                >
                  <option value="">Tất cả</option>
                  {uniquePickups.map((pickup) => (
                    <option key={pickup} value={pickup}>
                      {pickup}
                    </option>
                  ))}
                </select>
              </div>
              {/* Điểm trả */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Điểm Trả
                </label>
                <select
                  value={filters.dropoff}
                  onChange={(e) =>
                    setFilters((prev) => ({ ...prev, dropoff: e.target.value }))
                  }
                  className="block w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                >
                  <option value="">Tất cả</option>
                  {uniqueDropoffs.map((dropoff) => (
                    <option key={dropoff} value={dropoff}>
                      {dropoff}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          </div>

          {/* Main Content: Search Results */}
          <div className="lg:w-3/4">
            {/* Tiêu đề và Sắp xếp */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
              <h1 className="text-3xl font-bold text-gray-800 flex items-center gap-2">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-6 w-6 text-blue-600"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fillRule="evenodd"
                    d="M10 2a8 8 0 100 16 8 8 0 000-16zM8 7a1 1 0 011-1h2a1 1 0 011 1v2h2a1 1 0 110 2h-2v2a1 1 0 11-2 0v-2H7a1 1 0 110-2h2V7z"
                    clipRule="evenodd"
                  />
                </svg>
                Kết quả tìm kiếm: {departure} → {destination} | {formatDate(date)}
              </h1>
              <div className="mt-4 sm:mt-0 sm:ml-4">
                <label className="block text-sm font-medium text-gray-700 mb-2 sm:mb-0 sm:inline-block sm:mr-2">
                  Sắp xếp theo
                </label>
                <select
                  value={sortOption}
                  onChange={(e) => setSortOption(e.target.value)}
                  className="block w-full sm:w-auto border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                >
                  <option value="">Mặc định</option>
                  <option value="earliest">Giờ đi sớm nhất</option>
                  <option value="latest">Giờ đi muộn nhất</option>
                  <option value="price_asc">Giá tăng dần</option>
                  <option value="price_desc">Giá giảm dần</option>
                </select>
              </div>
            </div>

            {/* Danh sách chuyến xe */}
            {filteredTrips.length === 0 ? (
              <div className="bg-white p-8 rounded-xl shadow-md flex flex-col items-center justify-center 
              gap-3 text-gray-600 text-lg text-center leading-10">
                 <span>
                    Không tìm thấy chuyến xe nào từ {departure} đến {destination} 
                    vào ngày {formatDate(date)}.
                    <br/> Hiện tại, hệ thống chưa tìm thấy chuyến đi theo yêu của khách hàng
                    ,quý khách có thể thử.
                    <span className="text-bluecustom">Hoặc liên hệ hotline: 1900 1111 2222</span>
                  </span>
                  <img src={route_no_schedule_2} alt="không có chuyến xe nào"/>
              </div>
            ) : (
              <ul className="space-y-6">
                {filteredTrips.map((trip) => (
                  <li
                    key={trip.id}
                    className="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300"
                  >
                    <TripCard
                      trip={trip}
                      onSelect={() =>
                        setExpandedTripId(
                          expandedTripId === trip.id ? null : trip.id
                        )
                      }
                    />
                    {expandedTripId === trip.id && (
                      <div className="mt-6 border-t border-gray-200 pt-6">
                        <SeatSelection
                          selectedTrip={trip}
                          onBack={() => setExpandedTripId(null)}
                        />
                      </div>
                    )}
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

export default SearchResults;