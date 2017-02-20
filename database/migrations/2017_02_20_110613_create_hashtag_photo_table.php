<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHashtagPhotoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hashtag_photo', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('hashtag_id')->unsigned();

            $table->foreign('hashtag_id')
                ->references('id')
                ->on('hashtags')
                ->onDelete('cascade');

            $table->integer('photo_id')->unsigned();

            $table->foreign('photo_id')
                ->references('id')
                ->on('photos')
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
        Schema::dropIfExists('hashtag_photo');
    }
}
