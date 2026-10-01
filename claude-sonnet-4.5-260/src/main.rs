mod models;
mod handlers;
mod auth;
mod db;
mod middleware;

use actix_web::{web, App, HttpServer, middleware::Logger};
use actix_cors::Cors;
use sqlx::SqlitePool;
use std::env;

#[actix_web::main]
async fn main() -> std::io::Result<()> {
    dotenv::dotenv().ok();
    env_logger::init();

    let database_url = env::var("DATABASE_URL")
        .unwrap_or_else(|_| "sqlite:///app/data/docuvault.db".to_string());
    let jwt_secret = env::var("JWT_SECRET")
        .unwrap_or_else(|_| "change-this-secret-in-production-please-use-a-long-random-string".to_string());
    let host = env::var("HOST").unwrap_or_else(|_| "0.0.0.0".to_string());
    let port = env::var("PORT").unwrap_or_else(|_| "8000".to_string());

    let pool = db::init_db(&database_url).await.expect("Failed to initialize database");
    db::run_migrations(&pool).await.expect("Failed to run migrations");

    let bind_address = format!("{}:{}", host, port);
    log::info!("Starting server at http://{}", bind_address);

    HttpServer::new(move || {
        let cors = Cors::permissive();

        App::new()
            .app_data(web::Data::new(pool.clone()))
            .app_data(web::Data::new(jwt_secret.clone()))
            .wrap(cors)
            .wrap(Logger::default())
            .configure(handlers::config)
    })
    .bind(&bind_address)?
    .run()
    .await
}