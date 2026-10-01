use crate::auth::Claims;
use crate::middleware::AuthMiddleware;
use crate::models::{
    Document, CreateDocumentRequest, UpdateDocumentRequest, ErrorResponse, MessageResponse,
};
use actix_web::{web, HttpRequest, HttpResponse, Responder};
use sqlx::SqlitePool;
use uuid::Uuid;
use chrono::Utc;
use validator::Validate;

pub fn config(cfg: &mut web::ServiceConfig) {
    cfg.service(
        web::scope("/documents")
            .route("", web::get().to(list_documents))
            .route("", web::post().to(create_document).wrap(AuthMiddleware {
                required_roles: vec![],
            }))
            .route("/{id}", web::get().to(get_document))
            .route("/{id}", web::put().to(update_document).wrap(AuthMiddleware {
                required_roles: vec![],
            }))
            .route("/{id}", web::delete().to(delete_document).wrap(AuthMiddleware {
                required_roles: vec![],
            }))
    );
}

async fn list_documents(
    pool: web::Data<SqlitePool>,
    req: HttpRequest,
    secret: web::Data<String>,
) -> impl Responder {
    let auth_header = req.headers().get("Authorization");
    let user_id = if let Some(header) = auth_header {
        if let Ok(auth_str) = header.to_str() {
            if auth_str.starts_with("Bearer ") {
                let token = &auth_str[7..];
                if let Ok(claims) = crate::auth::verify_jwt(token, secret.get_ref()) {
                    Some(claims.sub)
                } else {
                    None
                }
            } else {
                None
            }
        } else {
            None
        }
    } else {
        None
    };

    let documents = if let Some(uid) = user_id {
        sqlx::query_as::<_, Document>(
            r#"
            SELECT id, title, content, author_id, category, is_public, created_at, updated_at
            FROM documents
            WHERE is_public = 1 OR author_id = ?
            ORDER BY created_at DESC
            "#,
        )
        .bind(&uid)
        .fetch_all(pool.get_ref())
        .await
    } else {
        sqlx::query_as::<_, Document>(
            r#"
            SELECT id, title, content, author_id, category, is_public, created_at, updated_at
            FROM documents
            WHERE is_public = 1
            ORDER BY created_at DESC
            "#,
        )
        .fetch_all(pool.get_ref())
        .await
    };

    match documents {
        Ok(docs) => HttpResponse::Ok().json(docs),
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to fetch documents".to_string(),
        }),
    }
}

async fn get_document(
    pool: web::Data<SqlitePool>,
    req: HttpRequest,
    secret: web::Data<String>,
    path: web::Path<String>,
) -> impl Responder {
    let doc_id = path.into_inner();

    let document = sqlx::query_as::<_, Document>(
        r#"
        SELECT id, title, content, author_id, category, is_public, created_at, updated_at
        FROM documents
        WHERE id = ?
        "#,
    )
    .bind(&doc_id)
    .fetch_one(pool.get_ref())
    .await;

    match document {
        Ok(doc) => {
            if doc.is_public {
                return HttpResponse::Ok().json(doc);
            }

            let auth_header = req.headers().get("Authorization");
            if let Some(header) = auth_header {
                if let Ok(auth_str) = header.to_str() {
                    if auth_str.starts_with("Bearer ") {
                        let token = &auth_str[7..];
                        if let Ok(claims) = crate::auth::verify_jwt(token, secret.get_ref()) {
                            if claims.sub == doc.author_id || claims.role == "admin" {
                                return HttpResponse::Ok().json(doc);
                            }
                        }
                    }
                }
            }

            HttpResponse::Forbidden().json(ErrorResponse {
                error: "Access denied".to_string(),
            })
        }
        Err(_) => HttpResponse::NotFound().json(ErrorResponse {
            error: "Document not found".to_string(),
        }),
    }
}

async fn create_document(
    pool: web::Data<SqlitePool>,
    req: HttpRequest,
    body: web::Json<CreateDocumentRequest>,
) -> impl Responder {
    if let Err(errors) = body.validate() {
        return HttpResponse::BadRequest().json(ErrorResponse {
            error: format!("Validation error: {:?}", errors),
        });
    }

    let claims = req.extensions().get::<Claims>().cloned();
    if claims.is_none() {
        return HttpResponse::Unauthorized().json(ErrorResponse {
            error: "Unauthorized".to_string(),
        });
    }

    let claims = claims.unwrap();
    let doc_id = Uuid::new_v4().to_string();
    let now = Utc::now();
    let is_public = body.is_public.unwrap_or(false);

    let result = sqlx::query(
        r#"
        INSERT INTO documents (id, title, content, author_id, category, is_public, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        "#,
    )
    .bind(&doc_id)
    .bind(&body.title)
    .bind(&body.content)
    .bind(&claims.sub)
    .bind(&body.category)
    .bind(is_public as i32)
    .bind(now.to_rfc3339())
    .bind(now.to_rfc3339())
    .execute(pool.get_ref())
    .await;

    match result {
        Ok(_) => {
            let document = Document {
                id: doc_id,
                title: body.title.clone(),
                content: body.content.clone(),
                author_id: claims.sub,
                category: body.category.clone(),
                is_public,
                created_at: now,
                updated_at: now,
            };
            HttpResponse::Created().json(document)
        }
        Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
            error: "Failed to create document".to_string(),
        }),
    }
}

async fn update_document(
    pool: web::Data<SqlitePool>,
    req: HttpRequest,
    path: web::Path<String>,
    body: web::Json<UpdateDocumentRequest>,
) -> impl Responder {
    if let Err(errors) = body.validate() {
        return HttpResponse::BadRequest().json(ErrorResponse {
            error: format!("Validation error: {:?}", errors),
        });
    }

    let claims = req.extensions().get::<Claims>().cloned();
    if claims.is_none() {
        return HttpResponse::Unauthorized().json(ErrorResponse {
            error: "Unauthorized".to_string(),
        });
    }

    let claims = claims.unwrap();
    let doc_id = path.into_inner();

    let document = sqlx::query_as::<_, Document>(
        r#"
        SELECT id, title, content, author_id, category, is_public, created_at, updated_at
        FROM documents
        WHERE id = ?
        "#,
    )
    .bind(&doc_id)
    .fetch_one(pool.get_ref())
    .await;

    match document {
        Ok(doc) => {
            if doc.author_id != claims.sub && claims.role != "admin" {
                return HttpResponse::Forbidden().json(ErrorResponse {
                    error: "Access denied".to_string(),
                });
            }

            let title = body.title.as_ref().unwrap_or(&doc.title);
            let content = body.content.as_ref().unwrap_or(&doc.content);
            let category = body.category.as_ref().unwrap_or(&doc.category);
            let is_public = body.is_public.unwrap_or(doc.is_public);
            let now = Utc::now();

            let result = sqlx::query(
                r#"
                UPDATE documents
                SET title = ?, content = ?, category = ?, is_public = ?, updated_at = ?
                WHERE id = ?
                "#,
            )
            .bind(title)
            .bind(content)
            .bind(category)
            .bind(is_public as i32)
            .bind(now.to_rfc3339())
            .bind(&doc_id)
            .execute(pool.get_ref())
            .await;

            match result {
                Ok(_) => {
                    let updated_doc = Document {
                        id: doc_id,
                        title: title.clone(),
                        content: content.clone(),
                        author_id: doc.author_id,
                        category: category.clone(),
                        is_public,
                        created_at: doc.created_at,
                        updated_at: now,
                    };
                    HttpResponse::Ok().json(updated_doc)
                }
                Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
                    error: "Failed to update document".to_string(),
                }),
            }
        }
        Err(_) => HttpResponse::NotFound().json(ErrorResponse {
            error: "Document not found".to_string(),
        }),
    }
}

async fn delete_document(
    pool: web::Data<SqlitePool>,
    req: HttpRequest,
    path: web::Path<String>,
) -> impl Responder {
    let claims = req.extensions().get::<Claims>().cloned();
    if claims.is_none() {
        return HttpResponse::Unauthorized().json(ErrorResponse {
            error: "Unauthorized".to_string(),
        });
    }

    let claims = claims.unwrap();
    let doc_id = path.into_inner();

    let document = sqlx::query_as::<_, Document>(
        r#"
        SELECT id, title, content, author_id, category, is_public, created_at, updated_at
        FROM documents
        WHERE id = ?
        "#,
    )
    .bind(&doc_id)
    .fetch_one(pool.get_ref())
    .await;

    match document {
        Ok(doc) => {
            if doc.author_id != claims.sub && claims.role != "admin" {
                return HttpResponse::Forbidden().json(ErrorResponse {
                    error: "Access denied".to_string(),
                });
            }

            let result = sqlx::query("DELETE FROM documents WHERE id = ?")
                .bind(&doc_id)
                .execute(pool.get_ref())
                .await;

            match result {
                Ok(_) => HttpResponse::Ok().json(MessageResponse {
                    message: "Document deleted successfully".to_string(),
                }),
                Err(_) => HttpResponse::InternalServerError().json(ErrorResponse {
                    error: "Failed to delete document".to_string(),
                }),
            }
        }
        Err(_) => HttpResponse::NotFound().json(ErrorResponse {
            error: "Document not found".to_string(),
        }),
    }
}