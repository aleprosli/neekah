<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every payment in one ledger. Booking payments, Neekah Pro, boost packs
     * and Neekah Kenangan used to live in four tables with four webhooks;
     * they are now rows of `payments`, told apart by `purpose`, with the
     * account the money went to (`merchant`: Neekah, or the vendor for a
     * booking) and the gateway that took it (herepay, manual, and whatever
     * comes next) as plain columns.
     *
     * What a payment is for is linked by explicit, nullable foreign keys
     * (booking, vendor, wedding, album), not a polymorphic pair: the database
     * keeps them honest and a ledger row outlives what it paid for.
     * Purpose-specific facts (a plan and its period, a pack's tokens, an
     * album's tier) sit in `details`.
     *
     * The rows of the three old tables are copied in, the boost ledger is
     * pointed at them, and the old tables go.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->change();
            $table->string('purpose', 20)->default('booking')->after('reference');
            $table->string('merchant', 10)->default('vendor')->after('purpose');
            $table->foreignId('vendor_id')->nullable()->after('booking_id')->constrained()->nullOnDelete();
            $table->foreignId('wedding_id')->nullable()->after('vendor_id')->constrained()->nullOnDelete();
            $table->foreignId('camera_album_id')->nullable()->after('wedding_id')->constrained()->nullOnDelete();
            $table->string('currency', 3)->default('MYR')->after('amount');
            $table->string('method')->nullable()->default(null)->change();
            $table->string('gateway', 30)->default('manual')->change();
            $table->string('gateway_invoice')->nullable()->after('gateway_reference')->index();
            $table->string('gateway_transaction_id')->nullable()->after('gateway_invoice');
            $table->string('gateway_status', 60)->nullable()->after('gateway_transaction_id');
            $table->json('details')->nullable()->after('note');
            $table->timestamp('last_checked_at')->nullable()->after('expires_at');

            $table->index(['purpose', 'status']);
            $table->index(['vendor_id', 'purpose']);
            $table->index(['wedding_id', 'purpose']);
            $table->index('gateway_reference');
        });

        // Booking payments were the only rows: gateway said "sandbox", the
        // method said how.
        DB::table('payments')->where('method', 'herepay')->update(['gateway' => 'herepay', 'method' => null]);
        DB::table('payments')->where('gateway', 'sandbox')->update(['gateway' => 'manual']);
        DB::table('payments')->whereNotNull('booking_id')->update([
            'vendor_id' => DB::table('bookings')->select('vendor_id')->whereColumn('bookings.id', 'payments.booking_id')->limit(1),
        ]);

        if (Schema::hasTable('vendor_subscriptions')) {
            foreach (DB::table('vendor_subscriptions')->orderBy('id')->get() as $row) {
                DB::table('payments')->insert([
                    'reference' => $row->reference,
                    'purpose' => 'vendor_pro',
                    'merchant' => 'neekah',
                    'vendor_id' => $row->vendor_id,
                    'recorded_by' => $row->added_by,
                    'amount' => $row->amount,
                    'status' => $row->status,
                    'gateway' => $row->gateway,
                    'gateway_reference' => $row->gateway_reference,
                    'payment_url' => $row->payment_url,
                    'note' => $row->note,
                    'details' => json_encode(array_filter(['plan' => $row->plan, 'starts_at' => $row->starts_at, 'ends_at' => $row->ends_at], fn ($value) => $value !== null)),
                    'paid_at' => $row->paid_at,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }

        if (Schema::hasTable('boost_purchases')) {
            foreach (DB::table('boost_purchases')->orderBy('id')->get() as $row) {
                $id = DB::table('payments')->insertGetId([
                    'reference' => $row->reference,
                    'purpose' => 'boost_tokens',
                    'merchant' => 'neekah',
                    'vendor_id' => $row->vendor_id,
                    'recorded_by' => $row->user_id,
                    'amount' => $row->amount,
                    'status' => $row->status,
                    'gateway' => $row->gateway,
                    'gateway_reference' => $row->gateway_reference,
                    'payment_url' => $row->payment_url,
                    'details' => json_encode(['pack' => $row->pack, 'tokens' => (int) $row->tokens]),
                    'paid_at' => $row->paid_at,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);

                DB::table('vendor_boost_entries')
                    ->where('source_type', 'App\\Models\\BoostPurchase')
                    ->where('source_id', $row->id)
                    ->update(['source_type' => 'App\\Models\\Payment', 'source_id' => $id]);
            }
        }

        if (Schema::hasTable('camera_purchases')) {
            foreach (DB::table('camera_purchases')->orderBy('id')->get() as $row) {
                DB::table('payments')->insert([
                    'reference' => $row->reference,
                    'purpose' => 'kenangan',
                    'merchant' => 'neekah',
                    'wedding_id' => $row->wedding_id,
                    'camera_album_id' => $row->camera_album_id,
                    'recorded_by' => $row->added_by ?? $row->user_id,
                    'amount' => $row->amount,
                    'status' => $row->status,
                    'gateway' => $row->gateway,
                    'gateway_reference' => $row->gateway_reference,
                    'payment_url' => $row->payment_url,
                    'note' => $row->note,
                    'details' => json_encode(array_filter([
                        'tier' => $row->tier,
                        'kind' => $row->kind,
                        'album_title' => $row->album_title,
                        'album_event_date' => $row->album_event_date,
                    ], fn ($value) => $value !== null)),
                    'paid_at' => $row->paid_at,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }

        Schema::dropIfExists('vendor_subscriptions');
        Schema::dropIfExists('boost_purchases');
        Schema::dropIfExists('camera_purchases');
    }

    /**
     * Back to four tables. The rows go back where they came from, so a
     * rollback loses nothing but the columns only the ledger has.
     */
    public function down(): void
    {
        Schema::create('vendor_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('plan');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending')->index();
            $table->string('gateway');
            $table->string('gateway_reference')->nullable()->index();
            $table->string('payment_url', 2048)->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('boost_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference', 20)->unique();
            $table->string('pack', 20);
            $table->unsignedInteger('tokens');
            $table->decimal('amount', 10, 2);
            $table->string('status', 20)->default('pending');
            $table->string('gateway', 20);
            $table->string('gateway_reference')->nullable();
            $table->text('payment_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('camera_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('camera_album_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('tier', 10);
            $table->string('kind', 10)->default('new');
            $table->string('album_title', 120)->nullable();
            $table->date('album_event_date')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending')->index();
            $table->string('gateway', 20);
            $table->string('gateway_reference')->nullable()->index();
            $table->string('payment_url', 2048)->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        foreach (DB::table('payments')->where('purpose', '!=', 'booking')->orderBy('id')->get() as $row) {
            $details = json_decode((string) $row->details, true) ?: [];
            $shared = ['reference' => $row->reference, 'amount' => $row->amount, 'status' => $row->status, 'gateway' => $row->gateway, 'gateway_reference' => $row->gateway_reference, 'payment_url' => $row->payment_url, 'paid_at' => $row->paid_at, 'created_at' => $row->created_at, 'updated_at' => $row->updated_at];

            match ($row->purpose) {
                'vendor_pro' => DB::table('vendor_subscriptions')->insert([...$shared, 'vendor_id' => $row->vendor_id, 'plan' => $details['plan'] ?? 'monthly', 'added_by' => $row->gateway === 'manual' ? $row->recorded_by : null, 'note' => $row->note, 'starts_at' => $details['starts_at'] ?? null, 'ends_at' => $details['ends_at'] ?? null]),
                'boost_tokens' => DB::table('vendor_boost_entries')
                    ->where('source_type', 'App\\Models\\Payment')->where('source_id', $row->id)
                    ->update(['source_type' => 'App\\Models\\BoostPurchase', 'source_id' => DB::table('boost_purchases')->insertGetId([...$shared, 'vendor_id' => $row->vendor_id, 'user_id' => $row->recorded_by, 'pack' => $details['pack'] ?? 'small', 'tokens' => $details['tokens'] ?? 0])]),
                'kenangan' => DB::table('camera_purchases')->insert([...$shared, 'wedding_id' => $row->wedding_id, 'camera_album_id' => $row->camera_album_id, 'user_id' => $row->gateway === 'manual' ? null : $row->recorded_by, 'added_by' => $row->gateway === 'manual' ? $row->recorded_by : null, 'tier' => $details['tier'] ?? 'basic', 'kind' => $details['kind'] ?? 'new', 'album_title' => $details['album_title'] ?? null, 'album_event_date' => $details['album_event_date'] ?? null, 'note' => $row->note]),
                default => null,
            };
        }

        DB::table('payments')->where('purpose', '!=', 'booking')->delete();
        DB::table('payments')->where('gateway', 'herepay')->update(['method' => 'herepay']);
        DB::table('payments')->where('gateway', 'manual')->update(['gateway' => 'sandbox']);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['purpose', 'status']);
            $table->dropIndex(['vendor_id', 'purpose']);
            $table->dropIndex(['wedding_id', 'purpose']);
            $table->dropIndex(['gateway_reference']);
            $table->dropIndex(['gateway_invoice']);
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropConstrainedForeignId('wedding_id');
            $table->dropConstrainedForeignId('camera_album_id');
            $table->dropColumn(['purpose', 'merchant', 'currency', 'gateway_invoice', 'gateway_transaction_id', 'gateway_status', 'details', 'last_checked_at']);
        });
    }
};
