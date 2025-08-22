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

## 6. Chạy migration 
```bash
php artisan migrate
```
## 7. Tạo url với cloudflare/ngrok( ví dụ với cloudflare và port 3000 - chạy ở terminal chứa file cloudflare.exe)
```bash
.\cloudflared.exe tunnel --url http://localhost:3000

```

## 6. Thay đổi URL của App
- Trong `.env`, chỉnh `APP_URL` thành URL tạo ở trên

## 7. Run project (ví dụ ở đây port 3000)
`php artisan serve --port=3000`


## 8. Chạy hàng đợi (Queue)
```bash
php artisan queue:work
```
