import axios from "axios";

// Định nghĩa base URL của API
const API_BASE_URL = "http://localhost:8000/wp-json/nhaxemyduyen/v1/locations";

// Tạo một instance của axios với cấu hình mặc định
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true, // Để gửi cookie xác thực (dành cho các endpoint yêu cầu quyền)
  headers: {
    "Content-Type": "application/json",
  },
});

const locationService = {
  // Lấy danh sách địa điểm
  getLocations: async () => {
    try {
      const response = await apiClient.get("/");
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi lấy danh sách địa điểm"
      );
    }
  },

  // Lấy thông tin một địa điểm
  getLocation: async (id) => {
    try {
      const response = await apiClient.get(`/${id}`);
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi lấy thông tin địa điểm"
      );
    }
  },

  // Thêm địa điểm mới
  createLocation: async (name) => {
    try {
      const response = await apiClient.post("/", { name });
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.message || "Lỗi khi thêm địa điểm");
    }
  },

  // Cập nhật địa điểm
  updateLocation: async (id, name) => {
    try {
      const response = await apiClient.put(`/${id}`, { name });
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi cập nhật địa điểm"
      );
    }
  },

  // Xóa địa điểm
  deleteLocation: async (id) => {
    try {
      const response = await apiClient.delete(`/${id}`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.message || "Lỗi khi xóa địa điểm");
    }
  },
};

export default locationService;
