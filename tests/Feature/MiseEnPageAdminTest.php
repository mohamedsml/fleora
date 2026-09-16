<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomRequests\Pages\ListCustomRequests;
use App\Models\CustomRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Enums\Width;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Mise en page de l'administration.
 *
 * La liste des demandes affichait neuf colonnes dans un contenu borné à
 * 80 rem : les actions tombaient hors de l'écran, et il fallait défiler
 * horizontalement pour ouvrir une demande.
 */
class MiseEnPageAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['actif' => true]));
    }

    #[Test]
    public function le_contenu_occupe_toute_la_largeur(): void
    {
        $this->assertSame(
            Width::Full,
            Filament::getPanel('admin')->getMaxContentWidth(),
        );
    }

    #[Test]
    public function le_menu_est_repliable(): void
    {
        // Replier le menu rend 14 rem à la table quand on travaille dedans.
        $this->assertTrue(
            Filament::getPanel('admin')->isSidebarCollapsibleOnDesktop(),
        );
    }

    #[Test]
    public function la_liste_des_demandes_reste_fonctionnelle(): void
    {
        CustomRequest::create([
            'nom' => 'Sarah', 'courriel' => 'sarah@example.com',
            'consentement' => true, 'consentement_le' => now(),
        ]);

        $this->get('/admin/custom-requests')
            ->assertOk()
            ->assertSee('Sarah');
    }

    #[Test]
    public function l_etat_de_reponse_accompagne_le_statut(): void
    {
        // La colonne « Répondu » disait la même chose que le statut : elle
        // est devenue une description sous la pastille.
        CustomRequest::create([
            'nom' => 'Sarah', 'courriel' => 'sarah@example.com',
            'consentement' => true, 'consentement_le' => now(),
        ]);

        Livewire::test(ListCustomRequests::class)
            ->assertSee('Sans réponse');
    }

    #[Test]
    public function une_demande_repondue_affiche_son_delai(): void
    {
        CustomRequest::create([
            'nom' => 'Sarah', 'courriel' => 'sarah@example.com',
            'repondu_le' => now()->subHours(3),
            'consentement' => true, 'consentement_le' => now(),
        ]);

        Livewire::test(ListCustomRequests::class)
            ->assertSee('Répondu')
            ->assertDontSee('Sans réponse');
    }

    #[Test]
    public function les_actions_restent_accessibles(): void
    {
        // Passées en icônes, elles doivent toujours répondre.
        $demande = CustomRequest::create([
            'nom' => 'Sarah', 'courriel' => 'sarah@example.com',
            'consentement' => true, 'consentement_le' => now(),
        ]);

        Livewire::test(ListCustomRequests::class)
            ->assertTableActionVisible('view', $demande)
            ->assertTableActionVisible('edit', $demande);
    }
}
