import Logo from "../assets/images/logo.png";

function Footer() {
  return (
    <footer className="w-full bg-blackcustom text-white p-10 md:p-20">
      <div className="flex flex-col gap-y-10 md:flex-row md:justify-between">
        {/* Cột 1: Logo và Thông tin công ty */}
        <div className="flex flex-col gap-y-2">
          <img
            className="w-40 h-40"
            src={Logo}
            alt="Logo Company"
          />
          <h3 className="font-semibold text-lg">
            CÔNG TY TNHH VẬN CHUYỂN HÀNH KHÁCH MỸ DUYÊN
          </h3>
          <p>Giấy chứng nhận đăng ký DN số 0300993856</p>
          <p>Đại diện: Nguyễn Thị Mỹ Duyên</p>
          <h4 className="font-semibold mt-2">Liên hệ</h4>
          <p>Email: xekhachmyduyen0309@gmail.com</p>
          <p>Tổng đài: 02862 77 55 44 – 0903 861 192</p>
        </div>

        {/* Cột 2: Khách hàng */}
        <div>
          <h3 className="font-semibold text-lg mb-2">Khách hàng</h3>
          <ul className="space-y-1">
            <li>Đặt vé online</li>
            <li>Kiểm tra vé</li>
            <li>Tra cứu đơn hàng</li>
            <li>Chính sách vận chuyển</li>
            <li>Chính sách thanh toán</li>
            <li>Chính sách hủy vé/đổi trả</li>
            <li>Chính sách bảo mật thông tin</li>
            <li>Chính sách bảo mật thanh toán</li>
            <li>Chính sách xử lý khiếu nại</li>
          </ul>
        </div>

        {/* Cột 3: Địa chỉ */}
        <div>
          <h3 className="font-semibold text-lg mb-2">Tại Sóc Trăng</h3>
          <p>VP Sóc Trăng: 38 Lê Duẩn, P. 3</p>
          <p>VP Long Phú: Ấp 14, đường Đoàn Thế Trung, TT. Long Phú</p>
          <p>VP Mỹ Tú: 170 ấp Ngoại Ô, TT. Huỳnh Hữu Nghĩa</p>

          <h3 className="font-semibold text-lg mt-4 mb-2">Tại Sài Gòn</h3>
          <p>
            Bến xe Miền Tây: Phòng vé 16
            <br />
            395 Kinh Dương Vương, P. An Lạc, Q. Bình Tân
          </p>
        </div>
      </div>

      <p className="text-center mt-10 text-sm">
        © 2024 My Duyen. Powered by Manh Cuong
      </p>
    </footer>
  );
}

export default Footer;
