use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;

#[derive(Debug, Serialize, Deserialize, sqlx::FromRow)]
pub struct User {
    pub id: String,
    pub username: String,
    pub password_hash: String,
    pub created_at: DateTime<Utc>,
}

#[derive(Debug, Deserialize)]
pub struct RegisterRequest {
    pub username: String,
    pub password: String,
}

#[derive(Debug, Deserialize)]
pub struct LoginRequest {
    pub username: String,
    pub password: String,
}

#[derive(Debug, Serialize)]
pub struct LoginResponse {
    pub token: String,
}

#[derive(Debug, Serialize)]
pub struct UserResponse {
    pub id: String,
    pub username: String,
    pub created_at: DateTime<Utc>,
}

#[derive(Debug, Serialize, Deserialize, sqlx::FromRow)]
pub struct LogEntry {
    pub id: String,
    pub timestamp: DateTime<Utc>,
    pub level: String,
    pub source: String,
    pub message: String,
    pub metadata: Option<String>,
}

#[derive(Debug, Deserialize)]
pub struct CreateLogRequest {
    pub level: String,
    pub source: String,
    pub message: String,
    pub metadata: Option<serde_json::Value>,
}

#[derive(Debug, Serialize)]
pub struct LogResponse {
    pub id: String,
    pub timestamp: DateTime<Utc>,
    pub level: String,
    pub source: String,
    pub message: String,
    pub metadata: Option<serde_json::Value>,
}

#[derive(Debug, Serialize)]
pub struct LogSearchResponse {
    pub logs: Vec<LogResponse>,
    pub total: i64,
    pub page: i64,
    pub page_size: i64,
}

impl From<LogEntry> for LogResponse {
    fn from(entry: LogEntry) -> Self {
        LogResponse {
            id: entry.id,
            timestamp: entry.timestamp,
            level: entry.level,
            source: entry.source,
            message: entry.message,
            metadata: entry
                .metadata
                .and_then(|m| serde_json::from_str(&m).ok()),
        }
    }
}

#[derive(Debug, Deserialize)]
pub struct LogSearchQuery {
    pub q: Option<String>,
    pub level: Option<String>,
    pub source: Option<String>,
    pub page: Option<i64>,
    pub page_size: Option<i64>,
}