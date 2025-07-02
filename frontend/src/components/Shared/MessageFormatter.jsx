import React from "react";

/**
 * MessageFormatter - Component để định dạng và hiển thị tin nhắn chatbot với các định dạng phong phú
 * Hỗ trợ:
 * - Xuống dòng (\n)
 * - In đậm (*văn bản*)
 * - In nghiêng (_văn bản_)
 * - Danh sách (- hoặc * ở đầu dòng)
 * - Link tự động (http://, https://)
 */
const MessageFormatter = ({ text }) => {
  if (!text) return null;
  
  // Tách các dòng trong tin nhắn
  const lines = text.split('\n');
  
  // Xử lý từng dòng tin nhắn
  const formattedLines = lines.map((line, lineIndex) => {
    // Kiểm tra nếu dòng bắt đầu bằng - hoặc * để tạo danh sách
    if (line.trim().startsWith('- ') || line.trim().startsWith('* ')) {
      return (
        <li key={lineIndex} className="message-list-item">
          {formatInlineContent(line.trim().substring(2))}
        </li>
      );
    }

    // Kiểm tra nếu dòng trống
    if (line.trim() === '') {
      return <br key={lineIndex} />;
    }

    // Xử lý định dạng inline cho dòng thường
    return <div key={lineIndex} className="message-line">{formatInlineContent(line)}</div>;
  });

  // Xử lý định dạng nội dòng (bold, italic, link)
  function formatInlineContent(text) {
    if (!text) return "";
    
    // Mảng để chứa các phần đã xử lý
    let result = [];
    let currentIndex = 0;
    
    // Tìm và xử lý các mẫu định dạng
    
    // Xử lý text in đậm (*text*)
    let boldRegex = /\*(.*?)\*/g;
    let boldMatch;
    let lastBoldIndex = 0;
    
    while ((boldMatch = boldRegex.exec(text)) !== null) {
      // Thêm text thường trước phần in đậm
      if (boldMatch.index > lastBoldIndex) {
        result.push(processLinks(text.substring(lastBoldIndex, boldMatch.index)));
      }
      
      // Thêm phần in đậm
      result.push(<strong key={`bold-${currentIndex++}`}>{boldMatch[1]}</strong>);
      
      lastBoldIndex = boldMatch.index + boldMatch[0].length;
    }
    
    // Thêm phần còn lại của text
    if (lastBoldIndex < text.length) {
      result.push(processLinks(text.substring(lastBoldIndex)));
    }
    
    // Nếu không có định dạng in đậm, xử lý toàn bộ text cho links
    if (result.length === 0) {
      result.push(processLinks(text));
    }
    
    return result;
  }
  
  // Xử lý links trong text
  function processLinks(text) {
    if (!text) return "";
    
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    const parts = text.split(urlRegex);
    
    if (parts.length === 1) return text;
    
    return parts.map((part, i) => {
      if (part.match(urlRegex)) {
        return (
          <a 
            key={`link-${i}`} 
            href={part} 
            target="_blank" 
            rel="noopener noreferrer"
            className="message-link"
          >
            {part}
          </a>
        );
      }
      return part;
    });
  }

  // Kiểm tra nếu tin nhắn có chứa danh sách
  const hasListItems = lines.some(line => 
    line.trim().startsWith('- ') || line.trim().startsWith('* ')
  );

  // Nếu có danh sách, bọc các mục danh sách trong thẻ <ul>
  if (hasListItems) {
    const formattedContent = [];
    let currentList = [];
    let inList = false;

    lines.forEach((line, index) => {
      if (line.trim().startsWith('- ') || line.trim().startsWith('* ')) {
        if (!inList) {
          inList = true;
        }
        currentList.push(
          <li key={index} className="message-list-item">
            {formatInlineContent(line.trim().substring(2))}
          </li>
        );
      } else {
        // Nếu không phải mục danh sách và đang trong danh sách, kết thúc danh sách hiện tại
        if (inList) {
          formattedContent.push(<ul key={`list-${formattedContent.length}`} className="message-list">{currentList}</ul>);
          currentList = [];
          inList = false;
        }
        
        // Thêm dòng thường
        if (line.trim() === '') {
          formattedContent.push(<br key={index} />);
        } else {
          formattedContent.push(
            <div key={index} className="message-line">
              {formatInlineContent(line)}
            </div>
          );
        }
      }
    });

    // Nếu kết thúc tin nhắn mà vẫn còn danh sách đang mở
    if (inList && currentList.length > 0) {
      formattedContent.push(<ul key={`list-final`} className="message-list">{currentList}</ul>);
    }

    return <div className="formatted-message">{formattedContent}</div>;
  }

  // Nếu không có danh sách, trả về các dòng đã định dạng
  return <div className="formatted-message">{formattedLines}</div>;
};

export default MessageFormatter;
