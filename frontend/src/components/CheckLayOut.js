import { useState } from "react";
import BackToTop from "./BackToTop";
import HeroPage from "./HeroPage";
import ticketService from "../service/ticketService"; // Fixed import path (services instead of service)

function CheckLayOut({ title, subtitle, fields, buttonText, steps }) {
  const [ticketInfo, setTicketInfo] = useState(null);
  const [error, setError] = useState(null);
  const [formData, setFormData] = useState({
    ticketCode: "",
    phoneNumber: "",
  });

  const handleInputChange = (e, fieldLabel) => {
    const value = e.target.value;
    console.log(`Input changed - ${fieldLabel}: ${value}`); // Debug giá trị nhập
    if (fieldLabel === "Mã vé") {
      setFormData((prev) => ({ ...prev, ticketCode: value }));
    } else if (fieldLabel === "Số điện thoại") {
      setFormData((prev) => ({ ...prev, phoneNumber: value }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setTicketInfo(null);

    const { ticketCode, phoneNumber } = formData;

    console.log("Form data on submit:", formData); // Debug dữ liệu form

    if (!ticketCode || !phoneNumber) {
      setError("Vui lòng nhập đầy đủ mã vé và số điện thoại.");
      return;
    }

    try {
      const ticket = await ticketService.checkTicket(ticketCode, phoneNumber);
      console.log("Ticket from API:", ticket); // Debug kết quả từ API
      setTicketInfo(ticket);
    } catch (err) {
      setError(err.message || "Đã xảy ra lỗi khi tra cứu vé.");
    }
  };

  return (
    <>
      <HeroPage pageTitle={title}></HeroPage>
      <div className="m-5">
        <h1 className="h1 text-center">{title}</h1>
        <p className="text-center mb-5">{subtitle}</p>
        <form
          onSubmit={handleSubmit}
          className="grid md:grid-cols-2 items-center gap-4 max-w-2xl mx-auto mb-8"
        >
          {fields.map((field, index) => (
            <div key={index}>
              <label className="text-bluecustom font-semibold">
                {field.label}
              </label>
              <input
                type="text"
                placeholder={field.placeholder}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl"
                onChange={(e) => handleInputChange(e, field.label)}
                value={
                  field.label === "Mã vé"
                    ? formData.ticketCode
                    : formData.phoneNumber
                }
              />
            </div>
          ))}
          <div className="md:col-span-2 text-center">
            <button
              type="submit"
              className="bg-bluecustom text-white w-52 h-10 rounded-xl"
            >
              {buttonText}
            </button>
          </div>
        </form>

        {error && (
          <p className="text-red-500 text-center mb-4 bg-red-50 p-3 rounded-lg">
            {error}
          </p>
        )}

        {ticketInfo && (
          <div className="bg-green-50 p-6 rounded-lg mb-6 max-w-2xl mx-auto">
            <h3 className="text-lg font-semibold mb-4 text-green-600 flex items-center gap-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                className="h-6 w-6"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                  clipRule="evenodd"
                />
              </svg>
              Thông tin vé của bạn
            </h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-gray-700">
              <p>
                <span className="font-medium">Mã Vé:</span>{" "}
                {ticketInfo.ticket_code}
              </p>
              <p>
                <span className="font-medium">Khách Hàng:</span>{" "}
                {ticketInfo.customer_name}
              </p>
              <p>
                <span className="font-medium">Số Điện Thoại:</span>{" "}
                {ticketInfo.customer_phone}
              </p>
              <p>
                <span className="font-medium">Email:</span>{" "}
                {ticketInfo.customer_email || "Không có"}
              </p>
              <p>
                <span className="font-medium">Chuyến Xe:</span>{" "}
                {ticketInfo.start_location} → {ticketInfo.end_location}
              </p>
              <p>
                <span className="font-medium">Điểm Đón:</span>{" "}
                {ticketInfo.pickup_location}
              </p>
              <p>
                <span className="font-medium">Điểm Trả:</span>{" "}
                {ticketInfo.dropoff_location}
              </p>
              <p>
                <span className="font-medium">Thời Gian Khởi Hành:</span>{" "}
                {new Date(ticketInfo.departure_time).toLocaleString("vi-VN", {
                  dateStyle: "short",
                  timeStyle: "short",
                })}
              </p>
              <p>
                <span className="font-medium">Ghế:</span>{" "}
                {ticketInfo.seat_number}
              </p>
              <p>
                <span className="font-medium">Trạng Thái:</span>{" "}
                {ticketInfo.status}
              </p>
              <p>
                <span className="font-medium">Tài Xế:</span>{" "}
                {ticketInfo.driver_name || "Chưa chọn"}
              </p>
              <p>
                <span className="font-medium">Phương Tiện:</span>{" "}
                {ticketInfo.vehicle_plate || "Chưa chọn"}
              </p>
              <p>
                <span className="font-medium">Ghi chú:</span>{" "}
                {ticketInfo.note || "Không có ghi chú"}
              </p>
            </div>
          </div>
        )}

        <div className="grid md:grid-cols-2 gap-6 items-start">
          {steps.map((step, index) => (
            <div className="flex flex-col items-center gap-y-5" key={index}>
              <h3 className="font-semibold text-bluecustom mb-2">
                {step.title}
              </h3>
              <img
                src={step.image}
                alt={step.alt}
                className="md:w-[369px] md:h-[401px]"
              />
            </div>
          ))}
        </div>
      </div>
      <BackToTop></BackToTop>
    </>
  );
}

export default CheckLayOut;
