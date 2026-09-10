<?php

namespace Tests\Feature;

use App\Models\Creation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Livewire embarque Alpine et le démarre lui-même. Importer puis lancer une
 * seconde instance côté application fait entrer les deux en conflit : les
 * `wire:click` deviennent inertes, sans aucune erreur en console.
 *
 * Ces tests existent parce que la panne est invisible : le bouton est là, le
 * curseur change, et rien ne se passe.
 */
class AlpineLivewireTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function l_application_ne_demarre_pas_sa_propre_instance_alpine(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertStringNotContainsString(
            'Alpine.start()',
            $js,
            'app.js démarre Alpine alors que Livewire le fait déjà : les wire:click seront inertes.'
        );

        $this->assertStringNotContainsString(
            "import Alpine from 'alpinejs'",
            $js,
            'Alpine ne doit pas être importé séparément — il vient de Livewire.'
        );
    }

    #[Test]
    public function livewire_est_charge_sur_toutes_les_pages(): void
    {
        // L'en-tête utilise Alpine pour son menu mobile, y compris sur des
        // pages sans composant Livewire. Sans injection explicite, Livewire
        // n'apparaît que là où un composant est rendu — et le menu casse
        // partout ailleurs.
        Creation::create(['titre_fr' => 'Coffret', 'slug_fr' => 'coffret', 'publie' => true]);

        foreach (['/', '/creations', '/creations/coffret', '/demande', '/merci'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('livewire.js', false);
        }
    }

    #[Test]
    public function livewire_n_est_charge_qu_une_fois(): void
    {
        // Un double chargement produit le même conflit que la double instance
        // d'Alpine.
        $html = $this->get('/demande')->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'livewire/livewire.js'),
            'Livewire est chargé plusieurs fois.'
        );
    }
}
