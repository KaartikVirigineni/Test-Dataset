require 'sqlite3'

class Database
  DB_FILE = ENV['DATABASE_PATH'] || 'rentatrack.db'

  def self.setup
    db = SQLite3::Database.new(DB_FILE)
    db.results_as_hash = true
    
    db.execute <<-SQL
      CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        name TEXT NOT NULL,
        role TEXT DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      );
    SQL

    db.execute <<-SQL
      CREATE TABLE IF NOT EXISTS equipment (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        type TEXT NOT NULL,
        description TEXT,
        daily_rate REAL NOT NULL,
        status TEXT DEFAULT 'available',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      );
    SQL

    db.execute <<-SQL
      CREATE TABLE IF NOT EXISTS rentals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        equipment_id INTEGER NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        total_cost REAL NOT NULL,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (equipment_id) REFERENCES equipment(id)
      );
    SQL

    seed_data(db)
    
    db.close
  end

  def self.seed_data(db)
    count = db.execute("SELECT COUNT(*) as count FROM equipment")[0]['count']
    return if count > 0

    equipment_data = [
      ['Toyota Camry 2023', 'car', 'Comfortable sedan, automatic transmission', 75.00, 'available'],
      ['BMW X5 2023', 'car', 'Luxury SUV with premium features', 150.00, 'available'],
      ['Honda Civic 2022', 'car', 'Compact and fuel-efficient', 55.00, 'available'],
      ['Forklift CAT 3000lb', 'equipment', 'Industrial forklift for warehouse use', 120.00, 'available'],
      ['Excavator Mini', 'equipment', 'Compact excavator for small jobs', 200.00, 'available'],
      ['Pressure Washer 3000PSI', 'equipment', 'Heavy-duty cleaning equipment', 45.00, 'available']
    ]

    equipment_data.each do |item|
      db.execute(
        "INSERT INTO equipment (name, type, description, daily_rate, status) VALUES (?, ?, ?, ?, ?)",
        item
      )
    end
  end

  def self.connection
    db = SQLite3::Database.new(DB_FILE)
    db.results_as_hash = true
    db
  end
end