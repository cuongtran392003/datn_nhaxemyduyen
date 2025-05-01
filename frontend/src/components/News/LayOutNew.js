import {useState, useEffect} from "react";
import { newData } from "../../data/homeData";
function LayOutNew() {
    const [currentPage, setCurrentPage]=useState(1);
    const [itemPerPages,setItemPerPage]=useState(9);

    useEffect(()=>{
        const updateItemPages=()=>{
            const width=window.innerWidth;
            if(width<640){
                setItemPerPage(3);
            } else if(width <768){
                setItemPerPage(6);
            } else {
                setItemPerPage(9);
            }
        };
        updateItemPages();
        window.addEventListener("resize",updateItemPages);
        return ()=>window.removeEventListener("resize",updateItemPages);
    },[]);
    const totalPages=Math.ceil(newData.length/itemPerPages);
    const handlePageChange = (page) =>{
        if(page>=1 && page<=totalPages){
            setCurrentPage(page);
        }
    };
    const startIndex=(currentPage-1)*itemPerPages;
    const currentNews=newData.slice(startIndex,startIndex+itemPerPages);
    return (
      <div className="max-w-6xl mx-auto p-6">
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
          {currentNews.map((news) => (
            <div
              key={news.id}
              className="bg-white shadow-md rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-200"
            >
              <img
                src={news.image}
                alt={news.title}
                className="w-full h-48 object-cover"
              />
              <div className="p-4">
                <h3 className="font-semibold text-lg text-gray-800 mb-2 line-clamp-2">
                  {news.title}
                </h3>
                <p className="text-gray-600 text-sm line-clamp-3">
                  {news.description}
                </p>
              </div>
            </div>
          ))}
        </div>

        {/* Pagination */}
        <div className="flex justify-center mt-8 space-x-2">
          <button
            onClick={() => handlePageChange(currentPage - 1)}
            disabled={currentPage === 1}
            className={`px-3 py-1 border rounded ${
              currentPage === 1
                ? "bg-gray-200 cursor-not-allowed"
                : "hover:bg-blue-500 hover:text-white"
            }`}
          >
            Prev
          </button>

          {[...Array(totalPages)].map((_, index) => (
            <button
              key={index + 1}
              onClick={() => handlePageChange(index + 1)}
              className={`w-8 h-8 border rounded ${
                currentPage === index + 1
                  ? "bg-blue-500 text-white"
                  : "hover:bg-blue-500 hover:text-white"
              }`}
            >
              {index + 1}
            </button>
          ))}

          <button
            onClick={() => handlePageChange(currentPage + 1)}
            disabled={currentPage === totalPages}
            className={`px-3 py-1 border rounded ${
              currentPage === totalPages
                ? "bg-gray-200 cursor-not-allowed"
                : "hover:bg-blue-500 hover:text-white"
            }`}
          >
            Next
          </button>
        </div>
      </div>
    );
}

export default LayOutNew;