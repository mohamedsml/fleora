<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\CreationsLesPlusConsultees;
use App\Filament\Widgets\DemandesEnCours;
use App\Filament\Widgets\StatistiquesVisites;
use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use RuntimeException;

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
            // Filament borne le contenu à 7xl (80rem) par défaut : sur un
            // écran large, cela laissait une bande vide entre le menu et la
            // table, qui devait alors défiler horizontalement pour montrer
            // ses actions.
            ->maxContentWidth(Width::Full)
            // Le menu se replie en icônes : la liste des demandes gagne
            // 14 rem quand on travaille dedans, et se redéploie d'un clic.
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            // Le lecteur de journaux vit sur sa propre route, hors du
            // panneau : sans cette entrée, il faudrait connaître l'URL par
            // cœur. L'icône ouvre dans un nouvel onglet — on y va pour
            // diagnostiquer, sans vouloir quitter l'écran en cours.
            ->navigationItems([
                NavigationItem::make('Journaux')
                    ->url(fn (): string => route('log-viewer.index'), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-document-text')
                    ->group('Système')
                    ->sort(99)
                    // Même règle que le reste de l'administration : un compte
                    // désactivé dont la session reste ouverte n'y accède pas.
                    ->visible(fn (): bool => (bool) auth()->user()?->actif),
            ])
            ->widgets([
                // Le tableau de bord répond à « qu'est-ce que je dois faire
                // aujourd'hui » : les demandes d'abord, puis une synthèse de
                // fréquentation. Le détail des visites — pages d'entrée,
                // sources, parcours — vit sur la page Rapport de visites, où
                // il peut être filtré par période.
                DemandesEnCours::class,
                StatistiquesVisites::class,
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
