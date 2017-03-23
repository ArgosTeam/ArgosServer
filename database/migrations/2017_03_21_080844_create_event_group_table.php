<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_group', function (Blueprint $table) {
            $table->increments('id');
            
            $table->integer('event_id')->unsigned();
            
            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->onDelete('cascade');

            $table->integer('group_id')->unsigned();

            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
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
        Schema::dropIfExists('event_group');
    }
}
