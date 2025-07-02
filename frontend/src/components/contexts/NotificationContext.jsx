import React, { createContext, useContext, useState, useEffect } from 'react';

const NotificationContext = createContext();

export const useNotification = () => {
  const context = useContext(NotificationContext);
  if (!context) {
    throw new Error('useNotification must be used within a NotificationProvider');
  }
  return context;
};

export const NotificationProvider = ({ children }) => {
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);

  // Load thông báo từ localStorage khi khởi tạo
  useEffect(() => {
    const savedNotifications = localStorage.getItem('notifications');
    if (savedNotifications) {
      const parsed = JSON.parse(savedNotifications);
      setNotifications(parsed);
      const unread = parsed.filter(n => !n.isRead).length;
      setUnreadCount(unread);
    }
  }, []);

  // Lưu thông báo vào localStorage mỗi khi có thay đổi
  useEffect(() => {
    localStorage.setItem('notifications', JSON.stringify(notifications));
  }, [notifications]);
  const addNotification = (notification) => {
    // Kiểm tra trùng lặp dựa trên nội dung
    const isDuplicate = notifications.some(existing => 
      existing.title === notification.title && 
      existing.message === notification.message &&
      Date.now() - new Date(existing.timestamp).getTime() < 60000 // Trong vòng 1 phút
    );

    if (isDuplicate) {
      console.log('Thông báo trùng lặp, bỏ qua');
      return;
    }

    const newNotification = {
      id: Date.now() + Math.random(),
      timestamp: new Date().toISOString(),
      isRead: false,
      type: 'success', // success, error, info, warning
      ...notification
    };    setNotifications(prev => [newNotification, ...prev]);
    setUnreadCount(prev => prev + 1);

    // Phát âm thanh thông báo
    playNotificationSound();

    // Trigger bell animation
    triggerBellAnimation();

    // Auto remove sau 5 phút nếu không có action
    setTimeout(() => {
      removeNotification(newNotification.id);
    }, 5 * 60 * 1000);
  };

  const markAsRead = (id) => {
    setNotifications(prev => 
      prev.map(notification => 
        notification.id === id 
          ? { ...notification, isRead: true }
          : notification
      )
    );
    setUnreadCount(prev => Math.max(0, prev - 1));
  };

  const markAllAsRead = () => {
    setNotifications(prev => 
      prev.map(notification => ({ ...notification, isRead: true }))
    );
    setUnreadCount(0);
  };

  const removeNotification = (id) => {
    setNotifications(prev => {
      const notification = prev.find(n => n.id === id);
      if (notification && !notification.isRead) {
        setUnreadCount(prevCount => Math.max(0, prevCount - 1));
      }
      return prev.filter(n => n.id !== id);
    });
  };

  const clearAllNotifications = () => {
    setNotifications([]);
    setUnreadCount(0);
  };
  const playNotificationSound = () => {
    try {
      // Tạo âm thanh thông báo bằng Web Audio API với âm thanh hay hơn
      const audioContext = new (window.AudioContext || window.webkitAudioContext)();
      const oscillator = audioContext.createOscillator();
      const gainNode = audioContext.createGain();

      oscillator.connect(gainNode);
      gainNode.connect(audioContext.destination);

      // Tạo âm thanh chuông đẹp hơn
      oscillator.frequency.setValueAtTime(523.25, audioContext.currentTime); // C5
      oscillator.frequency.setValueAtTime(659.25, audioContext.currentTime + 0.1); // E5
      oscillator.frequency.setValueAtTime(783.99, audioContext.currentTime + 0.2); // G5

      gainNode.gain.setValueAtTime(0.4, audioContext.currentTime);
      gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.4);

      oscillator.start(audioContext.currentTime);
      oscillator.stop(audioContext.currentTime + 0.4);
    } catch (error) {
      console.log('Không thể phát âm thanh thông báo:', error);
    }
  };

  const triggerBellAnimation = () => {
    try {
      // Trigger animation cho tất cả notification bells
      const bellElements = document.querySelectorAll('.notification-bell');
      bellElements.forEach(bell => {
        bell.classList.add('bell-shake');
        setTimeout(() => {
          bell.classList.remove('bell-shake');
        }, 1000);
      });
    } catch (error) {
      console.log('Không thể trigger bell animation:', error);
    }
  };

  // Thông báo đặt vé thành công
  const notifyBookingSuccess = (bookingData) => {
    addNotification({
      type: 'success',
      title: '🎉 Đặt vé thành công!',
      message: `Bạn đã đặt thành công ${bookingData.seatCount} ghế cho chuyến xe ${bookingData.route}`,
      details: {
        seats: bookingData.seats,
        totalAmount: bookingData.totalAmount,
        departureTime: bookingData.departureTime,
        ticketCode: bookingData.ticketCode
      },
      actionUrl: `/tickets/${bookingData.ticketId}`,
      actionText: 'Xem chi tiết'
    });
  };

  // Thông báo thanh toán thành công
  const notifyPaymentSuccess = (paymentData) => {
    addNotification({
      type: 'success',
      title: '💳 Thanh toán thành công!',
      message: `Đã thanh toán thành công ${paymentData.amount} VNĐ`,
      details: paymentData,
      actionUrl: '/orders',
      actionText: 'Xem đơn hàng'
    });
  };

  // Thông báo lỗi
  const notifyError = (errorData) => {
    addNotification({
      type: 'error',
      title: '❌ Có lỗi xảy ra',
      message: errorData.message,
      details: errorData.details
    });
  };

  const value = {
    notifications,
    unreadCount,
    addNotification,
    markAsRead,
    markAllAsRead,
    removeNotification,
    clearAllNotifications,
    notifyBookingSuccess,
    notifyPaymentSuccess,
    notifyError
  };

  return (
    <NotificationContext.Provider value={value}>
      {children}
    </NotificationContext.Provider>
  );
};
