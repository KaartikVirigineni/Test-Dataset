namespace LeadScorePro.Models;

public class Activity
{
    public int Id { get; set; }
    public int LeadId { get; set; }
    public Lead? Lead { get; set; }
    public string Type { get; set; } = string.Empty;
    public string Description { get; set; } = string.Empty;
    public int ScoreImpact { get; set; } = 0;
    public DateTime CreatedAt { get; set; }
}