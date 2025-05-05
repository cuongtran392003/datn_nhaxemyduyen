import { useState, useEffect } from "react";
import postService from "../../service/postService";
import {Link} from "react-router-dom"
function LayOutNew() {
  const [currentPage, setCurrentPage] = useState(1);
  const [itemPerPages, setItemPerPage] = useState(9);
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  

  // Xử lý responsive cho số lượng bài post mỗi trang
  useEffect(() => {
    const updateItemPages = () => {
      const width = window.innerWidth;
      if (width < 640) {
        setItemPerPage(3);
      } else if (width < 768) {
        setItemPerPage(6);
      } else {
        setItemPerPage(9);
      }
    };
    updateItemPages();
    window.addEventListener("resize", updateItemPages);
    return () => window.removeEventListener("resize", updateItemPages);
  }, []);

  // Gọi API để lấy bài post
  useEffect(() => {
    const fetchPosts = async () => {
      try {
        setLoading(true);
        setError(null);
        const data = await postService.getPosts();
        setPosts(data);
        setLoading(false);
      } catch (err) {
        setError(err.message);
        setLoading(false);
      }
    };
    fetchPosts();
  }, []);



  // Tính toán phân trang
  const totalPages = Math.ceil(posts.length / itemPerPages);
  const handlePageChange = (page) => {
    if (page >= 1 && page <= totalPages) {
      setCurrentPage(page);
    }
  };
  const startIndex = (currentPage - 1) * itemPerPages;
  const currentPosts = posts.slice(startIndex, startIndex + itemPerPages);

  // Xử lý giao diện khi đang tải hoặc có lỗi
  if (loading) {
    return (
      <div className="max-w-6xl mx-auto p-6 text-center">
        <p className="text-gray-600">Đang tải bài post...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-6xl mx-auto p-6 text-center">
        <p className="text-red-500">Lỗi: {error}</p>
      </div>
    );
  }

  return (
    
    <div className="max-w-6xl mx-auto p-6 ">
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 
      gap-6 items-center cursor-pointer">
        {currentPosts.map((post) => (
          <Link to={`/news/${post.id}`} key={post.id}>
          <div
            className="bg-white shadow-md rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-200"
          >
            <img
              src={post.image}
              alt={post.title}
              className="w-full h-48 object-cover"
            />
            <div className="p-4">
              <h3 className="font-semibold text-lg text-gray-800 mb-2 line-clamp-2">
                {post.title}
              </h3>
              <p className="text-gray-600 text-sm line-clamp-3">
                {post.description}
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
          </div>
          </Link>
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
