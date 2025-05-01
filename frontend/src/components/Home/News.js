function News({image,title,description}) {
    return (
      <div className="flex flex-col gap-y-2 items-center leading-2">
        <img className="w-full" src={image} alt={title} />
        <h3 className="h1 md:text-[25px]">{title}</h3>
        <p>{description}</p>
        <button
          className="relative inline-flex items-center justify-center p-0.5 mb-2 me-2
           overflow-hidden text-sm font-medium text-gray-900 rounded-full
            group bg-gradient-to-br from-purple-600 to-blue-500
             group-hover:from-purple-600 group-hover:to-blue-500 hover:text-white
           dark:text-black 
           focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800"
        >
          <span
            className="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900
             rounded-full group-hover:bg-transparent group-hover:dark:bg-transparent"
          >
            Chi tiết
          </span>
        </button>
      </div>
    );
}

export default News;