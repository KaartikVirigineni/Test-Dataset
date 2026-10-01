using EventFlow.Models;
using EventFlow.Models.DTOs;

namespace EventFlow.Services;

public interface ITicketService
{
    Task<Ticket?> PurchaseTicketAsync(PurchaseTicketRequest request, int userId);
    Task<Ticket?> GetTicketByIdAsync(int id);
    Task<List<Ticket>> GetUserTicketsAsync(int userId);
    Task<List<Ticket>> GetEventTicketsAsync(int eventId);
    Task<bool> CancelTicketAsync(int id, int userId);
}