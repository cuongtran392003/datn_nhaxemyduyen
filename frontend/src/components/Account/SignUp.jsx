import axios from "axios";
import Logo from "../../assets/images/logo.png";
import { FcGoogle } from "react-icons/fc";
import {
  FaFacebook,
  FaInstagram,
  FaTwitter,
  FaEye,
  FaEyeSlash,
} from "react-icons/fa";
import { Link, useNavigate } from "react-router-dom";
import { useState } from "react";
import { useAuth } from "../contexts/AuthContext";

const API_BASE_URL = "http://localhost:8000/wp-json"; // Điều chỉnh theo URL WordPress của bạn

function SignUp() {
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [showPassWord, setShowPassWord] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState(""); // Thêm trạng thái thành công
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const { login } = useAuth();

  const handleSignUp = async (e) => {
    e.preventDefault();
    setError("");
    setSuccess("");
    setLoading(true);

    // Kiểm tra các trường bắt buộc
    if (!firstName || !lastName || !email || !phone || !password) {
      setError("Vui lòng điền đầy đủ thông tin.");
      setLoading(false);
      return;
    }

    try {
      // Gửi yêu cầu đăng ký
      const registerResponse = await axios.post(
        `${API_BASE_URL}/custom/v1/register`,
        {
          username: email.split("@")[0],
          email,
          password,
          first_name: firstName,
          last_name: lastName,
          description: phone,
        }
      );

      // Kiểm tra phản hồi đăng ký
      if (
        registerResponse.data.message !==
        "Đăng ký thành công! Vui lòng đăng nhập."
      ) {
        throw new Error("Đăng ký không thành công.");
      }

      // Hiển thị thông báo thành công
      setSuccess(
        "Đăng ký thành công! Đang chuyển hướng đến trang đăng nhập..."
      );

      // Chuyển hướng đến trang đăng nhập sau 2 giây
      setTimeout(() => {
        navigate("/signin");
      }, 2000);
    } catch (error) {
      console.error("Lỗi đăng ký:", error);
      setError(
        error.response?.data?.message || "Đăng ký thất bại. Vui lòng thử lại."
      );
    } finally {
      setLoading(false);
    }
  };

  const toggleShowPassWord = () => {
    setShowPassWord(!showPassWord);
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-white p-4">
      <div className="flex flex-col md:flex-row w-full max-w-6xl shadow-lg rounded-lg overflow-hidden">
        <div className="w-full md:w-1/2 bg-white flex items-center justify-center p-6 md:p-10">
          <img
            src={Logo}
            alt="Mỹ Duyên Tour Bus Rental"
            className="w-48 md:w-72"
          />
        </div>

        <div className="w-full md:w-1/2 bg-gray-50 p-6 md:p-12">
          <h2 className="text-xl md:text-2xl font-bold mb-2">Đăng ký</h2>
          <p className="text-sm text-gray-600 mb-6">
            Tạo tài khoản của bạn một cách nhanh chóng
          </p>
          {error && <p className="text-red-500 mb-4">{error}</p>}
          {success && <p className="text-green-500 mb-4">{success}</p>}

          <form onSubmit={handleSignUp} className="space-y-4">
            <input
              type="text"
              placeholder="Họ"
              value={firstName}
              onChange={(e) => setFirstName(e.target.value)}
              className="w-full p-3 border rounded text-sm"
              disabled={loading}
            />
            <input
              type="text"
              placeholder="Tên"
              value={lastName}
              onChange={(e) => setLastName(e.target.value)}
              className="w-full p-3 border rounded text-sm"
              disabled={loading}
            />
            <input
              type="email"
              placeholder="Địa chỉ Email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full p-3 border rounded text-sm"
              disabled={loading}
            />
            <input
              type="tel"
              placeholder="Số điện thoại"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              className="w-full p-3 border rounded text-sm"
              disabled={loading}
            />
            <div className="relative">
              <input
                type={showPassWord ? "text" : "password"}
                placeholder="Tạo mật khẩu"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full p-3 border rounded text-sm"
                disabled={loading}
              />
              <button
                type="button"
                onClick={toggleShowPassWord}
                className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
                disabled={loading}
              >
                {showPassWord ? <FaEyeSlash /> : <FaEye />}
              </button>
            </div>

            <div className="flex items-center text-sm">
              <input type="checkbox" className="mr-2" disabled={loading} />
              <span>Tôi đồng ý với các điều khoản và chính sách bảo mật</span>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-purple-600 text-white p-3 rounded hover:bg-purple-700 shadow text-sm"
            >
              {loading ? (
                <span className="flex items-center justify-center">
                  <svg
                    className="animate-spin h-5 w-5 mr-2 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle
                      className="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      strokeWidth="4"
                    />
                    <path
                      className="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8v8z"
                    />
                  </svg>
                  Đang đăng ký...
                </span>
              ) : (
                "Tạo tài khoản"
              )}
            </button>
          </form>

          <p className="mt-6 text-sm text-center">
            Đã có tài khoản?{" "}
            <Link to="/signin" className="text-purple-600 hover:underline">
              Đăng nhập
            </Link>
          </p>

          <div className="mt-6 text-center text-sm text-gray-500">
            Hoặc đăng ký bằng
          </div>
          <div className="flex justify-center gap-4 mt-4 text-2xl">
            <FcGoogle className="cursor-pointer" title="Google" />
            <FaFacebook
              className="text-blue-600 cursor-pointer"
              title="Facebook"
            />
            <FaInstagram
              className="text-pink-500 cursor-pointer"
              title="Instagram"
            />
            <FaTwitter
              className="text-blue-400 cursor-pointer"
              title="Twitter"
            />
          </div>
        </div>
      </div>
    </div>
  );
}

export default SignUp;
