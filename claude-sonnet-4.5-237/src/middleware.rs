use crate::auth;
use crate::models::{Claims, Role};
use actix_web::{
    dev::{forward_ready, Service, ServiceRequest, ServiceResponse, Transform},
    Error, HttpMessage, HttpResponse,
};
use futures::future::LocalBoxFuture;
use std::future::{ready, Ready};

pub struct AuthMiddleware {
    pub jwt_secret: String,
}

impl<S, B> Transform<S, ServiceRequest> for AuthMiddleware
where
    S: Service<ServiceRequest, Response = ServiceResponse<B>, Error = Error>,
    S::Future: 'static,
    B: 'static,
{
    type Response = ServiceResponse<B>;
    type Error = Error;
    type InitError = ();
    type Transform = AuthMiddlewareService<S>;
    type Future = Ready<Result<Self::Transform, Self::InitError>>;

    fn new_transform(&self, service: S) -> Self::Future {
        ready(Ok(AuthMiddlewareService {
            service,
            jwt_secret: self.jwt_secret.clone(),
        }))
    }
}

pub struct AuthMiddlewareService<S> {
    service: S,
    jwt_secret: String,
}

impl<S, B> Service<ServiceRequest> for AuthMiddlewareService<S>
where
    S: Service<ServiceRequest, Response = ServiceResponse<B>, Error = Error>,
    S::Future: 'static,
    B: 'static,
{
    type Response = ServiceResponse<B>;
    type Error = Error;
    type Future = LocalBoxFuture<'static, Result<Self::Response, Self::Error>>;

    forward_ready!(service);

    fn call(&self, req: ServiceRequest) -> Self::Future {
        let auth_header = req.headers().get("Authorization");
        
        let token = auth_header
            .and_then(|h| h.to_str().ok())
            .and_then(|h| {
                if h.starts_with("Bearer ") {
                    Some(h[7..].to_string())
                } else {
                    None
                }
            });

        let jwt_secret = self.jwt_secret.clone();

        if let Some(token) = token {
            match auth::verify_jwt(&token, &jwt_secret) {
                Ok(claims) => {
                    req.extensions_mut().insert(claims);
                    let fut = self.service.call(req);
                    Box::pin(async move {
                        let res = fut.await?;
                        Ok(res)
                    })
                }
                Err(_) => {
                    Box::pin(async {
                        Err(actix_web::error::ErrorUnauthorized("Invalid token"))
                    })
                }
            }
        } else {
            Box::pin(async {
                Err(actix_web::error::ErrorUnauthorized("Missing authorization header"))
            })
        }
    }
}

pub fn extract_claims(req: &actix_web::HttpRequest) -> Result<Claims, HttpResponse> {
    req.extensions()
        .get::<Claims>()
        .cloned()
        .ok_or_else(|| {
            HttpResponse::Unauthorized().json(serde_json::json!({"error": "Unauthorized"}))
        })
}

pub fn require_role(claims: &Claims, required_role: Role) -> Result<(), HttpResponse> {
    match (&claims.role, &required_role) {
        (Role::Admin, _) => Ok(()),
        (user_role, req_role) if std::mem::discriminant(user_role) == std::mem::discriminant(req_role) => Ok(()),
        _ => Err(HttpResponse::Forbidden().json(serde_json::json!({"error": "Insufficient permissions"}))),
    }
}