<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bật extension pg_trgm (chỉ cần 1 lần duy nhất trong DB)
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id', 255);                 // ID người dùng
            $table->string('domain_name', 255);             // Tên miền shop
            $table->bigInteger('product_id');               // ID sản phẩm
            $table->bigInteger('parent_id')->nullable();    // Review cha (nếu là reply)
            $table->text('review_text');                    // Nội dung đánh giá hoặc phản hồi
            $table->integer('rating')->nullable();          // Rating (chỉ review gốc)
            $table->boolean('is_admin')->default(false);    // Có phải admin không

            // PostgreSQL không có ENUM sẵn → dùng check constraint
            $table->string('status', 20)->default('approved'); // pending/approved/rejected
            $table->string('type', 20)->default('product');    // product/article

            $table->timestamp('created_at')->useCurrent();

            // Indexes thường
            $table->index('product_id');
            $table->index('user_id');
            $table->index('parent_id');
        });

        // Tạo GIN index để search text nhanh (đa ngôn ngữ, hỗ trợ LIKE/ILIKE & fuzzy search)
        DB::statement("CREATE INDEX review_text_trgm_idx ON product_reviews USING GIN (review_text gin_trgm_ops);");

        // Thêm constraint ENUM cho status và type
        DB::statement("ALTER TABLE product_reviews ADD CONSTRAINT status_check CHECK (status IN ('pending', 'approved', 'rejected'));");
        DB::statement("ALTER TABLE product_reviews ADD CONSTRAINT type_check CHECK (type IN ('product', 'article'));");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
