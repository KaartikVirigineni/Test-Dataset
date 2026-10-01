using ReserveIt.Models;

namespace ReserveIt.Services;

public interface IReservationService
{
    Task<ReservationResponse?> CreateReservation(int userId, CreateReservationRequest request);
    Task<ReservationResponse?> GetReservation(int id);
    Task<List<ReservationResponse>> GetUserReservations(int userId);
    Task<List<ReservationResponse>> GetAllReservations();
    Task<ReservationResponse?> UpdateReservation(int id, int userId, string userRole, UpdateReservationRequest request);
    Task<bool> DeleteReservation(int id, int userId, string userRole);
}