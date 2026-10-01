use crate::auth::{create_jwt, hash_password, verify_password};
use crate::models::{AuthResponse, ErrorResponse, LoginRequest, RegisterRequest, User, UserResponse};
use actix_web::{post, web, HttpResponse, Result};
use sqlx::SqlitePool;
use validator::Validate;

#[post("/auth/register")]
pub async fn register(
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    payload: web::Json<RegisterRequest>,
) -> Result<HttpResponse> {
    if let Err(e) = payload.validate() {
        return Ok(HttpResponse::BadRequest().json(ErrorResponse {
            error: format!("Validation error: {}", e),
        }));
    }

    let existing_user = sqlx::query_as::<_, (String,)>("SELECT id FROM users WHERE email = ?")
        .bind(&payload.email)
        .fetch_optional(pool.get_ref())
        .await
        .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    if existing_user.is_some() {
        return Ok(HttpResponse::Conflict().json(ErrorResponse {
            error: "Email already registered".to_string(),
        }));
    }

    let user_id = uuid::Uuid::new_v4().to_string();
    let password_hash = hash_password(&payload.password)
        .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;
    let now = chrono::Utc::now();

    sqlx::query(
        "INSERT INTO users (id, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?)"
    )
    .bind(&user_id)
    .bind(&payload.email)
    .bind(&password_hash)
    .bind("user")
    .bind(now.to_rfc3339())
    .execute(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    let token = create_jwt(&user_id, &payload.email, "user", secret.get_ref())
        .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    let user = UserResponse {
        id: user_id,
        email: payload.email.clone(),
        role: "user".to_string(),
        created_at: now,
    };

    Ok(HttpResponse::Created().json(AuthResponse { token, user }))
}

#[post("/auth/login")]
pub async fn login(
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    payload: web::Json<LoginRequest>,
) -> Result<HttpResponse> {
    if let Err(e) = payload.validate() {
        return Ok(HttpResponse::BadRequest().json(ErrorResponse {
            error: format!("Validation error: {}", e),
        }));
    }

    let user = sqlx::query_as::<_, (String, String, String, String, String)>(
        "SELECT id, email, password_hash, role, created_at FROM users WHERE email = ?"
    )
    .bind(&payload.email)
    .fetch_optional(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    let (user_id, email, password_hash, role, created_at) = match user {
        Some(u) => u,
        None => {
            return Ok(HttpResponse::Unauthorized().json(ErrorResponse {
                error: "Invalid credentials".to_string(),
            }));
        }
    };

    let valid = verify_password(&payload.password, &password_hash)
        .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    if !valid {
        return Ok(HttpResponse::Unauthorized().json(ErrorResponse {
            error: "Invalid credentials".to_string(),
        }));
    }

    let token = create_jwt(&user_id, &email, &role, secret.get_ref())
        .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    let user_response = UserResponse {
        id: user_id,
        email,
        role,
        created_at: created_at.parse().unwrap_or_else(|_| chrono::Utc::now()),
    };

    Ok(HttpResponse::Ok().json(AuthResponse {
        token,
        user: user_response,
    }))
}