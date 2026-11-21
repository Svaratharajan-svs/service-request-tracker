## API Request Flow

```text
HTTP Request
     |
     v
Laravel Route
     |
     v
Sanctum Authentication
     |
     v
Form Request Validation
     |
     v
API Controller
     |
     v
Eloquent Models
     |
     v
Database Transaction
     |
     +------ Service Request Update
     |
     +------ Status History Insert
     |
     v
API Resource
     |
     v
JSON Response
```

### Step-by-Step Breakdown

1. **HTTP Request**: The client sends a request to the API.
2. **Laravel Route**: The route matches the request to a controller action.
3. **Sanctum Authentication**: The auth token is verified.
4. **Form Request Validation**: The input data is validated.
5. **API Controller**: The controller handles the request logic.
6. **Eloquent Models**: The models interact with the database.
7. **Database Transaction**: The following operations run atomically:
   - Service Request Update
   - Status History Insert
8. **API Resource**: The result is transformed into the response format.
9. **JSON Response**: The final JSON is returned to the client.