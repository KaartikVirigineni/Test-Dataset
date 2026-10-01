use serde::{Deserialize, Serialize};
use chrono::{DateTime, Utc};
use uuid::Uuid;
use validator::Validate;

#[derive(Debug, Serialize, Deserialize, sqlx::FromRow)]
pub struct User {
    pub id: String,
    pub username: String,
    #[serde(skip_serializing)]
    pub password_hash: String,
    pub created_at: DateTime<Utc>,
}

#[derive(Debug, Serialize, Deserialize, sqlx::FromRow)]
pub struct Campaign {
    pub id: String,
    pub user_id: String,
    pub name: String,
    pub message: String,
    pub campaign_type: String,
    pub status: String,
    pub scheduled_at: Option<DateTime<Utc>>,
    pub sent_at: Option<DateTime<Utc>>,
    pub recipients_count: i32,
    pub created_at: DateTime<Utc>,
}

#[derive(Debug, Deserialize, Validate)]
pub struct RegisterRequest {
    #[validate(length(min = 3))]
    pub username: String,
    #[validate(length(min = 6))]
    pub password: String,
}

#[derive(Debug, Deserialize)]
pub struct LoginRequest {
    pub username: String,
    pub password: String,
}

#[derive(Debug, Serialize)]
pub struct LoginResponse {
    pub token: String,
    pub user: User,
}

#[derive(Debug, Deserialize, Validate)]
pub struct CreateCampaignRequest {
    #[validate(length(min = 1))]
    pub name: String,
    #[validate(length(min = 1))]
    pub message: String,
    #[validate(custom = "validate_campaign_type")]
    pub campaign_type: String,
    pub scheduled_at: Option<DateTime<Utc>>,
    #[validate(range(min = 1))]
    pub recipients_count: i32,
}

#[derive(Debug, Deserialize)]
pub struct UpdateCampaignRequest {
    pub name: Option<String>,
    pub message: Option<String>,
    pub status: Option<String>,
    pub scheduled_at: Option<DateTime<Utc>>,
}

fn validate_campaign_type(campaign_type: &str) -> Result<(), validator::ValidationError> {
    if campaign_type == "sms" || campaign_type == "push" {
        Ok(())
    } else {
        Err(validator::ValidationError::new("invalid_campaign_type"))
    }
}

#[derive(Debug, Serialize, Deserialize)]
pub struct Claims {
    pub sub: String,
    pub exp: usize,
}

impl User {
    pub fn new(username: String, password_hash: String) -> Self {
        Self {
            id: Uuid::new_v4().to_string(),
            username,
            password_hash,
            created_at: Utc::now(),
        }
    }
}

impl Campaign {
    pub fn new(
        user_id: String,
        name: String,
        message: String,
        campaign_type: String,
        scheduled_at: Option<DateTime<Utc>>,
        recipients_count: i32,
    ) -> Self {
        Self {
            id: Uuid::new_v4().to_string(),
            user_id,
            name,
            message,
            campaign_type,
            status: "draft".to_string(),
            scheduled_at,
            sent_at: None,
            recipients_count,
            created_at: Utc::now(),
        }
    }
}