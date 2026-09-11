<?php

namespace Tests\Feature;

use App\Livewire\FormulaireDemande;
use App\Models\CustomRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use RyanChandler\LaravelCloudflareTurnstile\Facades\Turnstile;
use Tests\TestCase;

/**
 * Protections du formulaire et en-têtes HTTP.
 */
class SecuriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        RateLimiter::clear('demande:127.0.0.1');
    }

    // ── En-têtes HTTP ───────────────────────────────────────────────────

    #[Test]
    public function les_entetes_de_securite_sont_poses(): void
    {
        $reponse = $this->get('/');

        $reponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $reponse->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $reponse->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotNull($reponse->headers->get('Permissions-Policy'));
        $this->assertNotNull($reponse->headers->get('Content-Security-Policy'));
    }

    #[Test]
    public function la_csp_autorise_les_dependances_reelles(): void
    {
        // Une CSP trop stricte casse en SILENCE : le navigateur bloque sans
        // rien afficher. Turnstile, la carte de contact et Livewire doivent
        // rester autorisés.
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('challenges.cloudflare.com', $csp);
        $this->assertStringContainsString('openstreetmap.org', $csp);
        // Alpine et Livewire évaluent des expressions dans les attributs HTML.
        $this->assertStringContainsString("'unsafe-eval'", $csp);
    }

    #[Test]
    public function la_csp_interdit_l_encadrement_et_les_greffons(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    #[Test]
    public function hsts_n_est_pose_qu_en_https(): void
    {
        // Posé en HTTP il n'a aucun effet, et sur un domaine de développement
        // il rendrait le site inaccessible hors TLS.
        $this->assertNull(
            $this->get('/')->headers->get('Strict-Transport-Security')
        );
    }

    #[Test]
    public function l_administration_porte_aussi_les_entetes(): void
    {
        $this->get('/admin')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    // ── Turnstile ───────────────────────────────────────────────────────

    #[Test]
    public function le_formulaire_fonctionne_sans_cles_turnstile(): void
    {
        // Sans clés, le widget ne s'affiche pas : exiger le jeton rendrait le
        // formulaire inutilisable.
        config(['services.turnstile.key' => null, 'services.turnstile.secret' => null]);

        $this->assertFalse(FormulaireDemande::turnstileActif());

        Livewire::test(FormulaireDemande::class)
            ->set('ouvert_a', now()->subSeconds(10)->timestamp)
            ->set('nom', 'Sarah')
            ->set('courriel', 'sarah@example.com')
            ->set('consentement', true)
            ->call('envoyer')
            ->assertHasNoErrors();

        $this->assertSame(1, CustomRequest::count());
    }

    #[Test]
    public function le_widget_n_apparait_que_si_les_cles_existent(): void
    {
        config(['services.turnstile.key' => null, 'services.turnstile.secret' => null]);
        $this->assertStringNotContainsString('cf-turnstile', $this->get('/demande')->getContent());

        config([
            'services.turnstile.key' => '1x00000000000000000000AA',
            'services.turnstile.secret' => '1x0000000000000000000000000000000AA',
        ]);

        $html = Livewire::test(FormulaireDemande::class)->set('etape', 3)->html();

        $this->assertStringContainsString('cf-turnstile', $html);
        // wire:ignore : sans lui, Livewire détruirait le widget à chaque
        // re-rendu du composant.
        $this->assertStringContainsString('wire:ignore', $html);
    }

    #[Test]
    public function un_jeton_turnstile_invalide_bloque_l_envoi(): void
    {
        config([
            'services.turnstile.key' => '1x00000000000000000000AA',
            'services.turnstile.secret' => '1x0000000000000000000000000000000AA',
        ]);

        Turnstile::fake()->fail();

        Livewire::test(FormulaireDemande::class)
            ->set('ouvert_a', now()->subSeconds(10)->timestamp)
            ->set('nom', 'Bot')
            ->set('courriel', 'bot@example.com')
            ->set('consentement', true)
            ->set('turnstile', 'jeton-invalide')
            ->call('envoyer')
            ->assertHasErrors('turnstile');

        $this->assertSame(0, CustomRequest::count());
    }

    #[Test]
    public function un_jeton_turnstile_valide_laisse_passer(): void
    {
        config([
            'services.turnstile.key' => '1x00000000000000000000AA',
            'services.turnstile.secret' => '1x0000000000000000000000000000000AA',
        ]);

        Turnstile::fake()->pass();

        Livewire::test(FormulaireDemande::class)
            ->set('ouvert_a', now()->subSeconds(10)->timestamp)
            ->set('nom', 'Sarah')
            ->set('courriel', 'sarah@example.com')
            ->set('consentement', true)
            ->set('turnstile', 'jeton-valide')
            ->call('envoyer')
            ->assertHasNoErrors();

        $this->assertSame(1, CustomRequest::count());
    }

    #[Test]
    public function les_autres_protections_restent_actives_avec_turnstile(): void
    {
        // Défense en profondeur : Turnstile s'ajoute au honeypot, au délai
        // minimal et à la limite par IP, il ne les remplace pas.
        config([
            'services.turnstile.key' => '1x00000000000000000000AA',
            'services.turnstile.secret' => '1x0000000000000000000000000000000AA',
        ]);

        Turnstile::fake()->pass();

        Livewire::test(FormulaireDemande::class)
            ->set('ouvert_a', now()->subSeconds(10)->timestamp)
            ->set('nom', 'Bot')
            ->set('courriel', 'bot@example.com')
            ->set('consentement', true)
            ->set('turnstile', 'jeton-valide')
            ->set('site_web', 'http://spam.example')
            ->call('envoyer');

        // Honeypot rempli : rien en base, malgré un jeton valide.
        $this->assertSame(0, CustomRequest::count());
    }
}
