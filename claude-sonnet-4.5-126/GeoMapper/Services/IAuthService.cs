using GeoMapper.Models;

namespace GeoMapper.Services;

public interface IAuthService
{
    Task<User?> RegisterAsync(string username, string password);
    Task<User?> LoginAsync(string username, string password);
    string GenerateJwtToken(User user);
    Task<User?> GetUserByIdAsync(int userId);
}