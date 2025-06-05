import Logo from "../../assets/images/logo.png";
import { FcGoogle } from "react-icons/fc";
import {
  FaEye,
  FaEyeSlash,
  FaFacebook,
  FaInstagram,
  FaTwitter,
} from "react-icons/fa";
import { Link, useNavigate } from "react-router-dom";
import { useState, useEffect } from "react";
import { useAuth } from "../contexts/AuthContext";

function SignIn() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassWord, setShowPassWord] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState(""); // State cho thông báo thành công
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const { user, login } = useAuth();

  useEffect(() => {
    if (user) {
      navigate("/");
    }
  }, [user, navigate]);

  const handleLogin = async (e) => {
    e.preventDefault();
    setError("");
    setSuccess("");
    setLoading(true);

    try {
      await login(email, password);
      setSuccess("Đăng nhập thành công!");
      setTimeout(() => navigate("/"), 2000); // Chuyển hướng sau 2 giây
    } catch (err) {
      console.error("Lỗi đăng nhập:", err);
      // Xử lý thông báo lỗi thân thiện
      if (
        err.message.includes("incorrect") ||
        err.message.includes("invalid") ||
        err.message.includes("sai mật khẩu") ||
        err.message.includes("The password you entered")
      ) {
        setError(
          "Email hoặc mật khẩu không đúng. Nếu quên mật khẩu, hãy nhấn vào Quên mật khẩu."
        );
      } else if (err.message.includes("Không nhận được token")) {
        setError("Lỗi server: Không thể xác thực. Vui lòng thử lại sau.");
      } else if (err.message.includes("kiểm tra email")) {
        setError("Email hoặc mật khẩu không đúng.");
      } else if (err.message.includes("Bạn không có quyền")) {
        setError("Không thể tải thông tin người dùng. Vui lòng thử lại.");
      } else {
        setError(err.message);
      }
    } finally {
      setLoading(false);
    }
  };

  const toggleShowPassWord = () => {
    setShowPassWord(!showPassWord);
  };

  const handleGoogleLogin = () => {
    setError("Tính năng đăng nhập bằng Google đang được phát triển.");
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

        <div className="w-full md:w-1/2 bg-gray-50 p-6 md:p-12 relative">
          <h2 className="text-xl md:text-2xl font-bold mb-2">Đăng nhập</h2>
          <p className="text-sm text-gray-600 mb-6">
            Đăng nhập vào tài khoản của bạn một cách nhanh chóng
          </p>

          {/* Thông báo lỗi */}
          {error && (
            <div className="mb-4 p-3 text-sm text-red-700 bg-red-100 rounded-lg shadow-md animate-slide-in">
              {error}
            </div>
          )}

          {/* Thông báo thành công */}
          {success && (
            <div className="mb-4 p-3 text-sm text-green-700 bg-green-100 
            rounded-lg shadow-md animate-slide-in flex items-center">
              <svg
                className="w-5 h-5 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
              {success}
            </div>
          )}

          <form onSubmit={handleLogin}>
            <input
              type="email"
              placeholder="Địa chỉ Email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full mb-4 p-3 border rounded text-sm focus:outline-none focus:ring-2 focus:ring-purple-600"
              required
            />
            <div className="relative">
              <input
                type={showPassWord ? "text" : "password"}
                placeholder="Mật khẩu"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full mb-4 p-3 border rounded text-sm focus:outline-none focus:ring-2 focus:ring-purple-600"
                required
              />
              <button
                type="button"
                onClick={toggleShowPassWord}
                className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
              >
                {showPassWord ? <FaEyeSlash /> : <FaEye />}
              </button>
            </div>
            <div className="flex items-center justify-between mb-4 text-sm">
              <label className="flex items-center">
                <input type="checkbox" className="mr-2" />
                Ghi nhớ đăng nhập
              </label>
              <Link
                to="/forgot-password"
                className="text-blue-600 hover:underline"
              >
                Quên mật khẩu?
              </Link>
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
                  Đang đăng nhập...
                </span>
              ) : (
                "Đăng nhập"
              )}
            </button>
          </form>

          <p className="mt-6 text-sm text-center">
            Chưa có tài khoản?{" "}
            <Link to="/signup" className="text-purple-600 hover:underline">
              Đăng ký ngay
            </Link>
          </p>

          <div className="mt-6 text-center text-sm text-gray-500">
            Hoặc đăng nhập bằng
          </div>
          <div className="flex justify-center gap-4 mt-4 text-xl">
            <FcGoogle
              onClick={handleGoogleLogin}
              className="cursor-pointer hover:scale-110 transition-transform"
              title="Đăng nhập bằng Google"
            />
            <FaFacebook
              className="text-blue-600 cursor-pointer hover:scale-110 transition-transform"
              title="Đăng nhập bằng Facebook"
            />
            <FaInstagram
              className="text-pink-500 cursor-pointer hover:scale-110 transition-transform"
              title="Đăng nhập bằng Instagram"
            />
            <FaTwitter
              className="text-blue-400 cursor-pointer hover:scale-110 transition-transform"
              title="Đăng nhập bằng Twitter"
            />
          </div>
        </div>
      </div>
    </div>
  );
}

export default SignIn;