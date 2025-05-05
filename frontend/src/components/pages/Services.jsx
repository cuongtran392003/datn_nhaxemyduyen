import HeroPage from "../Layout/HeroPage";
import {Bus} from "../../assets/images";
import { Service } from "../../assets/images";
import BackToTop from "../Shared/BackToTop";
function Services() {
    return (
      <div className="text-center">
        <HeroPage pageTitle={"Dịch vụ"}></HeroPage>
        <h1 className="h1">Dịch vụ vận tải hành khách</h1>
        <p>
          Mỹ Duyên có văn phòng thuận tiện trên các khu vực xe chạy qua, có thể
          nhận vé và nhận hàng chuyển phát giữa Sóc Trăng – Sài Gòn
        </p>
        <div className="flex flex-col gap-y-2 my-5 mx-5 md:mx-[120px]">
          <img className="md:w-[1200px] rounded-xl" src={Bus} alt="dich vu" />
          <p className="md:items-start md:text-start">
            Mỹ Duyên là thương hiệu vận tải chuyên tuyến Sài Gòn – Sóc Trăng.
            <br /> Dịch vụ xe khách chất lượng cao tuyến Sài Gòn – Sóc Trăng.
            <br /> Chạy liên tục mỗi 60 phút 1 chuyến. Tổng đài đặt vé vui lòng
            liên hệ hotline 02862 77 55 44 –  0903 861 192
          </p>
          <p className="md:items-start md:text-start">
            Mỹ Duyên là thương hiệu vận tải chuyên tuyến Sài Gòn – Sóc Trăng.
            <br /> Dịch vụ xe khách chất lượng cao tuyến Sài Gòn – Sóc Trăng.
            <br /> Chạy liên tục mỗi 60 phút 1 chuyến. Tổng đài đặt vé vui lòng
            liên hệ hotline 02862 77 55 44 –  0903 861 192
          </p>
          <p className="md:items-start md:text-start">
            Mỹ Duyên là thương hiệu vận tải chuyên tuyến Sài Gòn – Sóc Trăng.
            <br /> Dịch vụ xe khách chất lượng cao tuyến Sài Gòn – Sóc Trăng.
            <br /> Chạy liên tục mỗi 60 phút 1 chuyến. Tổng đài đặt vé vui lòng
            liên hệ hotline 02862 77 55 44 –  0903 861 192
          </p>
        </div>
        <h1 className="h1">Dịch vụ vận tải hàng hóa</h1>
        <p>
          Mỹ Duyên có văn phòng thuận tiện trên các khu vực xe chạy qua, có thể
          nhận vé và nhận hàng chuyển phát giữa Sóc Trăng – Sài Gòn
        </p>
        <div className="flex flex-col gap-y-2 my-5 mx-5 md:mx-[120px]">
          <img className="md:w-[1200px] rounded-xl" src={Service} alt="dich vu" />
          <p className="md:items-start md:text-start">
            Mỹ Duyên là thương hiệu vận tải chuyên tuyến Sài Gòn – Sóc Trăng.
            <br /> Dịch vụ xe khách chất lượng cao tuyến Sài Gòn – Sóc Trăng.
            <br /> Chạy liên tục mỗi 60 phút 1 chuyến. Tổng đài đặt vé vui lòng
            liên hệ hotline 02862 77 55 44 –  0903 861 192
          </p>
          <p className="md:items-start md:text-start">
            Mỹ Duyên là thương hiệu vận tải chuyên tuyến Sài Gòn – Sóc Trăng.
            <br /> Dịch vụ xe khách chất lượng cao tuyến Sài Gòn – Sóc Trăng.
            <br /> Chạy liên tục mỗi 60 phút 1 chuyến. Tổng đài đặt vé vui lòng
            liên hệ hotline 02862 77 55 44 –  0903 861 192
          </p>
          <p className="md:items-start md:text-start">
            Mỹ Duyên là thương hiệu vận tải chuyên tuyến Sài Gòn – Sóc Trăng.
            <br /> Dịch vụ xe khách chất lượng cao tuyến Sài Gòn – Sóc Trăng.
            <br /> Chạy liên tục mỗi 60 phút 1 chuyến. Tổng đài đặt vé vui lòng
            liên hệ hotline 02862 77 55 44 –  0903 861 192
          </p>
        </div>
        <BackToTop></BackToTop>
      </div>
    );
}

export default Services;