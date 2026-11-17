<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('employee');   // employee | finance | boss
            $t->date('start_date')->nullable();        // تاریخ شروع کار (میلادی)
        });
        Schema::create('leaves', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('kind');                         // daily | hourly
            $t->date('start_date');
            $t->date('end_date');
            $t->string('from_time', 5)->nullable();
            $t->string('to_time', 5)->nullable();
            $t->unsignedInteger('minutes');
            $t->string('reason', 500)->default('');
            $t->string('fin_status')->default('pending');
            $t->string('boss_status')->default('pending');
            $t->unsignedBigInteger('fin_by')->nullable();
            $t->unsignedBigInteger('boss_by')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('leaves');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'start_date']));
    }
};
