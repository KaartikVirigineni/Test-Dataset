mod models;
mod handlers;
mod auth;
mod db;

use actix_web::{web, App, HttpServer, middleware::Logger};
use actix_cors::Cors;
use actix_files as fs;
use std::env;

#[actix_web::main]
async fn main() -> std::io::Result<()> {
    dotenv::dotenv().ok();
    env_logger::init();

    let database_url = env::var("DATABASE_URL")
        .unwrap_or_else(|_| "sqlite://./data/renthub.db".to_string());
    
    let jwt_secret = env::var("JWT_SECRET")
        .unwrap_or_else(|_| "default_secret_key".to_string());
    
    let host = env::var("HOST").unwrap_or_else(|_| "127.0.0.1".to_string());
    let port = env::var("PORT").unwrap_or_else(|_| "8000".to_string());
    
    let pool = db::init_db(&database_url).await.expect("Failed to initialize database");
    
    let bind_addr = format!("{}:{}", host, port);
    log::info!("Starting server at http://{}", bind_addr);

    HttpServer::new(move || {
        let cors = Cors::permissive();
        
        App::new()
            .app_data(web::Data::new(pool.clone()))
            .app_data(web::Data::new(jwt_secret.clone()))
            .wrap(Logger::default())
            .wrap(cors)
            .route("/health", web::get().to(handlers::health_check))
            .route("/swagger", web::get().to(handlers::get_swagger))
            .service(
                web::scope("/api/auth")
                    .route("/register", web::post().to(handlers::register))
                    .route("/login", web::post().to(handlers::login))
            )
            .service(
                web::scope("/api/rentals")
                    .route("", web::get().to(handlers::list_rentals))
                    .route("", web::post().to(handlers::create_rental))
                    .route("/{id}", web::get().to(handlers::get_rental))
                    .route("/{id}", web::put().to(handlers::update_rental))
                    .route("/{id}", web::delete().to(handlers::delete_rental))
            )
            .service(
                web::scope("/api/bookings")
                    .route("", web::get().to(handlers::list_bookings))
                    .route("", web::post().to(handlers::create_booking))
                    .route("/{id}", web::get().to(handlers::get_booking))
                    .route("/{id}", web::delete().to(handlers::cancel_booking))
            )
            .service(fs::Files::new("/docs", "./static").index_file("swagger-ui.html"))
    })
    .bind(&bind_addr)?
    .run()
    .await
}