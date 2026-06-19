**Missing Use Cases**

**User, Companion, Doctor & Organization Admin Use Cases**

**Authentication & Profile Management**

**Actor**

User, Companion, Doctor, Organization Admin

**Goal**

Register an account, log in securely, and manage profile information.

**Preconditions**

The user has downloaded the application.

**Main Flow**

1. The actor opens the application and selects Sign Up or Log In.
2. The actor enters their credentials (email/username and password).
3. The system authenticates the actor and issues a secure token.
4. The actor navigates to the Profile section.
5. The actor updates their profile details or changes their password.
6. The system saves the changes successfully.

**Alternative Flow**

- If authentication fails, an error message is displayed (e.g., incorrect password).
- If the user forgets their password, they can initiate a password reset via OTP.

**Postcondition**

The actor is securely logged in and their profile is up to date.

**Communicate via Private Chat**

**Actor**

User, Companion, Doctor

**Goal**

Send and receive private messages with connected friends.

**Preconditions**

The actor is logged in and has at least one accepted connection (friend).

**Main Flow**

1. The actor opens the Chats section.
2. The system displays active chat conversations.
3. The actor selects a connected friend to start or resume a chat.
4. The actor types and sends a message.
5. The message is delivered in real-time.

**Alternative Flow**

- If the connection was removed, the chat is disabled or an error message is shown.

**Postcondition**

The message is successfully sent and added to the chat history.

**Interact with Community Feed**

**Actor**

User, Companion, Doctor

**Goal**

Engage with the community by creating posts, liking, and commenting.

**Preconditions**

The actor is logged in.

**Main Flow**

1. The actor opens the Community section.
2. The system displays a paginated feed of community posts.
3. The actor creates a new post with text content.
4. The actor likes or comments on existing posts.
5. The system saves and displays the new interactions.

**Alternative Flow**

- The actor chooses to hide a specific post from their feed.

**Postcondition**

The community feed is updated with the actor's new post or interactions.

**User Use Cases**

**Manage Connections**

**Actor**

User

**Goal**

Accept, reject, or remove connection requests from Companions and Doctors.

**Preconditions**

User is logged in and has pending connection requests or existing connections.

**Main Flow**

1. The user opens the connections/friends section.
2. The system displays pending requests from Companions or Doctors.
3. The user selects to accept or reject a request.
4. The user views the list of current connections.
5. The user selects an existing connection and chooses to remove it.
6. The system updates the connection status.

**Alternative Flow**

- If there is a network issue, the action fails and the user is prompted to retry.

**Postcondition**

The user's connections are updated, and removed users can no longer access the user's data.

**Use AI Chatbot Assistant**

**Actor**

User

**Goal**

Interact with the AI Chatbot for quick assistance and application guidance.

**Preconditions**

The user is logged in.

**Main Flow**

1. The user opens the AI Chatbot section.
2. The user starts a new session or continues an existing one.
3. The user types a question or asks for guidance.
4. The Chatbot processes the request and replies with helpful information.

**Alternative Flow**

- If the AI service is unreachable, a temporary unavailability message is shown.

**Postcondition**

The user receives the needed guidance from the AI Chatbot.

**Create Private Spatial Hierarchy**

**Actor**

User

**Goal**

Create private organizations, buildings, and floors for personal use (e.g., home).

**Preconditions**

User is logged in.

**Main Flow**

1. The user navigates to the spatial management section.
2. The user creates a new private Organization.
3. The user adds a Building to the private Organization.
4. The user adds a Floor to the Building.
5. The system saves the private hierarchy, visible only to the user.

**Alternative Flow**

- If naming validation fails, the user is prompted to correct the input.

**Postcondition**

The private spatial hierarchy is successfully created.

**Initiate Floor Mapping (LIDAR)**

**Actor**

User, Organization Admin

**Goal**

Generate an indoor map for a specific floor using the wheelchair's LIDAR sensors.

**Preconditions**

Actor is logged in, the wheelchair is connected, and the actor has permission for the selected floor (User for private, Organization Admin for public).

**Main Flow**

1. The actor selects a floor that does not have a map.
2. The actor requests mapping permission from the system.
3. The system validates the permission and approves.
4. A "Start Mapping" button appears.
5. The actor presses the button, and the wheelchair begins generating the map via LIDAR.
6. Upon completion, the wheelchair uploads the map to the backend.

**Alternative Flow**

- If permission is denied, an error message is displayed and mapping cannot start.
- If mapping fails mid-way, the wheelchair reports a failure status.

**Postcondition**

A high-resolution floor map is securely saved in the system and linked to the floor.

**Companion Use Cases**

**Track Patient Live Location**

**Actor**

Companion

**Goal**

View the patient's real-time movement on the indoor map or GPS during active trips or SOS.

**Preconditions**

Companion is logged in and linked to the User.

**Main Flow**

1. The companion opens the tracking dashboard.
2. The system establishes a WebSocket connection (Laravel Reverb).
3. The companion views the patient's live location on the indoor map or GPS.
4. The location updates continuously in real-time.

**Alternative Flow**

- If the wheelchair loses connection, the companion is notified that live tracking is paused.

**Postcondition**

The companion accurately tracks the patient's movement.

**Doctor Use Cases**

**View AI Recommendations and Risk Levels**

**Actor**

Doctor

**Goal**

Monitor AI-generated risk levels and recommendations for supervised patients.

**Preconditions**

Doctor is logged in and has accepted patients.

**Main Flow**

1. The doctor opens the Doctor Dashboard.
2. The system displays a statistical overview of all supervised patients, categorized by risk level (Normal, Medium, Critical).
3. The doctor selects a specific patient.
4. The doctor views the AI-generated recommendations and risk assessments.

**Alternative Flow**

- If no new data is available, the last known risk level and timestamp are shown.

**Postcondition**

The doctor is informed about the AI assessments of their patients.

**Organization Admin Use Cases**

**Create and Manage Organization**

**Actor**

Organization Admin

**Goal**

Register and manage the details of a public organization.

**Preconditions**

The user has the Organization Admin role.

**Main Flow**

1. The Organization Admin navigates to the organization management dashboard.
2. Creates a new Organization by providing necessary details.
3. Manages existing organization details and settings.
4. The system saves the updates.

**Alternative Flow**

- Validation errors prompt the admin to correct missing or invalid fields.

**Postcondition**

The public organization is successfully created and updated.

**View Organization Dashboard & Activities**

**Actor**

Organization Admin

**Goal**

Monitor an overview of buildings, places, floors, maps, and real-time activities within their organization.

**Preconditions**

The Organization Admin is logged in.

**Main Flow**

1. The Organization Admin opens the Organization Dashboard.
2. The system aggregates data related to the organization.
3. The admin views statistics on added buildings, places, floors, and maps.
4. The admin monitors real-time activities within the organization's premises.

**Alternative Flow**

- If no buildings exist yet, the dashboard displays an empty state with a prompt to add one.

**Postcondition**

The Organization Admin gains a comprehensive overview of the organization's spatial data and activity.
