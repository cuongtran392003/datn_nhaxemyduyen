import CheckLayOut from "../Tickets/CheckLayOut";
import { AppTicket1 } from "../../assets/images";
import { AppTicket2 } from "../../assets/images";
function Orders() {
  return (
    <>
      <CheckLayOut
        title="Kiểm tra thông tin đơn hàng"
        subtilte="Mã Duyệt và số điện thoại giúp kiểm tra các lượt xe đã duyệt, các vé đã nhận vé và nhận hướng dẫn chuyển phát giao tại trạm – nhà ga"
        fields={[
          { label: "Mã đơn hàng ", placeholder: "Nhập mã vé" },
          { label: "Số điện thoại", placeholder: "Nhập số điện thoại" },
        ]}
        buttonText="Kiểm tra vé"
        steps={[
          {
            title: "Bước 1. Nhập thông tin đơn hàng",
            image: AppTicket1,
            alt: "Nhập thông tin vé",
          },
          {
            title: "Bước 2. Kiểm tra đơn hàng",
            image: AppTicket2,
            alt: "Kiểm tra đơn hàng",
          },
        ]}
      />
    </>
  );
}

export default Orders;
