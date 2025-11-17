# Health & Digital Nutrition - Backend API

Backend RESTful API untuk aplikasi "Health & Digital Nutrition" menggunakan Laravel dan MySQL.

## Fitur Utama

1. **Autentikasi & Manajemen Pengguna** (Laravel Sanctum)
   - Register
   - Login
   - Logout
   - Get User Profile

2. **Profil Kesehatan Pengguna**
   - Create/Update profil kesehatan
   - Data: Tanggal Lahir, Jenis Kelamin, Berat Badan, Tinggi Badan, Tingkat Aktivitas, Tujuan

3. **Database Makanan**
   - Pencarian makanan berdasarkan nama
   - Data nutrisi lengkap (kalori, protein, karbohidrat, lemak, serat)

4. **Pencatatan Makanan Harian**
   - Tambah log makanan
   - Lihat log berdasarkan tanggal
   - Hapus log makanan

5. **Manajemen Resep**
   - CRUD lengkap untuk resep
   - Manajemen bahan-bahan resep

6. **Dashboard & Analisis**
   - Ringkasan nutrisi harian
   - Perhitungan target kalori (Harris-Benedict)
   - Pengelompokan makanan berdasarkan waktu makan

## Instalasi

1. Install dependencies:
```bash
composer install
```

2. Copy file environment:
```bash
cp .env.example .env
```

3. Generate application key:
```bash
php artisan key:generate
```

4. Konfigurasi database di `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=health_nutrition
DB_USERNAME=root
DB_PASSWORD=
```

5. Jalankan migrasi:
```bash
php artisan migrate
```

6. Jalankan server:
```bash
php artisan serve
```

## API Endpoints

### Autentikasi

- `POST /api/register` - Register user baru
- `POST /api/login` - Login user
- `POST /api/logout` - Logout (memerlukan auth)
- `GET /api/user` - Get user profile (memerlukan auth)

### Health Profile

- `POST /api/health-profiles` - Create/Update health profile (memerlukan auth)
- `PUT /api/health-profiles/{id}` - Update health profile (memerlukan auth)
- `GET /api/health-profiles/{id}` - Get health profile (memerlukan auth)

### Foods

- `GET /api/foods?search={nama}` - Search foods (memerlukan auth)

### Food Logs

- `POST /api/logs` - Tambah food log (memerlukan auth)
- `GET /api/logs/{date}` - Get food logs by date (memerlukan auth)
- `GET /api/logs` - Get all food logs (paginated) (memerlukan auth)
- `DELETE /api/logs/{id}` - Hapus food log (memerlukan auth)

### Recipes

- `GET /api/recipes` - Get all recipes (memerlukan auth)
- `POST /api/recipes` - Create recipe (memerlukan auth)
- `GET /api/recipes/{id}` - Get recipe (memerlukan auth)
- `PUT /api/recipes/{id}` - Update recipe (memerlukan auth)
- `DELETE /api/recipes/{id}` - Delete recipe (memerlukan auth)
- `POST /api/recipes/{id}/ingredients` - Add ingredient (memerlukan auth)
- `DELETE /api/recipes/{id}/ingredients/{ingredient_id}` - Remove ingredient (memerlukan auth)

### Dashboard

- `GET /api/dashboard/{date}` - Get dashboard summary (memerlukan auth)

## Struktur Database

### Tabel: users
- id, name, email, password, timestamps

### Tabel: health_profiles
- id, user_id, date_of_birth, gender, weight_kg, height_cm, activity_level, goal, timestamps

### Tabel: foods
- id, name, serving_size_grams, calories, protein, carbs, fats, fiber, timestamps

### Tabel: food_logs
- id, user_id, food_id, quantity_grams, meal_type, log_date, timestamps

### Tabel: recipes
- id, user_id, name, description, instructions, timestamps

### Tabel: recipe_ingredients
- id, recipe_id, food_id, quantity_grams, timestamps

## Model & Relasi

- **User** hasOne **HealthProfile**
- **User** hasMany **FoodLog**
- **User** hasMany **Recipe**
- **FoodLog** belongsTo **User** dan **Food**
- **Recipe** belongsTo **User** dan hasMany **RecipeIngredient**
- **RecipeIngredient** belongsTo **Recipe** dan **Food**

## Perhitungan Target Kalori

Sistem menggunakan rumus **Harris-Benedict** untuk menghitung BMR (Basal Metabolic Rate), kemudian dikalikan dengan faktor aktivitas dan disesuaikan dengan tujuan pengguna:

- **Lose Weight**: TDEE - 500 kalori
- **Maintain**: TDEE
- **Gain Weight**: TDEE + 500 kalori

## Teknologi

- Laravel 12.x
- Laravel Sanctum (API Authentication)
- MySQL
- PHP 8.2+

## Catatan

- Semua endpoint yang memerlukan autentikasi harus menyertakan header: `Authorization: Bearer {token}`
- Token didapatkan saat login atau register
- Format tanggal: `Y-m-d` (contoh: 2024-01-15)
