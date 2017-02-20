<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommentPhotoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comment_photo', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('photo_id')->unsigned();

            $table->foreign('photo_id')
                ->references('id')
                ->on('photos')
                ->onDelete('cascade');

            $table->integer('comment_id')->unsigned();

            $table->foreign('comment_id')
                ->references('id')
                ->on('comments')
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
        Schema::dropIfExists('comment_photo');
    }
}
