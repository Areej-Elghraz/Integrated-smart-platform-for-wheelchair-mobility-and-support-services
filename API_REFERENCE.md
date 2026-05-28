# ChairPal API Reference: Real-Time Movement, Health, & Community

This document provides a detailed reference for all endpoints related to wheelchair management, trip execution, sensor health tracking, and community engagement.

## 0. Related Architecture Documentation

Before reviewing the API endpoints, please check out these specialized documentation files which describe the architecture and workflows in detail:
- **[MQTT Architecture & Manual/Autonomous Flows](docs_mqtt_architecture.md)**
- **[Dashboards & Real-time Aggregation](docs_dashboards.md)**
- **[Places & Geometric Center Calculation](docs_places.md)**
- **[Floors, Maps & Policy Restrictions](docs_floors_maps.md)**
- **[Wheelchair Hardware & WebSockets](docs_wheelchairs.md)**

## 0.5 Authentication & Tokens

All protected endpoints require an `access_token` passed in the `Authorization: Bearer <token>` header.

When you login via `POST /api/auth/login`, you receive:

- `access_token`: Short-lived token for API requests.
- `remember_token`: Used to retrieve a new `access_token` when it expires via `POST /api/auth/refresh`.

## 1. Wheelchair Management

### Handshake (Hardware Boot Initialization)

`GET /api/wheelchairs/handshake`

| Parameter       | Type   | Required | Description                                     | Example     |
| :-------------- | :----- | :------- | :---------------------------------------------- | :---------- |
| `serial_number` | String | Yes      | The serial number of the wheelchair to connect. | `CHAIR-001` |

### Connect Wheelchair

`POST /api/wheelchairs/connect`

| Parameter       | Type   | Required | Description                                     | Example     |
| :-------------- | :----- | :------- | :---------------------------------------------- | :---------- |
| `serial_number` | String | Yes      | The serial number of the wheelchair to connect. | `CHAIR-001` |

### Disconnect Wheelchair

`POST /api/wheelchairs/{wheelchairId}/disconnect`

### Update Wheelchair Details

`PUT /api/wheelchairs/{wheelchairId}`

| Parameter          | Type   | Required | Description                           | Example  |
| :----------------- | :----- | :------- | :------------------------------------ | :------- |
| `battery`          | Float  | No       | Battery percentage/voltage remaining. | `85.5`   |
| `voltage`          | Float  | No       | Current voltage reading.              | `24.0`   |
| `current`          | Float  | No       | Current draw in Amperes.              | `2.3`    |
| `temperature`      | Float  | No       | Device temperature reading.           | `32.1`   |
| `connection_state` | String | No       | `online` or `offline`.                | `online` |

### Unassign Wheelchair

`POST /api/wheelchairs/{wheelchairId}/unassign`

---

## 2. Trip Management

### Start Trip

`POST /api/wheelchairs/{wheelchairId}/trips`

| Parameter | Type   | Required | Description                      |
| :-------- | :----- | :------- | :------------------------------- |
| `mode`    | String | Yes      | Options: `autonomous`, `manual`. |

### End Trip

`POST /api/trips/{tripId}/end`

### Update Trip Movement State

`POST /api/trips/{tripId}/movement-states`

| Parameter           | Type    | Required | Description                   |
| :------------------ | :------ | :------- | :---------------------------- |
| `movement_status`   | String  | Yes      | `moving` or `idle`.           |
| `speed`             | Float   | Yes      | Speed of wheelchair.          |
| `position`          | JSON    | Yes      | Coordinate data (x, y, etc).  |
| `theta`             | Float   | Yes      | Orientation angle.            |
| `mode`              | String  | Yes      | `autonomous` or `manual`.     |
| `risk_level`        | String  | Yes      | `low`, `medium`, or `high`.   |
| `obstacle_detected` | Boolean | Yes      | True if obstacle detected.    |
| `obstacle_distance` | Float   | Yes      | Distance to closest obstacle. |

### Get Trip Movement State

`GET /api/trips/{tripId}/movement-states`

---

## 3. Sensors & Health Vitals

### Store Sensor Reading (Aggregated Windows)

`POST /api/wheelchairs/{wheelchairId}/sensor-readings`

| Parameter         | Type    | Required | Description           | Example                |
| :---------------- | :------ | :------- | :-------------------- | :--------------------- |
| `trip_id`         | Integer | No       | ID of active trip.    | `1`                    |
| `heart_rate_min`  | Float   | No       | Minimum HR in window. | `60`                   |
| `heart_rate_max`  | Float   | No       | Maximum HR in window. | `85`                   |
| `heart_rate_avg`  | Float   | No       | Average HR in window. | `72.5`                 |
| `temperature_min` | Float   | No       | Min temp.             | `36.5`                 |
| `temperature_max` | Float   | No       | Max temp.             | `37.0`                 |
| `temperature_avg` | Float   | No       | Avg temp.             | `36.7`                 |
| `mpu_angle_min`   | Float   | No       | Min tilt angle.       | `-5.0`                 |
| `mpu_angle_max`   | Float   | No       | Max tilt angle.       | `10.0`                 |
| `mpu_angle_avg`   | Float   | No       | Avg tilt angle.       | `2.3`                  |
| `reading_time`    | Date    | Yes      | ISO 8601 timestamp.   | `2026-05-25T12:00:00Z` |

### Get Sensor Reading (Aggregated Windows)

`GET /api/wheelchairs/{wheelchairId}/sensor-readings`

### Update Vitals

`POST /api/wheelchairs/{wheelchairId}/vitals`

| Parameter            | Type    | Required | Description                    |
| :------------------- | :------ | :------- | :----------------------------- |
| `heart_rate`         | Float   | Yes      | User heart rate.               |
| `heart_rate_status`  | String  | Yes      | `normal`, `medium`, `critical`.|
| `temperature`        | Float   | Yes      | Body temperature.              |
| `temperature_status` | String  | Yes      | `normal`, `medium`, `critical`.|
| `mpu_angle`          | Float   | Yes      | Tilt angle to detect falls.    |
| `fall_status`        | String  | Yes      | `normal`, `medium`, `critical`. Automatically triggers SOS if critical. |
| `type`               | String  | No       | defaults to 'health'.          |
| `risk_level`         | String  | Yes      | `normal`, `medium`, `critical`.|
| `reason`             | String  | No       | Explanation for risk level.    |
| `recommendation`     | String  | No       | Actions to take.               |

---

## 4. Events System

### Store Trip Event

`POST /api/trips/{tripId}/events`
Supports automated deduplication for recurring system events.

| Parameter      | Type   | Required | Description                             |
| :------------- | :----- | :------- | :-------------------------------------- |
| `type`         | String | Yes      | `health`, `obstacle`, `sos`, `battery`. |
| `severity`     | String | Yes      | `normal`, `medium`, `critical`.         |
| `message`      | String | Yes      | Human-readable event description.       |
| `data`         | JSON   | Yes      | Technical payload.                      |
| `event_source` | String | No       | `ai` (default) or `system`.             |

---

## 5. Dashboards

Real-time role-based dashboards pushed via Reverb (`dashboard.{userId}`) and queried directly:

- `GET /api/dashboard/user`
- `GET /api/dashboard/companion`
- `GET /api/dashboard/doctor`

---

## 6. Community & Chat

- **Real-Time Push:** Events broadcast over Laravel Reverb channels.
- **Database Notifications:** Stored in `notifications` table for read/unread history.

### Friend Requests

`POST /api/community/friends/send`

- Payload: `{ "user_id": 42 }`
- Event: `friend.request.received`

`POST /api/community/friends/{userId}/handle`

- Payload: `{ "action": "accept" }`
- Event: `friend.request.accepted`
- Result: Opens a chat conversation dynamically.

### Chats & Posts

- `POST /api/chats/{userId}/messages` -> Fires `message.sent`
- `POST /api/posts` -> Fires `post.created`
- `POST /api/posts/{postId}/like` -> Fires `post.liked`

---

## 7. AI ChatBot (Context-Aware Medical Assistant)

The chatbot is a **specialized medical & navigational assistant** built with **fastText** for automatic language detection (Arabic / English). The Flutter app sends **only the user's message**. Laravel collects all context data behind the scenes and sends it to the Python AI service.

### Chat with Bot

`POST /api/chatbot/sessions/{session}/chat`

| Parameter | Type   | Required | Description                |
| :-------- | :----- | :------- | :------------------------- |
| `message` | String | Yes*     | User's text message.       |
| `media`   | File[] | No       | Optional image/audio files.|

*Required if `media` is not provided.

**What Flutter sends:**
```json
{ "message": "انا حاسس بتعب ومش عارف اروح فين" }
```

**What Laravel builds and sends to Python AI (hidden from Flutter):**
```json
{
  "user_text": "انا حاسس بتعب ومش عارف اروح فين",
  "context": {
    "user_profile": { "name": "Ahmed", "medical_condition": "Lower Body Paralysis", "age": 28, "weight": 70, "gender": "male" },
    "relations": {
      "doctor": { "name": "Dr. Smith", "phone": "+201111111" },
      "companions": [{ "name": "Mona", "phone": "+201222222" }]
    },
    "wheelchair_status": { "serial_number": "CHAIR-001", "battery": 80, "connection": "online" },
    "current_health_state": {
      "heart_rate": 110, "temperature": 37.5,
      "mpu_monitoring": { "angle": 45, "fall_detected": true }
    },
    "current_trip": { "is_active": true, "destination": "Hospital", "current_coordinates": { "x": 10.5, "y": 20.2 } },
    "latest_alerts": {
      "heart": { "message": "High Heart Rate Detected", "severity": "critical", "timestamp": "2026-05-28T13:10:00Z" },
      "temperature": null,
      "mpu_monitoring": { "message": "High Fall Risk", "severity": "critical", "timestamp": "2026-05-28T13:15:00Z" },
      "obstacle": { "message": "Stairs ahead", "severity": "medium", "timestamp": "2026-05-28T09:50:00Z" },
      "sos": null,
      "battery": { "message": "Battery below 20%", "severity": "medium", "timestamp": "2026-05-28T08:00:00Z" }
    }
  }
}
```

### Session Management

| Endpoint | Method | Description |
|:---|:---|:---|
| `/api/chatbot/sessions` | `GET` | List all chatbot sessions |
| `/api/chatbot/sessions` | `POST` | Create a new session |
| `/api/chatbot/sessions/{session}` | `GET` | View session with messages |
| `/api/chatbot/sessions/{session}` | `DELETE` | Delete a session |
| `/api/chatbot/messages/{message}/reaction` | `POST` | Like/dislike a bot message |

---

## 8. System Maintenance (Artisan Commands)

| Command | Schedule | Description |
|:---|:---|:---|
| `php artisan data:cleanup` | Daily | Deletes old movement states (>1 month), events (>3 months), aggregated sensor readings (>1 year). |
| `php artisan files:clean-orphans` | Manual | Scans `storage/app/public` and deletes any file not linked in the database (Users, Organizations, Places, Categories, Messages, Posts). |

### Orphan File Protection (Model Observers)

All models with image/file columns have `booted()` observers that automatically delete associated files from storage when a record is deleted via Eloquent:
- `User` → `image`
- `Organization` → `image`
- `Place` → `image`
- `Category` → `image`
- `Message` → `attachment`
- `ChatMessage` → `attachments` (JSON array)
- `Post` → `images` + `files` (JSON arrays)

