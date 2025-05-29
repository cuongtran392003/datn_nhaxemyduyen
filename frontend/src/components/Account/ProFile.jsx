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

  const formatDate=(dateString) =>{
    if(!dateString) return "";
    const d= new Date(dateString);

    // dinh dang YYYY-MM-DD HH:mm:ss
    return `${String(d.getDate()).padStart(2, "0")} -${String(d.getMonth() + 1).padStart(2, "0")}-${d.getFullYear()}
    ${String(d.getHours()).padStart(2, "0")};`
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100 flex items-center justify-center p-6">
      <div className="w-full max-w-5xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row">
        {/* Left Section: Avatar & Summary */}
        <div className="w-full md:w-1/3 bg-gradient-to-b from-indigo-500 to-purple-600 p-8 flex flex-col items-center justify-center text-white">
          <div className="relative group mb-6">
            <div className="w-32 h-32 rounded-full bg-white/20 flex items-center justify-center overflow-hidden border-4 border-white/30 shadow-lg">
              {avatarPreview ? (
                <img
                  src={avatarPreview}
                  alt="Avatar"
                  className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                  onError={() => setAvatarPreview("")}
                />
              ) : (
                <span className="text-3xl font-bold text-white">
                  {getInitials()}
                </span>
              )}
            </div>
            <label
              htmlFor="avatar-upload"
              className="absolute inset-0 flex items-center 
              justify-center bg-black bg-opacity-60 rounded-full 
              opacity-0 group-hover:opacity-100 transition-opacity duration-300 cursor-pointer"
            >
              <FaCamera className="text-white text-2xl" />
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
              <div className="absolute inset-0 flex items-center justify-center bg-black bg-opacity-60 rounded-full">
                <svg
                  className="animate-spin h-6 w-6 text-white"
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
          <h2 className="text-2xl font-bold text-center">
            {firstName} {lastName || "Chưa có tên"}
          </h2>
        </div>

        {/* Right Section: View or Edit */}
        <div className="w-full md:w-2/3 p-8 md:p-12">
          {isEditing ? (
            <>
              <h2 className="text-3xl font-bold text-gray-800 mb-2">
                Chỉnh sửa hồ sơ
              </h2>
              <p className="text-sm text-gray-500 mb-6">
                Cập nhật thông tin cá nhân của bạn
              </p>

              {error && (
                <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg animate-fade-in">
                  {error}
                </div>
              )}
              {success && (
                <div className="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-lg animate-fade-in">
                  {success}
                </div>
              )}

              <form onSubmit={handleUpdateProfile} className="space-y-5">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-2">
                      Họ
                    </label>
                    <input
                      type="text"
                      placeholder="Nhập họ"
                      value={firstName}
                      onChange={(e) => setFirstName(e.target.value)}
                      className="w-full p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition-all duration-200 hover:bg-gray-100"
                      disabled={loading}
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-2">
                      Tên
                    </label>
                    <input
                      type="text"
                      placeholder="Nhập tên"
                      value={lastName}
                      onChange={(e) => setLastName(e.target.value)}
                      className="w-full p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition-all duration-200 hover:bg-gray-100"
                      disabled={loading}
                      required
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-semibold text-gray-700 mb-2">
                    Địa chỉ Email
                  </label>
                  <input
                    type="email"
                    placeholder="Nhập email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    className="w-full p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition-all duration-200 hover:bg-gray-100"
                    disabled={loading}
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-semibold text-gray-700 mb-2">
                    Số điện thoại
                  </label>
                  <input
                    type="tel"
                    placeholder="Nhập số điện thoại"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    className="w-full p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition-all duration-200 hover:bg-gray-100"
                    disabled={loading}
                    required
                  />
                </div>
                <div className="flex space-x-4">
                  <button
                    type="submit"
                    disabled={loading}
                    className="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-4 rounded-lg font-semibold text-sm shadow-lg hover:shadow-xl transition-all duration-300 hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50"
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
                      "Cập nhật hồ sơ"
                    )}
                  </button>
                  <button
                    type="button"
                    onClick={() => setIsEditing(false)}
                    className="w-full bg-gray-600 text-black 
                    py-4 rounded-lg font-semibold text-sm shadow-lg 
                    hover:shadow-xl transition-all duration-300 hover:bg-gray-700"
                  >
                    Hủy
                  </button>
                </div>
              </form>
            </>
          ) : (
            <>
              <h2 className="text-3xl font-bold text-gray-800 mb-2">
                Hồ sơ cá nhân
              </h2>
              <p className="text-sm text-gray-500 mb-6">
                Thông tin cá nhân của bạn
              </p>

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-semibold text-gray-700 mb-1">
                    Họ và Tên
                  </label>
                  <p className="text-gray-900">
                    {firstName} {lastName || "Chưa có tên"}
                  </p>
                </div>
                <div>
                  <label className="block text-sm font-semibold text-gray-700 mb-1">
                    Địa chỉ Email
                  </label>
                  <p className="text-gray-900">{email || "Chưa có email"}</p>
                </div>
                <div>
                  <label className="block text-sm font-semibold text-gray-700 mb-1">
                    Số điện thoại
                  </label>
                  <p className="text-gray-900">
                    {phone || "Chưa có số điện thoại"}
                  </p>
                </div>
              </div>

              <button
                onClick={() => setIsEditing(true)}
                className="w-full mt-6 bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-4 rounded-lg font-semibold text-sm shadow-lg hover:shadow-xl transition-all duration-300 hover:from-indigo-700 hover:to-purple-700"
              >
                Chỉnh sửa hồ sơ
              </button>
            </>
          )}

          <p className="mt-6 text-sm text-center text-gray-600">
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