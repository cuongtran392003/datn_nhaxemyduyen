import { useNavigate } from "react-router-dom";
import { useState, useEffect } from "react";
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

  // Lấy ngày hiện tại theo thời gian thực (YYYY-MM-DD)
  const currentDate = new Date().toISOString().split("T")[0];

  // Hàm lấy danh sách tuyến đường và chuyến xe
  const fetchRoutes = async () => {
    try {
      setLoading(true);

      // Lấy danh sách tuyến đường từ API /routes
      const routeData = await routeService.getRoutes();

      // Lấy danh sách chuyến xe từ API /trips
      const tripData = await tripService.getTrips();

      // Lọc các chuyến xe có ngày đi trùng với ngày hiện tại
      const filteredTrips = tripData.filter((trip) => {
        if (!trip.departure_time) return false;
        const departureDate = new Date(trip.departure_time)
          .toISOString()
          .split("T")[0];
        return departureDate === currentDate;
      });

      setTrips(filteredTrips);

      // Định dạng dữ liệu tuyến đường
      const formattedRoutes = routeData.map((route, index) => ({
        id: route.id || index + 1,
        from: route.from_location,
        to: route.to_location,
        price: `${parseFloat(route.price).toLocaleString("vi-VN")}đ`,
        distance: route.distance || 0,
        duration: route.duration || "N/A",
        bus_image: route.bus_image || "https://via.placeholder.com/286x185",
        date: currentDate,
      }));

      setRoutes(formattedRoutes.slice(0, 4)); // Giới hạn tối đa 4 tuyến
    } catch (err) {
      setError("Đã xảy ra lỗi khi tải dữ liệu: " + err.message);
    } finally {
      setLoading(false);
    }
  };

  // Gọi hàm lấy dữ liệu khi component được render
  useEffect(() => {
    fetchRoutes();
    const interval = setInterval(fetchRoutes, 30000); // Cập nhật mỗi 30 giây

    return () => clearInterval(interval);
  }, []);

  // Xử lý khi người dùng bấm "Đặt vé"
  const handleBookTicket = (route) => {
    try {
      setLoading(true);

      // Tìm các chuyến xe phù hợp với tuyến đường và ngày hiện tại
      const matchedTrips = trips.filter(
        (trip) =>
          trip.from_location === route.from && trip.to_location === route.to
      );

      if (matchedTrips.length > 0) {
        // Chuyển hướng đến trang /search và truyền dữ liệu chuyến xe
        navigate("/search", {
          state: {
            trips: matchedTrips,
            departure: route.from,
            destination: route.to,
            date: currentDate,
          },
        });
      } else {
        setError(
          "Không tìm thấy chuyến xe nào cho tuyến đường này vào ngày hôm nay."
        );
      }
    } catch (err) {
      setError("Đã xảy ra lỗi khi tìm kiếm chuyến xe: " + err.message);
    } finally {
      setLoading(false);
    }
  };

  // Hiển thị trạng thái loading
  if (loading) {
    return <div className="text-center p-5">Đang tải dữ liệu...</div>;
  }

  // Hiển thị lỗi nếu có
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
              <p className="text-gray-600">{route.duration}</p>
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
