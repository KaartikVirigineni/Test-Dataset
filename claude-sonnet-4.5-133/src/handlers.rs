use actix_web::{web, HttpResponse, Responder};
use sqlx::SqlitePool;
use uuid::Uuid;
use chrono::Utc;
use crate::models::*;
use crate::auth::{AuthenticatedUser, create_token, hash_password, verify_password};

pub async fn health_check() -> impl Responder {
    HttpResponse::Ok().json(serde_json::json!({ "status": "ok" }))
}

pub async fn get_swagger() -> impl Responder {
    let spec = include_str!("../openapi.yaml");
    HttpResponse::Ok()
        .content_type("application/yaml")
        .body(spec)
}

pub async fn register(
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    req: web::Json<RegisterRequest>,
) -> impl Responder {
    let password_hash = match hash_password(&req.password) {
        Ok(hash) => hash,
        Err(_) => return HttpResponse::InternalServerError()
            .json(ErrorResponse::new("Failed to hash password")),
    };

    let user_id = Uuid::new_v4().to_string();
    let now = Utc::now();

    let result = sqlx::query(
        "INSERT INTO users (id, username, email, password_hash, created_at) VALUES (?, ?, ?, ?, ?)"
    )
    .bind(&user_id)
    .bind(&req.username)
    .bind(&req.email)
    .bind(&password_hash)
    .bind(now.to_rfc3339())
    .execute(pool.get_ref())
    .await;

    match result {
        Ok(_) => {
            let token = match create_token(&user_id, secret.get_ref()) {
                Ok(t) => t,
                Err(_) => return HttpResponse::InternalServerError()
                    .json(ErrorResponse::new("Failed to create token")),
            };

            let user_response = UserResponse {
                id: user_id,
                username: req.username.clone(),
                email: req.email.clone(),
                created_at: now,
            };

            HttpResponse::Created().json(AuthResponse {
                token,
                user: user_response,
            })
        }
        Err(_) => HttpResponse::BadRequest()
            .json(ErrorResponse::new("Username or email already exists")),
    }
}

pub async fn login(
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    req: web::Json<LoginRequest>,
) -> impl Responder {
    let user = sqlx::query_as::<_, User>(
        "SELECT * FROM users WHERE username = ?"
    )
    .bind(&req.username)
    .fetch_optional(pool.get_ref())
    .await;

    match user {
        Ok(Some(user)) => {
            match verify_password(&req.password, &user.password_hash) {
                Ok(true) => {
                    let token = match create_token(&user.id, secret.get_ref()) {
                        Ok(t) => t,
                        Err(_) => return HttpResponse::InternalServerError()
                            .json(ErrorResponse::new("Failed to create token")),
                    };

                    let user_response = UserResponse {
                        id: user.id,
                        username: user.username,
                        email: user.email,
                        created_at: user.created_at,
                    };

                    HttpResponse::Ok().json(AuthResponse {
                        token,
                        user: user_response,
                    })
                }
                _ => HttpResponse::Unauthorized()
                    .json(ErrorResponse::new("Invalid credentials")),
            }
        }
        _ => HttpResponse::Unauthorized()
            .json(ErrorResponse::new("Invalid credentials")),
    }
}

pub async fn list_rentals(
    pool: web::Data<SqlitePool>,
    _user: AuthenticatedUser,
    query: web::Query<std::collections::HashMap<String, String>>,
) -> impl Responder {
    let mut sql = "SELECT * FROM rentals WHERE 1=1".to_string();
    let mut params: Vec<String> = vec![];

    if let Some(category) = query.get("category") {
        sql.push_str(" AND category = ?");
        params.push(category.clone());
    }

    if let Some(available) = query.get("available") {
        sql.push_str(" AND available = ?");
        params.push(if available == "true" { "1" } else { "0" }.to_string());
    }

    let mut query_builder = sqlx::query_as::<_, Rental>(&sql);
    for param in &params {
        query_builder = query_builder.bind(param);
    }

    match query_builder.fetch_all(pool.get_ref()).await {
        Ok(rentals) => HttpResponse::Ok().json(rentals),
        Err(_) => HttpResponse::InternalServerError()
            .json(ErrorResponse::new("Failed to fetch rentals")),
    }
}

pub async fn create_rental(
    pool: web::Data<SqlitePool>,
    _user: AuthenticatedUser,
    req: web::Json<CreateRentalRequest>,
) -> impl Responder {
    let id = Uuid::new_v4().to_string();
    let now = Utc::now();

    let result = sqlx::query(
        "INSERT INTO rentals (id, name, description, category, daily_rate, available, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?)"
    )
    .bind(&id)
    .bind(&req.name)
    .bind(&req.description)
    .bind(&req.category)
    .bind(req.daily_rate)
    .bind(now.to_rfc3339())
    .bind(now.to_rfc3339())
    .execute(pool.get_ref())
    .await;

    match result {
        Ok(_) => {
            let rental = Rental {
                id,
                name: req.name.clone(),
                description: req.description.clone(),
                category: req.category.clone(),
                daily_rate: req.daily_rate,
                available: true,
                created_at: now,
                updated_at: now,
            };
            HttpResponse::Created().json(rental)
        }
        Err(_) => HttpResponse::InternalServerError()
            .json(ErrorResponse::new("Failed to create rental")),
    }
}

pub async fn get_rental(
    pool: web::Data<SqlitePool>,
    _user: AuthenticatedUser,
    id: web::Path<String>,
) -> impl Responder {
    match sqlx::query_as::<_, Rental>("SELECT * FROM rentals WHERE id = ?")
        .bind(id.as_str())
        .fetch_optional(pool.get_ref())
        .await
    {
        Ok(Some(rental)) => HttpResponse::Ok().json(rental),
        Ok(None) => HttpResponse::NotFound()
            .json(ErrorResponse::new("Rental not found")),
        Err(_) => HttpResponse::InternalServerError()
            .json(ErrorResponse::new("Failed to fetch rental")),
    }
}

pub async fn update_rental(
    pool: web::Data<SqlitePool>,
    _user: AuthenticatedUser,
    id: web::Path<String>,
    req: web::Json<UpdateRentalRequest>,
) -> impl Responder {
    let existing = sqlx::query_as::<_, Rental>("SELECT * FROM rentals WHERE id = ?")
        .bind(id.as_str())
        .fetch_optional(pool.get_ref())
        .await;

    match existing {
        Ok(Some(mut rental)) => {
            if let Some(name) = &req.name {
                rental.name = name.clone();
            }
            if let Some(description) = &req.description {
                rental.description = Some(description.clone());
            }
            if let Some(category) = &req.category {
                rental.category = category.clone();
            }
            if let Some(daily_rate) = req.daily_rate {
                rental.daily_rate = daily_rate;
            }
            if let Some(available) = req.available {
                rental.available = available;
            }
            rental.updated_at = Utc::now();

            let result = sqlx::query(
                "UPDATE rentals SET name = ?, description = ?, category = ?, daily_rate = ?, available = ?, updated_at = ? WHERE id = ?"
            )
            .bind(&rental.name)
            .bind(&rental.description)
            .bind(&rental.category)
            .bind(rental.daily_rate)
            .bind(rental.available as i32)
            .bind(rental.updated_at.to_rfc3339())
            .bind(id.as_str())
            .execute(pool.get_ref())
            .await;

            match result {
                Ok(_) => HttpResponse::Ok().json(rental),
                Err(_) => HttpResponse::InternalServerError()
                    .json(ErrorResponse::new("Failed to update rental")),
            }
        }
        Ok(None) => HttpResponse::NotFound()
            .json(ErrorResponse::new("Rental not found")),
        Err(_) => HttpResponse::InternalServerError()
            .json(ErrorResponse::new("Failed to fetch rental")),
    }
}

pub async fn delete_rental(
    pool: web::Data<SqlitePool>,
    _user: AuthenticatedUser,
    id: web::Path<String>,
) -> impl Responder {
    match sqlx::query("DELETE FROM rentals WHERE id = ?")
        .bind(id.as_str())
        .execute(pool.get_ref())
        .await
    {
        Ok(result) => {
            if result.rows_affected() > 0 {
                HttpResponse::NoContent().finish()
            } else {
                HttpResponse::NotFound()
                    .json(ErrorResponse::new("Rental not found"))
            }
        }
        Err(_) => HttpResponse::InternalServerError()
            .json(ErrorResponse::new("Failed to delete rental")),
    }
}

pub async fn list_bookings(
    pool: web::Data<SqlitePool>,
    user: AuthenticatedUser,
) -> impl Responder {
    match sqlx::query_as::<_, Booking>("SELECT * FROM bookings WHERE user_id = ?")
        .bind(&user.user_id)
        .fetch_all(pool.get_ref())
        .await
    {
        Ok(bookings) => HttpResponse::Ok().json(bookings),
        Err(_) => HttpResponse::InternalServerError()
            .json(ErrorResponse::new("Failed to fetch bookings")),
    }
}

pub async fn create_booking(
    pool: web::Data<SqlitePool>,
    user: AuthenticatedUser,
    req: web::Json<CreateBookingRequest>,
) -> impl Responder {
    let rental = sqlx::query_as::<_, Rental>("SELECT * FROM rentals WHERE id = ?")
        .bind(&req.rental_id)
        .fetch_optional(pool.get_ref())
        .await;

    match rental {
        Ok(Some(rental)) => {
            if !rental.available {
                return HttpResponse::BadRequest()
                    .json(ErrorResponse::new("Rental is not available"));
            }

            let start = match chrono::NaiveDate::parse_from_str(&req.start_date, "%Y-%m-%d") {
                Ok(d) => d,
                Err(_) => return HttpResponse::BadRequest()
                    .json(ErrorResponse::new("Invalid start date format")),
            };

            let end = match chrono::NaiveDate::parse_from_str(&req.end_date, "%Y-%m-%d") {
                Ok(d) => d,
                Err(_) => return HttpResponse::BadRequest()
                    .json(ErrorResponse::new("Invalid end date format")),
            };

            let days = (end - start).num_days();
            if days <= 0 {
                return HttpResponse::BadRequest()
                    .json(ErrorResponse::new("End date must be after start date"));
            }

            let total_cost = days as f64 * rental.daily_rate;
            let id = Uuid::new_v4().to_string();
            let now = Utc::now();

            let result = sqlx::query(
                "INSERT INTO bookings (id, rental_id, user_id, start_date, end_date, total_cost, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)"
            )
            .bind(&id)
            .bind(&req.rental_id)
            .bind(&user.user_id)
            .bind(&req.start_date)
            .bind(&req.end_date)
            .bind(total_cost)
            .bind(now.to_rfc3339())
            .execute(pool.get_ref())
            .await;

            match result {
                Ok(_) => {
                    let booking = Booking {
                        id,
                        rental_id: req.rental_id.clone(),
                        user_id: user.user_id,
                        start_date: req.start_date.clone(),
                        end_date: req.end_date.clone(),
                        total_cost,
                        status: "pending".to_string(),
                        created_at: now,
                    };
                    HttpResponse::Created().json(booking)
                }
                Err(_) => HttpResponse::InternalServerError()
                    .json(ErrorResponse::new("Failed to create booking")),
            }
        }
        Ok(None) => HttpResponse::NotFound()
            .json(ErrorResponse::new("Rental not found")),
        Err(_) => HttpResponse::InternalServerError()
            .json(ErrorResponse::new("Failed to fetch rental")),
    }
}

pub async fn get_booking(
    pool: web::Data<SqlitePool>,
    user: AuthenticatedUser,
    id: web::Path<String>,
) -> impl Responder {
    match sqlx::query_as::<_, Booking>("SELECT * FROM bookings WHERE id = ? AND user_id = ?")
        .bind(id.as_str())
        .bind(&user.user_id)
        .fetch_optional(pool.get_ref())
        .await
    {
        Ok(Some(booking)) => HttpResponse::Ok().json(booking),
        Ok(None) => HttpResponse::NotFound()
            .json(ErrorResponse::new("Booking not found")),
        Err(_) => HttpResponse::InternalServerError()
            .json(ErrorResponse::new("Failed to fetch booking")),
    }
}

pub async fn cancel_booking(
    pool: web::Data<SqlitePool>,
    user: AuthenticatedUser,
    id: web::Path<String>,
) -> impl Responder {
    match sqlx::query("DELETE FROM bookings WHERE id = ? AND user_id = ?")
        .bind(id.as_str())
        .bind(&user.user_id)
        .execute(pool.get_ref())
        .await
    {
        Ok(result) => {
            if result.rows_affected() > 0 {
                HttpResponse::NoContent().finish()
            } else {
                HttpResponse::NotFound()
                    .json(ErrorResponse::new("Booking not found"))
            }
        }
        Err(_) => HttpResponse::InternalServerError()
            .json(ErrorResponse::new("Failed to cancel booking")),
    }
}