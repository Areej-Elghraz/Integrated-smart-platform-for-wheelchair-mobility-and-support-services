<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlaceResource\Pages;
use App\Filament\Resources\PlaceResource\RelationManagers;
use App\Models\Place;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlaceResource extends Resource
{
    protected static ?string $model = Place::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->default(null),
                Forms\Components\Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->default(null),
                Forms\Components\TextInput::make('parent_place_id')
                    ->numeric()
                    ->default(null),
                Forms\Components\Select::make('floor_id')
                    ->relationship('floor', 'name')
                    ->default(null),
                Forms\Components\TextInput::make('x')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('y')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('z')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('rotation')
                    ->numeric()
                    ->default(null),
                Forms\Components\Select::make('country_id')
                    ->relationship('country', 'name')
                    ->default(null),
                Forms\Components\Select::make('city_id')
                    ->relationship('city', 'name')
                    ->default(null),
                Forms\Components\FileUpload::make('image')
                    ->image(),
                Forms\Components\Textarea::make('accessibility_data')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('organization.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent_place_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('floor.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('x')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('y')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('z')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rotation')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('country.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlaces::route('/'),
            'create' => Pages\CreatePlace::route('/create'),
            'edit' => Pages\EditPlace::route('/{record}/edit'),
        ];
    }
}
