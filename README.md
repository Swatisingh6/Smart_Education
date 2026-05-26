# Smart Education & Dropout Analytics Portal (EduKeep)

Welcome to the **Smart Education Portal (EduKeep)**, a state-of-the-art production-grade government analytics platform designed to analyze student dropout data, audit administrative activities, and simulate policy interventions to retain vulnerable student demographics.

Built on **Laravel 11**, this application features clean MVC architecture, bilingual support, responsive glassmorphic interfaces, live interactive dashboards powered by **Chart.js**, printable official dossiers, and audit trail timelines.

---

## 1. System Architecture

The application is structured around a classic MVC design patterns enhanced with localized translations and predictive SQL aggregation tools.

```mermaid
graph TD
    %% Define Styles
    classDef client fill:#1e293b,stroke:#334155,stroke-width:2px,color:#cbd5e1;
    classDef route fill:#0f172a,stroke:#6366f1,stroke-width:2px,color:#a5b4fc;
    classDef controller fill:#111827,stroke:#2dd4bf,stroke-width:2px,color:#2dd4bf;
    classDef model fill:#0f172a,stroke:#ec4899,stroke-width:2px,color:#fbcfe8;
    classDef database fill:#020617,stroke:#e11d48,stroke-width:2px,color:#fda4af;

    %% Components
    A[Web Browser / Client] -->|HTTP Request / AJAX| B[routes/web.php]
    B -->|Route Mapping| C[DropoutController]
    B -->|Route Mapping| D[AuthController]

    subgraph "MVC Business Logic"
        C -->|Aggregates Stats / Exports| E[Student Model]
        C -->|Vulnerability Diagnostics| F[School Model]
        C -->|Deploy Policies| G[Intervention Model]
        
        D -->|Logs Actions| H[ActivityLog Model]
        D -->|Update Credentials| I[User Model]
    end

    E & F & G & H & I -->|Eloquent ORM| J[(SQLite / MySQL Database)]

    C -->|Sends JSON & Data Arrays| K[Blade Views + Chart.js]
    K -->|HTML / Tailwind UI rendering| A

    %% Apply Styles
    class A,K client;
    class B route;
    class C,D controller;
    class E,F,G,H,I model;
    class J database;
```

---

## 2. Entity-Relationship (ER) Diagram

The system employs five highly integrated database entities. Administrative activity logging tracks every data modification securely.

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email
        string password
        string remember_token
        timestamp created_at
        timestamp updated_at
    }
    
    SCHOOLS {
        bigint id PK
        string name
        string type "Government / Private / Semi-Government"
        string area_type "Urban / Rural"
        string district
        string pincode
        timestamp created_at
        timestamp updated_at
    }
    
    STUDENTS {
        bigint id PK
        bigint school_id FK
        string name
        string gender "Male / Female / Transgender"
        string caste "General / OBC / SC / ST"
        date date_of_birth
        integer standard "1 to 12"
        string status "Enrolled / Dropped Out"
        string dropout_reason "Poverty / Labor / Distance / etc."
        date dropout_date
        string area_village_city
        decimal parent_income "Annual"
        string academic_year "e.g. 2025-2026"
        timestamp created_at
        timestamp updated_at
    }
    
    INTERVENTIONS {
        bigint id PK
        string name
        string target_type "School / Area / Gender / Caste / Standard / All"
        string target_value
        string type "Meal / Transport / Scholarship / Counseling / Infrastructure"
        text description
        decimal budget_allocated
        string status "Planned / Active / Completed"
        integer expected_reduction_rate
        timestamp created_at
        timestamp updated_at
    }
    
    ACTIVITY_LOGS {
        bigint id PK
        bigint user_id FK
        string action "e.g. login, delete_student, store_school"
        text description
        string ip_address
        timestamp created_at
        timestamp updated_at
    }

    SCHOOLS ||--o{ STUDENTS : "enrolls"
    USERS ||--o{ ACTIVITY_LOGS : "audits"
```

---

## 3. Core Administrative Flowchart

Administrators interact with three core workspaces: **Dashboard View** (dynamic filter graphs), **Reports Center** (dossier printing & CSV exporting), and **CRUD Database Manager**.

```mermaid
flowchart TD
    classDef start fill:#0d9488,stroke:#0d9488,color:white;
    classDef decision fill:#4f46e5,stroke:#4f46e5,color:white;
    classDef process fill:#1e293b,stroke:#334155,color:#cbd5e1;

    A([Start: Logged in Administrator]) --> B{Select Navigation Workspace}:::decision
    
    %% Dashboard Flow
    B -->|Main Overview| C[Interactive Dashboard]:::process
    C --> D[Select Filters: Caste, Gender, Sector, Year]:::process
    D --> E[Re-render Chart.js & Recalculate AI Predictions]:::process
    
    %% Database Control Flow
    B -->|Registry Controls| F[Database Manager]:::process
    F --> G{Select Operation}:::decision
    G -->|Create School / Student| H[Validate Inputs & Store in Database]:::process
    G -->|Edit Modal Row| I[Populate Overlay Forms & Submit Update]:::process
    G -->|Delete Row| J[Prompt Cascading Warn Confirmation & DELETE]:::process
    H & I & J --> K[Create activity_logs Audit Record]:::process
    K --> F
    
    %% Reports Flow
    B -->|Official Dossier| L[Reports Central]:::process
    L --> M{Select Export Target}:::decision
    M -->|Physical Hardcopy| N[Invokes window.print CSS stylesheet overrides]:::process
    M -->|Excel Spreadsheet| O[Streams filtered rows directly to CSV file]:::process
    N & O --> L
    
    class A start;
    class B,G,M decision;
    class C,D,E,F,H,I,J,K,L,N,O process;
```

---

## 4. Complete Database Schema Details

### 4.1 Users Table (`users`)
- `id` (bigint, Primary Key, Auto-Increment)
- `name` (string)
- `email` (string, Unique)
- `password` (string)
- `remember_token` (string, Nullable)
- `timestamps` (`created_at`, `updated_at`)

### 4.2 Schools Table (`schools`)
- `id` (bigint, Primary Key, Auto-Increment)
- `name` (string): School Name
- `type` (string): Sector ("Government", "Private", "Semi-Government")
- `area_type` (string): Regional classification ("Urban", "Rural")
- `district` (string): District location
- `pincode` (string): 6-digit PIN
- `timestamps` (`created_at`, `updated_at`)

### 4.3 Students Table (`students`)
- `id` (bigint, Primary Key, Auto-Increment)
- `school_id` (bigint, Foreign Key constrained to `schools.id` cascading on delete)
- `name` (string): Student Name
- `gender` (string): ("Male", "Female", "Transgender")
- `caste` (string): ("General", "OBC", "SC", "ST")
- `date_of_birth` (date)
- `standard` (integer): Class level (1 to 12)
- `status` (string): ("Enrolled", "Dropped Out")
- `dropout_reason` (string, Nullable): Poverty, Commute Distance, Child Labor, etc.
- `dropout_date` (date, Nullable)
- `area_village_city` (string): Student Village / Hamlet
- `parent_income` (decimal, 10, 2): Annual parental income
- `academic_year` (string): ("2023-2024", "2024-2025", "2025-2026")
- `timestamps` (`created_at`, `updated_at`)

### 4.4 Interventions Table (`interventions`)
- `id` (bigint, Primary Key, Auto-Increment)
- `name` (string): Scheme Name
- `target_type` (string): target category ("School", "Area", "Gender", "Caste", "Standard", "All")
- `target_value` (string, Nullable): value identifier (e.g. "Female", "SC")
- `type` (string): scheme classification ("Meal", "Transport", "Scholarship", "Counseling", "Infrastructure")
- `description` (text, Nullable)
- `budget_allocated` (decimal, 15, 2)
- `status` (string): ("Planned", "Active", "Completed")
- `expected_reduction_rate` (integer): Percentage efficacy
- `timestamps` (`created_at`, `updated_at`)

### 4.5 Activity Logs Table (`activity_logs`)
- `id` (bigint, Primary Key, Auto-Increment)
- `user_id` (bigint, Foreign Key constrained to `users.id` setting null on delete, Nullable)
- `action` (string): ("login", "logout", "delete_student", "store_school", "profile_update", etc.)
- `description` (text)
- `ip_address` (string, Nullable)
- `timestamps` (`created_at`, `updated_at`)

---

## 5. Installation & Setup Instructions

### 5.1 System Prerequisites
Ensure you have the following installed on your machine:
- PHP version `>= 8.3`
- Composer package manager
- Node.js & NPM

---

### 5.2 Option A: Standard SQLite Setup (Recommended / Fastest)
SQLite is configured by default for zero-setup local execution.

1. **Clone the repository and enter the directory**:
   ```bash
   cd c:\Users\swati\Desktop\SmartEducation\caproject
   ```

2. **Install composer and front-end dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Verify and Configure Environment (`.env`)**:
   Ensure `.env` contains the SQLite connection settings:
   ```env
   DB_CONNECTION=sqlite
   ```
   *(Note: The SQLite database file will automatically initialize under `database/database.sqlite`).*

4. **Run Database Migrations & Rich Mock Seeding**:
   This runs our customized migrations (adding the new demographic fields and activity log tables) and seeds 1,600 heavily weighted socio-economic student records:
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Build Front-end Assets**:
   ```bash
   npm run build
   ```

6. **Serve the Application**:
   Start the Laravel local server:
   ```bash
   php artisan serve
   ```
   Open your browser and navigate to `http://127.0.0.1:8000`. 
   - **Log in credentials**: `admin@edukeep.gov.in`
   - **Password**: `password123`

---

### 5.3 Option B: MySQL Database Setup (Alternative)
To connect the application to a local or production MySQL server:

1. **Create Database**:
   Log into your MySQL server and create a blank database:
   ```sql
   CREATE DATABASE smart_education CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Modify Environment File (`.env`)**:
   Open `.env` and adjust the database configurations:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=smart_education
   DB_USERNAME=your_mysql_username
   DB_PASSWORD=your_mysql_password
   ```

3. **Run Composer and NPM installs** (if not already done):
   ```bash
   composer install
   npm install
   ```

4. **Run Fresh Migrations & Seeding**:
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Build and Serve**:
   ```bash
   npm run build
   php artisan serve
   ```
   Navigate to the local serve port (`http://127.0.0.1:8000`) and sign in using the seed details (`admin@edukeep.gov.in` / `password123`).
