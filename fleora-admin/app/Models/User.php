<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'actif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Défaut côté modèle et pas seulement en base : `canAccessPanel()` lit cet
     * attribut sur des instances neuves, avant tout aller-retour SQL.
     */
    protected $attributes = [
        'actif' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
        ];
    }

    /**
     * Contrôle d'accès au back-office.
     *
     * Sans cette méthode, Filament n'autorise l'accès qu'en environnement
     * `local` et renvoie 403 partout ailleurs — le back-office serait donc
     * inaccessible en production.
     *
     * Le back-office donne accès à des renseignements personnels de clientes
     * (Loi 25) : désactiver un compte doit suffire à couper l'accès, sans avoir
     * à le supprimer ni à changer de mot de passe.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Cast explicite : un `actif` nul ne doit jamais faire échouer le
        // contrôle par une erreur de type, il doit refuser l'accès.
        return (bool) $this->actif;
    }
}
