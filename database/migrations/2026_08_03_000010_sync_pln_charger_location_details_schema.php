<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sinkronkan schema pln_charger_location_details dgn kolom yang sudah dipakai
 * code (SpkluCsvImportService, PlnChargerLocationDetail, hydrate PLN):
 *
 *  - `category_charger_id`  — pengganti nama `charger_category_id` (relation
 *    & importer memakai `category_charger_id`). Bila kolom lama masih ada di
 *    DB yang migrasinya belum pernah jalan, rename.
 *  - `charging_type_id`     — FK ke charging_types (kategori PLN uppercase).
 *
 * Schema drift ini muncul karena kolom pernah ditambah manual di DB, bukan
 * lewat migration. Migration ini membuat fresh install konsisten dgn
 * production tanpa mengubah DB yang sudah benar (guard hasColumn).
 */
return new class extends Migration
{
    protected $connection = 'ev';

    public function up(): void
    {
        $table = 'pln_charger_location_details';

        Schema::connection('ev')->table($table, function (Blueprint $table) {
            $hasOld = Schema::connection('ev')->hasColumn('pln_charger_location_details', 'charger_category_id');
            $hasNew = Schema::connection('ev')->hasColumn('pln_charger_location_details', 'category_charger_id');

            if ($hasOld && !$hasNew) {
                $table->renameColumn('charger_category_id', 'category_charger_id');
            }

            if (!Schema::connection('ev')->hasColumn('pln_charger_location_details', 'charging_type_id')) {
                $table->unsignedBigInteger('charging_type_id')->nullable()->after('merk_charger_id');
            }
        });
    }

    public function down(): void
    {
        // Rename/revert tidak aman bila data sudah ada — biarkan seperti adanya.
    }
};
