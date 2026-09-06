<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Interrupteur d'accès au back-office.
 *
 * Le back-office expose des renseignements personnels de clientes (Loi 25).
 * Retirer l'accès à quelqu'un doit être immédiat et réversible : désactiver le
 * compte suffit, sans avoir à le supprimer — ce qui ferait perdre la trace de
 * qui a fait quoi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('actif')->default(true)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('actif');
        });
    }
};
