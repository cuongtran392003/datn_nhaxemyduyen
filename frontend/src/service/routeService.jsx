import axios from "axios";

// Định nghĩa base URL của API
const API_BASE_URL = "http://localhost:8000/wp-json/nhaxemyduyen/v1/routes";

// Tạo một instance của axios với cấu hình mặc định
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true, // Để gửi cookie xác thực (dành cho các endpoint yêu cầu quyền)
  headers: {
    "Content-Type": "application/json",
  },
});

const routeService = {
  // Lấy danh sách tuyến đường
  getRoutes: async () => {
    try {
      const response = await apiClient.get("/");
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi lấy danh sách tuyến đường"
      );
    }
  },

  // Lấy thông tin một tuyến đường
  getRoute: async (id) => {
    try {
      const response = await apiClient.get(`/${id}`);
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi lấy thông tin tuyến đường"
      );
    }
  },

  // Thêm tuyến đường mới
  createRoute: async (routeData) => {
    try {
      const response = await apiClient.post("/", routeData);
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi thêm tuyến đường"
      );
    }
  },

  // Cập nhật tuyến đường
  updateRoute: async (id, routeData) => {
    try {
      const response = await apiClient.put(`/${id}`, routeData);
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi cập nhật tuyến đường"
      );
    }
  },

  // Xóa tuyến đường
  deleteRoute: async (id) => {
    try {
      const response = await apiClient.delete(`/${id}`);
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi xóa tuyến đường"
      );
    }
  },
};

export default routeService;
