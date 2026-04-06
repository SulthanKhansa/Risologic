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
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Penjualan';

    public static function canViewAny(): bool { return true; }
    public static function canCreate(): bool { return true; }
    public static function canEdit(Model $record): bool { return true; }
    public static function canDelete(Model $record): bool { return true; }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Select::make('channel')
                            ->options([
                                'stand' => 'Stand',
                                'online' => 'Online',
                                'pre_order' => 'Pre-Order',
                            ])
                            ->required()
                            ->live()
                            ->disabled(fn (?Sale $record) => $record && in_array($record->status, ['paid', 'cancelled']))
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                if ($state === 'online') {
                                    $totalPrice = (float) ($get('total_price') ?? 0);
                                    $set('admin_fee', $totalPrice * 0.20);
                                } else {
                                    $set('admin_fee', 0);
                                }
                                self::updateCalculations($get, $set);
                            })
                            ->label('Channel Penjualan'),

                        Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->disabled(fn (?Sale $record) => $record && in_array($record->status, ['paid', 'cancelled']))
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateCalculations($get, $set))
                            ->label('Produk'),

                        TextInput::make('qty')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->live(onBlur: true)
                            ->disabled(fn (?Sale $record) => $record && in_array($record->status, ['paid', 'cancelled']))
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateCalculations($get, $set))
                            ->label('Quantity'),

                        TextInput::make('total_price')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->live(onBlur: true)
                            ->disabled(fn (?Sale $record) => $record && in_array($record->status, ['paid', 'cancelled']))
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                if ($get('channel') === 'online') {
                                    $set('admin_fee', (float) ($state ?? 0) * 0.20);
                                }
                                self::updateCalculations($get, $set);
                            })
                            ->label('Total Harga Jual'),
                    ])->columns(['sm' => 1, 'md' => 2]),

                Forms\Components\Section::make('Rincian Biaya & Profit')
                    ->schema([
                        TextInput::make('admin_fee')
                            ->numeric()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->disabled(fn (?Sale $record) => $record && in_array($record->status, ['paid', 'cancelled']))
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateCalculations($get, $set))
                            ->label('Biaya Admin / Komisi'),

                        TextInput::make('net_income')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->label('Pendapatan Bersih (Net)'),

                        Forms\Components\Placeholder::make('profit_prediction')
                            ->label('Estimasi Cuan (Gross Profit)')
                            ->content(function (Get $get) {
                                $grossProfit = (float) ($get('gross_profit_hidden') ?? 0);
                                $margin = (float) ($get('margin_hidden') ?? 0);
                                return 'Rp ' . number_format($grossProfit, 0, ',', '.') . ' (' . number_format($margin, 1) . '%)';
                            }),

                        Forms\Components\Hidden::make('gross_profit_hidden'),
                        Forms\Components\Hidden::make('margin_hidden'),
                    ])->columns(['sm' => 1, 'md' => 2]),

                Forms\Components\Section::make('Status & Petugas')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('paid')
                            ->required()
                            ->label('Status'),

                        Select::make('recorded_by')
                            ->relationship('recordedBy', 'name')
                            ->default(auth()->id())
                            ->required()
                            ->label('Pencatat'),
                    ])->columns(['sm' => 1, 'md' => 2]),
            ]);
    }

    public static function updateCalculations(Get $get, Set $set): void
    {
        $productId = $get('product_id');
        $qty = (int) ($get('qty') ?? 0);
        $totalPrice = (float) ($get('total_price') ?? 0);
        $adminFee = (float) ($get('admin_fee') ?? 0);

        if (!$productId || $qty <= 0) return;

        $product = Product::find($productId);
        if (!$product) return;

        $totalCost = $product->base_price * $qty;
        $netIncome = $totalPrice - $adminFee;
        $grossProfit = $netIncome - $totalCost;
        $margin = $netIncome > 0 ? ($grossProfit / $netIncome) * 100 : 0;

        $set('net_income', $netIncome);
        $set('gross_profit_hidden', $grossProfit);
        $set('margin_hidden', $margin);
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
                        'warning' => 'online',
                        'info' => 'pre_order',
                    ])
                    ->label('Channel'),

                TextColumn::make('qty')
                    ->sortable()
                    ->label('Quantity'),

                TextColumn::make('total_price')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->label('Total Price'),

                TextColumn::make('net_income')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total Net'))
                    ->label('Net Income'),

                TextColumn::make('gross_profit')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Cuan Bersih'),

                TextColumn::make('margin_percentage')
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 1) . '%')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        (float) $state >= 30 => 'success',
                        (float) $state >= 10 => 'warning',
                        default => 'danger',
                    })
                    ->label('Margin %'),

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
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Waktu Pencatatan'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->options([
                        'stand' => 'Stand',
                        'online' => 'Online',
                        'pre_order' => 'Pre-Order',
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
