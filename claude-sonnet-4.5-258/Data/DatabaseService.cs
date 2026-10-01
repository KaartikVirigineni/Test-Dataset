using Microsoft.Data.Sqlite;

namespace ExperimentHub.Data;

public class DatabaseService
{
    private readonly string _connectionString;

    public DatabaseService(IConfiguration configuration)
    {
        _connectionString = configuration.GetConnectionString("DefaultConnection") 
            ?? "Data Source=experiments.db";
    }

    public void Initialize()
    {
        using var connection = new SqliteConnection(_connectionString);
        connection.Open();

        var command = connection.CreateCommand();
        command.CommandText = @"
            CREATE TABLE IF NOT EXISTS Users (
                Id INTEGER PRIMARY KEY AUTOINCREMENT,
                Username TEXT NOT NULL UNIQUE,
                Email TEXT NOT NULL UNIQUE,
                PasswordHash TEXT NOT NULL,
                CreatedAt TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS Experiments (
                Id INTEGER PRIMARY KEY AUTOINCREMENT,
                UserId INTEGER NOT NULL,
                Name TEXT NOT NULL,
                Description TEXT,
                Hypothesis TEXT,
                Status TEXT NOT NULL DEFAULT 'draft',
                CreatedAt TEXT NOT NULL,
                StartedAt TEXT,
                CompletedAt TEXT,
                FOREIGN KEY (UserId) REFERENCES Users(Id)
            );

            CREATE TABLE IF NOT EXISTS Variants (
                Id INTEGER PRIMARY KEY AUTOINCREMENT,
                ExperimentId INTEGER NOT NULL,
                Name TEXT NOT NULL,
                Description TEXT,
                TrafficPercentage INTEGER NOT NULL,
                CreatedAt TEXT NOT NULL,
                FOREIGN KEY (ExperimentId) REFERENCES Experiments(Id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS Results (
                Id INTEGER PRIMARY KEY AUTOINCREMENT,
                ExperimentId INTEGER NOT NULL,
                VariantId INTEGER NOT NULL,
                Converted INTEGER NOT NULL,
                RecordedAt TEXT NOT NULL,
                FOREIGN KEY (ExperimentId) REFERENCES Experiments(Id) ON DELETE CASCADE,
                FOREIGN KEY (VariantId) REFERENCES Variants(Id) ON DELETE CASCADE
            );
        ";
        command.ExecuteNonQuery();
    }

    public SqliteConnection GetConnection()
    {
        return new SqliteConnection(_connectionString);
    }
}