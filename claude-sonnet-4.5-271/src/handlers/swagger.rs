use actix_web::{web, HttpResponse, Responder};
use actix_files::NamedFile;

pub fn configure(cfg: &mut web::ServiceConfig) {
    cfg.route("/swagger", web::get().to(get_swagger_spec))
        .route("/docs", web::get().to(get_swagger_ui));
}

async fn get_swagger_spec() -> impl Responder {
    match NamedFile::open("./openapi.yaml") {
        Ok(file) => file
            .customize()
            .insert_header(("Content-Type", "application/yaml")),
        Err(_) => return HttpResponse::NotFound().body("Swagger spec not found"),
    }
}

async fn get_swagger_ui() -> impl Responder {
    match NamedFile::open("./static/swagger-ui.html") {
        Ok(file) => file
            .customize()
            .insert_header(("Content-Type", "text/html")),
        Err(_) => return HttpResponse::NotFound().body("Swagger UI not found"),
    }
}