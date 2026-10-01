using BudgetTracker.Models;
using BudgetTracker.Models.DTOs;

namespace BudgetTracker.Services;

public interface IBudgetService
{
    Task<IEnumerable<Budget>> GetUserBudgets(int userId);
    Task<Budget?> GetBudgetById(int id, int userId);
    Task<Budget> CreateBudget(BudgetRequest request, int userId);
    Task<Budget?> UpdateBudget(int id, BudgetRequest request, int userId);
    Task<bool> DeleteBudget(int id, int userId);
    Task<IEnumerable<Transaction>> GetUserTransactions(int userId);
    Task<Transaction?> GetTransactionById(int id, int userId);
    Task<Transaction> CreateTransaction(TransactionRequest request, int userId);
    Task<Transaction?> UpdateTransaction(int id, TransactionRequest request, int userId);
    Task<bool> DeleteTransaction(int id, int userId);
    Task<IEnumerable<Category>> GetCategories();
}