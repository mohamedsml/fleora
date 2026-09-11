<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\CreationsLesPlusConsultees;
use App\Filament\Widgets\PagesLesPlusVues;
use App\Filament\Widgets\SourcesDeTrafic;
use App\Filament\Widgets\StatistiquesVisites;
use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use RuntimeException;
use Saade\FilamentLaravelLog\FilamentLaravelLogPlugin;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Connexion automatique en développement local.
     *
     * ⚠️ Contourne l'authentification de l'administration. Trois verrous
     * indépendants l'empêchent de s'exécuter ailleurs qu'en local :
     * la variable FLEORA_AUTO_LOGIN (absente = désactivé), l'environnement
     * réel de l'application, et l'origine privée de la requête.
     *
     * Fait dans boot() plutôt qu'en middleware : Filament place son propre
     * Authenticate en tête de la pile du panneau, quelle que soit la position
     * déclarée — un middleware s'exécuterait après la redirection.
     */
    public function boot(): void
    {
        if (! config('fleora.auto_login')) {
            return;
        }

        if (! app()->environment('local')) {
            throw new RuntimeException(
                'FLEORA_AUTO_LOGIN est activé hors environnement local. '
                .'Retirez-le du .env de ce serveur : l’administration serait '
                .'accessible sans mot de passe.'
            );
        }

        if (! $this->requetePrivee()) {
            return;
        }

        if (! Auth::check()) {
            // Le plus ancien compte : celui créé à l'installation.
            if ($utilisateur = User::query()->oldest('id')->first()) {
                Auth::login($utilisateur);
            }
        }
    }

    /**
     * Vrai pour localhost, le réseau Docker et un LAN — faux pour toute
     * adresse routable sur Internet.
     */
    private function requetePrivee(): bool
    {
        $ip = request()->ip();

        if ($ip === null) {
            return false;
        }

        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return true;
        }

        // FILTER_FLAG_NO_PRIV_RANGE fait échouer la validation pour une IP
        // privée : un échec signifie donc « privée », ce qu'on veut ici.
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // Sans cela, l'onglet de l'administration porte le logo de
            // Filament : le SVG reste net quel que soit l'écran.
            ->favicon(asset('favicon.svg'))
            // brandName reste défini : il sert de texte alternatif au logo.
            ->brandName(config('app.name'))
            ->brandLogo(asset('images/logo-fleora.svg'))
            // 3rem et non 2rem : le SVG intègre ses marges, le dessin ne
            // remplit que ~62 % de la hauteur du fichier.
            ->brandLogoHeight('3rem')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->plugins([
                /*
                 * Lecteur de logs dans l'administration.
                 *
                 * Sur un hébergement mutualisé, diagnostiquer une erreur 500
                 * demandait une session SSH. Cet écran donne la même
                 * information depuis le navigateur.
                 *
                 * ⚠️ Les logs contiennent des traces d'exception : chemins du
                 * serveur, extraits de code, parfois des données de requête.
                 * L'écran hérite de l'authentification du panneau, et
                 * `authorize` en restreint l'accès au-delà.
                 */
                FilamentLaravelLogPlugin::make()
                    ->navigationGroup('Système')
                    ->navigationLabel('Journaux')
                    ->navigationIcon('heroicon-o-document-text')
                    ->navigationSort(99)
                    // Réservé aux comptes actifs : un compte désactivé qui
                    // garderait une session ouverte ne doit pas lire les logs.
                    ->authorize(fn () => (bool) auth()->user()?->actif),
            ])
            ->widgets([
                // Statistiques de visite : les chiffres à voir en ouvrant
                // l'administration, avant toute autre chose.
                StatistiquesVisites::class,
                PagesLesPlusVues::class,
                SourcesDeTrafic::class,
                CreationsLesPlusConsultees::class,
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
