use actix_web::{web, HttpResponse, Error};
use sqlx::SqlitePool;
use bcrypt::{hash, verify, DEFAULT_COST};
use jsonwebtoken::{encode, Header, EncodingKey};
use validator::Validate;
use std::env;

use crate::models::{User, RegisterRequest, LoginRequest, LoginResponse, Claims};

pub async fn register(
    pool: web::Data<SqlitePool>,
    body: web::Json<RegisterRequest>,
) -> Result<HttpResponse, Error> {
    if let Err(e) = body.validate() {
        return Ok(HttpResponse::BadRequest().json(serde_json::json!({
            "error": format!("Validation error: {}", e)
        })));
    }

    let password_hash = match hash(&body.password, DEFAULT_COST) {
        Ok(h) => h,
        Err(_) => return Ok(HttpResponse::InternalServerError().json(serde_json::json!({
            "error": "Failed to hash password"
        }))),
    };

    let user = User::new(body.username.clone(), password_hash);

    let result = sqlx::query(
        "INSERT INTO users (id, username, password_hash, created_at) VALUES (?, ?, ?, ?)"
    )
    .bind(&user.id)
    .bind(&user.username)
    .bind(&user.password_hash)
    .bind(&user.created_at)
    .execute(pool.get_ref())
    .await;

    match result {
        Ok(_) => Ok(HttpResponse::Created().json(user)),
        Err(e) => {
            log::error!("Failed to create user: {}", e);
            Ok(HttpResponse::BadRequest().json(serde_json::json!({
                "error": "Username already exists"
            })))
        }
    }
}

pub async fn login(
    pool: web::Data<SqlitePool>,
    body: web::Json<LoginRequest>,
) -> Result<HttpResponse, Error> {
    let user = sqlx::query_as::<_, User>(
        "SELECT id, username, password_hash, created_at FROM users WHERE username = ?"
    )
    .bind(&body.username)
    .fetch_optional(pool.get_ref())
    .await;

    let user = match user {
        Ok(Some(u)) => u,
        _ => return Ok(HttpResponse::Unauthorized().json(serde_json::json!({
            "error": "Invalid credentials"
        }))),
    };

    let valid = verify(&body.password, &user.password_hash).unwrap_or(false);
    if !valid {
        return Ok(HttpResponse::Unauthorized().json(serde_json::json!({
            "error": "Invalid credentials"
        })));
    }

    let claims = Claims {
        sub: user.id.clone(),
        exp: (chrono::Utc::now() + chrono::Duration::hours(24)).timestamp() as usize,
    };

    let jwt_secret = env::var("JWT_SECRET").unwrap_or_else(|_| "secret".to_string());
    let token = match encode(
        &Header::default(),
        &claims,
        &EncodingKey::from_secret(jwt_secret.as_ref()),
    ) {
        Ok(t) => t,
        Err(_) => return Ok(HttpResponse::InternalServerError().json(serde_json::json!({
            "error": "Failed to generate token"
        }))),
    };

    Ok(HttpResponse::Ok().json(LoginResponse { token, user }))
}