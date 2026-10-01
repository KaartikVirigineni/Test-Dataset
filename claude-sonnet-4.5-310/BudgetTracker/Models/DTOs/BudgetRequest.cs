namespace BudgetTracker.Models.DTOs;

public class BudgetRequest
{
    public int CategoryId { get; set; }
    public decimal Amount { get; set; }
    public string Period { get; set; } = "Monthly";
    public DateTime StartDate { get; set; }
    public DateTime? EndDate { get; set; }
}