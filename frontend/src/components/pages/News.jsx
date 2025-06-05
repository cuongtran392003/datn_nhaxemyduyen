import HeroPage from "../Layout/HeroPage";
import BackToTop from "../Shared/BackToTop";
import { Bus } from "../../assets/images";
import LayOutNew from "../News/LayOutNew";

function News({ postid }) {
  return (
    <>
      <HeroPage pageTitle={"Tin tức"} />
      <div className="flex justify-center items-center py-8 px-2 md:px-0">
        <div className="w-full max-w-5xl bg-white rounded-3xl shadow-2xl p-6 md:p-10 flex flex-col md:flex-row gap-8 md:gap-12 relative overflow-hidden">
          <img
            className="w-full md:w-[420px] h-48 md:h-[420px] object-cover rounded-2xl shadow-md border-4 border-white"
            src={Bus}
            alt="tin tuc"
          />
          <div className="flex-1 flex flex-col justify-center font-montserrat text-start leading-9">
            <h1 className="text-[30px] md:text-[38px] font-bold text-bluecustom uppercase mb-2 tracking-wide drop-shadow-sm">
              Tin mới nhất
            </h1>
            <h2 className="text-lg md:text-2xl font-semibold text-gray-800 mb-3">
              Bến xe khách Vĩnh Niệm là bến xe đầu tiên của Hải Phòng
            </h2>
            <span className="text-base md:text-lg text-gray-600 mb-6 block font-roboto">
              Lorem Ipsum is simply dummy text of the printing and typesetting
              industry. Lorem Ipsum has been the industry's standard dummy text
              ever since the 1500s, when an unknown printer took a galley of
              type and scrambled it to make a type specimen book.
            </span>
            <button className="bg-gradient-to-r from-bluecustom to-cyan-400 text-white px-7 py-2.5 rounded-xl text-base md:text-lg font-semibold shadow-md hover:scale-105 hover:shadow-lg transition-all duration-200 ease-in-out w-fit">
              Xem thêm
            </button>
          </div>
        </div>
      </div>
      <LayOutNew />
      <BackToTop />
    </>
  );
}

export default News;