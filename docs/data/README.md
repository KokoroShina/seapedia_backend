# Data Dummy untuk Testing SEAPEDIA

## Cara Pakai

### 1. Jalankan Tinker
```bash
php artisan tinker
```

### 2. Copy-paste isi file `dummy-seeder.php` ke terminal tinker

### 3. Jalankan semua command

Atau cara alternatif:
```bash
php artisan tinker < docs/data/dummy-seeder.php
```

---

## Credential Testing untuk Postman

### Admin
|  Field   |      Value         |
|----------|--------------------|
| Email    | admin@seapedia.com |
| Password | password123        |
| Role     | admin              |

### Sellers
| Email | Password | Role |
|-------|----------|------|
| seller@techmart.com | password123 | seller |
| seller@fashionhub.com | password123 | seller |
| buyer@johndoe.com | password123 | seller (multi-role) |

### Buyers
| Email | Password | Role |
|-------|----------|------|
| buyer@johndoe.com | password123 | buyer |
| buyer@janedoe.com | password123 | buyer |

### Drivers
| Email | Password | Role |
|-------|----------|------|
| driver@budi.com | password123 | driver |
| driver@andi.com | password123 | driver |

---

## Voucher untuk Testing

| Code | Discount | Status |
|------|----------|--------|
| DISKON10 | 10% | ✅ Aktif |
| HEMAT20 | 20% | ✅ Aktif |
| PROMO25 | 25% | ❌ Habis (used_count = max_usage) |
| EXPIRED2024 | 15% | ❌ Expired |

---

## Promo Otomatis (Tanpa Kode)

| ID | Discount | Min. Purchase |
|----|----------|--------------|
| 1 | 5% | Rp 100.000 |
| 2 | 10% | Rp 250.000 |
| 3 | 15% | Rp 500.000 |
| 4 | 20% | Rp 1.000.000 |

> Promo otomatis aktif saat subtotal >= min_purchase

---

## Struktur Data

### Users (7 users)
- 1 Admin
- 3 Sellers (2 pure sellers + 1 multi-role buyer)
- 2 Buyers (1 multi-role + 1 pure buyer)
- 2 Drivers

### Stores (3 stores)
- TechMart Electronics (user_id: 2)
- FashionHub Indonesia (user_id: 3)
- John Gadget Store (user_id: 4)

### Products (8 products)
- Store 1: 3 products (earbuds, smartwatch, powerbank)
- Store 2: 3 products (kaos, hoodie, jeans)
- Store 3: 2 products (phone case, cable)

### Carts (2 carts)
- Cart 1: buyer_johndoe - 2 items dari TechMart
- Cart 2: buyer_janedoe - 2 items dari FashionHub

### Wallets (7 wallets)
- Admin: Rp 10.000.000
- Seller 1: Rp 5.000.000
- Seller 2: Rp 3.000.000
- Buyer+Seller (John): Rp 500.000
- Buyer (Jane): Rp 750.000
- Driver 1 (Budi): Rp 200.000
- Driver 2 (Andi): Rp 150.000

---

## Testing Flow

### BUYER

### 1. Login sebagai Buyer
```
POST /api/auth/login
{
  "email": "buyer@johndoe.com",
  "password": "password123"
}
```

### 2. Lihat Cart
```
GET /api/cart
Authorization: Bearer {token}
```

### 3. Checkout (tanpa voucher)
```
POST /api/checkout
Authorization: Bearer {token}
{
  "address_id": 1,
  "delivery_method": "instant"
}
```

### 4. Checkout (dengan voucher)
```
POST /api/checkout
Authorization: Bearer {token}
{
  "address_id": 1,
  "delivery_method": "instant",
  "voucher_code": "DISKON10"
}
```


### SELLLER


### 5. Login sebagai Seller
```
POST /api/auth/login
{
  "email": "seller@techmart.com",
  "password": "password123"
}

POST /api/auth/switch-role
{
  "role": "seller"
}
```

### 6. Process Order
```
PUT /api/seller/orders/{order_id}/process
Authorization: Bearer {token}
```

### 7. Login sebagai Driver
```
POST /api/auth/login
{
  "email": "driver@budi.com",
  "password": "password123"
}
```

### 8. Driver ambil job
```
GET /api/driver/deliveries
PUT /api/driver/deliveries/{id}/take
PUT /api/driver/deliveries/{id}/complete
```
