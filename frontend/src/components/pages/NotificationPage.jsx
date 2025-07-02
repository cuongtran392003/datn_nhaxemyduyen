import React from 'react';
import { useNotification } from '../contexts/NotificationContext';
import { useNavigate } from 'react-router-dom';

const NotificationPage = () => {
  const { notifications, markAsRead, markAllAsRead, removeNotification, clearAllNotifications } = useNotification();
  const navigate = useNavigate();

  const handleNotificationClick = (notification) => {
    markAsRead(notification.id);
    if (notification.actionUrl) {
      navigate(notification.actionUrl);
    }
  };

  const formatTime = (timestamp) => {
    const date = new Date(timestamp);
    return date.toLocaleString('vi-VN', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getNotificationIcon = (type) => {
    switch (type) {
      case 'success': return '✅';
      case 'error': return '❌';
      case 'warning': return '⚠️';
      case 'info': return 'ℹ️';
      default: return '🔔';
    }
  };

  const getBackgroundColor = (type, isRead) => {
    const baseColors = {
      success: isRead ? 'bg-green-50' : 'bg-green-100',
      error: isRead ? 'bg-red-50' : 'bg-red-100',
      warning: isRead ? 'bg-yellow-50' : 'bg-yellow-100',
      info: isRead ? 'bg-blue-50' : 'bg-blue-100'
    };
    return baseColors[type] || (isRead ? 'bg-gray-50' : 'bg-gray-100');
  };

  return (
    <div className="max-w-4xl mx-auto p-6">
      {/* Header */}
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-800">Thông báo</h1>
        <div className="flex space-x-3">
          {notifications.some(n => !n.isRead) && (
            <button
              onClick={markAllAsRead}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Đánh dấu đã đọc
            </button>
          )}
          {notifications.length > 0 && (
            <button
              onClick={clearAllNotifications}
              className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
            >
              Xóa tất cả
            </button>
          )}
        </div>
      </div>

      {/* Thống kê */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div className="bg-blue-50 p-4 rounded-lg">
          <div className="text-2xl font-bold text-blue-600">{notifications.length}</div>
          <div className="text-blue-700">Tổng thông báo</div>
        </div>
        <div className="bg-green-50 p-4 rounded-lg">
          <div className="text-2xl font-bold text-green-600">
            {notifications.filter(n => n.isRead).length}
          </div>
          <div className="text-green-700">Đã đọc</div>
        </div>
        <div className="bg-red-50 p-4 rounded-lg">
          <div className="text-2xl font-bold text-red-600">
            {notifications.filter(n => !n.isRead).length}
          </div>
          <div className="text-red-700">Chưa đọc</div>
        </div>
      </div>

      {/* Danh sách thông báo */}
      <div className="space-y-4">
        {notifications.length === 0 ? (
          <div className="text-center py-12">
            <svg className="w-24 h-24 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <h3 className="text-xl font-semibold text-gray-500 mb-2">Không có thông báo nào</h3>
            <p className="text-gray-400">Các thông báo mới sẽ xuất hiện tại đây</p>
          </div>
        ) : (
          notifications.map((notification) => (
            <div
              key={notification.id}
              className={`${getBackgroundColor(notification.type, notification.isRead)} p-6 rounded-lg shadow-sm border-l-4 ${
                notification.type === 'success' ? 'border-green-500' :
                notification.type === 'error' ? 'border-red-500' :
                notification.type === 'warning' ? 'border-yellow-500' :
                'border-blue-500'
              } transition-all duration-200 hover:shadow-md ${
                notification.actionUrl ? 'cursor-pointer' : ''
              }`}
              onClick={() => handleNotificationClick(notification)}
            >
              <div className="flex items-start justify-between">
                <div className="flex items-start space-x-4">
                  <span className="text-2xl">{getNotificationIcon(notification.type)}</span>
                  
                  <div className="flex-1">
                    <div className="flex items-center space-x-2 mb-2">
                      <h3 className="text-lg font-semibold text-gray-800">
                        {notification.title}
                      </h3>
                      {!notification.isRead && (
                        <span className="inline-block w-2 h-2 bg-blue-500 rounded-full"></span>
                      )}
                    </div>
                    
                    <p className="text-gray-600 mb-2">
                      {notification.message}
                    </p>
                    
                    {notification.details && (
                      <div className="bg-white bg-opacity-50 p-3 rounded-md mb-3">
                        <h4 className="font-medium text-gray-700 mb-2">Chi tiết:</h4>
                        <div className="space-y-1 text-sm text-gray-600">
                          {notification.details.seats && (
                            <p><strong>Ghế:</strong> {notification.details.seats.join(', ')}</p>
                          )}
                          {notification.details.totalAmount && (
                            <p><strong>Tổng tiền:</strong> {notification.details.totalAmount.toLocaleString('vi-VN')} VNĐ</p>
                          )}
                          {notification.details.departureTime && (
                            <p><strong>Giờ khởi hành:</strong> {new Date(notification.details.departureTime).toLocaleString('vi-VN')}</p>
                          )}
                          {notification.details.ticketCode && (
                            <p><strong>Mã vé:</strong> {notification.details.ticketCode}</p>
                          )}
                        </div>
                      </div>
                    )}
                    
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-400">
                        {formatTime(notification.timestamp)}
                      </span>
                      
                      {notification.actionText && notification.actionUrl && (
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            handleNotificationClick(notification);
                          }}
                          className="text-blue-600 hover:text-blue-800 text-sm font-medium"
                        >
                          {notification.actionText} →
                        </button>
                      )}
                    </div>
                  </div>
                </div>
                
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    removeNotification(notification.id);
                  }}
                  className="text-gray-400 hover:text-red-500 transition-colors p-2"
                  aria-label="Xóa thông báo"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
};

export default NotificationPage;
