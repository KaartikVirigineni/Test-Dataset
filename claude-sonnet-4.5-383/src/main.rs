mod models;
mod auth;
mod campaigns;
mod db;
mod middleware;

use actix_web::{web, App, HttpServer, HttpResponse, middleware::Logger};
use actix_cors::Cors;
use actix_files as fs;
use std::env;

#[actix_web::main]
async fn main() -> std::io::Result<()> {
    dotenv::dotenv().ok();
    env_logger::init();

    let database_url = env::var("DATABASE_URL")
        .unwrap_or_else(|_| "sqlite:///app/data/campaigns.db".to_string());
    let host = env::var("HOST").unwrap_or_else(|_| "0.0.0.0".to_string());
    let port = env::var("PORT").unwrap_or_else(|_| "8000".to_string());

    let pool = db::create_pool(&database_url).await.expect("Failed to create pool");
    db::run_migrations(&pool).await.expect("Failed to run migrations");

    let bind_addr = format!("{}:{}", host, port);
    log::info!("Starting server at http://{}", bind_addr);

    HttpServer::new(move || {
        let cors = Cors::permissive();

        App::new()
            .app_data(web::Data::new(pool.clone()))
            .wrap(Logger::default())
            .wrap(cors)
            .route("/health", web::get().to(health_check))
            .route("/swagger", web::get().to(get_swagger))
            .service(
                web::scope("/auth")
                    .route("/register", web::post().to(auth::register))
                    .route("/login", web::post().to(auth::login))
            )
            .service(
                web::scope("/campaigns")
                    .wrap(middleware::Auth)
                    .route("", web::get().to(campaigns::list_campaigns))
                    .route("", web::post().to(campaigns::create_campaign))
                    .route("/{id}", web::get().to(campaigns::get_campaign))
                    .route("/{id}", web::put().to(campaigns::update_campaign))
                    .route("/{id}", web::delete().to(campaigns::delete_campaign))
            )
            .service(fs::Files::new("/docs", "./static").index_file("index.html"))
    })
    .bind(&bind_addr)?
    .run()
    .await
}

async fn health_check() -> HttpResponse {
    HttpResponse::Ok().json(serde_json::json!({"status": "ok"}))
}

async fn get_swagger() -> HttpResponse {
    match std::fs::read_to_string("./openapi.yaml") {
        Ok(content) => HttpResponse::Ok()
            .content_type("application/yaml")
            .body(content),
        Err(_) => HttpResponse::NotFound().json(serde_json::json!({"error": "Swagger spec not found"}))
    }
}