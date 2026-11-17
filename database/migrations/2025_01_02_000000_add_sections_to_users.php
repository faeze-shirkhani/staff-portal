<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('section')->nullable();                 // sales | site | finance
            $t->boolean('can_report')->default(false);         // دسترسی به گزارش کارمندان
            $t->integer('extra_minutes')->default(0);          // مانده‌ی اضافه که مدیر ثبت می‌کند
        });
    }
    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['section', 'can_report', 'extra_minutes']));
    }
};
