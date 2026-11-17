<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();   // کسی که تسک برای اوست
            $t->unsignedBigInteger('created_by');                          // نویسنده (خودش یا مدیر)
            $t->string('title', 200);
            $t->text('note')->nullable();
            $t->date('week_start');                                        // شنبه‌ی هفته‌ی تسک (میلادی)
            $t->string('status', 10)->default('open');                     // open | done
            $t->timestamp('done_at')->nullable();
            $t->unsignedSmallInteger('carry_count')->default(0);           // چند بار منتقل شده
            $t->unsignedBigInteger('origin_id')->nullable();               // تسک اصلی
            $t->unsignedBigInteger('rolled_to')->nullable();               // نسخه‌ی منتقل‌شده به هفته‌ی بعد
            $t->timestamp('deleted_at')->nullable();                       // حذف = پنهان‌شدن، نه پاک‌شدن
            $t->timestamps();
            $t->index(['user_id', 'week_start']);
        });
        Schema::create('task_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('task_id')->index();
            $t->unsignedBigInteger('user_id');
            $t->string('action', 20);
            $t->string('detail', 500)->nullable();
            $t->timestamp('created_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('task_logs');
        Schema::dropIfExists('tasks');
    }
};
