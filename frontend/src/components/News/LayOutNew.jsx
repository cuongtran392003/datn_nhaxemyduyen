import { useState, useEffect, useRef } from "react";
import postService from "../../service/postService";
import { Link } from "react-router-dom";
import { FaArrowRight } from "react-icons/fa";

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
  const lastPostsJson = useRef(JSON.stringify([])); // Đặt ngoài useEffect để giữ giá trị qua các lần render
  useEffect(() => {
    let pollingInterval;
    let isFirst = true;
    const fetchPosts = async () => {
      try {
        if (isFirst) setLoading(true);
        setError(null);
        const data = await postService.getPosts();
        const newPostsJson = JSON.stringify(data);
        if (newPostsJson !== lastPostsJson.current) {
          setPosts(data);
          lastPostsJson.current = newPostsJson;
        }
        if (isFirst) setLoading(false);
        isFirst = false;
      } catch (err) {
        setError(err.message);
        setLoading(false);
      }
    };
    fetchPosts(); // Lấy dữ liệu ngay khi component mount
    pollingInterval = setInterval(fetchPosts, 3000); // Lấy dữ liệu mỗi 10 giây
    return () => clearInterval(pollingInterval);
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
        <p className="text-gray-600 font-montserrat animate-pulse">
          Đang tải bài post...
        </p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-6xl mx-auto p-6 text-center">
        <p className="text-red-500 font-montserrat">Lỗi: {error}</p>
      </div>
    );
  }

  return (
    <div className="max-w-6xl mx-auto p-6">
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8 items-stretch">
        {currentPosts.map((post) => (
          <Link to={`/news/${post.id}`} key={post.id} className="group">
            <div className="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-5 flex flex-col h-full transition-all duration-300 hover:scale-105 hover:shadow-blue-200 cursor-pointer overflow-hidden">
              <img
                src={post.image}
                alt={post.title}
                className="w-full h-48 object-cover rounded-2xl shadow-md mb-3 group-hover:scale-105 transition-transform duration-300"
              />
              <div className="flex-1 flex flex-col justify-between">
                <h3 className="font-bold text-lg md:text-xl text-gray-800 dark:text-gray-100 mb-2 line-clamp-2 font-montserrat drop-shadow-sm text-center">
                  {post.title}
                </h3>
                <p className="text-gray-600 dark:text-gray-300 text-sm md:text-base mb-4 line-clamp-3 font-roboto text-center">
                  {post.description}
                </p>
                <button className="relative inline-flex items-center justify-center px-7 py-2.5 bg-gradient-to-r from-bluecustom to-cyan-400 text-white font-semibold rounded-full shadow-md group-hover:from-blue-500 group-hover:to-cyan-300 transition-all duration-200 ease-in-out text-base md:text-lg gap-2 mx-auto">
                  Chi tiết{" "}
                  <FaArrowRight className="text-white text-sm mt-0.5" />
                </button>
              </div>
              <div className="absolute -z-10 top-0 left-0 w-full h-full bg-gradient-to-br from-bluecustom/10 to-cyan-200/10 rounded-3xl opacity-80 group-hover:opacity-100 transition-all duration-300" />
            </div>
          </Link>
        ))}
      </div>
      {/* Pagination */}
      <div className="flex justify-center mt-10 space-x-2">
        <button
          onClick={() => handlePageChange(currentPage - 1)}
          disabled={currentPage === 1}
          className={`w-10 h-10 flex items-center justify-center border rounded-full font-bold text-lg transition-all duration-200 ${
            currentPage === 1
              ? "bg-gray-200 text-gray-400 cursor-not-allowed"
              : "bg-white hover:bg-bluecustom hover:text-white shadow"
          }`}
        >
          &lt;
        </button>
        {[...Array(totalPages)].map((_, index) => (
          <button
            key={index + 1}
            onClick={() => handlePageChange(index + 1)}
            className={`w-10 h-10 flex items-center justify-center border rounded-full font-bold text-lg transition-all duration-200 ${
              currentPage === index + 1
                ? "bg-bluecustom text-white shadow-lg border-bluecustom"
                : "bg-white hover:bg-bluecustom hover:text-white shadow"
            }`}
          >
            {index + 1}
          </button>
        ))}
        <button
          onClick={() => handlePageChange(currentPage + 1)}
          disabled={currentPage === totalPages}
          className={`w-10 h-10 flex items-center justify-center border rounded-full font-bold text-lg transition-all duration-200 ${
            currentPage === totalPages
              ? "bg-gray-200 text-gray-400 cursor-not-allowed"
              : "bg-white hover:bg-bluecustom hover:text-white shadow"
          }`}
        >
          &gt;
        </button>
      </div>
    </div>
  );
}

export default LayOutNew;
