## Places Endpoints

### 1. List Places
#### Endpoint Information
- **HTTP Method:** `GET`
- **Full URL:** `/api/places`
- **Description:** Returns places based on optional filters, mapped to specific categories or organizations.

#### Request Details
- **Query Parameters:**
  - `category_id` (integer, optional)
  - `organization_id` (integer, optional)
  - `country_id` (integer, optional)
  - `has_categories` (boolean, optional)
  - `include` (string, optional): E.g., `categories,organization`

#### Responses
**Success (200 OK):**
```json
{
  "message": "Places retrieved successfully.",
  "data": [
    {
      "id": 1,
      "name": "Central Park Path",
      "average_rating": 5.0,
      "visitors_count": 55,
      "category_id": 2,
      "organization_id": 1
    }
  ]
}
```

### 2. Create Place
#### Endpoint Information
- **HTTP Method:** `POST`
- **Full URL:** `/api/places`
- **Description:** Stores a new place attached to the user or an organization.

#### Request Body
```json
{
  "name": "Accessibility Ramp A",
  "category_name": "Ramps",
  "country_name": "USA",
  "city_name": "New York",
  "country_id": 1,
  "city_id": 1,
  "organization_id": 1,
  "description": "Ramp at the main entrance",
  "image": "(file)",
  "floor_id": 1,
  "x": 10.5,
  "y": 20.0,
  "z": 1.2,
  "rotation": 90.0
}
```
**Fields:**
- `name` (string, required): Place name.
- `category_id` (integer, optional): Optional if category_name passed.
- `category_name` (string, required_without:category_id)
- `country_name` (string, required_without:country_id)
- `city_name` (string, required_without:city_id)
- `country_id` (integer, required_without:country_name)
- `city_id` (integer, required_without:city_name)
- `organization_id` (integer, optional): Org mapping.
- `floor_id` (integer, optional): The ID of the floor this place is located on.
- `points` (array, optional): Array of coordinate objects `[{"x": 2, "y": 3}, ...]`. If passed without `x` and `y`, the API automatically calculates the geometric center of these points and saves it as `x` and `y`.
- `x` (numeric, optional/required): X coordinate for indoor positioning/navigation. Required if `points` is not provided.
- `y` (numeric, optional/required): Y coordinate for indoor positioning/navigation. Required if `points` is not provided.
- `z` (numeric, optional): Z coordinate for indoor positioning/navigation.
- `rotation` (numeric, optional): Orientation/rotation angle of the place.

#### Validation Rules
- `name`: `required|string|max:255`
- `category_name`: `required_without:category_id|string|max:255`
- `country_name`: `required_without:country_id|string|max:255`
- `city_name`: `required_without:city_id|string|max:255`
- `country_id`: `required_without:country_name|exists:countries,id`
- `city_id`: `required_without:city_name|exists:cities,id`
- `floor_id`: `nullable|exists:floors,id`
- `x`: `required|numeric`
- `y`: `required|numeric`
- `z`: `nullable|numeric`
- `rotation`: `nullable|numeric`


### 3. Get Place Details
#### Endpoint Information
- **HTTP Method:** `GET`
- **Full URL:** `/api/places/{id}`

#### Responses
**Success (200 OK):**
```json
{
  "data": {
    "id": 1,
    "name": "Central Park Path"
  }
}
```


### 4. Update Place
#### Endpoint Information
- **HTTP Method:** `PUT`
- **Full URL:** `/api/places/{id}`

#### Request Body
```json
{
  "name": "Updated Place Name",
  "description": "Updated description",
  "floor_id": 2,
  "x": 14.2,
  "y": 28.1,
  "z": 0.0,
  "rotation": 180.0
}
```
#### Business Logic Notes
- You must own the place to update it (`PlacePolicy@update`). Organization users can update places strictly mapped to their organizations.


### 5. Add Review to Place
#### Endpoint Information
- **HTTP Method:** `POST`
- **Full URL:** `/api/places/{place}/reviews`

#### Request Body
```json
{
  "rating": 4,
  "comment": "Good incline, quite smooth."
}
```
#### Validation Rules
- `rating`: `required|integer|min:1|max:5`
- `comment`: `nullable|string`


### 6. Delete Review
#### Endpoint Information
- **HTTP Method:** `DELETE`
- **Full URL:** `/api/reviews/{review}`

#### Authorization Logic
- Controlled strictly by `ReviewPolicy@delete`.
- A `user` role may delete their own reviews.
- An `organization` role may delete reviews belonging to their organizations.


### 7. Toggle Favorite for a Place
#### Endpoint Information
- **HTTP Method:** `POST`
- **Full URL:** `/api/places/{place}/favorite`


### 8. Visit Place
#### Endpoint Information
- **HTTP Method:** `POST`
- **Full URL:** `/api/places/{place}/visit`
- **Description:** Logs a user's metric interaction for a place. Allowed exclusively to roles: `user`.
