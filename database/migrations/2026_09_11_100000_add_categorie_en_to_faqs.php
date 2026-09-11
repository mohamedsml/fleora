<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rend bilingue la catégorie des questions fréquentes.
 *
 * Les catégories sont affichées comme titres de section sur /faq : sans colonne
 * anglaise, la page /en/faq afficherait « Commander » et « Livraison » au milieu
 * de questions traduites.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->string('categorie_en', 64)->nullable()->after('categorie');
        });

        // Cohérence avec les autres champs bilingues du projet, où la colonne
        // française porte déjà le suffixe.
        Schema::table('faqs', function (Blueprint $table) {
            $table->renameColumn('categorie', 'categorie_fr');
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->renameColumn('categorie_fr', 'categorie');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn('categorie_en');
        });
    }
};
