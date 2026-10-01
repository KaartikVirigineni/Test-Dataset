using ImageGallery.Models;

namespace ImageGallery.Services;

public interface IAuthService
{
    Task<User?> RegisterAsync(string username, string password);
    Task<User?> AuthenticateAsync(string username, string password);
    string GenerateJwtToken(User user);
}