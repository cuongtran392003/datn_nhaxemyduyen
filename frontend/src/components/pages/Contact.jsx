import { useState } from "react";
import HeroPage from "../Layout/HeroPage";
import BackToTop from "../Shared/BackToTop";
import Map from "../Layout/Map";
import axios from "axios";

function Contact() {
  const [formData, setFormData] = useState({
    name: "",
    phone: "",
    email: "",
    message: "",
  });
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(null);
  const [error, setError] = useState(null);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const validateForm = () => {
    if (!formData.name) return "Vui lòng nhập họ tên.";
    if (!formData.phone) return "Vui lòng nhập số điện thoại.";
    if (!formData.email) return "Vui lòng nhập email.";
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email))
      return "Email không hợp lệ.";
    if (!formData.message) return "Vui lòng nhập nội dung ghi chú.";
    return null;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setSuccess(null);
    setError(null);

    const validationError = validateForm();
    if (validationError) {
      setError(validationError);
      setLoading(false);
      return;
    }

    try {
      const response = await axios.post(
        "http://localhost:8000/wp-json/custom/v1/contact",
        {
          name: formData.name,
          phone: formData.phone,
          email: formData.email,
          message: formData.message,
        }
      );
      setSuccess("Tin nhắn của bạn đã được gửi thành công!");
      setFormData({ name: "", phone: "", email: "", message: "" });
    } catch (err) {
      setError("Đã xảy ra lỗi khi gửi tin nhắn: " + (err.message || "Lỗi không xác định"));
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <HeroPage pageTitle={"Liên hệ"} />
      <div className="flex flex-col md:flex-row gap-x-5 m-5 items-center md:my-5 md:mx-[189px]">
        <div className="mb-5 md:mb-0">
          <p>
            Cần hỗ trợ từ Fixhero?{" "}
            <span className="text-bluecustom">Gửi tin nhắn cho chúng tôi</span>
          </p>
          <p>
            Chúng tôi luôn sẵn sàng hỗ trợ bạn với các chiến lược phát triển
            đa phương tiện tiên tiến. Hãy để lại thông tin để chúng tôi liên hệ!
          </p>
        </div>
        <form
          onSubmit={handleSubmit}
          className="w-full md:w-[633px] bg-bluecustom p-5 flex flex-col items-center rounded-xl"
        >
          <div className="flex flex-col gap-y-5 md:flex-row md:gap-x-5 w-full">
            <input
              className="p-2 outline-none rounded-xl w-full md:w-1/2"
              type="text"
              name="name"
              value={formData.name}
              onChange={handleChange}
              placeholder="Nhập họ tên"
            />
            <input
              className="p-2 outline-none rounded-xl w-full md:w-1/2"
              type="text"
              name="phone"
              value={formData.phone}
              onChange={handleChange}
              placeholder="Số điện thoại"
            />
          </div>
          <input
            className="p-2 outline-none rounded-xl w-full my-5"
            type="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            placeholder="Email"
          />
          <textarea
            className="p-2 outline-none rounded-xl w-full my-5"
            name="message"
            value={formData.message}
            onChange={handleChange}
            placeholder="Ghi chú nội dung"
            rows="4"
          />
          <button
            type="submit"
            disabled={loading}
            className={`bg-white p-2 rounded-xl ${
              loading ? "opacity-50 cursor-not-allowed" : ""
            }`}
          >
            {loading ? "Đang gửi..." : "Gửi tin nhắn"}
          </button>
          {success && (
            <p className="text-green-500 mt-3 text-center">{success}</p>
          )}
          {error && <p className="text-red-500 mt-3 text-center">{error}</p>}
        </form>
      </div>
      <Map />
      <BackToTop />
    </>
  );
}

export default Contact;