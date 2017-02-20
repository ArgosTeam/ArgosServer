<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('firstName');
            $table->string('lastName');
            $table->enum('sex', ['male', 'female', 'unsure']);
            $table->string('email');
            $table->string('username');
            $table->string('password');
            $table->integer('profile_pic_id')
                ->unsigned()
                ->nullable();

            $table->foreign('profile_pic_id')
                ->references('id')
                ->on('photos')
                ->onDelete('cascade');
            
            $table->string('remember_token')
                ->nullable();
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
        Schema::dropIfExists('users');
    }
}
