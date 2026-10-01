using ExperimentHub.Models;
using ExperimentHub.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;

namespace ExperimentHub.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class ExperimentsController : ControllerBase
{
    private readonly ExperimentService _experimentService;

    public ExperimentsController(ExperimentService experimentService)
    {
        _experimentService = experimentService;
    }

    private int GetUserId()
    {
        var userIdClaim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
        return int.Parse(userIdClaim ?? "0");
    }

    [HttpGet]
    public IActionResult GetAll()
    {
        var userId = GetUserId();
        var experiments = _experimentService.GetUserExperiments(userId);
        return Ok(experiments);
    }

    [HttpGet("{id}")]
    public IActionResult GetById(int id)
    {
        var userId = GetUserId();
        var experiment = _experimentService.GetExperiment(id, userId);
        
        if (experiment == null)
            return NotFound(new { message = "Experiment not found" });
            
        return Ok(experiment);
    }

    [HttpPost]
    public IActionResult Create([FromBody] CreateExperimentRequest request)
    {
        var userId = GetUserId();
        var experiment = _experimentService.CreateExperiment(
            userId,
            request.Name,
            request.Description,
            request.Hypothesis
        );
        return CreatedAtAction(nameof(GetById), new { id = experiment.Id }, experiment);
    }

    [HttpPut("{id}")]
    public IActionResult Update(int id, [FromBody] UpdateExperimentRequest request)
    {
        var userId = GetUserId();
        try
        {
            var experiment = _experimentService.UpdateExperiment(id, userId, request.Name, request.Description, request.Status);
            return Ok(experiment);
        }
        catch (KeyNotFoundException)
        {
            return NotFound(new { message = "Experiment not found" });
        }
    }

    [HttpDelete("{id}")]
    public IActionResult Delete(int id)
    {
        var userId = GetUserId();
        try
        {
            _experimentService.DeleteExperiment(id, userId);
            return NoContent();
        }
        catch (KeyNotFoundException)
        {
            return NotFound(new { message = "Experiment not found" });
        }
    }

    [HttpPost("{id}/variants")]
    public IActionResult AddVariant(int id, [FromBody] CreateVariantRequest request)
    {
        var userId = GetUserId();
        try
        {
            var variant = _experimentService.AddVariant(id, userId, request.Name, request.Description, request.TrafficPercentage);
            return Ok(variant);
        }
        catch (KeyNotFoundException)
        {
            return NotFound(new { message = "Experiment not found" });
        }
        catch (InvalidOperationException ex)
        {
            return BadRequest(new { message = ex.Message });
        }
    }

    [HttpPost("{id}/results")]
    public IActionResult RecordResult(int id, [FromBody] RecordResultRequest request)
    {
        var userId = GetUserId();
        try
        {
            var result = _experimentService.RecordResult(id, userId, request.VariantId, request.Converted);
            return Ok(result);
        }
        catch (KeyNotFoundException)
        {
            return NotFound(new { message = "Experiment or variant not found" });
        }
    }

    [HttpGet("{id}/stats")]
    public IActionResult GetStats(int id)
    {
        var userId = GetUserId();
        try
        {
            var stats = _experimentService.GetExperimentStats(id, userId);
            return Ok(stats);
        }
        catch (KeyNotFoundException)
        {
            return NotFound(new { message = "Experiment not found" });
        }
    }
}