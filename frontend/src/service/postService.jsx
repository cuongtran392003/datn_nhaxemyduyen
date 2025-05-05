import axios from "axios";

const BASE_URL = "http://localhost:8000/wp-json/wp/v2"; // URL WordPress của bạn

const postService = {
  // Lấy danh sách bài post
  getPosts: async (params = {}) => {
    try {
      const response = await axios.get(`${BASE_URL}/posts`, {
        params: {
          per_page: 50, // Mặc định lấy 50 bài
          ...params, // Tham số bổ sung
        },
      });
      // Chuẩn hóa dữ liệu, lấy URL ảnh thực tế
      const formattedPosts = await Promise.all(
        response.data.map(async (post) => {
          let imageUrl = "https://picsum.photos/400/300"; // Ảnh mặc định
          if (post.featured_media) {
            try {
              const mediaResponse = await axios.get(
                `${BASE_URL}/media/${post.featured_media}`
              );
              imageUrl = mediaResponse.data.source_url || imageUrl;
            } catch (mediaError) {
              console.warn(
                `Không thể lấy ảnh cho bài post ${post.id}:`,
                mediaError
              );
            }
          }
          return {
            id: post.id,
            title: post.title.rendered,
            description: post.excerpt.rendered.replace(/<[^>]+>/g, ""), // Xóa tag HTML
            image: imageUrl,
          };
        })
      );
      return formattedPosts;
    } catch (error) {
      console.error("Error fetching posts:", error);
      throw new Error(
        error.response?.data?.message ||
          "Không thể lấy bài post từ WordPress API"
      );
    }
  },

  // Lấy bài post nổi bật (bài đầu tiên)
  getFeaturedPost: async () => {
    try {
      const response = await axios.get(`${BASE_URL}/posts`, {
        params: {
          per_page: 1,
        },
      });
      const post = response.data[0];
      let imageUrl = "https://picsum.photos/400/300"; // Ảnh mặc định
      if (post.featured_media) {
        try {
          const mediaResponse = await axios.get(
            `${BASE_URL}/media/${post.featured_media}`
          );
          imageUrl = mediaResponse.data.source_url || imageUrl;
        } catch (mediaError) {
          console.warn(
            `Không thể lấy ảnh cho bài post nổi bật ${post.id}:`,
            mediaError
          );
        }
      }
      return {
        id: post.id,
        title: post.title.rendered,
        description: post.excerpt.rendered.replace(/<[^>]+>/g, ""),
        image: imageUrl,
      };
    } catch (error) {
      console.error("Error fetching featured post:", error);
      throw new Error(
        error.response?.data?.message || "Không thể lấy bài post nổi bật"
      );
    }
  },

  getPostById: async (postid) => {
    try {
      const response = await axios.get(`${BASE_URL}/posts/${postid}`);
      const post = response.data;
      let imageUrl = "https://picsum.photos/400/300"; // Ảnh mặc định
      if (post.featured_media) {
        try {
          const mediaResponse = await axios.get(
            `${BASE_URL}/media/${post.featured_media}`
          );
          imageUrl = mediaResponse.data.source_url || imageUrl;
        } catch (mediaError) {
          console.warn(
            `Không thể lấy ảnh cho bài post ${post.id}:`,
            mediaError
          );
        }
      }
      return {
        id: post.id,
        title: post.title.rendered,
        content: post.content.rendered,
        data: post.data,
        image: imageUrl,
      };
    } catch (error) {
      console.error("Lỗi khi lấy bài viết: ", error);
      throw new Error(
        error.response?.data?.message ||
          "Không thể lấy bài viết từ WordPress API"
      );
    }
  },

  // Lấy bài viết nổi bật
  getFeaturedPost: async () => {
    try {
      const response = await axios.get(`${BASE_URL}/posts`, {
        params: {
          per_page: 1,
        },
      });
      const post = response.data[0];
      let imageUrl = "https://picsum.photos/400/300";
      if (post.featured_media) {
        try {
          const mediaResponse = await axios.get(
            `${BASE_URL}/media/${post.featured_media}`
          );
          imageUrl = mediaResponse.data.source_url || imageUrl;
        } catch (mediaError) {
          console.warn(
            `Không thể lấy ảnh cho bài post nổi bật ${post.id}:`,
            mediaError
          );
        }
      }
      return {
        id: post.id,
        title: post.title.rendered,
        description: post.excerpt.rendered.replace(/<[^>]+>/g, ""),
        image: imageUrl,
      };
    } catch (error) {
      console.error("Lỗi khi lấy bài post nổi bật:", error);
      throw new Error(
        error.response?.data?.message || "Không thể lấy bài post nổi bật"
      );
    }
  },

  

  

};

export default postService;
