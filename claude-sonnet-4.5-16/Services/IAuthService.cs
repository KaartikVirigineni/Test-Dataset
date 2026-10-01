using WarehouseHub.Models;

namespace WarehouseHub.Services;

public interface IAuthService
{
    Task<string?> AuthenticateAsync(string email, string password);
    Task<bool> RegisterAsync(string email, string password, string? firstName, string? lastName);
    Task<ApplicationUser?> GetUserAsync(string userId);
}