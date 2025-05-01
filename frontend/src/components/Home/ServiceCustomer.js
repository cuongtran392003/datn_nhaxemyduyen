function ServiceCustomer({ image, title, description, IconDescription = {} }) {
  const { icon, descriptionIcon } = IconDescription;
  return (
    <div className="flex flex-col items-center gap-y-2
    md:items-start md:w-[375px]">
      <img className="w-full" src={image} alt={title} />
      <h3 className="text-textmobileh1 md:text-[25px] font-semibold">{title}</h3>
      <p className="text-textmobile text-center md:text-start md:text-[14px]">{description}</p>
      <hr className="w-10 md:w-[233px] bg-red h-1" />
      {icon && descriptionIcon && (
        <div className="flex flex-row gap-x-2 items-center">
          <img src={icon} alt="icon" className="w-6 h-6" />
          <p className="text-textmobile md:text-[14px]">{descriptionIcon}</p>
        </div>
      )}
    </div>
  );
}
export default ServiceCustomer; 
