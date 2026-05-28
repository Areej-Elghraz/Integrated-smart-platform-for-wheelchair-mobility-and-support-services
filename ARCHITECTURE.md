# ChairPal AI / HW Communication Architecture

This document outlines the high-level architecture and rules governing the interaction between the Laravel Backend and the onboard AI/HW Wheelchair Controller.

## 1. Core Principles

### Local Safety Primacy (LSP)
**"All safety-critical and time-sensitive decisions must execute locally on the AI/HW controller without backend dependency."**
The wheelchair must never wait for a server response to avoid an obstacle, stop in an emergency, or maintain balance.

### Asynchronous & Non-blocking Ingestion
The backend communication is designed to be asynchronous. When the wheelchair sends telemetry or events, the backend acknowledges receipt with a `202 Accepted` status. This indicates that the data has been ingested for background processing, ensuring that network latency does not block the wheelchair's local operations.

### High-Level Coordination Only
The backend acts as a **High-Level Coordination Layer**. It manages trip lifecycle, global navigation requests, long-term health tracking, and notifications. It **never** directly controls motors or low-level movement.

## 2. Navigation Modes

-   **Manual**: The user controls movement directly via the mobile app. All requests pass through local AI safety validation.
-   **Assisted**: An authorized assistant sends high-level intervention requests.
-   **Autonomous**: The wheelchair navigates a route independently. This mode is only available when a map or route context exists (mapped environments).

## 3. Data Structures

### Flexible Position Data (`position_data`)
Positioning is stored as an extensible JSON object to support diverse environments:
-   **GPS**: `{ "type": "gps", "lat": 30.0, "lng": 31.0 }`
-   **Indoor**: `{ "type": "indoor", "x": 10.5, "y": 20.2, "floor": 2, "building_id": 5 }`
-   **Map-Relative**: `{ "type": "map", "node_id": "A12", "offset": 0.5 }`

### Decision Context
Every AI decision (recorded in `AIStatusLog`) includes a `decision_context` JSON capturing the state of the world (obstacle distance, battery, sensor states, heart, temperature, and mpu_monitoring) at the exact moment the decision was made.

## 4. Fail-Safe Behavior

The system is designed to be resilient to connectivity issues:
1.  **Lost Connection**: If the wheelchair loses connection to the backend, it must switch to a safe state (e.g., local stop or manual-only) based on the current navigation mode.
2.  **Degraded Performance**: The `DeviceStatus` monitors connectivity levels (`online`, `degraded_connection`, `offline`) to adjust autonomous behavior.

## 5. Notification & Escalation

Events are mapped to a delivery strategy based on severity:
-   **Info**: Logged and visible in-app.
-   **Warning**: Push notification to the user's mobile device.
-   **Critical**: Persistent alert + immediate escalation to the assistant module/emergency contacts.

---

## 6. Indoor Mapping & Localization Integration

To support precise indoor navigation and autonomous mobility inside organizations and places:
1. **Multi-level Environments**: Every Organization or Place can have one or more `Floors` associated with it.
2. **Floor Map Reference**: Each `Floor` can have a registered `Map` containing:
   - `map_file`: A high-resolution layout image.
   - `width`/`height`: Real-world physical dimensions in meters.
   - `resolution`: Spatial resolution (meters per pixel).
   - `origin`: The reference origin coordinate `[x, y, z]`.
3. **Indoor Positioning Validation**: Places and waypoints map their exact indoor position via `x`, `y`, `z` coordinates and a `rotation` value, enabling the AI pathfinder to align sensor telemetry directly with registered floor maps.

---

## 7. AI ChatBot Architecture (FastText Context-Aware)

The chatbot is not a generic assistant. It is a **specialized medical & navigational AI** designed for wheelchair users with lower body paralysis or amputation.

### Flow
1. **Flutter** sends only `{ "message": "user text" }` to `POST /api/chatbot/sessions/{session}/chat`.
2. **Laravel** intercepts the request and **automatically collects** the full patient context from the database:
   - User profile (name, age, weight, gender, medical conditions).
   - Related persons (doctor name/phone, companion names/phones).
   - Wheelchair status (battery, serial number, connection state).
   - Live health vitals (heart rate, temperature, MPU angle, fall detection).
   - Active trip details (destination, current coordinates).
   - Latest alert for each category (heart, temperature, mpu_monitoring, obstacle, SOS, battery).
3. **Laravel** sends `{ "user_text": "...", "context": { ... } }` to the **Python fastText AI service**.
4. **fastText** detects the language (Arabic/English) automatically from `user_text` — no `language` parameter needed.
5. **Python AI** generates a context-aware, medically appropriate response and returns it.
6. **Laravel** saves both `user_message` and `bot_message` in `chat_messages` table and returns them to Flutter.

### Key Design Decisions
- **No language parameter:** fastText handles auto-detection.
- **No health data from Flutter:** Laravel fetches everything from DB to prevent spoofing.
- **Context JSON is hidden:** Flutter never sees or builds the context payload.

---

## 8. Data Lifecycle & Storage Management

### High-Frequency Data Strategy
| Data Type | Strategy | Retention |
|:---|:---|:---|
| `ai_recommendations` (vitals) | `updateOrCreate` — 1 row per wheelchair | Permanent (overwritten) |
| `trip_movement_states` | `updateOrCreate` — 1 row per trip | Deleted after 1 month |
| `sensor_readings_aggregated` | Append (pre-aggregated windows from HW) | Deleted after 1 year |
| `events` | Append with deduplication | Deleted after 3 months |

### Cleanup Automation
- **`php artisan data:cleanup`** — Runs daily via Laravel Scheduler. Prunes old data per retention policy.
- **`php artisan files:clean-orphans`** — Run manually. Scans `storage/app/public` and deletes files not referenced in any DB table.

---

## 9. File Safety Architecture (Orphan Prevention)

### Automatic Deletion (Model Observers)
Every model with image/file columns uses a `booted()` method with a `deleting` listener. When a record is deleted via Eloquent (`$model->delete()`), associated files are automatically removed from `storage/app/public`:

| Model | Column(s) | Type |
|:---|:---|:---|
| `User` | `image` | Single file |
| `Organization` | `image` | Single file |
| `Place` | `image` | Single file |
| `Category` | `image` | Single file |
| `Message` | `attachment` | Single file |
| `ChatMessage` | `attachments` | JSON array |
| `Post` | `images`, `files` | JSON arrays |

### Manual Cleanup (Fallback)
If records are deleted via raw SQL (not Eloquent), the `booted()` observer won't fire. In this case, run `php artisan files:clean-orphans` to scan the disk and remove any file without a DB reference.

> **⚠️ Production Rule:** Never run `migrate:refresh` or `migrate:fresh` on a live server. Use only `migrate` for incremental schema changes.

