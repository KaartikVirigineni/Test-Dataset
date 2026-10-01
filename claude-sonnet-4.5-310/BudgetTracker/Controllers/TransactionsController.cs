using BudgetTracker.Models.DTOs;
using BudgetTracker.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;

namespace BudgetTracker.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize(Policy = "UserOrAdmin")]
public class TransactionsController : ControllerBase
{
    private readonly IBudgetService _budgetService;

    public TransactionsController(IBudgetService budgetService)
    {
        _budgetService = budgetService;
    }

    private int GetUserId()
    {
        var userIdClaim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
        return int.Parse(userIdClaim ?? "0");
    }

    [HttpGet]
    public async Task<IActionResult> GetTransactions()
    {
        var transactions = await _budgetService.GetUserTransactions(GetUserId());
        return Ok(transactions);
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetTransaction(int id)
    {
        var transaction = await _budgetService.GetTransactionById(id, GetUserId());
        if (transaction == null)
        {
            return NotFound();
        }

        return Ok(transaction);
    }

    [HttpPost]
    public async Task<IActionResult> CreateTransaction([FromBody] TransactionRequest request)
    {
        var transaction = await _budgetService.CreateTransaction(request, GetUserId());
        return CreatedAtAction(nameof(GetTransaction), new { id = transaction.Id }, transaction);
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateTransaction(int id, [FromBody] TransactionRequest request)
    {
        var transaction = await _budgetService.UpdateTransaction(id, request, GetUserId());
        if (transaction == null)
        {
            return NotFound();
        }

        return Ok(transaction);
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> DeleteTransaction(int id)
    {
        var result = await _budgetService.DeleteTransaction(id, GetUserId());
        if (!result)
        {
            return NotFound();
        }

        return NoContent();
    }
}