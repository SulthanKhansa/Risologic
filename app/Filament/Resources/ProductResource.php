<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\RawMaterial;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
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
    protected static ?string $navigationGroup = 'Sales';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->unique('products', 'name', ignoreRecord: true)
                    ->maxLength(255)
                    ->label('Product Name'),

                TextInput::make('slug')
                    ->required()
                    ->unique('products', 'slug', ignoreRecord: true)
                    ->maxLength(255)
                    ->label('Slug')
                    ->hint('URL-friendly identifier'),

                TextInput::make('base_price')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->step('0.01')
                    ->minValue(0)
                    ->label('Modal (HPP) per Pcs')
                    ->helperText('This value will auto-calculate based on BoM below, but stay manual-editable.')
                    ->id('base_price_field'),

                // Removing redundant HPP field since base_price is now HPP


                TextInput::make('current_stock')
                    ->numeric()
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->label('Available Stock'),
                
                Forms\Components\Repeater::make('recipeItems')
                    ->relationship('recipeItems')
                    ->schema([
                        Forms\Components\Select::make('raw_material_id')
                            ->relationship('rawMaterial', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Raw Material')
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateHpp($get, $set)),
                        Forms\Components\TextInput::make('quantity_required')
                            ->numeric()
                            ->required()
                            ->label('Usage qty')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateHpp($get, $set)),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->label('Bill of Materials (BoM) - Kebutuhan Bahan per 1 Pcs')
                    ->addActionLabel('Tambah Bahan ke BoM')
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateHpp($get, $set)),
            ]);
    }

    public static function updateHpp(Get $get, Set $set): void
    {
        $recipeItems = $get('recipeItems') ?? [];
        $totalHpp = 0;

        foreach ($recipeItems as $item) {
            if (!empty($item['raw_material_id']) && !empty($item['quantity_required'])) {
                $material = RawMaterial::find($item['raw_material_id']);
                if ($material) {
                    $totalHpp += (float) $item['quantity_required'] * (float) $material->price_per_unit;
                }
            }
        }

        $set('base_price', $totalHpp);
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
                    ->label('Product'),

                TextColumn::make('slug')
                    ->searchable()
                    ->label('Slug'),

                TextColumn::make('base_price')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->label('Modal (HPP)'),

                // Removed redundant hpp column


                TextColumn::make('current_stock')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state < 50 ? 'danger' : 'success')
                    ->label('Stock'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created At'),
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
