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
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Raw Material';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Material Name'),
                Forms\Components\TextInput::make('unit')
                    ->required()
                    ->label('Unit (e.g. Kg, Gram, Liter, Pcs)'),
                Forms\Components\TextInput::make('price_per_unit')
                    ->label('Purchase Price (Per Unit)')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->default(0),
                Forms\Components\TextInput::make('current_stock')
                    ->required()
                    ->numeric()
                    ->label('Current Stock (Units)')
                    ->default(0),

                Forms\Components\Section::make('Kalkulator Harga Satuan (Sangat Disarankan)')
                    ->description('Gunakan ini agar Anda tidak perlu pusing menghitung harga per mili/gram secara manual.')
                    ->schema([
                        Forms\Components\TextInput::make('pack_price')
                            ->label('Harga Per Kemasan (Misal: 1 Botol)')
                            ->numeric()
                            ->prefix('Rp')
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $price = (float) ($get('pack_price') ?? 0);
                                $size = (float) ($get('pack_size') ?? 0);
                                if ($size > 0) $set('price_per_unit', $price / $size);
                            }),
                        Forms\Components\TextInput::make('pack_size')
                            ->label('Isi Per Kemasan (Misal: 335)')
                            ->numeric()
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $price = (float) ($get('pack_price') ?? 0);
                                $size = (float) ($get('pack_size') ?? 0);
                                if ($size > 0) $set('price_per_unit', $price / $size);
                            }),
                    ])->columns(['sm' => 1, 'md' => 2]),
            ])->columns(['sm' => 1, 'md' => 2]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Name'),
                Tables\Columns\TextColumn::make('unit')
                    ->searchable()
                    ->label('Unit'),
                Tables\Columns\TextColumn::make('price_per_unit')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->label('Unit Price'),
                Tables\Columns\TextColumn::make('current_stock')
                    ->numeric()
                    ->sortable()
                    ->label('Current Stock'),
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
