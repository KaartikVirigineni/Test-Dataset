using LogViewLite.Models.DTOs;
using LogViewLite.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace LogViewLite.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize(Policy = "ViewerOrAbove")]
public class LogsController : ControllerBase
{
    private readonly ILogService _logService;

    public LogsController(ILogService logService)
    {
        _logService = logService;
    }

    [HttpPost]
    [Authorize(Policy = "AdminOnly")]
    public async Task<IActionResult> CreateLog([FromBody] CreateLogRequest request)
    {
        var log = await _logService.CreateLogAsync(request);
        return CreatedAtAction(nameof(GetLog), new { id = log.Id }, log);
    }

    [HttpPost("query")]
    public async Task<IActionResult> QueryLogs([FromBody] LogQueryRequest request)
    {
        var (logs, total) = await _logService.QueryLogsAsync(request);
        return Ok(new
        {
            logs,
            total,
            page = request.Page,
            pageSize = request.PageSize,
            totalPages = (int)Math.Ceiling(total / (double)request.PageSize)
        });
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetLog(int id)
    {
        var log = await _logService.GetLogByIdAsync(id);
        if (log == null)
        {
            return NotFound();
        }
        return Ok(log);
    }

    [HttpDelete("{id}")]
    [Authorize(Policy = "AdminOnly")]
    public async Task<IActionResult> DeleteLog(int id)
    {
        await _logService.DeleteLogAsync(id);
        return NoContent();
    }

    [HttpGet("statistics")]
    public async Task<IActionResult> GetStatistics()
    {
        var stats = await _logService.GetLogStatisticsAsync();
        return Ok(stats);
    }
}