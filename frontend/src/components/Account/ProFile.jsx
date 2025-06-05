import axios from "axios";
import { FaCamera } from "react-icons/fa";
import { Link, useNavigate } from "react-router-dom";
import { useState, useEffect } from "react";
import { useAuth } from "../contexts/AuthContext";

const API_BASE_URL = process.env.REACT_APP_API_BASE_URL;

function ProFile() {
  const { user, updateUser } = useAuth();
  const navigate = useNavigate();
  const [isEditing, setIsEditing] = useState(false);
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [avatar, setAvatar] = useState(null);
  const [avatarPreview, setAvatarPreview] = useState("");
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [loading, setLoading] = useState(false);
  const [avatarLoading, setAvatarLoading] = useState(false);

  // Cập nhật state từ user
  useEffect(() => {
    if (!user || !user.id) {
      navigate("/signin");
      return;
    }

    setFirstName(user.first_name || "");
    setLastName(user.last_name || "");
    setEmail(user.email || "");
    setPhone(user.phone_number || "");
    setAvatarPreview(user.avatar_url || "");
  }, [user, navigate]);

  const handleAvatarChange = async (e) => {
    const file = e.target.files[0];
    if (file) {
      setAvatar(file);
      setAvatarPreview(URL.createObjectURL(file));
      setAvatarLoading(true);

      try {
        const formData = new FormData();
        formData.append("avatar", file);
        formData.append("user_id", user.id);

        const response = await axios.post(
          `${API_BASE_URL}/custom/v1/upload-avatar`,
          formData,
          {
            headers: {
              Authorization: `Bearer ${user.token}`,
              "Content-Type": "multipart/form-data",
            },
          }
        );

        updateUser({ avatar_url: response.data.avatar_url });
        setSuccess("Cập nhật ảnh đại diện thành công!");
      } catch (error) {
        console.error("Lỗi tải ảnh:", error);
        setError("Cập nhật ảnh đại diện thất bại. Vui lòng thử lại.");
        setAvatarPreview(user.avatar_url || "");
      } finally {
        setAvatarLoading(false);
      }
    }
  };

  const handleUpdateProfile = async (e) => {
    e.preventDefault();
    setError("");
    setSuccess("");
    setLoading(true);

    if (!firstName || !lastName || !email || !phone) {
      setError("Vui lòng điền đầy đủ thông tin bắt buộc.");
      setLoading(false);
      return;
    }

    try {
      const updateResponse = await axios.post(
        `${API_BASE_URL}/custom/v1/update-profile`,
        {
          user_id: user.id,
          first_name: firstName,
          last_name: lastName,
          email,
          phone_number: phone,
        },
        {
          headers: {
            Authorization: `Bearer ${user.token}`,
          },
        }
      );

      updateUser({
        first_name: firstName,
        last_name: lastName,
        email,
        phone_number: phone,
        avatar_url: avatarPreview,
      });

      setSuccess("Cập nhật hồ sơ thành công!");
      setIsEditing(false); // Quay lại chế độ xem
    } catch (error) {
      console.error("Lỗi cập nhật hồ sơ:", error);
      setError(
        error.response?.data?.message ||
          "Cập nhật hồ sơ thất bại. Vui lòng thử lại."
      );
    } finally {
      setLoading(false);
    }
  };

  const getInitials = () => {
    const firstInitial = firstName ? firstName[0] : "";
    const lastInitial = lastName ? lastName[0] : "";
    return `${firstInitial}${lastInitial}`.toUpperCase();
  };

  const formatDate = (dateString) => {
    if (!dateString) return "";
    const d = new Date(dateString);

    // dinh dang YYYY-MM-DD HH:mm:ss
    return `${String(d.getDate()).padStart(2, "0")} -${String(
      d.getMonth() + 1
    ).padStart(2, "0")}-${d.getFullYear()} ${String(d.getHours()).padStart(
      2,
      "0"
    )};`;
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#e0e7ff] via-[#f3e8ff] to-[#ffe4e6] flex items-center justify-center p-6 font-roboto">
      <div className="w-full max-w-5xl bg-white/80 backdrop-blur-lg rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row border border-white/40 font-roboto">
        {/* Left Section: Avatar & Summary */}
        <div className="w-full md:w-1/3 bg-gradient-to-b from-indigo-600 to-purple-500 p-10 flex flex-col items-center justify-center text-white relative font-roboto">
          <div className="relative group mb-8">
            <div className="w-36 h-36 rounded-full bg-white/20 flex items-center justify-center overflow-hidden border-4 border-gradient-to-br from-pink-400 via-indigo-400 to-purple-400 shadow-2xl ring-4 ring-white/30 transition-all duration-300 group-hover:scale-105">
              {avatarPreview ? (
                <img
                  src={avatarPreview}
                  alt="Avatar"
                  className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                  onError={() => setAvatarPreview("")}
                />
              ) : (
                <span className="text-4xl font-extrabold text-white drop-shadow-lg">
                  {getInitials()}
                </span>
              )}
            </div>
            <label
              htmlFor="avatar-upload"
              className="absolute inset-0 flex items-center justify-center bg-black/60 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 cursor-pointer"
            >
              <FaCamera className="text-white text-3xl drop-shadow-lg" />
            </label>
            <input
              id="avatar-upload"
              type="file"
              accept="image/*"
              onChange={handleAvatarChange}
              className="hidden"
              disabled={avatarLoading}
            />
            {avatarLoading && (
              <div className="absolute inset-0 flex items-center justify-center bg-black/70 rounded-full">
                <svg
                  className="animate-spin h-8 w-8 text-white"
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
              </div>
            )}
          </div>
          <h2 className="text-2xl md:text-3xl font-extrabold text-center drop-shadow-lg tracking-wide">
            {firstName} {lastName || "Chưa có tên"}
          </h2>
        </div>

        {/* Right Section: View or Edit */}
        <div className="w-full md:w-2/3 p-8 md:p-14 bg-white/70 backdrop-blur-lg font-roboto">
          {isEditing ? (
            <>
              <h2 className="text-3xl font-extrabold text-indigo-700 mb-2 tracking-tight">
                Chỉnh sửa hồ sơ
              </h2>
              <p className="text-sm text-gray-500 mb-6">
                Cập nhật thông tin cá nhân của bạn
              </p>

              {error && (
                <div className="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg flex items-center gap-2 animate-fade-in">
                  <svg
                    className="w-5 h-5 text-red-500"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                  {error}
                </div>
              )}
              {success && (
                <div className="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-lg flex items-center gap-2 animate-fade-in">
                  <svg
                    className="w-5 h-5 text-green-500"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M5 13l4 4L19 7"
                    />
                  </svg>
                  {success}
                </div>
              )}

              <form onSubmit={handleUpdateProfile} className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div>
                    <label className="block text-sm font-semibold text-indigo-700 mb-2">
                      Họ
                    </label>
                    <input
                      type="text"
                      placeholder="Nhập họ"
                      value={firstName}
                      onChange={(e) => setFirstName(e.target.value)}
                      className="w-full p-4 bg-white/80 border border-indigo-200 rounded-xl text-base focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition-all duration-200 hover:bg-indigo-50 shadow-sm"
                      disabled={loading}
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-semibold text-indigo-700 mb-2">
                      Tên
                    </label>
                    <input
                      type="text"
                      placeholder="Nhập tên"
                      value={lastName}
                      onChange={(e) => setLastName(e.target.value)}
                      className="w-full p-4 bg-white/80 border border-indigo-200 rounded-xl text-base focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition-all duration-200 hover:bg-indigo-50 shadow-sm"
                      disabled={loading}
                      required
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-semibold text-indigo-700 mb-2">
                    Địa chỉ Email
                  </label>
                  <input
                    type="email"
                    placeholder="Nhập email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    className="w-full p-4 bg-white/80 border border-indigo-200 rounded-xl text-base focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition-all duration-200 hover:bg-indigo-50 shadow-sm"
                    disabled={loading}
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-semibold text-indigo-700 mb-2">
                    Số điện thoại
                  </label>
                  <input
                    type="tel"
                    placeholder="Nhập số điện thoại"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    className="w-full p-4 bg-white/80 border border-indigo-200 rounded-xl text-base focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition-all duration-200 hover:bg-indigo-50 shadow-sm"
                    disabled={loading}
                    required
                  />
                </div>
                <div className="flex space-x-4 mt-4">
                  <button
                    type="submit"
                    disabled={loading}
                    className="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-4 rounded-xl font-bold text-base shadow-lg hover:shadow-2xl transition-all duration-300 hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50 flex items-center justify-center gap-2"
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
                        Đang cập nhật...
                      </span>
                    ) : (
                      <>
                        <svg
                          className="w-5 h-5"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth="2"
                          viewBox="0 0 24 24"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M5 13l4 4L19 7"
                          />
                        </svg>
                        Cập nhật hồ sơ
                      </>
                    )}
                  </button>
                  <button
                    type="button"
                    onClick={() => setIsEditing(false)}
                    className="w-full bg-gray-200 text-gray-700 py-4 rounded-xl font-bold text-base shadow-lg hover:shadow-2xl transition-all duration-300 hover:bg-gray-300 flex items-center justify-center gap-2"
                  >
                    <svg
                      className="w-5 h-5"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M6 18L18 6M6 6l12 12"
                      />
                    </svg>
                    Hủy
                  </button>
                </div>
              </form>
            </>
          ) : (
            <>
              <h2 className="text-3xl font-extrabold text-indigo-700 mb-2 tracking-tight">
                Hồ sơ cá nhân
              </h2>
              <p className="text-sm text-gray-500 mb-6">
                Thông tin cá nhân của bạn
              </p>

              <div className="space-y-6">
                <div>
                  <label className="block text-sm font-semibold text-indigo-700 mb-1">
                    Họ và Tên
                  </label>
                  <p className="text-gray-900 text-lg font-semibold">
                    {firstName} {lastName || "Chưa có tên"}
                  </p>
                </div>
                <div>
                  <label className="block text-sm font-semibold text-indigo-700 mb-1">
                    Địa chỉ Email
                  </label>
                  <p className="text-gray-900 text-lg font-semibold">
                    {email || "Chưa có email"}
                  </p>
                </div>
                <div>
                  <label className="block text-sm font-semibold text-indigo-700 mb-1">
                    Số điện thoại
                  </label>
                  <p className="text-gray-900 text-lg font-semibold">
                    {phone || "Chưa có số điện thoại"}
                  </p>
                </div>
              </div>

              <button
                onClick={() => setIsEditing(true)}
                className="w-full mt-8 bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-4 rounded-xl font-bold text-base shadow-lg hover:shadow-2xl transition-all duration-300 hover:from-indigo-700 hover:to-purple-700 flex items-center justify-center gap-2"
              >
                <svg
                  className="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M15.232 5.232l3.536 3.536M9 11l6 6M3 21h6v-6l9.293-9.293a1 1 0 00-1.414-1.414L9 9.586V3H3v6h6z"
                  />
                </svg>
                Chỉnh sửa hồ sơ
              </button>
            </>
          )}

          <p className="mt-8 text-sm text-center text-gray-600">
            Quay lại{" "}
            <Link
              to="/"
              className="text-indigo-600 hover:underline font-semibold"
            >
              Trang chủ
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}

export default ProFile;