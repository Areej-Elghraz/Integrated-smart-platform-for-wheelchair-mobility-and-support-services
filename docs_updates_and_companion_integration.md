# Recent Fixes & Companion/Doctor Integration Updates

This document combines the latest bug fixes, API features, and the complete Companion & Doctor Integration walkthrough.

## PART 1: LATEST BUG FIXES AND UPDATES

### 1. User Authentication & Verification Flow Fixes
- **Database Level:** Created a new migration to explicitly make `email_verified_at` nullable and remove the `CURRENT_TIMESTAMP` default value that was auto-verifying users upon sign up.
- **Application Level:** 
  - Restructured `LoginController` to check the `email_verified_at` property **before** `Auth::attempt()`. This guarantees that unverified users are strictly blocked with a 403 response before generating any Sanctum tokens.

### 2. Dedicated Last Visited Places API
- Extracted the 'Last Visited Places' list from the heavy `DashboardController` into a dedicated endpoint: `GET /api/places/last-visited`.
- This endpoint handles pagination and **Search Filtering** via the `?search=` parameter.
- Companion and Doctor roles can pass `?user_id=` to retrieve the visited places of their linked patients.

### 3. Multilingual Medical Conditions Fix
- Updated the migration to use `DB::table()->delete()` instead of `truncate()` to gracefully clear the tables without violating foreign key constraints.
- The `MedicalCondition` model natively intercepts the `Accept-Language` header to return the correctly translated string, keeping the API layer perfectly clean.

### 4. Global Search & Multi-Language Enhancements
Added global search `?search=` parameters across the following endpoints using Laravel Eloquent Scopes for optimization:
- Categories, Organizations, Buildings, Floors, Maps, Places
- Friends Chat histories
- Posts

Implemented a new public `GET /api/languages` endpoint returning an array of the fully supported languages (`ar, de, en, fr, ge, hi, ko, vi`) for frontend and user-profile usage.

---

## PART 2: COMPANION AND DOCTOR INTEGRATION WALKTHROUGH

This section outlines the modifications made to integrate the advanced Companion and Doctor features into the platform.

### What was Changed

#### 1. Connection Requests System
- **Database:** Created a new `connection_requests` table to track relations specifically between Users and Companions/Doctors without polluting the generic community friend request system.
- **API Endpoints:** Introduced dedicated endpoints (`/connection-requests/*`) to send, accept, reject, and list connection requests.
- **Model Logic:** Connected the `User` model to `ConnectionRequest` with helper attributes like `$user->connectedCompanions` and `$user->connectedDoctor`.
- **Auto-Friendship:** When a connection request is accepted, a `Friendship` is automatically generated. This allows the Companion/Doctor to use the community chat and interaction features exactly as requested.

#### 2. Companion Live Location Tracking
- **Outdoor Location:** Modified `LocationController` to cache the User's live GPS coordinates whenever they broadcast it.
- **Indoor Location:** Modified `LocationController` to access the User's connected wheelchair coordinates (updated by the hardware script).
- **Access Endpoint:** Added `GET /api/location/companion/user` specifically for the Companion to fetch both the indoor and outdoor real-time location of their connected User.

#### 3. Companion Authorization & Privileges
- Updated the Policies (`CategoryPolicy`, `OrganizationPolicy`, `BuildingPolicy`, `FloorPolicy`, `MapPolicy`, `PlacePolicy`).
- Companions now have inherited privileges. When a Companion makes a Create/Update/Delete request, the system checks if they are acting on behalf of their connected User. If so, they are granted permission to manage resources (like categories, organizations, places, etc.) just as the User would.

#### 4. Notifications & Automatic SOS Alerts
- **SOS Isolation:** Modified `SosController` and the Automatic AI Fall Detection (`WheelchairController`) so that SOS alerts are sent exclusively to the User's connected Companions and Doctors, rather than all community friends.
- **Connection Notifications:** Created Database Notifications (`ConnectionRequestReceivedNotification` and `ConnectionRequestAcceptedNotification`) that are triggered when requests are sent or accepted. A confirmation email (`RequestAcceptedMail`) is also dispatched automatically.

#### 5. Organization Admin Dashboard & Audit Logs
- **Audit Logging Trait:** Created a reusable `LogsAdminActions` trait.
- **Log Injection:** Injected this trait into `CategoryController`, `OrganizationController`, `BuildingController`, `FloorController`, `MapController`, and `PlaceController`. All created, updated, and deleted actions performed by an Organization Admin are now logged directly to the `audit_logs` table.
- **Admin Dashboard:** Added `GET /api/dashboard/org-admin` to fetch the Organization Admin's organizations along with their recent 50 activity logs.

### Verification
The core infrastructure for the companion, doctor, and admin flows is complete.
- Connection flows can be validated via the `/api/connection-requests/*` routes.
- The Organization Admin can access their logs via `/api/dashboard/org-admin`.
- Companions can check location using `/api/location/companion/user`.
