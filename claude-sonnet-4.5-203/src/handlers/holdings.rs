use crate::middleware::require_auth;
use crate::models::{CreateHoldingRequest, ErrorResponse, Holding, UpdateHoldingRequest};
use actix_web::{delete, get, post, put, web, HttpRequest, HttpResponse, Result};
use sqlx::SqlitePool;
use validator::Validate;

#[post("/portfolios/{portfolio_id}/holdings")]
pub async fn add_holding(
    req: HttpRequest,
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    path: web::Path<String>,
    payload: web::Json<CreateHoldingRequest>,
) -> Result<HttpResponse> {
    let user_id = require_auth(&req, secret.get_ref())?;
    let portfolio_id = path.into_inner();

    if let Err(e) = payload.validate() {
        return Ok(HttpResponse::BadRequest().json(ErrorResponse {
            error: format!("Validation error: {}", e),
        }));
    }

    let portfolio_exists = sqlx::query_as::<_, (String,)>(
        "SELECT id FROM portfolios WHERE id = ? AND user_id = ?"
    )
    .bind(&portfolio_id)
    .bind(&user_id)
    .fetch_optional(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    if portfolio_exists.is_none() {
        return Ok(HttpResponse::NotFound().json(ErrorResponse {
            error: "Portfolio not found".to_string(),
        }));
    }

    let holding_id = uuid::Uuid::new_v4().to_string();
    let now = chrono::Utc::now();
    let purchase_date = payload.purchase_date.unwrap_or(now);

    sqlx::query(
        "INSERT INTO holdings (id, portfolio_id, symbol, quantity, purchase_price, purchase_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    )
    .bind(&holding_id)
    .bind(&portfolio_id)
    .bind(&payload.symbol)
    .bind(payload.quantity)
    .bind(payload.purchase_price)
    .bind(purchase_date.to_rfc3339())
    .bind(now.to_rfc3339())
    .bind(now.to_rfc3339())
    .execute(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    let holding = Holding {
        id: holding_id,
        portfolio_id,
        symbol: payload.symbol.clone(),
        quantity: payload.quantity,
        purchase_price: payload.purchase_price,
        purchase_date,
        created_at: now,
        updated_at: now,
    };

    Ok(HttpResponse::Created().json(holding))
}

#[get("/portfolios/{portfolio_id}/holdings")]
pub async fn list_holdings(
    req: HttpRequest,
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    path: web::Path<String>,
) -> Result<HttpResponse> {
    let user_id = require_auth(&req, secret.get_ref())?;
    let portfolio_id = path.into_inner();

    let portfolio_exists = sqlx::query_as::<_, (String,)>(
        "SELECT id FROM portfolios WHERE id = ? AND user_id = ?"
    )
    .bind(&portfolio_id)
    .bind(&user_id)
    .fetch_optional(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    if portfolio_exists.is_none() {
        return Ok(HttpResponse::NotFound().json(ErrorResponse {
            error: "Portfolio not found".to_string(),
        }));
    }

    let holdings = sqlx::query_as::<_, (String, String, String, f64, f64, String, String, String)>(
        "SELECT id, portfolio_id, symbol, quantity, purchase_price, purchase_date, created_at, updated_at FROM holdings WHERE portfolio_id = ? ORDER BY created_at DESC"
    )
    .bind(&portfolio_id)
    .fetch_all(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    let holding_list: Vec<Holding> = holdings
        .into_iter()
        .map(|(id, portfolio_id, symbol, quantity, purchase_price, purchase_date, created_at, updated_at)| Holding {
            id,
            portfolio_id,
            symbol,
            quantity,
            purchase_price,
            purchase_date: purchase_date.parse().unwrap_or_else(|_| chrono::Utc::now()),
            created_at: created_at.parse().unwrap_or_else(|_| chrono::Utc::now()),
            updated_at: updated_at.parse().unwrap_or_else(|_| chrono::Utc::now()),
        })
        .collect();

    Ok(HttpResponse::Ok().json(holding_list))
}

#[put("/holdings/{id}")]
pub async fn update_holding(
    req: HttpRequest,
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    path: web::Path<String>,
    payload: web::Json<UpdateHoldingRequest>,
) -> Result<HttpResponse> {
    let user_id = require_auth(&req, secret.get_ref())?;
    let holding_id = path.into_inner();

    if let Err(e) = payload.validate() {
        return Ok(HttpResponse::BadRequest().json(ErrorResponse {
            error: format!("Validation error: {}", e),
        }));
    }

    let holding_check = sqlx::query_as::<_, (String, String)>(
        "SELECT h.id, p.user_id FROM holdings h JOIN portfolios p ON h.portfolio_id = p.id WHERE h.id = ?"
    )
    .bind(&holding_id)
    .fetch_optional(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    match holding_check {
        Some((_, owner_id)) if owner_id != user_id => {
            return Ok(HttpResponse::Forbidden().json(ErrorResponse {
                error: "Access denied".to_string(),
            }));
        }
        None => {
            return Ok(HttpResponse::NotFound().json(ErrorResponse {
                error: "Holding not found".to_string(),
            }));
        }
        _ => {}
    }

    let now = chrono::Utc::now();

    if let Some(quantity) = payload.quantity {
        sqlx::query("UPDATE holdings SET quantity = ?, updated_at = ? WHERE id = ?")
            .bind(quantity)
            .bind(now.to_rfc3339())
            .bind(&holding_id)
            .execute(pool.get_ref())
            .await
            .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;
    }

    if let Some(purchase_price) = payload.purchase_price {
        sqlx::query("UPDATE holdings SET purchase_price = ?, updated_at = ? WHERE id = ?")
            .bind(purchase_price)
            .bind(now.to_rfc3339())
            .bind(&holding_id)
            .execute(pool.get_ref())
            .await
            .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;
    }

    let holding = sqlx::query_as::<_, (String, String, String, f64, f64, String, String, String)>(
        "SELECT id, portfolio_id, symbol, quantity, purchase_price, purchase_date, created_at, updated_at FROM holdings WHERE id = ?"
    )
    .bind(&holding_id)
    .fetch_one(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    let updated_holding = Holding {
        id: holding.0,
        portfolio_id: holding.1,
        symbol: holding.2,
        quantity: holding.3,
        purchase_price: holding.4,
        purchase_date: holding.5.parse().unwrap_or_else(|_| chrono::Utc::now()),
        created_at: holding.6.parse().unwrap_or_else(|_| chrono::Utc::now()),
        updated_at: holding.7.parse().unwrap_or_else(|_| chrono::Utc::now()),
    };

    Ok(HttpResponse::Ok().json(updated_holding))
}

#[delete("/holdings/{id}")]
pub async fn delete_holding(
    req: HttpRequest,
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    path: web::Path<String>,
) -> Result<HttpResponse> {
    let user_id = require_auth(&req, secret.get_ref())?;
    let holding_id = path.into_inner();

    let holding_check = sqlx::query_as::<_, (String, String)>(
        "SELECT h.id, p.user_id FROM holdings h JOIN portfolios p ON h.portfolio_id = p.id WHERE h.id = ?"
    )
    .bind(&holding_id)
    .fetch_optional(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    match holding_check {
        Some((_, owner_id)) if owner_id != user_id => {
            return Ok(HttpResponse::Forbidden().json(ErrorResponse {
                error: "Access denied".to_string(),
            }));
        }
        None => {
            return Ok(HttpResponse::NotFound().json(ErrorResponse {
                error: "Holding not found".to_string(),
            }));
        }
        _ => {}
    }

    sqlx::query("DELETE FROM holdings WHERE id = ?")
        .bind(&holding_id)
        .execute(pool.get_ref())
        .await
        .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    Ok(HttpResponse::NoContent().finish())
}