use crate::middleware::require_auth;
use crate::models::{CreatePortfolioRequest, ErrorResponse, Portfolio, UpdatePortfolioRequest};
use actix_web::{delete, get, post, put, web, HttpRequest, HttpResponse, Result};
use sqlx::SqlitePool;
use validator::Validate;

#[post("/portfolios")]
pub async fn create_portfolio(
    req: HttpRequest,
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    payload: web::Json<CreatePortfolioRequest>,
) -> Result<HttpResponse> {
    let user_id = require_auth(&req, secret.get_ref())?;

    if let Err(e) = payload.validate() {
        return Ok(HttpResponse::BadRequest().json(ErrorResponse {
            error: format!("Validation error: {}", e),
        }));
    }

    let portfolio_id = uuid::Uuid::new_v4().to_string();
    let now = chrono::Utc::now();

    sqlx::query(
        "INSERT INTO portfolios (id, user_id, name, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)"
    )
    .bind(&portfolio_id)
    .bind(&user_id)
    .bind(&payload.name)
    .bind(&payload.description)
    .bind(now.to_rfc3339())
    .bind(now.to_rfc3339())
    .execute(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    let portfolio = Portfolio {
        id: portfolio_id,
        user_id,
        name: payload.name.clone(),
        description: payload.description.clone(),
        created_at: now,
        updated_at: now,
    };

    Ok(HttpResponse::Created().json(portfolio))
}

#[get("/portfolios")]
pub async fn list_portfolios(
    req: HttpRequest,
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
) -> Result<HttpResponse> {
    let user_id = require_auth(&req, secret.get_ref())?;

    let portfolios = sqlx::query_as::<_, (String, String, String, Option<String>, String, String)>(
        "SELECT id, user_id, name, description, created_at, updated_at FROM portfolios WHERE user_id = ? ORDER BY created_at DESC"
    )
    .bind(&user_id)
    .fetch_all(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    let portfolio_list: Vec<Portfolio> = portfolios
        .into_iter()
        .map(|(id, user_id, name, description, created_at, updated_at)| Portfolio {
            id,
            user_id,
            name,
            description,
            created_at: created_at.parse().unwrap_or_else(|_| chrono::Utc::now()),
            updated_at: updated_at.parse().unwrap_or_else(|_| chrono::Utc::now()),
        })
        .collect();

    Ok(HttpResponse::Ok().json(portfolio_list))
}

#[get("/portfolios/{id}")]
pub async fn get_portfolio(
    req: HttpRequest,
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    path: web::Path<String>,
) -> Result<HttpResponse> {
    let user_id = require_auth(&req, secret.get_ref())?;
    let portfolio_id = path.into_inner();

    let portfolio = sqlx::query_as::<_, (String, String, String, Option<String>, String, String)>(
        "SELECT id, user_id, name, description, created_at, updated_at FROM portfolios WHERE id = ? AND user_id = ?"
    )
    .bind(&portfolio_id)
    .bind(&user_id)
    .fetch_optional(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    match portfolio {
        Some((id, user_id, name, description, created_at, updated_at)) => {
            let portfolio = Portfolio {
                id,
                user_id,
                name,
                description,
                created_at: created_at.parse().unwrap_or_else(|_| chrono::Utc::now()),
                updated_at: updated_at.parse().unwrap_or_else(|_| chrono::Utc::now()),
            };
            Ok(HttpResponse::Ok().json(portfolio))
        }
        None => Ok(HttpResponse::NotFound().json(ErrorResponse {
            error: "Portfolio not found".to_string(),
        })),
    }
}

#[put("/portfolios/{id}")]
pub async fn update_portfolio(
    req: HttpRequest,
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    path: web::Path<String>,
    payload: web::Json<UpdatePortfolioRequest>,
) -> Result<HttpResponse> {
    let user_id = require_auth(&req, secret.get_ref())?;
    let portfolio_id = path.into_inner();

    if let Err(e) = payload.validate() {
        return Ok(HttpResponse::BadRequest().json(ErrorResponse {
            error: format!("Validation error: {}", e),
        }));
    }

    let existing = sqlx::query_as::<_, (String,)>(
        "SELECT id FROM portfolios WHERE id = ? AND user_id = ?"
    )
    .bind(&portfolio_id)
    .bind(&user_id)
    .fetch_optional(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    if existing.is_none() {
        return Ok(HttpResponse::NotFound().json(ErrorResponse {
            error: "Portfolio not found".to_string(),
        }));
    }

    let now = chrono::Utc::now();

    if let Some(ref name) = payload.name {
        sqlx::query("UPDATE portfolios SET name = ?, updated_at = ? WHERE id = ?")
            .bind(name)
            .bind(now.to_rfc3339())
            .bind(&portfolio_id)
            .execute(pool.get_ref())
            .await
            .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;
    }

    if let Some(ref description) = payload.description {
        sqlx::query("UPDATE portfolios SET description = ?, updated_at = ? WHERE id = ?")
            .bind(description)
            .bind(now.to_rfc3339())
            .bind(&portfolio_id)
            .execute(pool.get_ref())
            .await
            .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;
    }

    let portfolio = sqlx::query_as::<_, (String, String, String, Option<String>, String, String)>(
        "SELECT id, user_id, name, description, created_at, updated_at FROM portfolios WHERE id = ?"
    )
    .bind(&portfolio_id)
    .fetch_one(pool.get_ref())
    .await
    .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    let updated_portfolio = Portfolio {
        id: portfolio.0,
        user_id: portfolio.1,
        name: portfolio.2,
        description: portfolio.3,
        created_at: portfolio.4.parse().unwrap_or_else(|_| chrono::Utc::now()),
        updated_at: portfolio.5.parse().unwrap_or_else(|_| chrono::Utc::now()),
    };

    Ok(HttpResponse::Ok().json(updated_portfolio))
}

#[delete("/portfolios/{id}")]
pub async fn delete_portfolio(
    req: HttpRequest,
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    path: web::Path<String>,
) -> Result<HttpResponse> {
    let user_id = require_auth(&req, secret.get_ref())?;
    let portfolio_id = path.into_inner();

    let result = sqlx::query("DELETE FROM portfolios WHERE id = ? AND user_id = ?")
        .bind(&portfolio_id)
        .bind(&user_id)
        .execute(pool.get_ref())
        .await
        .map_err(|e| actix_web::error::ErrorInternalServerError(e))?;

    if result.rows_affected() == 0 {
        return Ok(HttpResponse::NotFound().json(ErrorResponse {
            error: "Portfolio not found".to_string(),
        }));
    }

    Ok(HttpResponse::NoContent().finish())
}