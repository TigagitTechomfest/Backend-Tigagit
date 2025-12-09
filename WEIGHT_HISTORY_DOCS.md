
### Get Weight History
Retrieves the user's weight history, including initial weight and current weight.

**Endpoint:** `GET /api/assessment/weight/history`
**Headers:**
- `Authorization: Bearer <token>`

**Response:**
```json
{
    "success": true,
    "message": "Weight history retrieved",
    "data": {
        "initial_weight": 70.00,
        "current_weight": 68.50,
        "history": [
            {
                "id": 2,
                "user_id": 1,
                "history_date": "2025-12-09",
                "weight": "68.50",
                "bmi": "22.37",
                "health_status": "Updated",
                "recorded_at": "2025-12-09 10:30:00"
            },
            {
                "id": 1,
                "user_id": 1,
                "history_date": "2025-11-20",
                "weight": "70.00",
                "bmi": "22.86",
                "health_status": "Initial Assessment",
                "recorded_at": "2025-11-20 09:00:00"
            }
        ]
    }
}
```
