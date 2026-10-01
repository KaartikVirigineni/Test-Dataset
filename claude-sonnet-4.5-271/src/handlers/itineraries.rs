use crate::auth::decode_jwt;
use crate::models::{CreateItineraryRequest, ErrorResponse, Itinerary};
use actix_web::{web, HttpRequest, HttpResponse, Responder};
use chrono::Utc;
use sqlx::SqlitePool;
use uuid::Uuid;
use validator::Validate;

pub fn configure(cfg: &mut web::ServiceConfig) {
    cfg.service(
        web::scope("/trips/{trip_id}/itineraries")
            .route("", web::post().to(create_itinerary))
            .route("", web::get().to(list_itineraries))
            .route("/{itinerary_id}", web::get().to(get_itinerary))
            .route("/{itinerary_id}", web::delete().to(delete_itinerary)),
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

async fn verify_trip_ownership(
    pool: &SqlitePool,
    trip_id: &str,
    user_id: &str,
) -> Result<bool, sqlx::Error> {
    let result = sqlx::query_as::<_, (i64,)>("SELECT COUNT(*) FROM trips WHERE id = ? AND user_id = ?")
        .bind(trip_id)
        .bind(user_id)
        .fetch_one(pool)
        .await?;

    Ok(result.0 > 0)
}

async fn create_itinerary(
    pool: web::Data<SqlitePool>,
    jwt_secret: web::Data<String>,
    req: HttpRequest,
    path: web::Path<String>,
    body: web::Json<CreateItineraryRequest>,
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

    match verify_trip_ownership(pool.get_ref(), &trip_id, &user_id).await {
        Ok(true) => {}
        Ok(false) => {
            return HttpResponse::NotFound().json(ErrorResponse {
                error: "Trip not found".to_string(),
            })
        }
        Err(_) => {
            return HttpResponse::InternalServerError().json(ErrorResponse {
                error: "Database error".to_string(),
            })
        }
    }

    let itinerary_id = Uuid::new_v4().to_string();
    let now = Utc::now();

    let result = sqlx::query(
        "INSERT INTO itineraries (id, trip_id, day_number, date, title, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
    )
    .bind(&itinerary_id)
    .bind(&trip_id)
    .bind(body.day_number)
    .bind(body.date.to_rfc3339())
    .bind(&body.title)
    .bind(&body.notes)
    .bind(now.to_rfc3339())
    .execute(pool.get_ref())
    .await;

    match result {
        Ok(_) => {
            let itinerary = Itinerary {
                id: itinerary_id,
                trip_id,
                day_number: body.day_number,
                date: body.date,
                title: body.title.clone(),
                notes: body.notes.clone(),
                created_at: now,
            };
            HttpResponse::Created().json(itinerary)
        }
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to create itinerary".to_string(),
        }),
    }
}

async fn list_itineraries(
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

    match verify_trip_ownership(pool.get_ref(), &trip_id, &user_id).await {
        Ok(true) => {}
        Ok(false) => {
            return HttpResponse::NotFound().json(ErrorResponse {
                error: "Trip not found".to_string(),
            })
        }
        Err(_) => {
            return HttpResponse::InternalServerError().json(ErrorResponse {
                error: "Database error".to_string(),
            })
        }
    }

    let result = sqlx::query_as::<_, (String, String, i32, String, String, Option<String>, String)>(
        "SELECT id, trip_id, day_number, date, title, notes, created_at FROM itineraries WHERE trip_id = ? ORDER BY day_number ASC",
    )
    .bind(&trip_id)
    .fetch_all(pool.get_ref())
    .await;

    match result {
        Ok(rows) => {
            let itineraries: Vec<Itinerary> = rows
                .into_iter()
                .map(|(id, trip_id, day_number, date, title, notes, created_at)| Itinerary {
                    id,
                    trip_id,
                    day_number,
                    date: date.parse().unwrap_or(Utc::now()),
                    title,
                    notes,
                    created_at: created_at.parse().unwrap_or(Utc::now()),
                })
                .collect();
            HttpResponse::Ok().json(itineraries)
        }
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to fetch itineraries".to_string(),
        }),
    }
}

async fn get_itinerary(
    pool: web::Data<SqlitePool>,
    jwt_secret: web::Data<String>,
    req: HttpRequest,
    path: web::Path<(String, String)>,
) -> impl Responder {
    let user_id = match get_user_id(&req, jwt_secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    let (trip_id, itinerary_id) = path.into_inner();

    match verify_trip_ownership(pool.get_ref(), &trip_id, &user_id).await {
        Ok(true) => {}
        Ok(false) => {
            return HttpResponse::NotFound().json(ErrorResponse {
                error: "Trip not found".to_string(),
            })
        }
        Err(_) => {
            return HttpResponse::InternalServerError().json(ErrorResponse {
                error: "Database error".to_string(),
            })
        }
    }

    let result = sqlx::query_as::<_, (String, String, i32, String, String, Option<String>, String)>(
        "SELECT id, trip_id, day_number, date, title, notes, created_at FROM itineraries WHERE id = ? AND trip_id = ?",
    )
    .bind(&itinerary_id)
    .bind(&trip_id)
    .fetch_optional(pool.get_ref())
    .await;

    match result {
        Ok(Some((id, trip_id, day_number, date, title, notes, created_at))) => {
            let itinerary = Itinerary {
                id,
                trip_id,
                day_number,
                date: date.parse().unwrap_or(Utc::now()),
                title,
                notes,
                created_at: created_at.parse().unwrap_or(Utc::now()),
            };
            HttpResponse::Ok().json(itinerary)
        }
        Ok(None) => HttpResponse::NotFound().json(ErrorResponse {
            error: "Itinerary not found".to_string(),
        }),
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Database error".to_string(),
        }),
    }
}

async fn delete_itinerary(
    pool: web::Data<SqlitePool>,
    jwt_secret: web::Data<String>,
    req: HttpRequest,
    path: web::Path<(String, String)>,
) -> impl Responder {
    let user_id = match get_user_id(&req, jwt_secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    let (trip_id, itinerary_id) = path.into_inner();

    match verify_trip_ownership(pool.get_ref(), &trip_id, &user_id).await {
        Ok(true) => {}
        Ok(false) => {
            return HttpResponse::NotFound().json(ErrorResponse {
                error: "Trip not found".to_string(),
            })
        }
        Err(_) => {
            return HttpResponse::InternalServerError().json(ErrorResponse {
                error: "Database error".to_string(),
            })
        }
    }

    let result = sqlx::query("DELETE FROM itineraries WHERE id = ? AND trip_id = ?")
        .bind(&itinerary_id)
        .bind(&trip_id)
        .execute(pool.get_ref())
        .await;

    match result {
        Ok(res) => {
            if res.rows_affected() > 0 {
                HttpResponse::NoContent().finish()
            } else {
                HttpResponse::NotFound().json(ErrorResponse {
                    error: "Itinerary not found".to_string(),
                })
            }
        }
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to delete itinerary".to_string(),
        }),
    }
}