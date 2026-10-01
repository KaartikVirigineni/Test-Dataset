using BudgetTracker.Data;
using BudgetTracker.Models;
using BudgetTracker.Models.DTOs;
using Microsoft.EntityFrameworkCore;

namespace BudgetTracker.Services;

public class BudgetService : IBudgetService
{
    private readonly AppDbContext _context;

    public BudgetService(AppDbContext context)
    {
        _context = context;
    }

    public async Task<IEnumerable<Budget>> GetUserBudgets(int userId)
    {
        return await _context.Budgets
            .Include(b => b.Category)
            .Where(b => b.UserId == userId)
            .OrderByDescending(b => b.CreatedAt)
            .ToListAsync();
    }

    public async Task<Budget?> GetBudgetById(int id, int userId)
    {
        return await _context.Budgets
            .Include(b => b.Category)
            .FirstOrDefaultAsync(b => b.Id == id && b.UserId == userId);
    }

    public async Task<Budget> CreateBudget(BudgetRequest request, int userId)
    {
        var budget = new Budget
        {
            UserId = userId,
            CategoryId = request.CategoryId,
            Amount = request.Amount,
            Period = request.Period,
            StartDate = request.StartDate,
            EndDate = request.EndDate
        };

        _context.Budgets.Add(budget);
        await _context.SaveChangesAsync();

        return await GetBudgetById(budget.Id, userId) ?? budget;
    }

    public async Task<Budget?> UpdateBudget(int id, BudgetRequest request, int userId)
    {
        var budget = await _context.Budgets.FirstOrDefaultAsync(b => b.Id == id && b.UserId == userId);
        if (budget == null) return null;

        budget.CategoryId = request.CategoryId;
        budget.Amount = request.Amount;
        budget.Period = request.Period;
        budget.StartDate = request.StartDate;
        budget.EndDate = request.EndDate;

        await _context.SaveChangesAsync();
        return await GetBudgetById(budget.Id, userId);
    }

    public async Task<bool> DeleteBudget(int id, int userId)
    {
        var budget = await _context.Budgets.FirstOrDefaultAsync(b => b.Id == id && b.UserId == userId);
        if (budget == null) return false;

        _context.Budgets.Remove(budget);
        await _context.SaveChangesAsync();
        return true;
    }

    public async Task<IEnumerable<Transaction>> GetUserTransactions(int userId)
    {
        return await _context.Transactions
            .Include(t => t.Category)
            .Where(t => t.UserId == userId)
            .OrderByDescending(t => t.TransactionDate)
            .ToListAsync();
    }

    public async Task<Transaction?> GetTransactionById(int id, int userId)
    {
        return await _context.Transactions
            .Include(t => t.Category)
            .FirstOrDefaultAsync(t => t.Id == id && t.UserId == userId);
    }

    public async Task<Transaction> CreateTransaction(TransactionRequest request, int userId)
    {
        var transaction = new Transaction
        {
            UserId = userId,
            CategoryId = request.CategoryId,
            Amount = request.Amount,
            Type = request.Type,
            Description = request.Description,
            TransactionDate = request.TransactionDate
        };

        _context.Transactions.Add(transaction);
        await _context.SaveChangesAsync();

        return await GetTransactionById(transaction.Id, userId) ?? transaction;
    }

    public async Task<Transaction?> UpdateTransaction(int id, TransactionRequest request, int userId)
    {
        var transaction = await _context.Transactions.FirstOrDefaultAsync(t => t.Id == id && t.UserId == userId);
        if (transaction == null) return null;

        transaction.CategoryId = request.CategoryId;
        transaction.Amount = request.Amount;
        transaction.Type = request.Type;
        transaction.Description = request.Description;
        transaction.TransactionDate = request.TransactionDate;

        await _context.SaveChangesAsync();
        return await GetTransactionById(transaction.Id, userId);
    }

    public async Task<bool> DeleteTransaction(int id, int userId)
    {
        var transaction = await _context.Transactions.FirstOrDefaultAsync(t => t.Id == id && t.UserId == userId);
        if (transaction == null) return false;

        _context.Transactions.Remove(transaction);
        await _context.SaveChangesAsync();
        return true;
    }

    public async Task<IEnumerable<Category>> GetCategories()
    {
        return await _context.Categories.ToListAsync();
    }
}