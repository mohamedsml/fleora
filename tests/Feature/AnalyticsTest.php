<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Intégration de Google Analytics.
 *
 * L'outil dépose des témoins de suivi : ce que le site en dit et là où il se
 * charge engagent la responsabilité du propriétaire (Loi 25), d'où ces
 * vérifications plutôt qu'une confiance dans la configuration.
 */
class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const MESURE = 'G-TEST123456';

    #[Test]
    public function le_tag_ne_se_charge_pas_sans_identifiant(): void
    {
        // Sans cette garde, les visites locales et celles de la suite de tests
        // gonfleraient les statistiques réelles.
        config(['services.google_analytics.id' => null]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com', false);
    }

    #[Test]
    public function le_tag_se_charge_sur_le_site_public(): void
    {
        config(['services.google_analytics.id' => self::MESURE]);

        $this->get('/')
            ->assertOk()
            ->assertSee('googletagmanager.com/gtag/js?id='.self::MESURE, false)
            ->assertSee("gtag('config', '".self::MESURE."'", false);
    }

    #[Test]
    public function l_administration_n_est_pas_mesuree(): void
    {
        // Compter ses propres passages dans /admin fausserait les chiffres de
        // fréquentation du site public.
        config(['services.google_analytics.id' => self::MESURE]);
        $this->actingAs(User::factory()->create(['actif' => true]));

        $this->get('/admin')
            ->assertOk()
            ->assertDontSee('googletagmanager.com', false);
    }

    #[Test]
    public function l_adresse_ip_est_tronquee(): void
    {
        config(['services.google_analytics.id' => self::MESURE]);

        $this->get('/')->assertSee('anonymize_ip', false);
    }

    #[Test]
    public function la_csp_autorise_les_domaines_de_google(): void
    {
        // Sans ces domaines, le script se charge mais n'envoie rien : la
        // panne serait silencieuse.
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://www.googletagmanager.com', $csp);
        $this->assertStringContainsString('https://www.google-analytics.com', $csp);
    }

    #[Test]
    public function la_politique_annonce_la_mesure_d_audience(): void
    {
        // La page affirmait « aucun témoin de suivi » : c'est devenu faux le
        // jour où Analytics a été activé.
        config(['services.google_analytics.id' => self::MESURE]);

        $contenu = $this->get('/confidentialite')->assertOk()->getContent();

        $this->assertStringContainsString('Google Analytics', $contenu);
        $this->assertStringContainsString('_ga', $contenu);
        $this->assertStringContainsString('gaoptout', $contenu);
    }

    #[Test]
    public function la_politique_ne_parle_pas_d_analytics_sans_mesure(): void
    {
        // Annoncer un outil absent serait faux dans l'autre sens.
        config(['services.google_analytics.id' => null]);

        $this->get('/confidentialite')
            ->assertOk()
            ->assertDontSee('Google Analytics', false);
    }

    #[Test]
    public function la_politique_anglaise_annonce_aussi_la_mesure(): void
    {
        config(['services.google_analytics.id' => self::MESURE]);

        $this->get('/en/privacy')
            ->assertOk()
            ->assertSee('Google Analytics', false)
            ->assertSee('Audience measurement', false);
    }
}
