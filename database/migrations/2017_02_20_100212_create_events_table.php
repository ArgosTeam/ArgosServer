<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name');
            $table->string('description')->nullable();
            $table->tinyInteger('public');
            $table->timestamp('start')->nullable();
            $table->timestamp('expires')->nullable();
            $table->integer('location_id')->unsigned();

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('cascade');
            
            $table->integer('profile_pic_id')
                ->unsigned()
                ->nullable();

            $table->foreign('profile_pic_id')
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
        Schema::dropIfExists('events');
    }
}
