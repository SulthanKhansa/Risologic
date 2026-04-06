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
                Forms\Components\Section::make('Informasi Utama Nota')
                    ->description('Satu nota sekarang bisa berisi belanjaan dari banyak vendor sekaligus.')
                    ->schema([
                        Forms\Components\DatePicker::make('purchase_date')
                            ->required()
                            ->default(now())
                            ->label('Tanggal Belanja'),
                        
                        Forms\Components\Select::make('temp_supplier_id')
                            ->label('Pilih Vendor Sumber (Untuk Auto-Load)')
                            ->options(Supplier::all()->pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->dehydrated(false)
                            ->hint('Pilih vendor ini lalu klik "Tarik Barang" di bawah untuk memunculkan semua barang belanjaan.'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending')
                            ->label('Status Nota'),

                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->readonly()
                            ->default(0)
                            ->label('Total Belanja Keseluruhan'),
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
                                    ->label('Beli Di Toko')
                                    ->live(),
                                Forms\Components\Select::make('raw_material_id')
                                    ->label('Bahan Baku')
                                    ->options(function (Forms\Get $get) {
                                        $supplierId = $get('supplier_id');
                                        if ($supplierId) {
                                            $supplier = Supplier::find($supplierId);
                                            if ($supplier) {
                                                return $supplier->rawMaterials->pluck('name', 'id');
                                            }
                                        }
                                        return RawMaterial::all()->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $material = RawMaterial::find($state);
                                        if ($material) {
                                            $set('unit_price', $material->price_per_unit);
                                            $set('pack_price', $material->pack_price);
                                            $set('pack_size', $material->pack_size);
                                        }
                                    }),
                                Forms\Components\TextInput::make('qty')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->disabled(fn (?Purchase $record) => $record && in_array($record->status, ['completed', 'cancelled']))
                                    ->afterStateUpdated(fn ($state, Set $set, Forms\Get $get) => 
                                        $set('subtotal', $state * ($get('unit_price') ?? 0))),
                                Forms\Components\TextInput::make('pack_price')
                                    ->label('Harga/Botol')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Set $set) {
                                        $price = (float) ($get('pack_price') ?? 0);
                                        $size = (float) ($get('pack_size') ?? 0);
                                        if ($size > 0) $set('unit_price', $price / $size);
                                        $set('subtotal', $get('qty') * ($get('unit_price') ?? 0));
                                    }),
                                Forms\Components\TextInput::make('pack_size')
                                    ->label('Isi/Botol')
                                    ->numeric()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Set $set) {
                                        $price = (float) ($get('pack_price') ?? 0);
                                        $size = (float) ($get('pack_size') ?? 0);
                                        if ($size > 0) $set('unit_price', $price / $size);
                                        $set('subtotal', $get('qty') * ($get('unit_price') ?? 0));
                                    }),
                                Forms\Components\TextInput::make('unit_price')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->readonly()
                                    ->label('Harga/Ml'),
                                Forms\Components\TextInput::make('subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readonly(),
                            ])
                            ->columns(['sm' => 1, 'md' => 6])
                            ->extraItemActions([
                                Forms\Components\Actions\Action::make('loadFromSupplier')
                                    ->icon('heroicon-m-arrow-path')
                                    ->label('Tarik Semua Barang Vendor')
                                    ->color('info')
                                    ->action(function (Forms\Get $get, Forms\Set $set) {
                                        $supplierId = $get('../../temp_supplier_id');
                                        if (!$supplierId) return;
                                        
                                        $supplier = Supplier::with('rawMaterials')->find($supplierId);
                                        if (!$supplier) return;

                                        $existingItems = $get('items') ?? [];
                                        
                                        foreach ($supplier->rawMaterials as $material) {
                                            $existingItems[] = [
                                                'supplier_id' => $supplierId,
                                                'raw_material_id' => $material->id,
                                                'qty' => 0,
                                                'pack_price' => $material->pack_price,
                                                'pack_size' => $material->pack_size,
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
                    ->label('Tgl Belanja'),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Jml Barang'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
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
