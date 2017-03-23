<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotoRating extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photo_rating', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('photo_id')->unsigned();

            $table->foreign('photo_id')
                ->references('id')
                ->on('photos')
                ->onDelete('cascade');

            $table->integer('rating_type_id')->unsigned();
            
            $table->foreign('rating_type_id')
                ->references('id')
                ->on('rating_types')
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
        Schema::dropIfExists('photo_rating');
    }
}
