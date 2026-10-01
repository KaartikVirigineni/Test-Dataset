using EventFlow.Models.DTOs;
using EventFlow.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;

namespace EventFlow.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class TicketsController : ControllerBase
{
    private readonly ITicketService _ticketService;

    public TicketsController(ITicketService ticketService)
    {
        _ticketService = ticketService;
    }

    [HttpGet("me")]
    public async Task<IActionResult> GetMyTickets()
    {
        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var tickets = await _ticketService.GetUserTicketsAsync(userId);
        return Ok(tickets);
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetTicketById(int id)
    {
        var ticket = await _ticketService.GetTicketByIdAsync(id);
        
        if (ticket == null)
        {
            return NotFound(new { message = "Ticket not found" });
        }

        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        
        if (ticket.UserId != userId)
        {
            return Forbid();
        }

        return Ok(ticket);
    }

    [HttpGet("event/{eventId}")]
    public async Task<IActionResult> GetEventTickets(int eventId)
    {
        var tickets = await _ticketService.GetEventTicketsAsync(eventId);
        return Ok(tickets);
    }

    [HttpPost("purchase")]
    public async Task<IActionResult> PurchaseTicket([FromBody] PurchaseTicketRequest request)
    {
        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var ticket = await _ticketService.PurchaseTicketAsync(request, userId);

        if (ticket == null)
        {
            return BadRequest(new { message = "Unable to purchase ticket. Event not found or no tickets available." });
        }

        return CreatedAtAction(nameof(GetTicketById), new { id = ticket.Id }, ticket);
    }

    [HttpPost("{id}/cancel")]
    public async Task<IActionResult> CancelTicket(int id)
    {
        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var result = await _ticketService.CancelTicketAsync(id, userId);

        if (!result)
        {
            return BadRequest(new { message = "Unable to cancel ticket. Ticket not found, already cancelled, or unauthorized." });
        }

        return Ok(new { message = "Ticket cancelled successfully" });
    }
}