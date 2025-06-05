// src/components/Home/StepCard.js
function StepCard({ icon, title, description, gradient }) {
  return (
    <div
      className={`
        bg-gradient-to-tr ${gradient}
        rounded-2xl shadow-xl p-6
        text-white flex flex-col items-center gap-y-3
        md:flex-row md:gap-x-4 md:w-[283px] md:h-[166px]
        font-roboto border border-white/20
        hover:shadow-2xl hover:scale-105 transition-all duration-300
      `}
    >
      <img className="w-12 h-12 md:w-[60px] md:h-[60px] drop-shadow-lg" src={icon} alt={`${title} icon`} />
      <div>
        <p className="font-bold md:text-[20px] md:mb-2 text-white drop-shadow-sm">{title}</p>
        <p className="text-white/90 md:text-[14px]">{description}</p>
      </div>
    </div>
  );
}

export default StepCard;
