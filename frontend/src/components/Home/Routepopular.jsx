import { useNavigate } from "react-router-dom";
import { useState, useEffect, useRef, useCallback } from "react";
import routeService from "../../service/routeService";
import tripService from "../../service/tripService";
import Iconlc1 from "../../assets/icons/iconlc1.png";
import Iconlc2 from "../../assets/icons/iconlc2.png";

function Routepopular() {
  const navigate = useNavigate();
  const [routes, setRoutes] = useState([]);
  const [trips, setTrips] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const routesRef = useRef([]);
  const tripsRef = useRef([]);
  const isFirstFetch = useRef(true);

  // Hàm chuyển đổi duration thành định dạng giờ:phút
  const formatDuration = (duration) => {
    if (!duration || duration === "N/A") return "N/A";

    // Nếu duration là chuỗi dạng "2h30m"
    if (typeof duration === "string" && duration.includes("h")) {
      const [hours, minutes] = duration
        .split("h")
        .map((part) => part.replace("m", ""));
      const formattedMinutes = minutes ? minutes.padStart(2, "0") : "00";
      return `${hours}:${formattedMinutes}`;
    }

    // Nếu duration là số (phút)
    if (typeof duration === "number" || !isNaN(duration)) {
      const totalMinutes = parseInt(duration);
      const hours = Math.floor(totalMinutes / 60);
      const minutes = (totalMinutes % 60).toString().padStart(2, "0");
      return `${hours}:${minutes}`;
    }

    return duration; // Giữ nguyên nếu không xác định được định dạng
  };

  const fetchRoutes = useCallback(async () => {
    try {
      if (isFirstFetch.current) setLoading(true);

      const routeData = await routeService.getRoutes();
      const tripData = await tripService.getTrips();

      // Lấy ngày local yyyy-MM-dd (không dùng UTC)
      const now = new Date();
      const localDate =
        now.getFullYear() +
        "-" +
        String(now.getMonth() + 1).padStart(2, "0") +
        "-" +
        String(now.getDate()).padStart(2, "0");

      const filteredTrips = tripData.filter((trip) => {
        if (!trip.departure_time) return false;
        // So sánh ngày local yyyy-MM-dd
        const dep = new Date(trip.departure_time);
        const tripDate =
          dep.getFullYear() +
          "-" +
          String(dep.getMonth() + 1).padStart(2, "0") +
          "-" +
          String(dep.getDate()).padStart(2, "0");
        return tripDate === localDate;
      });

      const formattedRoutes = routeData
        .map((route, index) => ({
          id: route.id || index + 1,
          from: route.from_location,
          to: route.to_location,
          price: `${parseFloat(route.price).toLocaleString("vi-VN")}đ`,
          distance: route.distance || 0,
          duration: route.duration || "N/A",
          bus_image: route.bus_image || "https://via.placeholder.com/286x185",
          date: localDate,
        }))
        .slice(0, 4);

      const routesChanged =
        JSON.stringify(formattedRoutes) !== JSON.stringify(routesRef.current);
      const tripsChanged =
        JSON.stringify(filteredTrips) !== JSON.stringify(tripsRef.current);

      if (routesChanged) {
        routesRef.current = formattedRoutes;
        setRoutes(formattedRoutes);
      }
      if (tripsChanged) {
        tripsRef.current = filteredTrips;
        setTrips(filteredTrips);
      }
    } catch (err) {
      setError("Đã xảy ra lỗi khi tải dữ liệu: " + err.message);
    } finally {
      if (isFirstFetch.current) {
        setLoading(false);
        isFirstFetch.current = false;
      }
    }
  }, []); // Không phụ thuộc currentDate, luôn lấy ngày local mới nhất

  useEffect(() => {
    fetchRoutes();
    const interval = setInterval(fetchRoutes, 3000);

    return () => clearInterval(interval);
  }, [fetchRoutes]);

  const handleBookTicket = (route) => {
    try {
      setLoading(true);
      const matchedTrips = trips.filter(
        (trip) =>
          trip.from_location === route.from && trip.to_location === route.to
      );

      // Luôn lấy ngày thực tế tại thời điểm bấm nút (theo local timezone, không dùng UTC)
      const today = new Date();
      const todayStr =
        today.getFullYear() +
        "-" +
        String(today.getMonth() + 1).padStart(2, "0") +
        "-" +
        String(today.getDate()).padStart(2, "0");

      navigate("/search", {
        state: {
          trips: matchedTrips, // Có thể là mảng rỗng nếu không tìm thấy chuyến
          departure: route.from,
          destination: route.to,
          date: todayStr, // Đảm bảo luôn truyền ngày hiện tại (local)
        },
      });
    } catch (err) {
      setError("Đã xảy ra lỗi khi tìm kiếm chuyến xe: " + err.message);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <div className="text-center p-5">Đang tải dữ liệu...</div>;
  }

  if (error) {
    return <div className="text-center p-5 text-red-500">{error}</div>;
  }

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-7 m-5 font-roboto">
      {routes.map((route) => (
        <div
          key={route.id}
          className="rounded-3xl shadow-xl bg-white md:w-[286px] md:h-[420px] border border-gray-100 hover:shadow-2xl hover:scale-105 transition-all duration-300 group relative overflow-hidden"
        >
          <div className="relative">
            <img
              src={route.bus_image}
              alt="Bus"
              className="w-full h-44 object-cover rounded-t-3xl md:h-[185px] group-hover:brightness-95 group-hover:scale-105 transition-all duration-300"
            />
            <div className="absolute top-3 left-3 bg-gradient-to-r from-purple-500 to-blue-500 text-white text-xs px-3 py-1 rounded-full shadow font-semibold uppercase tracking-wide">
              Tuyến nổi bật
            </div>
          </div>
          <div className="flex flex-col gap-2 p-5">
            <div className="flex flex-row gap-x-3 items-center mb-2">
              <img className="w-5 h-5" src={Iconlc1} alt="Điểm đi" />
              <span className="text-base font-bold text-indigo-700">
                {route.from}
              </span>
              <span className="mx-2 text-gray-400">→</span>
              <img className="w-5 h-5" src={Iconlc2} alt="Điểm đến" />
              <span className="text-base font-bold text-pink-600">
                {route.to}
              </span>
            </div>
            <div className="flex justify-between items-center mb-2">
              <span className="text-gray-500 text-sm flex items-center gap-1">
                <svg
                  className="w-4 h-4 text-yellow-500"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                  />
                </svg>
                {formatDuration(route.duration)}
              </span>
              <span className="text-gray-500 text-sm flex items-center gap-1">
                <svg
                  className="w-4 h-4 text-green-500"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                  />
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"
                  />
                </svg>
                {Math.round(route.distance)}km
              </span>
            </div>
            <div className="flex justify-between items-center mb-2">
              <span className="text-gray-700 font-semibold text-lg">
                Từ{" "}
                <span className="text-pink-600 font-bold">{route.price}</span>
              </span>
              <button
                onClick={() => handleBookTicket(route)}
                className="bg-gradient-to-r from-indigo-500 to-purple-500 text-white font-bold px-5 py-2 rounded-full shadow hover:from-indigo-600 hover:to-purple-600 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-300"
              >
                Đặt vé
              </button>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}

export default Routepopular;