<?php

namespace Tests\Feature;

use App\Models\Visite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Exclusion des visites du propriétaire.
 *
 * Sur un site jeune, vérifier une page après chaque modification gonfle les
 * chiffres au point de les rendre inutilisables : les passages du
 * propriétaire dépassent vite ceux des vraies clientes.
 */
class ExclusionIpTest extends TestCase
{
    use RefreshDatabase;

    private const NAVIGATEUR = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/130.0 Safari/537.36';

    private const EXCLUE = '198.51.100.7';

    private function visiter(string $ip): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeaders(['User-Agent' => self::NAVIGATEUR])
            ->get('/');
    }

    #[Test]
    public function une_visite_depuis_une_ip_exclue_n_est_pas_enregistree(): void
    {
        config(['fleora.ips_exclues' => [self::EXCLUE]]);

        $this->visiter(self::EXCLUE);

        $this->assertSame(0, Visite::count());
    }

    #[Test]
    public function les_autres_visites_restent_comptees(): void
    {
        // Le filtre ne doit pas assécher la mesure.
        config(['fleora.ips_exclues' => [self::EXCLUE]]);

        $this->visiter('203.0.113.42');

        $this->assertSame(1, Visite::count());
    }

    #[Test]
    public function le_tag_google_ne_se_charge_pas_pour_une_ip_exclue(): void
    {
        // Les chiffres envoyés à Google ne sont pas corrigeables après coup :
        // mieux vaut ne rien envoyer.
        config([
            'fleora.ips_exclues' => [self::EXCLUE],
            'services.google_analytics.id' => 'G-TEST123456',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => self::EXCLUE])
            ->get('/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com', false);
    }

    #[Test]
    public function le_tag_google_se_charge_pour_les_autres(): void
    {
        config([
            'fleora.ips_exclues' => [self::EXCLUE],
            'services.google_analytics.id' => 'G-TEST123456',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->get('/')
            ->assertOk()
            ->assertSee('googletagmanager.com', false);
    }

    #[Test]
    public function plusieurs_adresses_peuvent_etre_exclues(): void
    {
        config(['fleora.ips_exclues' => [self::EXCLUE, '203.0.113.9']]);

        $this->visiter(self::EXCLUE);
        $this->visiter('203.0.113.9');

        $this->assertSame(0, Visite::count());
    }

    #[Test]
    public function une_liste_vide_ne_bloque_rien(): void
    {
        // Le défaut de configuration ne doit pas assécher la mesure par
        // inadvertance.
        config(['fleora.ips_exclues' => []]);

        $this->visiter('203.0.113.42');

        $this->assertSame(1, Visite::count());
    }
}
