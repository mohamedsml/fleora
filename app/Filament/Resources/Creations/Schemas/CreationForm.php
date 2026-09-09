<?php

namespace App\Filament\Resources\Creations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CreationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Le contenu bilingue en onglets : la saisie française reste
                // complète et lisible, l'anglais se remplit quand il est prêt.
                Tabs::make()
                    ->tabs([
                        Tab::make('Français')
                            ->schema([
                                TextInput::make('titre_fr')
                                    ->label('Titre')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    // Le slug se dérive du titre tant qu'il n'a
                                    // pas été saisi. On ne l'écrase jamais
                                    // ensuite : une URL publiée qui change
                                    // casse les liens et le référencement.
                                    ->afterStateUpdated(function ($state, $get, $set) {
                                        if (blank($get('slug_fr'))) {
                                            $set('slug_fr', Str::slug($state));
                                        }
                                    }),
                                TextInput::make('slug_fr')
                                    ->label('Slug (URL)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Apparaît dans l’adresse de la page. Évitez de le modifier une fois la page publiée.'),
                                Textarea::make('description_fr')
                                    ->label('Description')
                                    ->rows(4),
                            ]),
                        Tab::make('English')
                            ->schema([
                                TextInput::make('titre_en')
                                    ->label('Title')
                                    ->maxLength(255)
                                    ->helperText('Laissé vide, le site affiche le français.'),
                                TextInput::make('slug_en')
                                    ->label('Slug (URL)')
                                    ->unique(ignoreRecord: true),
                                Textarea::make('description_en')
                                    ->label('Description')
                                    ->rows(4),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Classement')
                    ->schema([
                        Select::make('product_type_id')
                            ->label('Type de produit')
                            ->relationship('productType', 'nom_fr')
                            ->searchable()
                            ->preload(),
                        Select::make('occasions')
                            ->label('Occasions')
                            ->relationship('occasions', 'nom_fr')
                            ->multiple()
                            ->preload()
                            ->helperText('Une même création peut servir à plusieurs occasions.'),
                        TagsInput::make('couleurs')
                            ->label('Palette')
                            ->placeholder('blush, ivoire, or…')
                            ->helperText('Sert de filtre dans la galerie.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Prix indicatif')
                    ->description('Fourchette « à partir de » affichée sur le site. Laissez vide pour n’afficher aucun prix.')
                    ->schema([
                        // Saisie en dollars, stockage en cents : personne ne
                        // devrait avoir à taper « 4500 » pour 45 $.
                        TextInput::make('prix_min')
                            ->label('À partir de')
                            ->numeric()
                            ->prefix('$')
                            ->formatStateUsing(fn (?int $state) => $state === null ? null : $state / 100)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) round((float) $state * 100) : null),
                        TextInput::make('prix_max')
                            ->label('Jusqu’à')
                            ->numeric()
                            ->prefix('$')
                            ->formatStateUsing(fn (?int $state) => $state === null ? null : $state / 100)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) round((float) $state * 100) : null)
                            ->gte('prix_min')
                            ->helperText('Optionnel.'),
                    ])
                    ->columns(2),

                Section::make('Publication')
                    ->schema([
                        Toggle::make('publie')
                            ->label('Publiée sur le site')
                            ->helperText('Tant que c’est désactivé, la création reste invisible du public.'),
                        Toggle::make('vedette')
                            ->label('Mise en avant sur l’accueil'),
                        TextInput::make('ordre')
                            ->label('Ordre d’affichage')
                            ->numeric()
                            ->default(0)
                            ->helperText('Le plus petit apparaît en premier.'),
                    ])
                    ->columns(3),
            ]);
    }
}
