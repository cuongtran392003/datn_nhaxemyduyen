import React, { createContext, useContext, useState, useEffect } from "react";
import axios from "axios";

const API_BASE_URL = process.env.REACT_APP_API_BASE_URL;

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem("token") || null);
  const [refreshToken, setRefreshToken] = useState(localStorage.getItem("refreshToken") || null);
  const [isLoading, setIsLoading] = useState(true);

  // Xử lý lỗi 401 và làm mới token
  useEffect(() => {
    const interceptor = axios.interceptors.response.use(
      (response) => response,
      async (error) => {
        if (error.response?.status === 401 && refreshToken) {
          try {
            const response = await axios.post(`${API_BASE_URL}/jwt-auth/v1/token/refresh`, {
              refresh_token: refreshToken,
            });
            const newToken = response.data.token;
            setToken(newToken);
            localStorage.setItem("token", newToken);
            error.config.headers["Authorization"] = `Bearer ${newToken}`;
            return axios(error.config); // Thử lại yêu cầu gốc
          } catch (refreshError) {
            console.error("Lỗi làm mới token:", refreshError);
            logout();
            alert("Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.");
            window.location.href = "/login";
          }
        }
        return Promise.reject(error);
      }
    );

    return () => axios.interceptors.response.eject(interceptor);
  }, [refreshToken]);

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
          username: email,
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

      const token = response.data.token;
      const refreshToken = response.data.refresh_token;
      let user_id = response.data.user_id;

      if (!user_id) {
        const meResponse = await axios.get(`${API_BASE_URL}/wp/v2/users/me`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });
        user_id = meResponse.data.id;
      }

      if (!user_id) {
        throw new Error("Không thể lấy user_id.");
      }

      const userResponse = await axios.get(`${API_BASE_URL}/custom/v1/user/${user_id}`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
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
      setRefreshToken(refreshToken);
      localStorage.setItem("user", JSON.stringify(userData));
      localStorage.setItem("token", token);
      localStorage.setItem("refreshToken", refreshToken);
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
      token: user.token,
    };
    setUser(newUserData);
    localStorage.setItem("user", JSON.stringify(newUserData));
  };

  const logout = () => {
    setUser(null);
    setToken(null);
    setRefreshToken(null);
    localStorage.removeItem("user");
    localStorage.removeItem("token");
    localStorage.removeItem("refreshToken");
    localStorage.removeItem("isLoggedIn");
  };

  useEffect(() => {
    const initializeAuth = async () => {
      setIsLoading(true);
      const storedUser = localStorage.getItem("user");
      const storedToken = localStorage.getItem("token");
      const storedRefreshToken = localStorage.getItem("refreshToken");
      const isLoggedIn = localStorage.getItem("isLoggedIn");

      if (storedUser && storedToken && storedRefreshToken && isLoggedIn === "true") {
        try {
          const parsedUser = JSON.parse(storedUser);
          if (!parsedUser.id) {
            throw new Error("Dữ liệu người dùng thiếu user_id.");
          }
          setUser(parsedUser);
          setToken(storedToken);
          setRefreshToken(storedRefreshToken);

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