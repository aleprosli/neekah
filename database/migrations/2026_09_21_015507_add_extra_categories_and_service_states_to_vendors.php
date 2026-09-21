<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A vendor does more than one thing and travels: the pivot holds every
     * category they work in and service_states every negeri they cover. Both
     * always contain the primary category_id and the home state, so a search
     * never has to check two places.
     */
    public function up(): void
    {
        Schema::create('category_vendor', function (Blueprint $table) {
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['vendor_id', 'category_id']);
            $table->index('category_id');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->json('service_states')->nullable()->after('state');
        });

        DB::table('vendors')->orderBy('id')->chunkById(200, function ($vendors): void {
            DB::table('category_vendor')->insert($vendors->map(fn ($vendor): array => [
                'vendor_id' => $vendor->id,
                'category_id' => $vendor->category_id,
            ])->all());

            foreach ($vendors as $vendor) {
                DB::table('vendors')->where('id', $vendor->id)->update([
                    'service_states' => json_encode([$vendor->state]),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_vendor');

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('service_states');
        });
    }
};
