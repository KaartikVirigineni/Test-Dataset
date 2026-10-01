using LeadScorePro.Models.DTOs;

namespace LeadScorePro.Services;

public interface IAuthService
{
    Task<LoginResponse?> LoginAsync(LoginRequest request);
    Task<bool> ValidateTokenAsync(string token);
}