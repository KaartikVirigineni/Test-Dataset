use actix_web::{web, HttpResponse, Error, HttpRequest};
use sqlx::SqlitePool;
use validator::Validate;

use crate::models::{Campaign, CreateCampaignRequest, UpdateCampaignRequest};

pub async fn list_campaigns(
    pool: web::Data<SqlitePool>,
    req: HttpRequest,
) -> Result<HttpResponse, Error> {
    let user_id = req.extensions().get::<String>().unwrap().clone();

    let campaigns = sqlx::query_as::<_, Campaign>(
        "SELECT * FROM campaigns WHERE user_id = ? ORDER BY created_at DESC"
    )
    .bind(&user_id)
    .fetch_all(pool.get_ref())
    .await;

    match campaigns {
        Ok(c) => Ok(HttpResponse::Ok().json(c)),
        Err(e) => {
            log::error!("Failed to fetch campaigns: {}", e);
            Ok(HttpResponse::InternalServerError().json(serde_json::json!({
                "error": "Failed to fetch campaigns"
            })))
        }
    }
}

pub async fn create_campaign(
    pool: web::Data<SqlitePool>,
    req: HttpRequest,
    body: web::Json<CreateCampaignRequest>,
) -> Result<HttpResponse, Error> {
    if let Err(e) = body.validate() {
        return Ok(HttpResponse::BadRequest().json(serde_json::json!({
            "error": format!("Validation error: {}", e)
        })));
    }

    let user_id = req.extensions().get::<String>().unwrap().clone();

    let campaign = Campaign::new(
        user_id,
        body.name.clone(),
        body.message.clone(),
        body.campaign_type.clone(),
        body.scheduled_at,
        body.recipients_count,
    );

    let result = sqlx::query(
        "INSERT INTO campaigns (id, user_id, name, message, campaign_type, status, scheduled_at, sent_at, recipients_count, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )
    .bind(&campaign.id)
    .bind(&campaign.user_id)
    .bind(&campaign.name)
    .bind(&campaign.message)
    .bind(&campaign.campaign_type)
    .bind(&campaign.status)
    .bind(&campaign.scheduled_at)
    .bind(&campaign.sent_at)
    .bind(&campaign.recipients_count)
    .bind(&campaign.created_at)
    .execute(pool.get_ref())
    .await;

    match result {
        Ok(_) => Ok(HttpResponse::Created().json(campaign)),
        Err(e) => {
            log::error!("Failed to create campaign: {}", e);
            Ok(HttpResponse::InternalServerError().json(serde_json::json!({
                "error": "Failed to create campaign"
            })))
        }
    }
}

pub async fn get_campaign(
    pool: web::Data<SqlitePool>,
    req: HttpRequest,
    path: web::Path<String>,
) -> Result<HttpResponse, Error> {
    let user_id = req.extensions().get::<String>().unwrap().clone();
    let campaign_id = path.into_inner();

    let campaign = sqlx::query_as::<_, Campaign>(
        "SELECT * FROM campaigns WHERE id = ? AND user_id = ?"
    )
    .bind(&campaign_id)
    .bind(&user_id)
    .fetch_optional(pool.get_ref())
    .await;

    match campaign {
        Ok(Some(c)) => Ok(HttpResponse::Ok().json(c)),
        Ok(None) => Ok(HttpResponse::NotFound().json(serde_json::json!({
            "error": "Campaign not found"
        }))),
        Err(e) => {
            log::error!("Failed to fetch campaign: {}", e);
            Ok(HttpResponse::InternalServerError().json(serde_json::json!({
                "error": "Failed to fetch campaign"
            })))
        }
    }
}

pub async fn update_campaign(
    pool: web::Data<SqlitePool>,
    req: HttpRequest,
    path: web::Path<String>,
    body: web::Json<UpdateCampaignRequest>,
) -> Result<HttpResponse, Error> {
    let user_id = req.extensions().get::<String>().unwrap().clone();
    let campaign_id = path.into_inner();

    let existing = sqlx::query_as::<_, Campaign>(
        "SELECT * FROM campaigns WHERE id = ? AND user_id = ?"
    )
    .bind(&campaign_id)
    .bind(&user_id)
    .fetch_optional(pool.get_ref())
    .await;

    let mut campaign = match existing {
        Ok(Some(c)) => c,
        Ok(None) => return Ok(HttpResponse::NotFound().json(serde_json::json!({
            "error": "Campaign not found"
        }))),
        Err(e) => {
            log::error!("Failed to fetch campaign: {}", e);
            return Ok(HttpResponse::InternalServerError().json(serde_json::json!({
                "error": "Failed to fetch campaign"
            })));
        }
    };

    if let Some(name) = &body.name {
        campaign.name = name.clone();
    }
    if let Some(message) = &body.message {
        campaign.message = message.clone();
    }
    if let Some(status) = &body.status {
        campaign.status = status.clone();
        if status == "sent" && campaign.sent_at.is_none() {
            campaign.sent_at = Some(chrono::Utc::now());
        }
    }
    if body.scheduled_at.is_some() {
        campaign.scheduled_at = body.scheduled_at;
    }

    let result = sqlx::query(
        "UPDATE campaigns SET name = ?, message = ?, status = ?, scheduled_at = ?, sent_at = ? WHERE id = ?"
    )
    .bind(&campaign.name)
    .bind(&campaign.message)
    .bind(&campaign.status)
    .bind(&campaign.scheduled_at)
    .bind(&campaign.sent_at)
    .bind(&campaign_id)
    .execute(pool.get_ref())
    .await;

    match result {
        Ok(_) => Ok(HttpResponse::Ok().json(campaign)),
        Err(e) => {
            log::error!("Failed to update campaign: {}", e);
            Ok(HttpResponse::InternalServerError().json(serde_json::json!({
                "error": "Failed to update campaign"
            })))
        }
    }
}

pub async fn delete_campaign(
    pool: web::Data<SqlitePool>,
    req: HttpRequest,
    path: web::Path<String>,
) -> Result<HttpResponse, Error> {
    let user_id = req.extensions().get::<String>().unwrap().clone();
    let campaign_id = path.into_inner();

    let result = sqlx::query(
        "DELETE FROM campaigns WHERE id = ? AND user_id = ?"
    )
    .bind(&campaign_id)
    .bind(&user_id)
    .execute(pool.get_ref())
    .await;

    match result {
        Ok(r) if r.rows_affected() > 0 => Ok(HttpResponse::NoContent().finish()),
        Ok(_) => Ok(HttpResponse::NotFound().json(serde_json::json!({
            "error": "Campaign not found"
        }))),
        Err(e) => {
            log::error!("Failed to delete campaign: {}", e);
            Ok(HttpResponse::InternalServerError().json(serde_json::json!({
                "error": "Failed to delete campaign"
            })))
        }
    }
}