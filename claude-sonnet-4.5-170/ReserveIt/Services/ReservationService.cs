using Microsoft.EntityFrameworkCore;
using ReserveIt.Data;
using ReserveIt.Models;

namespace ReserveIt.Services;

public class ReservationService : IReservationService
{
    private readonly AppDbContext _context;

    public ReservationService(AppDbContext context)
    {
        _context = context;
    }

    public async Task<ReservationResponse?> CreateReservation(int userId, CreateReservationRequest request)
    {
        var table = await _context.Tables.FindAsync(request.TableId);
        if (table == null || !table.IsAvailable)
        {
            return null;
        }

        if (request.PartySize > table.Capacity)
        {
            return null;
        }

        var conflictingReservation = await _context.Reservations
            .Where(r => r.TableId == request.TableId
                && r.Status == "Confirmed"
                && r.ReservationDate < request.ReservationDate.AddMinutes(request.DurationMinutes)
                && request.ReservationDate < r.ReservationDate.AddMinutes(r.DurationMinutes))
            .AnyAsync();

        if (conflictingReservation)
        {
            return null;
        }

        var reservation = new Reservation
        {
            UserId = userId,
            TableId = request.TableId,
            ReservationDate = request.ReservationDate,
            DurationMinutes = request.DurationMinutes,
            PartySize = request.PartySize,
            SpecialRequests = request.SpecialRequests,
            Status = "Confirmed"
        };

        _context.Reservations.Add(reservation);
        await _context.SaveChangesAsync();

        return await GetReservation(reservation.Id);
    }

    public async Task<ReservationResponse?> GetReservation(int id)
    {
        var reservation = await _context.Reservations
            .Include(r => r.User)
            .Include(r => r.Table)
            .FirstOrDefaultAsync(r => r.Id == id);

        if (reservation == null)
        {
            return null;
        }

        return MapToResponse(reservation);
    }

    public async Task<List<ReservationResponse>> GetUserReservations(int userId)
    {
        var reservations = await _context.Reservations
            .Include(r => r.User)
            .Include(r => r.Table)
            .Where(r => r.UserId == userId)
            .OrderByDescending(r => r.ReservationDate)
            .ToListAsync();

        return reservations.Select(MapToResponse).ToList();
    }

    public async Task<List<ReservationResponse>> GetAllReservations()
    {
        var reservations = await _context.Reservations
            .Include(r => r.User)
            .Include(r => r.Table)
            .OrderByDescending(r => r.ReservationDate)
            .ToListAsync();

        return reservations.Select(MapToResponse).ToList();
    }

    public async Task<ReservationResponse?> UpdateReservation(int id, int userId, string userRole, UpdateReservationRequest request)
    {
        var reservation = await _context.Reservations
            .Include(r => r.User)
            .Include(r => r.Table)
            .FirstOrDefaultAsync(r => r.Id == id);

        if (reservation == null)
        {
            return null;
        }

        if (reservation.UserId != userId && userRole != "Admin")
        {
            return null;
        }

        if (request.ReservationDate.HasValue)
        {
            reservation.ReservationDate = request.ReservationDate.Value;
        }

        if (request.DurationMinutes.HasValue)
        {
            reservation.DurationMinutes = request.DurationMinutes.Value;
        }

        if (request.PartySize.HasValue)
        {
            reservation.PartySize = request.PartySize.Value;
        }

        if (request.Status != null)
        {
            reservation.Status = request.Status;
        }

        if (request.SpecialRequests != null)
        {
            reservation.SpecialRequests = request.SpecialRequests;
        }

        await _context.SaveChangesAsync();
        return MapToResponse(reservation);
    }

    public async Task<bool> DeleteReservation(int id, int userId, string userRole)
    {
        var reservation = await _context.Reservations.FindAsync(id);
        if (reservation == null)
        {
            return false;
        }

        if (reservation.UserId != userId && userRole != "Admin")
        {
            return false;
        }

        _context.Reservations.Remove(reservation);
        await _context.SaveChangesAsync();
        return true;
    }

    private ReservationResponse MapToResponse(Reservation reservation)
    {
        return new ReservationResponse
        {
            Id = reservation.Id,
            UserId = reservation.UserId,
            UserName = reservation.User?.Name ?? string.Empty,
            TableId = reservation.TableId,
            TableNumber = reservation.Table?.TableNumber ?? string.Empty,
            ReservationDate = reservation.ReservationDate,
            DurationMinutes = reservation.DurationMinutes,
            PartySize = reservation.PartySize,
            Status = reservation.Status,
            SpecialRequests = reservation.SpecialRequests,
            CreatedAt = reservation.CreatedAt
        };
    }
}