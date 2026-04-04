<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RawMaterialResource\Pages;
use App\Models\RawMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RawMaterialResource extends Resource
{
    protected static ?string $model = RawMaterial::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Inventaris';
    protected static ?string $modelLabel = 'Bahan Baku';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nama Bahan Baku'),
                Forms\Components\TextInput::make('unit')
                    ->required()
                    ->label('Satuan (Misal: Kg, Gram, Liter, Sachet)'),
                Forms\Components\TextInput::make('price_per_unit')
                    ->label('Harga Beli (Per Satuan)')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->default(0),
                Forms\Components\TextInput::make('current_stock')
                    ->required()
                    ->numeric()
                    ->label('Stok Saat Ini (Satuan)')
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Nama'),
                Tables\Columns\TextColumn::make('unit')
                    ->searchable()
                    ->label('Satuan'),
                Tables\Columns\TextColumn::make('price_per_unit')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->label('Harga Beli Satuan'),
                Tables\Columns\TextColumn::make('current_stock')
                    ->numeric()
                    ->sortable()
                    ->label('Stok Saat Ini'),
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
            'index' => Pages\ListRawMaterials::route('/'),
            'create' => Pages\CreateRawMaterial::route('/create'),
            'edit' => Pages\EditRawMaterial::route('/{record}/edit'),
        ];
    }
}
