function Commodity({image,title,description}) {
    return (
      <div
        className="
      md:w-[378px] md:h-[230px] md:justify-center md:p-10 "
      >
        <div className="flex flex-col gap-y-2 items-center p-2 border rounded-xl">
          <img
            className="w-6 h-6 md:w-40 md:h-20 md:my-2"
            src={image}
            alt={title}
          />
          <h3 className="font-semibold">{title}</h3>
          <p className="md:text-[14px]">{description}</p>
        </div>
      </div>
    );
}

export default Commodity;