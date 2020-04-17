<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportingPeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'reporting_periods',
            function (Blueprint $table) {
                $table->bigIncrements('id');

                // relationships
                $table->unsignedBigInteger('entity_id');

                // constraints
                $table->foreign('entity_id')->references('id')->on('entities');

                // attributes
                $table->integer('period_count');
                $table->enum('status', ['open', 'closed', 'adjusting'])->default('open');
                $table->year('year');

                // *permanent* deletion
                $table->dateTime('destroyed_at')->nullable();

                //soft deletion
                $table->softDeletes();

                $table->timestamps();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reporting_periods');
    }
}