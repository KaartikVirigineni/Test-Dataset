namespace FreelanceHub.Models;

public class Bid
{
    public int Id { get; set; }
    public int ProjectId { get; set; }
    public Project Project { get; set; } = null!;
    public int BidderId { get; set; }
    public User Bidder { get; set; } = null!;
    public decimal Amount { get; set; }
    public string Proposal { get; set; } = string.Empty;
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
}