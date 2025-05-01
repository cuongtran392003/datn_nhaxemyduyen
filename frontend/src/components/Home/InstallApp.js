import BtnGgPlay from "../../assets/images/btnggplay.png";
import BtnAppStore from "../../assets/images/btnappstore.png";
import { Link } from "react-router-dom";
import PhoneImg from "../../assets/images/phone.png";
function InstallApp() {
    return (
      <div
        className="flex flex-row items-center justify-between px-5 text-white
        text-center leading-3 bg-bluecustom my-5 md:w-full md:h-[600px] md:leading-5"
      >
        <div className="flex flex-col gap-y-2 p-5 items-center md:text-center
        md:gap-y-5">
          <h2 className="h1 md:text-white">Cài đặt App đặt vé Mỹ Duyên</h2>
          <p>
            Cài đặt nhanh ứng dụng để đặt vé từ Sài Gòn - Sóc Trăng ngay hôm nay
          </p>
          <h3 className="md:text-[25px]">Tải App và cài đặt</h3>
          <Link to={"https://play.google.com/store/games?hl=vi&pli=1"}>
            <img
              className="w-20 md:w-[296px] md:h-[96px]"
              src={BtnGgPlay}
              alt="Google Play"
            />
          </Link>
          <Link to={"https://www.apple.com/app-store/"}>
            <img
              className="w-20 md:w-[296px] md:h-[96px]"
              src={BtnAppStore}
              alt="AppStore Play"
            />
          </Link>
        </div>
        <img
          className="w-20  md:w-[376px] md:h-full"
          src={PhoneImg}
          alt="Phone"
        />
      </div>
    );
}

export default InstallApp;