use crate::auth::{create_jwt, hash_password, verify_password, verify_jwt};
use crate::models::*;
use actix_web::{delete, get, post, put, web, HttpRequest, HttpResponse, Responder};
use chrono::Utc;
use sqlx::SqlitePool;
use uuid::Uuid;
use std::str::FromStr;

#[get("/health")]
pub async fn health_check() -> impl Responder {
    HttpResponse::Ok().json(serde_json::json!({
        "status": "healthy"
    }))
}

#[get("/swagger")]
pub async fn get_swagger() -> impl Responder {
    match tokio::fs::read_to_string("./openapi.yaml").await {
        Ok(content) => HttpResponse::Ok()
            .content_type("application/yaml")
            .body(content),
        Err(_) => HttpResponse::NotFound().json(ErrorResponse {
            error: "Swagger file not found".to_string(),
        }),
    }
}

#[post("/register")]
pub async fn register(
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    req: web::Json<RegisterRequest>,
) -> impl Responder {
    let user_id = Uuid::new_v4().to_string();
    let password_hash = match hash_password(&req.password) {
        Ok(hash) => hash,
        Err(_) => {
            return HttpResponse::InternalServerError().json(ErrorResponse {
                error: "Failed to hash password".to_string(),
            })
        }
    };

    let now = Utc::now();

    let result = sqlx::query(
        r#"
        INSERT INTO users (id, username, email, password_hash, role, created_at)
        VALUES (?, ?, ?, ?, ?, ?)
        "#,
    )
    .bind(&user_id)
    .bind(&req.username)
    .bind(&req.email)
    .bind(&password_hash)
    .bind("user")
    .bind(now.to_rfc3339())
    .execute(pool.get_ref())
    .await;

    match result {
        Ok(_) => {
            let token = match create_jwt(&user_id, secret.get_ref()) {
                Ok(t) => t,
                Err(_) => {
                    return HttpResponse::InternalServerError().json(ErrorResponse {
                        error: "Failed to create token".to_string(),
                    })
                }
            };

            let user = User {
                id: user_id,
                username: req.username.clone(),
                email: req.email.clone(),
                password_hash,
                role: UserRole::User,
                created_at: now,
            };

            HttpResponse::Created().json(TokenResponse { token, user })
        }
        Err(_) => HttpResponse::BadRequest().json(ErrorResponse {
            error: "Username or email already exists".to_string(),
        }),
    }
}

#[post("/login")]
pub async fn login(
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    req: web::Json<LoginRequest>,
) -> impl Responder {
    let result = sqlx::query_as::<_, (String, String, String, String, String, String)>(
        r#"
        SELECT id, username, email, password_hash, role, created_at
        FROM users
        WHERE username = ?
        "#,
    )
    .bind(&req.username)
    .fetch_optional(pool.get_ref())
    .await;

    match result {
        Ok(Some((id, username, email, password_hash, role_str, created_at))) => {
            match verify_password(&req.password, &password_hash) {
                Ok(true) => {
                    let token = match create_jwt(&id, secret.get_ref()) {
                        Ok(t) => t,
                        Err(_) => {
                            return HttpResponse::InternalServerError().json(ErrorResponse {
                                error: "Failed to create token".to_string(),
                            })
                        }
                    };

                    let role = UserRole::from_str(&role_str).unwrap_or(UserRole::User);
                    let created_at = chrono::DateTime::parse_from_rfc3339(&created_at)
                        .map(|dt| dt.with_timezone(&Utc))
                        .unwrap_or_else(|_| Utc::now());

                    let user = User {
                        id,
                        username,
                        email,
                        password_hash,
                        role,
                        created_at,
                    };

                    HttpResponse::Ok().json(TokenResponse { token, user })
                }
                _ => HttpResponse::Unauthorized().json(ErrorResponse {
                    error: "Invalid credentials".to_string(),
                }),
            }
        }
        _ => HttpResponse::Unauthorized().json(ErrorResponse {
            error: "Invalid credentials".to_string(),
        }),
    }
}

fn extract_token(req: &HttpRequest) -> Option<String> {
    req.headers()
        .get("Authorization")
        .and_then(|h| h.to_str().ok())
        .and_then(|h| {
            if h.starts_with("Bearer ") {
                Some(h[7..].to_string())
            } else {
                None
            }
        })
}

fn get_user_id_from_token(req: &HttpRequest, secret: &str) -> Result<String, HttpResponse> {
    let token = extract_token(req).ok_or_else(|| {
        HttpResponse::Unauthorized().json(ErrorResponse {
            error: "Missing authorization token".to_string(),
        })
    })?;

    let claims = verify_jwt(&token, secret).map_err(|_| {
        HttpResponse::Unauthorized().json(ErrorResponse {
            error: "Invalid or expired token".to_string(),
        })
    })?;

    Ok(claims.sub)
}

#[get("/me")]
pub async fn get_current_user(
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    req: HttpRequest,
) -> impl Responder {
    let user_id = match get_user_id_from_token(&req, secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    let result = sqlx::query_as::<_, (String, String, String, String, String, String)>(
        r#"
        SELECT id, username, email, password_hash, role, created_at
        FROM users
        WHERE id = ?
        "#,
    )
    .bind(&user_id)
    .fetch_optional(pool.get_ref())
    .await;

    match result {
        Ok(Some((id, username, email, password_hash, role_str, created_at))) => {
            let role = UserRole::from_str(&role_str).unwrap_or(UserRole::User);
            let created_at = chrono::DateTime::parse_from_rfc3339(&created_at)
                .map(|dt| dt.with_timezone(&Utc))
                .unwrap_or_else(|_| Utc::now());

            let user = User {
                id,
                username,
                email,
                password_hash,
                role,
                created_at,
            };

            HttpResponse::Ok().json(user)
        }
        _ => HttpResponse::NotFound().json(ErrorResponse {
            error: "User not found".to_string(),
        }),
    }
}

#[get("")]
pub async fn list_tickets(
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    req: HttpRequest,
    query: web::Query<TicketQuery>,
) -> impl Responder {
    let _user_id = match get_user_id_from_token(&req, secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    let mut sql = "SELECT id, title, description, status, priority, user_id, assigned_to, created_at, updated_at FROM tickets WHERE 1=1".to_string();
    let mut params: Vec<String> = Vec::new();

    if let Some(ref status) = query.status {
        sql.push_str(" AND status = ?");
        params.push(status.to_string());
    }

    if let Some(ref priority) = query.priority {
        sql.push_str(" AND priority = ?");
        params.push(priority.to_string());
    }

    sql.push_str(" ORDER BY created_at DESC");

    let mut query_builder = sqlx::query_as::<_, (String, String, String, String, String, String, Option<String>, String, String)>(&sql);
    
    for param in &params {
        query_builder = query_builder.bind(param);
    }

    let result = query_builder.fetch_all(pool.get_ref()).await;

    match result {
        Ok(rows) => {
            let tickets: Vec<Ticket> = rows
                .into_iter()
                .filter_map(|(id, title, description, status_str, priority_str, user_id, assigned_to, created_at, updated_at)| {
                    let status = TicketStatus::from_str(&status_str).ok()?;
                    let priority = TicketPriority::from_str(&priority_str).ok()?;
                    let created_at = chrono::DateTime::parse_from_rfc3339(&created_at)
                        .map(|dt| dt.with_timezone(&Utc))
                        .ok()?;
                    let updated_at = chrono::DateTime::parse_from_rfc3339(&updated_at)
                        .map(|dt| dt.with_timezone(&Utc))
                        .ok()?;

                    Some(Ticket {
                        id,
                        title,
                        description,
                        status,
                        priority,
                        user_id,
                        assigned_to,
                        created_at,
                        updated_at,
                    })
                })
                .collect();

            HttpResponse::Ok().json(tickets)
        }
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to fetch tickets".to_string(),
        }),
    }
}

#[post("")]
pub async fn create_ticket(
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    req: HttpRequest,
    body: web::Json<CreateTicketRequest>,
) -> impl Responder {
    let user_id = match get_user_id_from_token(&req, secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    let ticket_id = Uuid::new_v4().to_string();
    let now = Utc::now();

    let result = sqlx::query(
        r#"
        INSERT INTO tickets (id, title, description, status, priority, user_id, assigned_to, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        "#,
    )
    .bind(&ticket_id)
    .bind(&body.title)
    .bind(&body.description)
    .bind(TicketStatus::Open.to_string())
    .bind(body.priority.to_string())
    .bind(&user_id)
    .bind::<Option<String>>(None)
    .bind(now.to_rfc3339())
    .bind(now.to_rfc3339())
    .execute(pool.get_ref())
    .await;

    match result {
        Ok(_) => {
            let ticket = Ticket {
                id: ticket_id,
                title: body.title.clone(),
                description: body.description.clone(),
                status: TicketStatus::Open,
                priority: body.priority.clone(),
                user_id,
                assigned_to: None,
                created_at: now,
                updated_at: now,
            };

            HttpResponse::Created().json(ticket)
        }
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to create ticket".to_string(),
        }),
    }
}

#[get("/{id}")]
pub async fn get_ticket(
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    req: HttpRequest,
    path: web::Path<String>,
) -> impl Responder {
    let _user_id = match get_user_id_from_token(&req, secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    let ticket_id = path.into_inner();

    let result = sqlx::query_as::<_, (String, String, String, String, String, String, Option<String>, String, String)>(
        r#"
        SELECT id, title, description, status, priority, user_id, assigned_to, created_at, updated_at
        FROM tickets
        WHERE id = ?
        "#,
    )
    .bind(&ticket_id)
    .fetch_optional(pool.get_ref())
    .await;

    match result {
        Ok(Some((id, title, description, status_str, priority_str, user_id, assigned_to, created_at, updated_at))) => {
            let status = match TicketStatus::from_str(&status_str) {
                Ok(s) => s,
                Err(_) => return HttpResponse::InternalServerError().json(ErrorResponse {
                    error: "Invalid status".to_string(),
                }),
            };

            let priority = match TicketPriority::from_str(&priority_str) {
                Ok(p) => p,
                Err(_) => return HttpResponse::InternalServerError().json(ErrorResponse {
                    error: "Invalid priority".to_string(),
                }),
            };

            let created_at = chrono::DateTime::parse_from_rfc3339(&created_at)
                .map(|dt| dt.with_timezone(&Utc))
                .unwrap_or_else(|_| Utc::now());
            
            let updated_at = chrono::DateTime::parse_from_rfc3339(&updated_at)
                .map(|dt| dt.with_timezone(&Utc))
                .unwrap_or_else(|_| Utc::now());

            let ticket = Ticket {
                id,
                title,
                description,
                status,
                priority,
                user_id,
                assigned_to,
                created_at,
                updated_at,
            };

            HttpResponse::Ok().json(ticket)
        }
        Ok(None) => HttpResponse::NotFound().json(ErrorResponse {
            error: "Ticket not found".to_string(),
        }),
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to fetch ticket".to_string(),
        }),
    }
}

#[put("/{id}")]
pub async fn update_ticket(
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    req: HttpRequest,
    path: web::Path<String>,
    body: web::Json<UpdateTicketRequest>,
) -> impl Responder {
    let _user_id = match get_user_id_from_token(&req, secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    let ticket_id = path.into_inner();
    let now = Utc::now();

    let existing = sqlx::query_as::<_, (String, String, String, String, String, String, Option<String>, String, String)>(
        "SELECT id, title, description, status, priority, user_id, assigned_to, created_at, updated_at FROM tickets WHERE id = ?"
    )
    .bind(&ticket_id)
    .fetch_optional(pool.get_ref())
    .await;

    let (_, current_title, current_desc, current_status, current_priority, user_id, current_assigned, created_at, _) = match existing {
        Ok(Some(t)) => t,
        Ok(None) => {
            return HttpResponse::NotFound().json(ErrorResponse {
                error: "Ticket not found".to_string(),
            })
        }
        Err(_) => {
            return HttpResponse::InternalServerError().json(ErrorResponse {
                error: "Failed to fetch ticket".to_string(),
            })
        }
    };

    let title = body.title.as_ref().unwrap_or(&current_title);
    let description = body.description.as_ref().unwrap_or(&current_desc);
    let status = body.status.as_ref().map(|s| s.to_string()).unwrap_or(current_status);
    let priority = body.priority.as_ref().map(|p| p.to_string()).unwrap_or(current_priority);
    let assigned_to = body.assigned_to.as_ref().or(current_assigned.as_ref());

    let result = sqlx::query(
        r#"
        UPDATE tickets
        SET title = ?, description = ?, status = ?, priority = ?, assigned_to = ?, updated_at = ?
        WHERE id = ?
        "#,
    )
    .bind(title)
    .bind(description)
    .bind(&status)
    .bind(&priority)
    .bind(assigned_to)
    .bind(now.to_rfc3339())
    .bind(&ticket_id)
    .execute(pool.get_ref())
    .await;

    match result {
        Ok(_) => {
            let status_enum = TicketStatus::from_str(&status).unwrap_or(TicketStatus::Open);
            let priority_enum = TicketPriority::from_str(&priority).unwrap_or(TicketPriority::Medium);
            let created_at = chrono::DateTime::parse_from_rfc3339(&created_at)
                .map(|dt| dt.with_timezone(&Utc))
                .unwrap_or_else(|_| Utc::now());

            let ticket = Ticket {
                id: ticket_id,
                title: title.clone(),
                description: description.clone(),
                status: status_enum,
                priority: priority_enum,
                user_id,
                assigned_to: assigned_to.cloned(),
                created_at,
                updated_at: now,
            };

            HttpResponse::Ok().json(ticket)
        }
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to update ticket".to_string(),
        }),
    }
}

#[delete("/{id}")]
pub async fn delete_ticket(
    pool: web::Data<SqlitePool>,
    secret: web::Data<String>,
    req: HttpRequest,
    path: web::Path<String>,
) -> impl Responder {
    let _user_id = match get_user_id_from_token(&req, secret.get_ref()) {
        Ok(id) => id,
        Err(resp) => return resp,
    };

    let ticket_id = path.into_inner();

    let result = sqlx::query("DELETE FROM tickets WHERE id = ?")
        .bind(&ticket_id)
        .execute(pool.get_ref())
        .await;

    match result {
        Ok(res) => {
            if res.rows_affected() > 0 {
                HttpResponse::NoContent().finish()
            } else {
                HttpResponse::NotFound().json(ErrorResponse {
                    error: "Ticket not found".to_string(),
                })
            }
        }
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to delete ticket".to_string(),
        }),
    }
}