import { HeroImg } from "../../assets/images";
import { Bus } from "../../assets/images";
import HeroPage from "../HeroPage";
import BackToTop from "../BackToTop";
function About() {
  return (
    <div className="text-center">
      <HeroPage pageTitle={"Về chúng tôi"}></HeroPage>
      <h1 className="h1">Đôi nét về Mỹ Duyên</h1>
      <div className="flex gap-x-2 m-5">
        <img
          className="w-60 rounded-xl md:w-[569px] md:h-[510px]"
          src={HeroImg}
          alt="Đôi nét vễ Mỹ Duyên"
        />
        <p>
          Lorem Ipsum is simply dummy text of the printing and typesetting
          industry. Lorem Ipsum has been the industry's standard dummy text ever
          since the 1500s, when an unknown printer took a galley of type and
          scrambled it to make a type specimen book.
        </p>
      </div>
      <h1 className="h1">Hệ thống văn phòng</h1>
      {/* {van phong} */}
      <div className="m-5 flex gap-x-2 items-center">
        <article className="flex flex-col gap-y-2 items-center">
          <div>
            <h2 className="text-[14px] md:text-textdesktop font-semibold ">
              Tại Sóc Trăng
            </h2>
            <p className="flex gap-x-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                strokeWidth="1.5"
                stroke="currentColor"
                className="size-6"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"
                />
              </svg>
              Bến xe Trần Đề : 1/5, Thị trấn Trần Đề, Trần Đề – 02996 512 512
            </p>
          </div>
          <div>
            <h2 className="text-[14px] md:text-textdesktop font-semibold">
              Tại Sài Gòn
            </h2>
            <p className="flex gap-x-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                strokeWidth="1.5"
                stroke="currentColor"
                className="size-6"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"
                />
              </svg>
              Bến xe Miền Tây: Phòng vé số 16, 395 Kinh Dương Vương, Phường An
              Lạc, Quận Bình Tân – 02862 77 55 44, 0903 861 192
            </p>
          </div>
        </article>
        <img
          className="w-60 rounded-xl md:w-[569px] md:h-[510px]"
          src={Bus}
          alt="he thong van phong"
        />
      </div>
      <div className="flex flex-col items-center gap-y-2 m-5">
        <img
          className="w-60 rounded-xl md:w-[1200px] md:h-[676px]"
          src={Bus}
          alt="Liên hệ"
        />
        <p className="flex gap-x-2 items-center">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            strokeWidth="1.5"
            stroke="currentColor"
            className="size-6"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"
            />
          </svg>
          Hotline tư vấn và đặt vé: 02862 77 55 44 – 0903 861 192 – 02996 512
          512
        </p>
        <p className="flex gap-x-2 items-center ">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            strokeWidth="1.5"
            stroke="currentColor"
            className="size-6"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
            />
          </svg>
          Thời gian hoạt động: Từ 4h sáng đến 24h hàng ngày
        </p>
        <p className="flex gap-x-2 items-center">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            strokeWidth="1.5"
            stroke="currentColor"
            className="size-6"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"
            />
          </svg>
          Website: nhaxemyduyen.com
        </p>
      </div>
      <BackToTop></BackToTop>
    </div>
  );
}

export default About;