import { useState } from "react";
import { Link } from "react-router-dom"; // Chỉ cần Link để tạo liên kết
import { useAuth } from "../contexts/AuthContext";
import { useNavigate } from "react-router-dom";
import Logo from "../../assets/images/logo.png";
function Nav() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [isOpen, setIsOpen] = useState(false);
  const [isUserMenuOpen, setIsUserMenuOpen] = useState(false);

  const handleLogout = () => {
    logout();
    setIsOpen(false);
    setIsUserMenuOpen(false);
    navigate("/");
  };


  // hàm lấy chữ cái đầu (fallback nếu không có avatar)
  const getInitials = () => {
    const firstInitial =user?.first_name ? user.first_name[0] : "";
    const lastInitial = user?.last_name ? user.last_name[0] : "";
    return `${firstInitial}${lastInitial}`.toUpperCase() || "U";
  };

  return (
    <nav className="flex justify-between items-center p-6 md:px-10">
      {/* Logo */}
      <Link to="/">
        <img
          className="w-16 h-16 md:w-[146px] md:h-[118px]"
          src={Logo}
          alt="Company Logo"
        />
      </Link>

      {/* Biểu tượng hamburger (hiển thị trên mobile) */}
      <button
        className="md:hidden focus:outline-none"
        onClick={() => setIsOpen(!isOpen)}
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          strokeWidth="1.5"
          stroke="currentColor"
          className="w-8 h-8"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d={
              isOpen
                ? "M6 18L18 6M6 6l12 12" // Biểu tượng "X" khi menu mở
                : "M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" // Biểu tượng hamburger khi menu đóng
            }
          />
        </svg>
      </button>

      {/* Menu điều hướng */}
      <ul
        className={`${isOpen ? "flex" : "hidden"} 
          md:flex flex-col md:flex-row gap-y-5 md:gap-y-0 md:gap-x-8 uppercase font-poppins absolute md:static 
          top-20 left-0 w-full md:w-auto bg-bluecustom md:bg-transparent p-6 md:p-0 shadow-md md:shadow-none
          text-white md:text-black md:items-center transition-all duration-300 ease-in-out z-10`}
      >
        <li>
          <Link
            to="/"
            onClick={() => setIsOpen(false)}
            className="text-gray-700 hover:text-blue-500 transition-colors"
          >
            Trang Chủ
          </Link>
        </li>
        <li>
          <Link
            to="/about"
            onClick={() => setIsOpen(false)}
            className="text-gray-700 hover:text-blue-500 transition-colors"
          >
            Giới thiệu
          </Link>
        </li>
        <li>
          <Link
            to="/services"
            onClick={() => setIsOpen(false)}
            className="text-gray-700 hover:text-blue-500 transition-colors"
          >
            Dịch Vụ
          </Link>
        </li>
        <li className="relative">
          <button className="flex gap-x-2 peer text-gray-700 hover:text-blue-500 transition-colors uppercase">
            Tra Cứu
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
                d="m8.25 4.5 7.5 7.5-7.5 7.5"
              />
            </svg>
          </button>
          <ul
            className="absolute hidden peer-hover:block hover:block
     bg-white shadow-xl rounded-xl z-10 transition-all duration-200 ease-in-out
     md:w-60"
          >
            <li>
              <Link
                to="/tickets"
                onClick={() => setIsOpen(false)}
                className="block px-4 py-2 text-black hover:bg-blue-500 hover:text-white transition-colors"
              >
                Tra Cứu Vé
              </Link>
            </li>
            <li>
              <Link
                to="/orders"
                onClick={() => setIsOpen(false)}
                className="block px-4 py-2 text-black hover:bg-blue-500 hover:text-white transition-colors"
              >
                Tra Cứu Đơn Hàng
              </Link>
            </li>
          </ul>
        </li>

        <li>
          <Link
            to="/news"
            onClick={() => setIsOpen(false)}
            className="text-gray-700 hover:text-blue-500 transition-colors"
          >
            Tin Tức
          </Link>
        </li>
        <li>
          <Link
            to="/contact"
            onClick={() => setIsOpen(false)}
            className="text-gray-700 hover:text-blue-500 transition-colors"
          >
            Liên Hệ
          </Link>
        </li>
        <li>
          <Link
            to="/careers"
            onClick={() => setIsOpen(false)}
            className="text-gray-700 hover:text-blue-500 transition-colors"
          >
            Tuyển Dụng
          </Link>
        </li>
        <li>
          {user ? (
            <>
            <button
              className="text-gray-700 hover:text-blue-500 transition-colors"
              onClick={() => setIsUserMenuOpen(!isUserMenuOpen)}
            >
              {user.avatar_url ? (
                <img
                src={user.avatar_url}
                alt='Avatar'
                className="w-10 h-10 rounded-full object-cover"
                onError={()=>{setIsUserMenuOpen(isUserMenuOpen)}}// giữ trạng thái menu nếu ảnh lỗi 
                />
              ):(
                <span className="text-sm font-bold">{getInitials()}</span>
              )}
            </button>
            <ul
              className={`${
                isUserMenuOpen ? "block" : "hidden"
              } absolute bg-white shadow-xl rounded-xl z-10 transition-all 
              duration-200 ease-in-out md:w-48 right-0 mt-2 font-poppins`}
            >
              <li>
                <Link
                  to="/profile"
                  onClick={() => {
                    setIsOpen(false);
                    setIsUserMenuOpen(false);
                  }}
                  className="block px-4 py-2 text-black hover:bg-blue-500
                   hover:text-white transition-colors lowercase "
                >
                  Hồ Sơ
                </Link>
              </li>
              <li>
                <Link
                  to="/booking-history"
                  onClick={() => {
                    setIsOpen(false);
                    setIsUserMenuOpen(false);
                  }}
                  className="block px-4 py-2 text-black hover:bg-blue-500
                   hover:text-white transition-colors lowercase"
                >
                  Lịch Sử Đặt Vé
                </Link>
              </li>
              <li>
                <button
                  onClick={handleLogout}
                  className="block w-full text-left px-4 py-2 text-black
                   hover:bg-blue-500 hover:text-white transition-colors lowercase"
                >
                  Đăng Xuất
                </button>
              </li>
            </ul>
          </>
          ) : (
            <Link to="/signin" onClick={() => setIsOpen(false)}>
              <button
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
                  Đăng nhập/Đăng ký
                </span>
              </button>
            </Link>
          )}
        </li>
      </ul>
    </nav>
  );
}

export default Nav;
