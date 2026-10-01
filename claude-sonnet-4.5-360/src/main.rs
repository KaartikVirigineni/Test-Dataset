use axum::{
    Router,
    routing::{get, post},
};
use sqlx::sqlite::SqlitePool;
use std::sync::Arc;
use tower_http::cors::CorsLayer;
use tower_http::services::ServeDir;
use tracing_subscriber;

mod auth;
mod handlers;
mod models;
mod db;

pub struct AppState {
    pub db: SqlitePool,
    pub jwt_secret: String,
}

#[tokio::main]
async fn main() {
    tracing_subscriber::fmt::init();

    let database_url = std::env::var("DATABASE_URL")
        .unwrap_or_else(|_| "sqlite:///app/data/pos.db".to_string());
    
    let jwt_secret = std::env::var("JWT_SECRET")
        .unwrap_or_else(|_| "super_secret_key_change_in_production".to_string());

    let pool = SqlitePool::connect(&database_url)
        .await
        .expect("Failed to connect to database");

    db::run_migrations(&pool).await.expect("Failed to run migrations");

    let state = Arc::new(AppState {
        db: pool,
        jwt_secret,
    });

    let app = Router::new()
        .route("/health", get(handlers::health))
        .route("/swagger", get(handlers::swagger_spec))
        .nest_service("/swagger-ui", ServeDir::new("swagger-ui"))
        .route("/auth/register", post(handlers::register))
        .route("/auth/login", post(handlers::login))
        .route("/products", get(handlers::list_products))
        .route("/products", post(handlers::create_product))
        .route("/products/:id", get(handlers::get_product))
        .route("/products/:id", axum::routing::put(handlers::update_product))
        .route("/products/:id", axum::routing::delete(handlers::delete_product))
        .route("/sales", get(handlers::list_sales))
        .route("/sales", post(handlers::create_sale))
        .route("/sales/:id", get(handlers::get_sale))
        .layer(CorsLayer::permissive())
        .with_state(state);

    let listener = tokio::net::TcpListener::bind("0.0.0.0:8000")
        .await
        .expect("Failed to bind to port 8000");

    tracing::info!("Server running on http://0.0.0.0:8000");
    tracing::info!("Swagger UI available at http://0.0.0.0:8000/swagger-ui");
    
    axum::serve(listener, app)
        .await
        .expect("Failed to start server");
}