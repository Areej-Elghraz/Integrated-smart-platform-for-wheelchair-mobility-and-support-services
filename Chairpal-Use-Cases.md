# Chairpal System Use Cases & CRUD Operations

This document outlines the detailed **Create, Read, Update, and Delete (CRUD)** operations assigned to each role within the system. It strictly adheres to Role-Based Access Control (RBAC) best practices and defines the exact scope for the cross-functional development teams.

---

## 1. Patient (Primary User)
The patient holds the highest authority over their personal data, hardware control, and medical connections.

| Feature Module | Create / Add | Update | Delete / Remove | Read / View |
| :--- | :--- | :--- | :--- | :--- |
| **Account & Profile** | Register as a Patient. | Update profile data (height, weight, password). | Delete account permanently. | View personal profile. |
| **Wheelchair & Trips** | Start manual/autonomous trip, pair a wheelchair. | Control wheelchair movement (via LAN). | Unassign wheelchair, terminate active trips. | View real-time movement, trip history, sensors. |
| **Private Spatial Data** | Create private organization, building, floor, map, places. | Update private places and maps. | Delete private building/floor/map/place. | View personal private locations. |
| **Public Spatial Data** | Add private "Places" within a public floor. | *Not allowed* (cannot modify public assets). | *Not allowed* (cannot delete public assets). | View public buildings, floors, and maps. |
| **Connections** | Send Doctor request, Accept Companion request. | - | Remove an assigned Doctor or Companion. | View friends and active connections list. |
| **Community** | Add a Post, Comment, Like a Post. | Edit own posts/comments. | Delete own posts/comments, Hide other posts. | Read community feed and interactions. |
| **Chat & Support** | Send messages to connections or AI Chatbot. | Edit sent messages. | Delete sent messages. | Read incoming messages and AI replies. |
| **Emergency (SOS)** | Trigger manual SOS alert. | - | Cancel active SOS alert. | Receive localized alerts. |

---

## 2. Companion
The companion role focuses exclusively on patient monitoring and emergency response, without hardware movement privileges.

| Feature Module | Create / Add | Update | Delete / Remove | Read / View |
| :--- | :--- | :--- | :--- | :--- |
| **Account & Profile** | Register as a Companion. | Update profile data. | Delete account permanently. | View personal profile. |
| **Connections** | Send follow request to a Patient. | - | Remove a Patient from monitoring list. | View assigned Patient profile. |
| **Monitoring Dashboard**| - | - | - | View Patient's Live Location, Vitals, active SOS alerts. |
| **Wheelchair Control** | *Not allowed*. | *Not allowed*. | *Not allowed*. | *Not allowed*. |
| **Community & Chat** | Add Posts, Comments, Likes. Send messages. | Edit own posts/comments/messages. | Delete own posts/comments/messages. | Read community feed, Chat with Patient. |

---

## 3. Doctor
The doctor serves as a medical supervisor, analyzing historical data and AI-driven health risk assessments.

| Feature Module | Create / Add | Update | Delete / Remove | Read / View |
| :--- | :--- | :--- | :--- | :--- |
| **Account & Profile** | Register as a Doctor. | Update profile data. | Delete account permanently. | View personal profile. |
| **Patient Management** | Accept/Reject Patient requests. | - | Remove a Patient from supervision list. | View assigned patients, categorized by risk level. |
| **Medical Dashboard** | - | - | - | View AI Recommendations, Vital charts (Heart rate, Temp). |
| **Community & Chat** | Add medical advice posts. Send messages. | Edit own posts/comments. | Delete own posts/comments. | Chat with supervised patients. |

---

## 4. Organization Admin
Manages the hierarchical indoor mapping data for public access points (e.g., hospitals, malls).

| Feature Module | Create / Add | Update | Delete / Remove | Read / View |
| :--- | :--- | :--- | :--- | :--- |
| **Account & Profile** | Register as an Org Admin. | Update profile data. | Delete account permanently. | View personal profile. |
| **Spatial Hierarchy** | Create Organization, Buildings, Floors, Places, Maps. | Update building/floor/place details. | Delete buildings, floors, places, maps. | View full spatial structure and maps. |
| **Admin Dashboard** | - | - | - | View visitor statistics and place reviews. |

---

## 5. IoT System (Wheelchair Hardware)
Acts as an automated client streaming telemetry data continuously to the cloud.

| Feature Module | Create / Add | Update | Delete / Remove | Read / View |
| :--- | :--- | :--- | :--- | :--- |
| **Telemetry & Sensors** | Push absolute (X, Y) coordinates, push sensor vitals. | - | - | Receive connection state updates. |
| **Trip Lifecycle** | - | Send 'Completed' or 'Failed' status. | - | Receive start command/path from Backend. |
| **Event Logging** | Trigger SOS, log Obstacle encounters. | Update duplicate obstacle timestamp (Deduplication). | - | - |
| **Mapping (LIDAR)** | Upload generated Floor Map to backend. | - | - | Receive map initialization signal. |

---

## 6. Cross-Functional Teams Responsibilities

| Team | Core Responsibilities & Implementation Scope |
| :--- | :--- |
| **Flutter (Mobile)** | 1. Develop role-specific UI Dashboards.<br>2. Implement local LAN sockets for Zero-Latency Joystick control.<br>3. Manage interactive indoor map rendering with selectable coordinate points.<br>4. Consume WebSockets (Reverb) for Live GPS/Indoor tracking and SOS overlays. |
| **Backend (Laravel)** | 1. Design 3NF Database and secure REST APIs (Sanctum for Users, API-Key for IoT).<br>2. Implement Event-Driven WebSockets (Reverb) for instant SOS broadcasting.<br>3. Manage high-traffic telemetry via Queues and Data Aggregation (30s intervals).<br>4. Implement automated Cron Jobs for old sensor data pruning (DB maintenance). |
| **AI (Machine Learning)**| 1. Develop NLP Chatbot to guide users and answer app-related queries.<br>2. Design Risk Classification Model based on Heart Rate, Temp, and MPU (tilt) thresholds to automatically detect falls/faints. |
| **Python / Hardware** | 1. Interface with physical sensors (Heart, Temp, MPU) via microcontrollers.<br>2. Execute LIDAR mapping logic (ROS) and format map data for backend uploads.<br>3. Listen to LAN commands from Flutter to drive motors (Manual) or navigate to points (Autonomous).<br>4. Stream secure Telemetry payloads to Laravel API. |
| **UI / UX** | 1. Design distinct, intuitive User Journeys for all 4 roles.<br>2. Map the Autonomous Mapping flow visually (Approval -> Scan -> Upload).<br>3. Design high-contrast Emergency (SOS) UI states for companions and users. |
