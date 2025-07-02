import React, { useState, useEffect } from 'react';
import { useNotification } from '../contexts/NotificationContext';

const ToastNotification = () => {
  const { notifications } = useNotification();
  const [visibleToasts, setVisibleToasts] = useState([]);
  useEffect(() => {
    // Hiển thị toast cho thông báo mới (trong vòng 10 giây)
    const recentNotifications = notifications.filter(notification => {
      const now = new Date();
      const notificationTime = new Date(notification.timestamp);
      const diffInSeconds = (now - notificationTime) / 1000;
      return diffInSeconds <= 10; // Tăng thời gian để dễ thấy
    });

    setVisibleToasts(recentNotifications.slice(0, 3)); // Chỉ hiển thị tối đa 3 toast

    // Auto hide toast sau 6 giây
    if (recentNotifications.length > 0) {
      const timer = setTimeout(() => {
        setVisibleToasts([]);
      }, 6000);

      return () => clearTimeout(timer);
    }
  }, [notifications]);

  const getToastStyles = (type) => {
    const baseStyles = "flex items-start p-4 mb-3 rounded-lg shadow-lg border-l-4 toast-enter";
    
    switch (type) {
      case 'success':
        return `${baseStyles} bg-green-50 border-green-500 text-green-800`;
      case 'error':
        return `${baseStyles} bg-red-50 border-red-500 text-red-800`;
      case 'warning':
        return `${baseStyles} bg-yellow-50 border-yellow-500 text-yellow-800`;
      case 'info':
        return `${baseStyles} bg-blue-50 border-blue-500 text-blue-800`;
      default:
        return `${baseStyles} bg-gray-50 border-gray-500 text-gray-800`;
    }
  };

  const getIcon = (type) => {
    switch (type) {
      case 'success':
        return (
          <svg className="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
          </svg>
        );
      case 'error':
        return (
          <svg className="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        );
      case 'warning':
        return (
          <svg className="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L12.732 4.5c-.77-.833-2.694-.833-3.464 0L2.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        );
      case 'info':
        return (
          <svg className="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        );
      default:
        return (
          <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
        );
    }
  };

  if (visibleToasts.length === 0) return null;

  return (
    <div className="fixed top-20 right-4 z-50 w-80 space-y-2">
      {visibleToasts.map((toast) => (
        <div key={toast.id} className={getToastStyles(toast.type)}>
          <div className="flex-shrink-0 mr-3">
            {getIcon(toast.type)}
          </div>
          <div className="flex-1">
            <p className="font-semibold text-sm mb-1">{toast.title}</p>
            <p className="text-sm opacity-90 line-clamp-2">{toast.message}</p>
            {toast.details && toast.details.seats && (
              <p className="text-xs mt-1 opacity-75">
                Ghế: {toast.details.seats.join(', ')}
              </p>
            )}
          </div>
          <button
            onClick={() => setVisibleToasts(prev => prev.filter(t => t.id !== toast.id))}
            className="flex-shrink-0 ml-2 text-gray-400 hover:text-gray-600"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      ))}
    </div>
  );
};

export default ToastNotification;
