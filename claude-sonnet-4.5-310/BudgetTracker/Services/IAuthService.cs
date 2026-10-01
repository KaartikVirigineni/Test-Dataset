using BudgetTracker.Models;
using BudgetTracker.Models.DTOs;

namespace BudgetTracker.Services;

public interface IAuthService
{
    Task<AuthResponse?> Login(LoginRequest request);
    Task<AuthResponse?> Register(RegisterRequest request);
    Task SeedDefaultUsers();
    Task<User?> GetUserById(int id);
}