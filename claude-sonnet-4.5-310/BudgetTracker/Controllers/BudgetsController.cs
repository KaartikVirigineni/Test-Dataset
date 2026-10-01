using BudgetTracker.Models.DTOs;
using BudgetTracker.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;

namespace BudgetTracker.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize(Policy = "UserOrAdmin")]
public class BudgetsController : ControllerBase
{
    private readonly IBudgetService _budgetService;

    public BudgetsController(IBudgetService budgetService)
    {
        _budgetService = budgetService;
    }

    private int GetUserId()
    {
        var userIdClaim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
        return int.Parse(userIdClaim ?? "0");
    }

    [HttpGet]
    public async Task<IActionResult> GetBudgets()
    {
        var budgets = await _budgetService.GetUserBudgets(GetUserId());
        return Ok(budgets);
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetBudget(int id)
    {
        var budget = await _budgetService.GetBudgetById(id, GetUserId());
        if (budget == null)
        {
            return NotFound();
        }

        return Ok(budget);
    }

    [HttpPost]
    public async Task<IActionResult> CreateBudget([FromBody] BudgetRequest request)
    {
        var budget = await _budgetService.CreateBudget(request, GetUserId());
        return CreatedAtAction(nameof(GetBudget), new { id = budget.Id }, budget);
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateBudget(int id, [FromBody] BudgetRequest request)
    {
        var budget = await _budgetService.UpdateBudget(id, request, GetUserId());
        if (budget == null)
        {
            return NotFound();
        }

        return Ok(budget);
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> DeleteBudget(int id)
    {
        var result = await _budgetService.DeleteBudget(id, GetUserId());
        if (!result)
        {
            return NotFound();
        }

        return NoContent();
    }
}