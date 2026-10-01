use axum::{
    extract::State,
    http::StatusCode,
    response::IntoResponse,
    routing::{delete, get, post},
    Json, Router,
};
use sqlx::sqlite::SqlitePool;
use std::sync::Arc;
use tower_http::cors::CorsLayer;
use tower_http::services::ServeDir;
use tracing_subscriber;

mod auth;
mod db;
mod handlers;
mod models;

#[derive(Clone)]
pub struct AppState {
    db: SqlitePool,
    jwt_secret: String,
}

#[tokio::main]
async fn main() {
    tracing_subscriber::fmt::init();

    let database_url = std::env::var("DATABASE_URL")
        .unwrap_or_else(|_| "sqlite:///data/loglite.db".to_string());
    let jwt_secret =
        std::env::var("JWT_SECRET").unwrap_or_else(|_| "dev-secret-key".to_string());

    std::fs::create_dir_all("/data").ok();

    let pool = SqlitePool::connect(&database_url)
        .await
        .expect("Failed to connect to database");

    db::init_db(&pool).await.expect("Failed to initialize database");

    let state = Arc::new(AppState {
        db: pool,
        jwt_secret,
    });

    let app = Router::new()
        .route("/health", get(health_check))
        .route("/swagger", get(serve_openapi))
        .route("/auth/register", post(handlers::register))
        .route("/auth/login", post(handlers::login))
        .route("/logs", get(handlers::list_logs).post(handlers::create_log))
        .route(
            "/logs/:id",
            get(handlers::get_log).delete(handlers::delete_log),
        )
        .nest_service("/swagger-ui", ServeDir::new("static"))
        .layer(CorsLayer::permissive())
        .with_state(state);

    let listener = tokio::net::TcpListener::bind("0.0.0.0:8000")
        .await
        .expect("Failed to bind port");

    tracing::info!("Server running on http://0.0.0.0:8000");
    axum::serve(listener, app)
        .await
        .expect("Failed to start server");
}

async fn health_check() -> impl IntoResponse {
    Json(serde_json::json!({"status": "ok"}))
}

async fn serve_openapi() -> impl IntoResponse {
    let yaml = include_str!("../openapi.yaml");
    (
        StatusCode::OK,
        [("content-type", "application/yaml")],
        yaml,
    )
}