import { useEffect, useState, useRef, useCallback } from "react";
import { Link } from "react-router-dom";

function News({ postid, image, title, description, fetchPosts }) {
  // Nếu là component danh sách, dùng polling:
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const postsRef = useRef([]);
  const isFirstFetch = useRef(true);

  // Polling fetchPosts nếu có props fetchPosts truyền vào (dùng cho trang danh sách)
  const fetchData = useCallback(async () => {
    if (!fetchPosts) return;
    try {
      if (isFirstFetch.current) setLoading(true);
      const data = await fetchPosts();
      if (JSON.stringify(data) !== JSON.stringify(postsRef.current)) {
        postsRef.current = data;
        setPosts(data);
      }
    } catch (err) {
      setError("Đã xảy ra lỗi khi tải dữ liệu: " + err.message);
    } finally {
      if (isFirstFetch.current) {
        setLoading(false);
        isFirstFetch.current = false;
      }
    }
  }, [fetchPosts]);

  useEffect(() => {
    if (!fetchPosts) return;
    fetchData();
    const interval = setInterval(fetchData, 30000);
    return () => clearInterval(interval);
  }, [fetchData, fetchPosts]);

  // Nếu là trang chi tiết hoặc không truyền fetchPosts thì render như cũ
  if (!fetchPosts) {
    return (
      <Link to={`/news/${postid}`}>
        <div className="flex flex-col gap-y-4 items-center bg-white rounded-xl shadow-lg p-4 transition-transform duration-300 hover:scale-105 cursor-pointer font-roboto border border-gray-100">
          <div className="w-full overflow-hidden rounded-lg">
            <img
              className="w-full h-48 object-cover transition-transform duration-300 hover:scale-110"
              src={image}
              alt={title}
            />
          </div>
          <h3 className="text-xl md:text-2xl font-bold text-gray-900 text-center line-clamp-2">
            {title}
          </h3>
          <p className="text-gray-600 text-sm md:text-base text-center line-clamp-3">
            {description}
          </p>
          <button className="relative inline-flex items-center justify-center px-6 py-2 overflow-hidden text-sm font-semibold text-white bg-gradient-to-br from-purple-600 to-blue-500 rounded-full hover:from-purple-500 hover:to-blue-400 focus:ring-4 focus:outline-none focus:ring-blue-300 transition-all duration-300 shadow-lg">
            Chi tiết
          </button>
        </div>
      </Link>
    );
  }

  // Nếu là trang danh sách, render danh sách posts
  if (loading) {
    return <div className="text-center p-5">Đang tải dữ liệu...</div>;
  }
  if (error) {
    return <div className="text-center p-5 text-red-500">{error}</div>;
  }
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-7 m-5 font-roboto">
      {posts.map((post) => (
        <Link key={post.postid} to={`/news/${post.postid}`}>
          <div className="flex flex-col gap-y-4 items-center bg-white rounded-xl shadow-lg p-4 transition-transform duration-300 hover:scale-105 cursor-pointer font-roboto border border-gray-100">
            <div className="w-full overflow-hidden rounded-lg">
              <img
                className="w-full h-48 object-cover transition-transform duration-300 hover:scale-110"
                src={post.image}
                alt={post.title}
              />
            </div>
            <h3 className="text-xl md:text-2xl font-bold text-gray-900 text-center line-clamp-2">
              {post.title}
            </h3>
            <p className="text-gray-600 text-sm md:text-base text-center line-clamp-3">
              {post.description}
            </p>
            <button className="relative inline-flex items-center justify-center px-6 py-2 overflow-hidden text-sm font-semibold text-white bg-gradient-to-br from-purple-600 to-blue-500 rounded-full hover:from-purple-500 hover:to-blue-400 focus:ring-4 focus:outline-none focus:ring-blue-300 transition-all duration-300 shadow-lg">
              Chi tiết
            </button>
          </div>
        </Link>
      ))}
    </div>
  );
}

export default News;
