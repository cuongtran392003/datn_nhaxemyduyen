import React, { createContext, useContext, useState, useEffect } from "react";
import axios from "axios";

const API_BASE_URL = "http://localhost:8000/wp-json";

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

      // Đăng nhập qua JWT
      const response = await axios.post(
        `${API_BASE_URL}/jwt-auth/v1/token`,
        {
          username: email,
          password,
        },
        {
          headers: {
            "Content-Type": "application/json",
          },
        }
      );

      console.log("Phản hồi JWT:", response.data);

      if (!response.data.token) {
        throw new Error("Không nhận được token từ server.");
      }

      const token = response.data.token;
      let user_id = response.data.user_id;

      // Nếu user_id không có, lấy từ /wp/v2/users/me
      if (!user_id) {
        const meResponse = await axios.get(`${API_BASE_URL}/wp/v2/users/me`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });
        user_id = meResponse.data.id;
        console.log("Lấy user_id từ /wp/v2/users/me:", user_id);
      }

      if (!user_id) {
        throw new Error("Không thể lấy user_id.");
      }

      // Lấy thông tin chi tiết người dùng
      const userResponse = await axios
        .get(`${API_BASE_URL}/custom/v1/user/${user_id}`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        })
        .catch((error) => {
          console.error(
            "Lỗi khi lấy dữ liệu người dùng:",
            error.response?.data || error.message
          );
          throw error;
        });

      const userData = {
        id: user_id,
        first_name: userResponse.data.first_name || "",
        last_name: userResponse.data.last_name || "",
        email: userResponse.data.email || response.data.user_email || email,
        phone_number: userResponse.data.phone_number || "",
        avatar_url: userResponse.data.avatar_url || "",
        roles: userResponse.data.roles || [],
        token,
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
          error.message ||
          "Đăng nhập thất bại. Vui lòng kiểm tra email và mật khẩu."
      );
    }
  };

  const updateUser = (updatedUserData) => {
    const newUserData = {
      ...user,
      ...updatedUserData,
      token: user.token, // Giữ nguyên token
    };
    setUser(newUserData);
    localStorage.setItem("user", JSON.stringify(newUserData));
    console.log("Cập nhật thông tin người dùng:", newUserData);
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
          if (!parsedUser.id) {
            throw new Error("Dữ liệu người dùng thiếu user_id.");
          }
          setUser(parsedUser);
          setToken(storedToken);

          // Kiểm tra token hợp lệ
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
    <AuthContext.Provider
      value={{ user, token, login, updateUser, logout, isLoading }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
