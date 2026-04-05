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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Purchase Info')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->required()
                            ->preload()
                            ->disabled(fn (?Purchase $record) => $record && in_array($record->status, ['completed', 'cancelled'])),
                        Forms\Components\DatePicker::make('purchase_date')
                            ->required()
                            ->default(now())
                            ->disabled(fn (?Purchase $record) => $record && in_array($record->status, ['completed', 'cancelled'])),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending')
                            ->native(false),
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->readonly()
                            ->default(0),
                    ])->columns(['sm' => 1, 'md' => 2]),

                Forms\Components\Section::make('Purchase Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('raw_material_id')
                                    ->label('Material')
                                    ->options(RawMaterial::all()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->disabled(fn (?Purchase $record) => $record && in_array($record->status, ['completed', 'cancelled']))
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $material = RawMaterial::find($state);
                                        if ($material) {
                                            $set('unit_price', $material->price_per_unit);
                                        }
                                    }),
                                Forms\Components\TextInput::make('qty')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->disabled(fn (?Purchase $record) => $record && in_array($record->status, ['completed', 'cancelled']))
                                    ->afterStateUpdated(fn ($state, Set $set, Forms\Get $get) => 
                                        $set('subtotal', $state * ($get('unit_price') ?? 0))),
                                Forms\Components\TextInput::make('unit_price')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->reactive()
                                    ->disabled(fn (?Purchase $record) => $record && in_array($record->status, ['completed', 'cancelled']))
                                    ->afterStateUpdated(fn ($state, Set $set, Forms\Get $get) => 
                                        $set('subtotal', ($get('qty') ?? 0) * $state)),
                                Forms\Components\TextInput::make('subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readonly(),
                            ])
                            ->columns(['sm' => 1, 'md' => 4])
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->searchable()
                    ->sortable(),
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
