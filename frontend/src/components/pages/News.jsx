import HeroPage from "../Layout/HeroPage";
import BackToTop from "../Shared/BackToTop";
import { Bus } from "../../assets/images";
import LayOutNew from "../News/LayOutNew";

function News({postid}) {
  
    return (
      <>
        <HeroPage pageTitle={"Tin tức"}></HeroPage>
        <div
          className="flex flex-col gap-y-5 items-center m-5
         md:flex-row md:gap-x-5 md:mx-[190px]"
        >
          <img
            className="w-60 md:w-[629px] md:h-[420px] rounded-xl"
            src={Bus}
            alt="tin tuc"
          />
          <div className="text-start leading-9">
            <h1 className="text-[35px] text-bluecustom uppercase">Tin mới nhất</h1>
            <h2 className="text-textmobileh1 md:text-[21px]">
              Bến xe khách Vĩnh Niệm là bến xe đầu tiên của Hải Phòng
            </h2>
            <span className="text-textmobile md:text-[14px]">
              Lorem Ipsum is simply dummy text of the printing and typesetting
              industry. Lorem Ipsum has been the industry's standard dummy text
              ever since the 1500s, when an unknown printer took a galley of
              type and scrambled it to make a type specimen book.
            </span>
            <button className="bg-bluecustom text-white p-2 rounded-xl text-textmobile md:text-[14px]">
              Xem thêm
            </button>
          </div>
        </div>
        <LayOutNew></LayOutNew>
        <BackToTop></BackToTop>
      </>
    );
}

export default News;