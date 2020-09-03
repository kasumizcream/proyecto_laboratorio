<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoctoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctores', function (Blueprint $table) {
            $table->id();
            $table->char('tipo_documento', 1)->comment('1-Dni/2-Ruc')->default(1);
            $table->integer('nro_documento');
            $table->string('nombres');
            $table->string('apellido_materno')->nullable();
            $table->string('apellido_paterno');
            $table->string('firma');
            $table->integer('nro_colegiatura');
            $table->date('fecha_nacimiento');
            $table->integer('edad');
            $table->char('sexo', 1)->comment('1-Femenino/2-Masculino')->default(1);
            $table->integer('nro_celular')->nullable();
            $table->string('email')->nullable();
            $table->string('direccion');
            $table->text('referencias')->nullable();
            $table->char('tipo_doctor', 1)->comment('1-Nuevo/2-Antiguo')->default(1);
            $table->text('observaciones')->nullable();
            $table->char('estado', 1)->comment('0-Desactivo/1-Activo')->default(1);

            $table->unsignedBigInteger('especialidad_id');
            $table->foreign('especialidad_id')->references('id')->on('especialidades');

            $table->unsignedBigInteger('ubigeo_id');
            $table->foreign('ubigeo_id')->references('id')->on('ubigeo');

            $table->unsignedBigInteger('rol_id');
            $table->foreign('rol_id')->references('id')->on('roles');

            $table->unsignedBigInteger('insert_user_id')->comment('Usuario que hizo el registro');
            $table->foreign('insert_user_id')->references('id')->on('users');

            $table->unsignedBigInteger('edit_user_id')->comment('Usuario que editó el registro')->nullable();
            $table->foreign('edit_user_id')->references('id')->on('users');

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
        Schema::dropIfExists('doctores');
    }
}
