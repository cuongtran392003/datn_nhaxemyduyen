import axios from "axios";

// Định nghĩa base URL của API
const API_BASE_URL = "http://localhost:8000/wp-json/nhaxemyduyen/v1/trips";

// Tạo một instance của axios với cấu hình mặc định
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true, // Để gửi cookie xác thực (dành cho các endpoint yêu cầu quyền)
  headers: {
    "Content-Type": "application/json",
  },
});

const tripService = {
  // Lấy danh sách chuyến xe
  getTrips: async () => {
    try {
      const response = await apiClient.get("/");
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi lấy danh sách chuyến xe"
      );
    }
  },

  // Lấy thông tin một chuyến xe
  getTrip: async (id) => {
    try {
      const response = await apiClient.get(`/${id}`);
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi lấy thông tin chuyến xe"
      );
    }
  },

  // Thêm chuyến xe mới
  createTrip: async (tripData) => {
    try {
      const response = await apiClient.post("/", tripData);
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi thêm chuyến xe"
      );
    }
  },

  // Cập nhật chuyến xe
  updateTrip: async (id, tripData) => {
    try {
      const response = await apiClient.put(`/${id}`, tripData);
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi cập nhật chuyến xe"
      );
    }
  },

  // Xóa chuyến xe
  deleteTrip: async (id) => {
    try {
      const response = await apiClient.delete(`/${id}`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.message || "Lỗi khi xóa chuyến xe");
    }
  },
};

export default tripService;
