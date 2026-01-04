# Validation

NanoMVC provides a simple yet powerful validation system for validating user input.

## Table of Contents

- [Basic Validation](#basic-validation)
- [Validation Rules](#validation-rules)
- [Handling Errors](#handling-errors)
- [Custom Validation](#custom-validation)
- [Form Validation Patterns](#form-validation-patterns)

---

## Basic Validation

### In Controllers

```php
use PaigeJulianne\NanoMVC\Controller;
use PaigeJulianne\NanoMVC\Request;
use PaigeJulianne\NanoMVC\Response;
use PaigeJulianne\NanoMVC\ValidationException;

class UsersController extends Controller
{
    public function store(Request $request): Response
    {
        try {
            // Validate and get only validated data
            $data = $this->validate([
                'name' => 'required|min:2|max:100',
                'email' => 'required|email',
                'password' => 'required|min:8'
            ]);

            // $data only contains validated fields
            $user = User::create($data);

            return $this->redirect('/users/' . $user->id);

        } catch (ValidationException $e) {
            // Handle validation errors
            return $this->view('users.create', [
                'errors' => $e->getErrors(),
                'old' => $request->all()
            ]);
        }
    }
}
```

### Using Validator Directly

```php
use PaigeJulianne\NanoMVC\Validator;

$validator = new Validator($request->all(), [
    'name' => 'required|min:2',
    'email' => 'required|email',
    'age' => 'numeric|min:18'
]);

if ($validator->fails()) {
    $errors = $validator->errors();
    // Handle errors
}

$validatedData = $validator->validated();
```

---

## Validation Rules

### Required

Field must be present and not empty.

```php
'name' => 'required'
'email' => 'required'
```

### Email

Must be a valid email format.

```php
'email' => 'email'
'email' => 'required|email'
```

### Numeric

Must be a numeric value (integer or float).

```php
'price' => 'numeric'
'quantity' => 'required|numeric'
```

### Integer

Must be an integer value.

```php
'age' => 'integer'
'count' => 'required|integer'
```

### Min/Max (String Length)

String must meet minimum/maximum length requirements.

```php
'name' => 'min:2'              // At least 2 characters
'bio' => 'max:500'             // At most 500 characters
'password' => 'min:8|max:100'  // Between 8 and 100 characters
```

### Min/Max (Numeric Value)

For numeric fields, min/max validate the value itself.

```php
'age' => 'numeric|min:18'        // Must be at least 18
'rating' => 'numeric|min:1|max:5' // Must be between 1 and 5
```

### In (Whitelist)

Value must be one of the specified options.

```php
'status' => 'in:active,pending,inactive'
'role' => 'required|in:user,admin,moderator'
'priority' => 'in:low,medium,high,critical'
```

### URL

Must be a valid URL.

```php
'website' => 'url'
'callback' => 'required|url'
```

### Alpha

Must contain only letters (a-z, A-Z).

```php
'country_code' => 'alpha'
```

### Alphanumeric

Must contain only letters and numbers.

```php
'username' => 'alphanumeric'
'code' => 'required|alphanumeric'
```

### Combining Rules

Rules are combined with the pipe `|` character:

```php
$data = $this->validate([
    'username' => 'required|alphanumeric|min:3|max:20',
    'email' => 'required|email',
    'password' => 'required|min:8|max:100',
    'age' => 'required|integer|min:13',
    'role' => 'required|in:user,admin',
    'website' => 'url',  // Optional, but validated if provided
    'bio' => 'max:1000'  // Optional, with max length
]);
```

---

## Handling Errors

### ValidationException

When validation fails, a `ValidationException` is thrown:

```php
use PaigeJulianne\NanoMVC\ValidationException;

try {
    $data = $this->validate([
        'email' => 'required|email'
    ]);
} catch (ValidationException $e) {
    // Get all errors as array
    $errors = $e->getErrors();
    // [
    //     'email' => ['The email field is required.']
    // ]

    // Get first error for a field
    $emailError = $errors['email'][0] ?? null;

    // Get as JSON response (for APIs)
    return $e->toResponse();
    // Returns: {"errors": {"email": ["The email field is required."]}}
}
```

### Error Messages

Default error messages:

| Rule | Message |
|------|---------|
| `required` | The {field} field is required. |
| `email` | The {field} field must be a valid email. |
| `numeric` | The {field} field must be numeric. |
| `integer` | The {field} field must be an integer. |
| `min` | The {field} field must be at least {n} characters. |
| `max` | The {field} field must not exceed {n} characters. |
| `in` | The {field} field must be one of: {values}. |
| `url` | The {field} field must be a valid URL. |
| `alpha` | The {field} field must contain only letters. |
| `alphanumeric` | The {field} field must contain only letters and numbers. |

### API Error Response

For JSON APIs, use `expectsJson()` to return appropriate format:

```php
public function store(Request $request): Response
{
    try {
        $data = $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email'
        ]);

        $user = User::create($data);
        return $this->json($user, 201);

    } catch (ValidationException $e) {
        if ($request->expectsJson()) {
            return $e->toResponse();  // JSON with 422 status
        }

        return $this->view('form', [
            'errors' => $e->getErrors(),
            'old' => $request->all()
        ]);
    }
}
```

---

## Custom Validation

### Additional Checks After Validation

```php
public function store(Request $request): Response
{
    try {
        $data = $this->validate([
            'email' => 'required|email',
            'username' => 'required|alphanumeric|min:3'
        ]);

        // Additional custom validation
        if (User::where('email', '=', $data['email'])->first()) {
            throw new ValidationException([
                'email' => ['This email is already registered.']
            ]);
        }

        if (User::where('username', '=', $data['username'])->first()) {
            throw new ValidationException([
                'username' => ['This username is already taken.']
            ]);
        }

        // Check password confirmation
        if ($request->input('password') !== $request->input('password_confirmation')) {
            throw new ValidationException([
                'password_confirmation' => ['Passwords do not match.']
            ]);
        }

        $user = User::create($data);
        return $this->redirect('/users/' . $user->id);

    } catch (ValidationException $e) {
        return $this->view('users.create', [
            'errors' => $e->getErrors(),
            'old' => $request->except(['password', 'password_confirmation'])
        ]);
    }
}
```

### Validation Helper Functions

```php
// helpers.php

/**
 * Validate that a date is in the future
 */
function validateFutureDate(string $date): bool
{
    $timestamp = strtotime($date);
    return $timestamp !== false && $timestamp > time();
}

/**
 * Validate file upload
 */
function validateFileUpload(array $file, array $allowedTypes, int $maxSize): array
{
    $errors = [];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed.';
        return $errors;
    }

    if (!in_array($file['type'], $allowedTypes)) {
        $errors[] = 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes);
    }

    if ($file['size'] > $maxSize) {
        $errors[] = 'File too large. Maximum size: ' . formatBytes($maxSize);
    }

    return $errors;
}

// Usage in controller
$file = $request->file('document');
$fileErrors = validateFileUpload($file, ['application/pdf'], 5 * 1024 * 1024);

if (!empty($fileErrors)) {
    throw new ValidationException(['document' => $fileErrors]);
}
```

---

## Form Validation Patterns

### Complete Form Example

```php
class RegistrationController extends Controller
{
    public function showForm(Request $request): Response
    {
        return $this->view('auth.register', [
            'errors' => Session::getFlash('errors', []),
            'old' => Session::getFlash('old', [])
        ]);
    }

    public function register(Request $request): Response
    {
        try {
            // Step 1: Basic validation
            $data = $this->validate([
                'name' => 'required|min:2|max:100',
                'email' => 'required|email',
                'password' => 'required|min:8|max:100',
                'terms' => 'required'
            ]);

            // Step 2: Custom validation
            if (User::where('email', '=', $data['email'])->first()) {
                throw new ValidationException([
                    'email' => ['This email is already registered.']
                ]);
            }

            $password = $request->input('password');
            $confirmation = $request->input('password_confirmation');

            if ($password !== $confirmation) {
                throw new ValidationException([
                    'password_confirmation' => ['Passwords do not match.']
                ]);
            }

            // Step 3: Create user
            $user = new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $user->save();

            // Step 4: Log in and redirect
            Session::regenerate();
            Session::set('user_id', $user->id);
            Session::flash('success', 'Welcome, ' . $user->name . '!');

            return $this->redirect('/dashboard');

        } catch (ValidationException $e) {
            // Flash errors and old input for form re-display
            Session::flash('errors', $e->getErrors());
            Session::flash('old', $request->except(['password', 'password_confirmation']));

            return $this->redirect('/register');
        }
    }
}
```

### Form View with Error Display

```php
<!-- views/auth/register.php -->
<?php ob_start(); ?>

<h1>Register</h1>

<form method="POST" action="/register">
    <input type="hidden" name="_token" value="<?= Session::csrfToken() ?>">

    <div class="form-group">
        <label for="name">Name</label>
        <input type="text"
               id="name"
               name="name"
               value="<?= htmlspecialchars($old['name'] ?? '') ?>"
               class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>">
        <?php if (isset($errors['name'])): ?>
            <span class="error"><?= htmlspecialchars($errors['name'][0]) ?></span>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="email">Email</label>
        <input type="email"
               id="email"
               name="email"
               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
               class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>">
        <?php if (isset($errors['email'])): ?>
            <span class="error"><?= htmlspecialchars($errors['email'][0]) ?></span>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <input type="password"
               id="password"
               name="password"
               class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>">
        <?php if (isset($errors['password'])): ?>
            <span class="error"><?= htmlspecialchars($errors['password'][0]) ?></span>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="password_confirmation">Confirm Password</label>
        <input type="password"
               id="password_confirmation"
               name="password_confirmation"
               class="<?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>">
        <?php if (isset($errors['password_confirmation'])): ?>
            <span class="error"><?= htmlspecialchars($errors['password_confirmation'][0]) ?></span>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label>
            <input type="checkbox" name="terms" value="1"
                   <?= !empty($old['terms']) ? 'checked' : '' ?>>
            I agree to the terms and conditions
        </label>
        <?php if (isset($errors['terms'])): ?>
            <span class="error"><?= htmlspecialchars($errors['terms'][0]) ?></span>
        <?php endif; ?>
    </div>

    <button type="submit">Register</button>
</form>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
```

### Reusable Error Display Partial

```php
<!-- views/partials/form-errors.php -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h4>Please correct the following errors:</h4>
        <ul>
            <?php foreach ($errors as $field => $fieldErrors): ?>
                <?php foreach ($fieldErrors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Usage in form view -->
<?php include __DIR__ . '/partials/form-errors.php'; ?>
```

---

## Next Steps

- [Controllers](controllers.md) - Controller patterns
- [Request & Response](request-response.md) - Input handling
- [Sessions](sessions.md) - Flash messages for errors
