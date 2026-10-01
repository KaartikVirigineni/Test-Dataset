use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use chrono::Utc;
use std::sync::Arc;
use uuid::Uuid;

use crate::{
    auth::{create_jwt, AuthUser},
    models::*,
    AppState,
};

pub async fn register(
    State(state): State<Arc<AppState>>,
    Json(payload): Json<RegisterRequest>,
) -> impl IntoResponse {
    let password_hash = bcrypt::hash(&payload.password, bcrypt::DEFAULT_COST)
        .map_err(|_| (StatusCode::INTERNAL_SERVER_ERROR, "Hash error"))?;

    let user_id = Uuid::new_v4().to_string();
    let now = Utc::now();

    let result = sqlx::query(
        r#"
        INSERT INTO users (id, username, password_hash, created_at)
        VALUES (?, ?, ?, ?)
        "#,
    )
    .bind(&user_id)
    .bind(&payload.username)
    .bind(&password_hash)
    .bind(now.to_rfc3339())
    .execute(&state.db)
    .await;

    match result {
        Ok(_) => Ok((
            StatusCode::CREATED,
            Json(UserResponse {
                id: user_id,
                username: payload.username,
                created_at: now,
            }),
        )),
        Err(_) => Err((StatusCode::BAD_REQUEST, "User already exists")),
    }
}

pub async fn login(
    State(state): State<Arc<AppState>>,
    Json(payload): Json<LoginRequest>,
) -> impl IntoResponse {
    let user: Option<User> = sqlx::query_as(
        r#"
        SELECT id, username, password_hash, created_at
        FROM users
        WHERE username = ?
        "#,
    )
    .bind(&payload.username)
    .fetch_optional(&state.db)
    .await
    .map_err(|_| (StatusCode::INTERNAL_SERVER_ERROR, "Database error"))?;

    let user = user.ok_or((StatusCode::UNAUTHORIZED, "Invalid credentials"))?;

    let valid = bcrypt::verify(&payload.password, &user.password_hash)
        .map_err(|_| (StatusCode::INTERNAL_SERVER_ERROR, "Hash error"))?;

    if !valid {
        return Err((StatusCode::UNAUTHORIZED, "Invalid credentials"));
    }

    let token = create_jwt(&user.id, &state.jwt_secret)
        .map_err(|_| (StatusCode::INTERNAL_SERVER_ERROR, "Token generation failed"))?;

    Ok(Json(LoginResponse { token }))
}

pub async fn create_log(
    State(state): State<Arc<AppState>>,
    _user: AuthUser,
    Json(payload): Json<CreateLogRequest>,
) -> impl IntoResponse {
    let valid_levels = ["DEBUG", "INFO", "WARN", "ERROR", "FATAL"];
    if !valid_levels.contains(&payload.level.as_str()) {
        return Err((StatusCode::BAD_REQUEST, "Invalid log level"));
    }

    let log_id = Uuid::new_v4().to_string();
    let now = Utc::now();
    let metadata = payload.metadata.map(|m| m.to_string());

    sqlx::query(
        r#"
        INSERT INTO logs (id, timestamp, level, source, message, metadata)
        VALUES (?, ?, ?, ?, ?, ?)
        "#,
    )
    .bind(&log_id)
    .bind(now.to_rfc3339())
    .bind(&payload.level)
    .bind(&payload.source)
    .bind(&payload.message)
    .bind(&metadata)
    .execute(&state.db)
    .await
    .map_err(|_| (StatusCode::INTERNAL_SERVER_ERROR, "Database error"))?;

    Ok((
        StatusCode::CREATED,
        Json(LogResponse {
            id: log_id,
            timestamp: now,
            level: payload.level,
            source: payload.source,
            message: payload.message,
            metadata: metadata.and_then(|m| serde_json::from_str(&m).ok()),
        }),
    ))
}

pub async fn list_logs(
    State(state): State<Arc<AppState>>,
    _user: AuthUser,
    Query(params): Query<LogSearchQuery>,
) -> impl IntoResponse {
    let page = params.page.unwrap_or(1).max(1);
    let page_size = params.page_size.unwrap_or(50).min(100).max(1);
    let offset = (page - 1) * page_size;

    let mut conditions = Vec::new();
    let mut bind_values: Vec<String> = Vec::new();

    if let Some(q) = &params.q {
        conditions.push("message LIKE ?");
        bind_values.push(format!("%{}%", q));
    }

    if let Some(level) = &params.level {
        conditions.push("level = ?");
        bind_values.push(level.clone());
    }

    if let Some(source) = &params.source {
        conditions.push("source = ?");
        bind_values.push(source.clone());
    }

    let where_clause = if conditions.is_empty() {
        String::new()
    } else {
        format!("WHERE {}", conditions.join(" AND "))
    };

    let count_query = format!("SELECT COUNT(*) as count FROM logs {}", where_clause);
    let mut count_q = sqlx::query_scalar::<_, i64>(&count_query);
    for val in &bind_values {
        count_q = count_q.bind(val);
    }
    let total: i64 = count_q
        .fetch_one(&state.db)
        .await
        .map_err(|_| (StatusCode::INTERNAL_SERVER_ERROR, "Database error"))?;

    let select_query = format!(
        "SELECT id, timestamp, level, source, message, metadata FROM logs {} ORDER BY timestamp DESC LIMIT ? OFFSET ?",
        where_clause
    );
    let mut select_q = sqlx::query_as::<_, LogEntry>(&select_query);
    for val in &bind_values {
        select_q = select_q.bind(val);
    }
    select_q = select_q.bind(page_size).bind(offset);

    let logs: Vec<LogEntry> = select_q
        .fetch_all(&state.db)
        .await
        .map_err(|_| (StatusCode::INTERNAL_SERVER_ERROR, "Database error"))?;

    let log_responses: Vec<LogResponse> = logs.into_iter().map(|l| l.into()).collect();

    Ok(Json(LogSearchResponse {
        logs: log_responses,
        total,
        page,
        page_size,
    }))
}

pub async fn get_log(
    State(state): State<Arc<AppState>>,
    _user: AuthUser,
    Path(id): Path<String>,
) -> impl IntoResponse {
    let log: Option<LogEntry> = sqlx::query_as(
        r#"
        SELECT id, timestamp, level, source, message, metadata
        FROM logs
        WHERE id = ?
        "#,
    )
    .bind(&id)
    .fetch_optional(&state.db)
    .await
    .map_err(|_| (StatusCode::INTERNAL_SERVER_ERROR, "Database error"))?;

    match log {
        Some(log) => Ok(Json(LogResponse::from(log))),
        None => Err((StatusCode::NOT_FOUND, "Log not found")),
    }
}

pub async fn delete_log(
    State(state): State<Arc<AppState>>,
    _user: AuthUser,
    Path(id): Path<String>,
) -> impl IntoResponse {
    let result = sqlx::query("DELETE FROM logs WHERE id = ?")
        .bind(&id)
        .execute(&state.db)
        .await
        .map_err(|_| (StatusCode::INTERNAL_SERVER_ERROR, "Database error"))?;

    if result.rows_affected() == 0 {
        Err((StatusCode::NOT_FOUND, "Log not found"))
    } else {
        Ok(StatusCode::NO_CONTENT)
    }
}