using LogViewLite.Models;
using LogViewLite.Models.DTOs;

namespace LogViewLite.Services;

public interface IAuthService
{
    Task<LoginResponse?> AuthenticateAsync(LoginRequest request);
    Task<User?> GetUserByUsernameAsync(string username);
    Task SeedDefaultUsersAsync();
}