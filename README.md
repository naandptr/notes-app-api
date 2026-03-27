# 🎀 Notes App API 🎀

> RESTful API for managing personal notes with authentication, tagging, and search functionality. Built using Laravel 12.

---

## 🌸 Features

- Authentication (JWT)
- Google OAuth 2.0 login (Laravel Socialite)
- Email verification & password reset
- Full CRUD for notes
- Tagging system (many-to-many per user)
- Search notes (title & content)
- Pagination with configurable page size
- UUID as primary keys
- Standardized JSON responses
- Feature testing with PHPUnit

---

## 🩰 Tech Stack

| | |
|---|---|
| **Framework** | Laravel 12 |
| **Auth** | JWT + Google OAuth |
| **Database** | MySQL |
| **Testing** | PHPUnit |

---

## 🎀 Requirements

- PHP >= 8.2
- Composer
- MySQL
- Google OAuth Credentials

---

## 🌷 Installation & Setup

### 1. Clone repository

```bash
git clone https://github.com/naandptr/notes-app-api.git
cd notes-app-api
```

### 2. Install dependencies

```bash
composer install
```

### 3. Copy environment file

```bash
cp .env.example .env
```

### 4. Generate app key

```bash
php artisan key:generate
```

### 5. Generate JWT secret

```bash
php artisan jwt:secret
```

### 6. Configure `.env` 

```env
APP_NAME="Notes App"
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_notes_app
DB_USERNAME=root
DB_PASSWORD=

GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_FROM_ADDRESS=noreply@notesapp.com
MAIL_FROM_NAME="Notes App"
```

### 7. Run migrations

```bash
php artisan migrate
```

### 8. Start server 

```bash
php artisan serve
```

---

## 🌹 Google OAuth Setup

1. Go to [console.cloud.google.com](https://console.cloud.google.com) 
2. Create a new project 
3. Enable **google people api** 
4. Go to **apis & services** → **oauth consent screen** → configure 
5. Go to **credentials** → **create credentials** → **oauth 2.0 client ids** 🔑
6. Set **authorized redirect uris** to `http://localhost:8000/api/auth/google/callback`
7. Copy **client id** and **client secret** to `.env` 

---

## 🎀 API Endpoints

### Auth

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/auth/google/redirect` | Get google oauth redirect url | ✗ |
| `GET` | `/api/auth/google/callback` | Handle google oauth callback | ✗ |
| `POST` | `/api/auth/register` | Register with email & password | ✗ |
| `POST` | `/api/auth/login` | Login with email & password | ✗ |
| `POST` | `/api/auth/logout` | Logout | ✓ |
| `POST` | `/api/auth/refresh` | Refresh jwt token | ✓ |
| `POST` | `/api/auth/change-password` | Change password | ✓ |
| `POST` | `/api/auth/forgot-password` | Send password reset link | ✗ |
| `POST` | `/api/auth/reset-password` | Reset password with token | ✗ |
| `GET` | `/api/auth/verify-email/{id}/{hash}` | Verify email address | ✗ |
| `POST` | `/api/auth/resend-verification` | Resend verification email | ✗ |

> ⚠️ Change password is not available for google oauth users without a password

### Notes

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/notes` | Get all notes | ✓ |
| `POST` | `/api/notes` | Create a new note | ✓ |
| `GET` | `/api/notes/{id}` | Get a single note | ✓ |
| `PUT/PATCH` | `/api/notes/{id}` | Update a note | ✓ |
| `DELETE` | `/api/notes/{id}` | Delete a note | ✓ |

#### Query parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `page` | integer | Page number | `?page=1` |
| `per_page` | integer | Items per page (default: 10) | `?per_page=5` |
| `search` | string | Search by title or content | `?search=laravel` |
| `tag_id` | uuid | Filter by tag | `?tag_id=019cf4d0-...` |

### Tags

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/tags` | Get all tags | ✓ |
| `POST` | `/api/tags` | Create a new tag | ✓ |
| `DELETE` | `/api/tags/{id}` | Delete a tag | ✓ |

---

## 💅 Request & Response Examples

### Register 
```http
POST /api/auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

```json
{
    "success": true,
    "code": 201,
    "message": "Registration successful. Please check your email to verify your account.",
    "data": null
}
```

### Login 
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```

```json
{
    "success": true,
    "code": 200,
    "message": "User retrieved successfully",
    "data": {
        "token": "eyJ0eXAiOiJKV1Qi...",
        "type": "bearer",
        "expires_in": 3600,
        "user": {
            "id": "019cea71-d973-73d3-9c20-d2bea6a6826f",
            "name": "John Doe",
            "email": "john@example.com"
        }
    }
}
```

### Change password 
```http
POST /api/auth/change-password
Authorization: Bearer {token}
Content-Type: application/json

{
    "current_password": "password123",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

```json
{
    "success": true,
    "code": 200,
    "message": "Password changed successfully",
    "data": null
}
```

### Create tag 
```http
POST /api/tags
Authorization: Bearer {token}
Content-Type: application/json

{
    "tag_name": "Laravel"
}
```

```json
{
    "success": true,
    "code": 201,
    "message": "Tag created successfully",
    "data": {
        "id": "019cf4d0-c324-7067-85dd-cfa8e926d8d2",
        "tag_name": "Laravel",
        "created_by": "019cea71-d973-73d3-9c20-d2bea6a6826f"
    }
}
```

### Create note 
```http
POST /api/notes
Authorization: Bearer {token}
Content-Type: application/json

{
    "note_title": "My First Note",
    "note_content": "This is the content of my note.",
    "tag_ids": [
        "019cf4d0-c324-7067-85dd-cfa8e926d8d2"
    ]
}
```

```json
{
    "success": true,
    "code": 201,
    "message": "Note created successfully",
    "data": {
        "id": "019cf4d0-c324-7067-85dd-cfa8e926d8d3",
        "note_title": "My First Note",
        "note_content": "This is the content of my note.",
        "created_by": "019cea71-d973-73d3-9c20-d2bea6a6826f",
        "tags": [
            {
                "id": "019cf4d0-c324-7067-85dd-cfa8e926d8d2",
                "tag_name": "Laravel"
            }
        ]
    }
}
```

### Get notes 
```http
GET /api/notes?page=1&per_page=10&search=laravel&tag_id=019cf4d0-...
Authorization: Bearer {token}
```

```json
{
    "success": true,
    "code": 200,
    "message": "Note retrieved successfully",
    "data": [...],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 25,
        "last_page": 3
    }
}
```

### Update note 
```http
PUT /api/notes/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "note_title": "Updated Title",
    "note_content": "Updated content.",
    "tag_ids": []
}
```

---

## 🚨 Standard Error Responses

| Code | Message | Description |
|------|---------|-------------|
| `400` | Bad Request | invalid request parameters |
| `401` | Unauthenticated | missing or invalid token |
| `403` | Forbidden | access denied to resource |
| `404` | Resource not found | resource does not exist |
| `422` | Validation Error | request validation failed |
| `500` | Internal Server Error | server error |

---

## 🧪 Running Tests

```bash
# run all tests 
php artisan test

# run specific test file
php artisan test --filter GoogleAuthTest
php artisan test --filter AuthTest
php artisan test --filter NoteTest
php artisan test --filter TagTest
php artisan test --filter EmailVerificationTest
php artisan test --filter ForgotResetPasswordTest
```

### Test coverage

| Test File | Coverage |
|-----------|----------|
| `GoogleAuthTest` | Google OAuth register, login, update google_id, auth failure |
| `AuthTest` | Register, login, logout, change password (manual) |
| `NoteTest` | CRUD notes, search, pagination, filter by tag, authorization checks |
| `TagTest` | CRUD tags, tag & note relation, authorization checks |
| `EmailVerificationTest` | Verify email, resend verification |
| `ForgotResetPasswordTest` | Forgot password, reset password |

---

*This project focuses on building a clean and scalable backend structure, including authentication, relational data handling, and API design.*
