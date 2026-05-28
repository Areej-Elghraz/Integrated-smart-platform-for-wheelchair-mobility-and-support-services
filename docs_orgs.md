## Organizations Endpoints

### 1. List Organizations
#### Endpoint Information
- **HTTP Method:** `GET`
- **Full URL:** `/api/organizations`
- **Description:** Retrieves a paginated list of organizations visible to the authenticated user.

#### Authentication & Authorization
- **Requires Authentication:** Yes
- **Allowed Roles:** User, Organization (sees only their own)

#### Request Details
- **Headers:** `Accept: application/json`, `Authorization: Bearer {token}`
- **Query Parameters:**
  - `category_id` (integer, optional)
  - `country_id` (integer, optional)
  - `city_id` (integer, optional)
  - `has_categories` (boolean, optional)
  - `include` (string, optional): E.g., `categories,places`

#### Responses
**Success (200 OK):**
```json
{
  "message": "Organizations retrieved successfully.",
  "data": [
    {
      "id": 1,
      "name": "HealthCare Partners",
      "average_rating": 4.5,
      "visitors_count": 120,
      "city_id": 5,
      "country_id": 2
    }
  ]
}
```

### 2. Create Organization
#### Endpoint Information
- **HTTP Method:** `POST`
- **Full URL:** `/api/organizations`
- **Description:** Registers a new organization under the user's account.

#### Authentication & Authorization
- **Requires Authentication:** Yes
- **Authorization Logic:** Verified via `OrganizationPolicy@create`.
- **Allowed Roles:** User (acting as owner), Organization (if permitted multiple under limits)

#### Request Body
```json
{
  "name": "New Organization",
  "category_name": "Healthcare",
  "country_name": "USA",
  "city_name": "New York",
  "country_id": 1,
  "city_id": 1,
  "description": "Accessible Healthcare Organization",
  "image": "(file)"
}
```
**Fields:**
- `name` (string, required): Org name.
- `category_id` (integer, optional): Existing category.
- `category_name` (string, required_without:category_id): Dynamic category creation.
- `country_name` (string, required_without:country_id): Registers or finds geo entry.
- `city_name` (string, required_without:city_id)
- `country_id` (integer, required_without:country_name)
- `city_id` (integer, required_without:city_name)

#### Validation Rules
- `name`: `required|string|max:255`
- `category_id`: `nullable|exists:categories,id`
- `category_name`: `required_without:category_id|string|max:255`
- `country_name`: `required_without:country_id|string|max:255`
- `city_name`: `required_without:city_id|string|max:255`
- `country_id`: `required_without:country_name|exists:countries,id`
- `city_id`: `required_without:city_name|exists:cities,id`

#### Business Logic Notes
- Resolves mapping `city_name` and `country_name` automatically into IDs using `GeoService`.
- Resolves `category_name` to `category_id` seamlessly.


### 3. Get Organization Details
#### Endpoint Information
- **HTTP Method:** `GET`
- **Full URL:** `/api/organizations/{id}`
- **Description:** Returns detailed data for a specific organization.

#### Request Details
- **Path Parameters:**
  - `id` (integer, required): Organization ID.
- **Query Parameters:**
  - `include` (string, optional): `places,categories,reviews,owner`

#### Responses
**Success (200 OK):**
```json
{
  "data": {
    "id": 1,
    "name": "HealthCare Partners"
  }
}
```


### 4. Update Organization
#### Endpoint Information
- **HTTP Method:** `PUT`
- **Full URL:** `/api/organizations/{id}`

#### Authentication & Authorization
- **Authorization Logic:** Verified by `OrganizationPolicy@update`. Must be the owner.

#### Request Body
```json
{
  "name": "Updated Org Name",
  "description": "Updated info"
}
```
**Fields:**
- `name` (string, sometimes)
- `description` (string, sometimes)

#### Validation Rules
- `name`: `sometimes|string|max:255`
- `description`: `nullable|string`


### 5. Add Review to Organization
#### Endpoint Information
- **HTTP Method:** `POST`
- **Full URL:** `/api/organizations/{id}/reviews`
- **Description:** Leaves a rating/review on the specified organization.

#### Authentication & Authorization
- **Allowed Roles:** Only `user` role (`ReviewPolicy@create`).

#### Request Body
```json
{
  "rating": 5,
  "comment": "Incredible accessibility inside."
}
```
**Fields:**
- `rating` (numeric, required): Rating from 1 to 5.
- `comment` (string, optional): Text review.


### 6. Toggle Favorite Organization
#### Endpoint Information
- **HTTP Method:** `POST`
- **Full URL:** `/api/organizations/{id}/favorite`

#### Responses
**Success (200 OK):**
```json
{
  "message": "Added to favorites successfully.",
  "data": []
}
```


### 7. Visit Organization
#### Endpoint Information
- **HTTP Method:** `POST`
- **Full URL:** `/api/organizations/{id}/visit`
- **Description:** Records that an authenticated user visited this organization online/offline.

#### Authentication & Authorization
- **Allowed Roles:** Only `user` role (`OrganizationPolicy@visit`).
