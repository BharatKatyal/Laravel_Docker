# Todo API - CRUD Operations

This document describes all available CRUD operations for the Todo API.

## Table of Contents
- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Base URL](#base-url)
- [API Endpoints](#endpoints)
- [Validation Rules](#validation-rules)
- [Error Responses](#error-responses)
- [Testing with Postman](#testing-with-postman)

---

## Prerequisites

Before running the Todo API, ensure you have the following installed:

- **Docker** (version 20.10 or higher)
- **Docker Compose** (optional, for easier setup)
- **MySQL 8.0** (running in Docker or locally)

---

## Quick Start

### 1. Start MySQL Database (if not already running)

```bash
docker run -d \
  --name laravel-app-docker-mysql \
  -p 3306:3306 \
  -e MYSQL_ROOT_PASSWORD=root_password \
  -e MYSQL_DATABASE=todo_db \
  mysql:8.0
```

### 2. Build the Docker Image

```bash
docker build -t my-laravel-app .
```

### 3. Run the Application Container

```bash
docker run -d -p 8080:8080 \
  -e APP_KEY=base64:Na0tjOXG5jsSESnbeVc0SMxZ6dQPwGCCE8QSxWCMCc4= \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=host.docker.internal \
  -e DB_PORT=3306 \
  -e DB_DATABASE=todo_db \
  -e DB_USERNAME=root \
  -e DB_PASSWORD=root_password \
  my-laravel-app
```

### 4. Verify the API is Running

```bash
curl http://localhost:8080/api/todos
```

You should see an empty array `[]` or a list of todos if any exist.

---

## Base URL

```
http://localhost:8080/api
```

**Note:** All API endpoints are prefixed with `/api`

---

## Endpoints

All endpoints return JSON responses.

### 1. Get All Todos

**Endpoint:** `GET /todos`

**Description:** Retrieve a list of all todos.

**Request:**
```bash
curl http://localhost:8080/api/todos
```

**Response:** `200 OK`
```json
[
  {
    "id": 1,
    "title": "Buy groceries",
    "description": "Milk, eggs, bread",
    "completed": false,
    "created_at": "2026-02-03T20:50:19.000000Z",
    "updated_at": "2026-02-03T20:50:19.000000Z"
  },
  {
    "id": 2,
    "title": "Learn Docker",
    "description": "Master containerization",
    "completed": true,
    "created_at": "2026-02-03T20:54:50.000000Z",
    "updated_at": "2026-02-03T20:55:04.000000Z"
  }
]
```

---

### 2. Get Single Todo

**Endpoint:** `GET /todos/{id}`

**Description:** Retrieve a specific todo by ID.

**Request:**
```bash
curl http://localhost:8080/api/todos/1
```

**Response:** `200 OK`
```json
{
  "id": 1,
  "title": "Buy groceries",
  "description": "Milk, eggs, bread",
  "completed": false,
  "created_at": "2026-02-03T20:50:19.000000Z",
  "updated_at": "2026-02-03T20:50:19.000000Z"
}
```

---

### 3. Create Todo

**Endpoint:** `POST /todos`

**Description:** Create a new todo item.

**Request:**
```bash
curl -X POST http://localhost:8080/api/todos \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Learn Docker",
    "description": "Master containerization",
    "completed": false
  }'
```

**Request Body:**
```json
{
  "title": "Learn Docker",           // Required: string, max 255 characters
  "description": "Master containerization",  // Optional: string
  "completed": false                 // Optional: boolean, default: false
}
```

**Response:** `201 Created`
```json
{
  "id": 4,
  "title": "Learn Docker",
  "description": "Master containerization",
  "completed": false,
  "created_at": "2026-02-03T20:54:50.000000Z",
  "updated_at": "2026-02-03T20:54:50.000000Z"
}
```

---

### 4. Update Todo

**Endpoint:** `PUT /todos/{id}` or `PATCH /todos/{id}`

**Description:** Update an existing todo item.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/todos/4 \
  -H "Content-Type: application/json" \
  -d '{
    "completed": true
  }'
```

**Request Body (all fields optional):**
```json
{
  "title": "Updated title",          // Optional: string, max 255 characters
  "description": "Updated description",  // Optional: string
  "completed": true                  // Optional: boolean
}
```

**Response:** `200 OK`
```json
{
  "id": 4,
  "title": "Learn Docker",
  "description": "Master containerization",
  "completed": true,
  "created_at": "2026-02-03T20:54:50.000000Z",
  "updated_at": "2026-02-03T20:55:04.000000Z"
}
```

---

### 5. Delete Todo

**Endpoint:** `DELETE /todos/{id}`

**Description:** Delete a todo item.

**Request:**
```bash
curl -X DELETE http://localhost:8080/api/todos/3
```

**Response:** `204 No Content`

---

## Validation Rules

### Create Todo (POST)
- `title`: **required**, string, max 255 characters
- `description`: optional, string
- `completed`: optional, boolean

### Update Todo (PUT/PATCH)
- `title`: optional, string, max 255 characters
- `description`: optional, string
- `completed`: optional, boolean

---

## Error Responses

### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\Todo] {id}"
}
```

### 422 Validation Error
```json
{
  "message": "The title field is required.",
  "errors": {
    "title": [
      "The title field is required."
    ]
  }
}
```

---

## Testing with Postman

### Import the Postman Collection

1. Open Postman
2. Click **Import** button
3. Select the file: `Todo_API.postman_collection.json`
4. The collection will be imported with all endpoints pre-configured

### Environment Variables

The Postman collection uses the following variable:
- `base_url`: `http://localhost:8080` (default)

You can modify this in Postman's environment settings if your API runs on a different port.

---

## Complete Testing Workflow

Here's a complete workflow to test all CRUD operations:

```bash
# 1. Get all todos (should be empty initially)
curl http://localhost:8080/api/todos

# 2. Create first todo
curl -X POST http://localhost:8080/api/todos \
  -H "Content-Type: application/json" \
  -d '{"title":"Buy groceries","description":"Milk, eggs, bread","completed":false}'

# 3. Create second todo
curl -X POST http://localhost:8080/api/todos \
  -H "Content-Type: application/json" \
  -d '{"title":"Learn Docker","description":"Master containerization","completed":false}'

# 4. Get all todos (should show 2 todos)
curl http://localhost:8080/api/todos

# 5. Get single todo by ID
curl http://localhost:8080/api/todos/1

# 6. Update todo - mark as completed
curl -X PUT http://localhost:8080/api/todos/1 \
  -H "Content-Type: application/json" \
  -d '{"completed":true}'

# 7. Update todo - change title and description
curl -X PUT http://localhost:8080/api/todos/2 \
  -H "Content-Type: application/json" \
  -d '{"title":"Learn Kubernetes","description":"After mastering Docker"}'

# 8. Verify updates
curl http://localhost:8080/api/todos

# 9. Delete a todo
curl -X DELETE http://localhost:8080/api/todos/1

# 10. Verify deletion
curl http://localhost:8080/api/todos
```

---

## Docker Management Commands

### View Running Containers
```bash
docker ps
```

### View Container Logs
```bash
docker logs <container_id>
```

### Stop the Application
```bash
docker stop <container_id>
```

### Remove the Container
```bash
docker rm <container_id>
```

### Rebuild After Code Changes
```bash
# Stop and remove old container
docker stop <container_id> && docker rm <container_id>

# Rebuild image
docker build -t my-laravel-app .

# Run new container
docker run -d -p 8080:8080 \
  -e APP_KEY=base64:Na0tjOXG5jsSESnbeVc0SMxZ6dQPwGCCE8QSxWCMCc4= \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=host.docker.internal \
  -e DB_PORT=3306 \
  -e DB_DATABASE=todo_db \
  -e DB_USERNAME=root \
  -e DB_PASSWORD=root_password \
  my-laravel-app
```

---

## Troubleshooting

### API Returns Connection Refused
- Ensure the container is running: `docker ps`
- Check container logs: `docker logs <container_id>`
- Verify port 8080 is not in use by another application

### Database Connection Errors
- Ensure MySQL container is running
- Verify `host.docker.internal` resolves correctly (Mac/Windows)
- For Linux, use `--add-host=host.docker.internal:host-gateway` flag

### 404 Not Found on API Endpoints
- Verify you're using the correct base URL: `http://localhost:8080/api`
- Check that routes are cached: `docker exec <container_id> php artisan route:list`

---

## Database Schema

### Todos Table Structure

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | NO | AUTO_INCREMENT | Primary key |
| title | varchar(255) | NO | - | Todo title |
| description | text | YES | NULL | Todo description |
| completed | tinyint(1) | NO | 0 | Completion status |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Last update timestamp |

---

## Additional Resources

- **Laravel Documentation**: https://laravel.com/docs/12.x
- **Laravel Pulse**: http://localhost:8080/pulse (monitoring dashboard)
- **Postman Collection**: `Todo_API.postman_collection.json`

---

## Support

For issues or questions:
1. Check the [Troubleshooting](#troubleshooting) section
2. Review container logs: `docker logs <container_id>`
3. Verify database connectivity
4. Ensure all environment variables are set correctly

