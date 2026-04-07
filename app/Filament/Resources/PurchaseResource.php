<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Purchase;
use App\Models\RawMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Procurement';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Main Purchase Info')
                    ->description('One receipt can now contain items from multiple vendors.')
                    ->schema([
                        Forms\Components\DatePicker::make('purchase_date')
                            ->required()
                            ->default(now())
                            ->label('Purchase Date'),
                        
                        Forms\Components\Select::make('temp_supplier_id')
                            ->label('Source Vendor (For Auto-Load)')
                            ->options(\App\Models\Supplier::all()->pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->dehydrated(false)
                            ->hint('Choose a vendor then click "Load All Items" below to populate the list.'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending')
                            ->label('Status'),

                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->readonly()
                            ->default(0)
                            ->label('Total Grand Amount'),
                    ])->columns(['sm' => 1, 'md' => 2]),

                Forms\Components\Section::make('Purchase Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('supplier_id')
                                    ->relationship('supplier', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Purchase From Vendor')
                                    ->live(),
                                Forms\Components\Select::make('raw_material_id')
                                    ->label('Material')
                                    ->options(function (Forms\Get $get) {
                                        $supplierId = $get('supplier_id');
                                        if ($supplierId) {
                                            $supplier = \App\Models\Supplier::find($supplierId);
                                            if ($supplier) {
                                                return $supplier->rawMaterials->pluck('name', 'id');
                                            }
                                        }
                                        return \App\Models\RawMaterial::all()->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $material = \App\Models\RawMaterial::find($state);
                                        if ($material) {
                                            $set('unit_price', $material->price_per_unit);
                                        }
                                    }),
                                Forms\Components\TextInput::make('qty')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->label('Qty (Packs/Items)')
                                    ->disabled(fn (?Purchase $record) => $record && in_array($record->status, ['completed', 'cancelled']))
                                    ->afterStateUpdated(fn ($state, Set $set, Forms\Get $get) => 
                                        $set('subtotal', $state * ($get('unit_price') ?? 0))),
                                Forms\Components\TextInput::make('unit_price')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->label('Price per Pack/Item')
                                    ->afterStateUpdated(fn ($state, Set $set, Forms\Get $get) => 
                                        $set('subtotal', $state * ($get('qty') ?? 0))),
                                Forms\Components\TextInput::make('subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readonly()
                                    ->label('Subtotal (Auto)'),
                            ])
                            ->columns(['sm' => 1, 'md' => 4])
                            ->extraItemActions([
                                Forms\Components\Actions\Action::make('loadFromSupplier')
                                    ->icon('heroicon-m-arrow-path')
                                    ->label('Load All Vendor Items')
                                    ->color('info')
                                    ->action(function (Forms\Get $get, Forms\Set $set) {
                                        $supplierId = $get('../../temp_supplier_id');
                                        if (!$supplierId) return;
                                        
                                        $supplier = \App\Models\Supplier::with('rawMaterials')->find($supplierId);
                                        if (!$supplier) return;

                                        $existingItems = $get('items') ?? [];
                                        
                                        foreach ($supplier->rawMaterials as $material) {
                                            $existingItems[] = [
                                                'supplier_id' => $supplierId,
                                                'raw_material_id' => $material->id,
                                                'qty' => 0,
                                                'unit_price' => $material->price_per_unit,
                                                'subtotal' => 0,
                                            ];
                                        }

                                        $set('items', $existingItems);
                                    }),
                            ])
                            ->live()
                            ->afterStateUpdated(function (Set $set, Forms\Get $get) {
                                $items = $get('items') ?? [];
                                $total = 0;
                                foreach ($items as $item) {
                                    $total += $item['subtotal'] ?? 0;
                                }
                                $set('total_amount', $total);
                            }),
                    ]),
                
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchase_date')
                    ->date()
                    ->sortable()
                    ->label('Purchase Date'),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items Count'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->label('Total Amount'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->label('Status'),
                Tables\Columns\TextColumn::make('created_at')
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
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}
