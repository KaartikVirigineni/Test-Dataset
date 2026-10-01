using ReserveIt.Models;

namespace ReserveIt.Services;

public interface IAuthService
{
    Task<AuthResponse?> Login(LoginRequest request);
    Task<AuthResponse?> Register(RegisterRequest request);
    Task<User?> GetUserByEmail(string email);
}