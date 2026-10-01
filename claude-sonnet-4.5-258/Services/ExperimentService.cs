using ExperimentHub.Data;
using ExperimentHub.Models;
using Microsoft.Data.Sqlite;

namespace ExperimentHub.Services;

public class ExperimentService
{
    private readonly DatabaseService _dbService;

    public ExperimentService(DatabaseService dbService)
    {
        _dbService = dbService;
    }

    public List<Experiment> GetUserExperiments(int userId)
    {
        using var connection = _dbService.GetConnection();
        connection.Open();

        var command = connection.CreateCommand();
        command.CommandText = @"
            SELECT Id, UserId, Name, Description, Hypothesis, Status, CreatedAt, StartedAt, CompletedAt
            FROM Experiments
            WHERE UserId = $userId
            ORDER BY CreatedAt DESC
        ";
        command.Parameters.AddWithValue("$userId", userId);

        var experiments = new List<Experiment>();
        using var reader = command.ExecuteReader();
        while (reader.Read())
        {
            experiments.Add(ReadExperiment(reader));
        }

        return experiments;
    }

    public Experiment? GetExperiment(int id, int userId)
    {
        using var connection = _dbService.GetConnection();
        connection.Open();

        var command = connection.CreateCommand();
        command.CommandText = @"
            SELECT Id, UserId, Name, Description, Hypothesis, Status, CreatedAt, StartedAt, CompletedAt
            FROM Experiments
            WHERE Id = $id AND UserId = $userId
        ";
        command.Parameters.AddWithValue("$id", id);
        command.Parameters.AddWithValue("$userId", userId);

        using var reader = command.ExecuteReader();
        if (!reader.Read())
            return null;

        var experiment = ReadExperiment(reader);
        reader.Close();

        // Load variants
        var variantsCommand = connection.CreateCommand();
        variantsCommand.CommandText = @"
            SELECT Id, ExperimentId, Name, Description, TrafficPercentage, CreatedAt
            FROM Variants
            WHERE ExperimentId = $experimentId
        ";
        variantsCommand.Parameters.AddWithValue("$experimentId", id);

        using var variantsReader = variantsCommand.ExecuteReader();
        while (variantsReader.Read())
        {
            experiment.Variants.Add(ReadVariant(variantsReader));
        }

        return experiment;
    }

    public Experiment CreateExperiment(int userId, string name, string description, string hypothesis)
    {
        using var connection = _dbService.GetConnection();
        connection.Open();

        var command = connection.CreateCommand();
        command.CommandText = @"
            INSERT INTO Experiments (UserId, Name, Description, Hypothesis, Status, CreatedAt)
            VALUES ($userId, $name, $description, $hypothesis, 'draft', $createdAt);
            SELECT last_insert_rowid();
        ";
        command.Parameters.AddWithValue("$userId", userId);
        command.Parameters.AddWithValue("$name", name);
        command.Parameters.AddWithValue("$description", description);
        command.Parameters.AddWithValue("$hypothesis", hypothesis);
        command.Parameters.AddWithValue("$createdAt", DateTime.UtcNow.ToString("o"));

        var id = (long)command.ExecuteScalar()!;

        return new Experiment
        {
            Id = (int)id,
            UserId = userId,
            Name = name,
            Description = description,
            Hypothesis = hypothesis,
            Status = "draft",
            CreatedAt = DateTime.UtcNow
        };
    }

    public Experiment UpdateExperiment(int id, int userId, string name, string description, string status)
    {
        using var connection = _dbService.GetConnection();
        connection.Open();

        var command = connection.CreateCommand();
        command.CommandText = @"
            UPDATE Experiments
            SET Name = $name, Description = $description, Status = $status
            WHERE Id = $id AND UserId = $userId
        ";
        command.Parameters.AddWithValue("$id", id);
        command.Parameters.AddWithValue("$userId", userId);
        command.Parameters.AddWithValue("$name", name);
        command.Parameters.AddWithValue("$description", description);
        command.Parameters.AddWithValue("$status", status);

        var rowsAffected = command.ExecuteNonQuery();
        if (rowsAffected == 0)
            throw new KeyNotFoundException("Experiment not found");

        return GetExperiment(id, userId)!;
    }

    public void DeleteExperiment(int id, int userId)
    {
        using var connection = _dbService.GetConnection();
        connection.Open();

        var command = connection.CreateCommand();
        command.CommandText = "DELETE FROM Experiments WHERE Id = $id AND UserId = $userId";
        command.Parameters.AddWithValue("$id", id);
        command.Parameters.AddWithValue("$userId", userId);

        var rowsAffected = command.ExecuteNonQuery();
        if (rowsAffected == 0)
            throw new KeyNotFoundException("Experiment not found");
    }

    public Variant AddVariant(int experimentId, int userId, string name, string description, int trafficPercentage)
    {
        using var connection = _dbService.GetConnection();
        connection.Open();

        // Verify experiment ownership
        var checkCommand = connection.CreateCommand();
        checkCommand.CommandText = "SELECT COUNT(*) FROM Experiments WHERE Id = $id AND UserId = $userId";
        checkCommand.Parameters.AddWithValue("$id", experimentId);
        checkCommand.Parameters.AddWithValue("$userId", userId);
        
        var count = (long)checkCommand.ExecuteScalar()!;
        if (count == 0)
            throw new KeyNotFoundException("Experiment not found");

        var command = connection.CreateCommand();
        command.CommandText = @"
            INSERT INTO Variants (ExperimentId, Name, Description, TrafficPercentage, CreatedAt)
            VALUES ($experimentId, $name, $description, $trafficPercentage, $createdAt);
            SELECT last_insert_rowid();
        ";
        command.Parameters.AddWithValue("$experimentId", experimentId);
        command.Parameters.AddWithValue("$name", name);
        command.Parameters.AddWithValue("$description", description);
        command.Parameters.AddWithValue("$trafficPercentage", trafficPercentage);
        command.Parameters.AddWithValue("$createdAt", DateTime.UtcNow.ToString("o"));

        var id = (long)command.ExecuteScalar()!;

        return new Variant
        {
            Id = (int)id,
            ExperimentId = experimentId,
            Name = name,
            Description = description,
            TrafficPercentage = trafficPercentage,
            CreatedAt = DateTime.UtcNow
        };
    }

    public Result RecordResult(int experimentId, int userId, int variantId, bool converted)
    {
        using var connection = _dbService.GetConnection();
        connection.Open();

        // Verify experiment ownership
        var checkCommand = connection.CreateCommand();
        checkCommand.CommandText = "SELECT COUNT(*) FROM Experiments WHERE Id = $id AND UserId = $userId";
        checkCommand.Parameters.AddWithValue("$id", experimentId);
        checkCommand.Parameters.AddWithValue("$userId", userId);
        
        var count = (long)checkCommand.ExecuteScalar()!;
        if (count == 0)
            throw new KeyNotFoundException("Experiment not found");

        // Verify variant belongs to experiment
        var variantCommand = connection.CreateCommand();
        variantCommand.CommandText = "SELECT COUNT(*) FROM Variants WHERE Id = $id AND ExperimentId = $experimentId";
        variantCommand.Parameters.AddWithValue("$id", variantId);
        variantCommand.Parameters.AddWithValue("$experimentId", experimentId);
        
        var variantCount = (long)variantCommand.ExecuteScalar()!;
        if (variantCount == 0)
            throw new KeyNotFoundException("Variant not found");

        var command = connection.CreateCommand();
        command.CommandText = @"
            INSERT INTO Results (ExperimentId, VariantId, Converted, RecordedAt)
            VALUES ($experimentId, $variantId, $converted, $recordedAt);
            SELECT last_insert_rowid();
        ";
        command.Parameters.AddWithValue("$experimentId", experimentId);
        command.Parameters.AddWithValue("$variantId", variantId);
        command.Parameters.AddWithValue("$converted", converted ? 1 : 0);
        command.Parameters.AddWithValue("$recordedAt", DateTime.UtcNow.ToString("o"));

        var id = (long)command.ExecuteScalar()!;

        return new Result
        {
            Id = (int)id,
            ExperimentId = experimentId,
            VariantId = variantId,
            Converted = converted,
            RecordedAt = DateTime.UtcNow
        };
    }

    public ExperimentStats GetExperimentStats(int experimentId, int userId)
    {
        using var connection = _dbService.GetConnection();
        connection.Open();

        // Verify experiment ownership and get name
        var checkCommand = connection.CreateCommand();
        checkCommand.CommandText = "SELECT Name FROM Experiments WHERE Id = $id AND UserId = $userId";
        checkCommand.Parameters.AddWithValue("$id", experimentId);
        checkCommand.Parameters.AddWithValue("$userId", userId);
        
        var experimentName = checkCommand.ExecuteScalar() as string;
        if (experimentName == null)
            throw new KeyNotFoundException("Experiment not found");

        var stats = new ExperimentStats
        {
            ExperimentId = experimentId,
            ExperimentName = experimentName
        };

        var command = connection.CreateCommand();
        command.CommandText = @"
            SELECT 
                v.Id,
                v.Name,
                COUNT(r.Id) as TotalViews,
                SUM(CASE WHEN r.Converted = 1 THEN 1 ELSE 0 END) as TotalConversions
            FROM Variants v
            LEFT JOIN Results r ON v.Id = r.VariantId
            WHERE v.ExperimentId = $experimentId
            GROUP BY v.Id, v.Name
        ";
        command.Parameters.AddWithValue("$experimentId", experimentId);

        using var reader = command.ExecuteReader();
        while (reader.Read())
        {
            var totalViews = reader.GetInt32(2);
            var totalConversions = reader.GetInt32(3);
            var conversionRate = totalViews > 0 ? (double)totalConversions / totalViews : 0.0;

            stats.VariantStats.Add(new VariantStats
            {
                VariantId = reader.GetInt32(0),
                VariantName = reader.GetString(1),
                TotalViews = totalViews,
                TotalConversions = totalConversions,
                ConversionRate = conversionRate
            });
        }

        return stats;
    }

    private Experiment ReadExperiment(SqliteDataReader reader)
    {
        return new Experiment
        {
            Id = reader.GetInt32(0),
            UserId = reader.GetInt32(1),
            Name = reader.GetString(2),
            Description = reader.IsDBNull(3) ? string.Empty : reader.GetString(3),
            Hypothesis = reader.IsDBNull(4) ? string.Empty : reader.GetString(4),
            Status = reader.GetString(5),
            CreatedAt = DateTime.Parse(reader.GetString(6)),
            StartedAt = reader.IsDBNull(7) ? null : DateTime.Parse(reader.GetString(7)),
            CompletedAt = reader.IsDBNull(8) ? null : DateTime.Parse(reader.GetString(8))
        };
    }

    private Variant ReadVariant(SqliteDataReader reader)
    {
        return new Variant
        {
            Id = reader.GetInt32(0),
            ExperimentId = reader.GetInt32(1),
            Name = reader.GetString(2),
            Description = reader.IsDBNull(3) ? string.Empty : reader.GetString(3),
            TrafficPercentage = reader.GetInt32(4),
            CreatedAt = DateTime.Parse(reader.GetString(5))
        };
    }
}