import { useState } from "react";
import BackToTop from "../Shared/BackToTop";
import HeroPage from "../Layout/HeroPage";
import ticketService from "../../service/ticketService"; // Fixed import path (services instead of service)
import {ClipLoader} from "react-spinners";

function CheckLayOut({ title, subtitle, fields, buttonText, steps }) {
  const [ticketInfo, setTicketInfo] = useState(null);
  const [error, setError] = useState(null);
  const [formData, setFormData] = useState({
    ticketCode: "",
    phoneNumber: "",
  });

  const [loading, setLoading] = useState(false);

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
    setLoading(true); // Bắt đầu loading

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
    } finally {
      setLoading(false); // Kết thúc loading
    }
  };

  return (
    <>
      <HeroPage pageTitle={title} />
      <div className="m-5">
        <h1 className="h1 text-center text-indigo-700 font-bold mb-2">{title}</h1>
        <p className="text-center mb-8 text-gray-600 text-lg">{subtitle}</p>
        <form
          onSubmit={handleSubmit}
          className="flex flex-col md:flex-row items-center gap-4 max-w-2xl mx-auto mb-10 bg-white p-6 rounded-2xl shadow-lg"
        >
          {fields.map((field, index) => (
            <div key={index} className="w-full">
              <label className="text-bluecustom font-semibold block mb-1">
                {field.label}
              </label>
              <input
                type="text"
                placeholder={field.placeholder}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:outline-none transition"
                onChange={(e) => handleInputChange(e, field.label)}
                value={
                  field.label === "Mã vé"
                    ? formData.ticketCode
                    : formData.phoneNumber
                }
                disabled={loading}
              />
            </div>
          ))}
          <div className="md:col-span-2 text-center w-full">
            <button
              type="submit"
              className="bg-gradient-to-r from-indigo-500 to-blue-500 text-white w-full md:w-52 h-10 rounded-xl font-semibold shadow hover:from-indigo-600 hover:to-blue-600 transition"
              disabled={loading}
            >
              {loading ? (
                <ClipLoader color="#ffffff" size={20} />
              ) : (
                buttonText
              )}
            </button>
          </div>
        </form>

        {error && (
          <p className="text-red-500 text-center mb-4 bg-red-50 p-3 rounded-lg font-medium shadow">
            {error}
          </p>
        )}

        {ticketInfo && (
          <div className="relative bg-gradient-to-br from-green-50 via-white to-blue-50 p-8 rounded-3xl mb-8 max-w-2xl mx-auto shadow-2xl border-4 border-transparent hover:border-blue-300 transition group">
            <div className="absolute -top-6 left-1/2 -translate-x-1/2 flex items-center gap-2">
              <span
                className={
                  `inline-flex items-center px-4 py-1 rounded-full text-base font-bold shadow-lg border-2 border-white group-hover:border-blue-400 transition ` +
                  (ticketInfo.status === 'Đã thanh toán'
                    ? 'bg-green-500 text-white'
                    : ticketInfo.status === 'Đã hủy'
                    ? 'bg-red-500 text-white'
                    : 'bg-yellow-400 text-gray-900')
                }
              >
                {ticketInfo.status === 'Đã thanh toán' && (
                  <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" /></svg>
                )}
                {ticketInfo.status === 'Đã hủy' && (
                  <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                )}
                {ticketInfo.status === 'Chưa thanh toán' && (
                  <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3" /></svg>
                )}
                {ticketInfo.status}
              </span>
            </div>
            <h3 className="text-2xl font-extrabold mb-8 text-center text-indigo-700 tracking-widest drop-shadow-lg mt-4 font-montserrat">
              Vé #{ticketInfo.ticket_code}
            </h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-8 text-gray-700 text-lg font-roboto">
              <div className="bg-white rounded-2xl p-5 shadow-md border border-blue-100">
                <div className="font-semibold text-gray-500 mb-1 font-roboto">Khách Hàng</div>
                <div className="text-xl font-bold font-montserrat">{ticketInfo.customer_name}</div>
                <div className="text-sm text-gray-400 font-roboto">SĐT: {ticketInfo.customer_phone}</div>
                <div className="text-sm text-gray-400 font-roboto">Email: {ticketInfo.customer_email || "Không có"}</div>
              </div>
              <div className="bg-white rounded-2xl p-5 shadow-md border border-blue-100">
                <div className="font-semibold text-gray-500 mb-1 font-roboto">Hành trình</div>
                <div className="text-xl font-bold font-montserrat">{ticketInfo.start_location} → {ticketInfo.end_location}</div>
                <div className="text-sm text-gray-400 font-roboto">Điểm đón: {ticketInfo.pickup_location}</div>
                <div className="text-sm text-gray-400 font-roboto">Điểm trả: {ticketInfo.dropoff_location}</div>
              </div>
              <div className="bg-white rounded-2xl p-5 shadow-md border border-blue-100">
                <div className="font-semibold text-gray-500 mb-1 font-roboto">Thời gian khởi hành</div>
                <div className="text-lg font-semibold font-montserrat">{new Date(ticketInfo.departure_time).toLocaleString("vi-VN", {dateStyle: "short", timeStyle: "short"})}</div>
              </div>
              <div className="bg-white rounded-2xl p-5 shadow-md border border-blue-100 flex flex-col gap-2">
                <div className="font-semibold text-gray-500 mb-1 font-roboto">Ghế</div>
                <div className="text-2xl font-extrabold text-indigo-600 font-montserrat">{ticketInfo.seat_number}</div>
              </div>
              <div className="bg-white rounded-2xl p-5 shadow-md border border-blue-100">
                <div className="font-semibold text-gray-500 mb-1 font-roboto">Tài xế</div>
                <div className="text-lg font-semibold font-montserrat">{ticketInfo.driver_name || "Chưa chọn"}</div>
              </div>
              <div className="bg-white rounded-2xl p-5 shadow-md border border-blue-100">
                <div className="font-semibold text-gray-500 mb-1 font-roboto">Phương tiện</div>
                <div className="text-lg font-semibold font-montserrat">{ticketInfo.vehicle_plate || "Chưa chọn"}</div>
              </div>
              <div className="bg-white rounded-2xl p-5 shadow-md border border-blue-100 sm:col-span-2">
                <div className="font-semibold text-gray-500 mb-1 font-roboto">Ghi chú</div>
                <div className="text-base font-roboto">{ticketInfo.note || "Không có ghi chú"}</div>
              </div>
            </div>
          </div>
        )}

        <div className="grid md:grid-cols-2 gap-6 items-start mt-10">
          {steps.map((step, index) => (
            <div className="flex flex-col items-center gap-y-5" key={index}>
              <h3 className="font-semibold text-bluecustom mb-2 text-lg">{step.title}</h3>
              <img
                src={step.image}
                alt={step.alt}
                className="md:w-[369px] md:h-[401px] rounded-xl shadow"
              />
            </div>
          ))}
        </div>
      </div>
      <BackToTop />
    </>
  );
}

export default CheckLayOut;
