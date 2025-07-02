import { useLocation } from "react-router-dom";
import { useEffect, useState, useMemo } from "react";
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

  // Hàm kiểm tra chuyến xe có phải hiện tại hoặc tương lai không
  const isFutureOrTodayTrip = (departureTimeStr) => {
    if (!departureTimeStr) return false;
    const dep = new Date(departureTimeStr);
    if (isNaN(dep.getTime())) return false;
    const now = new Date();
    // So sánh từ 00:00 hôm nay
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    return dep >= today;
  };

  // Helper: normalize date string to yyyy-MM-dd (local, not UTC)
  const normalizeDate = (dateString) => {
    if (!dateString) return "";
    // yyyy-MM-dd
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return dateString;
    // dd/MM/yyyy or MM/dd/yyyy
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateString)) {
      const [a, b, c] = dateString.split("/");
      // Luôn ưu tiên dd/MM/yyyy cho người Việt Nam, kể cả khi a <= 12 và b <= 12
      return `${c}-${b.padStart(2, "0")}-${a.padStart(2, "0")}`;
    }
    // Fallback: try Date
    const d = new Date(dateString);
    if (!isNaN(d.getTime())) {
      return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
    }
    return dateString;
  };

  useEffect(() => {
    if (initialTrips && Array.isArray(initialTrips)) {
      setIsLoading(true);
      // Lọc đúng ngày (so sánh yyyy-MM-dd)
      const normalizedDate = normalizeDate(date);
      const formattedTrips = initialTrips
        .filter((trip) => {
          const tripDate = trip.departure_time ? normalizeDate(trip.departure_time) : "";
          // Không cần kiểm tra isFutureOrTodayTrip vì đã lọc ở Routepopular
          return tripDate === normalizedDate;
        })
        .map((trip) => {
          const departureTime = trip.departure_time ? new Date(trip.departure_time) : null;
          let timeCategory = "Không xác định";

          if (departureTime && !isNaN(departureTime.getTime())) {
            const hours = departureTime.getHours();
            if (hours >= 5 && hours < 12) timeCategory = "Morning";
            else if (hours >= 12 && hours < 17) timeCategory = "Afternoon";
            else timeCategory = "Evening";
          } else {
            console.warn(`Invalid departure time for trip ${trip.trip_id}: ${trip.departure_time}`);
          }

          return {
            id: trip.trip_id || `trip-${Math.random()}`,
            pickup_location: trip.pickup_location || "Không xác định",
            dropoff_location: trip.dropoff_location || "Không xác định",
            date: formatDate(normalizedDate),
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

  // Nếu được chuyển từ nút 'Tìm chuyến xe khác' hoặc từ chat, hiển thị tất cả chuyến xe hiện tại và tương lai
  useEffect(() => {
    if (location.state?.reset) {
      setIsLoading(true);
      
      // Kiểm tra xem có sẵn initialTrips từ chat không
      if (location.state.initialTrips && Array.isArray(location.state.initialTrips)) {
        console.log("Using pre-fetched trips from chat", location.state.initialTrips.length);
        const formattedTrips = location.state.initialTrips
          .filter((trip) => isFutureOrTodayTrip(trip.departure_time))
          .map((trip) => {
            const departureTime = trip.departure_time ? new Date(trip.departure_time) : null;
            let timeCategory = "Không xác định";
            if (departureTime && !isNaN(departureTime.getTime())) {
              const hours = departureTime.getHours();
              if (hours >= 5 && hours < 12) timeCategory = "Morning";
              else if (hours >= 12 && hours < 17) timeCategory = "Afternoon";
              else timeCategory = "Evening";
            }
            return {
              id: trip.trip_id || `trip-${Math.random()}`,
              pickup_location: trip.pickup_location || "Không xác định",
              dropoff_location: trip.dropoff_location || "Không xác định",
              date: trip.departure_time ? formatDate(trip.departure_time) : "Không xác định",
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
        // Nếu không có initialTrips, gọi API như bình thường
        console.log("Fetching trips from API");
        tripService.getTrips()
          .then((allTrips) => {
            const formattedTrips = allTrips
              .filter((trip) => isFutureOrTodayTrip(trip.departure_time))
              .map((trip) => {
                const departureTime = trip.departure_time ? new Date(trip.departure_time) : null;
                let timeCategory = "Không xác định";
                if (departureTime && !isNaN(departureTime.getTime())) {
                  const hours = departureTime.getHours();
                  if (hours >= 5 && hours < 12) timeCategory = "Morning";
                  else if (hours >= 12 && hours < 17) timeCategory = "Afternoon";
                  else timeCategory = "Evening";
                }
                return {
                  id: trip.trip_id || `trip-${Math.random()}`,
                  pickup_location: trip.pickup_location || "Không xác định",
                  dropoff_location: trip.dropoff_location || "Không xác định",
                  date: trip.departure_time ? formatDate(trip.departure_time) : "Không xác định",
                  time: timeCategory,
                  price: parseFloat(trip.price) || 0,
                  availableSeats: parseInt(trip.available_seats) || 0,
                  bus_image: trip.bus_image || "",
                  company: "Nhà Xe Mỹ Duyên",
                  departure_time: trip.departure_time || "",
                };
              });
            setTrips(formattedTrips);
          })
          .finally(() => setIsLoading(false));
      }
    }
  }, [location.state]);

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

  // Tính toán số lượng chuyến xe có thể đặt được (chưa khởi hành)
  const availableTripsCount = useMemo(() => {
    if (!filteredTrips || filteredTrips.length === 0) return 0;
    
    const currentDate = new Date();
    return filteredTrips.filter(trip => {
      if (!trip.departure_time) return false;
      const departureDate = new Date(trip.departure_time);
      return !isNaN(departureDate.getTime()) && departureDate > currentDate;
    }).length;
  }, [filteredTrips]);

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

  // Polling để cập nhật danh sách chuyến xe nhưng không render lại giao diện nếu không có thay đổi
  useEffect(() => {
    // Nếu có cờ skipPolling, không thực hiện polling để tránh mất dữ liệu
    if (location.state?.skipPolling) {
      console.log("Polling skipped due to skipPolling flag");
      return;
    }
    
    let pollingInterval;
    let lastTripsJson = JSON.stringify(trips);
    
    // Hàm thực hiện polling
    const pollTrips = async () => {
      try {
        const allTrips = await tripService.getTrips();
        
        // Kiểm tra xem có reset flag hay không
        if (location.state?.reset) {
          // Nếu có reset, chỉ cập nhật khi số lượng chuyến thay đổi đáng kể
          // hoặc có chuyến xe mới xuất hiện/mất đi
          const currentTripsCount = trips.length;
          const futureTrips = allTrips.filter(trip => isFutureOrTodayTrip(trip.departure_time));
          
          // Chỉ cập nhật nếu có sự thay đổi lớn về số lượng chuyến xe
          if (Math.abs(futureTrips.length - currentTripsCount) > 1) {
            const formattedTrips = futureTrips.map((trip) => {
              const departureTime = trip.departure_time ? new Date(trip.departure_time) : null;
              let timeCategory = "Không xác định";
              if (departureTime && !isNaN(departureTime.getTime())) {
                const hours = departureTime.getHours();
                if (hours >= 5 && hours < 12) timeCategory = "Morning";
                else if (hours >= 12 && hours < 17) timeCategory = "Afternoon";
                else timeCategory = "Evening";
              }
              return {
                id: trip.trip_id || `trip-${Math.random()}`,
                pickup_location: trip.pickup_location || "Không xác định",
                dropoff_location: trip.dropoff_location || "Không xác định",
                date: trip.departure_time ? formatDate(trip.departure_time) : "Không xác định",
                time: timeCategory,
                price: parseFloat(trip.price) || 0,
                availableSeats: parseInt(trip.available_seats) || 0,
                bus_image: trip.bus_image || "",
                company: "Nhà Xe Mỹ Duyên",
                departure_time: trip.departure_time || "",
              };
            });
            console.log("Updating trips from reset polling: ", formattedTrips.length);
            setTrips(formattedTrips);
          }
        } else if (date && departure && destination) {
          // Lọc đúng ngày hiện tại (local) và đúng tuyến đường
          const normalizedDate = normalizeDate(date);
          const filtered = allTrips.filter((trip) => {
            const tripDate = trip.departure_time ? normalizeDate(trip.departure_time) : "";
            // Lọc đúng tuyến đường (from_location, to_location)
            return tripDate === normalizedDate &&
              trip.from_location === departure &&
              trip.to_location === destination;
          });
          const newTripsJson = JSON.stringify(filtered);
          if (newTripsJson !== lastTripsJson) {
            console.log("Updating trips from normal polling");
            setTrips(filtered.map((trip) => ({
              id: trip.trip_id || `trip-${Math.random()}`,
              pickup_location: trip.pickup_location || "Không xác định",
              dropoff_location: trip.dropoff_location || "Không xác định",
              date: formatDate(normalizedDate),
              time: (() => {
                const departureTime = trip.departure_time ? new Date(trip.departure_time) : null;
                if (departureTime && !isNaN(departureTime.getTime())) {
                  const hours = departureTime.getHours();
                  if (hours >= 5 && hours < 12) return "Morning";
                  if (hours >= 12 && hours < 17) return "Afternoon";
                  return "Evening";
                }
                return "Không xác định";
              })(),
              price: parseFloat(trip.price) || 0,
              availableSeats: parseInt(trip.available_seats) || 0,
              bus_image: trip.bus_image || "",
              company: "Nhà Xe Mỹ Duyên",
              departure_time: trip.departure_time || "",
            })));
            lastTripsJson = newTripsJson;
          }
        }
      } catch (err) {
        console.error("Polling error:", err);
      }
    };
    
    // Thực hiện polling với khoảng thời gian dài hơn
    pollingInterval = setInterval(pollTrips, 10000); // 10 giây thay vì 3 giây
    
    return () => clearInterval(pollingInterval);
  }, [date, trips, departure, destination, location.state]);

  if ((!initialTrips || !departure || !destination || !date) && !location.state?.reset) {
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
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-100 font-roboto">
      {/* Search Form Section - Positioned at the very top */}
      <section className="shadow-2xl z-30 relative mx-2 md:mx-24 lg:mx-48 rounded-2xl py-8 px-4 sm:px-6 lg:px-8 bg-white/90 border border-blue-100 mt-4 mb-8">
        <SearchForm />
      </section>

      <main className="max-w-7xl mx-auto py-10 px-2 sm:px-6 lg:px-8">
        <div className="flex flex-col lg:flex-row gap-8">
          {/* Filter Sidebar */}
          <aside
            className={`fixed inset-y-0 left-0 w-80 bg-white shadow-2xl rounded-r-3xl border-r-4 border-blue-200 transform transition-transform duration-300 ease-in-out z-30 lg:static lg:w-1/4 lg:transform-none lg:rounded-2xl lg:p-6 ${
              isFilterOpen ? "translate-x-0" : "-translate-x-full"
            }`}
            aria-label="Bộ lọc chuyến xe"
          >
            <div className="flex items-center justify-between p-4 lg:p-0 border-b border-gray-200 lg:border-0">
              <h2 className="text-2xl font-bold text-blue-700 flex items-center gap-2 font-montserrat">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-6 w-6 text-blue-600"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fillRule="evenodd"
                    d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                    clipRule="evenodd"
                  />
                </svg>
                Bộ lọc chuyến xe
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
            <div className="p-4 lg:p-0 space-y-6">
              <div>
                <label className="flex text-sm font-medium text-gray-700 mb-2 items-center gap-1.5">
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
                <label className="flex text-sm font-medium text-gray-700 mb-2 items-center gap-1.5">
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
                <label className="flex text-sm font-medium text-gray-700 mb-2 items-center gap-1.5">
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
                <label className="flex text-sm font-medium text-gray-700 mb-2 items-center gap-1.5">
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
                <label className="flex text-sm font-medium text-gray-700 mb-2 items-center gap-1.5">
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
                className="w-full bg-gradient-to-r from-blue-600 to-indigo-500 text-white py-2.5 px-4 rounded-xl flex items-center justify-center gap-2 hover:from-blue-700 hover:to-indigo-600 transition-all duration-200 font-semibold font-montserrat shadow-lg"
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
            <div className="bg-white rounded-2xl shadow-xl p-8 mb-6 border border-blue-100">
              <h1 className="text-3xl font-extrabold text-indigo-700 mb-3 font-montserrat">
                Kết quả tìm kiếm: {departure} → {destination} | {formatDate(date)}
              </h1>
              <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <p className="text-blue-600 text-lg mb-3 sm:mb-0 font-montserrat">
                  {availableTripsCount} chuyến xe có thể đặt được
                </p>
                <div className="flex items-center gap-3">
                  <label className="text-base font-medium text-gray-700 font-roboto">Sắp xếp theo</label>
                  <select
                    value={sortOption}
                    onChange={(e) => setSortOption(e.target.value)}
                    className="w-full sm:w-48 border-gray-300 rounded-lg py-2.5 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all duration-200 bg-white shadow-sm font-roboto"
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
                    className="bg-white rounded-2xl shadow-lg p-8 animate-pulse border border-blue-100"
                  >
                    <div className="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                    <div className="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                    <div className="h-4 bg-gray-200 rounded w-1/4"></div>
                  </div>
                ))}
              </div>
            ) : availableTripsCount === 0 ? (
              <section className="bg-white rounded-2xl shadow-lg p-10 text-center border border-blue-100">
                <img
                  src={route_no_schedule_2}
                  alt="Không có chuyến xe nào"
                  className="max-w-full h-auto max-h-48 mx-auto mb-6"
                />
                <p className="text-gray-500 text-lg mb-6 leading-relaxed font-roboto">
                  Không tìm thấy chuyến xe nào từ <strong>{departure}</strong> đến{" "}
                  <strong>{destination}</strong> vào ngày {formatDate(date)}.
                  <br />
                  Vui lòng thử tìm kiếm với các tiêu chí khác hoặc liên hệ hỗ trợ.
                </p>
                <a
                  href="tel:190011112222"
                  className="inline-block bg-gradient-to-r from-blue-600 to-indigo-500 text-white px-8 py-3 rounded-xl hover:from-blue-700 hover:to-indigo-600 transition-all duration-200 font-semibold font-montserrat shadow-lg"
                  aria-label="Liên hệ hotline"
                >
                  Gọi Hotline: 1900 1111 2222
                </a>
              </section>
            ) : (
              <ul className="flex flex-col md:flex-col gap-8">
                {filteredTrips.map((trip) => (
                  <li
                    key={trip.id}
                    className="bg-white rounded-2xl shadow-xl hover:shadow-2xl hover:scale-[1.02] transition-all duration-300 overflow-hidden border border-blue-100 font-montserrat"
                  >
                    <TripCard
                      trip={trip}
                      onSelect={() =>
                        setExpandedTripId(expandedTripId === trip.id ? null : trip.id)
                      }
                      hideIfDeparted={true}
                    />
                    {expandedTripId === trip.id && (
                      <div
                        className={`transition-all duration-300 ease-in-out ${
                          expandedTripId === trip.id ? "block" : "hidden"
                        }`}
                      >
                        <div className="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50 lg:static lg:bg-transparent lg:p-4 lg:border-t lg:border-gray-200">
                          <div className="bg-white p-6 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto lg:max-w-full lg:p-0 shadow-2xl lg:shadow-none">
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