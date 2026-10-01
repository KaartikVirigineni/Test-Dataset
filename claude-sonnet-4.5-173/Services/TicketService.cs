using EventFlow.Data;
using EventFlow.Models;
using EventFlow.Models.DTOs;
using Microsoft.EntityFrameworkCore;

namespace EventFlow.Services;

public class TicketService : ITicketService
{
    private readonly AppDbContext _context;

    public TicketService(AppDbContext context)
    {
        _context = context;
    }

    public async Task<Ticket?> PurchaseTicketAsync(PurchaseTicketRequest request, int userId)
    {
        var eventEntity = await _context.Events.FindAsync(request.EventId);
        
        if (eventEntity == null || eventEntity.AvailableTickets <= 0)
        {
            return null;
        }

        var ticket = new Ticket
        {
            TicketNumber = GenerateTicketNumber(),
            EventId = request.EventId,
            UserId = userId,
            PurchaseDate = DateTime.UtcNow,
            Status = "Active"
        };

        eventEntity.AvailableTickets--;

        _context.Tickets.Add(ticket);
        await _context.SaveChangesAsync();

        return ticket;
    }

    public async Task<Ticket?> GetTicketByIdAsync(int id)
    {
        return await _context.Tickets
            .Include(t => t.Event)
            .Include(t => t.User)
            .FirstOrDefaultAsync(t => t.Id == id);
    }

    public async Task<List<Ticket>> GetUserTicketsAsync(int userId)
    {
        return await _context.Tickets
            .Include(t => t.Event)
            .Where(t => t.UserId == userId)
            .OrderByDescending(t => t.PurchaseDate)
            .ToListAsync();
    }

    public async Task<List<Ticket>> GetEventTicketsAsync(int eventId)
    {
        return await _context.Tickets
            .Include(t => t.User)
            .Where(t => t.EventId == eventId)
            .OrderByDescending(t => t.PurchaseDate)
            .ToListAsync();
    }

    public async Task<bool> CancelTicketAsync(int id, int userId)
    {
        var ticket = await _context.Tickets
            .Include(t => t.Event)
            .FirstOrDefaultAsync(t => t.Id == id);
        
        if (ticket == null || ticket.UserId != userId || ticket.Status == "Cancelled")
        {
            return false;
        }

        ticket.Status = "Cancelled";
        
        if (ticket.Event != null)
        {
            ticket.Event.AvailableTickets++;
        }

        await _context.SaveChangesAsync();

        return true;
    }

    private string GenerateTicketNumber()
    {
        return $"TKT-{DateTime.UtcNow:yyyyMMddHHmmss}-{Guid.NewGuid().ToString()[..8].ToUpper()}";
    }
}