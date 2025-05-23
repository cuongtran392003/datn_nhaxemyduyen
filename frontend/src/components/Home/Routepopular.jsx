import { useNavigate } from "react-router-dom";
import { useState, useEffect, useRef } from "react";
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

  const currentDate = new Date().toISOString().split("T")[0];

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

  const fetchRoutes = async () => {
    try {
      setLoading(true);

      const routeData = await routeService.getRoutes();
      const tripData = await tripService.getTrips();

      const filteredTrips = tripData.filter((trip) => {
        if (!trip.departure_time) return false;
        const departureDate = new Date(trip.departure_time)
          .toISOString()
          .split("T")[0];
        return departureDate === currentDate;
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
          date: currentDate,
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
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchRoutes();
    const interval = setInterval(fetchRoutes, 30000);

    return () => clearInterval(interval);
  }, []);

  const handleBookTicket = (route) => {
    try {
      setLoading(true);
      const matchedTrips = trips.filter(
        (trip) =>
          trip.from_location === route.from && trip.to_location === route.to
      );

      // Luôn điều hướng đến /search, kể cả khi không có chuyến xe nào
      navigate("/search", {
        state: {
          trips: matchedTrips, // Có thể là mảng rỗng nếu không tìm thấy chuyến
          departure: route.from,
          destination: route.to,
          date: currentDate,
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
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 m-5">
      {routes.map((route) => (
        <div
          key={route.id}
          className="rounded-xl shadow-lg shadow-yellow bg-white md:w-[286px] md:h-[398px]"
        >
          <img
            src={route.bus_image}
            alt="Bus"
            className="w-full h-40 object-cover rounded-t-xl md:h-[185px]"
          />
          <div className="flex justify-between items-center p-5">
            <div className="flex flex-col gap-y-2 md:gap-y-5">
              <div className="flex flex-row gap-x-2 items-center">
                <img className="w-4 h-4" src={Iconlc1} alt="Điểm đi" />
                <p>{route.from}</p>
              </div>
              <div className="flex flex-row gap-x-2 items-center">
                <img className="w-4 h-4" src={Iconlc2} alt="Điểm đến" />
                <p>{route.to}</p>
              </div>
            </div>
            <p className="">Từ {route.price}</p>
          </div>
          <hr className="border-dashed border-yellow" />
          <ul className="flex justify-between items-center p-5">
            <li className="flex flex-row gap-x-2 items-center">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                strokeWidth="1.5"
                stroke="currentColor"
                className="size-4 text-gray-600"
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
              <p className="text-gray-600">{Math.round(route.distance)}km</p>
            </li>
            <li className="flex flex-row justify-around items-center">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                strokeWidth="1.5"
                stroke="currentColor"
                className="size-4 text-gray-600"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                />
              </svg>
              <p className="text-gray-600">{formatDuration(route.duration)}</p>
            </li>
            <button
              onClick={() => handleBookTicket(route)}
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
                Đặt vé
              </span>
            </button>
          </ul>
        </div>
      ))}
    </div>
  );
}

export default Routepopular;