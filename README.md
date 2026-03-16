# Notes App API

A RESTful API built with Laravel 12 for managing personal notes. Supports authentication via Google OAuth and manual email/password registration, with JWT-based authorization.

---

## Features

- Google OAuth 2.0 login & register (via Laravel Socialite)
- Manual register & login with email verification
- Forgot & reset password via email
- JWT authentication (via tymon/jwt-auth)
- Full CRUD for personal notes
- UUID primary keys
- Standardized JSON responses
- Feature tests with PHPUnit

---

## Tech Stack

- **Framework**: Laravel 12
- **Auth**: JWT (tymon/jwt-auth) + Google OAuth (laravel/socialite)
- **Database**: MySQL
- **Testing**: PHPUnit

---

## Requirements

- PHP >= 8.2
- Composer
- MySQL
- Google OAuth credentials

---

## Installation & Setup

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

## Google OAuth Setup

1. Go to [console.cloud.google.com](https://console.cloud.google.com)
2. Create a new project
3. Enable **Google People API**
4. Go to **APIs & Services** → **OAuth consent screen** → configure
5. Go to **Credentials** → **Create Credentials** → **OAuth 2.0 Client IDs**
6. Set **Authorized redirect URIs** to `http://localhost:8000/api/auth/google/callback`
7. Copy **Client ID** and **Client Secret** to `.env`

---

## API Endpoints

### Auth

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `GET` | `/api/auth/google/redirect` | Get Google OAuth redirect URL | No |
| `GET` | `/api/auth/google/callback` | Handle Google OAuth callback | No |
| `POST` | `/api/auth/register` | Register with email & password | No |
| `POST` | `/api/auth/login` | Login with email & password | No |
| `POST` | `/api/auth/logout` | Logout | Yes |
| `POST` | `/api/auth/refresh` | Refresh JWT token | Yes |
| `POST` | `/api/auth/forgot-password` | Send password reset link | No |
| `POST` | `/api/auth/reset-password` | Reset password with token | No |
| `GET` | `/api/auth/verify-email/{id}/{hash}` | Verify email address | No |
| `POST` | `/api/auth/resend-verification` | Resend verification email | No |

### Notes

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `GET` | `/api/notes` | Get all notes (current user) | Yes |
| `POST` | `/api/notes` | Create a new note | Yes |
| `GET` | `/api/notes/{id}` | Get a single note | Yes |
| `PUT/PATCH` | `/api/notes/{id}` | Update a note | Yes |
| `DELETE` | `/api/notes/{id}` | Delete a note | Yes |

### Request & Response Examples

**Register**
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

**Login**
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

**Create Note**
```http
POST /api/notes
Authorization: Bearer {token}
Content-Type: application/json

{
    "note_title": "My First Note",
    "note_content": "This is the content of my note."
}
```

```json
{
    "success": true,
    "code": 201,
    "message": "Note created successfully",
    "data": {
        "id": "019cf4d0-c324-7067-85dd-cfa8e926d8d2",
        "note_title": "My First Note",
        "note_content": "This is the content of my note.",
        "created_by": "019cea71-d973-73d3-9c20-d2bea6a6826f",
        "created_at": "2026-03-16T05:00:00.000000Z",
        "updated_at": "2026-03-16T05:00:00.000000Z"
    }
}
```

### Standard Error Responses

| Code | Message | Description |
|------|---------|-------------|
| `400` | Bad Request | Invalid request parameters |
| `401` | Unauthenticated | Missing or invalid token |
| `403` | Forbidden | Access denied to resource |
| `404` | Resource not found | Resource does not exist |
| `422` | Validation Error | Request validation failed |
| `500` | Internal Server Error | Server error |

---

## Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter GoogleAuthTest
php artisan test --filter AuthTest
php artisan test --filter NoteTest
php artisan test --filter EmailVerificationTest
php artisan test --filter ForgotResetPasswordTest
```

### Test Coverage

| Test File | Coverage |
|-----------|----------|
| `GoogleAuthTest` | Google OAuth register, login, update google_id, auth failure |
| `AuthTest` | Register, login, logout (manual) |
| `NoteTest` | CRUD notes, authorization checks |
| `EmailVerificationTest` | Verify email, resend verification |
| `ForgotResetPasswordTest` | Forgot password, reset password |

---

## Local Development

For testing without going through Google OAuth, use the test token endpoint (only available in `local` environment):

```http
GET /api/auth/test-token        → token for first user
GET /api/auth/test-token/1      → token for second user
GET /api/auth/test-token/2      → token for third user
```

> ⚠️ This endpoint is automatically disabled in production.
