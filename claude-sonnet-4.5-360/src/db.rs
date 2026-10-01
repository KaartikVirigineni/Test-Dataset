use sqlx::{SqlitePool, Row};
use chrono::Utc;

use crate::models::*;

pub async fn run_migrations(pool: &SqlitePool) -> Result<(), sqlx::Error> {
    sqlx::query(
        r#"
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )
        "#,
    )
    .execute(pool)
    .await?;

    sqlx::query(
        r#"
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            price REAL NOT NULL,
            quantity INTEGER NOT NULL,
            sku TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )
        "#,
    )
    .execute(pool)
    .await?;

    sqlx::query(
        r#"
        CREATE TABLE IF NOT EXISTS sales (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            total_amount REAL NOT NULL,
            payment_method TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
        "#,
    )
    .execute(pool)
    .await?;

    sqlx::query(
        r#"
        CREATE TABLE IF NOT EXISTS sale_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sale_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL,
            unit_price REAL NOT NULL,
            subtotal REAL NOT NULL,
            FOREIGN KEY (sale_id) REFERENCES sales(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
        "#,
    )
    .execute(pool)
    .await?;

    Ok(())
}

pub async fn create_user(
    pool: &SqlitePool,
    username: &str,
    password_hash: &str,
) -> Result<User, sqlx::Error> {
    let result = sqlx::query(
        "INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, ?)"
    )
    .bind(username)
    .bind(password_hash)
    .bind(Utc::now().to_rfc3339())
    .execute(pool)
    .await?;

    let user = sqlx::query_as::<_, User>("SELECT * FROM users WHERE id = ?")
        .bind(result.last_insert_rowid())
        .fetch_one(pool)
        .await?;

    Ok(user)
}

pub async fn get_user_by_username(pool: &SqlitePool, username: &str) -> Result<User, sqlx::Error> {
    sqlx::query_as::<_, User>("SELECT * FROM users WHERE username = ?")
        .bind(username)
        .fetch_one(pool)
        .await
}

pub async fn list_products(pool: &SqlitePool) -> Result<Vec<Product>, sqlx::Error> {
    sqlx::query_as::<_, Product>("SELECT * FROM products ORDER BY created_at DESC")
        .fetch_all(pool)
        .await
}

pub async fn get_product(pool: &SqlitePool, id: i64) -> Result<Product, sqlx::Error> {
    sqlx::query_as::<_, Product>("SELECT * FROM products WHERE id = ?")
        .bind(id)
        .fetch_one(pool)
        .await
}

pub async fn create_product(
    pool: &SqlitePool,
    name: &str,
    description: Option<&str>,
    price: f64,
    quantity: i32,
    sku: &str,
) -> Result<Product, sqlx::Error> {
    let now = Utc::now().to_rfc3339();
    let result = sqlx::query(
        "INSERT INTO products (name, description, price, quantity, sku, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)"
    )
    .bind(name)
    .bind(description)
    .bind(price)
    .bind(quantity)
    .bind(sku)
    .bind(&now)
    .bind(&now)
    .execute(pool)
    .await?;

    let product = sqlx::query_as::<_, Product>("SELECT * FROM products WHERE id = ?")
        .bind(result.last_insert_rowid())
        .fetch_one(pool)
        .await?;

    Ok(product)
}

pub async fn update_product(
    pool: &SqlitePool,
    id: i64,
    name: Option<&str>,
    description: Option<&str>,
    price: Option<f64>,
    quantity: Option<i32>,
) -> Result<Product, sqlx::Error> {
    let mut product = get_product(pool, id).await?;

    if let Some(n) = name {
        product.name = n.to_string();
    }
    if let Some(d) = description {
        product.description = Some(d.to_string());
    }
    if let Some(p) = price {
        product.price = p;
    }
    if let Some(q) = quantity {
        product.quantity = q;
    }

    let now = Utc::now().to_rfc3339();

    sqlx::query(
        "UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, updated_at = ? WHERE id = ?"
    )
    .bind(&product.name)
    .bind(&product.description)
    .bind(product.price)
    .bind(product.quantity)
    .bind(&now)
    .bind(id)
    .execute(pool)
    .await?;

    get_product(pool, id).await
}

pub async fn delete_product(pool: &SqlitePool, id: i64) -> Result<(), sqlx::Error> {
    let result = sqlx::query("DELETE FROM products WHERE id = ?")
        .bind(id)
        .execute(pool)
        .await?;

    if result.rows_affected() == 0 {
        return Err(sqlx::Error::RowNotFound);
    }

    Ok(())
}

pub async fn list_sales(pool: &SqlitePool, user_id: i64) -> Result<Vec<Sale>, sqlx::Error> {
    sqlx::query_as::<_, Sale>("SELECT * FROM sales WHERE user_id = ? ORDER BY created_at DESC")
        .bind(user_id)
        .fetch_all(pool)
        .await
}

pub async fn get_sale(pool: &SqlitePool, id: i64, user_id: i64) -> Result<Sale, sqlx::Error> {
    sqlx::query_as::<_, Sale>("SELECT * FROM sales WHERE id = ? AND user_id = ?")
        .bind(id)
        .bind(user_id)
        .fetch_one(pool)
        .await
}

pub async fn get_sale_items(
    pool: &SqlitePool,
    sale_id: i64,
) -> Result<Vec<SaleItemWithProduct>, sqlx::Error> {
    let rows = sqlx::query(
        r#"
        SELECT si.id, si.product_id, p.name as product_name, si.quantity, si.unit_price, si.subtotal
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
        "#
    )
    .bind(sale_id)
    .fetch_all(pool)
    .await?;

    let items = rows
        .iter()
        .map(|row| SaleItemWithProduct {
            id: row.get("id"),
            product_id: row.get("product_id"),
            product_name: row.get("product_name"),
            quantity: row.get("quantity"),
            unit_price: row.get("unit_price"),
            subtotal: row.get("subtotal"),
        })
        .collect();

    Ok(items)
}

pub async fn create_sale(
    pool: &SqlitePool,
    user_id: i64,
    request: &CreateSaleRequest,
) -> Result<(Sale, Vec<SaleItemWithProduct>), sqlx::Error> {
    let mut tx = pool.begin().await?;

    let mut total_amount = 0.0;
    let mut sale_items_data = Vec::new();

    for item in &request.items {
        let product = sqlx::query_as::<_, Product>("SELECT * FROM products WHERE id = ?")
            .bind(item.product_id)
            .fetch_one(&mut *tx)
            .await?;

        if product.quantity < item.quantity {
            return Err(sqlx::Error::RowNotFound);
        }

        let subtotal = product.price * item.quantity as f64;
        total_amount += subtotal;

        sale_items_data.push((item.product_id, product.name.clone(), item.quantity, product.price, subtotal));

        sqlx::query("UPDATE products SET quantity = quantity - ? WHERE id = ?")
            .bind(item.quantity)
            .bind(item.product_id)
            .execute(&mut *tx)
            .await?;
    }

    let result = sqlx::query(
        "INSERT INTO sales (user_id, total_amount, payment_method, created_at) VALUES (?, ?, ?, ?)"
    )
    .bind(user_id)
    .bind(total_amount)
    .bind(&request.payment_method)
    .bind(Utc::now().to_rfc3339())
    .execute(&mut *tx)
    .await?;

    let sale_id = result.last_insert_rowid();

    let mut items = Vec::new();

    for (product_id, product_name, quantity, unit_price, subtotal) in sale_items_data {
        let result = sqlx::query(
            "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)"
        )
        .bind(sale_id)
        .bind(product_id)
        .bind(quantity)
        .bind(unit_price)
        .bind(subtotal)
        .execute(&mut *tx)
        .await?;

        items.push(SaleItemWithProduct {
            id: result.last_insert_rowid(),
            product_id,
            product_name,
            quantity,
            unit_price,
            subtotal,
        });
    }

    tx.commit().await?;

    let sale = sqlx::query_as::<_, Sale>("SELECT * FROM sales WHERE id = ?")
        .bind(sale_id)
        .fetch_one(pool)
        .await?;

    Ok((sale, items))
}