use actix_web::{web, HttpResponse, Responder};
use actix_files::NamedFile;

pub fn config(cfg: &mut web::ServiceConfig) {
    cfg.route("/swagger", web::get().to(get_swagger_spec))
       .route("/docs", web::get().to(swagger_ui));
}

async fn get_swagger_spec() -> impl Responder {
    match NamedFile::open("openapi.yaml") {
        Ok(file) => file
            .customize()
            .insert_header(("Content-Type", "application/yaml"))
            .respond_to(&actix_web::HttpRequest::default())
            .await,
        Err(_) => Ok(HttpResponse::NotFound().body("Swagger spec not found")),
    }
}

async fn swagger_ui() -> impl Responder {
    HttpResponse::Ok()
        .content_type("text/html")
        .body(include_str!("../../swagger-ui/index.html"))
}