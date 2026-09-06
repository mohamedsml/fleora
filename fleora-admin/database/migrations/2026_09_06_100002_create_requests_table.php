<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demandes de soumission — LA conversion du site.
 *
 * ⚠️ DONNÉES PERSONNELLES (Loi 25, Québec)
 * Cette table contient des renseignements personnels : nom, courriel, téléphone,
 * ville, date d'événement. Conséquences :
 *   - consentement explicite obligatoire et horodaté (colonnes consentement_*)
 *   - politique de conservation : suppression 24 mois après le dernier contact
 *   - toute perte est un incident de confidentialité à consigner
 *   - droit d'accès et de suppression à honorer sous 30 jours
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('numero_suivi', 24)->unique();

            // ── Étape 1 : le projet ──────────────────────────────────────────
            $table->foreignId('occasion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('occasion_autre')->nullable();
            $table->date('date_evenement')->nullable();
            $table->foreignId('product_type_id')->nullable()->constrained()->nullOnDelete();
            // Tranches plutôt qu'un entier : boutons rapides sur mobile
            $table->string('quantite', 16)->nullable()->comment('1|2-5|6-15|16-50|50+');

            // ── Étape 2 : la personnalisation (tout optionnel) ───────────────
            $table->string('texte_a_inscrire', 60)->nullable();
            $table->json('couleurs')->nullable();
            $table->string('theme', 64)->nullable();
            $table->string('fleurs', 32)->nullable()->comment('artificielles|naturelles|a_conseiller');
            // Tranche large et optionnelle : champ de qualification n°1
            $table->string('budget', 32)->nullable();
            $table->text('commentaires')->nullable();
            // Création de référence si la demande vient d'un CTA « je veux ça »
            $table->foreignId('creation_reference_id')->nullable()->constrained('creations')->nullOnDelete();

            // ── Étape 3 : les coordonnées ────────────────────────────────────
            $table->string('nom');
            $table->string('courriel');
            $table->string('telephone', 32)->nullable();
            $table->string('ville')->nullable();
            $table->string('moyen_prefere', 24)->nullable()->comment('courriel|texto|whatsapp|appel');
            // Seul moyen de mesurer le bouche-à-oreille, invisible dans l'analytics
            $table->string('source', 64)->nullable();

            // ── Loi 25 : traçabilité du consentement ─────────────────────────
            $table->boolean('consentement')->default(false);
            $table->timestamp('consentement_le')->nullable();
            $table->boolean('infolettre')->default(false);

            // ── Suivi commercial ─────────────────────────────────────────────
            $table->string('statut', 32)->default('nouvelle')
                ->comment('nouvelle|en_cours|devis_envoye|confirmee|livree|perdue');
            $table->text('notes_internes')->nullable();
            $table->timestamp('repondu_le')->nullable();

            // ── Métadonnées techniques (anti-spam, analytics) ────────────────
            $table->string('langue', 5)->default('fr');
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 512)->nullable();

            $table->timestamps();
            // Suppression douce : ne pas perdre une demande sur une fausse manip
            $table->softDeletes();

            $table->index(['statut', 'created_at']);
            $table->index('date_evenement');
            $table->index('courriel');
        });

        // Images d'inspiration jointes à une demande (max 5, 5 Mo chacune)
        Schema::create('request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained()->cascadeOnDelete();
            $table->string('chemin');
            $table->string('nom_original')->nullable();
            $table->string('mime', 96)->nullable();
            $table->unsignedInteger('taille')->nullable();
            $table->timestamps();

            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_attachments');
        Schema::dropIfExists('requests');
    }
};
