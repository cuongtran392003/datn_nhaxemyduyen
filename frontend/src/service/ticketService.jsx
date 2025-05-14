import axios from "axios";

const API_BASE_URL = "http://localhost:8000/wp-json/nhaxemyduyen/v1";

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    "Content-Type": "application/json",
  },
});

const ticketService = {
  getTickets: async () => {
    try {
      const response = await apiClient.get("/tickets");
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi lấy danh sách vé xe"
      );
    }
  },

  getTicket: async (id) => {
    try {
      const response = await apiClient.get(`/tickets/${id}`);
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi lấy thông tin vé xe"
      );
    }
  },

  createTicketsBulk: async (ticketsData) => {
    try {
      const response = await apiClient.post("/tickets/bulk", {
        tickets: ticketsData,
      });
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi thêm các vé xe"
      );
    }
  },

  updateTicket: async (id, ticketData) => {
    try {
      const response = await apiClient.put(`/tickets/${id}`, ticketData);
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi cập nhật vé xe"
      );
    }
  },

  deleteTicket: async (id) => {
    try {
      const response = await apiClient.delete(`/tickets/${id}`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.message || "Lỗi khi xóa vé xe");
    }
  },

  getSeatAvailability: async (tripId) => {
    try {
      const response = await apiClient.get(`/trips/${tripId}/seats`);
      return response.data;
    } catch (error) {
      throw new Error(
        error.response?.data?.message || "Lỗi khi lấy thông tin ghế"
      );
    }
  },

  checkTicket: async (ticketCode, phoneNumber) => {
    console.log('checkTicket called with:', { ticketCode, phoneNumber });
    try {
        const response = await apiClient.post("/tickets/check", {
            ticket_code: ticketCode,
            customer_phone: phoneNumber,
        });
        console.log('checkTicket response:', response.data);
        return response.data;
    } catch (error) {
        console.error('checkTicket error:', error.response?.data || error.message);
        throw new Error(error.response?.data?.message || "Lỗi khi tra cứu vé xe");
    }
},
};

export default ticketService;
