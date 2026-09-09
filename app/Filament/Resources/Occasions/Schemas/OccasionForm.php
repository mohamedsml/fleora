<?php

namespace App\Filament\Resources\Occasions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class OccasionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->tabs([
                        Tab::make('Français')
                            ->schema([
                                TextInput::make('nom_fr')
                                    ->label('Nom')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $get, $set) {
                                        if (blank($get('slug_fr'))) {
                                            $set('slug_fr', Str::slug($state));
                                        }
                                    }),
                                TextInput::make('slug_fr')
                                    ->label('Slug (URL)')
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                Textarea::make('intro_fr')
                                    ->label('Introduction')
                                    ->rows(3)
                                    ->helperText('Court paragraphe affiché en haut de la page.'),
                                Textarea::make('contenu_seo_fr')
                                    ->label('Contenu détaillé')
                                    ->rows(8)
                                    ->helperText('Texte long qui porte le référencement de la page. C’est ce qui fait remonter « boîte à fleurs mariage Laval ».'),
                                TextInput::make('meta_title_fr')
                                    ->label('Titre pour Google')
                                    ->maxLength(60)
                                    ->helperText('60 caractères maximum : au-delà, Google tronque.'),
                                Textarea::make('meta_description_fr')
                                    ->label('Description pour Google')
                                    ->rows(2)
                                    ->maxLength(160)
                                    ->helperText('160 caractères maximum. C’est le texte affiché sous le lien dans les résultats.'),
                            ]),
                        Tab::make('English')
                            ->schema([
                                TextInput::make('nom_en')->label('Name'),
                                TextInput::make('slug_en')
                                    ->label('Slug (URL)')
                                    ->unique(ignoreRecord: true),
                                Textarea::make('intro_en')->label('Introduction')->rows(3),
                                Textarea::make('contenu_seo_en')->label('Detailed content')->rows(8),
                                TextInput::make('meta_title_en')->label('Google title')->maxLength(60),
                                Textarea::make('meta_description_en')->label('Google description')->rows(2)->maxLength(160),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Affichage')
                    ->schema([
                        TextInput::make('icone')
                            ->label('Icône')
                            ->helperText('Nom d’icône Heroicon, ex. heroicon-o-heart.'),
                        TextInput::make('ordre')
                            ->label('Ordre')
                            ->numeric()
                            ->default(0),
                        Toggle::make('publie')
                            ->label('Publiée')
                            ->default(true),
                    ])
                    ->columns(3),
            ]);
    }
}
