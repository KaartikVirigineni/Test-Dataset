namespace ExperimentHub.Models;

public class Experiment
{
    public int Id { get; set; }
    public int UserId { get; set; }
    public string Name { get; set; } = string.Empty;
    public string Description { get; set; } = string.Empty;
    public string Hypothesis { get; set; } = string.Empty;
    public string Status { get; set; } = "draft"; // draft, running, completed, paused
    public DateTime CreatedAt { get; set; }
    public DateTime? StartedAt { get; set; }
    public DateTime? CompletedAt { get; set; }
    public List<Variant> Variants { get; set; } = new();
}

public class Variant
{
    public int Id { get; set; }
    public int ExperimentId { get; set; }
    public string Name { get; set; } = string.Empty;
    public string Description { get; set; } = string.Empty;
    public int TrafficPercentage { get; set; }
    public DateTime CreatedAt { get; set; }
}

public class Result
{
    public int Id { get; set; }
    public int ExperimentId { get; set; }
    public int VariantId { get; set; }
    public bool Converted { get; set; }
    public DateTime RecordedAt { get; set; }
}

public class CreateExperimentRequest
{
    public string Name { get; set; } = string.Empty;
    public string Description { get; set; } = string.Empty;
    public string Hypothesis { get; set; } = string.Empty;
}

public class UpdateExperimentRequest
{
    public string Name { get; set; } = string.Empty;
    public string Description { get; set; } = string.Empty;
    public string Status { get; set; } = string.Empty;
}

public class CreateVariantRequest
{
    public string Name { get; set; } = string.Empty;
    public string Description { get; set; } = string.Empty;
    public int TrafficPercentage { get; set; }
}

public class RecordResultRequest
{
    public int VariantId { get; set; }
    public bool Converted { get; set; }
}

public class VariantStats
{
    public int VariantId { get; set; }
    public string VariantName { get; set; } = string.Empty;
    public int TotalViews { get; set; }
    public int TotalConversions { get; set; }
    public double ConversionRate { get; set; }
}

public class ExperimentStats
{
    public int ExperimentId { get; set; }
    public string ExperimentName { get; set; } = string.Empty;
    public List<VariantStats> VariantStats { get; set; } = new();
}