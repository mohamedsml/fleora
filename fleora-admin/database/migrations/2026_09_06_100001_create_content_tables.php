<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables de contenu du site vitrine.
 *
 * Bilinguisme : colonnes suffixées _fr / _en plutôt qu'une table de traductions.
 * Plus simple à requêter depuis l'API (pas de jointure par lecture) et plus simple
 * dans Filament (deux onglets). Les colonnes _en existent dès maintenant même vides —
 * les ajouter après coup imposerait une migration de toutes les données.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Types de produit : boîte à fleurs, boîte cadeau, coffret prénom, lot d'invités
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('nom_fr');
            $table->string('nom_en')->nullable();
            $table->string('slug_fr')->unique();
            $table->string('slug_en')->unique()->nullable();
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('publie')->default(true);
            $table->timestamps();

            $table->index(['publie', 'ordre']);
        });

        // Occasions : mariage, baby shower, anniversaire… Pages piliers SEO.
        Schema::create('occasions', function (Blueprint $table) {
            $table->id();
            $table->string('nom_fr');
            $table->string('nom_en')->nullable();
            $table->string('slug_fr')->unique();
            $table->string('slug_en')->unique()->nullable();
            $table->string('icone', 64)->nullable();
            $table->text('intro_fr')->nullable();
            $table->text('intro_en')->nullable();
            $table->longText('contenu_seo_fr')->nullable();
            $table->longText('contenu_seo_en')->nullable();
            $table->string('meta_title_fr')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->string('meta_description_fr', 320)->nullable();
            $table->string('meta_description_en', 320)->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('publie')->default(true);
            $table->timestamps();

            $table->index(['publie', 'ordre']);
        });

        // Créations : le catalogue visuel. Prix en fourchette « à partir de ».
        Schema::create('creations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('titre_fr');
            $table->string('titre_en')->nullable();
            $table->string('slug_fr')->unique();
            $table->string('slug_en')->unique()->nullable();
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();
            // Palette : ex. ["blush", "ivoire", "or"] — filtrable côté galerie
            $table->json('couleurs')->nullable();
            $table->unsignedInteger('prix_min')->nullable()->comment('en cents');
            $table->unsignedInteger('prix_max')->nullable()->comment('en cents');
            $table->boolean('vedette')->default(false);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('publie')->default(false);
            $table->timestamps();

            $table->index(['publie', 'vedette', 'ordre']);
            $table->index('product_type_id');
        });

        // Une création couvre plusieurs occasions (ex. un coffret prénom sert
        // au baptême ET au baby shower)
        Schema::create('creation_occasion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occasion_id')->constrained()->cascadeOnDelete();

            $table->unique(['creation_id', 'occasion_id']);
        });

        // Médias polymorphes : une création, une occasion ou un item de production
        // peuvent porter des images. `variantes` stocke les tailles WebP générées.
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('mediable');
            $table->string('chemin');
            $table->string('disque', 32)->default('public');
            $table->string('mime', 96)->nullable();
            $table->unsignedInteger('taille')->nullable()->comment('octets');
            $table->unsignedSmallInteger('largeur')->nullable();
            $table->unsignedSmallInteger('hauteur')->nullable();
            // alt écrit à la main : SEO image + accessibilité réelle
            $table->string('alt_fr')->nullable();
            $table->string('alt_en')->nullable();
            // ex. {"400":"...webp","800":"...webp","1200":"...webp","1600":"...webp"}
            $table->json('variantes')->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['mediable_type', 'mediable_id', 'ordre'], 'media_mediable_ordre_idx');
        });

        // Témoignages — publiés uniquement s'ils sont réels et consentis.
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occasion_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('creation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('auteur');
            $table->string('ville')->nullable();
            $table->text('texte_fr');
            $table->text('texte_en')->nullable();
            $table->unsignedTinyInteger('note')->nullable();
            $table->boolean('publie')->default(false);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['publie', 'ordre']);
        });

        // FAQ : lève les objections + SEO longue traîne (schema.org FAQPage)
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question_fr');
            $table->string('question_en')->nullable();
            $table->text('reponse_fr');
            $table->text('reponse_en')->nullable();
            $table->string('categorie', 64)->nullable();
            // Les 4 questions affichées sur l'accueil
            $table->boolean('sur_accueil')->default(false);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('publie')->default(true);
            $table->timestamps();

            $table->index(['publie', 'ordre']);
        });

        // Réglages globaux (singleton) : NAP, réseaux, horaires, bandeau
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('cle')->unique();
            $table->json('valeur')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('media');
        Schema::dropIfExists('creation_occasion');
        Schema::dropIfExists('creations');
        Schema::dropIfExists('occasions');
        Schema::dropIfExists('product_types');
    }
};
