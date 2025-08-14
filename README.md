# Hướng dẫn cài đặt dự án Laravel với Shopify App

## 1. Cài đặt dependencies PHP
```bash
composer install
```

## 2. Tạo file `.env` từ file mẫu
```bash
cp .env.example .env
```

## 3. Tạo APP_KEY cho Laravel
```bash
php artisan key:generate
```

## 4. Tạo database trong PostgreSQL hoặc MySQL
- Vào pgAdmin hoặc MySQL Workbench, tạo database mới theo tên trong `.env`.

## 5. Thêm **App Key** và **Secret Key** từ Shopify App
- Mở [Shopify Partners](https://partners.shopify.com/)
- Chọn App của bạn → Copy **API Key** và **API Secret Key**.
- Dán vào `.env`:
```
SHOPIFY_API_KEY=your_api_key
SHOPIFY_API_SECRET=your_api_secret
```

## 6. Thay đổi URL của App
- Trong `.env`, chỉnh `APP_URL` thành URL backend của bạn (ví dụ: `https://your-backend.com`).

## 7. Chạy migration
```bash
php artisan migrate
```

## 8. Chạy hàng đợi (Queue)
```bash
php artisan queue:work
```
