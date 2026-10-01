using EventFlow.Models.DTOs;
using EventFlow.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;

namespace EventFlow.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class EventsController : ControllerBase
{
    private readonly IEventService _eventService;

    public EventsController(IEventService eventService)
    {
        _eventService = eventService;
    }

    [HttpGet]
    [AllowAnonymous]
    public async Task<IActionResult> GetAllEvents()
    {
        var events = await _eventService.GetAllEventsAsync();
        return Ok(events);
    }

    [HttpGet("{id}")]
    [AllowAnonymous]
    public async Task<IActionResult> GetEventById(int id)
    {
        var eventEntity = await _eventService.GetEventByIdAsync(id);
        
        if (eventEntity == null)
        {
            return NotFound(new { message = "Event not found" });
        }

        return Ok(eventEntity);
    }

    [HttpGet("organizer/me")]
    public async Task<IActionResult> GetMyEvents()
    {
        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var events = await _eventService.GetEventsByOrganizerAsync(userId);
        return Ok(events);
    }

    [HttpPost]
    public async Task<IActionResult> CreateEvent([FromBody] CreateEventRequest request)
    {
        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var eventEntity = await _eventService.CreateEventAsync(request, userId);

        return CreatedAtAction(nameof(GetEventById), new { id = eventEntity!.Id }, eventEntity);
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateEvent(int id, [FromBody] CreateEventRequest request)
    {
        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var eventEntity = await _eventService.UpdateEventAsync(id, request, userId);

        if (eventEntity == null)
        {
            return NotFound(new { message = "Event not found or unauthorized" });
        }

        return Ok(eventEntity);
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> DeleteEvent(int id)
    {
        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var result = await _eventService.DeleteEventAsync(id, userId);

        if (!result)
        {
            return NotFound(new { message = "Event not found or unauthorized" });
        }

        return NoContent();
    }
}