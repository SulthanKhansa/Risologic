<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionEventResource\Pages;
use App\Models\ProductionEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductionEventResource extends Resource
{
    protected static ?string $model = ProductionEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    
    protected static ?string $navigationGroup = 'Production';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Production';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produksi')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Product To Produce')
                            ->live()
                            ->disabled(fn (?ProductionEvent $record) => $record && $record->status === 'completed'),
                        Forms\Components\TextInput::make('quantity_produced')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->label('Quantity Produced')
                            ->live(onBlur: true)
                            ->disabled(fn (?ProductionEvent $record) => $record && $record->status === 'completed'),
                        Forms\Components\DatePicker::make('production_date')
                            ->required()
                            ->default(now())
                            ->label('Date of Production')
                            ->disabled(fn (?ProductionEvent $record) => $record && $record->status === 'completed'),
                        
                        Forms\Components\Hidden::make('status')
                            ->default('completed'),

                        Forms\Components\Placeholder::make('bom_preview')
                            ->label('Bahan yang Dibutuhkan (Resep)')
                            ->columnSpanFull()
                            ->content(function (Forms\Get $get) {
                                $productId = $get('product_id');
                                $qty = (float) ($get('quantity_produced') ?? 0);
                                if ($productId && $qty > 0) {
                                    $product = \App\Models\Product::with('recipeItems.rawMaterial')->find($productId);
                                    if ($product) {
                                        $html = '<ul style="list-style: disc; margin-left: 20px;">';
                                        foreach ($product->recipeItems as $item) {
                                            $totalUsed = $qty * $item->quantity_required;
                                            $html .= "<li><strong>{$item->rawMaterial->name}</strong>: {$totalUsed} {$item->rawMaterial->unit}</li>";
                                        }
                                        $html .= '</ul>';
                                        return new \Illuminate\Support\HtmlString($html);
                                    }
                                }
                                return 'Pilih produk dan jumlah untuk melihat resep.';
                            }),
                    ])->columns(['sm' => 1, 'md' => 3]),
                
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull()
                    ->label('Optional Notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->sortable()
                    ->searchable()
                    ->label('Product'),
                Tables\Columns\TextColumn::make('quantity_produced')
                    ->numeric()
                    ->sortable()
                    ->label('Quantity'),
                Tables\Columns\TextColumn::make('production_date')
                    ->date()
                    ->sortable()
                    ->label('Date'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'draft',
                        'danger' => 'cancelled',
                    ])
                    ->label('Status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Notes'),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductionEvents::route('/'),
            'create' => Pages\CreateProductionEvent::route('/create'),
            'edit' => Pages\EditProductionEvent::route('/{record}/edit'),
        ];
    }
}
