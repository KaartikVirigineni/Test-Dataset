use actix_web::{get, HttpResponse, Result};

#[get("/swagger")]
pub async fn get_swagger_spec() -> Result<HttpResponse> {
    let spec = include_str!("../../openapi.yaml");
    Ok(HttpResponse::Ok()
        .content_type("application/yaml")
        .body(spec))
}