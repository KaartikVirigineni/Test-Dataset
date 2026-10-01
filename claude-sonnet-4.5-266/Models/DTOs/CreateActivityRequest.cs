namespace LeadScorePro.Models.DTOs;

public class CreateActivityRequest
{
    public string Type { get; set; } = string.Empty;
    public string Description { get; set; } = string.Empty;
    public int ScoreImpact { get; set; } = 0;
}