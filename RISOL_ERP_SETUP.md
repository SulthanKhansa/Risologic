# Risol ERP System - Complete Setup Guide

## ✨ Features Implemented

### 1. **Database Migrations & Models**
- **Product Table**: Stores product inventory with name, slug, base_price (decimal 12,2), and current_stock
- **Sale Table**: Multi-channel sales tracking with automatic net income calculations
- **Relationships**: Product has many Sales, Sale belongs to Product and User

### 2. **Automatic Business Logic (Sale Model Observer)**
- ✅ Automatically calculates `net_income` based on channel:
  - **GoFood**: `net_income = total_price * 0.80` (20% commission deducted)
  - **Stand**: `net_income = total_price`
  - **PO**: `net_income = total_price`
- ✅ Automatically deducts `qty` from `Product.current_stock` when a sale is created
- ✅ Prevents overselling with real-time inventory sync

### 3. **Filament Admin Panel Resource**
- Complete CRUD interface for Sales management
- Mobile-friendly responsive design perfect for stand teams
- Form components:
  - Select dropdown for channels (Stand, PO, GoFood)
  - TextInput for quantity and total_price
  - Auto-calculated net_income display (read-only)
  - Status tracking (Pending, Paid, Cancelled)
- Table features:
  - Search functionality
  - Channel filter (visual badges with color coding)
  - All columns display (product, qty, total, net_income, status)
  - Sortable columns
  - User tracking (who recorded the sale)

### 4. **Role-Based Access Control (Shield)**
- **Staff Role**: 
  - ✅ Can VIEW sales
  - ✅ Can CREATE new sales
  - ❌ CANNOT update existing sales
  - ❌ CANNOT delete sales
- **Admin Role**:
  - ✅ Full permissions for all operations
  - ✅ Can manage Products
  - ✅ Can manage all Sales
  - ✅ Can update and delete

---

## 🚀 Installation Steps

### Step 1: Fix PHP Environment
Since the project requires PHP 8.2+, use Laragon's PHP directory (adjust version as needed):

```bash
# In PowerShell, run:
$env:PATH = "C:\laragon\bin\php\php-8.2.1-Win32-vs16-x64;$env:PATH"
cd C:\Users\user\Documents\Risologic
```

### Step 2: Install Filament & Dependencies
```bash
php composer.phar require filament/filament spatie/laravel-permission -W
php artisan filament:install --panels=admin
```

### Step 3: Run Migrations
```bash
php artisan migrate
```

### Step 4: Create Admin User
```bash
php artisan tinker

# In Tinker shell:
$user = \App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@risol.test',
    'password' => bcrypt('password'),
]);
$user->assignRole('admin');
exit
```

### Step 5: Create Staff User (Optional)
```bash
php artisan tinker

$staff = \App\Models\User::create([
    'name' => 'Staff Member',
    'email' => 'staff@risol.test',
    'password' => bcrypt('password'),
]);
$staff->assignRole('staff');
exit
```

### Step 6: Create Sample Products
```bash
php artisan tinker

\App\Models\Product::create([
    'name' => 'Risol Mayo',
    'slug' => 'risol-mayo',
    'base_price' => 15000,
    'current_stock' => 100,
]);

\App\Models\Product::create([
    'name' => 'Risol Keju',
    'slug' => 'risol-keju',
    'base_price' => 18000,
    'current_stock' => 150,
]);

exit
```

### Step 7: Start Development Server
```bash
php artisan serve
```

Access the admin panel at: `http://localhost:8000/admin`

---

## 🔐 Shield Configuration Details

### File: `app/Policies/SalePolicy.php`
Defines who can perform what action on Sales:
- `viewAny()`: Staff and Admin only
- `view()`: Staff and Admin only
- `create()`: Staff and Admin only
- `update()`: **Admin only** ← Staff is blocked
- `delete()`: **Admin only** ← Staff is blocked

### File: `app/Policies/ProductPolicy.php`
Restricts Product management to Admin only.

### Registering Policies
The `AuthServiceProvider.php` registers these policies:
```php
protected $policies = [
    Product::class => ProductPolicy::class,
    Sale::class => SalePolicy::class,
];
```

---

## 📊 Business Logic Reference

### Sale Creation Flow
When a staff member creates a new sale:

1️⃣ **Form Submission**
   - User selects: Channel, Product, Qty, Total Price

2️⃣ **Automatic Calculations** (via Sale Model `booted()` method)
   - If `channel === 'gofood'`: 
     - `net_income = total_price * 0.80`
   - Else:
     - `net_income = total_price`

3️⃣ **Inventory Update** (automatic)
   - `Product.current_stock -= qty`
   - Example: If Risol Mayo had 100 stock and you sell 5, new stock = 95

4️⃣ **Record Saved**
   - Sale status defaults to "pending"
   - `recorded_by` = current authenticated user
   - `created_at` timestamp auto-set

---

## 🎯 Key Models & Relationships

### Product Model
```php
class Product {
    // Attributes
    - id
    - name (unique)
    - slug (unique)
    - base_price (decimal 12,2)
    - current_stock (int)
    - timestamps
    
    // Relationships
    - hasMany('sales')
}
```

### Sale Model
```php
class Sale {
    // Attributes
    - id
    - channel (enum: stand, po, gofood)
    - product_id (FK)
    - qty (int)
    - total_price (decimal 12,2)
    - net_income (decimal 12,2) [AUTO-CALCULATED]
    - status (enum: pending, paid, cancelled)
    - recorded_by (FK to users)
    - timestamps
    
    // Relationships
    - belongsTo('product')
    - belongsTo('user', 'recorded_by')
    
    // Observers
    - booted(): Handles auto-calculation & inventory deduction
}
```

### User Model
```php
class User {
    // Already comes with Laravel
    // Extended with roles/permissions via Spatie
    - assignRole('staff')
    - assignRole('admin')
    - hasRole('staff')
}
```

---

## 📱 Mobile-Friendly Features

The Filament Resource is fully responsive:
- ✅ Stacked forms on mobile
- ✅ Touch-friendly buttons and inputs
- ✅ Scrollable data tables
- ✅ Mobile-optimized Select dropdowns
- ✅ Perfect for team at the stand recording sales on phones/tablets

---

## 🔍 Testing the System

### Test GoFood Commission Logic
```bash
# Create a GoFood sale for 100,000
Sale::create([
    'channel' => 'gofood',
    'product_id' => 1,
    'qty' => 1,
    'total_price' => 100000,
    'recorded_by' => 1,
]);

# Check the result:
Sale::latest()->first()->net_income; // Should show 80000
```

### Test Inventory Sync
```bash
# Check product stock before
Product::find(1)->current_stock; // 100

# Create a sale of qty 10
Sale::create([...]);

# Check stock after
Product::find(1)->current_stock; // Should show 90
```

---

## 🛠️ File Structure

```
app/
├── Models/
│   ├── Product.php (with hasMany relationships)
│   ├── Sale.php (with observer & auto-calculation)
│   └── User.php
├── Filament/
│   └── Resources/
│       ├── SaleResource.php (Admin UI definition)
│       └── SaleResource/
│           └── Pages/
│               ├── ListSales.php
│               ├── CreateSale.php
│               └── EditSale.php
├── Policies/
│   ├── SalePolicy.php (Staff: view, create only)
│   └── ProductPolicy.php (Admin only)
└── Providers/
    └── AuthServiceProvider.php (Policy registration)

database/
└── migrations/
    ├── 0001_01_02_100000_create_products_table.php
    └── 0001_01_02_200000_create_sales_table.php
```

---

## ⚠️ Environment Requirements

- **PHP**: 8.2 or higher
- **Laravel**: 11.x
- **Database**: MySQL/MariaDB
- **Packages**: 
  - filament/filament ^5.4
  - spatie/laravel-permission ^6.x

---

## 🐛 Troubleshooting

### Issue: "Class 'SalePolicy' not found"
**Solution**: Clear the autoloader cache:
```bash
php artisan clear-cache
composer dump-autoload
```

### Issue: PHP version error
**Solution**: Use Laragon's PHP 8.2 bin directory:
```bash
$env:PATH = "C:\laragon\bin\php\php-8.2.1-Win32-vs16-x64;$env:PATH"
```

### Issue: Staff can still delete sales
**Solution**: Ensure Spatie permissions are installed:
```bash
php composer.phar require spatie/laravel-permission
```

---

## 📝 Next Steps

1. Install Filament & dependencies (see Step 2 above)
2. Run migrations to create tables
3. Create users with roles
4. Test the admin panel

Your Risol ERP is ready! 🎉
