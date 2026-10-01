use axum::{
    extract::{Path, State},
    http::StatusCode,
    middleware,
    response::IntoResponse,
    Extension, Json, Router,
};
use uuid::Uuid;
use chrono::Utc;

use crate::auth::{auth_middleware, create_jwt, hash_password, verify_password};
use crate::models::{
    AppState, CreateVideoRequest, ErrorResponse, LoginRequest, RegisterRequest,
    TokenResponse, User, UserResponse, Video,
};

pub async fn health() -> impl IntoResponse {
    Json(serde_json::json!({ "status": "ok" }))
}

pub async fn swagger_spec() -> impl IntoResponse {
    let spec = include_str!("../openapi.yaml");
    (
        StatusCode::OK,
        [("Content-Type", "application/yaml")],
        spec,
    )
}

pub async fn register(
    State(state): State<AppState>,
    Json(payload): Json<RegisterRequest>,
) -> Result<(StatusCode, Json<TokenResponse>), (StatusCode, Json<ErrorResponse>)> {
    if payload.username.len() < 3 {
        return Err((
            StatusCode::BAD_REQUEST,
            Json(ErrorResponse {
                error: "Username must be at least 3 characters".to_string(),
            }),
        ));
    }

    if payload.password.len() < 6 {
        return Err((
            StatusCode::BAD_REQUEST,
            Json(ErrorResponse {
                error: "Password must be at least 6 characters".to_string(),
            }),
        ));
    }

    let existing = sqlx::query_as::<_, User>("SELECT * FROM users WHERE email = ?")
        .bind(&payload.email)
        .fetch_optional(&state.pool)
        .await
        .map_err(|e| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ErrorResponse {
                    error: format!("Database error: {}", e),
                }),
            )
        })?;

    if existing.is_some() {
        return Err((
            StatusCode::BAD_REQUEST,
            Json(ErrorResponse {
                error: "Email already registered".to_string(),
            }),
        ));
    }

    let password_hash = hash_password(&payload.password).map_err(|e| {
        (
            StatusCode::INTERNAL_SERVER_ERROR,
            Json(ErrorResponse {
                error: format!("Failed to hash password: {}", e),
            }),
        )
    })?;

    let user_id = Uuid::new_v4().to_string();
    let now = Utc::now();

    sqlx::query(
        "INSERT INTO users (id, username, email, password_hash, created_at) VALUES (?, ?, ?, ?, ?)",
    )
    .bind(&user_id)
    .bind(&payload.username)
    .bind(&payload.email)
    .bind(&password_hash)
    .bind(now)
    .execute(&state.pool)
    .await
    .map_err(|e| {
        (
            StatusCode::INTERNAL_SERVER_ERROR,
            Json(ErrorResponse {
                error: format!("Failed to create user: {}", e),
            }),
        )
    })?;

    let token = create_jwt(&user_id, &state.jwt_secret).map_err(|e| {
        (
            StatusCode::INTERNAL_SERVER_ERROR,
            Json(ErrorResponse {
                error: format!("Failed to create token: {}", e),
            }),
        )
    })?;

    Ok((
        StatusCode::CREATED,
        Json(TokenResponse {
            token,
            user: UserResponse {
                id: user_id,
                username: payload.username,
                email: payload.email,
                created_at: now,
            },
        }),
    ))
}

pub async fn login(
    State(state): State<AppState>,
    Json(payload): Json<LoginRequest>,
) -> Result<Json<TokenResponse>, (StatusCode, Json<ErrorResponse>)> {
    let user = sqlx::query_as::<_, User>("SELECT * FROM users WHERE email = ?")
        .bind(&payload.email)
        .fetch_optional(&state.pool)
        .await
        .map_err(|e| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ErrorResponse {
                    error: format!("Database error: {}", e),
                }),
            )
        })?;

    let user = user.ok_or((
        StatusCode::UNAUTHORIZED,
        Json(ErrorResponse {
            error: "Invalid credentials".to_string(),
        }),
    ))?;

    let valid = verify_password(&payload.password, &user.password_hash).map_err(|e| {
        (
            StatusCode::INTERNAL_SERVER_ERROR,
            Json(ErrorResponse {
                error: format!("Password verification error: {}", e),
            }),
        )
    })?;

    if !valid {
        return Err((
            StatusCode::UNAUTHORIZED,
            Json(ErrorResponse {
                error: "Invalid credentials".to_string(),
            }),
        ));
    }

    let token = create_jwt(&user.id, &state.jwt_secret).map_err(|e| {
        (
            StatusCode::INTERNAL_SERVER_ERROR,
            Json(ErrorResponse {
                error: format!("Failed to create token: {}", e),
            }),
        )
    })?;

    Ok(Json(TokenResponse {
        token,
        user: user.into(),
    }))
}

pub async fn list_videos(
    State(state): State<AppState>,
    Extension(_user): Extension<User>,
) -> Result<Json<Vec<Video>>, (StatusCode, Json<ErrorResponse>)> {
    let videos = sqlx::query_as::<_, Video>("SELECT * FROM videos ORDER BY created_at DESC")
        .fetch_all(&state.pool)
        .await
        .map_err(|e| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ErrorResponse {
                    error: format!("Database error: {}", e),
                }),
            )
        })?;

    Ok(Json(videos))
}

pub async fn create_video(
    State(state): State<AppState>,
    Extension(user): Extension<User>,
    Json(payload): Json<CreateVideoRequest>,
) -> Result<(StatusCode, Json<Video>), (StatusCode, Json<ErrorResponse>)> {
    if payload.title.is_empty() {
        return Err((
            StatusCode::BAD_REQUEST,
            Json(ErrorResponse {
                error: "Title is required".to_string(),
            }),
        ));
    }

    let video_id = Uuid::new_v4().to_string();
    let now = Utc::now();

    sqlx::query(
        "INSERT INTO videos (id, title, description, duration_seconds, url, thumbnail_url, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
    )
    .bind(&video_id)
    .bind(&payload.title)
    .bind(&payload.description)
    .bind(&payload.duration_seconds)
    .bind(&payload.url)
    .bind(&payload.thumbnail_url)
    .bind(&user.id)
    .bind(now)
    .execute(&state.pool)
    .await
    .map_err(|e| {
        (
            StatusCode::INTERNAL_SERVER_ERROR,
            Json(ErrorResponse {
                error: format!("Failed to create video: {}", e),
            }),
        )
    })?;

    let video = Video {
        id: video_id,
        title: payload.title,
        description: payload.description,
        duration_seconds: payload.duration_seconds,
        url: payload.url,
        thumbnail_url: payload.thumbnail_url,
        user_id: user.id,
        created_at: now,
    };

    Ok((StatusCode::CREATED, Json(video)))
}

pub async fn get_video(
    State(state): State<AppState>,
    Extension(_user): Extension<User>,
    Path(id): Path<String>,
) -> Result<Json<Video>, (StatusCode, Json<ErrorResponse>)> {
    let video = sqlx::query_as::<_, Video>("SELECT * FROM videos WHERE id = ?")
        .bind(&id)
        .fetch_optional(&state.pool)
        .await
        .map_err(|e| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ErrorResponse {
                    error: format!("Database error: {}", e),
                }),
            )
        })?;

    video.map(Json).ok_or((
        StatusCode::NOT_FOUND,
        Json(ErrorResponse {
            error: "Video not found".to_string(),
        }),
    ))
}

pub async fn update_video(
    State(state): State<AppState>,
    Extension(user): Extension<User>,
    Path(id): Path<String>,
    Json(payload): Json<CreateVideoRequest>,
) -> Result<Json<Video>, (StatusCode, Json<ErrorResponse>)> {
    let existing = sqlx::query_as::<_, Video>("SELECT * FROM videos WHERE id = ?")
        .bind(&id)
        .fetch_optional(&state.pool)
        .await
        .map_err(|e| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ErrorResponse {
                    error: format!("Database error: {}", e),
                }),
            )
        })?;

    let existing = existing.ok_or((
        StatusCode::NOT_FOUND,
        Json(ErrorResponse {
            error: "Video not found".to_string(),
        }),
    ))?;

    if existing.user_id != user.id {
        return Err((
            StatusCode::FORBIDDEN,
            Json(ErrorResponse {
                error: "Not authorized to update this video".to_string(),
            }),
        ));
    }

    sqlx::query(
        "UPDATE videos SET title = ?, description = ?, duration_seconds = ?, url = ?, thumbnail_url = ? WHERE id = ?",
    )
    .bind(&payload.title)
    .bind(&payload.description)
    .bind(&payload.duration_seconds)
    .bind(&payload.url)
    .bind(&payload.thumbnail_url)
    .bind(&id)
    .execute(&state.pool)
    .await
    .map_err(|e| {
        (
            StatusCode::INTERNAL_SERVER_ERROR,
            Json(ErrorResponse {
                error: format!("Failed to update video: {}", e),
            }),
        )
    })?;

    let video = Video {
        id: existing.id,
        title: payload.title,
        description: payload.description,
        duration_seconds: payload.duration_seconds,
        url: payload.url,
        thumbnail_url: payload.thumbnail_url,
        user_id: existing.user_id,
        created_at: existing.created_at,
    };

    Ok(Json(video))
}

pub async fn delete_video(
    State(state): State<AppState>,
    Extension(user): Extension<User>,
    Path(id): Path<String>,
) -> Result<StatusCode, (StatusCode, Json<ErrorResponse>)> {
    let existing = sqlx::query_as::<_, Video>("SELECT * FROM videos WHERE id = ?")
        .bind(&id)
        .fetch_optional(&state.pool)
        .await
        .map_err(|e| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ErrorResponse {
                    error: format!("Database error: {}", e),
                }),
            )
        })?;

    let existing = existing.ok_or((
        StatusCode::NOT_FOUND,
        Json(ErrorResponse {
            error: "Video not found".to_string(),
        }),
    ))?;

    if existing.user_id != user.id {
        return Err((
            StatusCode::FORBIDDEN,
            Json(ErrorResponse {
                error: "Not authorized to delete this video".to_string(),
            }),
        ));
    }

    sqlx::query("DELETE FROM videos WHERE id = ?")
        .bind(&id)
        .execute(&state.pool)
        .await
        .map_err(|e| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ErrorResponse {
                    error: format!("Failed to delete video: {}", e),
                }),
            )
        })?;

    Ok(StatusCode::NO_CONTENT)
}