## Floors & Maps Endpoints

### 1. List Floors of an Organization

#### Endpoint Information

- **HTTP Method:** `GET`
- **Full URL:** `/api/organizations/{organization}/floors`
- **Description:** Returns a list of floors belonging to a specific organization.
- **Allowed Roles:** Authenticated user who has view permission on the organization. Note: Users with the `doctor` role are explicitly blocked from viewing or managing floors, maps, and places by the strict system policies.

#### Query Parameters

- `include` (string, optional): Related resources to eager-load (e.g. `map`, `places`, `organization`).

#### Responses

**Success (200 OK):**

```json
{
    "message": "Floors retrieved successfully!",
    "data": [
        {
            "id": 1,
            "organization_id": 1,
            "place_id": null,
            "name": "Ground Floor",
            "number": 1,
            "created_at": "2026-05-20T14:58:20+03:00",
            "updated_at": "2026-05-20T14:58:20+03:00"
        }
    ]
}
```

---

### 2. Create Floor for an Organization

#### Endpoint Information

- **HTTP Method:** `POST`
- **Full URL:** `/api/organizations/{organization}/floors`
- **Description:** Creates a new floor under an organization.
- **Allowed Roles:** Organization owner.

#### Request Body

```json
{
    "name": "Floor 1",
    "number": 1
}
```

**Fields:**

- `name` (string, required): The name of the floor (e.g., "Ground Floor").
- `number` (integer, required): The floor number (e.g., `1`, `2`, `-1` for basement).

#### Validation Rules

- `name`: `required|string|max:255`
- `number`: `required|integer`

#### Responses

**Success (201 Created):**

```json
{
    "message": "Floor created successfully!",
    "data": {
        "id": 1,
        "organization_id": 1,
        "place_id": null,
        "name": "Floor 1",
        "number": 1,
        "created_at": "2026-05-20T14:58:20+03:00",
        "updated_at": "2026-05-20T14:58:20+03:00"
    }
}
```

---

### 3. List Floors of a Place

#### Endpoint Information

- **HTTP Method:** `GET`
- **Full URL:** `/api/places/{place}/floors`
- **Description:** Returns a list of sub-floors inside a specific place.
- **Allowed Roles:** Authenticated user who has view permission on the place.

#### Query Parameters

- `include` (string, optional): Related resources to eager-load (e.g. `map`, `places`, `place`).

#### Responses

**Success (200 OK):**

```json
{
    "message": "Floors retrieved successfully!",
    "data": [
        {
            "id": 2,
            "organization_id": null,
            "place_id": 1,
            "name": "First Sub-floor",
            "number": 2,
            "created_at": "2026-05-20T14:58:20+03:00",
            "updated_at": "2026-05-20T14:58:20+03:00"
        }
    ]
}
```

---

### 4. Create Floor for a Place

#### Endpoint Information

- **HTTP Method:** `POST`
- **Full URL:** `/api/places/{place}/floors`
- **Description:** Creates a new sub-floor under a place.
- **Allowed Roles:** Place owner.

#### Request Body

```json
{
    "name": "First Sub-floor",
    "number": 2
}
```

**Fields:**

- `name` (string, required): The name of the sub-floor.
- `number` (integer, required): The floor number.

#### Validation Rules

- `name`: `required|string|max:255`
- `number`: `required|integer`

#### Responses

**Success (201 Created):**

```json
{
    "message": "Floor created successfully!",
    "data": {
        "id": 2,
        "organization_id": null,
        "place_id": 1,
        "name": "First Sub-floor",
        "number": 2,
        "created_at": "2026-05-20T14:58:20+03:00",
        "updated_at": "2026-05-20T14:58:20+03:00"
    }
}
```

---

### 5. Get Floor Details

#### Endpoint Information

- **HTTP Method:** `GET`
- **Full URL:** `/api/floors/{floor}`
- **Description:** Retrieves details of a specific floor.
- **Allowed Roles:** Authenticated user who has view permission on the parent organization/place.

#### Query Parameters

- `include` (string, optional): Related resources to eager-load (e.g. `map`, `places`).

#### Responses

**Success (200 OK):**

```json
{
    "message": "Floor retrieved successfully!",
    "data": {
        "id": 1,
        "organization_id": 1,
        "place_id": null,
        "name": "Floor 1",
        "number": 1,
        "created_at": "2026-05-20T14:58:20+03:00",
        "updated_at": "2026-05-20T14:58:20+03:00"
    }
}
```

---

### 6. Update Floor

#### Endpoint Information

- **HTTP Method:** `PUT`
- **Full URL:** `/api/floors/{floor}`
- **Description:** Updates floor properties.
- **Allowed Roles:** Floor owner (organization or place owner).

#### Request Body

```json
{
    "name": "Updated Floor Name"
}
```

**Fields:**

- `name` (string, optional)
- `number` (integer, optional)

#### Validation Rules

- `name`: `sometimes|required|string|max:255`
- `number`: `sometimes|required|integer`

#### Responses

**Success (200 OK):**

```json
{
    "message": "Floor updated successfully!",
    "data": {
        "id": 1,
        "organization_id": 1,
        "place_id": null,
        "name": "Updated Floor Name",
        "number": 1,
        "created_at": "2026-05-20T14:58:20+03:00",
        "updated_at": "2026-05-20T14:58:20+03:00"
    }
}
```

---

### 7. Delete Floor

#### Endpoint Information

- **HTTP Method:** `DELETE`
- **Full URL:** `/api/floors/{floor}`
- **Description:** Deletes a specific floor and its associated map from storage and database.
- **Allowed Roles:** Floor owner.

#### Responses

**Success (200 OK):**

```json
{
    "message": "Floor deleted successfully!",
    "data": []
}
```

---

### 8. Upload / Update Floor Map

#### Endpoint Information

- **HTTP Method:** `POST`
- **Full URL:** `/api/floors/{floor}/map`
- **Description:** Uploads or overwrites a map image for a floor. The map file is stored in public storage and metadata is saved in the database.
- **Allowed Roles:** Floor owner.

#### Request Body (Multipart/form-data)

- `map_file` (file, required): The map image file (PNG, JPG, JPEG, GIF).
- `yaml_file` (file, required): The yaml file contain needed data about map_file.
- `width` (double, required): The width of the map layout in meters.
- `height` (double, required): The height of the map layout in meters.
- `resolution` (double, required): Grid resolution in meters per pixel. Defaults to `0.05`.
- `origin` (array, required): Coordinate array representing [x, y, z] origin (JSON format).

#### Validation Rules

- `map_file`: `required|image|mimes:png,jpg,jpeg,gif|max:2048`
- `yaml_file`: `required_without:width,height,resolution,origin|file|max:5120`
- `width`: `required_without:yaml_file|numeric`
- `height`: `required_without:yaml_file|numeric`
- `resolution`: `required_without:yaml_file|numeric`
- `origin`: `required_without:yaml_file|array`

#### Responses

**Success (201 Created):**

```json
{
    "message": "Map created successfully!",
    "data": {
        "id": 2,
        "floor_id": 1,
        "map_file": "http://127.0.0.1:8000/storage/maps/65BMqFKP8ApvnQqTBu47q2UA0sOT19NbCSweryQh.png",
        "extension": "png",
        "width": 1920,
        "height": 1080,
        "origin": [-1.5, -1, 0],
        "resolution": 0.1,
        "created_at": "2026-05-27T15:41:37.000000Z",
        "updated_at": "2026-05-27T16:25:03.000000Z",
        "yaml_file": "http://127.0.0.1:8000/storage/maps/T23R7no0uXohuoj1AoXQW5W8JD5tycinz6p17xh9.txt",
        "yaml_data": {
            "image": "map.pgm",
            "mode": "trinary",
            "resolution": 0.1,
            "origin": [-1.5, -1, 0],
            "negate": 0,
            "occupied_thresh": 0.65,
            "free_thresh": 0.196
        },
        "mode": "trinary",
        "negate": 0,
        "occupied_thresh": 0.65,
        "free_thresh": 0.196
    }
}
```

---

### 9. Get Floor Map

#### Endpoint Information

- **HTTP Method:** `GET`
- **Full URL:** `/api/floors/{floor}/map`
- **Description:** Retrieves the map associated with a specific floor.
- **Allowed Roles:** Authenticated user who has view permission on the floor.

#### Responses

**Success (200 OK):**

```json
{
    "message": "Map retrieved successfully!",
    "data": {
        "id": 1,
        "floor_id": 1,
        "map_file": "http://localhost/storage/maps/map.png",
        "extension": "png",
        "width": 100.5,
        "height": 80.2,
        "resolution": 0.05,
        "origin": [-10, -10, 0],
        "created_at": "2026-05-20T14:58:20+03:00",
        "updated_at": "2026-05-20T14:58:20+03:00"
    }
}
```

---

### 10. Delete Floor Map

#### Endpoint Information

- **HTTP Method:** `DELETE`
- **Full URL:** `/api/floors/{floor}/map`
- **Description:** Deletes the map associated with a floor from the storage and database.
- **Allowed Roles:** Floor owner.

#### Responses

**Success (200 OK):**

```json
{
    "message": "Map deleted successfully!",
    "data": []
}
```
