<?php

namespace App\Filament\Resources\ProductTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductTypeForm
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
                                Textarea::make('description_fr')
                                    ->label('Description')
                                    ->rows(3),
                            ]),
                        Tab::make('English')
                            ->schema([
                                TextInput::make('nom_en')->label('Name'),
                                TextInput::make('slug_en')
                                    ->label('Slug (URL)')
                                    ->unique(ignoreRecord: true),
                                Textarea::make('description_en')->label('Description')->rows(3),
                            ]),
                    ])
                    ->columnSpanFull(),

                TextInput::make('ordre')
                    ->label('Ordre')
                    ->numeric()
                    ->default(0),
                Toggle::make('publie')
                    ->label('Publié')
                    ->default(true),
            ]);
    }
}
