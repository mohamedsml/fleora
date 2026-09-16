<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Lecteur de journaux.
 *
 * L'écran affichait ses clés de traduction brutes
 * (« log::filament-laravel-log.navigation.label ») au lieu des libellés. Le
 * paquet enregistre ses traductions sous l'espace de noms
 * « filament-laravel-log » mais les appelle sous « log » : aucune langue ne
 * fonctionnait, pas seulement le français.
 *
 * Les fichiers publiés dans lang/vendor/log/ comblent ce décalage et
 * survivent aux mises à jour de composer.
 */
class LecteurJournauxTest extends TestCase
{
    use RefreshDatabase;

    /** Les clés effectivement appelées par le paquet. */
    private const CLES = [
        'navigation.label',
        'navigation.group',
        'page.title',
        'page.form.placeholder',
        'actions.clear.label',
        'actions.clear.modal.heading',
        'actions.clear.modal.description',
        'actions.refresh.label',
        'actions.jumpToStart.label',
        'actions.jumpToEnd.label',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['actif' => true]));
    }

    #[Test]
    public function toutes_les_cles_sont_traduites_en_francais(): void
    {
        app()->setLocale('fr');

        foreach (self::CLES as $suffixe) {
            $cle = 'log::filament-laravel-log.'.$suffixe;

            $this->assertNotSame(
                $cle,
                __($cle),
                "La clé « {$suffixe} » s’affiche brute au lieu de son libellé.",
            );
        }
    }

    #[Test]
    public function toutes_les_cles_sont_traduites_en_anglais(): void
    {
        // Le paquet fournit l'anglais, mais sous un espace de noms qu'il
        // n'interroge jamais : la copie dans lang/vendor/log/ le rend
        // atteignable.
        app()->setLocale('en');

        foreach (self::CLES as $suffixe) {
            $cle = 'log::filament-laravel-log.'.$suffixe;

            $this->assertNotSame($cle, __($cle), "Clé anglaise manquante : {$suffixe}");
        }
    }

    #[Test]
    public function la_page_affiche_ses_libelles(): void
    {
        $contenu = $this->get('/admin/logs')->assertOk()->getContent();

        $this->assertStringNotContainsString(
            'log::filament-laravel-log',
            $contenu,
            'Aucune clé brute ne doit apparaître à l’écran.',
        );

        $this->assertStringContainsString('Journaux', $contenu);
        $this->assertStringContainsString('Actualiser', $contenu);
    }

    #[Test]
    public function l_avertissement_de_suppression_est_explicite(): void
    {
        // Vider un journal est irréversible : le texte doit le dire, pas
        // seulement demander confirmation.
        app()->setLocale('fr');

        $this->assertStringContainsString(
            'définitivement',
            __('log::filament-laravel-log.actions.clear.modal.description'),
        );
    }
}
