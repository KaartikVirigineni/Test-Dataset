namespace LeadScorePro.Models;

public class Lead
{
    public int Id { get; set; }
    public string FirstName { get; set; } = string.Empty;
    public string LastName { get; set; } = string.Empty;
    public string Email { get; set; } = string.Empty;
    public string? Company { get; set; }
    public string? Phone { get; set; }
    public int Score { get; set; } = 0;
    public string Status { get; set; } = "New";
    public string? Source { get; set; }
    public DateTime CreatedAt { get; set; }
    public DateTime? LastContactedAt { get; set; }
    public ICollection<Activity> Activities { get; set; } = new List<Activity>();
}