import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import postService from '../../service/postService';
import BackToTop from '../Shared/BackToTop';
function NewsDetail() {
  const { postId } = useParams();
  const [post, setPost] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  

  useEffect(() => {
    const fetchPost = async () => {
      try {
        const data = await postService.getPostById(postId);
        setPost(data);

        setLoading(false);
      } catch (err) {
        setError(err.message);
        setLoading(false);
      }
    };

    fetchPost();
  }, [postId]);

  

  


  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gray-100">
        <p className="text-lg text-gray-600">Đang tải...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gray-100">
        <div className="bg-red-100 p-6 rounded-lg shadow-md">
          <p className="text-lg text-red-600">Lỗi: {error}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-100 py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-md">
        {post.image && (
          <div className="w-full overflow-hidden rounded-lg mb-6">
            <img
              className="w-full h-64 object-cover"
              src={post.image}
              alt={post.title}
            />
          </div>
        )}
        <h1 className="text-3xl font-bold text-gray-800 mb-4">
          {post.title}
        </h1>
        <p className="text-sm text-gray-500 mb-6">
          Ngày đăng: {new Date(post.date).toLocaleDateString('vi-VN')}
        </p>
        <div
          className="prose prose-lg text-gray-600"
          dangerouslySetInnerHTML={{ __html: post.content }}
        />
      </div>
      <BackToTop></BackToTop>
    </div>
  );
}
export default NewsDetail;