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

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool { return true; }
    public static function canCreate(): bool { return true; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return true; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return true; }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Info')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->unique('products', 'name', ignoreRecord: true)
                            ->maxLength(255)
                            ->label('Product Name'),

                        TextInput::make('current_stock')
                            ->numeric()
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->label('Available Stock'),
                            
                        Forms\Components\Select::make('type')
                            ->options([
                                'final' => 'Final Product',
                                'intermediate' => 'Intermediate Product',
                            ])
                            ->required()
                            ->default('final')
                            ->label('Product Type'),
                    ])->columns(3),

                Forms\Components\Hidden::make('slug'),
                Forms\Components\Hidden::make('base_price')->default(0),

                Forms\Components\Section::make('Bill of Materials')
                    ->schema([
                        TextInput::make('batch_yield')
                            ->numeric()
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->label('1 Resep = berapa pcs?')
                            ->suffix('pcs')
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('recipeItems')
                            ->relationship('recipeItems')
                            ->schema([
                                Forms\Components\Select::make('ingredient_type')
                                    ->options([
                                        'raw_material' => 'Bahan Baku',
                                        'product' => 'Produk (Setengah Jadi)',
                                    ])
                                    ->required()
                                    ->default('raw_material')
                                    ->live()
                                    ->label('Tipe Bahan')
                                    ->afterStateHydrated(function (Forms\Components\Select $component, ?\App\Models\RecipeItem $record) {
                                        if ($record) {
                                            $component->state($record->ingredient_product_id ? 'product' : 'raw_material');
                                        }
                                    })
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $set('raw_material_id', null);
                                        $set('ingredient_product_id', null);
                                    }),

                                Forms\Components\Select::make('raw_material_id')
                                    ->relationship('rawMaterial', 'name')
                                    ->required(fn (Get $get) => $get('ingredient_type') === 'raw_material')
                                    ->visible(fn (Get $get) => $get('ingredient_type') === 'raw_material')
                                    ->searchable()
                                    ->preload()
                                    ->label('Material')
                                    ->live(),

                                Forms\Components\Select::make('ingredient_product_id')
                                    ->relationship('ingredientProduct', 'name')
                                    ->required(fn (Get $get) => $get('ingredient_type') === 'product')
                                    ->visible(fn (Get $get) => $get('ingredient_type') === 'product')
                                    ->searchable()
                                    ->preload()
                                    ->label('Product')
                                    ->live(),

                                Forms\Components\TextInput::make('quantity_required')
                                    ->numeric()
                                    ->required()
                                    ->label('Usage qty')
                                    ->formatStateUsing(fn ($state) => $state === null ? null : (str_contains((string)$state, '.') ? (string)(float)$state : $state))
                                    ->suffix(function (Get $get) {
                                        if ($get('ingredient_type') === 'product') {
                                            return 'pcs';
                                        }
                                        if ($get('raw_material_id')) {
                                            $mat = \App\Models\RawMaterial::find($get('raw_material_id'));
                                            return $mat ? $mat->unit : '';
                                        }
                                        return '';
                                    })
                                    ->live(onBlur: true),
                                
                                Forms\Components\Placeholder::make('ingredient_cost')
                                    ->label('Estimasi Biaya')
                                    ->content(function (Get $get) {
                                        $qty = (float) ($get('quantity_required') ?? 0);
                                        
                                        $cost = 0;
                                        if ($get('ingredient_type') === 'product' && $get('ingredient_product_id')) {
                                            $prod = \App\Models\Product::find($get('ingredient_product_id'));
                                            $cost = $prod ? $prod->base_price : 0;
                                        } elseif ($get('ingredient_type') === 'raw_material' && $get('raw_material_id')) {
                                            $mat = \App\Models\RawMaterial::find($get('raw_material_id'));
                                            $cost = $mat ? $mat->price_per_unit : 0;
                                        }

                                        return 'Rp ' . number_format($qty * $cost, 0, ',', '.');
                                    }),
                            ])
                            ->columns(['sm' => 1, 'md' => 4])
                            ->columnSpanFull()
                            ->label('Bahan-bahan per 1 Resep')
                            ->addActionLabel('Tambah Bahan ke BoM')
                            ->live(),
                        
                        Forms\Components\Placeholder::make('total_hpp_summary')
                            ->label('TOTAL MODAL (HPP) BARU')
                            ->columnSpanFull()
                            ->content(function (Get $get) {
                                $recipeItems = $get('recipeItems') ?? [];
                                $totalHpp = 0;
                                foreach ($recipeItems as $item) {
                                    $qty = (float) ($item['quantity_required'] ?? 0);
                                    $cost = 0;
                                    $type = $item['ingredient_type'] ?? 'raw_material';
                                    
                                    if ($type === 'product' && !empty($item['ingredient_product_id'])) {
                                        $prod = \App\Models\Product::find($item['ingredient_product_id']);
                                        $cost = $prod ? $prod->base_price : 0;
                                    } elseif ($type === 'raw_material' && !empty($item['raw_material_id'])) {
                                        $mat = \App\Models\RawMaterial::find($item['raw_material_id']);
                                        $cost = $mat ? $mat->price_per_unit : 0;
                                    }
                                    
                                    $totalHpp += $qty * $cost;
                                }
                                $batchYield = max(1, (int) ($get('batch_yield') ?? 1));
                                $hppPerPcs = $batchYield > 0 ? $totalHpp / $batchYield : 0;
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="text-base text-gray-500">Total biaya 1 resep: Rp ' . number_format($totalHpp, 0, ',', '.') . '</div>' .
                                    '<div class="text-2xl font-bold text-primary-600 mt-1">HPP per pcs: Rp ' . number_format($hppPerPcs, 0, ',', '.') . '</div>'
                                );
                            }),
                    ]),
            ])->columns(['sm' => 1, 'md' => 2]);
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
                    
                TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'success' => 'final',
                        'info' => 'intermediate',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'final' => 'Final',
                        'intermediate' => 'Intermediate',
                        default => $state,
                    })
                    ->label('Type'),

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
                    ->toggleable(isToggledHiddenByDefault: true)
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
            ])
            ->paginated(false);
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
