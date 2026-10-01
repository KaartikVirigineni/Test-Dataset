using ExpenseTracker.Data;
using ExpenseTracker.Models;
using ExpenseTracker.Models.DTOs;
using Microsoft.EntityFrameworkCore;

namespace ExpenseTracker.Services;

public class ExpenseService : IExpenseService
{
    private readonly AppDbContext _context;

    public ExpenseService(AppDbContext context)
    {
        _context = context;
    }

    public async Task<Expense> CreateExpenseAsync(int userId, CreateExpenseRequest request)
    {
        var expense = new Expense
        {
            Description = request.Description,
            Amount = request.Amount,
            Date = request.Date,
            Category = request.Category,
            UserId = userId
        };

        _context.Expenses.Add(expense);
        await _context.SaveChangesAsync();

        return expense;
    }

    public async Task<IEnumerable<Expense>> GetUserExpensesAsync(int userId)
    {
        return await _context.Expenses
            .Where(e => e.UserId == userId)
            .OrderByDescending(e => e.Date)
            .ToListAsync();
    }

    public async Task<Expense?> GetExpenseByIdAsync(int expenseId, int userId)
    {
        return await _context.Expenses
            .FirstOrDefaultAsync(e => e.Id == expenseId && e.UserId == userId);
    }

    public async Task<Expense?> UpdateExpenseAsync(int expenseId, int userId, UpdateExpenseRequest request)
    {
        var expense = await _context.Expenses
            .FirstOrDefaultAsync(e => e.Id == expenseId && e.UserId == userId);

        if (expense == null)
        {
            return null;
        }

        if (request.Description != null)
            expense.Description = request.Description;

        if (request.Amount.HasValue)
            expense.Amount = request.Amount.Value;

        if (request.Date.HasValue)
            expense.Date = request.Date.Value;

        if (request.Category != null)
            expense.Category = request.Category;

        await _context.SaveChangesAsync();

        return expense;
    }

    public async Task<bool> DeleteExpenseAsync(int expenseId, int userId)
    {
        var expense = await _context.Expenses
            .FirstOrDefaultAsync(e => e.Id == expenseId && e.UserId == userId);

        if (expense == null)
        {
            return false;
        }

        _context.Expenses.Remove(expense);
        await _context.SaveChangesAsync();

        return true;
    }
}