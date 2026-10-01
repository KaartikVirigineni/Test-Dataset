using DevPortal.Models;
using DevPortal.Models.DTOs;

namespace DevPortal.Services;

public interface IAuthService
{
    Task<LoginResponse?> Login(LoginRequest request);
    Task<User?> Register(RegisterRequest request);
    Task SeedDefaultUsers();
    string GenerateToken(User user);
}