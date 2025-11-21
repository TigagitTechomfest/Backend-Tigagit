# Tigagit Backend API

Backend RESTful API untuk platform Tigagit menggunakan Laravel dan PostgreSQL.

## Fitur Utama

1.  **Autentikasi System** (JWT)
    -   Register, Login, Logout, Refresh Token
2.  **Health Assessment Module**
    -   Onboarding user (Age, Gender, Height, Weight, Activity, Goal)
    -   Auto-calculate BMI & Daily Targets (Calories, Macros)
3.  **Food Database Management**
    -   Database makanan Indonesia (100+ items)
    -   Search & Filter
4.  **Daily Food Tracking**
    -   Log meal (Breakfast, Lunch, Dinner, Snack)
    -   Auto-calculate nutrition based on portion
5.  **AI Feedback Engine**
    -   Contextual feedback per meal & daily
    -   Macro analysis & suggestions
6.  **Progress Dashboard**
    -   Real-time daily progress
    -   Weekly analytics (Consistency, Trends)
7.  **Exercise Logging**
    -   Log exercises & calories burned
8.  **Health History**
    -   Track weight & BMI over time

## Instalasi

1.  **Prerequisites**: PHP 8.2+, Composer, PostgreSQL.

2.  **Install Dependencies**:
    ```bash
    composer install
    ```

3.  **Environment Setup**:
    Copy `.env.example` ke `.env` dan konfigurasi database PostgreSQL:
    ```env
    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=tigagit
    DB_USERNAME=postgres
    DB_PASSWORD=yourpassword
    ```

4.  **Generate Key & JWT Secret**:
    ```bash
    php artisan key:generate
    php artisan jwt:secret
    ```

5.  **Run Migrations & Seeder**:
    ```bash
    php artisan migrate
    php artisan db:seed --class=FoodDatabaseSeeder
    ```

6.  **Run Server**:
    ```bash
    php artisan serve
    ```

## API Endpoints

### Authentication
-   `POST /api/auth/register`
-   `POST /api/auth/login`
-   `POST /api/auth/logout`
-   `POST /api/auth/refresh`
-   `GET /api/auth/me`

### Health Assessment
-   `POST /api/assessment` - Save assessment
-   `GET /api/assessment` - Get current assessment
-   `POST /api/assessment/weight` - Update weight (logs history)

### Food Database
-   `GET /api/foods?search={name}&category={cat}`
-   `POST /api/foods` - Add new food

### Daily Logs
-   `GET /api/daily-logs?date=YYYY-MM-DD`
-   `POST /api/daily-logs/meal` - Add meal entry

### AI Feedback
-   `POST /api/feedback/daily` - Generate daily feedback

### Progress & Analytics
-   `GET /api/progress/daily?date=YYYY-MM-DD`
-   `GET /api/progress/weekly?date=YYYY-MM-DD`

### Exercise
-   `POST /api/exercises` - Log exercise

## Teknologi
-   Laravel 12.x
-   PostgreSQL (JSONB support)
-   JWT Auth (php-open-source-saver/jwt-auth)
