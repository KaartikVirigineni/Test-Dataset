use crate::auth::extract_claims;
use actix_web::{Error, HttpRequest};

pub fn require_auth(req: &HttpRequest, secret: &str) -> Result<String, Error> {
    let claims = extract_claims(req, secret)?;
    Ok(claims.sub)
}

pub fn require_role(req: &HttpRequest, secret: &str, allowed_roles: &[&str]) -> Result<String, Error> {
    let claims = extract_claims(req, secret)?;
    
    if !allowed_roles.contains(&claims.role.as_str()) {
        return Err(actix_web::error::ErrorForbidden("Insufficient permissions"));
    }
    
    Ok(claims.sub)
}