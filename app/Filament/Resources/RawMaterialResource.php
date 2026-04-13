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
    protected static ?string $modelLabel = 'Material';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Material Name'),
                Forms\Components\TextInput::make('brand')
                    ->label('Brand (Merk)'),
                Forms\Components\Fieldset::make('Price Calculator (Optional)')
                    ->schema([
                        Forms\Components\Placeholder::make('last_purchase_info')
                            ->label('Previous Purchase Info')
                            ->columnSpanFull()
                            ->hidden(fn ($record) => !$record || !$record->pack_price || !$record->pack_size)
                            ->content(function ($record) {
                                if (!$record) return '';
                                return 'Rp ' . number_format($record->pack_price, 0, ',', '.') . ' for ' . floatval($record->pack_size) . ' ' . ($record->unit ?? '');
                            }),
                        Forms\Components\TextInput::make('pack_price')
                            ->label('Minimarket Total Price')
                            ->numeric()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->afterStateHydrated(fn ($component) => $component->state(null))
                            ->hint('e.g., 13600')
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                $totalPrice = (float) ($state ?? 0);
                                $totalQty = (float) ($get('pack_size') ?? 1);
                                if ($totalQty > 0 && $totalPrice > 0) {
                                    $set('price_per_unit', $totalPrice / $totalQty);
                                }
                            }),
                        Forms\Components\TextInput::make('pack_size')
                            ->label('Total Base Unit in Package')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateHydrated(fn ($component) => $component->state(null))
                            ->hint('e.g., 1000 for 1kg in gr')
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                $totalQty = (float) ($state ?? 1);
                                $totalPrice = (float) ($get('pack_price') ?? 0);
                                if ($totalQty > 0 && $totalPrice > 0) {
                                    $set('price_per_unit', $totalPrice / $totalQty);
                                }
                            }),
                    ])->columns(2),

                Forms\Components\TextInput::make('price_per_unit')
                    ->label('Final Price per Base Unit')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->formatStateUsing(fn ($state) => $state === null ? null : (str_contains((string)$state, '.') ? (string)(float)$state : $state)),
                Forms\Components\TextInput::make('current_stock')
                    ->required()
                    ->numeric()
                    ->label('Current Stock')
                    ->formatStateUsing(fn ($state) => $state === null ? null : (str_contains((string)$state, '.') ? (string)(float)$state : $state))
                    ->suffix(fn (Forms\Get $get) => $get('unit') ?? 'pcs'),
                Forms\Components\TextInput::make('unit')
                    ->required()
                    ->default('pcs')
                    ->live()
                    ->label('Base Unit')
                    ->datalist([
                        'kg', 'gr', 'ltr', 'ml', 'pcs', 'pack', 'botol', 'sachet', 'lembar', 'butir', 'meter',
                    ])
                    ->hint('Pilih atau ketik satuan baru'),
            ])->columns(['sm' => 1, 'md' => 2]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Material')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('brand')
                    ->searchable()
                    ->label('Brand')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price_per_unit')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->label('Price/Base Unit')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('current_stock')
                    ->formatStateUsing(fn ($state, $record) => floatval($state) . ' ' . ($record->unit ?? 'pcs'))
                    ->sortable()
                    ->label('Current Stock')
                    ->toggleable(),
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
            ])
            ->paginated(false);
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
