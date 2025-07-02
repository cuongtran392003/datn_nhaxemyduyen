# NHÀ XE MỸ DUYÊN - HỆ THỐNG ĐẶT VÉ XE KHÁCH

## Tổng quan dự án

Nhà Xe Mỹ Duyên là một hệ thống toàn diện giúp quản lý và đặt vé xe khách. Dự án được phát triển với kiến trúc client-server, bao gồm:

- Frontend: Ứng dụng React hiện đại, responsive
- Backend: WordPress API tùy chỉnh + MySQL database

## Cấu trúc dự án

```
/
├── frontend/         # Ứng dụng React
└── backend/          # WordPress backend + Database
```

## Công nghệ sử dụng

### Frontend

- **React 19** - Framework JavaScript hiện đại
- **React Router Dom v7** - Quản lý định tuyến (routing)
- **TailwindCSS** - Framework CSS utility-first
- **Axios** - HTTP client cho các request API
- **Framer Motion** - Thư viện animation
- **Socket.io Client** - Kết nối realtime
- **React Toastify** - Thông báo toast
- **Leaflet/React Leaflet** - Hiển thị bản đồ tương tác
- **Swiper** - Slider/carousel
- **React DatePicker** - Component chọn ngày
- **Google & OpenAI APIs** - Tích hợp AI

### Backend

- **WordPress** - Nền tảng quản lý nội dung
- **WordPress REST API** - API gốc của WordPress
- **Custom API Endpoints** - API tùy chỉnh cho ứng dụng
- **MySQL** - Cơ sở dữ liệu
- **Stripe PHP** - Xử lý thanh toán
- **Docker** - Containerization

## Chi tiết về các thành phần

### Frontend

#### Cấu trúc thư mục frontend

```
frontend/
├── public/             # Tài nguyên công khai
├── build/              # Phiên bản production đã biên dịch
└── src/                # Source code
    ├── assets/         # Hình ảnh, font, và tài nguyên tĩnh
    ├── components/     # React components
    │   ├── Account/    # Components liên quan đến tài khoản
    │   ├── Layout/     # Components bố cục (Header, Footer)
    │   ├── News/       # Components liên quan đến tin tức
    │   ├── Shared/     # Components dùng chung
    │   ├── contexts/   # React contexts
    │   └── pages/      # Components trang
    ├── data/           # Dữ liệu tĩnh và fixtures
    ├── hooks/          # Custom React hooks
    ├── service/        # Services gọi API
    ├── App.jsx         # Component gốc của ứng dụng
    └── index.js        # Điểm khởi đầu ứng dụng
```

#### Các Components chính

1. **App.jsx**

   - Component gốc quản lý định tuyến và bố cục
   - Thiết lập context providers (AuthProvider, NotificationProvider)
   - Định nghĩa các routes chính của ứng dụng

2. **AuthContext.js**

   - Quản lý trạng thái đăng nhập/đăng xuất
   - Xử lý JWT authentication với WordPress
   - Tự động refresh token
   - Lưu trữ thông tin người dùng

3. **NotificationContext.jsx**

   - Hệ thống thông báo toàn diện
   - Quản lý thông báo, âm thanh và animation
   - Đánh dấu đã đọc/chưa đọc
   - Lưu trữ thông báo vào localStorage

4. **Service Modules**
   - `ticketService.jsx` - Xử lý API liên quan đến vé xe
   - `tripService.jsx` - Xử lý API liên quan đến chuyến xe
   - `routeService.jsx` - Xử lý API liên quan đến tuyến đường
   - `locationService.jsx` - Xử lý API liên quan đến địa điểm
   - `postService.jsx` - Xử lý API liên quan đến bài viết

### Backend

#### Cấu trúc thư mục backend

```
backend/
├── docker-compose.yml  # Cấu hình Docker
├── nhaxemyduyen.sql    # Database dump
├── composer.json       # Quản lý dependencies PHP
└── wordpress/          # WordPress core
    ├── wp-content/
    │   ├── plugins/
    │   │   └── nhaxemyduyen-plugin/ # Plugin chính
    │   │       ├── inc/             # API Endpoints
    │   │       └── nhaxemyduyen-plugin.php # Main plugin file
    │   └── themes/           # Theme WordPress
    └── wp-config.php         # Cấu hình WordPress
```

#### WordPress Plugin và APIs

**Plugin nhaxemyduyen-plugin**

- Quản lý và hiển thị menu admin trong WordPress
- Định nghĩa các endpoint API tùy chỉnh

**API Endpoints**:

1. **Trip API** (`trip-api.php`)

   - Quản lý chuyến xe (thêm, sửa, xóa, lấy thông tin)
   - Truy vấn thông tin ghế ngồi của chuyến xe

2. **Ticket API** (`ticket-api.php`)

   - Quản lý vé xe (đặt vé, hủy vé, cập nhật trạng thái)
   - Xác thực người dùng trước khi thực hiện các thao tác

3. **Route API** (`route-api.php`)

   - Quản lý tuyến đường (thêm, sửa, xóa tuyến)

4. **Location API** (`location-api.php`)

   - Quản lý địa điểm đón trả khách

5. **Custom API** (`custom-api.php`)

   - API mở rộng tùy chỉnh khác

6. **Contact API** (`contact-api.php`)
   - Xử lý biểu mẫu liên hệ

#### Database

Cơ sở dữ liệu MySQL với các bảng chính:

- `wp_users` - Người dùng (WordPress core)
- `wp_trips` - Chuyến xe
- `wp_routes` - Tuyến đường
- `wp_locations` - Địa điểm
- `wp_tickets` - Vé xe
- `wp_drivers` - Tài xế
- `wp_buses` - Phương tiện

## Luồng xử lý

### Quy trình đặt vé

1. **Tìm kiếm chuyến xe**:

   - Người dùng chọn điểm đi, điểm đến và ngày khởi hành
   - Frontend gọi API để tìm kiếm các chuyến xe phù hợp
   - Hiển thị kết quả tìm kiếm

2. **Chọn chuyến xe và ghế ngồi**:

   - Người dùng chọn chuyến xe mong muốn
   - Hiển thị sơ đồ ghế và trạng thái ghế (đã đặt/còn trống)
   - Người dùng chọn ghế ngồi

3. **Nhập thông tin hành khách**:

   - Nhập thông tin liên hệ và hành khách
   - Xác nhận thông tin đặt vé

4. **Thanh toán**:

   - Tích hợp Stripe để xử lý thanh toán
   - Cập nhật trạng thái đặt vé

5. **Xác nhận đặt vé**:
   - Gửi email xác nhận
   - Hiển thị thông báo đặt vé thành công
   - Lưu vé vào lịch sử đặt vé của người dùng

### Quản lý tài khoản

1. **Đăng ký / Đăng nhập**:

   - Đăng ký tài khoản mới hoặc đăng nhập
   - Xác thực qua JWT với WordPress REST API
   - Quản lý phiên đăng nhập với localStorage và token refresh

2. **Quản lý hồ sơ**:

   - Xem và cập nhật thông tin cá nhân
   - Thay đổi mật khẩu
   - Quản lý thông tin thanh toán

3. **Lịch sử đặt vé**:
   - Xem danh sách vé đã đặt
   - Xem chi tiết từng vé
   - Hủy vé (nếu điều kiện cho phép)

### Hệ thống thông báo

1. **Thông báo realtime**:

   - Sử dụng Socket.io để nhận thông báo realtime
   - Animation và âm thanh khi có thông báo mới

2. **Quản lý thông báo**:
   - Đánh dấu đã đọc/chưa đọc
   - Xóa thông báo
   - Lưu trữ lịch sử thông báo

## Cách chạy dự án

### Khởi động Backend

```bash
cd backend
docker-compose up -d
```

Backend sẽ chạy tại:

- WordPress: http://localhost:8000
- phpMyAdmin: http://localhost:8081

### Khởi động Frontend

```bash
cd frontend
npm install
npm start
```

Frontend sẽ chạy tại: http://localhost:3000

## Lưu ý phát triển

1. **API Authentication**:

   - API sử dụng JWT authentication
   - Token lưu trong localStorage
   - Token tự động làm mới khi hết hạn

2. **Responsive Design**:

   - Giao diện tương thích với mọi thiết bị
   - Sử dụng TailwindCSS để styling

3. **Error Handling**:

   - Xử lý lỗi với React Error Boundaries
   - Hiển thị thông báo lỗi người dùng thân thiện

4. **Performance**:
   - Lazy loading components
   - Tối ưu API calls
   - Caching phía client

## Phân tích nghiệp vụ

### Mô hình nghiệp vụ

1. **Quản lý tuyến đường và lịch trình**

   - **Định nghĩa**: Quản lý các tuyến đường cố định mà nhà xe phục vụ (VD: Hà Nội - Thanh Hóa)
   - **Thông tin lưu trữ**: Điểm đi, điểm đến, khoảng cách, thời gian di chuyển dự kiến, giá vé cơ bản
   - **Điểm đón trả khách**: Mỗi tuyến đường có nhiều điểm đón/trả khách cụ thể
   - **Quy trình nghiệp vụ**: Admin tạo và quản lý các tuyến đường, cập nhật khi cần thiết

2. **Quản lý phương tiện**

   - **Định nghĩa**: Theo dõi đội xe của nhà xe
   - **Thông tin lưu trữ**: Biển số, loại xe, sơ đồ ghế ngồi, số ghế, năm sản xuất, tình trạng hoạt động
   - **Sơ đồ ghế**: Mỗi loại xe có sơ đồ ghế khác nhau (giường nằm, ghế ngồi, VIP)
   - **Quy trình nghiệp vụ**: Admin thêm xe mới, cập nhật tình trạng, quản lý bảo dưỡng

3. **Quản lý tài xế**

   - **Định nghĩa**: Theo dõi thông tin tài xế
   - **Thông tin lưu trữ**: Họ tên, số điện thoại, giấy phép, kinh nghiệm, tình trạng
   - **Lịch làm việc**: Phân công tài xế cho các chuyến xe
   - **Quy trình nghiệp vụ**: Admin quản lý hồ sơ tài xế, phân công công việc

4. **Quản lý chuyến xe**

   - **Định nghĩa**: Các chuyến xe cụ thể theo lịch trình
   - **Thông tin lưu trữ**: Tuyến đường, ngày giờ khởi hành, xe được phân công, tài xế, trạng thái chuyến
   - **Trạng thái chuyến**: Chưa khởi hành, đang di chuyển, đã hoàn thành, đã hủy
   - **Quy trình nghiệp vụ**: Admin lập lịch chuyến xe hàng ngày/tuần/tháng

5. **Quy trình đặt vé**

   - **Bước 1 - Tìm kiếm**: Khách hàng tìm chuyến xe phù hợp theo điểm đi, điểm đến, ngày giờ
   - **Bước 2 - Chọn ghế**: Hiển thị sơ đồ ghế, khách chọn vị trí mong muốn
   - **Bước 3 - Thông tin**: Khách điền thông tin cá nhân và hành khách
   - **Bước 4 - Thanh toán**: Khách thanh toán qua các phương thức: trực tuyến (Stripe), chuyển khoản, tiền mặt
   - **Bước 5 - Xác nhận**: Hệ thống gửi email/SMS xác nhận có mã QR

6. **Quy trình hoàn/hủy vé**

   - **Chính sách hoàn vé**: Hoàn 100% nếu hủy trước 24h, 50% nếu hủy trước 12h, 0% nếu hủy trong vòng 6h
   - **Quy trình hoàn vé**: Khách yêu cầu → Admin duyệt → Hoàn tiền
   - **Xử lý ngoại lệ**: Hủy chuyến do nhà xe, thay đổi lịch trình, sự cố

7. **Quản lý khách hàng**

   - **Đăng ký tài khoản**: Email/Phone + mật khẩu hoặc đăng nhập qua mạng xã hội
   - **Thông tin khách hàng**: Cá nhân, lịch sử đặt vé, điểm tích lũy
   - **Chương trình khách hàng thân thiết**: Tích điểm và ưu đãi cho khách hàng thường xuyên
   - **Quy trình nghiệp vụ**: Khách tự đăng ký, admin có thể quản lý tài khoản

8. **Báo cáo và thống kê**

   - **Báo cáo doanh thu**: Theo ngày, tuần, tháng, năm, tuyến đường
   - **Báo cáo chuyến**: Tỷ lệ lấp đầy ghế, tuyến đường phổ biến
   - **Báo cáo khách hàng**: Khách hàng thường xuyên, phân tích hành vi
   - **Quy trình nghiệp vụ**: Hệ thống tự động tạo báo cáo, admin xem và xuất báo cáo

9. **Quản lý thanh toán**
   - **Phương thức thanh toán**: Stripe (thẻ), chuyển khoản ngân hàng, tiền mặt
   - **Xử lý thanh toán**: Ghi nhận giao dịch, xác nhận thanh toán
   - **Hoàn tiền**: Xử lý hoàn tiền khi hủy vé theo chính sách
   - **Quy trình nghiệp vụ**: Tự động với Stripe, xác nhận thủ công với chuyển khoản

### Quy tắc nghiệp vụ

1. **Quy tắc đặt vé**

   - Không thể đặt vé cho chuyến đã khởi hành
   - Không thể đặt ghế đã được đặt
   - Mỗi vé chỉ được đặt cho một hành khách
   - Vé chỉ có hiệu lực cho chuyến xe và ngày cụ thể

2. **Quy tắc về giá vé**

   - Giá vé thay đổi theo tuyến đường và loại ghế
   - Giá có thể thay đổi theo thời điểm (cao điểm/thấp điểm)
   - Có thể áp dụng mã giảm giá hoặc khuyến mãi
   - Ưu đãi cho người cao tuổi, trẻ em, người khuyết tật

3. **Quy tắc về hủy vé và hoàn tiền**

   - Hoàn 100% nếu hủy trước 24 giờ
   - Hoàn 50% nếu hủy từ 12 đến 24 giờ
   - Không hoàn tiền nếu hủy trong vòng 12 giờ
   - Hoàn 100% nếu nhà xe hủy chuyến

4. **Quy tắc vận hành**
   - Mỗi chuyến xe phải có ít nhất 1 tài xế chính và 1 phụ xe
   - Tài xế không được phép lái quá 4 giờ liên tục
   - Phương tiện phải được kiểm tra kỹ thuật trước mỗi chuyến
   - Khởi hành đúng giờ, chậm không quá 15 phút

## Phân tích kỹ thuật triển khai

### Kiến trúc hệ thống

1. **Kiến trúc tổng thể**

   - **Mô hình**: Kiến trúc client-server với RESTful API
   - **Phân lớp**: Presentation Layer (React), API Layer (WordPress REST), Business Logic Layer (PHP), Data Access Layer (MySQL)
   - **Phương pháp thiết kế**: Component-based design, Stateful và Stateless components

2. **Kiến trúc Frontend**

   - **Cấu trúc React**: Sử dụng functional components và React Hooks
   - **State Management**: Context API cho trạng thái toàn cục (auth, notifications)
   - **Routing**: React Router v7 với nested routes và lazy loading
   - **Styling**: TailwindCSS với component-based styling

3. **Kiến trúc Backend**
   - **WordPress as Headless CMS**: Sử dụng WordPress làm backend API
   - **Plugin Architecture**: Custom plugin với các modules tách biệt
   - **API Endpoints**: RESTful endpoints theo tài nguyên (resources)
   - **Database Schema**: Normalized schema với các quan hệ rõ ràng

### Kỹ thuật triển khai Frontend

1. **Component Patterns**

   - **Atomic Design**: Chia components thành atoms, molecules, organisms, templates, pages
   - **Container/Presentational Pattern**: Tách logic và UI
   - **Render Props và HOC**: Sử dụng cho các logic phức tạp và tái sử dụng
   - **Custom Hooks**: Encapsulate và chia sẻ logic giữa các components

2. **State và Data Management**

   - **Local State**: useState cho trạng thái component
   - **Global State**: Context API + useReducer cho auth và notifications
   - **Side Effects**: useEffect cho API calls và lifecycle events
   - **API Interaction**: Custom service modules với Axios
   - **Caching**: Lưu trữ trong localStorage và sessionStorage

3. **Performance Optimizations**

   - **Code Splitting**: Dynamic imports và React.lazy()
   - **Memoization**: React.memo(), useMemo(), useCallback()
   - **Virtualization**: Sử dụng windowing cho danh sách dài
   - **Image Optimization**: Lazy loading, WebP format, srcset
   - **Bundle Optimization**: Tree shaking, minification

4. **UI/UX Implementation**

   - **Responsive Design**: Mobile-first approach với TailwindCSS
   - **Accessibility**: ARIA attributes, semantic HTML, keyboard navigation
   - **Animation**: Framer Motion cho micro-interactions
   - **Form Handling**: Controlled components, form validation
   - **Error Handling**: Error boundaries, toast notifications

5. **Realtime Features**
   - **WebSocket Connection**: Socket.io client cho thông báo realtime
   - **Connection Management**: Auto-reconnect, offline detection
   - **Event Handling**: Subscribe/unsubscribe patterns
   - **UI Updates**: Optimistic updates và state reconciliation

### Kỹ thuật triển khai Backend

1. **WordPress Customization**

   - **Custom Tables**: Tạo các bảng custom cho business models
   - **Custom Endpoints**: Register REST routes với WP REST API
   - **Authentication**: JWT authentication với token refresh
   - **Hooks và Filters**: Extend WordPress core functionality

2. **Database Design**

   - **Schema Design**: Normalized database với foreign keys
   - **Indexing Strategy**: Indexing các cột thường được tìm kiếm
   - **Query Optimization**: Prepared statements, JOIN optimization
   - **Transaction Management**: ACID transactions cho booking process

3. **API Development**

   - **RESTful Principles**: Resource-based endpoints, HTTP verbs
   - **Request Validation**: Input sanitization và validation
   - **Response Formatting**: Consistent JSON structure
   - **Error Handling**: HTTP status codes và error messages
   - **Rate Limiting**: Prevent abuse và DoS attacks

4. **Security Implementation**

   - **Authentication**: JWT với expiration và refresh
   - **Authorization**: Role-based access control
   - **Data Protection**: Encryption cho sensitive data
   - **SQL Injection Prevention**: Prepared statements
   - **XSS Protection**: Output escaping, Content-Security-Policy
   - **CSRF Protection**: CSRF tokens

5. **Payment Integration**
   - **Stripe Integration**: Server-side với Stripe PHP SDK
   - **Payment Flow**: Intent-based payment flow
   - **Webhook Handling**: Async payment event processing
   - **Error Recovery**: Transaction retry và reconciliation

### DevOps và Deployment

1. **Development Environment**

   - **Docker Containerization**: Docker Compose cho local development
   - **Environment Variables**: .env files cho configuration
   - **Hot Reloading**: Fast feedback loop trong development

2. **CI/CD Pipeline**

   - **Version Control**: Git workflow (feature branches, PRs)
   - **Automated Testing**: Unit tests, integration tests
   - **Build Process**: Automated builds và assets optimization
   - **Deployment Strategy**: Blue-green deployment

3. **Monitoring và Maintenance**
   - **Error Logging**: Centralized error logging
   - **Performance Monitoring**: Các metrics chính (load time, API latency)
   - **User Analytics**: Theo dõi user behavior và conversion
   - **Backup Strategy**: Database backups và disaster recovery

### Challenges và Solutions

1. **Seat Reservation Concurrency**

   - **Challenge**: Đồng thời nhiều users có thể chọn cùng một ghế
   - **Solution**: Optimistic locking và temporary reservation

2. **Payment Processing Reliability**

   - **Challenge**: Đảm bảo payment status consistency
   - **Solution**: Event-driven architecture với webhooks và idempotency

3. **Realtime Notification Delivery**

   - **Challenge**: Đảm bảo notifications gửi đến tất cả devices
   - **Solution**: Socket.io với persistent connections và message queuing

4. **Performance Under Load**

   - **Challenge**: Xử lý traffic spikes trong thời điểm cao điểm
   - **Solution**: Caching, connection pooling, horizontal scaling

5. **Cross-platform Compatibility**

   - **Challenge**: Đảm bảo trải nghiệm nhất quán trên các browsers và devices
   - **Solution**: Progressive enhancement, feature detection, polyfills

6. **Kiểm soát trạng thái chuyến xe theo thời gian thực**
   - **Challenge**: Đảm bảo người dùng không thể đặt vé cho chuyến xe đã khởi hành
   - **Solution**: Kiểm tra thời gian khởi hành và so sánh với thời gian hiện tại

### Kỹ thuật kiểm tra thời gian khởi hành

Một trong những kỹ thuật quan trọng trong hệ thống là cơ chế kiểm tra thời gian khởi hành của các chuyến xe để đảm bảo người dùng không thể đặt vé cho chuyến đã khởi hành.

**1. Cài đặt kỹ thuật**:

```jsx
// Biến kiểm tra chuyến xe đã khởi hành chưa
let hasDeparted = false;
let departureDate = null;

// Xử lý kiểm tra thời gian
if (trip.departure_time) {
  departureDate = new Date(trip.departure_time);
  if (!isNaN(departureDate.getTime())) {
    // Kiểm tra xem chuyến xe đã khởi hành chưa
    const currentDate = new Date();
    hasDeparted = departureDate < currentDate;

    // Logic xử lý tiếp theo...
  }
}

// Xử lý hiển thị dựa trên trạng thái
if (hideIfDeparted && hasDeparted) {
  return null; // Không hiển thị chuyến xe đã khởi hành
}
```

**2. Xử lý UI/UX**:

- Ẩn hoàn toàn các chuyến đã khởi hành (optional)
- Chuyển nút "Đặt vé" thành "Liên hệ" cho chuyến đã khởi hành
- Thêm chỉ báo trạng thái và trực quan để người dùng hiểu tại sao không thể đặt vé

**3. Lợi ích**:

- Tránh trải nghiệm tiêu cực khi người dùng cố gắng đặt vé cho chuyến đã khởi hành
- Giảm tải cho hệ thống xử lý đặt vé backend
- Hiển thị thông tin chính xác và cập nhật cho người dùng

**4. Triển khai trong dự án**:

- Thành phần `TripCard` xử lý hiển thị trạng thái chuyến xe
- Trang tìm kiếm chuyến xe có thể áp dụng bộ lọc `hideIfDeparted` để loại bỏ chuyến đã khởi hành
- Style CSS đặc biệt giúp phân biệt trực quan giữa chuyến có thể đặt và chuyến đã khởi hành
- Hiển thị chính xác số lượng chuyến xe thực sự có thể đặt được (không tính chuyến đã khởi hành)

## Tính năng nổi bật

1. **Bản đồ tương tác**: Hiển thị tuyến đường và địa điểm bằng Leaflet
2. **Thông báo realtime**: Cập nhật tình trạng đặt vé và thay đổi lịch trình
3. **Thanh toán online**: Tích hợp Stripe để thanh toán an toàn
4. **Đa ngôn ngữ**: Hỗ trợ nhiều ngôn ngữ (tiếng Việt, tiếng Anh)
5. **Hỗ trợ trực tuyến**: Chat hỗ trợ khách hàng
6. **Tích hợp AI**: Sử dụng Google và OpenAI APIs để hỗ trợ khách hàng
7. **Chatbot thông minh**: Hỗ trợ định dạng phong phú trong tin nhắn (markdown đơn giản)

### Cải tiến hiển thị tin nhắn chatbot

Chatbot của Nhà xe Mỹ Duyên đã được nâng cấp với khả năng hiển thị tin nhắn định dạng phong phú:

**1. Các tính năng định dạng:**

- Xuống dòng (sử dụng `\n`)
- In đậm văn bản (sử dụng `*văn bản*`)
- Danh sách (sử dụng `-` hoặc `*` ở đầu dòng)
- Tự động nhận dạng và tạo liên kết URL

**2. Cách triển khai:**

- Component `MessageFormatter` chuyên xử lý định dạng tin nhắn
- CSS riêng biệt để hiển thị đúng các kiểu định dạng
- Áp dụng cho tin nhắn từ bot và thông báo lỗi

**3. Lợi ích:**

- Tin nhắn dễ đọc và có cấu trúc rõ ràng hơn
- Cải thiện trải nghiệm người dùng khi tương tác với chatbot
- Dễ dàng truyền tải thông tin phức tạp và nhiều bước
- Linh hoạt hơn trong việc cập nhật nội dung tin nhắn từ API

**4. Ví dụ sử dụng:**

```jsx
addMessage(
  "Chatbot",
  "*Thông báo quan trọng*\n\nBạn cần chuẩn bị:\n- Giấy tờ tùy thân\n- Mã đặt vé\n- Có mặt trước 15 phút",
  "bot"
);
```

---

© 2025 Nhà Xe Mỹ Duyên - Dự án phát triển bởi [Tên tác giả]
