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
    protected static ?string $modelLabel = 'Production';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Finished Product')
                    ->disabled(fn (?ProductionEvent $record) => $record && $record->status === 'completed'),
                Forms\Components\TextInput::make('quantity_produced')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->label('Quantity Produced')
                    ->disabled(fn (?ProductionEvent $record) => $record && $record->status === 'completed'),
                Forms\Components\DatePicker::make('production_date')
                    ->required()
                    ->default(now())
                    ->label('Production Date')
                    ->disabled(fn (?ProductionEvent $record) => $record && $record->status === 'completed'),
                Forms\Components\Select::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'draft' => 'Draft / Plan',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('completed')
                    ->required()
                    ->label('Production Status'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull()
                    ->label('Notes'),
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
