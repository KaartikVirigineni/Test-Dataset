mod models;
mod routes;
mod auth;
mod db;

use axum::{
    Router,
    routing::{get, post, put, delete},
};
use sqlx::sqlite::SqlitePoolOptions;
use std::net::SocketAddr;
use tower_http::cors::CorsLayer;
use tower_http::services::ServeDir;
use tracing_subscriber::{layer::SubscriberExt, util::SubscriberInitExt};

#[tokio::main]
async fn main() {
    tracing_subscriber::registry()
        .with(
            tracing_subscriber::EnvFilter::try_from_default_env()
                .unwrap_or_else(|_| "info".into()),
        )
        .with(tracing_subscriber::fmt::layer())
        .init();

    dotenvy::dotenv().ok();

    let database_url = std::env::var("DATABASE_URL")
        .unwrap_or_else(|_| "sqlite:///app/data/streamlite.db".to_string());

    let pool = SqlitePoolOptions::new()
        .max_connections(5)
        .connect(&database_url)
        .await
        .expect("Failed to connect to database");

    db::run_migrations(&pool).await.expect("Failed to run migrations");

    let app_state = models::AppState {
        pool: pool.clone(),
        jwt_secret: std::env::var("JWT_SECRET")
            .unwrap_or_else(|_| "super-secret-key-change-in-production".to_string()),
    };

    let app = Router::new()
        .route("/health", get(routes::health))
        .route("/swagger", get(routes::swagger_spec))
        .route("/auth/register", post(routes::register))
        .route("/auth/login", post(routes::login))
        .route("/videos", get(routes::list_videos).post(routes::create_video))
        .route("/videos/:id", 
            get(routes::get_video)
            .put(routes::update_video)
            .delete(routes::delete_video))
        .nest_service("/docs", ServeDir::new("static"))
        .layer(CorsLayer::permissive())
        .with_state(app_state);

    let addr = SocketAddr::from(([0, 0, 0, 0], 8000));
    tracing::info!("Server listening on {}", addr);

    let listener = tokio::net::TcpListener::bind(addr)
        .await
        .expect("Failed to bind to address");

    axum::serve(listener, app)
        .await
        .expect("Server failed");
}