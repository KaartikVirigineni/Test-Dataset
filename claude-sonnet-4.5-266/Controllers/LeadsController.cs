using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using LeadScorePro.Models.DTOs;
using LeadScorePro.Services;

namespace LeadScorePro.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class LeadsController : ControllerBase
{
    private readonly ILeadService _leadService;

    public LeadsController(ILeadService leadService)
    {
        _leadService = leadService;
    }

    [HttpGet]
    [Authorize(Policy = "User")]
    public async Task<IActionResult> GetAll()
    {
        var leads = await _leadService.GetAllLeadsAsync();
        return Ok(leads);
    }

    [HttpGet("{id}")]
    [Authorize(Policy = "User")]
    public async Task<IActionResult> GetById(int id)
    {
        var lead = await _leadService.GetLeadByIdAsync(id);
        
        if (lead == null)
        {
            return NotFound(new { message = "Lead not found" });
        }

        return Ok(lead);
    }

    [HttpPost]
    [Authorize(Policy = "User")]
    public async Task<IActionResult> Create([FromBody] CreateLeadRequest request)
    {
        var lead = await _leadService.CreateLeadAsync(request);
        return CreatedAtAction(nameof(GetById), new { id = lead.Id }, lead);
    }

    [HttpPut("{id}")]
    [Authorize(Policy = "Manager")]
    public async Task<IActionResult> Update(int id, [FromBody] UpdateLeadRequest request)
    {
        var lead = await _leadService.UpdateLeadAsync(id, request);
        
        if (lead == null)
        {
            return NotFound(new { message = "Lead not found" });
        }

        return Ok(lead);
    }

    [HttpDelete("{id}")]
    [Authorize(Policy = "Admin")]
    public async Task<IActionResult> Delete(int id)
    {
        var result = await _leadService.DeleteLeadAsync(id);
        
        if (!result)
        {
            return NotFound(new { message = "Lead not found" });
        }

        return NoContent();
    }

    [HttpPost("{id}/activities")]
    [Authorize(Policy = "User")]
    public async Task<IActionResult> AddActivity(int id, [FromBody] CreateActivityRequest request)
    {
        var activity = await _leadService.AddActivityAsync(id, request);
        
        if (activity == null)
        {
            return NotFound(new { message = "Lead not found" });
        }

        return CreatedAtAction(nameof(GetActivities), new { id }, activity);
    }

    [HttpGet("{id}/activities")]
    [Authorize(Policy = "User")]
    public async Task<IActionResult> GetActivities(int id)
    {
        var activities = await _leadService.GetLeadActivitiesAsync(id);
        return Ok(activities);
    }
}