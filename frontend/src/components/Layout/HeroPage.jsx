import { HeroImg } from "../../assets/images";
function HeroPage({pageTitle}) {
    return ( 
        <div>
            <div className="w-full relative">
                <img className="w-full" src={HeroImg} alt="hero" />
                <div className="absolute inset-0 bg-black opacity-50"></div>
                <p className="absolute top-1/2 translate-x-10 font-bold text-white md:text-[45px] uppercase">{pageTitle}</p>
            </div>
        </div>
     );
}

export default HeroPage;
