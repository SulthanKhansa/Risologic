# 🛡️ Risologic: Smart ERP & POS Solutions

Risologic adalah solusi digital all-in-one untuk bisnis F&B yang menggabungkan kekuatan sistem ERP (Enterprise Resource Planning), POS (Point of Sales), dan Public Website dalam satu ekosistem terpadu.

## 🚀 Business Flow & Logic
Project ini dirancang dengan alur yang mencerminkan kebutuhan bisnis nyata:

*   **Inventory Management**: Input bahan baku, pengelolaan supplier, dan pemantauan stok secara real-time.
*   **Financial Automation**: Sistem secara otomatis menghitung margin keuntungan berdasarkan HPP yang dinamis.
*   **Sales Flow**: Transaksi melalui POS akan langsung memotong stok di gudang dan mencatat laporan pendapatan di dashboard admin.
*   **Customer Facing**: Public website yang memungkinkan pelanggan melihat menu dan informasi bisnis secara transparan.

## 🛠️ Technical Stack
*   **Framework**: Laravel 11 (The latest features)
*   **Admin Panel**: Filament PHP v3 (TALL Stack: Tailwind, Alpine.js, Laravel, Livewire)
*   **Database**: MySQL dengan optimasi triggers dan functions untuk integritas data stok.
*   **Infrastructure**: Dockerized environment, deployed on Railway.

## 🔧 Core Features
*   ✅ **Real-time Inventory**: Notifikasi otomatis jika stok di bawah limit.
*   ✅ **Automated Reports**: Laporan penjualan harian, mingguan, dan bulanan yang bisa diekspor.
*   ✅ **Role-Based Access**: Keamanan data dengan pemisahan hak akses admin dan staf.
*   ✅ **Mobile Responsive**: Bisa diakses dari tablet/HP untuk kebutuhan kasir di lapangan.
