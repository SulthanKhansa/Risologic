<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Penjualan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->unique('products', 'name', ignoreRecord: true)
                    ->maxLength(255)
                    ->label('Nama Produk'),

                TextInput::make('slug')
                    ->required()
                    ->unique('products', 'slug', ignoreRecord: true)
                    ->maxLength(255)
                    ->label('Slug')
                    ->hint('URL-friendly identifier'),

                TextInput::make('base_price')
                    ->numeric()
                    ->required()
                    ->step('0.01')
                    ->minValue(0)
                    ->label('Harga Dasar Jual'),

                TextInput::make('hpp')
                    ->label('HPP (Harga Pokok Penjualan)')
                    ->numeric()
                    ->disabled() // Auto calculated
                    ->dehydrated() // Must be saved even if disabled to avoid null issues sometimes, wait actually it's fine
                    ->prefix('Rp')
                    ->default(0)
                    ->helperText('Otomatis dihitung saat Produksi selesai berdasar resep.'),

                TextInput::make('current_stock')
                    ->numeric()
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->label('Stok Saat Ini'),
                
                Forms\Components\Repeater::make('recipeItems')
                    ->relationship('recipeItems')
                    ->schema([
                        Forms\Components\Select::make('raw_material_id')
                            ->relationship('rawMaterial', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Bahan Baku'),
                        Forms\Components\TextInput::make('quantity_required')
                            ->numeric()
                            ->required()
                            ->label('Takaran'),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->label('Resep (Bahan untuk 1 Pcs)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Produk'),

                TextColumn::make('slug')
                    ->searchable()
                    ->label('Slug'),

                TextColumn::make('base_price')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->label('Harga Jual'),

                TextColumn::make('hpp')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->label('HPP / Modal'),

                TextColumn::make('current_stock')
                    ->numeric()
                    ->sortable()
                    ->label('Stok'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Dibuat'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
