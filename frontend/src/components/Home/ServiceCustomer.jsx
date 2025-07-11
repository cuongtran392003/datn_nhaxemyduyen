function ServiceCustomer({ image, title, description, IconDescription = {} }) {
  const { icon, descriptionIcon } = IconDescription;
  return (
    <div className="flex flex-col items-center md:items-start max-w-sm mx-auto bg-white rounded-2xl shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-300 p-6 font-roboto">
      <img
        className="w-full h-48 object-cover rounded-xl mb-4 drop-shadow-lg"
        src={image}
        alt={title}
      />
      <h3 className="text-xl md:text-2xl font-bold text-gray-900 mb-2 text-center md:text-left">
        {title}
      </h3>
      <p className="text-gray-600 text-sm md:text-base text-center md:text-left mb-4 leading-relaxed">
        {description}
      </p>
      <hr className="w-12 md:w-56 h-1 bg-red-500 rounded mb-4" />
      {icon && descriptionIcon && (
        <div className="flex flex-row items-center gap-x-3">
          <img src={icon} alt="icon" className="w-7 h-7 drop-shadow-md" />
          <p className="text-gray-700 text-sm md:text-base">
            {descriptionIcon}
          </p>
        </div>
      )}
    </div>
  );
}

export default ServiceCustomer;
