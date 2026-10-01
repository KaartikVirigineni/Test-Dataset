namespace BudgetTracker.Models.DTOs;

public class TransactionRequest
{
    public int CategoryId { get; set; }
    public decimal Amount { get; set; }
    public string Type { get; set; } = "Expense";
    public string Description { get; set; } = string.Empty;
    public DateTime TransactionDate { get; set; } = DateTime.UtcNow;
}