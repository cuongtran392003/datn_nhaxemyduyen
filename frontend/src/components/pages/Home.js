import Hero from "../Home/Hero";
import { steps, service, commodity } from "../../data/homeData";
import Routepopular from "../Home/Routepopular";
import StepCard from "../Home/StepCard";
import InstallApp from "../Home/InstallApp";
import ServicesCustomer from "../Home/ServiceCustomer";
import Commodity from "../Home/Comodity";
import NewsSlider from "../NewsSlider";
import { Hotline } from "../../assets/images";
import BackToTop from "../BackToTop";

function Home() {
  return (
    <div className="flex flex-col items-center py-10">
      <Hero />
      <h1 className="h1">Dễ dàng đặt vé trên Website của Mỹ Duyên</h1>
      <div className="grid grid-cols-4 gap-x-2 m-5">
        {steps.map((step, index) => (
          <StepCard
            key={index}
            {...step}
            icon={step.icon}
            title={step.title}
            description={step.description}
            gradient={step.gradient}
          />
        ))}
      </div>
      <h1 className="h1">Lộ trình phổ biến</h1>
      <Routepopular />
      <InstallApp />
      <h1 className="h1">Dịch vụ hành khách</h1>
      <div
        className="flex flex-col gap-y-2 m-5
      md:flex-row md:gap-x-5"
      >
        {service.map((service, index) => (
          <ServicesCustomer
            key={index}
            {...service}
            image={service.image}
            title={service.title}
            description={service.description}
            IconDescription={service.IconDescription}
          />
        ))}
      </div>
      {/* commodity */}
      <div
        className="bg-bluecustom my-5 text-white text-center font-poppins p-2
      md:h-[549px] md:px-[120px] md:py-[54px]"
      >
        <h1 className="font-semibold my-5 md:text-[43px]">Dịch vụ hàng hóa</h1>
        <div className="grid grid-cols-3 gap-x-2 md:gap-x-3">
          {commodity.map((commodity, index) => (
            <Commodity
              key={index}
              {...commodity}
              image={commodity.image}
              title={commodity.title}
              description={commodity.description}
            />
          ))}
        </div>
      </div>
      {/* hotline */}
      <div className="flex gap-x-3 my-5 px-5">
        <img className="w-60 md:w-[657px]" src={Hotline} alt="hotline" />
        <div>
          <h1 className="h1">Hotline Dịch Vụ</h1>
          <p>
            Hotline dịch vụ của Mỹ Duyên hoạt động 24/7. Chúng tôi luôn sẵn sàng
            phục vụ khách hàng
          </p>
          <div className="flex items-center gap-x-2 my-2">
            <div className="p-1 md:p-3 bg-bluecustom text-white rounded-full">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                strokeWidth="1.5"
                stroke="currentColor"
                className="size-4"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"
                />
              </svg>
            </div>
            <p>Hotline đặt vé: 1900 6746</p>
          </div>
          <div className="flex items-center gap-x-2 my-2">
            <div className="p-1 md:p-3  bg-bluecustom text-white rounded-full">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                strokeWidth="1.5"
                stroke="currentColor"
                className="size-4"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"
                />
              </svg>
            </div>
            <p>Hotline gửi hàng: 1900 0257</p>
          </div>
          <div className="flex items-center gap-x-2 my-2">
            <div className="p-1 md:p-3  bg-bluecustom text-white rounded-full">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                strokeWidth="1.5"
                stroke="currentColor"
                className="size-4"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"
                />
              </svg>
            </div>
            <p>Chăm sóc khách hàng: 1900 6746</p>
          </div>
          <div className="flex items-center gap-x-2 my-2">
            <div className="p-1 md:p-3  bg-bluecustom text-white rounded-full">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                strokeWidth="1.5"
                stroke="currentColor"
                className="size-4"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"
                />
              </svg>
            </div>
            <p>Xử lý khiếu nại: 02439 937 099</p>
          </div>
        </div>
      </div>
      {/* news */}
      <h1 className="h1">Tin tức nổi bật</h1>
      <NewsSlider />
      {/* {BackToTop} */}
      <BackToTop/>
    </div>
  );
}

export default Home;
