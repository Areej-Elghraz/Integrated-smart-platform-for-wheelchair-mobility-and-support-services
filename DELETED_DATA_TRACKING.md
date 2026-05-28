# Deleted Data Tracking & Pruning

This document outlines the tables and columns that have been removed from the database schema over the course of the project, the reasons for their removal, and how data pruning is handled periodically.

## Phase 3 & 4: Schema Simplification
During Phase 3 and Phase 4, the database underwent significant simplification to optimize relationships and remove redundant data models.

### Dropped Tables
1. **`emergency_contacts`**
   - **Reason:** Legacy table that became redundant. Emergency notifications (SOS) are now exclusively routed through the `user_friends` system where users with the `companion` role automatically receive SOS notifications.
2. **`disability_types`** & **`user_disability_types`**
   - **Reason:** Consolidated into the `medical_conditions` table to provide a single, unified source of truth for patient health metrics.
3. **`current_vital_states`**
   - **Reason:** Replaced entirely by the `ai_recommendations` table to closely map the AI engine's output schema, which includes more detailed context (`mpu_angle`, `reason`, `recommendation`).

### Dropped Columns (from `users` table)
1. **`blood_type`** (boolean flag)
   - **Reason:** Legacy boolean field that did not accurately capture patient medical context.
2. **`follow_doctor`** (boolean flag)
   - **Reason:** Role-based access and the `user_friends` connection system replaced this flag. Doctors now manage connections dynamically.

## Periodic Data Pruning
Currently, data deletion is handled automatically via **foreign key cascading (`onDelete('cascade')`)**.

1. **User Deletion:** If a user account is deleted, all associated `wheelchairs`, `trips`, `user_friends`, `ai_recommendations`, and `events` are automatically cascade-deleted at the database level.
2. **Telemetry Pruning:** Since AI recommendations (`ai_recommendations`), events (`events`), and raw sensor readings (`sensor_readings`) accumulate rapidly, they must be pruned.
   - *Future Implementation:* A Laravel Scheduler command (`php artisan model:prune`) will be implemented to automatically delete `sensor_readings` older than 30 days, while keeping `sensor_readings_aggregated` for long-term historical trends.
