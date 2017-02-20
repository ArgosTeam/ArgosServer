<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            
            $table->increments('id');
            $table->integer('origin_user_id');
            $table->string('description')
                ->nullable();
            $table->string('path');
            $table->string('md5');
            $table->tinyInteger('public')
                ->nullable();
            $table->string('mode')
                ->nullable();
            $table->integer('location_id')
                ->unsigned()
                ->nullable();

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photos');
    }
}
