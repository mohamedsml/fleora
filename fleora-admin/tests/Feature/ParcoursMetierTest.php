<?php

namespace Tests\Feature;

use App\Enums\StatutFacture;
use App\Enums\StatutProduction;
use App\Enums\TypeMouvement;
use App\Models\Creation;
use App\Models\CustomRequest;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\Occasion;
use App\Models\Order;
use App\Models\ProductType;
use App\Models\Quote;
use App\Models\SiteSetting;
use App\Support\Taxes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Vérifie les règles métier qui coûtent cher si elles cassent : numérotation
 * fiscale, calcul des taxes, immuabilité des factures, traçabilité du stock.
 */
class ParcoursMetierTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function il_genere_un_numero_de_suivi_unique(): void
    {
        $a = CustomRequest::create(['nom' => 'A', 'courriel' => 'a@ex.ca']);
        $b = CustomRequest::create(['nom' => 'B', 'courriel' => 'b@ex.ca']);

        $this->assertMatchesRegularExpression('/^FL-\d{4}-[A-Z0-9]{4}$/', $a->numero_suivi);
        $this->assertNotSame($a->numero_suivi, $b->numero_suivi);
    }

    #[Test]
    public function il_numerote_les_documents_sans_trou(): void
    {
        $numeros = [];

        for ($i = 0; $i < 3; $i++) {
            $numeros[] = Quote::create(['client_nom' => 'X', 'client_courriel' => 'x@ex.ca'])->numero;
        }

        $annee = now()->year;

        $this->assertSame(
            ["DEV-{$annee}-0001", "DEV-{$annee}-0002", "DEV-{$annee}-0003"],
            $numeros,
        );
    }

    #[Test]
    public function il_ventile_la_tps_et_la_tvq_sur_le_sous_total(): void
    {
        config()->set('fleora.taxes.inscrit', true);

        // 100,00 $ → TPS 5,00 $ + TVQ 9,98 $ (arrondi au cent) = 114,98 $
        $this->assertSame(
            ['sous_total' => 10000, 'tps' => 500, 'tvq' => 998, 'total' => 11498],
            Taxes::ventiler(10000),
        );
    }

    #[Test]
    public function il_ne_facture_aucune_taxe_sans_inscription(): void
    {
        config()->set('fleora.taxes.inscrit', false);

        $this->assertSame(
            ['sous_total' => 10000, 'tps' => 0, 'tvq' => 0, 'total' => 10000],
            Taxes::ventiler(10000),
        );
    }

    #[Test]
    public function un_oubli_d_argument_ne_fait_pas_facturer_de_taxes(): void
    {
        // Le défaut suit l'inscription de l'entreprise, jamais `true` :
        // facturer des taxes sans être inscrit est une infraction.
        config()->set('fleora.taxes.inscrit', false);

        $this->assertSame(0, Taxes::ventiler(10000)['tps']);
    }

    #[Test]
    public function il_recalcule_le_devis_quand_ses_lignes_changent(): void
    {
        config()->set('fleora.taxes.inscrit', true);

        $devis = Quote::create(['client_nom' => 'Marie', 'client_courriel' => 'marie@ex.ca']);

        $ligne = $devis->items()->create([
            'description' => 'Boîte à fleurs prénom',
            'quantite' => 3,
            'prix_unitaire' => 4500,
        ]);

        $this->assertSame(13500, $ligne->total);
        $this->assertSame(13500, $devis->refresh()->sous_total);
        $this->assertSame(15522, $devis->total);

        $ligne->delete();

        $this->assertSame(0, $devis->refresh()->total);
    }

    #[Test]
    public function un_brouillon_de_facture_ne_consomme_pas_de_numero(): void
    {
        $facture = Invoice::create([
            'client_nom' => 'Sophie',
            'client_courriel' => 'sophie@ex.ca',
            'statut' => StatutFacture::Brouillon,
        ]);

        $this->assertNull($facture->numero);
    }

    #[Test]
    public function une_facture_emise_ne_peut_plus_etre_modifiee(): void
    {
        $facture = Invoice::create(['client_nom' => 'Sophie', 'client_courriel' => 'sophie@ex.ca']);
        $facture->items()->create(['description' => 'Coffret baptême', 'quantite' => 2, 'prix_unitaire' => 6000]);

        $facture->refresh()->emettre();

        $this->assertMatchesRegularExpression('/^FAC-\d{4}-0001$/', $facture->numero);
        $this->assertSame(StatutFacture::Emise, $facture->statut);
        $this->assertSame(12000, $facture->sous_total);

        $facture->client_nom = 'Autre personne';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/ne peut plus être modifié/');

        $facture->save();
    }

    #[Test]
    public function une_facture_emise_accepte_son_encaissement(): void
    {
        $facture = Invoice::create(['client_nom' => 'Ana', 'client_courriel' => 'ana@ex.ca']);
        $facture->emettre();

        $facture->update([
            'statut' => StatutFacture::Payee,
            'payee_le' => now(),
            'mode_paiement' => 'virement',
        ]);

        $this->assertSame(StatutFacture::Payee, $facture->refresh()->statut);
    }

    #[Test]
    public function il_horodate_la_production_au_franchissement_des_etapes(): void
    {
        $commande = Order::create(['client_nom' => 'Léa', 'client_courriel' => 'lea@ex.ca']);
        $item = $commande->productionItems()->create(['description' => 'Boîte mariage']);

        $this->assertNull($item->commence_le);

        $item->update(['statut' => StatutProduction::EnCours]);
        $this->assertNotNull($item->commence_le);
        $this->assertNull($item->termine_le);

        $item->update(['statut' => StatutProduction::Pret]);
        $this->assertNotNull($item->termine_le);
    }

    #[Test]
    public function il_calcule_l_avancement_d_une_commande(): void
    {
        $commande = Order::create(['client_nom' => 'Ivy', 'client_courriel' => 'ivy@ex.ca']);

        $this->assertNull($commande->avancement(), 'Sans item, l’avancement n’a pas de sens');

        $commande->productionItems()->create(['description' => 'A', 'statut' => StatutProduction::Pret]);
        $commande->productionItems()->create(['description' => 'B', 'statut' => StatutProduction::AFaire]);

        $this->assertSame(50, $commande->avancement());
    }

    #[Test]
    public function il_trace_chaque_mouvement_de_stock(): void
    {
        $ruban = Material::create([
            'nom' => 'Ruban satin ivoire',
            'categorie' => 'ruban',
            'unite' => 'metre',
            'stock_actuel' => 0,
            'seuil_alerte' => 10,
            'cout_unitaire' => 150,
        ]);

        $ruban->mouvement(TypeMouvement::Entree, 50, 'Réception fournisseur');
        $this->assertSame(50.0, (float) $ruban->refresh()->stock_actuel);

        $ruban->mouvement(TypeMouvement::Sortie, 12.5, 'Commande CMD-0001');
        $this->assertSame(37.5, (float) $ruban->refresh()->stock_actuel);
        $this->assertSame(5625, $ruban->valeurStock());
        $this->assertSame(2, $ruban->movements()->count());

        // Un ajustement se saisit signé
        $ruban->mouvement(TypeMouvement::Ajustement, -2, 'Inventaire physique');
        $this->assertSame(35.5, (float) $ruban->refresh()->stock_actuel);
    }

    #[Test]
    public function il_signale_les_matieres_sous_le_seuil(): void
    {
        Material::create(['nom' => 'Boîte ronde', 'categorie' => 'boite', 'stock_actuel' => 3, 'seuil_alerte' => 10]);
        Material::create(['nom' => 'Pivoine', 'categorie' => 'fleur', 'stock_actuel' => 80, 'seuil_alerte' => 20]);

        $this->assertSame(['Boîte ronde'], Material::aRecommander()->pluck('nom')->all());
    }

    #[Test]
    public function il_retombe_sur_le_francais_sans_traduction_anglaise(): void
    {
        $mariage = Occasion::create(['nom_fr' => 'Mariage', 'nom_en' => 'Wedding', 'slug_fr' => 'mariage']);
        $bapteme = Occasion::create(['nom_fr' => 'Baptême', 'slug_fr' => 'bapteme']);

        $this->assertSame('Wedding', $mariage->t('nom', 'en'));
        $this->assertSame('Baptême', $bapteme->t('nom', 'en'));
        $this->assertSame('Baptême', $bapteme->t('nom', 'fr'));
    }

    #[Test]
    public function il_formate_la_fourchette_de_prix(): void
    {
        $type = ProductType::create(['nom_fr' => 'Boîte à fleurs', 'slug_fr' => 'boite-a-fleurs']);

        $fourchette = Creation::create([
            'product_type_id' => $type->id,
            'titre_fr' => 'Boîte blush',
            'slug_fr' => 'boite-blush',
            'prix_min' => 4500,
            'prix_max' => 9000,
        ]);

        $unique = Creation::create(['titre_fr' => 'Coffret', 'slug_fr' => 'coffret', 'prix_min' => 4500]);
        $sansPrix = Creation::create(['titre_fr' => 'Sur mesure', 'slug_fr' => 'sur-mesure']);

        $this->assertStringContainsString('–', $fourchette->fourchettePrix('fr'));
        $this->assertStringStartsWith('À partir de', $unique->fourchettePrix('fr'));
        $this->assertNull($sansPrix->fourchettePrix('fr'));
    }

    #[Test]
    public function il_relie_une_creation_a_plusieurs_occasions(): void
    {
        $creation = Creation::create(['titre_fr' => 'Coffret prénom', 'slug_fr' => 'coffret-prenom']);
        $bapteme = Occasion::create(['nom_fr' => 'Baptême', 'slug_fr' => 'bapteme']);
        $shower = Occasion::create(['nom_fr' => 'Baby shower', 'slug_fr' => 'baby-shower']);

        $creation->occasions()->attach([$bapteme->id, $shower->id]);

        $this->assertCount(2, $creation->occasions);
        $this->assertSame('Coffret prénom', $bapteme->creations()->first()->titre_fr);
    }

    #[Test]
    public function il_met_en_cache_les_reglages_et_invalide_a_l_ecriture(): void
    {
        SiteSetting::ecrire('telephone', '450-555-0199');
        $this->assertSame('450-555-0199', SiteSetting::lire('telephone'));

        SiteSetting::ecrire('telephone', '514-555-0100');
        $this->assertSame('514-555-0100', SiteSetting::lire('telephone'));
        $this->assertSame('défaut', SiteSetting::lire('inexistant', 'défaut'));
    }

    #[Test]
    public function il_conserve_une_demande_supprimee_par_erreur(): void
    {
        $demande = CustomRequest::create(['nom' => 'Nadia', 'courriel' => 'nadia@ex.ca']);
        $numero = $demande->numero_suivi;

        $demande->delete();

        $this->assertSame(0, CustomRequest::query()->count());
        $this->assertTrue(CustomRequest::withTrashed()->where('numero_suivi', $numero)->exists());
    }

    #[Test]
    public function il_repere_les_demandes_urgentes_non_repondues(): void
    {
        $urgente = CustomRequest::create([
            'nom' => 'Zoé', 'courriel' => 'zoe@ex.ca', 'date_evenement' => now()->addDays(5),
        ]);

        $lointaine = CustomRequest::create([
            'nom' => 'Emma', 'courriel' => 'emma@ex.ca', 'date_evenement' => now()->addMonths(6),
        ]);

        $repondue = CustomRequest::create([
            'nom' => 'Lou', 'courriel' => 'lou@ex.ca',
            'date_evenement' => now()->addDays(5), 'repondu_le' => now(),
        ]);

        $this->assertTrue($urgente->estUrgente());
        $this->assertFalse($lointaine->estUrgente());
        $this->assertFalse($repondue->estUrgente());
    }

    #[Test]
    public function il_calcule_le_solde_du_apres_depot(): void
    {
        $commande = Order::create([
            'client_nom' => 'Rita', 'client_courriel' => 'rita@ex.ca',
            'total' => 30000, 'depot_montant' => 10000,
        ]);

        $this->assertSame(30000, $commande->soldeDu());

        $commande->update(['depot_paye_le' => now()]);
        $this->assertSame(20000, $commande->refresh()->soldeDu());

        $commande->update(['solde_paye_le' => now()]);
        $this->assertSame(0, $commande->refresh()->soldeDu());
    }
}
