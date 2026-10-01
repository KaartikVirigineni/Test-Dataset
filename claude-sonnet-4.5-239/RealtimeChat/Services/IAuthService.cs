using RealtimeChat.Models;

namespace RealtimeChat.Services;

public interface IAuthService
{
    Task<LoginResponse?> LoginAsync(LoginRequest request);
    Task<LoginResponse?> RegisterAsync(RegisterRequest request);
    Task SeedDefaultUsers();
    string GenerateJwtToken(User user);
}