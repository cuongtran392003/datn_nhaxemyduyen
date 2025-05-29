import { useState, useRef, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { format } from "date-fns";
import DatePicker from "react-datepicker";
import locationService from "../../../service/locationService"; // Import locationService
import tripService from "../../../service/tripService"; // Import tripService

function SearchForm() {

    const navigate = useNavigate();

  const [startDate, setStartDate] = useState(new Date());
  const [departure, setDeparture] = useState("");
  const [destination, setDestination] = useState("");
  const [showDepartureDropdown, setShowDepartureDropdown] = useState(false);
  const [showDestinationDropdown, setShowDestinationDropdown] = useState(false);
  const [suggestions, setSuggestions] = useState([]); // Danh sách địa điểm từ API
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const datepickerRef = useRef(null);
  const departureRef = useRef(null);
  const destinationRef = useRef(null);

  // Kiểm tra xem nút "Tìm vé xe" có nên bị vô hiệu hóa hay không
  const isSearchDisabled = !departure.trim() || !destination.trim();

  // Lấy danh sách địa điểm từ API khi component được mount
  useEffect(() => {
    const fetchLocations = async () => {
      try {
        const data = await locationService.getLocations();
        // Lấy danh sách tên địa điểm từ API
        const locationNames = data.map((location) => location.name);
        setSuggestions(locationNames);
      } catch (err) {
        setError("Không thể tải danh sách địa điểm: " + err.message);
      }
    };

    fetchLocations();
  }, []);

  // Xử lý sự kiện click ngoài dropdown
  useEffect(() => {
    function handleClickOutside(event) {
      if (
        departureRef.current &&
        !departureRef.current.contains(event.target)
      ) {
        setShowDepartureDropdown(false);
      }
      if (
        destinationRef.current &&
        !destinationRef.current.contains(event.target)
      ) {
        setShowDestinationDropdown(false);
      }
    }

    document.addEventListener("mousedown", handleClickOutside);
    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, []);

  // Xử lý tìm kiếm chuyến xe
  const handleSubmit = async (e) => {
    e.preventDefault();

    // Nếu chưa nhập đủ thông tin, hiển thị thông báo lỗi
    if (!departure.trim() || !destination.trim()) {
      setError("Vui lòng nhập nơi đi và nơi đến.");
      return;
    }

    // Kiểm tra xem điểm đi và điểm đến có trong danh sách địa điểm không
    if (
      !suggestions.includes(departure) ||
      !suggestions.includes(destination)
    ) {
      setError("Điểm đi hoặc điểm đến không hợp lệ.");
      return;
    }

    setLoading(true);
    setError(null);

    try {
      // Định dạng ngày thành yyyy-MM-dd để so sánh với dữ liệu từ API
      const formattedDate = format(startDate, "yyyy-MM-dd");

      // Lấy danh sách chuyến xe từ API
      const trips = await tripService.getTrips();

      // Tìm các chuyến xe phù hợp với điểm đi, điểm đến và ngày
      const matchedTrips = trips.filter(
        (trip) =>
          trip.from_location === departure &&
          trip.to_location === destination &&
          trip.departure_time.startsWith(formattedDate)
      );

      if (matchedTrips.length > 0) {
        // Chuyển hướng đến trang /search và truyền dữ liệu chuyến xe
        navigate("/search", {
          state: {
            trips: matchedTrips,
            departure,
            destination,
            date: format(startDate, "dd/MM/yyyy"),
          },
        });
      } else {
        setError("Không tìm thấy chuyến xe nào.");
      }
    } catch (err) {
      setError("Đã xảy ra lỗi khi tìm kiếm: " + err.message);
    } finally {
      setLoading(false);
    }
  };

    return ( 
        
      <form onSubmit={handleSubmit}>
      <ul className="flex flex-col gap-y-5 items-center md:flex-row md:justify-between">
        {/* Nơi đi */}
        <li className="relative" ref={departureRef}>
          <div className="flex items-center gap-x-2 mb-2">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              strokeWidth="1.5"
              stroke="currentColor"
              className="size-6"
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
            <p>Nơi đi</p>
          </div>
          <input
            type="text"
            placeholder="Nhập nơi đi"
            value={departure}
            onChange={(e) => setDeparture(e.target.value)}
            onFocus={() => setShowDepartureDropdown(true)}
            className="input"
          />
          {showDepartureDropdown && (
            <ul className="absolute z-10 bg-white border w-full mt-1 rounded shadow-md">
              {suggestions
                .filter((sug) =>
                  sug.toLowerCase().includes(departure.toLowerCase())
                )
                .map((item, idx) => (
                  <li
                    key={idx}
                    onClick={() => {
                      setDeparture(item);
                      setShowDepartureDropdown(false);
                    }}
                    className="p-2 hover:bg-gray-200 cursor-pointer"
                  >
                    {item}
                  </li>
                ))}
            </ul>
          )}
        </li>

        <li>
          <hr className="w-1 h-20 bg-black" />
        </li>

        {/* Nơi đến */}
        <li className="relative" ref={destinationRef}>
          <div className="flex items-center gap-x-2 mb-2">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              strokeWidth="1.5"
              stroke="currentColor"
              className="size-6"
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
            <p>Nơi đến</p>
          </div>
          <input
            type="text"
            placeholder="Nhập nơi đến"
            value={destination}
            onChange={(e) => setDestination(e.target.value)}
            onFocus={() => setShowDestinationDropdown(true)}
            className="input"
          />
          {showDestinationDropdown && (
            <ul className="absolute z-10 bg-white border w-full mt-1 rounded shadow-md">
              {suggestions
                .filter((sug) =>
                  sug.toLowerCase().includes(destination.toLowerCase())
                )
                .map((item, idx) => (
                  <li
                    key={idx}
                    onClick={() => {
                      setDestination(item);
                      setShowDestinationDropdown(false);
                    }}
                    className="p-2 hover:bg-gray-200 cursor-pointer"
                  >
                    {item}
                  </li>
                ))}
            </ul>
          )}
        </li>

        <li>
          <hr className="w-1 h-20 bg-black" />
        </li>

        {/* Ngày đi */}
        <li>
          <div className="flex items-center gap-x-2 mb-2">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              strokeWidth="1.5"
              stroke="currentColor"
              className="size-6 cursor-pointer"
              onClick={() => datepickerRef.current.setOpen(true)}
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"
              />
            </svg>
            <p>Ngày đi</p>
          </div>
          <DatePicker
            ref={datepickerRef}
            className="input"
            selected={startDate}
            onChange={(date) => setStartDate(date)}
            dateFormat="dd/MM/yyyy"
            placeholderText="Chọn ngày"
            minDate={new Date()} // Không cho phép chọn ngày trong quá khứ
          />
        </li>

        {/* Nút tìm kiếm */}
        <li>
          <button
            type="submit"
            disabled={loading || isSearchDisabled}
            className="relative inline-flex items-center justify-center p-0.5 mb-2 me-2
            overflow-hidden text-sm font-medium text-gray-900 rounded-full
            group bg-gradient-to-br from-purple-600 to-blue-500
            group-hover:from-purple-600 group-hover:to-blue-500 hover:text-white
            dark:text-black focus:ring-4 focus:outline-none focus:ring-blue-300
            dark:focus:ring-blue-800 disabled:opacity-50"
          >
            <span
              className="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white
              dark:bg-gray-900 rounded-full group-hover:bg-transparent
              group-hover:dark:bg-transparent"
            >
              {loading ? "Đang tìm..." : "Tìm vé xe"}
            </span>
          </button>
        </li>
      </ul>
      {error && <p className="text-red-500 text-center mt-4">{error}</p>}
    </form>
     );
}

export default SearchForm;