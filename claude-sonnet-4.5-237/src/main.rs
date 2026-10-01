mod auth;
mod db;
mod handlers;
mod models;
mod middleware;

use actix_web::{web, App, HttpServer, middleware::Logger};
use actix_cors::Cors;
use sqlx::sqlite::SqlitePool;
use std::sync::Arc;

pub struct AppState {
    pub db: SqlitePool,
    pub jwt_secret: String,
}

#[actix_web::main]
async fn main() -> std::io::Result<()> {
    dotenv::dotenv().ok();
    env_logger::init();

    let database_url = std::env::var("DATABASE_URL")
        .unwrap_or_else(|_| "sqlite:///app/data/presence.db".to_string());
    
    let jwt_secret = std::env::var("JWT_SECRET")
        .unwrap_or_else(|_| "change-this-secret-in-production".to_string());

    log::info!("Connecting to database: {}", database_url);
    
    let pool = SqlitePool::connect(&database_url)
        .await
        .expect("Failed to connect to database");

    db::run_migrations(&pool).await.expect("Failed to run migrations");

    let app_state = Arc::new(AppState {
        db: pool,
        jwt_secret,
    });

    log::info!("Starting server on 0.0.0.0:8000");

    HttpServer::new(move || {
        let cors = Cors::permissive();

        App::new()
            .app_data(web::Data::new(app_state.clone()))
            .wrap(Logger::default())
            .wrap(cors)
            .route("/health", web::get().to(handlers::health_check))
            .route("/swagger", web::get().to(handlers::swagger_spec))
            .service(
                web::scope("/api/auth")
                    .route("/register", web::post().to(handlers::register))
                    .route("/login", web::post().to(handlers::login))
            )
            .service(
                web::scope("/api/sessions")
                    .route("", web::get().to(handlers::list_sessions))
                    .route("", web::post().to(handlers::create_session))
                    .route("/{id}", web::get().to(handlers::get_session))
                    .route("/{id}", web::put().to(handlers::update_session))
                    .route("/{id}", web::delete().to(handlers::delete_session))
            )
            .service(
                web::scope("/api/users")
                    .route("", web::get().to(handlers::list_users))
                    .route("/{id}", web::delete().to(handlers::delete_user))
            )
            .service(actix_files::Files::new("/", "./static").index_file("index.html"))
    })
    .bind("0.0.0.0:8000")?
    .run()
    .await
}