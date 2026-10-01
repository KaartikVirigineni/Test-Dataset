use crate::auth::{create_jwt, hash_password, verify_password};
use crate::models::{
    User, UserResponse, RegisterRequest, LoginRequest, AuthResponse, ErrorResponse, MessageResponse,
};
use actix_web::{web, HttpResponse, Responder};
use sqlx::SqlitePool;
use uuid::Uuid;
use chrono::Utc;
use validator::Validate;

pub fn config(cfg: &mut web::ServiceConfig) {
    cfg.service(
        web::scope("/auth")
            .route("/register", web::post().to(register))
            .route("/login", web::post().to(login))
    );
}

async fn register(
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    req: web::Json<RegisterRequest>,
) -> impl Responder {
    if let Err(errors) = req.validate() {
        return HttpResponse::BadRequest().json(ErrorResponse {
            error: format!("Validation error: {:?}", errors),
        });
    }

    let password_hash = match hash_password(&req.password) {
        Ok(hash) => hash,
        Err(_) => {
            return HttpResponse::InternalServerError().json(ErrorResponse {
                error: "Failed to hash password".to_string(),
            });
        }
    };

    let user_id = Uuid::new_v4().to_string();
    let now = Utc::now();

    let result = sqlx::query(
        r#"
        INSERT INTO users (id, username, email, password_hash, role, created_at)
        VALUES (?, ?, ?, ?, 'user', ?)
        "#,
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
            let user = User {
                id: user_id,
                username: req.username.clone(),
                email: req.email.clone(),
                password_hash,
                role: "user".to_string(),
                created_at: now,
            };

            match create_jwt(&user, secret.get_ref()) {
                Ok(token) => HttpResponse::Created().json(AuthResponse {
                    token,
                    user: user.into(),
                }),
                Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
                    error: "Failed to create token".to_string(),
                }),
            }
        }
        Err(e) => {
            let error_message = if e.to_string().contains("UNIQUE") {
                "Username or email already exists".to_string()
            } else {
                "Failed to create user".to_string()
            };
            HttpResponse::BadRequest().json(ErrorResponse { error: error_message })
        }
    }
}

async fn login(
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    req: web::Json<LoginRequest>,
) -> impl Responder {
    let user_result = sqlx::query_as::<_, User>(
        r#"
        SELECT id, username, email, password_hash, role, created_at
        FROM users
        WHERE username = ?
        "#,
    )
    .bind(&req.username)
    .fetch_one(pool.get_ref())
    .await;

    match user_result {
        Ok(user) => {
            match verify_password(&req.password, &user.password_hash) {
                Ok(true) => {
                    match create_jwt(&user, secret.get_ref()) {
                        Ok(token) => HttpResponse::Ok().json(AuthResponse {
                            token,
                            user: user.into(),
                        }),
                        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
                            error: "Failed to create token".to_string(),
                        }),
                    }
                }
                Ok(false) => HttpResponse::Unauthorized().json(ErrorResponse {
                    error: "Invalid credentials".to_string(),
                }),
                Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
                    error: "Authentication error".to_string(),
                }),
            }
        }
        Err(_) => HttpResponse::Unauthorized().json(ErrorResponse {
            error: "Invalid credentials".to_string(),
        }),
    }
}