import HeroPage from "../HeroPage";
import BackToTop from "../BackToTop";
import Map from "../Map";
function Contact() {
    return (
      <>
        <HeroPage pageTitle={"Liên hệ"}></HeroPage>
        <div className="flex gap-x-5 m-5 items-center md:my-5 md:mx-[189px]">
          <div>
            <p>
              Need any kind of fixhero help?{" "}
              <span className="text-bluecustom">Send us a message</span>
            </p>
            <p>
              Proactively envisioned multimedia based expertisee cross-media
              growth strategies. Seamlessly visualize quality intellectual
              capital without superior collaboration.
            </p>
          </div>
          <form className="w-56 md:w-[633px] bg-bluecustom p-5 flex flex-col items-center
          rounded-xl">
            <div className="flex flex-col gap-y-5 md:flex-row md:gap-x-5">
              <input
                className="p-2 outline-none rounded-xl"
                type="text"
                placeholder="Nhập họ tên"
              />
              <input
                className="p-2 outline-none rounded-xl"
                type="text"
                placeholder="Số điện thoại"
              />
            </div>
            <input
              className="p-2 outline-none rounded-xl w-full my-5"
              type="text"
              placeholder="Email"
            />
            <input
              className="p-2 outline-none rounded-xl w-full my-5"
              type="text"
              placeholder="Ghi chú nội dung"
            />
            <button className="bg-white p-2 rounded-xl">Gửi tin nhắn</button>
          </form>
        </div>
        <Map></Map>
        <BackToTop></BackToTop>
      </>
    );
}

export default Contact;
