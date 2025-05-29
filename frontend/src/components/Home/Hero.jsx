
import "react-datepicker/dist/react-datepicker.css";
import HeroImg from "../../assets/images/hero.png";
import SearchForm from "./components/SearchForm";


function Hero() {
  

  return (
    <div className="md:relative">
      <img
        className="md:w-full md:h-[622px] object-cover"
        src={HeroImg}
        alt="Hero"
      />
      <div className="rounded-xl shadow-xl shadow-gray-500 m-5 p-5 md:absolute md:h-[165px] md:bg-white md:w-[1200px] md:top-[400px] md:px-[72px] md:py-[50px]">
      <SearchForm/>
        </div>
    </div>
  );
}

export default Hero;
