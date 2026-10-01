mod auth;
mod trips;
mod itineraries;
mod activities;
mod swagger;

use actix_web::web;

pub fn configure_routes(cfg: &mut web::ServiceConfig) {
    cfg.service(
        web::scope("/api")
            .configure(auth::configure)
            .configure(trips::configure)
            .configure(itineraries::configure)
            .configure(activities::configure)
    )
    .configure(swagger::configure);
}