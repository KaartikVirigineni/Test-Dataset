use crate::auth::decode_jwt;
use crate::models::Claims;
use actix_web::{dev::ServiceRequest, Error, HttpMessage};
use actix_web::error::ErrorUnauthorized;

pub fn extract_user_from_request(req: &ServiceRequest, jwt_secret: &str) -> Result<Claims, Error> {
    let auth_header = req
        .headers()
        .get("Authorization")
        .and_then(|h| h.to_str().ok())
        .ok_or_else(|| ErrorUnauthorized("Missing authorization header"))?;

    if !auth_header.starts_with("Bearer ") {
        return Err(ErrorUnauthorized("Invalid authorization header format"));
    }

    let token = &auth_header[7..];
    let claims = decode_jwt(token, jwt_secret)
        .map_err(|_| ErrorUnauthorized("Invalid or expired token"))?;

    Ok(claims)
}

pub fn get_user_id_from_extensions(req: &actix_web::HttpRequest) -> Result<String, Error> {
    req.extensions()
        .get::<String>()
        .cloned()
        .ok_or_else(|| ErrorUnauthorized("User not authenticated"))
}