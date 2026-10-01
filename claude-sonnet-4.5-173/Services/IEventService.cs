using EventFlow.Models;
using EventFlow.Models.DTOs;

namespace EventFlow.Services;

public interface IEventService
{
    Task<Event?> CreateEventAsync(CreateEventRequest request, int organizerId);
    Task<Event?> GetEventByIdAsync(int id);
    Task<List<Event>> GetAllEventsAsync();
    Task<List<Event>> GetEventsByOrganizerAsync(int organizerId);
    Task<Event?> UpdateEventAsync(int id, CreateEventRequest request, int organizerId);
    Task<bool> DeleteEventAsync(int id, int organizerId);
}