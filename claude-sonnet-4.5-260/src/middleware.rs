use crate::auth::{verify_jwt, Claims};
use crate::models::ErrorResponse;
use actix_web::{
    dev::{forward_ready, Service, ServiceRequest, ServiceResponse, Transform},
    Error, HttpMessage, HttpResponse,
    web,
};
use futures::future::LocalBoxFuture;
use std::future::{ready, Ready};

pub struct AuthMiddleware {
    pub required_roles: Vec<String>,
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
            required_roles: self.required_roles.clone(),
        }))
    }
}

pub struct AuthMiddlewareService<S> {
    service: S,
    required_roles: Vec<String>,
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
        let required_roles = self.required_roles.clone();
        
        let auth_header = req.headers().get("Authorization");
        
        if auth_header.is_none() {
            let response = HttpResponse::Unauthorized().json(ErrorResponse {
                error: "Missing authorization header".to_string(),
            });
            return Box::pin(async { Ok(req.into_response(response)) });
        }

        let auth_str = auth_header.unwrap().to_str().unwrap_or("");
        if !auth_str.starts_with("Bearer ") {
            let response = HttpResponse::Unauthorized().json(ErrorResponse {
                error: "Invalid authorization header format".to_string(),
            });
            return Box::pin(async { Ok(req.into_response(response)) });
        }

        let token = &auth_str[7..];
        let secret = req.app_data::<web::Data<String>>().unwrap().get_ref().clone();

        match verify_jwt(token, &secret) {
            Ok(claims) => {
                if !required_roles.is_empty() && !required_roles.contains(&claims.role) {
                    let response = HttpResponse::Forbidden().json(ErrorResponse {
                        error: "Insufficient permissions".to_string(),
                    });
                    return Box::pin(async { Ok(req.into_response(response)) });
                }

                req.extensions_mut().insert(claims);
                let fut = self.service.call(req);
                Box::pin(async move {
                    let res = fut.await?;
                    Ok(res)
                })
            }
            Err(_) => {
                let response = HttpResponse::Unauthorized().json(ErrorResponse {
                    error: "Invalid or expired token".to_string(),
                });
                Box::pin(async { Ok(req.into_response(response)) })
            }
        }
    }
}