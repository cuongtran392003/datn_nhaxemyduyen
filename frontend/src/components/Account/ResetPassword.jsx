import React, { useState, useEffect } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import Logo from "../../assets/images/logo.png";
import axios from "axios";

const API_BASE_URL = process.env.REACT_APP_API_BASE_URL;

function ResetPassword() {
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  const resetKey = searchParams.get("key");
  const email = searchParams.get("email");

  useEffect(() => {
    if (!resetKey || !email) {
      setError("Liên kết không hợp lệ. Vui lòng thử lại.");
    }
  }, [resetKey, email]);

  const handleResetPassword = async (e) => {
    e.preventDefault();
    setMessage("");
    setError("");
    setLoading(true);

    if (newPassword !== confirmPassword) {
      setError("Mật khẩu và xác nhận mật khẩu không khớp.");
      setLoading(false);
      return;
    }

    if (newPassword.length < 6) {
      setError("Mật khẩu phải có ít nhất 6 ký tự.");
      setLoading(false);
      return;
    }

    const requestBody = {
      user_login: email,
      reset_key: resetKey,
      new_password: newPassword,
    };
    console.log("Gửi yêu cầu đặt lại mật khẩu:", requestBody);

    try {
      const response = await axios.post(
        `${API_BASE_URL}/custom/v1/resetpassword`,
        requestBody
      );
      setMessage(response.data.message);
      setTimeout(() => navigate("/signin"), 3000);
    } catch (err) {
      console.error("Lỗi đặt lại mật khẩu:", err.response?.data);
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
          <h2 className="text-xl md:text-2xl font-bold mb-2">
            Đặt Lại Mật Khẩu
          </h2>
          <p className="text-sm text-gray-600 mb-6">
            Nhập mật khẩu mới của bạn.
          </p>
          {message && <p className="text-green-600 mb-4">{message}</p>}
          {error && <p className="text-red-600 mb-4">{error}</p>}
          <form onSubmit={handleResetPassword}>
            <input
              type="password"
              placeholder="Mật khẩu mới"
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              className="w-full mb-4 p-3 border rounded text-sm"
              required
              disabled={loading}
            />
            <input
              type="password"
              placeholder="Xác nhận mật khẩu"
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              className="w-full mb-4 p-3 border rounded text-sm"
              required
              disabled={loading}
            />
            <button
              type="submit"
              disabled={loading}
              className="w-full bg-purple-600 text-white p-3 rounded shadow hover:bg-purple-700 transition duration-300 text-sm"
            >
              {loading ? "Đang cập nhật..." : "Đặt Lại Mật Khẩu"}
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

export default ResetPassword;
