<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdenAtencionAnalisisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orden_atencion_analisis', function (Blueprint $table) {
            $table->id();
            $table->string('analisis');
            $table->string('muestras');
            $table->char('forma_pago', 1)->comment('1-Contado/2-Transferencia/3-Yape/4-TCredito')->default(1);
            $table->string('factura_boleta');
            $table->char('estado_analisis', 1)->comment('1-Pendiente/2-Atendido/3-Pagado/4-No_Pagado/5-Terminado/6-Anulado')->default(1);
            $table->char('estado', 1)->comment('0-Desactivo/1-Activo')->default(1);

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
        Schema::dropIfExists('orden_atencion_analisis');
    }
}
