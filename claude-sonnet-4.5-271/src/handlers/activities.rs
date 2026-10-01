use crate::auth::decode_jwt;
use crate::models::{Activity, CreateActivityRequest, ErrorResponse};
use actix_web::{web, HttpRequest, HttpResponse, Responder};
use chrono::Utc;
use sqlx::SqlitePool;
use uuid::Uuid;
use validator::Validate;

pub fn configure(cfg: &mut web::ServiceConfig) {
    cfg.service(
        web::scope("/itineraries/{itinerary_id}/activities")
            .route("", web::post().to(create_activity))
            .route("", web::get().to(list_activities))
            .route("/{activity_id}", web::get().to(get_activity))
            .route("/{activity_id}", web::delete().to(delete_activity)),
    );
}

fn get_user_id(req: &HttpRequest, jwt_secret: &str) -> Result<String, HttpResponse> {
    let auth_header = req
        .headers()
        .get("Authorization")
        .and_then(|h| h.to_str().ok())
        .ok_or_else(|| {
            HttpResponse::Unauthorized().json(ErrorResponse {
                error: "Missing authorization header".to_string(),
            })
        })?;

    if !auth_header.starts_with("Bearer ") {
        return Err(HttpResponse::Unauthorized().json(ErrorResponse {
            error: "Invalid authorization format".to_string(),
        }));
    }

    let token = &auth_header[7..];
    let claims = decode_jwt(token, jwt_secret).map_err(|_| {
        HttpResponse::Unauthorized().json(ErrorResponse {
            error: "Invalid or expired token".to_string(),
        })
    })?;

    Ok(claims.sub)
}

async fn verify_itinerary_ownership(
    pool: &SqlitePool,
    itinerary_id: &str,
    user_id: &str,
) -> Result<bool, sqlx::Error> {
    let result = sqlx::query_as::<_, (i64,)>(
        "SELECT COUNT(*) FROM itineraries i JOIN trips t ON i.trip_id = t.id WHERE i.id = ? AND t.user_id = ?",
    )
    .bind(itinerary_id)
    .bind(user_id)
    .fetch_one(pool)
    .await?;

    Ok(result.0 > 0)
}

async fn create_activity(
    pool: web::Data<SqlitePool>,
    jwt_secret: web::Data<String>,
    req: HttpRequest,
    path: web::Path<String>,
    body: web::Json<CreateActivityRequest>,
) -> impl Responder {
    let user_id = match get_user_id(&req, jwt_secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    if let Err(e) = body.validate() {
        return HttpResponse::BadRequest().json(ErrorResponse {
            error: format!("Validation error: {}", e),
        });
    }

    let itinerary_id = path.into_inner();

    match verify_itinerary_ownership(pool.get_ref(), &itinerary_id, &user_id).await {
        Ok(true) => {}
        Ok(false) => {
            return HttpResponse::NotFound().json(ErrorResponse {
                error: "Itinerary not found".to_string(),
            })
        }
        Err(_) => {
            return HttpResponse::InternalServerError().json(ErrorResponse {
                error: "Database error".to_string(),
            })
        }
    }

    let activity_id = Uuid::new_v4().to_string();
    let now = Utc::now();

    let result = sqlx::query(
        "INSERT INTO activities (id, itinerary_id, title, description, location, start_time, end_time, cost, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
    )
    .bind(&activity_id)
    .bind(&itinerary_id)
    .bind(&body.title)
    .bind(&body.description)
    .bind(&body.location)
    .bind(body.start_time.map(|t| t.to_rfc3339()))
    .bind(body.end_time.map(|t| t.to_rfc3339()))
    .bind(body.cost)
    .bind(now.to_rfc3339())
    .execute(pool.get_ref())
    .await;

    match result {
        Ok(_) => {
            let activity = Activity {
                id: activity_id,
                itinerary_id,
                title: body.title.clone(),
                description: body.description.clone(),
                location: body.location.clone(),
                start_time: body.start_time,
                end_time: body.end_time,
                cost: body.cost,
                created_at: now,
            };
            HttpResponse::Created().json(activity)
        }
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to create activity".to_string(),
        }),
    }
}

async fn list_activities(
    pool: web::Data<SqlitePool>,
    jwt_secret: web::Data<String>,
    req: HttpRequest,
    path: web::Path<String>,
) -> impl Responder {
    let user_id = match get_user_id(&req, jwt_secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    let itinerary_id = path.into_inner();

    match verify_itinerary_ownership(pool.get_ref(), &itinerary_id, &user_id).await {
        Ok(true) => {}
        Ok(false) => {
            return HttpResponse::NotFound().json(ErrorResponse {
                error: "Itinerary not found".to_string(),
            })
        }
        Err(_) => {
            return HttpResponse::InternalServerError().json(ErrorResponse {
                error: "Database error".to_string(),
            })
        }
    }

    let result = sqlx::query_as::<_, (String, String, String, Option<String>, Option<String>, Option<String>, Option<String>, Option<f64>, String)>(
        "SELECT id, itinerary_id, title, description, location, start_time, end_time, cost, created_at FROM activities WHERE itinerary_id = ? ORDER BY start_time ASC",
    )
    .bind(&itinerary_id)
    .fetch_all(pool.get_ref())
    .await;

    match result {
        Ok(rows) => {
            let activities: Vec<Activity> = rows
                .into_iter()
                .map(|(id, itinerary_id, title, description, location, start_time, end_time, cost, created_at)| Activity {
                    id,
                    itinerary_id,
                    title,
                    description,
                    location,
                    start_time: start_time.and_then(|t| t.parse().ok()),
                    end_time: end_time.and_then(|t| t.parse().ok()),
                    cost,
                    created_at: created_at.parse().unwrap_or(Utc::now()),
                })
                .collect();
            HttpResponse::Ok().json(activities)
        }
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to fetch activities".to_string(),
        }),
    }
}

async fn get_activity(
    pool: web::Data<SqlitePool>,
    jwt_secret: web::Data<String>,
    req: HttpRequest,
    path: web::Path<(String, String)>,
) -> impl Responder {
    let user_id = match get_user_id(&req, jwt_secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    let (itinerary_id, activity_id) = path.into_inner();

    match verify_itinerary_ownership(pool.get_ref(), &itinerary_id, &user_id).await {
        Ok(true) => {}
        Ok(false) => {
            return HttpResponse::NotFound().json(ErrorResponse {
                error: "Itinerary not found".to_string(),
            })
        }
        Err(_) => {
            return HttpResponse::InternalServerError().json(ErrorResponse {
                error: "Database error".to_string(),
            })
        }
    }

    let result = sqlx::query_as::<_, (String, String, String, Option<String>, Option<String>, Option<String>, Option<String>, Option<f64>, String)>(
        "SELECT id, itinerary_id, title, description, location, start_time, end_time, cost, created_at FROM activities WHERE id = ? AND itinerary_id = ?",
    )
    .bind(&activity_id)
    .bind(&itinerary_id)
    .fetch_optional(pool.get_ref())
    .await;

    match result {
        Ok(Some((id, itinerary_id, title, description, location, start_time, end_time, cost, created_at))) => {
            let activity = Activity {
                id,
                itinerary_id,
                title,
                description,
                location,
                start_time: start_time.and_then(|t| t.parse().ok()),
                end_time: end_time.and_then(|t| t.parse().ok()),
                cost,
                created_at: created_at.parse().unwrap_or(Utc::now()),
            };
            HttpResponse::Ok().json(activity)
        }
        Ok(None) => HttpResponse::NotFound().json(ErrorResponse {
            error: "Activity not found".to_string(),
        }),
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Database error".to_string(),
        }),
    }
}

async fn delete_activity(
    pool: web::Data<SqlitePool>,
    jwt_secret: web::Data<String>,
    req: HttpRequest,
    path: web::Path<(String, String)>,
) -> impl Responder {
    let user_id = match get_user_id(&req, jwt_secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    let (itinerary_id, activity_id) = path.into_inner();

    match verify_itinerary_ownership(pool.get_ref(), &itinerary_id, &user_id).await {
        Ok(true) => {}
        Ok(false) => {
            return HttpResponse::NotFound().json(ErrorResponse {
                error: "Itinerary not found".to_string(),
            })
        }
        Err(_) => {
            return HttpResponse::InternalServerError().json(ErrorResponse {
                error: "Database error".to_string(),
            })
        }
    }

    let result = sqlx::query("DELETE FROM activities WHERE id = ? AND itinerary_id = ?")
        .bind(&activity_id)
        .bind(&itinerary_id)
        .execute(pool.get_ref())
        .await;

    match result {
        Ok(res) => {
            if res.rows_affected() > 0 {
                HttpResponse::NoContent().finish()
            } else {
                HttpResponse::NotFound().json(ErrorResponse {
                    error: "Activity not found".to_string(),
                })
            }
        }
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to delete activity".to_string(),
        }),
    }
}