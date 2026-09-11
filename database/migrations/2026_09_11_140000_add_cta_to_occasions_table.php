<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Libellé du bouton d'appel à l'action, propre à chaque occasion.
 *
 * « Créer pour mon mariage » convertit mieux qu'un « Créer ma Fleora »
 * générique : le bouton reprend les mots de la visiteuse et confirme qu'elle
 * est au bon endroit.
 *
 * En base plutôt qu'en fichier de langue : l'article varie selon le mot
 * (« mon mariage », « un baptême », « une graduation »), et une occasion
 * ajoutée depuis l'administration doit pouvoir avoir son libellé sans passer
 * par une modification du code.
 *
 * Nullable : sans valeur, la page retombe sur le CTA générique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occasions', function (Blueprint $table) {
            $table->string('cta_fr')->nullable()->after('intro_en');
            $table->string('cta_en')->nullable()->after('cta_fr');
        });
    }

    public function down(): void
    {
        Schema::table('occasions', function (Blueprint $table) {
            $table->dropColumn(['cta_fr', 'cta_en']);
        });
    }
};
