using BudgetTracker.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace BudgetTracker.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize(Policy = "UserOrAdmin")]
public class CategoriesController : ControllerBase
{
    private readonly IBudgetService _budgetService;

    public CategoriesController(IBudgetService budgetService)
    {
        _budgetService = budgetService;
    }

    [HttpGet]
    public async Task<IActionResult> GetCategories()
    {
        var categories = await _budgetService.GetCategories();
        return Ok(categories);
    }
}