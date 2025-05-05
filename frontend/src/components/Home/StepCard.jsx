// src/components/Home/StepCard.js
function StepCard({ icon, title, description, gradient }) {
  return (
    <div
      className={`
        bg-gradient-to-tr ${gradient}
        rounded-xl shadow-xl shadow-gray-500 p-5
        text-white flex flex-col items-center gap-y-3
        md:flex-row md:gap-x-2 md:w-[283px] md:h-[166px]
      `}
    >
      <img className="w-10 h-10 md:w-[60px] md:h-[60px]" src={icon} alt={`${title} icon`} />
      <div>
        <p className="font-semibold md:text-[20px] md:mb-2">{title}</p>
        <p className="text-textmobile md:text-[14px]">{description}</p>
      </div>
    </div>
  );
}

export default StepCard;
