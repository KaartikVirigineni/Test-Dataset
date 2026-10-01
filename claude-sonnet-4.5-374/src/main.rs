mod models;
mod handlers;
mod auth;
mod db;

use actix_web::{web, App, HttpServer, middleware};
use actix_cors::Cors;
use actix_files::Files;
use sqlx::sqlite::SqlitePool;
use std::env;

#[actix_web::main]
async fn main() -> std::io::Result<()> {
    dotenv::dotenv().ok();
    env_logger::init();

    let database_url = env::var("DATABASE_URL")
        .unwrap_or_else(|_| "sqlite:///app/data/tickets.db".to_string());
    
    let pool = SqlitePool::connect(&database_url)
        .await
        .expect("Failed to connect to database");

    db::run_migrations(&pool).await.expect("Failed to run migrations");

    let jwt_secret = env::var("JWT_SECRET")
        .unwrap_or_else(|_| "change_this_secret_in_production".to_string());

    log::info!("Starting TicketDesk API on 0.0.0.0:8000");

    HttpServer::new(move || {
        let cors = Cors::permissive();
        
        App::new()
            .wrap(middleware::Logger::default())
            .wrap(cors)
            .app_data(web::Data::new(pool.clone()))
            .app_data(web::Data::new(jwt_secret.clone()))
            .service(handlers::health_check)
            .service(handlers::get_swagger)
            .service(
                web::scope("/auth")
                    .service(handlers::register)
                    .service(handlers::login)
            )
            .service(
                web::scope("/tickets")
                    .service(handlers::list_tickets)
                    .service(handlers::create_ticket)
                    .service(handlers::get_ticket)
                    .service(handlers::update_ticket)
                    .service(handlers::delete_ticket)
            )
            .service(
                web::scope("/users")
                    .service(handlers::get_current_user)
            )
            .service(Files::new("/docs", "./static").index_file("index.html"))
    })
    .bind("0.0.0.0:8000")?
    .run()
    .await
}