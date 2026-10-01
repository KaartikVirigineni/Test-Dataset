using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using DevPortal.Data;
using DevPortal.Models;

namespace DevPortal.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class ResourcesController : ControllerBase
{
    private readonly ApplicationDbContext _context;

    public ResourcesController(ApplicationDbContext context)
    {
        _context = context;
    }

    [HttpGet]
    public async Task<ActionResult<IEnumerable<Resource>>> GetResources()
    {
        return await _context.Resources.ToListAsync();
    }

    [HttpGet("{id}")]
    public async Task<ActionResult<Resource>> GetResource(int id)
    {
        var resource = await _context.Resources.FindAsync(id);

        if (resource == null)
        {
            return NotFound();
        }

        return resource;
    }

    [HttpPost]
    public async Task<ActionResult<Resource>> CreateResource([FromBody] CreateResourceRequest request)
    {
        var resource = new Resource
        {
            Title = request.Title,
            Description = request.Description,
            Url = request.Url,
            Category = request.Category,
            CreatedAt = DateTime.UtcNow,
            UpdatedAt = DateTime.UtcNow
        };

        _context.Resources.Add(resource);
        await _context.SaveChangesAsync();

        return CreatedAtAction(nameof(GetResource), new { id = resource.Id }, resource);
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateResource(int id, [FromBody] UpdateResourceRequest request)
    {
        var resource = await _context.Resources.FindAsync(id);

        if (resource == null)
        {
            return NotFound();
        }

        if (!string.IsNullOrEmpty(request.Title))
            resource.Title = request.Title;
        if (!string.IsNullOrEmpty(request.Description))
            resource.Description = request.Description;
        if (request.Url != null)
            resource.Url = request.Url;
        if (request.Category != null)
            resource.Category = request.Category;

        resource.UpdatedAt = DateTime.UtcNow;

        await _context.SaveChangesAsync();

        return Ok(resource);
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> DeleteResource(int id)
    {
        var resource = await _context.Resources.FindAsync(id);

        if (resource == null)
        {
            return NotFound();
        }

        _context.Resources.Remove(resource);
        await _context.SaveChangesAsync();

        return NoContent();
    }
}