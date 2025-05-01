import CheckLayOut from "../CheckLayOut";
import { AppTicket1 } from "../../assets/images";
import { AppTicket2 } from "../../assets/images";
import BackToTop from "../BackToTop";
function Tickets() {
    return (
      <div>
        <CheckLayOut
          title="Kiểm tra thông tin vé"
          subtilte="Mã Duyệt và số điện thoại giúp kiểm tra các lượt xe đã duyệt, các vé đã nhận vé và nhận hướng dẫn chuyển phát giao tại trạm – nhà ga"
          fields={[
            { label: "Mã vé", placeholder: "Nhập mã vé" },
            { label: "Số điện thoại", placeholder: "Nhập số điện thoại" },
          ]}
          buttonText="Kiểm tra vé"
          steps={[
            {
              title: "Bước 1. Nhập thông tin vé",
              image: AppTicket1,
              alt: "Nhập thông tin vé",
            },
            {
              title: "Bước 2. Kiểm tra vé",
              image: AppTicket2,
              alt: "Kiểm tra vé",
            },
          ]}
        />
        <BackToTop></BackToTop>
      </div>
    );
}

export default Tickets;