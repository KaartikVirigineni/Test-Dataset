use crate::auth::{create_jwt, hash_password, verify_password};
use crate::middleware::{extract_claims, require_role};
use crate::models::*;
use crate::AppState;
use actix_web::{web, HttpRequest, HttpResponse, Responder};
use chrono::Utc;
use std::sync::Arc;
use uuid::Uuid;
use validator::Validate;

pub async fn health_check() -> impl Responder {
    HttpResponse::Ok().json(serde_json::json!({"status": "ok"}))
}

pub async fn swagger_spec() -> impl Responder {
    let spec = include_str!("../openapi.yaml");
    HttpResponse::Ok()
        .content_type("application/yaml")
        .body(spec)
}

pub async fn register(
    state: web::Data<Arc<AppState>>,
    body: web::Json<RegisterRequest>,
) -> impl Responder {
    if let Err(e) = body.validate() {
        return HttpResponse::BadRequest().json(serde_json::json!({"error": e.to_string()}));
    }

    let user_id = Uuid::new_v4().to_string();
    let password_hash = match hash_password(&body.password) {
        Ok(hash) => hash,
        Err(_) => {
            return HttpResponse::InternalServerError()
                .json(serde_json::json!({"error": "Failed to hash password"}))
        }
    };

    let role = body.role.clone().unwrap_or(Role::User);
    let created_at = Utc::now();

    let result = sqlx::query(
        r#"
        INSERT INTO users (id, username, password_hash, role, created_at)
        VALUES (?, ?, ?, ?, ?)
        "#,
    )
    .bind(&user_id)
    .bind(&body.username)
    .bind(&password_hash)
    .bind(role.to_string())
    .bind(created_at.to_rfc3339())
    .execute(&state.db)
    .await;

    match result {
        Ok(_) => {
            let user = User {
                id: user_id,
                username: body.username.clone(),
                role,
                created_at,
            };
            HttpResponse::Created().json(user)
        }
        Err(_) => HttpResponse::BadRequest()
            .json(serde_json::json!({"error": "Username already exists"})),
    }
}

pub async fn login(
    state: web::Data<Arc<AppState>>,
    body: web::Json<LoginRequest>,
) -> impl Responder {
    if let Err(e) = body.validate() {
        return HttpResponse::BadRequest().json(serde_json::json!({"error": e.to_string()}));
    }

    let user_result = sqlx::query_as::<_, (String, String, String, String, String)>(
        r#"
        SELECT id, username, password_hash, role, created_at
        FROM users
        WHERE username = ?
        "#,
    )
    .bind(&body.username)
    .fetch_optional(&state.db)
    .await;

    match user_result {
        Ok(Some((id, username, password_hash, role_str, created_at_str))) => {
            match verify_password(&body.password, &password_hash) {
                Ok(true) => {
                    let role: Role = role_str.parse().unwrap_or(Role::User);
                    let token = match create_jwt(&id, &username, role.clone(), &state.jwt_secret) {
                        Ok(t) => t,
                        Err(_) => {
                            return HttpResponse::InternalServerError()
                                .json(serde_json::json!({"error": "Failed to create token"}))
                        }
                    };

                    let created_at = created_at_str.parse().unwrap_or(Utc::now());

                    let user = User {
                        id,
                        username,
                        role,
                        created_at,
                    };

                    HttpResponse::Ok().json(LoginResponse { token, user })
                }
                _ => HttpResponse::Unauthorized()
                    .json(serde_json::json!({"error": "Invalid credentials"})),
            }
        }
        _ => HttpResponse::Unauthorized().json(serde_json::json!({"error": "Invalid credentials"})),
    }
}

pub async fn list_sessions(
    state: web::Data<Arc<AppState>>,
    req: HttpRequest,
) -> impl Responder {
    let claims = match extract_claims(&req) {
        Ok(c) => c,
        Err(resp) => return resp,
    };

    let sessions_result = sqlx::query_as::<_, (String, String, String, String, Option<String>)>(
        r#"
        SELECT id, user_id, status, last_seen, metadata
        FROM sessions
        ORDER BY last_seen DESC
        "#,
    )
    .fetch_all(&state.db)
    .await;

    match sessions_result {
        Ok(rows) => {
            let sessions: Vec<Session> = rows
                .into_iter()
                .map(|(id, user_id, status_str, last_seen_str, metadata)| Session {
                    id,
                    user_id,
                    status: status_str.parse().unwrap_or(SessionStatus::Offline),
                    last_seen: last_seen_str.parse().unwrap_or(Utc::now()),
                    metadata,
                })
                .collect();
            HttpResponse::Ok().json(sessions)
        }
        Err(_) => HttpResponse::InternalServerError()
            .json(serde_json::json!({"error": "Failed to fetch sessions"})),
    }
}

pub async fn create_session(
    state: web::Data<Arc<AppState>>,
    req: HttpRequest,
    body: web::Json<SessionUpdate>,
) -> impl Responder {
    let claims = match extract_claims(&req) {
        Ok(c) => c,
        Err(resp) => return resp,
    };

    if let Err(e) = body.validate() {
        return HttpResponse::BadRequest().json(serde_json::json!({"error": e.to_string()}));
    }

    let session_id = Uuid::new_v4().to_string();
    let status = body.status.clone().unwrap_or(SessionStatus::Online);
    let last_seen = Utc::now();

    let result = sqlx::query(
        r#"
        INSERT INTO sessions (id, user_id, status, last_seen, metadata)
        VALUES (?, ?, ?, ?, ?)
        "#,
    )
    .bind(&session_id)
    .bind(&claims.sub)
    .bind(status.to_string())
    .bind(last_seen.to_rfc3339())
    .bind(&body.metadata)
    .execute(&state.db)
    .await;

    match result {
        Ok(_) => {
            let session = Session {
                id: session_id,
                user_id: claims.sub,
                status,
                last_seen,
                metadata: body.metadata.clone(),
            };
            HttpResponse::Created().json(session)
        }
        Err(_) => HttpResponse::InternalServerError()
            .json(serde_json::json!({"error": "Failed to create session"})),
    }
}

pub async fn get_session(
    state: web::Data<Arc<AppState>>,
    req: HttpRequest,
    path: web::Path<String>,
) -> impl Responder {
    let claims = match extract_claims(&req) {
        Ok(c) => c,
        Err(resp) => return resp,
    };

    let session_id = path.into_inner();

    let session_result = sqlx::query_as::<_, (String, String, String, String, Option<String>)>(
        r#"
        SELECT id, user_id, status, last_seen, metadata
        FROM sessions
        WHERE id = ?
        "#,
    )
    .bind(&session_id)
    .fetch_optional(&state.db)
    .await;

    match session_result {
        Ok(Some((id, user_id, status_str, last_seen_str, metadata))) => {
            let session = Session {
                id,
                user_id,
                status: status_str.parse().unwrap_or(SessionStatus::Offline),
                last_seen: last_seen_str.parse().unwrap_or(Utc::now()),
                metadata,
            };
            HttpResponse::Ok().json(session)
        }
        Ok(None) => {
            HttpResponse::NotFound().json(serde_json::json!({"error": "Session not found"}))
        }
        Err(_) => HttpResponse::InternalServerError()
            .json(serde_json::json!({"error": "Failed to fetch session"})),
    }
}

pub async fn update_session(
    state: web::Data<Arc<AppState>>,
    req: HttpRequest,
    path: web::Path<String>,
    body: web::Json<SessionUpdate>,
) -> impl Responder {
    let claims = match extract_claims(&req) {
        Ok(c) => c,
        Err(resp) => return resp,
    };

    if let Err(e) = body.validate() {
        return HttpResponse::BadRequest().json(serde_json::json!({"error": e.to_string()}));
    }

    let session_id = path.into_inner();

    let owner_check = sqlx::query_as::<_, (String,)>(
        r#"
        SELECT user_id FROM sessions WHERE id = ?
        "#,
    )
    .bind(&session_id)
    .fetch_optional(&state.db)
    .await;

    match owner_check {
        Ok(Some((user_id,))) => {
            if user_id != claims.sub && !matches!(claims.role, Role::Admin) {
                return HttpResponse::Forbidden()
                    .json(serde_json::json!({"error": "You can only update your own sessions"}));
            }
        }
        Ok(None) => {
            return HttpResponse::NotFound()
                .json(serde_json::json!({"error": "Session not found"}));
        }
        Err(_) => {
            return HttpResponse::InternalServerError()
                .json(serde_json::json!({"error": "Failed to check session ownership"}));
        }
    }

    let last_seen = Utc::now();
    let status = body.status.clone().unwrap_or(SessionStatus::Online);

    let result = sqlx::query(
        r#"
        UPDATE sessions
        SET status = ?, last_seen = ?, metadata = COALESCE(?, metadata)
        WHERE id = ?
        "#,
    )
    .bind(status.to_string())
    .bind(last_seen.to_rfc3339())
    .bind(&body.metadata)
    .bind(&session_id)
    .execute(&state.db)
    .await;

    match result {
        Ok(_) => {
            let session_result = sqlx::query_as::<_, (String, String, String, String, Option<String>)>(
                r#"
                SELECT id, user_id, status, last_seen, metadata
                FROM sessions
                WHERE id = ?
                "#,
            )
            .bind(&session_id)
            .fetch_one(&state.db)
            .await;

            match session_result {
                Ok((id, user_id, status_str, last_seen_str, metadata)) => {
                    let session = Session {
                        id,
                        user_id,
                        status: status_str.parse().unwrap_or(SessionStatus::Offline),
                        last_seen: last_seen_str.parse().unwrap_or(Utc::now()),
                        metadata,
                    };
                    HttpResponse::Ok().json(session)
                }
                Err(_) => HttpResponse::InternalServerError()
                    .json(serde_json::json!({"error": "Failed to fetch updated session"})),
            }
        }
        Err(_) => HttpResponse::InternalServerError()
            .json(serde_json::json!({"error": "Failed to update session"})),
    }
}

pub async fn delete_session(
    state: web::Data<Arc<AppState>>,
    req: HttpRequest,
    path: web::Path<String>,
) -> impl Responder {
    let claims = match extract_claims(&req) {
        Ok(c) => c,
        Err(resp) => return resp,
    };

    let session_id = path.into_inner();

    let owner_check = sqlx::query_as::<_, (String,)>(
        r#"
        SELECT user_id FROM sessions WHERE id = ?
        "#,
    )
    .bind(&session_id)
    .fetch_optional(&state.db)
    .await;

    match owner_check {
        Ok(Some((user_id,))) => {
            if user_id != claims.sub && !matches!(claims.role, Role::Admin) {
                return HttpResponse::Forbidden()
                    .json(serde_json::json!({"error": "You can only delete your own sessions"}));
            }
        }
        Ok(None) => {
            return HttpResponse::NotFound()
                .json(serde_json::json!({"error": "Session not found"}));
        }
        Err(_) => {
            return HttpResponse::InternalServerError()
                .json(serde_json::json!({"error": "Failed to check session ownership"}));
        }
    }

    let result = sqlx::query(r#"DELETE FROM sessions WHERE id = ?"#)
        .bind(&session_id)
        .execute(&state.db)
        .await;

    match result {
        Ok(_) => HttpResponse::NoContent().finish(),
        Err(_) => HttpResponse::InternalServerError()
            .json(serde_json::json!({"error": "Failed to delete session"})),
    }
}

pub async fn list_users(
    state: web::Data<Arc<AppState>>,
    req: HttpRequest,
) -> impl Responder {
    let claims = match extract_claims(&req) {
        Ok(c) => c,
        Err(resp) => return resp,
    };

    if let Err(resp) = require_role(&claims, Role::Admin) {
        return resp;
    }

    let users_result = sqlx::query_as::<_, (String, String, String, String)>(
        r#"
        SELECT id, username, role, created_at
        FROM users
        ORDER BY created_at DESC
        "#,
    )
    .fetch_all(&state.db)
    .await;

    match users_result {
        Ok(rows) => {
            let users: Vec<User> = rows
                .into_iter()
                .map(|(id, username, role_str, created_at_str)| User {
                    id,
                    username,
                    role: role_str.parse().unwrap_or(Role::User),
                    created_at: created_at_str.parse().unwrap_or(Utc::now()),
                })
                .collect();
            HttpResponse::Ok().json(users)
        }
        Err(_) => HttpResponse::InternalServerError()
            .json(serde_json::json!({"error": "Failed to fetch users"})),
    }
}

pub async fn delete_user(
    state: web::Data<Arc<AppState>>,
    req: HttpRequest,
    path: web::Path<String>,
) -> impl Responder {
    let claims = match extract_claims(&req) {
        Ok(c) => c,
        Err(resp) => return resp,
    };

    if let Err(resp) = require_role(&claims, Role::Admin) {
        return resp;
    }

    let user_id = path.into_inner();

    let result = sqlx::query(r#"DELETE FROM users WHERE id = ?"#)
        .bind(&user_id)
        .execute(&state.db)
        .await;

    match result {
        Ok(r) => {
            if r.rows_affected() > 0 {
                HttpResponse::NoContent().finish()
            } else {
                HttpResponse::NotFound().json(serde_json::json!({"error": "User not found"}))
            }
        }
        Err(_) => HttpResponse::InternalServerError()
            .json(serde_json::json!({"error": "Failed to delete user"})),
    }
}