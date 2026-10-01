use axum::{
    extract::{Path, State},
    http::StatusCode,
    Json, Extension,
};
use std::sync::Arc;
use validator::Validate;

use crate::{
    AppState,
    auth::{create_token, Claims},
    models::*,
    db,
};

pub async fn health() -> StatusCode {
    StatusCode::OK
}

pub async fn swagger_spec() -> (StatusCode, [(String, String); 1], String) {
    let spec = include_str!("../openapi.yaml");
    (
        StatusCode::OK,
        [("content-type".to_string(), "application/yaml".to_string())],
        spec.to_string(),
    )
}

pub async fn register(
    State(state): State<Arc<AppState>>,
    Json(payload): Json<RegisterRequest>,
) -> Result<(StatusCode, Json<AuthResponse>), (StatusCode, Json<ErrorResponse>)> {
    payload.validate().map_err(|e| {
        (
            StatusCode::BAD_REQUEST,
            Json(ErrorResponse {
                error: format!("Validation error: {}", e),
            }),
        )
    })?;

    let password_hash = bcrypt::hash(&payload.password, bcrypt::DEFAULT_COST)
        .map_err(|_| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ErrorResponse {
                    error: "Failed to hash password".to_string(),
                }),
            )
        })?;

    let user = db::create_user(&state.db, &payload.username, &password_hash)
        .await
        .map_err(|e| {
            (
                StatusCode::BAD_REQUEST,
                Json(ErrorResponse {
                    error: format!("Failed to create user: {}", e),
                }),
            )
        })?;

    let token = create_token(user.id, &user.username, &state.jwt_secret)
        .map_err(|_| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ErrorResponse {
                    error: "Failed to generate token".to_string(),
                }),
            )
        })?;

    Ok((
        StatusCode::CREATED,
        Json(AuthResponse {
            token,
            user: UserResponse {
                id: user.id,
                username: user.username,
                created_at: user.created_at,
            },
        }),
    ))
}

pub async fn login(
    State(state): State<Arc<AppState>>,
    Json(payload): Json<LoginRequest>,
) -> Result<Json<AuthResponse>, (StatusCode, Json<ErrorResponse>)> {
    let user = db::get_user_by_username(&state.db, &payload.username)
        .await
        .map_err(|_| {
            (
                StatusCode::UNAUTHORIZED,
                Json(ErrorResponse {
                    error: "Invalid credentials".to_string(),
                }),
            )
        })?;

    let valid = bcrypt::verify(&payload.password, &user.password_hash)
        .map_err(|_| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ErrorResponse {
                    error: "Authentication error".to_string(),
                }),
            )
        })?;

    if !valid {
        return Err((
            StatusCode::UNAUTHORIZED,
            Json(ErrorResponse {
                error: "Invalid credentials".to_string(),
            }),
        ));
    }

    let token = create_token(user.id, &user.username, &state.jwt_secret)
        .map_err(|_| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ErrorResponse {
                    error: "Failed to generate token".to_string(),
                }),
            )
        })?;

    Ok(Json(AuthResponse {
        token,
        user: UserResponse {
            id: user.id,
            username: user.username,
            created_at: user.created_at,
        },
    }))
}

pub async fn list_products(
    State(state): State<Arc<AppState>>,
) -> Result<Json<Vec<Product>>, (StatusCode, Json<ErrorResponse>)> {
    let products = db::list_products(&state.db)
        .await
        .map_err(|e| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ErrorResponse {
                    error: format!("Failed to fetch products: {}", e),
                }),
            )
        })?;

    Ok(Json(products))
}

pub async fn get_product(
    State(state): State<Arc<AppState>>,
    Path(id): Path<i64>,
) -> Result<Json<Product>, (StatusCode, Json<ErrorResponse>)> {
    let product = db::get_product(&state.db, id)
        .await
        .map_err(|_| {
            (
                StatusCode::NOT_FOUND,
                Json(ErrorResponse {
                    error: "Product not found".to_string(),
                }),
            )
        })?;

    Ok(Json(product))
}

pub async fn create_product(
    State(state): State<Arc<AppState>>,
    Extension(_claims): Extension<Claims>,
    Json(payload): Json<CreateProductRequest>,
) -> Result<(StatusCode, Json<Product>), (StatusCode, Json<ErrorResponse>)> {
    payload.validate().map_err(|e| {
        (
            StatusCode::BAD_REQUEST,
            Json(ErrorResponse {
                error: format!("Validation error: {}", e),
            }),
        )
    })?;

    let product = db::create_product(
        &state.db,
        &payload.name,
        payload.description.as_deref(),
        payload.price,
        payload.quantity,
        &payload.sku,
    )
    .await
    .map_err(|e| {
        (
            StatusCode::BAD_REQUEST,
            Json(ErrorResponse {
                error: format!("Failed to create product: {}", e),
            }),
        )
    })?;

    Ok((StatusCode::CREATED, Json(product)))
}

pub async fn update_product(
    State(state): State<Arc<AppState>>,
    Extension(_claims): Extension<Claims>,
    Path(id): Path<i64>,
    Json(payload): Json<UpdateProductRequest>,
) -> Result<Json<Product>, (StatusCode, Json<ErrorResponse>)> {
    payload.validate().map_err(|e| {
        (
            StatusCode::BAD_REQUEST,
            Json(ErrorResponse {
                error: format!("Validation error: {}", e),
            }),
        )
    })?;

    let product = db::update_product(
        &state.db,
        id,
        payload.name.as_deref(),
        payload.description.as_deref(),
        payload.price,
        payload.quantity,
    )
    .await
    .map_err(|_| {
        (
            StatusCode::NOT_FOUND,
            Json(ErrorResponse {
                error: "Product not found".to_string(),
            }),
        )
    })?;

    Ok(Json(product))
}

pub async fn delete_product(
    State(state): State<Arc<AppState>>,
    Extension(_claims): Extension<Claims>,
    Path(id): Path<i64>,
) -> Result<StatusCode, (StatusCode, Json<ErrorResponse>)> {
    db::delete_product(&state.db, id)
        .await
        .map_err(|_| {
            (
                StatusCode::NOT_FOUND,
                Json(ErrorResponse {
                    error: "Product not found".to_string(),
                }),
            )
        })?;

    Ok(StatusCode::NO_CONTENT)
}

pub async fn list_sales(
    State(state): State<Arc<AppState>>,
    Extension(claims): Extension<Claims>,
) -> Result<Json<Vec<Sale>>, (StatusCode, Json<ErrorResponse>)> {
    let sales = db::list_sales(&state.db, claims.sub)
        .await
        .map_err(|e| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ErrorResponse {
                    error: format!("Failed to fetch sales: {}", e),
                }),
            )
        })?;

    Ok(Json(sales))
}

pub async fn get_sale(
    State(state): State<Arc<AppState>>,
    Extension(claims): Extension<Claims>,
    Path(id): Path<i64>,
) -> Result<Json<SaleResponse>, (StatusCode, Json<ErrorResponse>)> {
    let sale = db::get_sale(&state.db, id, claims.sub)
        .await
        .map_err(|_| {
            (
                StatusCode::NOT_FOUND,
                Json(ErrorResponse {
                    error: "Sale not found".to_string(),
                }),
            )
        })?;

    let items = db::get_sale_items(&state.db, id)
        .await
        .map_err(|e| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ErrorResponse {
                    error: format!("Failed to fetch sale items: {}", e),
                }),
            )
        })?;

    Ok(Json(SaleResponse { sale, items }))
}

pub async fn create_sale(
    State(state): State<Arc<AppState>>,
    Extension(claims): Extension<Claims>,
    Json(payload): Json<CreateSaleRequest>,
) -> Result<(StatusCode, Json<SaleResponse>), (StatusCode, Json<ErrorResponse>)> {
    payload.validate().map_err(|e| {
        (
            StatusCode::BAD_REQUEST,
            Json(ErrorResponse {
                error: format!("Validation error: {}", e),
            }),
        )
    })?;

    let (sale, items) = db::create_sale(&state.db, claims.sub, &payload)
        .await
        .map_err(|e| {
            (
                StatusCode::BAD_REQUEST,
                Json(ErrorResponse {
                    error: format!("Failed to create sale: {}", e),
                }),
            )
        })?;

    Ok((StatusCode::CREATED, Json(SaleResponse { sale, items })))
}