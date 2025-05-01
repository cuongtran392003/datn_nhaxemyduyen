import React, { useState } from "react";
import { Link } from "react-router-dom";
import Logo from "../../assets/images/logo.png";
import axios from "axios";

const API_BASE_URL = "http://localhost:8000/wp-json";

function ForgotPassword() {
  const [email, setEmail] = useState("");
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleForgotPassword = async (e) => {
    e.preventDefault();
    setMessage("");
    setError("");
    setLoading(true);

    if (!email) {
      setError("Vui lòng nhập địa chỉ email.");
      setLoading(false);
      return;
    }

    try {
      const response = await axios.post(
        `${API_BASE_URL}/custom/v1/lostpassword`,
        {
          user_login: email,
        }
      );
      setMessage(response.data.message);
    } catch (err) {
      setError(
        err.response?.data?.message || "Đã có lỗi xảy ra. Vui lòng thử lại."
      );
    } finally {
      setLoading(false);
    }
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
          <h2 className="text-xl md:text-2xl font-bold mb-2">Quên Mật Khẩu</h2>
          <p className="text-sm text-gray-600 mb-6">
            Nhập email của bạn để nhận liên kết đặt lại mật khẩu.
          </p>
          {message && <p className="text-green-600 mb-4">{message}</p>}
          {error && <p className="text-red-600 mb-4">{error}</p>}
          <form onSubmit={handleForgotPassword}>
            <input
              type="email"
              placeholder="Địa chỉ Email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full mb-4 p-3 border rounded text-sm"
              required
              disabled={loading}
            />
            <button
              type="submit"
              disabled={loading}
              className="w-full bg-purple-600 text-white p-3 rounded shadow hover:bg-purple-700 transition duration-300 text-sm"
            >
              {loading ? "Đang gửi..." : "Gửi Liên Kết Đặt Lại"}
            </button>
          </form>
          <p className="mt-6 text-sm text-center">
            Quay lại{" "}
            <Link to="/signin" className="text-purple-600 hover:underline">
              Đăng nhập
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}

export default ForgotPassword;
