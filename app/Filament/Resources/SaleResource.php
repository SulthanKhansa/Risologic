<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('channel')
                    ->options([
                        'stand' => 'Penjualan Stand',
                        'po' => 'Purchase Order',
                        'gofood' => 'GoFood',
                    ])
                    ->required()
                    ->label('Channel Penjualan'),

                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->label('Produk'),

                TextInput::make('qty')
                    ->numeric()
                    ->required()
                    ->label('Jumlah'),

                TextInput::make('total_price')
                    ->numeric()
                    ->step('0.01')
                    ->required()
                    ->label('Total Harga'),

                TextInput::make('net_income')
                    ->numeric()
                    ->step('0.01')
                    ->disabled()
                    ->label('Net Income (Otomatis)'),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required()
                    ->label('Status'),

                Select::make('recorded_by')
                    ->relationship('recordedBy', 'name')
                    ->required()
                    ->label('Pencatat'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),

                TextColumn::make('product.name')
                    ->searchable()
                    ->label('Produk'),

                BadgeColumn::make('channel')
                    ->colors([
                        'success' => 'stand',
                        'info' => 'po',
                        'warning' => 'gofood',
                    ])
                    ->label('Channel'),

                TextColumn::make('qty')
                    ->sortable()
                    ->label('Qty'),

                TextColumn::make('total_price')
                    ->money('IDR')
                    ->sortable()
                    ->label('Total'),

                TextColumn::make('net_income')
                    ->money('IDR')
                    ->sortable()
                    ->label('Net Income (Cuan)'),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ])
                    ->label('Status'),

                TextColumn::make('recordedBy.name')
                    ->label('Pencatat'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Waktu Pencatatan'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->options([
                        'stand' => 'Stand',
                        'po' => 'PO',
                        'gofood' => 'GoFood',
                    ]),
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
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}
