# Laravel Sanctum Authentication API Documentation

## Installation & Setup Completed

### 1. Composer Installation Commands

```bash
# Install Laravel Sanctum
composer require laravel/sanctum

# Publish Sanctum configuration and migrations
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Run migrations
php artisan migrate
```

---

## 2. Environment Configuration

Update your `.env` file (no additional configuration needed, Sanctum auto-detects):

```env
# CORS is already configured for localhost:3000 by default
# For production, update the SANCTUM_STATEFUL_DOMAINS in .env:
SANCTUM_STATEFUL_DOMAINS=localhost:3000,yourdomain.com
```

---

## 3. API Endpoints

### Authentication Endpoints (Public)

#### POST `/api/register`
Register a new user

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response (201 Created):**
```json
{
  "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

**Error Response (422 Validation):**
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password confirmation does not match."]
  }
}
```

---

#### POST `/api/login`
Login user and get authentication token

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200 OK):**
```json
{
  "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Invalid credentials"
}
```

**Error Response (422 Validation):**
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

---

### Protected Endpoints (Require Authentication)

All protected endpoints require the `Authorization` header:

```
Authorization: Bearer YOUR_API_TOKEN
```

---

#### GET `/api/user`
Get the authenticated user's information

**Request Headers:**
```
Authorization: Bearer 1|AbCdEfGhIjKlMnOpQrStUvWxYz...
```

**Response (200 OK):**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com"
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

---

#### POST `/api/logout`
Logout user (revokes the current token)

**Request Headers:**
```
Authorization: Bearer 1|AbCdEfGhIjKlMnOpQrStUvWxYz...
```

**Response (200 OK):**
```json
{
  "message": "Logout successful"
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

---

## 4. Frontend Implementation (React)

### Setup Authentication Context

```javascript
import React, { createContext, useState, useEffect } from 'react';

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem('authToken'));
  const [loading, setLoading] = useState(false);

  const API_URL = 'http://localhost:8000/api';

  // Register
  const register = async (name, email, password, passwordConfirmation) => {
    setLoading(true);
    try {
      const response = await fetch(`${API_URL}/register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name,
          email,
          password,
          password_confirmation: passwordConfirmation,
        }),
      });
      const data = await response.json();
      if (response.ok) {
        localStorage.setItem('authToken', data.token);
        setToken(data.token);
        setUser(data.user);
      }
      return data;
    } catch (error) {
      console.error('Registration error:', error);
      throw error;
    } finally {
      setLoading(false);
    }
  };

  // Login
  const login = async (email, password) => {
    setLoading(true);
    try {
      const response = await fetch(`${API_URL}/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      });
      const data = await response.json();
      if (response.ok) {
        localStorage.setItem('authToken', data.token);
        setToken(data.token);
        setUser(data.user);
      }
      return data;
    } catch (error) {
      console.error('Login error:', error);
      throw error;
    } finally {
      setLoading(false);
    }
  };

  // Get Current User
  const getCurrentUser = async () => {
    if (!token) return;
    try {
      const response = await fetch(`${API_URL}/user`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      if (response.ok) {
        const userData = await response.json();
        setUser(userData);
      } else if (response.status === 401) {
        // Token expired or invalid
        localStorage.removeItem('authToken');
        setToken(null);
        setUser(null);
      }
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  // Logout
  const logout = async () => {
    setLoading(true);
    try {
      await fetch(`${API_URL}/logout`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      localStorage.removeItem('authToken');
      setToken(null);
      setUser(null);
      setLoading(false);
    }
  };

  // Fetch user on mount
  useEffect(() => {
    if (token) {
      getCurrentUser();
    }
  }, [token]);

  return (
    <AuthContext.Provider value={{ user, token, loading, register, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = React.useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};
```

### Usage in Components

```javascript
import { useAuth } from './AuthContext';

function LoginPage() {
  const { login, loading } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const handleLogin = async (e) => {
    e.preventDefault();
    try {
      const result = await login(email, password);
      if (result.token) {
        // Navigate to dashboard
      }
    } catch (error) {
      console.error('Login failed:', error);
    }
  };

  return (
    <form onSubmit={handleLogin}>
      <input
        type="email"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        placeholder="Email"
      />
      <input
        type="password"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        placeholder="Password"
      />
      <button type="submit" disabled={loading}>
        {loading ? 'Loading...' : 'Login'}
      </button>
    </form>
  );
}
```

### Protected Routes in React Router

```javascript
import { Navigate } from 'react-router-dom';
import { useAuth } from './AuthContext';

const ProtectedRoute = ({ children }) => {
  const { token } = useAuth();

  if (!token) {
    return <Navigate to="/login" replace />;
  }

  return children;
};

// Usage
<Routes>
  <Route path="/login" element={<LoginPage />} />
  <Route path="/register" element={<RegisterPage />} />
  <Route
    path="/dashboard"
    element={
      <ProtectedRoute>
        <Dashboard />
      </ProtectedRoute>
    }
  />
</Routes>
```

### API Calls with Authentication

```javascript
const makeAuthenticatedRequest = async (endpoint, options = {}) => {
  const token = localStorage.getItem('authToken');
  
  const response = await fetch(`http://localhost:8000/api${endpoint}`, {
    ...options,
    headers: {
      ...options.headers,
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
  });

  if (response.status === 401) {
    // Handle token expiration
    localStorage.removeItem('authToken');
    window.location.href = '/login';
  }

  return response.json();
};

// Usage
const getVulnerabilityStats = async () => {
  return makeAuthenticatedRequest('/vulnerability-stats');
};
```

---

## 5. Security Best Practices

### Server-Side (Laravel)

✅ **Implemented:**
- Passwords hashed with bcrypt
- Token-based authentication with Sanctum
- CORS properly configured for frontend domain
- Validation on all inputs
- Error handling without exposing sensitive information
- Middleware protection on sensitive routes

### Client-Side (React)

✅ **Recommendations:**
- Store tokens in localStorage (or httpOnly cookies for better security)
- Always send tokens in `Authorization: Bearer` header
- Implement token refresh mechanism for long sessions
- Clear tokens on 401 responses
- Use HTTPS in production
- Implement rate limiting on frontend
- Add request/response interceptors for consistent error handling

### Environment Configuration

```env
# .env
APP_ENV=production
APP_DEBUG=false
SANCTUM_STATEFUL_DOMAINS=yourdomain.com
SESSION_DOMAIN=yourdomain.com
```

---

## 6. Testing the Endpoints

### Using cURL

```bash
# Register
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'

# Get User (replace TOKEN with actual token)
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer TOKEN"

# Logout
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer TOKEN"
```

### Using Postman

1. Register: Set method to `POST`, URL to `http://localhost:8000/api/register`
2. In Body (raw JSON), add registration data
3. Copy the `token` from response
4. For protected routes, go to Authorization tab
5. Select Type: `Bearer Token`
6. Paste the token in the Token field

---

## 7. Troubleshooting

### Issue: "Unauthenticated" on protected routes

**Solution:**
- Verify token is being sent in `Authorization: Bearer` header
- Check if token is still valid (Sanctum tokens don't expire by default)
- Verify CORS is configured correctly for your frontend domain

### Issue: CORS errors in React

**Solution:**
- Ensure frontend is on localhost:3000 (or update `SANCTUM_STATEFUL_DOMAINS`)
- Check that `Authorization` header is properly set
- Verify Content-Type header is `application/json`

### Issue: "Invalid credentials" on login

**Solution:**
- Verify user exists in database
- Check password is correct
- Use case-sensitive email matching

---

## 8. Production Checklist

- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Update `SANCTUM_STATEFUL_DOMAINS` to your production domain
- [ ] Use HTTPS for all API calls
- [ ] Set strong `APP_KEY` in `.env`
- [ ] Configure proper CORS headers for your domain
- [ ] Implement rate limiting (consider using `throttle` middleware)
- [ ] Set up proper error logging
- [ ] Use environment-specific database configuration
- [ ] Implement token refresh mechanism for long-lived sessions

---

## File Structure

```
app/
├── Http/
│   └── Controllers/
│       ├── AuthController.php          (NEW - Authentication logic)
│       └── VulnerabilityController.php (Protected routes)
├── Models/
│   └── User.php                        (Updated with HasApiTokens)
config/
├── sanctum.php                         (NEW - Sanctum configuration)
└── auth.php                            (Uses Sanctum guard)
database/
└── migrations/
    └── [timestamp]_create_personal_access_tokens_table.php (NEW)
routes/
└── api.php                             (Updated with auth routes)
bootstrap/
└── app.php                             (Updated with Sanctum middleware)
```

---

## Additional Resources

- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [Laravel Authentication Documentation](https://laravel.com/docs/authentication)
- [CORS Configuration](https://laravel.com/docs/cors)
- [Laravel Validation](https://laravel.com/docs/validation)

