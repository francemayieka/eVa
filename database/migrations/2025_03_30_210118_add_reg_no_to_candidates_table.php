<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('reg_no')->nullable()->unique()->after('name'); // Allow NULL and enforce uniqueness
        });
    }

    public function down()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('reg_no');
        });
    }
};
