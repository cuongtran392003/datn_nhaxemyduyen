import { useLocation } from "react-router-dom";
import { useEffect, useState, useMemo } from "react";
import { Link } from "react-router-dom";
import TripCard from "../TripCard";
import SeatSelection from "../SeatSelection";
import route_no_schedule_2 from "../../assets/images/route-no-schedule-2.png";
import SearchForm from "../Home/components/SearchForm";
import tripService from "../../service/tripService";



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
  const [isFilterOpen, setIsFilterOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  const { trips: initialTrips, departure, destination, date } = location.state || {};

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

  const [currentTime, setCurrentTime] = useState(new Date());
  useEffect(() => {
    const timer = setInterval(() => setCurrentTime(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    if (initialTrips && Array.isArray(initialTrips)) {
      setIsLoading(true);
      const formattedTrips = initialTrips.map((trip) => {
        const departureTime = trip.departure_time ? new Date(trip.departure_time) : null;
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
          console.warn(`Invalid departure time for trip ${trip.trip_id}: ${trip.departure_time}`);
        }

        return {
          id: trip.trip_id || `trip-${Math.random()}`,
          pickup_location: trip.pickup_location || "Không xác định",
          dropoff_location: trip.dropoff_location || "Không xác định",
          date: formatDate(date),
          time: timeCategory,
          price: parseFloat(trip.price) || 0,
          availableSeats: parseInt(trip.available_seats) || 0,
          bus_image: trip.bus_image || "",
          company: "Nhà Xe Mỹ Duyên",
          departure_time: trip.departure_time || "",
        };
      });
      setTrips(formattedTrips);
      setIsLoading(false);
    } else {
      setTrips([]);
      setIsLoading(false);
      console.warn("No valid initialTrips provided in location.state");
    }
  }, [initialTrips, date]);

  const filteredTrips = useMemo(() => {
    setIsLoading(true);
    let updatedTrips = [...trips];

    if (filters.time) {
      updatedTrips = updatedTrips.filter((trip) => trip.time === filters.time);
    }
    if (filters.price && !isNaN(parseInt(filters.price)) && parseInt(filters.price) >= 0) {
      updatedTrips = updatedTrips.filter((trip) => trip.price <= parseInt(filters.price));
    }
    if (filters.seats && !isNaN(parseInt(filters.seats)) && parseInt(filters.seats) >= 0) {
      updatedTrips = updatedTrips.filter((trip) => trip.availableSeats >= parseInt(filters.seats));
    }
    if (filters.pickup) {
      updatedTrips = updatedTrips.filter((trip) =>
        trip.pickup_location.toLowerCase().includes(filters.pickup.toLowerCase())
      );
    }
    if (filters.dropoff) {
      updatedTrips = updatedTrips.filter((trip) =>
        trip.dropoff_location.toLowerCase().includes(filters.dropoff.toLowerCase())
      );
    }

    if (sortOption) {
      updatedTrips.sort((a, b) => {
        if (sortOption === "earliest" || sortOption === "latest") {
          const timeA = a.departure_time ? new Date(a.departure_time).getTime() : Infinity;
          const timeB = b.departure_time ? new Date(b.departure_time).getTime() : Infinity;
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

    setIsLoading(false);
    return updatedTrips;
  }, [filters, sortOption, trips]);

  const uniquePickups = [...new Set(trips.map((trip) => trip.pickup_location))];
  const uniqueDropoffs = [...new Set(trips.map((trip) => trip.dropoff_location))];

  const handleClearFilters = () => {
    setFilters({
      time: "",
      price: "",
      seats: "",
      pickup: "",
      dropoff: "",
    });
    setSortOption("");
  };

  if (!initialTrips || !departure || !destination || !date) {
    return (
      <div>
        
        <div className="m-10 p-10 shadow rounded-xl">
          <SearchForm />
        </div>
        <div>
          {isLoading && <p>Đang tải...</p>}
          {trips.length > 0 ? (
            <ul className="m-10">
              {trips.map((trip, index) => (
                <li key={index} className="p-4 border-b">
                  {/* Adjust based on your trip data structure */}
                  <p>Chuyến xe: {trip.name || `Chuyến ${index + 1}`}</p>
                  {/* Add more trip details as needed */}
                </li>
              ))}
            </ul>
          ) : (
            !isLoading && <p>Không có chuyến xe nào.</p>
          )}
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-100">
      {/* Search Form Section - Positioned at the very top */}
      <section className="shadow-lg z-30 relative mx-48 rounded-xl py-8 px-10 sm:px-6 lg:px-8">
          <SearchForm />
      </section>

      <main className="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col lg:flex-row gap-8">
          {/* Filter Sidebar */}
          <aside
            className={`fixed inset-y-0 left-0 w-80 bg-white shadow-xl rounded-r-2xl transform transition-transform duration-300 ease-in-out z-30 lg:static lg:w-1/4 lg:transform-none lg:rounded-2xl lg:p-6 ${
              isFilterOpen ? "translate-x-0" : "-translate-x-full"
            }`}
            aria-label="Bộ lọc chuyến xe"
          >
            <div className="flex items-center justify-between p-4 lg:p-0 border-b border-gray-200 lg:border-0">
              <h2 className="text-xl font-semibold text-gray-800 flex items-center gap-2">
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
              <button
                onClick={() => setIsFilterOpen(false)}
                className="lg:hidden text-gray-600 hover:text-gray-800 transition-colors duration-200"
                aria-label="Đóng bộ lọc"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-6 w-6"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
            <div className="p-4 lg:p-0 space-y-5">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1.5">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="h-4 w-4 text-gray-500"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path
                      fillRule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                      clipRule="evenodd"
                    />
                  </svg>
                  Giờ Đi
                </label>
                <select
                  value={filters.time}
                  onChange={(e) => setFilters((prev) => ({ ...prev, time: e.target.value }))}
                  className="w-full border-gray-300 rounded-md py-2.5 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all duration-200 bg-white shadow-sm"
                  aria-label="Chọn khung giờ"
                >
                  <option value="">Tất cả</option>
                  <option value="Morning">Sáng (5:00 - 11:59)</option>
                  <option value="Afternoon">Chiều (12:00 - 16:59)</option>
                  <option value="Evening">Tối (17:00 - 23:59)</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1.5">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="h-4 w-4 text-gray-500"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path d="M8 7a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1zm0 4a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1zm-5 5h14a1 1 0 001-1V5a1 1 0 00-1-1H3a1 1 0 00-1 1v10a1 1 0 001 1z" />
                  </svg>
                  Giá Tối Đa (VND)
                </label>
                <input
                  type="number"
                  value={filters.price}
                  onChange={(e) => setFilters((prev) => ({ ...prev, price: e.target.value }))}
                  min="0"
                  className="w-full border-gray-300 rounded-md py-2.5 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all duration-200 bg-white shadow-sm"
                  placeholder="Nhập giá tối đa"
                  aria-label="Giá tối đa"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1.5">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="h-4 w-4 text-gray-500"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" />
                  </svg>
                  Số Chỗ Trống Tối Thiểu
                </label>
                <input
                  type="number"
                  value={filters.seats}
                  onChange={(e) => setFilters((prev) => ({ ...prev, seats: e.target.value }))}
                  min="0"
                  className="w-full border-gray-300 rounded-md py-2.5 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all duration-200 bg-white shadow-sm"
                  placeholder="Nhập số chỗ"
                  aria-label="Số chỗ trống tối thiểu"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1.5">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="h-4 w-4 text-gray-500"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path
                      fillRule="evenodd"
                      d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                      clipRule="evenodd"
                    />
                  </svg>
                  Điểm Đón
                </label>
                <select
                  value={filters.pickup}
                  onChange={(e) => setFilters((prev) => ({ ...prev, pickup: e.target.value }))}
                  className="w-full border-gray-300 rounded-md py-2.5 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all duration-200 bg-white shadow-sm"
                  aria-label="Chọn điểm đón"
                >
                  <option value="">Tất cả</option>
                  {uniquePickups.map((pickup) => (
                    <option key={pickup} value={pickup}>
                      {pickup}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1.5">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="h-4 w-4 text-gray-500"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path
                      fillRule="evenodd"
                      d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                      clipRule="evenodd"
                    />
                  </svg>
                  Điểm Trả
                </label>
                <select
                  value={filters.dropoff}
                  onChange={(e) => setFilters((prev) => ({ ...prev, dropoff: e.target.value }))}
                  className="w-full border-gray-300 rounded-md py-2.5 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all duration-200 bg-white shadow-sm"
                  aria-label="Chọn điểm trả"
                >
                  <option value="">Tất cả</option>
                  {uniqueDropoffs.map((dropoff) => (
                    <option key={dropoff} value={dropoff}>
                      {dropoff}
                    </option>
                  ))}
                </select>
              </div>
              <button
                onClick={handleClearFilters}
                className="w-full bg-gray-100 text-gray-700 py-2.5 rounded-md hover:bg-gray-200 transition-all duration-200 font-medium shadow-sm"
                aria-label="Xóa tất cả bộ lọc"
              >
                Xóa Bộ Lọc
              </button>
            </div>
          </aside>

          {/* Main Content */}
          <section className="lg:w-3/4">
            {/* Mobile Filter Toggle */}
            <div className="lg:hidden mb-6">
              <button
                onClick={() => setIsFilterOpen(true)}
                className="w-full bg-blue-600 text-white py-2.5 px-4 rounded-md flex items-center justify-center gap-2 hover:bg-blue-700 transition-all duration-200 font-medium shadow-sm"
                aria-label="Mở bộ lọc"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-5 w-5"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fillRule="evenodd"
                    d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                    clipRule="evenodd"
                  />
                </svg>
                Mở Bộ Lọc
              </button>
            </div>

            {/* Search Results Header */}
            <div className="bg-white rounded-xl shadow-lg p-6 mb-6">
              <h1 className="text-2xl font-bold text-gray-800 mb-3">
                Kết quả tìm kiếm: {departure} → {destination} | {formatDate(date)}
              </h1>
              <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <p className="text-gray-500 text-sm mb-3 sm:mb-0">
                  {filteredTrips.length} chuyến xe được tìm thấy
                </p>
                <div className="flex items-center gap-3">
                  <label className="text-sm font-medium text-gray-700">Sắp xếp theo</label>
                  <select
                    value={sortOption}
                    onChange={(e) => setSortOption(e.target.value)}
                    className="w-full sm:w-48 border-gray-300 rounded-md py-2.5 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all duration-200 bg-white shadow-sm"
                    aria-label="Sắp xếp kết quả"
                  >
                    <option value="">Mặc định</option>
                    <option value="earliest">Giờ đi sớm nhất</option>
                    <option value="latest">Giờ đi muộn nhất</option>
                    <option value="price_asc">Giá tăng dần</option>
                    <option value="price_desc">Giá giảm dần</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Trip List */}
            {isLoading ? (
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {[...Array(4)].map((_, index) => (
                  <div
                    key={index}
                    className="bg-white rounded-xl shadow-lg p-6 animate-pulse"
                  >
                    <div className="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                    <div className="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                    <div className="h-4 bg-gray-200 rounded w-1/4"></div>
                  </div>
                ))}
              </div>
            ) : filteredTrips.length === 0 ? (
              <section className="bg-white rounded-xl shadow-lg p-8 text-center">
                <img
                  src={route_no_schedule_2}
                  alt="Không có chuyến xe nào"
                  className="max-w-full h-auto max-h-48 mx-auto mb-6"
                />
                <p className="text-gray-500 text-base mb-6 leading-relaxed">
                  Không tìm thấy chuyến xe nào từ <strong>{departure}</strong> đến{" "}
                  <strong>{destination}</strong> vào ngày {formatDate(date)}.
                  <br />
                  Vui lòng thử tìm kiếm với các tiêu chí khác hoặc liên hệ hỗ trợ.
                </p>
                <a
                  href="tel:190011112222"
                  className="inline-block bg-blue-600 text-white px-6 py-2.5 rounded-md hover:bg-blue-700 transition-all duration-200 font-medium shadow-sm"
                  aria-label="Liên hệ hotline"
                >
                  Gọi Hotline: 1900 1111 2222
                </a>
              </section>
            ) : (
              <ul className="flex flex-col md:flex-col gap-6">
                {filteredTrips.map((trip) => (
                  <li
                    key={trip.id}
                    className="bg-white rounded-xl shadow-lg hover:shadow-xl 
                    hover:scale-[1.02] transition-all duration-300 overflow-hidden"
                  >
                    <TripCard
                      trip={trip}
                      onSelect={() =>
                        setExpandedTripId(expandedTripId === trip.id ? null : trip.id)
                      }
                    />
                    {expandedTripId === trip.id && (
                      <div
                        className={`transition-all duration-300 ease-in-out ${
                          expandedTripId === trip.id ? "block" : "hidden"
                        }`}
                      >
                        <div className="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50 lg:static lg:bg-transparent lg:p-4 lg:border-t lg:border-gray-200">
                          <div className="bg-white p-6 rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto lg:max-w-full lg:p-0 shadow-2xl lg:shadow-none">
                            <div className="flex justify-end mb-4 lg:hidden">
                              <button
                                onClick={() => setExpandedTripId(null)}
                                className="text-gray-600 hover:text-gray-800 transition-colors duration-200"
                                aria-label="Đóng chọn ghế"
                              >
                                <svg
                                  xmlns="http://www.w3.org/2000/svg"
                                  className="h-6 w-6"
                                  fill="none"
                                  viewBox="0 0 24 24"
                                  stroke="currentColor"
                                >
                                  <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M6 18L18 6M6 6l12 12"
                                  />
                                </svg>
                              </button>
                            </div>
                            <SeatSelection
                              selectedTrip={trip}
                              onBack={() => setExpandedTripId(null)}
                            />
                          </div>
                        </div>
                      </div>
                    )}
                  </li>
                ))}
              </ul>
            )}
          </section>
        </div>
      </main>
    </div>
  );
}

export default SearchResults;