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
    
    protected static ?string $navigationGroup = 'Produksi';
    protected static ?string $modelLabel = 'Produksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Produk Jadi (Target)'),
                Forms\Components\TextInput::make('quantity_produced')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->label('Jumlah Diproduksi'),
                Forms\Components\DatePicker::make('production_date')
                    ->required()
                    ->default(now())
                    ->label('Tanggal Produksi'),
                Forms\Components\Select::make('status')
                    ->options([
                        'completed' => 'Selesai (Potong Stok Bahan Baku Sekarang)',
                        'draft' => 'Draft / Rencana',
                    ])
                    ->default('completed')
                    ->required()
                    ->label('Status Produksi'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull()
                    ->label('Catatan'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->sortable()
                    ->searchable()
                    ->label('Produk'),
                Tables\Columns\TextColumn::make('quantity_produced')
                    ->numeric()
                    ->sortable()
                    ->label('Jumlah'),
                Tables\Columns\TextColumn::make('production_date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'draft',
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
