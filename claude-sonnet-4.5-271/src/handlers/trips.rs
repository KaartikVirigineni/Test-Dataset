use crate::auth::decode_jwt;
use crate::models::{CreateTripRequest, ErrorResponse, Trip, UpdateTripRequest};
use actix_web::{web, HttpRequest, HttpResponse, Responder};
use chrono::Utc;
use sqlx::SqlitePool;
use uuid::Uuid;
use validator::Validate;

pub fn configure(cfg: &mut web::ServiceConfig) {
    cfg.service(
        web::scope("/trips")
            .route("", web::post().to(create_trip))
            .route("", web::get().to(list_trips))
            .route("/{trip_id}", web::get().to(get_trip))
            .route("/{trip_id}", web::put().to(update_trip))
            .route("/{trip_id}", web::delete().to(delete_trip)),
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

async fn create_trip(
    pool: web::Data<SqlitePool>,
    jwt_secret: web::Data<String>,
    req: HttpRequest,
    body: web::Json<CreateTripRequest>,
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

    let trip_id = Uuid::new_v4().to_string();
    let now = Utc::now();

    let result = sqlx::query(
        "INSERT INTO trips (id, user_id, title, description, start_date, end_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
    )
    .bind(&trip_id)
    .bind(&user_id)
    .bind(&body.title)
    .bind(&body.description)
    .bind(body.start_date.to_rfc3339())
    .bind(body.end_date.to_rfc3339())
    .bind(now.to_rfc3339())
    .bind(now.to_rfc3339())
    .execute(pool.get_ref())
    .await;

    match result {
        Ok(_) => {
            let trip = Trip {
                id: trip_id,
                user_id,
                title: body.title.clone(),
                description: body.description.clone(),
                start_date: body.start_date,
                end_date: body.end_date,
                created_at: now,
                updated_at: now,
            };
            HttpResponse::Created().json(trip)
        }
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to create trip".to_string(),
        }),
    }
}

async fn list_trips(
    pool: web::Data<SqlitePool>,
    jwt_secret: web::Data<String>,
    req: HttpRequest,
) -> impl Responder {
    let user_id = match get_user_id(&req, jwt_secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    let result = sqlx::query_as::<_, (String, String, String, Option<String>, String, String, String, String)>(
        "SELECT id, user_id, title, description, start_date, end_date, created_at, updated_at FROM trips WHERE user_id = ? ORDER BY start_date DESC",
    )
    .bind(&user_id)
    .fetch_all(pool.get_ref())
    .await;

    match result {
        Ok(rows) => {
            let trips: Vec<Trip> = rows
                .into_iter()
                .map(|(id, user_id, title, description, start_date, end_date, created_at, updated_at)| Trip {
                    id,
                    user_id,
                    title,
                    description,
                    start_date: start_date.parse().unwrap_or(Utc::now()),
                    end_date: end_date.parse().unwrap_or(Utc::now()),
                    created_at: created_at.parse().unwrap_or(Utc::now()),
                    updated_at: updated_at.parse().unwrap_or(Utc::now()),
                })
                .collect();
            HttpResponse::Ok().json(trips)
        }
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to fetch trips".to_string(),
        }),
    }
}

async fn get_trip(
    pool: web::Data<SqlitePool>,
    jwt_secret: web::Data<String>,
    req: HttpRequest,
    path: web::Path<String>,
) -> impl Responder {
    let user_id = match get_user_id(&req, jwt_secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    let trip_id = path.into_inner();

    let result = sqlx::query_as::<_, (String, String, String, Option<String>, String, String, String, String)>(
        "SELECT id, user_id, title, description, start_date, end_date, created_at, updated_at FROM trips WHERE id = ? AND user_id = ?",
    )
    .bind(&trip_id)
    .bind(&user_id)
    .fetch_optional(pool.get_ref())
    .await;

    match result {
        Ok(Some((id, user_id, title, description, start_date, end_date, created_at, updated_at))) => {
            let trip = Trip {
                id,
                user_id,
                title,
                description,
                start_date: start_date.parse().unwrap_or(Utc::now()),
                end_date: end_date.parse().unwrap_or(Utc::now()),
                created_at: created_at.parse().unwrap_or(Utc::now()),
                updated_at: updated_at.parse().unwrap_or(Utc::now()),
            };
            HttpResponse::Ok().json(trip)
        }
        Ok(None) => HttpResponse::NotFound().json(ErrorResponse {
            error: "Trip not found".to_string(),
        }),
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Database error".to_string(),
        }),
    }
}

async fn update_trip(
    pool: web::Data<SqlitePool>,
    jwt_secret: web::Data<String>,
    req: HttpRequest,
    path: web::Path<String>,
    body: web::Json<UpdateTripRequest>,
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

    let trip_id = path.into_inner();

    let existing = sqlx::query_as::<_, (String, String, String, Option<String>, String, String, String, String)>(
        "SELECT id, user_id, title, description, start_date, end_date, created_at, updated_at FROM trips WHERE id = ? AND user_id = ?",
    )
    .bind(&trip_id)
    .bind(&user_id)
    .fetch_optional(pool.get_ref())
    .await;

    match existing {
        Ok(Some((id, user_id, mut title, mut description, mut start_date, mut end_date, created_at, _))) => {
            if let Some(new_title) = &body.title {
                title = new_title.clone();
            }
            if body.description.is_some() {
                description = body.description.clone();
            }
            if let Some(new_start) = body.start_date {
                start_date = new_start.to_rfc3339();
            }
            if let Some(new_end) = body.end_date {
                end_date = new_end.to_rfc3339();
            }

            let now = Utc::now();
            let result = sqlx::query(
                "UPDATE trips SET title = ?, description = ?, start_date = ?, end_date = ?, updated_at = ? WHERE id = ?",
            )
            .bind(&title)
            .bind(&description)
            .bind(&start_date)
            .bind(&end_date)
            .bind(now.to_rfc3339())
            .bind(&trip_id)
            .execute(pool.get_ref())
            .await;

            match result {
                Ok(_) => {
                    let trip = Trip {
                        id,
                        user_id,
                        title,
                        description,
                        start_date: start_date.parse().unwrap_or(Utc::now()),
                        end_date: end_date.parse().unwrap_or(Utc::now()),
                        created_at: created_at.parse().unwrap_or(Utc::now()),
                        updated_at: now,
                    };
                    HttpResponse::Ok().json(trip)
                }
                Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
                    error: "Failed to update trip".to_string(),
                }),
            }
        }
        Ok(None) => HttpResponse::NotFound().json(ErrorResponse {
            error: "Trip not found".to_string(),
        }),
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Database error".to_string(),
        }),
    }
}

async fn delete_trip(
    pool: web::Data<SqlitePool>,
    jwt_secret: web::Data<String>,
    req: HttpRequest,
    path: web::Path<String>,
) -> impl Responder {
    let user_id = match get_user_id(&req, jwt_secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    let trip_id = path.into_inner();

    let result = sqlx::query("DELETE FROM trips WHERE id = ? AND user_id = ?")
        .bind(&trip_id)
        .bind(&user_id)
        .execute(pool.get_ref())
        .await;

    match result {
        Ok(res) => {
            if res.rows_affected() > 0 {
                HttpResponse::NoContent().finish()
            } else {
                HttpResponse::NotFound().json(ErrorResponse {
                    error: "Trip not found".to_string(),
                })
            }
        }
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to delete trip".to_string(),
        }),
    }
}