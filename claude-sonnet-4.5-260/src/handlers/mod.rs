mod auth;
mod documents;
mod swagger;

use actix_web::web;

pub fn config(cfg: &mut web::ServiceConfig) {
    cfg.service(
        web::scope("/api")
            .configure(auth::config)
            .configure(documents::config)
    )
    .configure(swagger::config);
}