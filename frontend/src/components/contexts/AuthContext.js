import React, { createContext, useContext , useState, useEffect } from "react";
import axios from "axios";

const API_BASE_URL = "http://localhost:8000/wp-json"; // Điều chỉnh theo URL WordPress của bạn

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem("token") || null);
  const [isLoading, setIsLoading] = useState(true);

  // Cấu hình Axios để thêm token vào tiêu đề Authorization
  useEffect(() => {
    if (token) {
      axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
    } else {
      delete axios.defaults.headers.common["Authorization"];
    }
  }, [token]);

  const login = async (email, password) => {
    try {
      if (!email || !password) {
        throw new Error("Vui lòng nhập email và mật khẩu.");
      }

      const response = await axios.post(
        `${API_BASE_URL}/jwt-auth/v1/token`,
        {
          username: email, // Sử dụng email làm tên người dùng (điều chỉnh nếu WordPress yêu cầu tên người dùng thực)
          password,
        },
        {
          headers: {
            "Content-Type": "application/json",
          },
        }
      );

      if (!response.data.token) {
        throw new Error("Không nhận được token từ server.");
      }

      const { token, user_email, user_display_name } = response.data;

      const userData = {
        email: user_email,
        name: user_display_name,
      };

      setUser(userData);
      setToken(token);
      localStorage.setItem("user", JSON.stringify(userData));
      localStorage.setItem("token", token);
      localStorage.setItem("isLoggedIn", "true");

      return true;
    } catch (error) {
      console.error("Lỗi đăng nhập:", error);
      throw new Error(
        error.response?.data?.message ||
          "Đăng nhập thất bại. Vui lòng kiểm tra email và mật khẩu."
      );
    }
  };

  const logout = () => {
    setUser(null);
    setToken(null);
    localStorage.removeItem("user");
    localStorage.removeItem("token");
    localStorage.removeItem("isLoggedIn");
  };

  useEffect(() => {
    const initializeAuth = async () => {
      setIsLoading(true);
      const storedUser = localStorage.getItem("user");
      const storedToken = localStorage.getItem("token");
      const isLoggedIn = localStorage.getItem("isLoggedIn");

      if (storedUser && storedToken && isLoggedIn === "true") {
        try {
          const parsedUser = JSON.parse(storedUser);
          setUser(parsedUser);
          setToken(storedToken);

          await axios.get(`${API_BASE_URL}/wp/v2/users/me`, {
            headers: {
              Authorization: `Bearer ${storedToken}`,
            },
          });
        } catch (error) {
          console.error("Lỗi khởi tạo xác thực:", error);
          logout();
        }
      } else {
        logout();
      }
      setIsLoading(false);
    };

    initializeAuth();
  }, []);

  return (
    <AuthContext.Provider value={{ user, token, login, logout, isLoading }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);