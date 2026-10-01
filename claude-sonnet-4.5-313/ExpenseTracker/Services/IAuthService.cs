using ExpenseTracker.Models;
using ExpenseTracker.Models.DTOs;

namespace ExpenseTracker.Services;

public interface IAuthService
{
    Task<User?> RegisterAsync(RegisterRequest request);
    Task<LoginResponse?> LoginAsync(LoginRequest request);
    Task<User?> GetUserByIdAsync(int userId);
}