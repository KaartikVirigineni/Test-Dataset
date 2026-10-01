using EventFlow.Data;
using EventFlow.Models;
using EventFlow.Models.DTOs;
using Microsoft.EntityFrameworkCore;

namespace EventFlow.Services;

public class EventService : IEventService
{
    private readonly AppDbContext _context;

    public EventService(AppDbContext context)
    {
        _context = context;
    }

    public async Task<Event?> CreateEventAsync(CreateEventRequest request, int organizerId)
    {
        var eventEntity = new Event
        {
            Name = request.Name,
            Description = request.Description,
            Location = request.Location,
            StartDate = request.StartDate,
            EndDate = request.EndDate,
            TotalTickets = request.TotalTickets,
            AvailableTickets = request.TotalTickets,
            Price = request.Price,
            OrganizerId = organizerId,
            CreatedAt = DateTime.UtcNow
        };

        _context.Events.Add(eventEntity);
        await _context.SaveChangesAsync();

        return eventEntity;
    }

    public async Task<Event?> GetEventByIdAsync(int id)
    {
        return await _context.Events
            .Include(e => e.Organizer)
            .FirstOrDefaultAsync(e => e.Id == id);
    }

    public async Task<List<Event>> GetAllEventsAsync()
    {
        return await _context.Events
            .Include(e => e.Organizer)
            .OrderByDescending(e => e.CreatedAt)
            .ToListAsync();
    }

    public async Task<List<Event>> GetEventsByOrganizerAsync(int organizerId)
    {
        return await _context.Events
            .Where(e => e.OrganizerId == organizerId)
            .OrderByDescending(e => e.CreatedAt)
            .ToListAsync();
    }

    public async Task<Event?> UpdateEventAsync(int id, CreateEventRequest request, int organizerId)
    {
        var eventEntity = await _context.Events.FindAsync(id);
        
        if (eventEntity == null || eventEntity.OrganizerId != organizerId)
        {
            return null;
        }

        eventEntity.Name = request.Name;
        eventEntity.Description = request.Description;
        eventEntity.Location = request.Location;
        eventEntity.StartDate = request.StartDate;
        eventEntity.EndDate = request.EndDate;
        eventEntity.Price = request.Price;

        if (request.TotalTickets != eventEntity.TotalTickets)
        {
            var soldTickets = eventEntity.TotalTickets - eventEntity.AvailableTickets;
            eventEntity.TotalTickets = request.TotalTickets;
            eventEntity.AvailableTickets = Math.Max(0, request.TotalTickets - soldTickets);
        }

        await _context.SaveChangesAsync();

        return eventEntity;
    }

    public async Task<bool> DeleteEventAsync(int id, int organizerId)
    {
        var eventEntity = await _context.Events.FindAsync(id);
        
        if (eventEntity == null || eventEntity.OrganizerId != organizerId)
        {
            return false;
        }

        _context.Events.Remove(eventEntity);
        await _context.SaveChangesAsync();

        return true;
    }
}