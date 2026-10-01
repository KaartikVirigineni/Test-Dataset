use crate::auth::{create_jwt, hash_password, verify_password};
use crate::models::{AuthResponse, ErrorResponse, LoginRequest, RegisterRequest, User, UserResponse};
use actix_web::{web, HttpResponse, Responder};
use chrono::Utc;
use sqlx::SqlitePool;
use uuid::Uuid;
use validator::Validate;

pub fn configure(cfg: &mut web::ServiceConfig) {
    cfg.service(
        web::scope("/auth")
            .route("/register", web::post().to(register))
            .route("/login", web::post().to(login)),
    );
}

async fn register(
    pool: web::Data<SqlitePool>,
    jwt_secret: web::Data<String>,
    req: web::Json<RegisterRequest>,
) -> impl Responder {
    if let Err(e) = req.validate() {
        return HttpResponse::BadRequest().json(ErrorResponse {
            error: format!("Validation error: {}", e),
        });
    }

    let password_hash = match hash_password(&req.password) {
        Ok(h) => h,
        Err(_) => {
            return HttpResponse::InternalServerError().json(ErrorResponse {
                error: "Failed to hash password".to_string(),
            })
        }
    };

    let user_id = Uuid::new_v4().to_string();
    let now = Utc::now();

    let result = sqlx::query(
        "INSERT INTO users (id, username, email, password_hash, created_at) VALUES (?, ?, ?, ?, ?)",
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
                created_at: now,
            };

            match create_jwt(&user, jwt_secret.get_ref()) {
                Ok(token) => HttpResponse::Created().json(AuthResponse {
                    token,
                    user: UserResponse {
                        id: user.id,
                        username: user.username,
                        email: user.email,
                    },
                }),
                Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
                    error: "Failed to create token".to_string(),
                }),
            }
        }
        Err(e) => {
            if e.to_string().contains("UNIQUE constraint failed") {
                HttpResponse::Conflict().json(ErrorResponse {
                    error: "Username or email already exists".to_string(),
                })
            } else {
                HttpResponse::InternalServerError().json(ErrorResponse {
                    error: "Failed to create user".to_string(),
                })
            }
        }
    }
}

async fn login(
    pool: web::Data<SqlitePool>,
    jwt_secret: web::Data<String>,
    req: web::Json<LoginRequest>,
) -> impl Responder {
    let result = sqlx::query_as::<_, (String, String, String, String, String)>(
        "SELECT id, username, email, password_hash, created_at FROM users WHERE username = ?",
    )
    .bind(&req.username)
    .fetch_optional(pool.get_ref())
    .await;

    match result {
        Ok(Some((id, username, email, password_hash, created_at))) => {
            match verify_password(&req.password, &password_hash) {
                Ok(true) => {
                    let user = User {
                        id,
                        username,
                        email,
                        password_hash,
                        created_at: created_at.parse().unwrap_or(Utc::now()),
                    };

                    match create_jwt(&user, jwt_secret.get_ref()) {
                        Ok(token) => HttpResponse::Ok().json(AuthResponse {
                            token,
                            user: UserResponse {
                                id: user.id,
                                username: user.username,
                                email: user.email,
                            },
                        }),
                        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
                            error: "Failed to create token".to_string(),
                        }),
                    }
                }
                _ => HttpResponse::Unauthorized().json(ErrorResponse {
                    error: "Invalid credentials".to_string(),
                }),
            }
        }
        Ok(None) => HttpResponse::Unauthorized().json(ErrorResponse {
            error: "Invalid credentials".to_string(),
        }),
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Database error".to_string(),
        }),
    }
}