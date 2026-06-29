# Dummy Data dengan Tinker

Dokumentasi ini berisi perintah Tinker untuk membuat dummy data kategori dan produk.

## Setup Awal

```bash
# Jalankan migration
php artisan migrate

# Seed data dasar (roles + categories)
php artisan db:seed
```

---

## Tinker Commands untuk Kategori

### Lihat Semua Kategori
```bash
php artisan tinker
```
```php
App\Models\Category::all();
```

### Tambah Kategori Manual
```php
App\Models\Category::create([
    'name' => 'Snack',
    'slug' => 'snack',
    'icon' => '🍿',
    'is_active' => true
]);
```

### Nonaktifkan Kategori
```php
$category = App\Models\Category::find(1);
$category->update(['is_active' => false]);
```

---

## Tinker Commands untuk Products

### Lihat Semua Toko
```php
App\Models\Store::all();
```

### Tambah Product dengan Category
```php
// Dapatkan store dan category
$store = App\Models\Store::first();
$category = App\Models\Category::where('slug', 'makanan')->first();

// Buat product
App\Models\Product::create([
    'store_id' => $store->id,
    'category_id' => $category->id,
    'name' => 'Nasi Goreng Spesial',
    'description' => 'Nasi goreng dengan telur, ayam, dan sayuran',
    'price' => 25000,
    'stock' => 50
]);
```

### Bulk Create Products (per kategori)
```php
$store = App\Models\Store::first();

// Products untuk kategori Makanan
$makanan = App\Models\Category::where('slug', 'makanan')->first();
$productsMakanan = [
    ['name' => 'Nasi Goreng', 'price' => 20000, 'stock' => 30],
    ['name' => 'Mie Goreng', 'price' => 18000, 'stock' => 25],
    ['name' => 'Ayam Geprek', 'price' => 22000, 'stock' => 20],
    ['name' => 'Sate Ayam', 'price' => 25000, 'stock' => 15],
    ['name' => 'Rendang', 'price' => 30000, 'stock' => 10],
];

foreach ($productsMakanan as $p) {
    App\Models\Product::create([
        'store_id' => $store->id,
        'category_id' => $makanan->id,
        'name' => $p['name'],
        'description' => 'Deskripsi ' . $p['name'],
        'price' => $p['price'],
        'stock' => $p['stock']
    ]);
}
```

### Products untuk Kategori Minuman
```php
$store = App\Models\Store::first();
$minuman = App\Models\Category::where('slug', 'minuman')->first();
$productsMinuman = [
    ['name' => 'Es Teh Manis', 'price' => 5000, 'stock' => 100],
    ['name' => 'Es Jeruk', 'price' => 7000, 'stock' => 80],
    ['name' => 'Kopi Hitam', 'price' => 10000, 'stock' => 50],
    ['name' => 'Jus Alpukat', 'price' => 15000, 'stock' => 40],
    ['name' => 'Es Cincau', 'price' => 8000, 'stock' => 60],
];

foreach ($productsMinuman as $p) {
    App\Models\Product::create([
        'store_id' => $store->id,
        'category_id' => $minuman->id,
        'name' => $p['name'],
        'description' => 'Deskripsi ' . $p['name'],
        'price' => $p['price'],
        'stock' => $p['stock']
    ]);
}
```

### Products untuk Kategori Elektronik
```php
$store = App\Models\Store::first();
$elektronik = App\Models\Category::where('slug', 'elektronik')->first();
$productsElektronik = [
    ['name' => 'Kabel USB Type-C', 'price' => 35000, 'stock' => 50],
    ['name' => 'Powerbank 10000mAh', 'price' => 120000, 'stock' => 20],
    ['name' => 'Earphone Bluetooth', 'price' => 150000, 'stock' => 15],
    ['name' => 'Charger Fast Charging', 'price' => 75000, 'stock' => 25],
    ['name' => 'Phone Stand', 'price' => 45000, 'stock' => 30],
];

foreach ($productsElektronik as $p) {
    App\Models\Product::create([
        'store_id' => $store->id,
        'category_id' => $elektronik->id,
        'name' => $p['name'],
        'description' => 'Deskripsi ' . $p['name'],
        'price' => $p['price'],
        'stock' => $p['stock']
    ]);
}
```

---

## One-Liner untuk Bulk Create Semua Products

```php
// Jalankan di tinker
$store = App\Models\Store::first();
$categories = App\Models\Category::where('is_active', true)->get();

$allProducts = [
    'makanan' => [
        ['name' => 'Nasi Goreng', 'price' => 20000, 'stock' => 30],
        ['name' => 'Mie Goreng', 'price' => 18000, 'stock' => 25],
        ['name' => 'Ayam Geprek', 'price' => 22000, 'stock' => 20],
    ],
    'minuman' => [
        ['name' => 'Es Teh Manis', 'price' => 5000, 'stock' => 100],
        ['name' => 'Es Jeruk', 'price' => 7000, 'stock' => 80],
        ['name' => 'Kopi Hitam', 'price' => 10000, 'stock' => 50],
    ],
    'elektronik' => [
        ['name' => 'Kabel USB', 'price' => 35000, 'stock' => 50],
        ['name' => 'Powerbank', 'price' => 120000, 'stock' => 20],
        ['name' => 'Earphone', 'price' => 150000, 'stock' => 15],
    ],
];

foreach ($allProducts as $slug => $products) {
    $category = $categories->firstWhere('slug', $slug);
    if ($category) {
        foreach ($products as $p) {
            App\Models\Product::create([
                'store_id' => $store->id,
                'category_id' => $category->id,
                'name' => $p['name'],
                'description' => 'Deskripsi ' . $p['name'],
                'price' => $p['price'],
                'stock' => $p['stock']
            ]);
        }
    }
}
echo "Berhasil membuat products!";
```

---

## Utility Commands

### Hitung Products per Kategori
```php
App\Models\Category::withCount('products')->get()->map(function($cat) {
    return $cat->name . ': ' . $cat->products_count . ' products';
});
```

### Update Stock Produk
```php
$product = App\Models\Product::find(1);
$product->update(['stock' => 100]);
```

### Delete Products Tanpa Kategori
```php
App\Models\Product::whereNull('category_id')->delete();
```

### Assign Category ke Products yang belum punya
```php
$category = App\Models\Category::first();
App\Models\Product::whereNull('category_id')->update(['category_id' => $category->id]);
```
