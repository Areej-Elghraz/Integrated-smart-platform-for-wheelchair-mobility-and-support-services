# Admin Panel Documentation

## Why was it returning a 404 Error?

The admin panel was returning a `404 Not Found` error because the Filament configuration file (`app/Providers/Filament/AdminPanelProvider.php`) had an incorrect `id` and `path`.
Instead of `'admin'`, they were set to the string `'return $panel'`.

Additionally, the `User` model did not implement the `FilamentUser` interface, which is required by Filament 3.x to determine if a user is allowed to access the admin panel.

## How to Access the Admin Panel

1. Open your web browser.
2. Navigate to your application's base URL followed by `/admin`.
    - Example: `http://127.0.0.1:8000/admin`
3. You will be greeted by the login screen.
4. Log in using an account that has access privileges.

## Access Control (Authorization)

Access to the panel is controlled by the `canAccessPanel(Panel $panel)` method in the `app/Models/User.php` file.

Currently, it is set to always return `true` so you can test it easily during development:

```php
public function canAccessPanel(Panel $panel): bool
{
    // Returns true for testing purposes so any logged-in user can access the panel
    return true;
}
```

In production, you should restrict this to administrators only:

```php
public function canAccessPanel(Panel $panel): bool
{
    // Only users with the 'admin' role can access the panel
    return $this->isAdmin();
}
```

## Adding Resources

To add new tables (like Users, Organizations, Wheelchairs) to the Admin Panel, you can use the Filament artisan commands.
Example:

```bash
php artisan filament:resource User
php artisan filament:resource Organization
```

This will automatically generate the forms and tables needed to manage these records from the `/admin` interface.

---

# ChairPal Admin Panel URLs

This document outlines the URLs available in the ChairPal Admin Panel (powered by FilamentPHP) and describes what each page contains.

## Accessing the Admin Panel

- **Base URL:** `http://your-domain/admin`
- **Login Page:** `http://your-domain/admin/login` (Admin credentials required)
- **Dashboard (Home):** `http://your-domain/admin` (Displays statistics like total users, active wheelchairs, etc.)

---

## 1. User Management

Manage all users in the system, including patients (wheelchair users), doctors, and relatives.

- **URL:** `http://your-domain/admin/users`
- **What you will see:** A list of all users.
- **Actions:**
    - **Create:** Add a new user and assign their role.
    - **Edit:** Update user details (name, email, role, etc.).
    - **View:** See the medical conditions attached to a specific user.
    - **Delete:** Remove a user from the system.

## 2. Medical Conditions Management

Manage the static list of medical conditions available for users to select during registration.

- **URL:** `http://your-domain/admin/medical-conditions`
- **What you will see:** A list of conditions (e.g., Heart Disease, Diabetes, Hypertension, Epilepsy).
- **Actions:**
    - **Create:** Add a new medical condition (with English and Arabic names/descriptions).
    - **Edit:** Update condition names and categories.
    - **Delete:** Remove an outdated condition.

## 3. Wheelchairs Management

Monitor and manage the wheelchairs registered in the system.

- **URL:** `http://your-domain/admin/wheelchairs`
- **What you will see:** A list of wheelchairs showing their serial number, battery level, online status, and assigned user.
- **Actions:**
    - **View:** See real-time metrics for a specific wheelchair.
    - **Edit:** Update wheelchair details.
    - **Delete:** Unregister a wheelchair from the system.

## 4. Geographical Data (Countries & Cities)

Manage the locations available in the app.

- **URL:** `http://your-domain/admin/countries`
- **What you will see:** A list of countries supported by the app.
- **URL:** `http://your-domain/admin/cities`
- **What you will see:** A list of cities linked to countries.

## 5. Organizations & Places

Manage accessibility-friendly places and organizations.

- **URL:** `http://your-domain/admin/organizations`
- **What you will see:** A list of organizations (e.g., hospital chains, NGOs).
- **URL:** `http://your-domain/admin/places`
- **What you will see:** A list of specific places (e.g., a specific hospital branch) with details on accessibility features (e.g., ramps, elevators).

## 6. Support Messages

View and respond to messages sent by users through the app's support center.

- **URL:** `http://your-domain/admin/support-messages`
- **What you will see:** A list of messages from users detailing issues or feedback.
- **Actions:**
    - **View:** Read the full message and user contact details.
    - **Delete:** Remove resolved messages
