<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRatingTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rating_types', function (BluePrint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->enum('name', ['hot', 'chill', 'cute', 'trip']);
            $table->timestamps();
        });
        DB::table('rating_types')->insert([
            ['name' => 'hot'],
            ['name' => 'chill'],
            ['name' => 'cute'],
            ['name' => 'trip']
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rating_types');
    }
}
