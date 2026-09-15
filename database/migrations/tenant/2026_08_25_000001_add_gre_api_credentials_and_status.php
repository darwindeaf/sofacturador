<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGreApiCredentialsAndStatus extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('gre_client_id', 100)->nullable()->after('soap_url');
            $table->text('gre_client_secret')->nullable()->after('gre_client_id');
        });

        Schema::table('dispatches', function (Blueprint $table) {
            $table->string('gre_ticket', 36)->nullable()->after('soap_shipping_response');
            $table->char('gre_status', 2)->nullable()->after('gre_ticket');
            $table->json('gre_response')->nullable()->after('gre_status');
            $table->dateTime('gre_sent_at')->nullable()->after('gre_response');
            $table->dateTime('gre_checked_at')->nullable()->after('gre_sent_at');
            $table->index('gre_ticket');
        });
    }

    public function down()
    {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->dropIndex(['gre_ticket']);
            $table->dropColumn([
                'gre_ticket',
                'gre_status',
                'gre_response',
                'gre_sent_at',
                'gre_checked_at',
            ]);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['gre_client_id', 'gre_client_secret']);
        });
    }
}
