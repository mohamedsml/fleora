<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables métier : devis → commande → production → facture, plus le stock.
 *
 * Montants en CENTS (entiers) — jamais en flottants. Un float sur de l'argent
 * produit des écarts d'arrondi qui finissent par des factures fausses.
 *
 * TPS/TVQ modélisées dès maintenant : les inscrire après coup dans un système
 * de facturation en production est pénible. Taux au 2026 : TPS 5 %, TVQ 9,975 %.
 * L'obligation d'inscription démarre à 30 000 $ de revenus sur 12 mois glissants ;
 * en deçà elle reste volontaire — les colonnes existent, leur usage suit ta situation.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Devis ────────────────────────────────────────────────────────────
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('numero', 24)->unique();

            $table->string('client_nom');
            $table->string('client_courriel');
            $table->string('client_telephone', 32)->nullable();

            $table->unsignedInteger('sous_total')->default(0)->comment('cents');
            $table->unsignedInteger('tps')->default(0)->comment('cents');
            $table->unsignedInteger('tvq')->default(0)->comment('cents');
            $table->unsignedInteger('total')->default(0)->comment('cents');

            $table->date('valide_jusqu_au')->nullable();
            $table->string('statut', 24)->default('brouillon')
                ->comment('brouillon|envoye|accepte|refuse|expire');
            $table->text('notes')->nullable();
            $table->text('conditions')->nullable();
            $table->timestamp('envoye_le')->nullable();
            $table->timestamp('repondu_le')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['statut', 'created_at']);
        });

        // Lignes de devis — description libre : chaque création est unique
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->text('details')->nullable();
            $table->unsignedSmallInteger('quantite')->default(1);
            $table->unsignedInteger('prix_unitaire')->default(0)->comment('cents');
            $table->unsignedInteger('total')->default(0)->comment('cents');
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->index('quote_id');
        });

        // ── Commandes ────────────────────────────────────────────────────────
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('numero', 24)->unique();

            $table->string('client_nom');
            $table->string('client_courriel');
            $table->string('client_telephone', 32)->nullable();

            $table->unsignedInteger('sous_total')->default(0)->comment('cents');
            $table->unsignedInteger('tps')->default(0)->comment('cents');
            $table->unsignedInteger('tvq')->default(0)->comment('cents');
            $table->unsignedInteger('total')->default(0)->comment('cents');

            // Dépôt : pratique courante sur le sur-mesure
            $table->unsignedInteger('depot_montant')->default(0)->comment('cents');
            $table->timestamp('depot_paye_le')->nullable();
            $table->timestamp('solde_paye_le')->nullable();

            $table->date('date_evenement')->nullable();
            $table->date('date_livraison_prevue')->nullable();
            $table->string('mode_livraison', 32)->nullable()->comment('cueillette|livraison|poste');
            $table->text('adresse_livraison')->nullable();

            $table->string('statut', 24)->default('confirmee')
                ->comment('confirmee|en_production|prete|livree|annulee');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['statut', 'date_livraison_prevue']);
            $table->index('date_evenement');
        });

        // ── Production ───────────────────────────────────────────────────────
        // Vue kanban dans Filament : à faire → en cours → assemblé → photographié → validé → prêt
        Schema::create('production_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->text('personnalisation')->nullable()->comment('prénom, couleurs, thème');
            $table->unsignedSmallInteger('quantite')->default(1);
            $table->string('statut', 24)->default('a_faire')
                ->comment('a_faire|en_cours|assemble|photographie|valide|pret');
            $table->string('assignee')->nullable();
            $table->text('notes')->nullable();
            // Photo envoyée à la cliente avant livraison (étape 3 du parcours)
            $table->foreignId('photo_validation_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamp('commence_le')->nullable();
            $table->timestamp('termine_le')->nullable();
            $table->timestamps();

            $table->index(['statut', 'order_id']);
        });

        // ── Facturation ──────────────────────────────────────────────────────
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            // Numérotation séquentielle SANS TROU — exigence fiscale.
            // Nullable à dessein : le numéro n'est attribué qu'à l'ÉMISSION.
            // Le donner dès le brouillon ferait qu'un brouillon abandonné
            // consomme un numéro et laisse un trou dans la séquence.
            $table->string('numero', 24)->nullable()->unique();

            $table->string('client_nom');
            $table->string('client_courriel');
            $table->text('client_adresse')->nullable();

            $table->unsignedInteger('sous_total')->default(0)->comment('cents');
            $table->unsignedInteger('tps')->default(0)->comment('cents');
            $table->unsignedInteger('tvq')->default(0)->comment('cents');
            $table->unsignedInteger('total')->default(0)->comment('cents');

            // Numéros d'inscription aux taxes, figés au moment de l'émission
            $table->string('numero_tps', 32)->nullable();
            $table->string('numero_tvq', 32)->nullable();

            $table->date('emise_le')->nullable();
            $table->date('echeance_le')->nullable();
            $table->timestamp('payee_le')->nullable();
            $table->string('mode_paiement', 32)->nullable();
            $table->string('statut', 24)->default('brouillon')
                ->comment('brouillon|emise|payee|annulee');
            $table->text('notes')->nullable();
            $table->timestamps();
            // Jamais de suppression dure sur une facture émise
            $table->softDeletes();

            $table->index(['statut', 'emise_le']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedSmallInteger('quantite')->default(1);
            $table->unsignedInteger('prix_unitaire')->default(0)->comment('cents');
            $table->unsignedInteger('total')->default(0)->comment('cents');
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->index('invoice_id');
        });

        // ── Stock ────────────────────────────────────────────────────────────
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('reference', 64)->nullable();
            $table->string('categorie', 32)->comment('fleur|boite|ruban|accessoire|autre');
            $table->string('unite', 24)->default('unite');
            $table->decimal('stock_actuel', 10, 2)->default(0);
            $table->decimal('seuil_alerte', 10, 2)->default(0);
            $table->unsignedInteger('cout_unitaire')->default(0)->comment('cents');
            $table->string('fournisseur')->nullable();
            $table->string('couleur', 32)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['categorie', 'actif']);
        });

        // Mouvements de stock — traçabilité complète (entrées, sorties, ajustements)
        Schema::create('material_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 24)->comment('entree|sortie|ajustement|perte');
            $table->decimal('quantite', 10, 2);
            $table->decimal('stock_apres', 10, 2)->nullable();
            $table->string('motif')->nullable();
            $table->timestamps();

            $table->index(['material_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_movements');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('production_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
