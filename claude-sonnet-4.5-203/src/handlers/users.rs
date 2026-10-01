use crate::middleware::{require_auth, require_role};
use crate::models::{ErrorResponse, UserResponse};
use actix_web::{get, web, HttpRequest, HttpResponse, Result};
use sqlx::SqlitePool;

#[get("/users/me")]
pub async fn get_profile(
    req: HttpRequest,
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
) -> Result<HttpResponse> {
    let user_id = require_auth(&req, secret.get_ref())?;

    let user = sqlx::query_as::<_, (String, String, String, String)>(
        "SELECT id, email, role, created_at FROM users WHERE id = ?"
    )
    .bind(&user_id)
    .fetch_optional(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    match user {
        Some((id, email, role, created_at)) => {
            let user_response = UserResponse {
                id,
                email,
                role,
                created_at: created_at.parse().unwrap_or_else(|_| chrono::Utc::now()),
            };
            Ok(HttpResponse::Ok().json(user_response))
        }
        None => Ok(HttpResponse::NotFound().json(ErrorResponse {
            error: "User not found".to_string(),
        })),
    }
}

#[get("/users")]
pub async fn list_users(
    req: HttpRequest,
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
) -> Result<HttpResponse> {
    require_role(&req, secret.get_ref(), &["admin"])?;

    let users = sqlx::query_as::<_, (String, String, String, String)>(
        "SELECT id, email, role, created_at FROM users ORDER BY created_at DESC"
    )
    .fetch_all(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    let user_responses: Vec<UserResponse> = users
        .into_iter()
        .map(|(id, email, role, created_at)| UserResponse {
            id,
            email,
            role,
            created_at: created_at.parse().unwrap_or_else(|_| chrono::Utc::now()),
        })
        .collect();

    Ok(HttpResponse::Ok().json(user_responses))
}