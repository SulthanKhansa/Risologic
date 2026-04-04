# Risol ERP - Code Examples & API Reference

## 🎯 Common Operations

### Creating a Sale (Programmatic)

```php
use App\Models\Sale;
use App\Models\Product;

// Simple way - platform auto-calculates net_income
$sale = Sale::create([
    'channel' => 'stand',
    'product_id' => 1,
    'qty' => 3,
    'total_price' => 45000,
    'recorded_by' => auth()->id(),
]);

echo $sale->net_income; // Output: 45000 (for stand channel)
echo $sale->status; // Output: pending (default)
```

### Creating GoFood Sale (With Auto 20% Commission)

```php
$gofood_sale = Sale::create([
    'channel' => 'gofood',
    'product_id' => 2,
    'qty' => 5,
    'total_price' => 90000,
    'recorded_by' => auth()->id(),
]);

echo $gofood_sale->net_income; // Output: 72000 (90000 * 0.80)
// The 20% commission (18000) is automatically deducted!
```

### Querying Sales

```php
use App\Models\Sale;

// Get all sales today
$today_sales = Sale::whereDate('created_at', today())->get();

// Get GoFood sales only
$gofood_sales = Sale::where('channel', 'gofood')->get();

// Get pending sales
$pending = Sale::where('status', 'pending')->get();

// Get sales for a specific product
$mayo_sales = Sale::where('product_id', 1)->get();

// Get sales recorded by specific user
$my_sales = Sale::where('recorded_by', auth()->id())->get();

// Complex query: GoFood sales by today
$gofood_today = Sale::where('channel', 'gofood')
    ->whereDate('created_at', today())
    ->get();
```

### Relationship Access

```php
$sale = Sale::find(1);

// Access related product
$sale->product; // Returns Product model
echo $sale->product->name; // "Risol Mayo"
echo $sale->product->current_stock; // 95

// Access who recorded the sale
$sale->recordedBy; // Returns User model
echo $sale->recordedBy->name; // "Staff Member"

// Get all sales for a product
$product = Product::find(1);
$product->sales; // Collection of all sales for this product
$total_sold = $product->sales->sum('qty'); // Total qty sold
```

### Checking Stock & Calculations

```php
$product = Product::find(1);

// Current stock after automatic deductions
echo $product->current_stock; // Already updated by Sale observer!

// Get profit breakdown for a product
$sales = $product->sales;
$total_revenue = $sales->sum('total_price');
$total_net_income = $sales->sum('net_income');
$total_commission = $total_revenue - $total_net_income;

echo "Revenue: {$total_revenue}";
echo "Net Income: {$total_net_income}";
echo "Commissions Paid: {$total_commission}";
```

---

## 💰 Financial Reports

### Daily Sales Summary

```php
use App\Models\Sale;
use Carbon\Carbon;

$today = Carbon::now()->format('Y-m-d');

$daily = Sale::whereDate('created_at', $today)
    ->with('product', 'recordedBy')
    ->get()
    ->groupBy('channel');

foreach ($daily as $channel => $sales) {
    $revenue = $sales->sum('total_price');
    $net_income = $sales->sum('net_income');
    $count = $sales->count();
    
    echo "{$channel}: {$count} sales, Revenue: {$revenue}, Net: {$net_income}";
}
```

### Channel Breakdown

```php
// Get commission percentage by channel
$sales_by_channel = Sale::all()->groupBy('channel');

$channels = [
    'stand' => ['commission' => 0, 'icon' => '🏪'],
    'po' => ['commission' => 0, 'icon' => '📦'],
    'gofood' => ['commission' => 20, 'icon' => '🍜'],
];

foreach ($sales_by_channel as $channel => $sales) {
    $total = $sales->sum('total_price');
    $net = $sales->sum('net_income');
    $commission_pct = $channels[$channel]['commission'];
    
    echo "{$channels[$channel]['icon']} {$channel}: Rp{$total} (Net: Rp{$net})";
}
```

### Top Products

```php
$top_products = Product::all()
    ->map(function($product) {
        return [
            'name' => $product->name,
            'qty_sold' => $product->sales->sum('qty'),
            'revenue' => $product->sales->sum('total_price'),
            'net_income' => $product->sales->sum('net_income'),
            'current_stock' => $product->current_stock,
        ];
    })
    ->sortByDesc('revenue')
    ->take(5);

foreach ($top_products as $product) {
    echo "{$product['name']}: {$product['qty_sold']} qty, Rp{$product['revenue']}";
}
```

---

## 🔐 Permission Checking

### In Controller/Service

```php
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SaleController extends Controller
{
    use AuthorizesRequests;

    public function update(Sale $sale)
    {
        // This automatically checks SalePolicy::update()
        $this->authorize('update', $sale);
        
        // If staff user tries this, an AuthorizationException is thrown
        // Only admin reaches here
        
        $sale->update(...);
    }

    public function delete(Sale $sale)
    {
        $this->authorize('delete', $sale);
        // Admin only
        $sale->delete();
    }
}
```

### In Blade Template

```blade
@can('update', $sale)
    <a href="/admin/sales/{{ $sale->id }}/edit">Edit</a>
@endcan

@can('delete', $sale)
    <form action="/admin/sales/{{ $sale->id }}" method="POST">
        @method('DELETE')
        <button>Delete</button>
    </form>
@endcan

<!-- Staff will only see "View" button, not Edit/Delete -->
@if(auth()->user()->hasRole('staff'))
    <!-- Only view-related content shows -->
@endif
```

---

## 📱 Using in Filament

### Modifying Sale Form

The SaleResource form automatically calculates `net_income`:

```php
// In SaleResource.php - the form already has:
TextInput::make('net_income')
    ->numeric()
    ->step('0.01')
    ->disabled() // Can't manually edit - auto-calculated
    ->label('Net Income (Otomatis)'),

// This works because Sale model booted() method handles it!
```

### Filtering Sales in Table

```php
// In SaleResource.php table (already implemented):
Tables\Filters\SelectFilter::make('channel')
    ->options([
        'stand' => 'Stand',
        'po' => 'PO',
        'gofood' => 'GoFood',
    ]),

// Staff in Filament UI can click to filter by channel
```

### Checking Staff Permissions

Filament automatically hides actions based on SalePolicy:

```php
// If logged in as Staff:
- Can see List of Sales ✓
- Can see Edit button when viewing a sale... NO ✗
- Can see Delete button... NO ✗
- Can create new sale... YES ✓

// This is handled by SalePolicy.php
```

---

## 🛠️ Database Operations

### Direct SQL Queries (artisan tinker)

```bash
php artisan tinker

# All code below runs in the Tinker shell

# List all products
Product::all()

# Get product with sales
Product::with('sales')->find(1)

# Count total sales
Sale::count()

# Sum all revenue (no commission)
Sale::sum('total_price')

# Sum all net income (with commissions deducted)
Sale::sum('net_income')

# Get average sale value
Sale::avg('total_price')

# Find sales by particular staff
Sale::where('recorded_by', 2)->get()

# Sales in past 7 days
Sale::where('created_at', '>=', now()->subDays(7))->get()

# Exit tinker
exit
```

---

## 📊 Real-World Scenarios

### Scenario 1: Daily Stand Report

```php
// Get sales for today by Risol at the stand
$today_stand_sales = Sale::where('channel', 'stand')
    ->whereDate('created_at', today())
    ->get();

$report = [
    'date' => today(),
    'total_sales_qty' => $today_stand_sales->sum('qty'),
    'total_revenue' => $today_stand_sales->sum('total_price'),
    'total_payout' => $today_stand_sales->sum('net_income'),
    'commission' => $today_stand_sales->sum('total_price') - $today_stand_sales->sum('net_income'),
    'avg_transaction' => $today_stand_sales->avg('total_price'),
];

// Output: 
// Date: 2026-04-02
// Total Sold: 45 pcs
// Revenue: Rp 675,000
// Net: Rp 675,000 (no commission for stand)
```

### Scenario 2: GoFood Earnings

```php
// Calculate GoFood earnings for the week
$gofood_sales = Sale::where('channel', 'gofood')
    ->where('created_at', '>=', now()->subWeek())
    ->get();

$gross = $gofood_sales->sum('total_price');
$net = $gofood_sales->sum('net_income');
$gofood_fees = $gross - $net;

echo "This week's GoFood:";
echo "Gross Orders: Rp {$gross}";
echo "Net Income: Rp {$net}";
echo "GoFood Commission (20%): Rp {$gofood_fees}";
```

### Scenario 3: Low Stock Alert

```php
// Products with stock < 50
$low_stock = Product::where('current_stock', '<', 50)->get();

foreach ($low_stock as $product) {
    echo "⚠️ {$product->name}: Only {$product->current_stock} left!";
    echo "Recommended to restock at Rp {$product->base_price} each";
}
```

---

## 🔄 Extending the System

### Adding a New Channel

1. **Update Migration** (if starting fresh):
```php
$table->enum('channel', ['stand', 'po', 'gofood', 'tokopedia']); // Add new channel
```

2. **Update Sale Model Observer**:
```php
protected static function booted(): void
{
    static::creating(function (self $sale) {
        if ($sale->channel === 'gofood') {
            $sale->net_income = $sale->total_price * 0.80; // 20% commission
        } elseif ($sale->channel === 'tokopedia') {
            $sale->net_income = $sale->total_price * 0.85; // 15% commission
        } else {
            $sale->net_income = $sale->total_price;
        }
        // ... rest of code
    });
}
```

3. **Update SaleResource Form**:
```php
Select::make('channel')
    ->options([
        'stand' => 'Penjualan Stand',
        'po' => 'Purchase Order',
        'gofood' => 'GoFood',
        'tokopedia' => 'Tokopedia', // Add here
    ])
```

### Creating a Dashboard Widget

```php
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RisolStats extends BaseWidget
{
    protected function getStats(): array
    {
        $today_sales = Sale::whereDate('created_at', today())->sum('net_income');
        $pending_count = Sale::where('status', 'pending')->count();
        
        return [
            Stat::make('Today\'s Cuan', "Rp " . number_format($today_sales)),
            Stat::make('Pending Sales', $pending_count),
            Stat::make('Products', Product::count()),
        ];
    }
}
```

---

## 🚨 Common Issues & Solutions

### Stock Goes Negative
**Problem**: Product stock is negative (overselling)
**Solution**: The booted() method prevents this - just ensure it's in place
```php
// It's automatic! No need for manual checks
```

### Net Income Wrong
**Problem**: GoFood sales showing 100% instead of 80%
**Solution**: Verify the channel name exactly matches 'gofood' (case-sensitive)
```php
// These are DIFFERENT:
'channel' => 'gofood'  // ✓ Correct
'channel' => 'GoFood'  // ✗ Won't apply commission logic
'channel' => 'GOFOOD'  // ✗ Won't apply commission logic
```

### Staff Can Edit Sales
**Problem**: Staff shouldn't able to edit but they can
**Solution**: Check SalePolicy and AuthServiceProvider are properly registered
```php
// Ensure in AuthServiceProvider:
Sale::class => SalePolicy::class,
```

---

## 📚 Model Summary

```php
// PRODUCT
Product::create(['name', 'slug', 'base_price', 'current_stock']);
$product->sales(); // Get all sales of this product
$product->current_stock; // Real-time count (auto-updated)

// SALE  
Sale::create(['channel', 'product_id', 'qty', 'total_price', 'recorded_by']);
$sale->net_income; // Auto-calculated based on channel
$sale->product; // Related product
$sale->recordedBy; // User who made the sale
$sale->status; // pending|paid|cancelled

// USER
User::hasRole('staff'); // Staff at stand
User::hasRole('admin'); // Full access
```

---

This reference covers 95% of common operations in Risol ERP! 🚀
