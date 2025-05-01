import { useLocation } from "react-router-dom";
import { useEffect, useState } from "react";
import TripCard from "../TripCard";
import SeatSelection from "../SeatSelection";

function SearchResults() {
  const location = useLocation();
  const [trips, setTrips] = useState([]);
  const [filteredTrips, setFilteredTrips] = useState([]);
  const [filters, setFilters] = useState({ time: "", price: "" });
  const [expandedTripId, setExpandedTripId] = useState(null);

  const {
    trips: initialTrips,
    departure,
    destination,
    date,
  } = location.state || {};

  useEffect(() => {
    if (initialTrips) {
      // Chuẩn hóa dữ liệu
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
          console.error(
            `Invalid departure time for trip ${trip.trip_id}: ${trip.departure_time}`
          );
        }

        return {
          id: trip.trip_id,
          pickup_location: trip.pickup_location,
          dropoff_location: trip.dropoff_location,
          date: date,
          time: timeCategory,
          price: parseFloat(trip.price) || 0,
          availableSeats: trip.available_seats,
          bus_image: trip.bus_image,
          company: "Nhà Xe Mỹ Duyên",
          departure_time: trip.departure_time, // Truyền nguyên bản departure_time
        };
      });
      setTrips(formattedTrips);
      setFilteredTrips(formattedTrips);
    }
  }, [initialTrips, date]);

  useEffect(() => {
    let updatedTrips = [...trips];
    if (filters.time) {
      updatedTrips = updatedTrips.filter((trip) => trip.time === filters.time);
    }
    if (filters.price) {
      updatedTrips = updatedTrips.filter(
        (trip) => trip.price <= parseInt(filters.price)
      );
    }
    setFilteredTrips(updatedTrips);
  }, [filters, trips]);

  return (
    <div className="min-h-screen bg-gray-100 py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-5xl mx-auto">
        {/* Tiêu đề */}
        <h1 className="text-3xl font-bold text-gray-800 mb-6 flex items-center gap-2">
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
          Kết quả tìm kiếm: {departure} → {destination} | {date}
        </h1>

        {/* Form lọc */}
        <div className="bg-white p-6 rounded-lg shadow-md mb-6">
          <div className="flex flex-col sm:flex-row sm:items-end gap-4">
            <div className="flex-1">
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Thời gian
              </label>
              <select
                onChange={(e) =>
                  setFilters((prev) => ({ ...prev, time: e.target.value }))
                }
                className="block w-full border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
              >
                <option value="">Tất cả</option>
                <option value="Morning">Sáng</option>
                <option value="Afternoon">Chiều</option>
                <option value="Evening">Tối</option>
              </select>
            </div>
            <div className="flex-1">
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Giá tối đa (VND)
              </label>
              <input
                type="number"
                onChange={(e) =>
                  setFilters((prev) => ({ ...prev, price: e.target.value }))
                }
                className="block w-full border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                placeholder="Nhập giá tối đa"
              />
            </div>
          </div>
        </div>

        {/* Danh sách chuyến xe */}
        {filteredTrips.length === 0 ? (
          <div className="bg-white p-6 rounded-lg shadow-md flex items-center justify-center gap-3 text-gray-600">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-6 w-6"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fillRule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1zm-1 4a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1z"
                clipRule="evenodd"
              />
            </svg>
            Không tìm thấy chuyến xe nào.
          </div>
        ) : (
          <ul className="space-y-6">
            {filteredTrips.map((trip) => (
              <li
                key={trip.id}
                className="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300"
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
                  <div className="mt-6 border-t pt-4">
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
  );
}

export default SearchResults;
