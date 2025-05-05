
import {Link} from "react-router-dom"
function News({ postid,image, title, description }) {
  
  return (
    
      <Link to={`/news/${postid}`}>
      <div className="flex flex-col gap-y-4 items-center bg-white
      dark:bg-gray-800 rounded-xl shadow-lg p-4 
      transition-transform duration-300 hover:scale-105 cursor-pointer">
        <div className="w-full overflow-hidden rounded-lg">
          <img
            className="w-full h-48 object-cover transition-transform duration-300 hover:scale-110"
            src={image}
            alt={title}
          />
        </div>
        <h3 className="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100 text-center line-clamp-2">
          {title}
        </h3>
        <p className="text-gray-600 dark:text-gray-300 text-sm md:text-base text-center line-clamp-3">
          {description}
        </p>
        <button
          className="relative inline-flex items-center justify-center px-6 py-2
            overflow-hidden text-sm font-semibold text-white
            bg-gradient-to-br from-purple-600 to-blue-500
            rounded-full
            hover:from-purple-500 hover:to-blue-400
            focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800
            transition-all duration-300"
        >
          Chi tiết
        </button>
      </div>
      </Link>

  );
}

export default News;
