import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation, Pagination, Autoplay } from "swiper/modules";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";

import News from "./Home/News";
import { news } from "../data/homeData";

const NewsSlider = () => {
  return (
    <section className="w-full px-4 py-6">
      <Swiper
        modules={[Navigation, Pagination, Autoplay]}
        spaceBetween={20}
        loop={true}
        slidesPerView={1}
        breakpoints={{
          640: { slidesPerView: 2 },
          1024: { slidesPerView: 3 },
        }}
      >
        {news.map((item, index) => (
          <SwiperSlide key={index}>
            <News {...item} />
          </SwiperSlide>
        ))}
      </Swiper>
    </section>
  );
};

export default NewsSlider;
