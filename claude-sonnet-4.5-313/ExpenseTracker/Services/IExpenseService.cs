using ExpenseTracker.Models;
using ExpenseTracker.Models.DTOs;

namespace ExpenseTracker.Services;

public interface IExpenseService
{
    Task<Expense> CreateExpenseAsync(int userId, CreateExpenseRequest request);
    Task<IEnumerable<Expense>> GetUserExpensesAsync(int userId);
    Task<Expense?> GetExpenseByIdAsync(int expenseId, int userId);
    Task<Expense?> UpdateExpenseAsync(int expenseId, int userId, UpdateExpenseRequest request);
    Task<bool> DeleteExpenseAsync(int expenseId, int userId);
}