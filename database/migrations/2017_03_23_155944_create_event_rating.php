
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventRating extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_rating', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('event_id')->unsigned();

            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->onDelete('cascade');

            $table->integer('rating_type_id')->unsigned();
            
            $table->foreign('rating_type_id')
                ->references('id')
                ->on('rating_types')
                ->onDelete('cascade');

            $table->integer('rate');
            
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
        Schema::dropIfExists('event_rating');
    }
}
