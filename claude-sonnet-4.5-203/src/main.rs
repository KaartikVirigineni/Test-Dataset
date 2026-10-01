mod models;
mod handlers;
mod auth;
mod db;
mod middleware;

use actix_web::{web, App, HttpServer, middleware::Logger};
use actix_cors::Cors;
use actix_files::Files;
use std::env;

#[actix_web::main]
async fn main() -> std::io::Result<()> {
    dotenv::dotenv().ok();
    env_logger::init();

    let database_url = env::var("DATABASE_URL")
        .unwrap_or_else(|_| "sqlite://data/portfolio.db".to_string());
    let jwt_secret = env::var("JWT_SECRET")
        .unwrap_or_else(|_| "change-this-secret".to_string());
    let host = env::var("HOST").unwrap_or_else(|_| "0.0.0.0".to_string());
    let port = env::var("PORT").unwrap_or_else(|_| "8000".to_string());

    let pool = db::init_db(&database_url).await.expect("Failed to initialize database");
    
    let jwt_secret_data = web::Data::new(jwt_secret);
    let pool_data = web::Data::new(pool);

    log::info!("Starting server at {}:{}", host, port);

    HttpServer::new(move || {
        let cors = Cors::permissive();

        App::new()
            .app_data(pool_data.clone())
            .app_data(jwt_secret_data.clone())
            .wrap(cors)
            .wrap(Logger::default())
            .service(
                web::scope("/api/v1")
                    .service(handlers::auth::register)
                    .service(handlers::auth::login)
                    .service(handlers::users::get_profile)
                    .service(handlers::users::list_users)
                    .service(handlers::portfolios::create_portfolio)
                    .service(handlers::portfolios::list_portfolios)
                    .service(handlers::portfolios::get_portfolio)
                    .service(handlers::portfolios::update_portfolio)
                    .service(handlers::portfolios::delete_portfolio)
                    .service(handlers::holdings::add_holding)
                    .service(handlers::holdings::list_holdings)
                    .service(handlers::holdings::update_holding)
                    .service(handlers::holdings::delete_holding)
            )
            .service(handlers::swagger::get_swagger_spec)
            .service(Files::new("/swagger-ui", "./swagger-ui").index_file("index.html"))
            .service(handlers::health::health_check)
    })
    .bind(format!("{}:{}", host, port))?
    .run()
    .await
}