use actix_web::{
    dev::{forward_ready, Service, ServiceRequest, ServiceResponse, Transform},
    Error, HttpMessage, HttpResponse,
};
use futures::future::LocalBoxFuture;
use jsonwebtoken::{decode, DecodingKey, Validation};
use std::env;
use std::future::{ready, Ready};

use crate::models::Claims;

pub struct Auth;

impl<S, B> Transform<S, ServiceRequest> for Auth
where
    S: Service<ServiceRequest, Response = ServiceResponse<B>, Error = Error>,
    S::Future: 'static,
    B: 'static,
{
    type Response = ServiceResponse<B>;
    type Error = Error;
    type InitError = ();
    type Transform = AuthMiddleware<S>;
    type Future = Ready<Result<Self::Transform, Self::InitError>>;

    fn new_transform(&self, service: S) -> Self::Future {
        ready(Ok(AuthMiddleware { service }))
    }
}

pub struct AuthMiddleware<S> {
    service: S,
}

impl<S, B> Service<ServiceRequest> for AuthMiddleware<S>
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

        let token = match auth_header {
            Some(header) => match header.to_str() {
                Ok(h) => {
                    if h.starts_with("Bearer ") {
                        h.trim_start_matches("Bearer ")
                    } else {
                        return Box::pin(async {
                            Ok(req.into_response(
                                HttpResponse::Unauthorized()
                                    .json(serde_json::json!({"error": "Invalid authorization header"}))
                                    .into_body(),
                            ))
                        });
                    }
                }
                Err(_) => {
                    return Box::pin(async {
                        Ok(req.into_response(
                            HttpResponse::Unauthorized()
                                .json(serde_json::json!({"error": "Invalid authorization header"}))
                                .into_body(),
                        ))
                    });
                }
            },
            None => {
                return Box::pin(async {
                    Ok(req.into_response(
                        HttpResponse::Unauthorized()
                            .json(serde_json::json!({"error": "Missing authorization header"}))
                            .into_body(),
                    ))
                });
            }
        };

        let jwt_secret = env::var("JWT_SECRET").unwrap_or_else(|_| "secret".to_string());
        let token_data = decode::<Claims>(
            token,
            &DecodingKey::from_secret(jwt_secret.as_ref()),
            &Validation::default(),
        );

        match token_data {
            Ok(data) => {
                req.extensions_mut().insert(data.claims.sub.clone());
                let fut = self.service.call(req);
                Box::pin(async move {
                    let res = fut.await?;
                    Ok(res)
                })
            }
            Err(_) => Box::pin(async {
                Ok(req.into_response(
                    HttpResponse::Unauthorized()
                        .json(serde_json::json!({"error": "Invalid token"}))
                        .into_body(),
                ))
            }),
        }
    }
}