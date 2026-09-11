<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Statistiques de visite.
 *
 * ⚠️ AUCUNE DONNÉE PERSONNELLE. Pas d'adresse IP en clair, pas de témoin, pas
 * d'identifiant persistant. Le visiteur est distingué par une empreinte salée
 * et renouvelée chaque jour : elle permet de compter les sessions d'une
 * journée sans permettre de suivre quelqu'un d'un jour à l'autre, ni de
 * remonter à une personne.
 *
 * C'est ce qui dispense de bannière de consentement sous la Loi 25 — et ce qui
 * rend ces statistiques honnêtes plutôt que conformes de justesse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visites', function (Blueprint $table) {
            $table->id();

            // Chemin sans le domaine ni les paramètres : « /creations » et non
            // l'URL complète. Les filtres de galerie créeraient sinon des
            // milliers de lignes distinctes pour une même page.
            $table->string('chemin', 255);

            $table->string('langue', 5)->default('fr');

            // Domaine du référent uniquement : « instagram.com », pas l'URL
            // exacte du profil ou de la publication.
            $table->string('source', 120)->nullable();

            $table->string('appareil', 16)->nullable()->comment('mobile|tablette|ordinateur');

            // Empreinte du jour : sha256(ip + user-agent + sel + date). Ne
            // permet ni de retrouver l'IP, ni de relier deux jours.
            $table->char('empreinte', 64)->nullable();

            // Renseigné sur une fiche création ou une page occasion : c'est ce
            // qui dira quelles pièces intéressent réellement.
            $table->string('entite_type', 32)->nullable();
            $table->unsignedBigInteger('entite_id')->nullable();

            // `useCurrent()` ne sert que de filet pour une insertion faite en
            // SQL brut : c'est le modèle qui horodate. MariaDB écrit en UTC
            // alors que l'application vit en America/Toronto — une ligne
            // horodatée par la base tombe quatre heures dans le futur et
            // échappe aux périodes du tableau de bord. Voir App\Models\Visite.
            $table->timestamp('created_at')->useCurrent();

            // Les trois requêtes du tableau de bord : par période, par page,
            // par source.
            $table->index('created_at');
            $table->index(['chemin', 'created_at']);
            $table->index(['source', 'created_at']);
            $table->index(['entite_type', 'entite_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visites');
    }
};
