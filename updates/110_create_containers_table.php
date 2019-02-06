<?php

namespace Dieterholvoet\Contentblocks\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateContainersTable extends Migration
{
    public function up()
    {
        Schema::create('dieterholvoet_contentblocks_containers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');

            $table->text('host_type');
            $table->text('host_id');
            $table->text('container_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dieterholvoet_contentblocks_containers');
    }
}
