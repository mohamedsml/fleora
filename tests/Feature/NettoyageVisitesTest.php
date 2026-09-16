<?php

namespace Tests\Feature;

use App\Models\Visite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Filtrage des balayages automatiques.
 *
 * Deux empreintes ont enregistré 141 pages en 90 secondes chacune en se
 * présentant comme un navigateur ordinaire — 282 des 369 visites d'une seule
 * journée. La détection par nom d'agent ne les voyait pas : c'est le rythme
 * qui les trahit.
 */
class NettoyageVisitesTest extends TestCase
{
    use RefreshDatabase;

    private const NAVIGATEUR = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/130.0 Safari/537.36';

    /**
     * Pose une séquence de visites pour une même empreinte.
     */
    private function sequence(string $empreinte, int $pages, int $secondes): void
    {
        $debut = now()->subMinutes(10);

        for ($i = 0; $i < $pages; $i++) {
            Visite::create([
                'chemin' => '/page-'.$i,
                'langue' => 'fr',
                'appareil' => 'ordinateur',
                'empreinte' => $empreinte,
                'created_at' => $debut->copy()->addSeconds(
                    $pages > 1 ? (int) ($i * $secondes / ($pages - 1)) : 0
                ),
            ]);
        }
    }

    // ── Nettoyage de l'historique ───────────────────────────────────────

    #[Test]
    public function un_balayage_est_supprime(): void
    {
        // 141 pages en 90 secondes : le cas réel constaté en production.
        $this->sequence(str_repeat('a', 64), 141, 90);

        $this->artisan('fleora:nettoyer-visites --no-interaction')->assertSuccessful();

        $this->assertSame(0, Visite::count());
    }

    #[Test]
    public function une_navigation_normale_est_conservee(): void
    {
        // Douze pages en vingt minutes : une visiteuse qui parcourt le
        // catalogue avec attention.
        $this->sequence(str_repeat('b', 64), 12, 20 * 60);

        $this->artisan('fleora:nettoyer-visites --no-interaction')->assertSuccessful();

        $this->assertSame(12, Visite::count());
    }

    #[Test]
    public function une_courte_serie_rapide_est_conservee(): void
    {
        // Cinq pages ouvertes coup sur coup : quelqu'un qui compare des
        // créations dans plusieurs onglets. Sous dix pages, on ne juge pas.
        $this->sequence(str_repeat('c', 64), 5, 10);

        $this->artisan('fleora:nettoyer-visites --no-interaction')->assertSuccessful();

        $this->assertSame(5, Visite::count());
    }

    #[Test]
    public function le_mode_essai_ne_supprime_rien(): void
    {
        $this->sequence(str_repeat('d', 64), 141, 90);

        $this->artisan('fleora:nettoyer-visites --essai')->assertSuccessful();

        $this->assertSame(141, Visite::count());
    }

    #[Test]
    public function seules_les_empreintes_suspectes_partent(): void
    {
        // Le nettoyage ne doit pas emporter le trafic légitime au passage.
        $this->sequence(str_repeat('e', 64), 141, 90);
        $this->sequence(str_repeat('f', 64), 12, 20 * 60);

        $this->artisan('fleora:nettoyer-visites --no-interaction')->assertSuccessful();

        $this->assertSame(12, Visite::count());
        $this->assertSame(
            str_repeat('f', 64),
            Visite::first()->empreinte,
        );
    }

    // ── Prévention ──────────────────────────────────────────────────────

    #[Test]
    public function un_balayage_en_cours_cesse_d_etre_compte(): void
    {
        // Sans cette limite, le nettoyage serait à relancer indéfiniment.
        for ($i = 0; $i < 45; $i++) {
            $this->withHeaders(['User-Agent' => self::NAVIGATEUR])->get('/');
        }

        $this->assertLessThanOrEqual(
            30,
            Visite::count(),
            'Au-delà de trente pages par minute, les visites ne doivent plus être enregistrées.'
        );
    }

    #[Test]
    public function une_navigation_ordinaire_reste_comptee(): void
    {
        // La limite ne doit pas assécher la mesure : cinq pages passent.
        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders(['User-Agent' => self::NAVIGATEUR])->get('/');
        }

        $this->assertSame(5, Visite::count());
    }
}
