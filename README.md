# LUMINA

LUMINA là website bán mắt kính viết bằng PHP thuần, MySQL và giao diện HTML/CSS. Dự án mô phỏng đầy đủ một luồng eCommerce cơ bản: xem sản phẩm, lọc danh mục, thêm giỏ hàng, đặt hàng, nhập đơn kính và quản trị catalog/đơn hàng.

## Chức năng chính

- Trang khách hàng: trang chủ, giới thiệu, bộ sưu tập, danh sách sản phẩm, chi tiết sản phẩm.
- Tài khoản: đăng ký, đăng nhập, hồ sơ cá nhân và lịch sử đơn hàng.
- Giỏ hàng và thanh toán: thêm sản phẩm, kiểm tra giỏ, đặt hàng, xem chi tiết đơn.
- Luồng đơn kính: chọn phương thức, nhập/upload toa kính, chọn tròng kính, xem lại và thanh toán.
- Trang quản trị: dashboard, quản lý đơn hàng, cập nhật trạng thái đơn, quản lý sản phẩm và danh mục.
- Cơ sở dữ liệu mẫu: danh mục, sản phẩm, biến thể, hình ảnh, lựa chọn tròng kính và vai trò người dùng.

## Công nghệ sử dụng

- PHP 8.2 với Apache
- MySQL 8.0
- PDO
- Docker Compose
- phpMyAdmin
- HTML, CSS, JavaScript thuần
- Flaticon UIcons cho icon giao diện

## Cấu trúc thư mục

```text
LUMINA/
+-- app/
|   +-- config/          # Cấu hình app và database
|   +-- helpers/         # Hàm dùng chung, auth, prescription flow
|   +-- middleware/      # Middleware kiểm tra quyền
|   +-- views/partials/  # Header, footer, layout admin
+-- database/
|   +-- schema.sql       # Tạo bảng
|   +-- seed.sql         # Dữ liệu mẫu
|   +-- admin_seed_*.sql # Seed tài khoản admin mẫu
+-- docker/apache/       # Dockerfile và cấu hình Apache
+-- public/              # Các trang public và admin
+-- docker-compose.yml
```

## Yêu cầu

- Docker Desktop
- Docker Compose
- Trình duyệt web

## Cách chạy dự án

Chạy container:

```bash
docker compose up -d --build
```

Mở ứng dụng:

- Website: `http://localhost:8080`
- phpMyAdmin: `http://localhost:8081`

Thông tin database mặc định:

```text
Host trong Docker: db
Port ngoài máy: 3307
Database: lumina_db
User: lumina_user
Password: lumina_password
Root password: root
```

## Tài khoản admin mẫu

File `database/seed.sql` chưa tự tạo tài khoản admin. Nếu cần đăng nhập trang quản trị, import file:

```text
database/admin_seed_123456.sql
```

Sau khi import, dùng:

```text
Email: admin@lumina.local
Password: 123456
```

Trang quản trị nằm tại:

```text
http://localhost:8080/admin/
```

## Reset database

Nếu muốn tạo lại database từ đầu và nạp lại `schema.sql` + `seed.sql`:

```bash
docker compose down -v
docker compose up -d --build
```

Lưu ý: lệnh này xóa volume MySQL hiện tại, toàn bộ dữ liệu phát sinh trong quá trình demo sẽ mất.

## Một số đường dẫn quan trọng

- `/products.php`: danh sách sản phẩm
- `/product-detail.php?id=...`: chi tiết sản phẩm
- `/cart.php`: giỏ hàng
- `/checkout.php`: thanh toán
- `/orders.php`: lịch sử đơn hàng
- `/prescription-start.php`: bắt đầu luồng đơn kính
- `/admin/`: dashboard quản trị
- `/admin/orders/index.php`: quản lý đơn hàng
- `/admin/products/index.php`: quản lý sản phẩm
- `/admin/categories/index.php`: quản lý danh mục

## Ghi chú về assets

Bộ icon Flaticon UIcons đang nằm trong:

```text
public/assets/vendor/flaticon-uicons/
```

Nếu icon không hiển thị, kiểm tra lại các file sau:

```text
public/assets/vendor/flaticon-uicons/css/uicons-regular-rounded.css
public/assets/vendor/flaticon-uicons/webfonts/
```

## Cấu hình môi trường

Các biến môi trường chính được khai báo trong `docker-compose.yml`:

```text
APP_ENV=local
APP_URL=http://localhost:8080
DB_HOST=db
DB_PORT=3306
DB_NAME=lumina_db
DB_USER=lumina_user
DB_PASS=lumina_password
```

Trong code, cấu hình được đọc tại:

- `app/config/config.php`
- `app/config/database.php`

## Tác giả

Dự án phục vụ học tập môn Lập trình Web.
