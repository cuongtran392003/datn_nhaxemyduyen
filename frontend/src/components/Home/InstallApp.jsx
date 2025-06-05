import BtnGgPlay from "../../assets/images/btnggplay.png";
import BtnAppStore from "../../assets/images/btnappstore.png";
import { Link } from "react-router-dom";
import PhoneImg from "../../assets/images/phone.png";
function InstallApp() {
    return (
      <div
        className="w-full flex flex-col md:flex-row items-center justify-center px-0 md:px-0 py-0 md:py-0 text-white bg-gradient-to-r from-bluecustom
         to-purple-600 my-8 rounded-3xl shadow-xl font-roboto min-h-[400px] md:min-h-[600px]"
      >
        <div className="flex flex-col gap-y-4 p-5 items-center justify-center md:items-center md:justify-center md:text-center md:gap-y-7 max-w-xl w-full">
          <h2 className="text-2xl md:text-4xl font-extrabold text-white mb-2 drop-shadow-lg">Cài đặt App đặt vé Mỹ Duyên</h2>
          <p className="text-base md:text-lg text-white/90 mb-2">Cài đặt nhanh ứng dụng để đặt vé từ Sài Gòn - Sóc Trăng ngay hôm nay</p>
          <h3 className="text-lg md:text-2xl font-semibold text-white/90 mb-4">Tải App và cài đặt</h3>
          <div className="flex gap-4">
            <a href="https://play.google.com/store/games?hl=vi&pli=1" target="_blank" rel="noopener noreferrer">
              <img className="w-32 md:w-[196px] md:h-[64px] hover:scale-105 transition-all duration-200" src={BtnGgPlay} alt="Google Play" />
            </a>
            <a href="https://www.apple.com/app-store/" target="_blank" rel="noopener noreferrer">
              <img className="w-32 md:w-[196px] md:h-[64px] hover:scale-105 transition-all duration-200" src={BtnAppStore} alt="AppStore Play" />
            </a>
          </div>
        </div>
        <div className="flex justify-center items-center w-full md:w-auto mt-8 md:mt-0">
          <img className="w-32 md:w-[320px] md:h-[520px] drop-shadow-2xl rounded-2xl object-contain" src={PhoneImg} alt="Phone" />
        </div>
      </div>
    );
}

export default InstallApp;