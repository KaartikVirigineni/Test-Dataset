using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;
using DevPortal.Models.DTOs;
using DevPortal.Services;

namespace DevPortal.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize(Policy = "Developer")]
public class ProjectsController : ControllerBase
{
    private readonly IProjectService _projectService;

    public ProjectsController(IProjectService projectService)
    {
        _projectService = projectService;
    }

    private int GetUserId()
    {
        var userIdClaim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
        return int.Parse(userIdClaim ?? "0");
    }

    [HttpPost]
    public async Task<IActionResult> CreateProject([FromBody] CreateProjectRequest request)
    {
        var userId = GetUserId();
        var project = await _projectService.CreateProject(request, userId);
        
        if (project == null)
        {
            return BadRequest(new { message = "Failed to create project" });
        }

        return CreatedAtAction(nameof(GetProject), new { id = project.Id }, project);
    }

    [HttpGet]
    public async Task<IActionResult> GetProjects()
    {
        var userId = GetUserId();
        var projects = await _projectService.GetUserProjects(userId);
        return Ok(projects);
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetProject(int id)
    {
        var userId = GetUserId();
        var project = await _projectService.GetProject(id, userId);
        
        if (project == null)
        {
            return NotFound(new { message = "Project not found" });
        }

        return Ok(project);
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> DeleteProject(int id)
    {
        var userId = GetUserId();
        var result = await _projectService.DeleteProject(id, userId);
        
        if (!result)
        {
            return NotFound(new { message = "Project not found" });
        }

        return NoContent();
    }
}