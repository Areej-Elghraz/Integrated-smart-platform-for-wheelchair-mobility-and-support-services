# Buildings API Documentation

This document provides a comprehensive guide to managing **Buildings** in the ChairPal system. Buildings are associated with `Organizations` and can contain multiple `Floors` and `Places`.

**Base URL:** `https://chairpal-api.duckdns.org`
**Headers Required:**
- `Accept: application/json`
- `Authorization: Bearer {token}`

**Important Notice regarding Images:**
Whenever creating or updating a building with an image, you **MUST** send the request as `multipart/form-data` instead of `application/json`. The backend will automatically upload and link the image. If you delete or update the building with a new image, the backend will automatically delete the old image file from the storage to save space.

---

## 1. List Buildings for an Organization
Get all buildings belonging to a specific organization. The response will include its floors and floor maps.

- **Endpoint:** `GET /api/organizations/{organization}/buildings`
- **Authorization:** Required (Must be a logged-in user with view access)

### Query Parameters
None

### Response (200 OK)
```json
{
  "message": "Buildings retrieved successfully.",
  "data": [
    {
      "id": 1,
      "name": "Main Building",
      "description": "Headquarters of the organization",
      "latitude": 30.0123,
      "longitude": 31.0456,
      "image": "https://chairpal-api.duckdns.org/storage/buildings/abc.png",
      "organization_id": 1,
      "floors": [
        {
           "id": 1,
           "name": "Ground Floor",
           "number": 1,
           "map": {
              "id": 1,
              "map_file": "https://chairpal-api.duckdns.org/storage/maps/map.png"
           }
        }
      ],
      "created_at": "2026-06-09T10:00:00Z",
      "updated_at": "2026-06-09T10:00:00Z"
    }
  ]
}
```

---

## 2. Create a Building
Add a new building to a specific organization.

- **Endpoint:** `POST /api/organizations/{organization}/buildings`
- **Authorization:** Required (Must have update permissions for the Organization)
- **Headers:** `Content-Type: multipart/form-data` (if uploading `image`)

### Request Body Data (FormData or JSON)
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `name` | String | Yes | Name of the building | max: 255 |
| `description` | String | No | Description of the building | |
| `latitude` | Float | No | Map coordinates (latitude) | numeric |
| `longitude` | Float | No | Map coordinates (longitude) | numeric |
| `image` | File | No | Building Image | jpeg, png, jpg, max: 2MB |

### Response (201 Created)
```json
{
  "message": "Building created successfully.",
  "data": {
    "id": 2,
    "name": "East Wing",
    "description": "East side entrance",
    "latitude": 30.01,
    "longitude": 31.02,
    "image": "https://chairpal-api.duckdns.org/storage/buildings/xyz.jpg",
    "organization_id": 1,
    "created_at": "2026-06-09T10:00:00Z",
    "updated_at": "2026-06-09T10:00:00Z"
  }
}
```

---

## 3. Get a Specific Building
Retrieve the details of a single building by its ID. Will automatically load its floors and floor maps.

- **Endpoint:** `GET /api/buildings/{id}`
- **Authorization:** Required

### Query Parameters
None

### Response (200 OK)
```json
{
  "message": "Building retrieved successfully.",
  "data": {
    "id": 1,
    "name": "Main Building",
    "description": "Headquarters of the organization",
    "latitude": 30.0123,
    "longitude": 31.0456,
    "image": "https://chairpal-api.duckdns.org/storage/buildings/abc.png",
    "organization_id": 1,
    "floors": [],
    "created_at": "2026-06-09T10:00:00Z",
    "updated_at": "2026-06-09T10:00:00Z"
  }
}
```

---

## 4. Update a Building
Update the details of an existing building. Note: If a new `image` is uploaded, the old image file is safely deleted from the server.

- **Endpoint:** `PUT /api/buildings/{id}` *(or `POST` with `_method=PUT` if uploading file)*
- **Authorization:** Required (Must have update permissions for the building)
- **Headers:** `Content-Type: multipart/form-data` (if uploading new `image`)

### Request Body Data
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `name` | String | No | Updated Name | max: 255 |
| `description` | String | No | Updated Description | |
| `latitude` | Float | No | Map coordinates (latitude) | numeric |
| `longitude` | Float | No | Map coordinates (longitude) | numeric |
| `image` | File | No | New Building Image | jpeg, png, jpg |

### Response (200 OK)
```json
{
  "message": "Building updated successfully.",
  "data": {
    "id": 1,
    "name": "Updated Building Name",
    "description": "Updated Description",
    "latitude": 30.0123,
    "longitude": 31.0456,
    "image": "https://chairpal-api.duckdns.org/storage/buildings/new.png",
    "organization_id": 1,
    "created_at": "2026-06-09T10:00:00Z",
    "updated_at": "2026-06-09T10:05:00Z"
  }
}
```

---

## 5. Delete a Building
Delete a specific building. This action will permanently remove the building record and completely delete its image from the server storage.

- **Endpoint:** `DELETE /api/buildings/{id}`
- **Authorization:** Required (Must have delete permissions)

### Request Body Data
None

### Response (200 OK)
```json
{
  "message": "Building deleted successfully.",
  "data": []
}
```
